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
 * Inicia o vinculo da conta Mercado Pago do vendedor.
 *
 * @package    paygw_mercadopago
 * @copyright  2026 Leonardo Della Giustina
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__ . '/../../../config.php');

use paygw_mercadopago\mp_client;

$accountid = required_param('accountid', PARAM_INT);

require_login();

$account = new \core_payment\account($accountid);
$context = $account->get_context();

// A capability e verificada no contexto da CONTA, nao no sistema: e isso que
// permite ao vendedor vincular a propria conta sem poder tocar nas outras.
require_capability('moodle/payment:manageaccounts', $context);

$config = get_config('paygw_mercadopago');
if (empty($config->clientid) || empty($config->clientsecret)) {
    throw new moodle_exception('errormissingappconfig', 'paygw_mercadopago');
}

// O state protege contra CSRF: sem ele, alguem poderia induzir o vendedor a
// concluir um fluxo iniciado por terceiro e vincular a conta errada. Guardamos
// na sessao e conferimos no retorno.
$state = random_string(32);
$SESSION->paygw_mercadopago_oauth = (object) [
    'state' => $state,
    'accountid' => $accountid,
    'timecreated' => time(),
];

$redirecturi = (new moodle_url('/payment/gateway/mercadopago/oauth_callback.php'))->out(false);

redirect(mp_client::build_authorization_url($config->clientid, $redirecturi, $state));
