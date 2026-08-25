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
 * Ofertas de uma empresa.
 *
 * Vitrine minima: lista o que esta a venda e dispara o modal de pagamento do
 * core_payment. Na Fase 4 vira a pagina publica do vendedor, com o tema dele.
 *
 * @package    local_marketplace
 * @copyright  2026 Leonardo Della Giustina
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__ . '/../../config.php');

use core_payment\helper;
use local_marketplace\company;
use local_marketplace\entitlement;
use local_marketplace\offer;

$shortname = required_param('company', PARAM_ALPHANUMEXT);

// Oferta a destacar. Chega de quem clicou no botao de um topico bloqueado: sem
// isto o aluno cai numa lista e tem que adivinhar qual das ofertas destrava o
// conteudo que ele estava vendo.
$highlight = optional_param('highlight', 0, PARAM_INT);

require_login();

$company = company::get_record(['shortname' => $shortname]);
if (!$company || $company->get('status') !== company::STATUS_ACTIVE) {
    throw new moodle_exception('invalidrecord', 'error');
}

$url = new moodle_url('/local/marketplace/offers.php', ['company' => $shortname]);
$PAGE->set_context($company->get_context());
$PAGE->set_url($url);
$PAGE->set_pagelayout('standard');
$PAGE->set_title(format_string($company->get('name')));
$PAGE->set_heading(format_string($company->get('name')));

echo $OUTPUT->header();

$offers = offer::get_published((int) $company->get('id'));

if (!$offers) {
    echo $OUTPUT->notification(get_string('nooffers', 'local_marketplace'), 'info');
    echo $OUTPUT->footer();
    exit;
}

// A empresa sem meio de pagamento configurado so pode oferecer curso gratuito.
// Avisar aqui evita o aluno clicar em comprar e receber erro do gateway.
$cansell = $company->can_sell();
if (!$cansell) {
    echo $OUTPUT->notification(get_string('nopaymentaccount', 'local_marketplace'), 'warning');
}

echo html_writer::start_div('local-marketplace-offers');

