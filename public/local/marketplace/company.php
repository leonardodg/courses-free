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
 * Painel da empresa: o que o vendedor precisa para operar.
 *
 * Existe porque o Moodle NAO tem navegacao para conta de pagamento em
 * contexto de categoria. As tres telas do core (accounts, manage_account,
 * manage_gateway) sao alcancaveis apenas pela lista, e a lista consulta
 * get_payment_accounts_to_manage(context_system::instance()), que filtra por
 * contexto EXATO. A conta da empresa vive na categoria dela, entao nunca
 * aparece ali - so por URL montada a mao.
 *
 * Poderiamos ter criado a conta no contexto do sistema para ganhar a tela
 * nativa, mas ai todo vendedor com a capability veria e editaria a conta dos
 * outros. Num marketplace isso e inaceitavel; a tela e o preco de manter o
 * isolamento.
 *
 * @package    local_marketplace
 * @copyright  2026 LeoDG <callme@leodg.dev>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__ . '/../../config.php');

use local_marketplace\company;
use local_marketplace\member;
use local_marketplace\offer;

$shortname = optional_param('company', '', PARAM_ALPHANUMEXT);

require_login();

// Sem parametro, mostra as empresas do usuario. Um vendedor pode participar
// de mais de uma.
if ($shortname === '') {
    $companies = company::get_by_member((int) $USER->id);
    if (count($companies) === 1) {
        redirect(new moodle_url('/local/marketplace/company.php', [
            'company' => reset($companies)->get('shortname'),
        ]));
    }
    $shortname = $companies ? reset($companies)->get('shortname') : '';
    if ($shortname === '') {
        throw new moodle_exception('nocompany', 'local_marketplace');
    }
}

$company = company::get_record(['shortname' => $shortname]);
if (!$company) {
    throw new moodle_exception('invalidrecord', 'error');
}

$context = $company->get_context();
$url = new moodle_url('/local/marketplace/company.php', ['company' => $shortname]);

$PAGE->set_context($context);
$PAGE->set_url($url);
$PAGE->set_pagelayout('standard');
$PAGE->set_title(format_string($company->get('name')));
$PAGE->set_heading(format_string($company->get('name')));

// Ser membro nao basta para administrar; e a capability que decide, e ela e
// avaliada no contexto da categoria da empresa.
require_capability('local/marketplace:managecompany', $context);

echo $OUTPUT->header();

// Meio de pagamento.
echo $OUTPUT->heading(get_string('paymentsection', 'local_marketplace'), 3);

// Uma secao por PAIS: a empresa tem uma conta em cada mercado onde vende, e
// cada uma tem os proprios gateways. Juntar tudo numa lista so faria o vendedor
// argentino ver botao de gateway que nao opera na Argentina.
$accounts = $company->get_payment_accounts();

if (!$accounts) {
    echo $OUTPUT->notification(get_string('errornoaccount', 'local_marketplace'), 'error');
} else {
    foreach ($accounts as $countrycode => $account) {
        echo $OUTPUT->heading(\local_marketplace\country::describe($countrycode), 4);

        $enabled = [];
        $configured = [];
        foreach ($account->get_gateways() as $name => $gw) {
            if (!$gw->get('id')) {
                continue;
            }
            if ($gw->get('enabled')) {
                $enabled[] = $name;
            }
            // Ha credencial guardada mesmo que o gateway esteja desligado. Sem
            // separar os dois casos, uma empresa ja vinculada aparecia como
            // "sem meio de pagamento" - mandando o vendedor refazer um vinculo
            // que ja estava feito.
            if ($gw->get_configuration()) {
                $configured[] = $name;
            }
        }

        if ($company->can_sell($countrycode)) {
            echo $OUTPUT->notification(
                get_string('cansellyes', 'local_marketplace', implode(', ', $enabled)),
                'success'
            );
        } else if ($configured) {
            echo $OUTPUT->notification(get_string('linkednotenabled', 'local_marketplace'), 'warning');
        } else {
            echo $OUTPUT->notification(get_string('nopaymentaccount', 'local_marketplace'), 'warning');
        }

        // Um botao por gateway que atende aquele pais. A lista vem do que cada
        // gateway declara atender, e nao de um nome escrito aqui: o literal
        // 'mercadopago' que existia neste lugar era o que impedia um segundo
        // meio de pagamento de aparecer.
        $buttons = [];
        foreach (\local_marketplace\api::gateways_for_country($countrycode) as $name) {
            $buttons[] = html_writer::link(
                new moodle_url('/payment/manage_gateway.php', [
                    'accountid' => $account->get('id'),
                    'gateway' => $name,
                ]),
                get_string('pluginname', 'paygw_' . $name),
                ['class' => 'btn btn-sm ' . (in_array($name, $enabled, true) ? 'btn-outline-primary' : 'btn-primary')]
            );
        }

        echo html_writer::div(
            $buttons
                ? implode(' ', $buttons)
                : $OUTPUT->notification(get_string('nogatewayforcountry', 'local_marketplace'), 'warning'),
            'mb-4'
        );
    }
}

