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

namespace local_partners;

use core\hook\output\before_standard_head_html_generation;
use moodle_url;

/**
 * Callbacks de hook do local_partners.
 *
 * @package    local_partners
 * @copyright  2026 Leonardo Della Giustina
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class hook_callbacks {
    /**
     * Injeta as meta tags de compartilhamento da landing.
     *
     * ESTE CALLBACK RODA EM TODA PAGINA DO SITE. Por isso as saidas antecipadas
     * vem primeiro e sao baratas, e a checagem de URL vem antes de qualquer
     * consulta: sem elas, compartilhar um curso no WhatsApp mostraria o pitch de
     * parceria em vez do curso.
     *
     * @param before_standard_head_html_generation $hook
     * @return void
     */
    public static function before_standard_head_html_generation(
        before_standard_head_html_generation $hook
    ): void {
        global $CFG, $PAGE;

        // Usuario autenticado nunca ve a landing.
        if (isloggedin() && !isguestuser()) {
            return;
        }

        // Dominio de vendedor tem a home dele, e nao a nossa.
        if (!empty($CFG->marketplacecompany)) {
            return;
        }

        if (!$PAGE->has_set_url()) {
            return;
        }

        // So a raiz e a URL propria da landing recebem as tags. Poluir o resto
        // do site com og:type=website da landing faria toda pagina compartilhada
        // parecer a mesma coisa.
        $path = $PAGE->url->get_path();
        $root = (new moodle_url('/'))->get_path();

        $allowed = [
            rtrim($root, '/'),
            rtrim($root, '/') . '/index.php',
            (new moodle_url('/local/partners/index.php'))->get_path(),
        ];

        if (!in_array(rtrim($path, '/') ?: '', $allowed, true) && !in_array($path, $allowed, true)) {
            return;
        }

        // A consulta de configuracao fica por ultimo, depois de todas as
        // saidas baratas.
        if (!landing::replaces_frontpage() && $path !== (new moodle_url('/local/partners/index.php'))->get_path()) {
            return;
        }

        $hook->add_html(landing::head_html());
    }
}
