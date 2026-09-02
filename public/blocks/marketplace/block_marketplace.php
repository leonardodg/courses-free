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

use local_marketplace\company;
use local_marketplace\entitlement;
use local_marketplace\offer;
use local_marketplace\task\notify_expiring;

/**
 * As assinaturas do aluno.
 *
 * Existe porque sem debito automatico o aluno precisa AGIR para continuar
 * assinando, e nao ha onde ele veja o que tem e quando vence. O e-mail de aviso
 * chega uma vez; o bloco fica.
 *
 * Mostra so o que exige atencao ou decisao. O historico de pagamentos vai para
 * uma pagina propria: numa barra lateral, uma lista que cresce a cada mes
 * empurraria para baixo justamente o que precisa ser visto.
 *
 * @package    block_marketplace
 * @copyright  2026 LeoDG <callme@leodg.dev>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class block_marketplace extends block_base {
    /**
     * Titulo.
     *
     * @return void
     */
    public function init() {
        $this->title = get_string('pluginname', 'block_marketplace');
    }

    /**
     * Onde pode ser adicionado.
     *
     * @return array
     */
    public function applicable_formats() {
        return ['my' => true, 'site-index' => true, 'course-view' => true];
    }

    /**
     * Conteudo.
     *
     * @return stdClass|null
     */
    public function get_content() {
        global $USER, $OUTPUT;

        if ($this->content !== null) {
            return $this->content;
        }

        $this->content = new stdClass();
        $this->content->text = '';
        $this->content->footer = '';

        if (!isloggedin() || isguestuser()) {
            return $this->content;
        }

        $ents = entitlement::get_active_for_user((int) $USER->id);
        if (!$ents) {
            return $this->content;
        }

        $now = time();
        $notice = notify_expiring::NOTICE_DAYS * DAYSECS;
        $items = [];

        foreach ($ents as $ent) {
            $offer = offer::get_record(['id' => (int) $ent->get('offerid')]);
            $companyrec = company::get_record(['id' => (int) $ent->get('companyid')]);
            if (!$offer || !$companyrec) {
                continue;
            }

            $end = (int) $ent->get('timeend');
            $cancelled = (int) $ent->get('norenew') === 1;
            $recurring = $offer->get('accessmode') === offer::ACCESS_RECURRING;

            // Vitalicia sem cancelamento nao pede nada do aluno e nao entra:
            // ocuparia espaco para dizer "esta tudo bem".
            if ($end === 0 && !$cancelled) {
                continue;
            }

            $parts = [];
            $parts[] = html_writer::tag('strong', format_string($offer->get('name')));
            $parts[] = html_writer::div(format_string($companyrec->get('name')), 'small text-muted');

            if ($end > 0) {
                $days = (int) ceil(($end - $now) / DAYSECS);
                $urgent = ($end - $now) < $notice;
                $parts[] = html_writer::div(
                    get_string(
                        $cancelled ? 'blockendson' : 'blockrenewson',
                        'block_marketplace',
                        userdate($end, get_string('strftimedaydate'))
                    ),
                    'small ' . ($urgent ? 'text-danger fw-semibold' : 'text-muted')
                );

                if ($urgent && !$cancelled && $recurring && $offer->accepts_cycle((int) $ent->get('cycles'))) {
                    $parts[] = html_writer::link(
                        new moodle_url('/local/marketplace/offers.php', [
                            'company' => $companyrec->get('shortname'),
                            'highlight' => $offer->get('id'),
                        ]),
                        get_string('blockpaynow', 'block_marketplace', $days),
                        ['class' => 'btn btn-sm btn-primary mt-1']
                    );
                }
            }

            if ($cancelled) {
                $parts[] = html_writer::div(
                    get_string('blockcancelled', 'block_marketplace'),
                    'small fst-italic'
                );
            }

            $items[] = html_writer::div(implode('', $parts), 'mb-3');
        }

        if (!$items) {
            return $this->content;
        }

        $this->content->text = implode('', $items);
        $this->content->footer = html_writer::link(
            new moodle_url('/local/marketplace/mysubscriptions.php'),
            get_string('blockhistory', 'block_marketplace')
        );

        return $this->content;
    }

    /**
     * Uma instancia por pagina basta.
     *
     * @return bool
     */
    public function instance_allow_multiple() {
        return false;
    }
}
