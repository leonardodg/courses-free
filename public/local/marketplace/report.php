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
 * Relatorios da empresa: transacoes, cursos vendidos e assinaturas.
 *
 * O que estes relatorios NAO fazem: estimar o liquido. A taxa do Mercado Pago
 * varia por meio de pagamento e por prazo de recebimento, e nao volta na
 * notificacao. Uma coluna "liquido" calculada por percentual fixo divergiria do
 * extrato - e relatorio financeiro que discorda do extrato e pior que nenhum.
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
$view = optional_param('view', 'transactions', PARAM_ALPHA);
$from = optional_param('from', 0, PARAM_INT);

require_login();

$company = company::get_record(['shortname' => $shortname]);
if (!$company) {
    throw new moodle_exception('invalidrecord', 'error');
}

$context = $company->get_context();
$url = new moodle_url('/local/marketplace/report.php', ['company' => $shortname, 'view' => $view]);

$PAGE->set_context($context);
$PAGE->set_url($url);
$PAGE->set_pagelayout('standard');
$PAGE->set_title(get_string('reportsection', 'local_marketplace'));
$PAGE->set_heading(format_string($company->get('name')));

require_capability('local/marketplace:viewreport', $context);

$account = $company->get_payment_account();

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('reportsection', 'local_marketplace'), 3);

if (!$account) {
    echo $OUTPUT->notification(get_string('errornoaccount', 'local_marketplace'), 'error');
    echo $OUTPUT->footer();
    exit;
}

// ------------------------------------------------------------- navegacao --
$views = [
    'transactions' => get_string('reportviewtransactions', 'local_marketplace'),
    'courses' => get_string('reportviewcourses', 'local_marketplace'),
    'subscriptions' => get_string('reportviewsubscriptions', 'local_marketplace'),
];
$tabs = [];
foreach ($views as $key => $label) {
    $tabs[] = html_writer::link(
        new moodle_url('/local/marketplace/report.php', ['company' => $shortname, 'view' => $key]),
        $label,
        ['class' => 'btn btn-sm ' . ($view === $key ? 'btn-primary' : 'btn-outline-secondary')]
    );
}
echo html_writer::div(implode(' ', $tabs), 'mb-3');

// Assinaturas seguem a validade do direito, nao a data da venda: filtrar por
// periodo ali esconderia justamente quem esta prestes a vencer.
if ($view !== 'subscriptions') {
    $periods = [
        0 => get_string('reportall', 'local_marketplace'),
        30 => get_string('reportdays', 'local_marketplace', 30),
        90 => get_string('reportdays', 'local_marketplace', 90),
        365 => get_string('reportdays', 'local_marketplace', 365),
    ];
    $links = [];
    foreach ($periods as $days => $label) {
        $links[] = html_writer::link(
            new moodle_url('/local/marketplace/report.php',
                ['company' => $shortname, 'view' => $view, 'from' => $days]),
            $label,
            ['class' => 'btn btn-sm ' . ((int) $from === (int) $days ? 'btn-secondary' : 'btn-outline-secondary')]
        );
    }
    echo html_writer::div(implode(' ', $links), 'mb-3');
}

// ------------------------------------------------------ vendas aprovadas --
$params = ['accountid' => (int) $account->get('id'), 'status' => 'approved'];
$where = 'accountid = :accountid AND status = :status';
if ($from > 0 && $view !== 'subscriptions') {
    $where .= ' AND timecreated >= :since';
    $params['since'] = time() - ($from * DAYSECS);
}
$sales = $DB->get_records_select('paygw_mercadopago', $where, $params, 'timecreated DESC');

