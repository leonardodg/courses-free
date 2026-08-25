<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Recebe a autorizacao do vendedor e grava o token na conta de pagamento.
 *
 * Esta e a URL que precisa estar cadastrada no painel do Mercado Pago, com
 * correspondencia exata.
 *
 * @package    paygw_mercadopago
 * @copyright  2026 Leonardo Della Giustina
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__ . '/../../../config.php');

use paygw_mercadopago\mp_client;

$code = optional_param('code', '', PARAM_RAW_TRIMMED);
$state = optional_param('state', '', PARAM_RAW_TRIMMED);
$error = optional_param('error', '', PARAM_RAW_TRIMMED);

require_login();

$pending = $SESSION->paygw_mercadopago_oauth ?? null;
unset($SESSION->paygw_mercadopago_oauth);

// Confere o state ANTES de qualquer outra coisa. Sem isso o endpoint aceitaria
// um codigo de autorizacao de origem desconhecida e vincularia a conta de quem
// estivesse logado.
//
// A ausencia do code_verifier entra na mesma checagem: ou a sessao e de um
// fluxo iniciado antes do PKCE existir, ou nao veio daqui. Nos dois casos a
// troca falharia adiante - melhor recusar agora, com mensagem clara.
if (empty($pending) || empty($state) || !hash_equals($pending->state, $state)
        || empty($pending->codeverifier)) {
    throw new moodle_exception('errorstatemismatch', 'paygw_mercadopago');
}

$account = new \core_payment\account((int) $pending->accountid);
$context = $account->get_context();
require_capability('moodle/payment:manageaccounts', $context);

$returnurl = $account->get_edit_url();

// O vendedor pode ter recusado a autorizacao na tela do Mercado Pago.
if ($error !== '' || $code === '') {
    redirect($returnurl, get_string('errorstatemismatch', 'paygw_mercadopago'), null,
        \core\output\notification::NOTIFY_ERROR);
}

$config = get_config('paygw_mercadopago');
$redirecturi = (new moodle_url('/payment/gateway/mercadopago/oauth_callback.php'))->out(false);

$token = mp_client::exchange_code(
    $config->clientid,
    $config->clientsecret,
    $code,
    $redirecturi,
    $pending->codeverifier
);

// Localiza ou cria a linha do gateway nesta conta.
$gateway = \core_payment\account_gateway::get_record([
    'accountid' => $account->get('id'),
    'gateway' => 'mercadopago',
]);
if (!$gateway) {
    $gateway = new \core_payment\account_gateway();
    $gateway->set('accountid', $account->get('id'));
    $gateway->set('gateway', 'mercadopago');
    $gateway->set('enabled', 0);
}

$existing = $gateway->get('id') ? $gateway->get_configuration() : [];

// Em que pais - e portanto em que moeda - este vendedor recebe. Perguntamos ao
// Mercado Pago em vez de deixar o vendedor escolher: a conta e presa a um pais
// e so recebe na moeda dele. Uma oferta em moeda diferente produziria
// preferencia recusada no checkout, ja com o aluno na tela de pagamento.
$siteid = '';
$currency = '';
try {
    $me = (new mp_client((string) ($token['access_token'] ?? '')))->get_me();
    $siteid = (string) ($me['site_id'] ?? '');
    $currency = mp_client::currency_for_site($siteid);
} catch (moodle_exception $e) {
    // O vinculo em si deu certo; nao vale descartar o token por causa disto.
    // Sem moeda a empresa nao publica oferta paga, e o painel diz o porque.
    debugging('paygw_mercadopago: falha ao consultar /users/me: ' . $e->getMessage(), DEBUG_DEVELOPER);
}

// expires_in vem em segundos. Guardar o INSTANTE do vencimento, e nao a
// duracao, evita ter que lembrar quando o token foi emitido.
$expires = time() + (int) ($token['expires_in'] ?? 0);

$gateway->set('config', json_encode(array_merge($existing, [
    'mpuserid' => (string) ($token['user_id'] ?? ''),
    'accesstoken' => (string) ($token['access_token'] ?? ''),
    'refreshtoken' => (string) ($token['refresh_token'] ?? ''),
    'tokenexpires' => $expires,
    'siteid' => $siteid,
    'currency' => $currency,
])));

if ($gateway->get('id')) {
    $gateway->update();
} else {
    $gateway->create();
}

redirect(
    $returnurl,
    get_string('oauthlinked', 'paygw_mercadopago', [
        'mpuserid' => s((string) ($token['user_id'] ?? '?')),
        'expires' => userdate($expires),
    ]),
    null,
    \core\output\notification::NOTIFY_SUCCESS
);
