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
 * Desfaz o vinculo da conta Mercado Pago do vendedor.
 *
 * Sem esta tela o vinculo seria de mao unica: o formulario do gateway guarda o
 * token em campos escondidos, entao nao ha como limpa-lo pela interface. Trocar
 * de conta ainda funcionaria - autorizar de novo sobrescreve - mas SAIR nao.
 *
 * @package    paygw_mercadopago
 * @copyright  2026 Leonardo Della Giustina
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__ . '/../../../config.php');

$accountid = required_param('accountid', PARAM_INT);
$confirm = optional_param('confirm', 0, PARAM_BOOL);

require_login();

$account = new \core_payment\account($accountid);
$context = $account->get_context();
require_capability('moodle/payment:manageaccounts', $context);

$url = new moodle_url('/payment/gateway/mercadopago/oauth_unlink.php', ['accountid' => $accountid]);
$returnurl = $account->get_edit_url();

$PAGE->set_context($context);
$PAGE->set_url($url);
$PAGE->set_pagelayout('admin');
$PAGE->set_title(get_string('unlinkaccount', 'paygw_mercadopago'));
$PAGE->set_heading(get_string('unlinkaccount', 'paygw_mercadopago'));

$gateway = \core_payment\account_gateway::get_record([
    'accountid' => $account->get('id'),
    'gateway' => 'mercadopago',
]);

if (!$gateway || !$gateway->get('id')) {
    redirect($returnurl);
}

$config = $gateway->get_configuration();

if (!$confirm) {
    echo $OUTPUT->header();
    echo $OUTPUT->confirm(
        get_string('unlinkconfirm', 'paygw_mercadopago', s((string) ($config['mpuserid'] ?? '?'))),
        new moodle_url($url, ['confirm' => 1, 'sesskey' => sesskey()]),
        $returnurl
    );
    echo $OUTPUT->footer();
    exit;
}

require_sesskey();

// Desabilita junto. Uma conta habilitada sem token levaria o aluno ate o
// checkout para receber erro do Mercado Pago - e o gateway se recusa a ser
// habilitado sem token, entao deixar enabled=1 aqui criaria um estado que a
// propria validacao do formulario considera invalido.
$gateway->set('enabled', 0);

// Preserva o que nao e credencial: sandbox e escolha de configuracao, nao
// vestigio do vinculo.
$gateway->set('config', json_encode([
    'sandbox' => (int) ($config['sandbox'] ?? 0),
]));
$gateway->update();

// Limpar aqui NAO revoga a autorizacao do lado do Mercado Pago. O vendedor
// continua vendo a aplicacao autorizada na conta dele, e so ele pode remove-la
// de la. Sem o token guardado, porem, nada podemos fazer em nome dele.
redirect(
    $returnurl,
    get_string('unlinkdone', 'paygw_mercadopago'),
    null,
    \core\output\notification::NOTIFY_SUCCESS
);