// =========================================================== TRANSACOES ==
if ($view === 'transactions') {
    if (!$sales) {
        echo $OUTPUT->notification(get_string('reportnosales', 'local_marketplace'), 'info');
        echo $OUTPUT->footer();
        exit;
    }

    $totals = [];
    foreach ($sales as $s) {
        $cur = $s->currency;
        $totals[$cur] = $totals[$cur] ?? ['gross' => 0.0, 'fee' => 0.0, 'count' => 0];
        $totals[$cur]['gross'] += (float) $s->amount;
        $totals[$cur]['fee'] += (float) $s->feeamount;
        $totals[$cur]['count']++;
    }

    // Moedas separadas: somar BRL com ARS produziria um numero sem significado.
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

    foreach ($sales as $s) {
        $o = offer::get_record(['id' => (int) $s->itemid]);
        $u = \core_user::get_user((int) $s->userid, '*', IGNORE_MISSING);
        $table->data[] = [
            userdate((int) $s->timecreated, get_string('strftimedatetimeshort')),
            $o ? format_string($o->get('name')) : '#' . (int) $s->itemid,
            $u ? fullname($u) : '?',
            helper::get_cost_as_string((float) $s->amount, $s->currency),
            helper::get_cost_as_string((float) $s->feeamount, $s->currency),
            $s->mppaymentid ? s($s->mppaymentid) : '-',
        ];
    }
    echo html_writer::div(html_writer::table($table), 'table-responsive');
}

// =============================================== CURSOS VENDIDOS ==
if ($view === 'courses') {
    if (!$sales) {
        echo $OUTPUT->notification(get_string('reportnosales', 'local_marketplace'), 'info');
        echo $OUTPUT->footer();
        exit;
    }

    // Uma venda de combo conta INTEIRA para cada curso que ela libera. Ratear
    // o valor entre os cursos daria a impressao de uma receita por curso que
    // nao existe: ninguem comprou "um terco do combo". A soma da coluna
    // ultrapassa o faturamento de proposito, e o aviso abaixo diz isso.
    $percourse = [];
    foreach ($sales as $s) {
        $o = offer::get_record(['id' => (int) $s->itemid]);
        if (!$o) {
            continue;
        }
        foreach ($o->get_course_ids() as $courseid) {
            $courseid = (int) $courseid;
            $percourse[$courseid] = $percourse[$courseid] ?? ['count' => 0, 'gross' => 0.0, 'cur' => $s->currency];
            $percourse[$courseid]['count']++;
            $percourse[$courseid]['gross'] += (float) $s->amount;
        }
    }

    if (!$percourse) {
        echo $OUTPUT->notification(get_string('reportnocourses', 'local_marketplace'), 'info');
        echo $OUTPUT->footer();
        exit;
    }

    uasort($percourse, fn($a, $b) => $b['gross'] <=> $a['gross']);

    $table = new html_table();
    $table->head = [
        get_string('course'),
        get_string('reportsaleswith', 'local_marketplace'),
        get_string('reportgross', 'local_marketplace'),
    ];
    $table->attributes['class'] = 'generaltable';

    foreach ($percourse as $courseid => $d) {
        $course = $DB->get_record('course', ['id' => $courseid], 'id, fullname', IGNORE_MISSING);
        $name = $course
            ? html_writer::link(new moodle_url('/course/view.php', ['id' => $courseid]),
                format_string($course->fullname))
            : '#' . $courseid;
        $table->data[] = [
            $name,
            $d['count'],
            helper::get_cost_as_string($d['gross'], $d['cur']),
        ];
    }

    echo html_writer::div(html_writer::table($table), 'table-responsive');
    echo $OUTPUT->notification(get_string('reportcoursesnotice', 'local_marketplace'), 'info');
}

