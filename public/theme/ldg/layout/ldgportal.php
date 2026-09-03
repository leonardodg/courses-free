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
 * Layout do portal do aluno.
 *
 * Usado pela pagina do curso no format_ldg, e so para quem nao esta editando.
 * Quem pede este layout e o \format_ldg\hook_callbacks; aqui so se desenha.
 *
 * O que este layout NAO tem, de proposito: navbar, drawers, breadcrumb e
 * regiao de bloco. O que ele nao pode deixar de ter e o menu de usuario - tirar
 * o chrome do Moodle nao pode tirar junto o "sair" do aluno.
 *
 * @package    theme_ldg
 * @copyright  2026 LeoDG <callme@leodg.dev>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/course/lib.php');

// O menu de usuario vem do core, pela mesma classe que o layout de drawers usa.
// Reaproveitar em vez de montar um menu proprio mantem perfil, preferencias,
// trocar papel e sair funcionando sem este tema saber o que cada um faz.
$primary = new core\navigation\output\primary($PAGE);
$renderer = $PAGE->get_renderer('core');
$primarymenu = $primary->export_for_template($renderer);

$templatecontext = [
    'sitename' => format_string($SITE->shortname, true, [
        'context' => \core\context\course::instance(SITEID),
        'escape' => false,
    ]),
    'output' => $OUTPUT,
    'bodyattributes' => $OUTPUT->body_attributes(['ldg-portal']),
    'usermenu' => $primarymenu['user'],
    'langmenu' => $primarymenu['lang'],
    'coursename' => format_string($COURSE->fullname),

    // O botao de fechar devolve o aluno para a area dele, e nao para a home do
    // site: quem esta dentro de um curso quer voltar para a lista de cursos.
    'exiturl' => (new moodle_url('/my/'))->out(false),
    'exitlabel' => get_string('exitcourse', 'format_ldg'),
];

echo $OUTPUT->render_from_template('theme_ldg/ldgportal', $templatecontext);