foreach ($offers as $offer) {
    $offerid = (int) $offer->get('id');
    $free = $offer->is_free();

    // Quem ja tem direito vigente nao ve botao de comprar: veria um preco por
    // algo que ja pode acessar.
    $owned = false;
    $held = null;
    foreach (entitlement::get_active_for_user((int) $USER->id, (int) $company->get('id')) as $ent) {
        if ((int) $ent->get('offerid') === $offerid) {
            $owned = true;
            $held = $ent;
            break;
        }
    }

    // Renovar e a excecao a regra acima. Sem debito automatico, o unico jeito
    // de continuar assinando e comprar de novo - e esconder o botao de quem
    // esta prestes a vencer garantiria a perda de acesso.
    $cancelled = $held && (int) $held->get('norenew') === 1;
    $canrenew = false;
    if ($held && !$cancelled && $offer->get('accessmode') === offer::ACCESS_RECURRING) {
        $end = (int) $held->get('timeend');
        $canrenew = $end > 0
            && ($end - time()) < (\local_marketplace\task\notify_expiring::NOTICE_DAYS * DAYSECS)
            && $offer->accepts_cycle((int) $held->get('cycles'));
    }

    $wanted = ($highlight === $offerid);

    echo html_writer::start_div('card mb-3' . ($wanted ? ' border-primary' : ''), $wanted ? ['id' => 'offer-wanted'] : []);
    echo html_writer::start_div('card-body');

    if ($wanted) {
        echo $OUTPUT->notification(get_string('offerunlocks', 'local_marketplace'), 'info');
    }

    echo html_writer::tag('h3', format_string($offer->get('name')), ['class' => 'card-title h5']);

    if ($offer->get('description')) {
        echo html_writer::div(format_text($offer->get('description')), 'card-text');
    }

    $courses = $offer->get_course_ids();
    echo html_writer::div(
        get_string('offerincludes', 'local_marketplace', count($courses)) . ' · ' .
        $offer->describe_billing(),
        'text-muted small mb-2'
    );

    // Quais cursos, e nao so quantos. Com planos em niveis - basico,
    // intermediario, completo - a diferenca entre eles E a lista: "inclui 4
    // cursos" nao deixa o aluno escolher.
    if ($courses && $offer->get('offertype') !== offer::TYPE_CATALOG) {
        $names = $DB->get_records_list('course', 'id', $courses, 'fullname', 'id, fullname');
        $items = array_map(fn($c) => html_writer::tag('li', format_string($c->fullname)), $names);
        echo html_writer::tag('ul', implode('', $items), ['class' => 'small text-muted mb-2']);
    }

    if ($owned && $canrenew) {
        echo $OUTPUT->notification(
            get_string(
                'renewnotice',
                'local_marketplace',
                userdate((int) $held->get('timeend'), get_string('strftimedaydate'))
            ),
            'warning'
        );
        echo html_writer::div(
            helper::get_cost_as_string((float) $offer->get('price'), $offer->get('currency')),
            'h4 mb-2'
        );
        $attributes = helper::gateways_modal_link_params(
            'local_marketplace',
            'offer',
            $offerid,
            format_string($offer->get('name'))
        );
        $attributes['id'] = 'pay-offer-' . $offerid;
        $attributes['class'] = 'btn btn-primary';
        echo html_writer::tag('button', get_string('renewnow', 'local_marketplace'), $attributes);
    } else if ($owned) {
        $until = $held ? (int) $held->get('timeend') : 0;

        if ($cancelled) {
            // Cancelada, mas o acesso continua ate a data paga. Dizer as duas
            // coisas juntas evita o aluno achar que perdeu o que ja pagou.
            echo $OUTPUT->notification(
                $until > 0
                    ? get_string(
                        'cancelledbut',
                        'local_marketplace',
                        userdate($until, get_string('strftimedaydate'))
                    )
                    : get_string('cancelledlifetime', 'local_marketplace'),
                'info'
            );
            echo html_writer::link(
                new moodle_url(
                    '/local/marketplace/cancel.php',
                    ['id' => $held->get('id'), 'undo' => 1, 'sesskey' => sesskey()]
                ),
                get_string('cancelundo', 'local_marketplace'),
                ['class' => 'btn btn-sm btn-secondary']
            );
        } else {
            echo $OUTPUT->notification(get_string('alreadyowned', 'local_marketplace'), 'success');

            // Assinatura vigente mostra ate quando vale. Sem isso o aluno so
            // descobre a data quando o acesso acaba.
            if ($until > 0) {
                echo html_writer::div(
                    get_string(
                        'accessuntil',
                        'local_marketplace',
                        userdate($until, get_string('strftimedaydate'))
                    ),
                    'text-muted small mb-2'
                );
            }

            // So faz sentido cancelar o que se renova. Numa compra avulsa ou
            // vitalicia nao ha renovacao a interromper.
            if ($held && $offer->get('accessmode') === offer::ACCESS_RECURRING) {
                echo html_writer::link(
                    new moodle_url('/local/marketplace/cancel.php', ['id' => $held->get('id')]),
                    get_string('cancelsubscription', 'local_marketplace'),
                    ['class' => 'btn btn-sm btn-outline-secondary']
                );
            }
        }
    } else if ($free) {
        // Oferta gratuita nao passa pelo gateway: nao ha o que cobrar.
        echo html_writer::link(
            new moodle_url('/local/marketplace/claim.php', ['offerid' => $offerid, 'sesskey' => sesskey()]),
            get_string('getfree', 'local_marketplace'),
            ['class' => 'btn btn-primary']
        );
    } else if ($cansell) {
        echo html_writer::div(
            helper::get_cost_as_string((float) $offer->get('price'), $offer->get('currency')),
            'h4 mb-2'
        );
        $attributes = helper::gateways_modal_link_params(
            'local_marketplace',
            'offer',
            $offerid,
            format_string($offer->get('name'))
        );
        // O id precisa ser unico por botao: o helper devolve sempre o mesmo,
        // e a pagina lista varias ofertas.
        $attributes['id'] = 'pay-offer-' . $offerid;
        $attributes['class'] = 'btn btn-primary';
        echo html_writer::tag('button', get_string('buynow', 'local_marketplace'), $attributes);
    } else {
        echo html_writer::tag('span', get_string('unavailable', 'local_marketplace'), ['class' => 'text-muted']);
    }

    echo html_writer::end_div();
    echo html_writer::end_div();
}

echo html_writer::end_div();

$PAGE->requires->js_call_amd('core_payment/gateways_modal', 'init');

echo $OUTPUT->footer();
