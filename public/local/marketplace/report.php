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
 * Relatorio financeiro da empresa.
 *
 * O que este relatorio NAO faz: estimar. Ele mostra o bruto e a comissao que a
 * plataforma pediu ao Mercado Pago, e nada mais.
 *
 * A taxa do Mercado Pago nao aparece aqui porque nao a conhecemos: ela e
 * descontada do lado deles, varia por meio de pagamento e por prazo de
 * recebimento, e nao volta na notificacao. O liquido do vendedor so existe no
 * extrato do Mercado Pago.
 *
 * Inventar uma coluna "liquido" a partir de um percentual fixo produziria um
 * numero que diverge do extrato - e um relatorio financeiro que discorda do
 * extrato e pior que nenhum.
 *
 * @package    local_marketplace
 * @copyright  2026 Leonardo Della Giustina
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__ . '/../../config.php');

use core_payment\helper;
use local_marketplace\company;
use local_marketplace\offer;

$shortname = required_param('company', PARAM_ALPHANUMEXT);
$from = optional_param('from', 0, PARAM_INT);

require_login();

$company = company::get_record(['shortname' => $shortname]);
if (!$company) {
    throw new moodle_exception('invalidrecord', 'error');
}

$context = $company->get_context();
$url = new moodle_url('/local/marketplace/report.php', ['company' => $shortname]);

$PAGE->set_context($context);
$PAGE->set_url($url);
$PAGE->set_pagelayout('standard');
$PAGE->set_title(get_string('reportsection', 'local_marketplace'));
$PAGE->set_heading(format_string($company->get('name')));

require_capability('local/marketplace:viewreport', $context);

// A conta de pagamento e o que liga as transacoes a esta empresa. Sem ela nao
// houve venda alguma.
$account = $company->get_payment_account();

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('reportsection', 'local_marketplace'), 3);

if (!$account) {
    echo $OUTPUT->notification(get_string('errornoaccount', 'local_marketplace'), 'error');
    echo $OUTPUT->footer();
    exit;
}

// Filtro de periodo. Sem ele o relatorio cresce sem limite e o total deixa de
// responder "quanto entrou este mes", que e a pergunta real.
$periods = [
    0 => get_string('reportall', 'local_marketplace'),
    30 => get_string('reportdays', 'local_marketplace', 30),
    90 => get_string('reportdays', 'local_marketplace', 90),
    365 => get_string('reportdays', 'local_marketplace', 365),
];
$links = [];
foreach ($periods as $days => $label) {
    $active = ((int) $from === (int) $days);
    $links[] = html_writer::link(
        new moodle_url($url, ['from' => $days]),
        $label,
        ['class' => 'btn btn-sm ' . ($active ? 'btn-primary' : 'btn-outline-secondary')]
    );
}
echo html_writer::div(implode(' ', $links), 'mb-3');

$params = ['accountid' => (int) $account->get('id'), 'status' => 'approved'];
$where = 'accountid = :accountid AND status = :status';
if ($from > 0) {
    $where .= ' AND timecreated >= :since';
    $params['since'] = time() - ($from * DAYSECS);
}

$rows = $DB->get_records_select('paygw_mercadopago', $where, $params, 'timecreated DESC');

if (!$rows) {
    echo $OUTPUT->notification(get_string('reportnosales', 'local_marketplace'), 'info');
    echo $OUTPUT->footer();
    exit;
}

// --------------------------------------------------------------- totalizacao --
$totals = [];
foreach ($rows as $r) {
    $cur = $r->currency;
    if (!isset($totals[$cur])) {
        $totals[$cur] = ['gross' => 0.0, 'fee' => 0.0, 'count' => 0];
    }
    $totals[$cur]['gross'] += (float) $r->amount;
    $totals[$cur]['fee'] += (float) $r->feeamount;
    $totals[$cur]['count']++;
}

// Somar moedas diferentes num total unico produziria um numero sem significado.
foreach ($totals as $cur => $t) {
    $summary = new html_table();
    $summary->attributes['class'] = 'generaltable w-auto';
    $summary->data = [
        [get_string('reportsales', 'local_marketplace'), $t['count']],
        [get_string('reportgross', 'local_marketplace'), helper::get_cost_as_string($t['gross'], $cur)],
        [get_string('reportcommission', 'local_marketplace'), helper::get_cost_as_string($t['fee'], $cur)],
    ];
    echo html_writer::tag('h4', s($cur), ['class' => 'h6 mt-3']);
    echo html_writer::table($summary);
}

echo $OUTPUT->notification(get_string('reportnetnotice', 'local_marketplace'), 'info');

// -------------------------------------------------------------- lancamentos --
echo $OUTPUT->heading(get_string('reportentries', 'local_marketplace'), 4);

$table = new html_table();
$table->head = [
    get_string('date'),
    get_string('offername', 'local_marketplace'),
    get_string('user'),
    get_string('reportgross', 'local_marketplace'),
    get_string('reportcommission', 'local_marketplace'),
    get_string('reportmppayment', 'local_marketplace'),
];
$table->attributes['class'] = 'generaltable';

foreach ($rows as $r) {
    $offer = offer::get_record(['id' => (int) $r->itemid]);
    $user = \core_user::get_user((int) $r->userid, '*', IGNORE_MISSING);

    $table->data[] = [
        userdate((int) $r->timecreated, get_string('strftimedatetimeshort')),
        $offer ? format_string($offer->get('name')) : '#' . (int) $r->itemid,
        $user ? fullname($user) : '?',
        helper::get_cost_as_string((float) $r->amount, $r->currency),
        helper::get_cost_as_string((float) $r->feeamount, $r->currency),
        $r->mppaymentid ? s($r->mppaymentid) : '-',
    ];
}

echo html_writer::div(html_writer::table($table), 'table-responsive');

echo html_writer::div(
    html_writer::link(
        new moodle_url('/local/marketplace/company.php', ['company' => $shortname]),
        get_string('back'),
        ['class' => 'btn btn-secondary']
    ),
    'mt-3'
);

echo $OUTPUT->footer();