// ==================================================== ASSINATURAS ==
if ($view === 'subscriptions') {
    echo $OUTPUT->notification(get_string('reportsubsnotice', 'local_marketplace'), 'warning');

    // Assinatura aqui e oferta com accessmode=recurring. Nao ha plano nem
    // parcela: cada renovacao e uma compra avulsa que estende a validade.
    $recurring = [];
    foreach (offer::get_records(['companyid' => (int) $company->get('id')]) as $o) {
        if ($o->get('accessmode') === offer::ACCESS_RECURRING) {
            $recurring[(int) $o->get('id')] = $o;
        }
    }

    if (!$recurring) {
        echo $OUTPUT->notification(get_string('reportnosubs', 'local_marketplace'), 'info');
        echo $OUTPUT->footer();
        exit;
    }

    [$insql, $inparams] = $DB->get_in_or_equal(array_keys($recurring), SQL_PARAMS_NAMED);
    $ents = $DB->get_records_select('local_marketplace_entitlement',
        "companyid = :companyid AND offerid $insql",
        array_merge(['companyid' => (int) $company->get('id')], $inparams),
        'timeend DESC');

    if (!$ents) {
        echo $OUTPUT->notification(get_string('reportnosubs', 'local_marketplace'), 'info');
        echo $OUTPUT->footer();
        exit;
    }

    // Quantas vezes cada aluno pagou cada assinatura. E o mais proximo de
    // "mensalidades pagas" que os dados permitem: cada linha aprovada em
    // paygw_mercadopago e um pagamento efetivo daquela oferta.
    $paid = [];
    foreach ($sales as $s) {
        $key = (int) $s->userid . ':' . (int) $s->itemid;
        $paid[$key] = $paid[$key] ?? ['n' => 0, 'last' => 0];
        $paid[$key]['n']++;
        $paid[$key]['last'] = max($paid[$key]['last'], (int) $s->timecreated);
    }

    $now = time();
    $table = new html_table();
    $table->head = [
        get_string('user'),
        get_string('offername', 'local_marketplace'),
        get_string('reportpayments', 'local_marketplace'),
        get_string('reportlastpayment', 'local_marketplace'),
        get_string('reportaccessuntil', 'local_marketplace'),
        get_string('companystatus', 'local_marketplace'),
    ];
    $table->attributes['class'] = 'generaltable';

    foreach ($ents as $e) {
        $u = \core_user::get_user((int) $e->userid, '*', IGNORE_MISSING);
        $o = $recurring[(int) $e->offerid] ?? null;
        $key = (int) $e->userid . ':' . (int) $e->offerid;
        $timeend = (int) $e->timeend;

        if ($e->status !== 'active') {
            $badge = html_writer::tag('span', get_string('reportsubcancelled', 'local_marketplace'),
                ['class' => 'badge bg-secondary']);
        } else if ($timeend > 0 && $timeend <= $now) {
            $badge = html_writer::tag('span', get_string('reportsubexpired', 'local_marketplace'),
                ['class' => 'badge bg-danger']);
        } else if ($timeend > 0 && ($timeend - $now) < (7 * DAYSECS)) {
            $badge = html_writer::tag('span',
                get_string('reportsubduesoon', 'local_marketplace', max(1, (int) ceil(($timeend - $now) / DAYSECS))),
                ['class' => 'badge bg-warning text-dark']);
        } else {
            $badge = html_writer::tag('span', get_string('reportsubactive', 'local_marketplace'),
                ['class' => 'badge bg-success']);
        }

        $table->data[] = [
            $u ? fullname($u) : '?',
            $o ? format_string($o->get('name')) : '#' . (int) $e->offerid,
            $paid[$key]['n'] ?? 0,
            !empty($paid[$key]['last'])
                ? userdate($paid[$key]['last'], get_string('strftimedateshort'))
                : '-',
            $timeend > 0 ? userdate($timeend, get_string('strftimedateshort')) : '-',
            $badge,
        ];
    }

    echo html_writer::div(html_writer::table($table), 'table-responsive');
}

echo html_writer::div(
    html_writer::link(
        new moodle_url('/local/marketplace/company.php', ['company' => $shortname]),
        get_string('back'),
        ['class' => 'btn btn-secondary']
    ),
    'mt-3'
);

echo $OUTPUT->footer();