// Ofertas.
echo $OUTPUT->heading(get_string('offerssection', 'local_marketplace'), 3);

echo html_writer::div(
    html_writer::link(
        new moodle_url('/local/marketplace/offer_edit.php', ['company' => $shortname]),
        get_string('offercreate', 'local_marketplace'),
        ['class' => 'btn btn-primary']
    ),
    'mb-3'
);

$offers = offer::get_records(['companyid' => $company->get('id')], 'sortorder, name');

if (!$offers) {
    echo $OUTPUT->notification(get_string('nooffers', 'local_marketplace'), 'info');
} else {
    $table = new html_table();
    $table->head = [
        get_string('offername', 'local_marketplace'),
        get_string('offertype', 'local_marketplace'),
        get_string('cost'),
        get_string('courses'),
        get_string('offeraccess', 'local_marketplace'),
        get_string('companystatus', 'local_marketplace'),
        '',
    ];
    foreach ($offers as $o) {
        $table->data[] = [
            format_string($o->get('name')),
            get_string('type' . $o->get('offertype'), 'local_marketplace'),
            $o->is_free()
                ? get_string('free', 'local_marketplace')
                : \core_payment\helper::get_cost_as_string((float) $o->get('price'), $o->get('currency')),
            count($o->get_course_ids()),
            $o->describe_billing(),
            get_string('status' . $o->get('status'), 'local_marketplace'),
            html_writer::link(
                new moodle_url('/local/marketplace/offer_edit.php', [
                    'company' => $shortname,
                    'id' => $o->get('id'),
                ]),
                get_string('edit'),
                ['class' => 'btn btn-sm btn-secondary']
            ),
        ];
    }
    echo html_writer::table($table);
}

echo html_writer::div(
    html_writer::link(
        new moodle_url('/local/marketplace/offers.php', ['company' => $shortname]),
        get_string('viewstorefront', 'local_marketplace'),
        ['class' => 'btn btn-secondary']
    ) . ' ' .
    html_writer::link(
        new moodle_url('/course/index.php', ['categoryid' => $company->get('categoryid')]),
        get_string('managecourses', 'local_marketplace'),
        ['class' => 'btn btn-secondary']
    ) . ' ' .
    (has_capability('local/marketplace:viewreport', $context)
        ? html_writer::link(
            new moodle_url('/local/marketplace/report.php', ['company' => $shortname]),
            get_string('reportsection', 'local_marketplace'),
            ['class' => 'btn btn-secondary']
        )
        : ''),
    'mb-4'
);

// Vendedores.
echo $OUTPUT->heading(get_string('members', 'local_marketplace'), 3);

$table = new html_table();
$table->head = [get_string('fullname'), get_string('role')];
foreach (member::get_by_company((int) $company->get('id')) as $m) {
    $user = core_user::get_user((int) $m->get('userid'));
    $table->data[] = [
        $user ? fullname($user) : '?',
        get_string('member' . $m->get('memberrole'), 'local_marketplace'),
    ];
}
echo html_writer::table($table);

echo $OUTPUT->footer();
