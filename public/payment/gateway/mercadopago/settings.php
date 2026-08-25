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
 * Configuracao da APLICACAO da plataforma no Mercado Pago.
 *
 * Estes valores sao do dono da plataforma, nao do vendedor: e a aplicacao
 * registrada no painel do Mercado Pago que autoriza o marketplace_fee. O
 * token de cada vendedor fica na conta de pagamento dele, obtido por OAuth.
 *
 * @package    paygw_mercadopago
 * @copyright  2026 Leonardo Della Giustina
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if ($ADMIN->fulltree) {
    $settings->add(new admin_setting_heading(
        'paygw_mercadopago/appheading',
        get_string('appheading', 'paygw_mercadopago'),
        get_string('appheading_desc', 'paygw_mercadopago', (new moodle_url(
            '/payment/gateway/mercadopago/oauth_callback.php'
        ))->out(false))
    ));

    $settings->add(new admin_setting_configtext(
        'paygw_mercadopago/clientid',
        get_string('clientid', 'paygw_mercadopago'),
        get_string('clientid_desc', 'paygw_mercadopago'),
        '',
        PARAM_ALPHANUMEXT
    ));

    // configpasswordunmask esconde o valor na tela e no log de alteracoes.
    $settings->add(new admin_setting_configpasswordunmask(
        'paygw_mercadopago/clientsecret',
        get_string('clientsecret', 'paygw_mercadopago'),
        get_string('clientsecret_desc', 'paygw_mercadopago'),
        ''
    ));

    // O pais da aplicacao decide em que dominio o vendedor autoriza e quais
    // contas podem ser vinculadas. Nao e cosmetico: o split so acontece entre
    // contas do MESMO pais, porque a comissao cai na conta da plataforma e uma
    // conta so guarda a moeda do proprio pais. Nao ha cambio no meio.
    $sites = [];
    foreach (\paygw_mercadopago\mp_client::SITE_CURRENCY as $siteid => $currency) {
        $sites[$siteid] = $siteid . ' - ' . $currency;
    }
    $settings->add(new admin_setting_configselect(
        'paygw_mercadopago/platformsite',
        get_string('platformsite', 'paygw_mercadopago'),
        get_string('platformsite_desc', 'paygw_mercadopago'),
        'MLB',
        $sites
    ));

    $settings->add(new admin_setting_configtext(
        'paygw_mercadopago/defaultfeepercent',
        get_string('defaultfeepercent', 'paygw_mercadopago'),
        get_string('defaultfeepercent_desc', 'paygw_mercadopago'),
        '25',
        PARAM_FLOAT
    ));
}
