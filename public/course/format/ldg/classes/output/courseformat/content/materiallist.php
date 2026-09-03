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
 * A lista de materiais de apoio.
 *
 * @package    format_ldg
 * @author     LeoDG <callme@leodg.dev>
 * @copyright  2026 LeoDG <callme@leodg.dev>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace format_ldg\output\courseformat\content;

use cm_info;
use core\output\named_templatable;
use core\output\renderer_base;
use core_courseformat\base as course_format;
use format_ldg\catalog;
use renderable;
use stdClass;

/**
 * O material de apoio do curso, agrupado pelo modulo a que pertence.
 *
 * COMO SE DECIDE ONDE O MATERIAL ABRE. Nem todo material pode entrar no quadro
 * embutido: um arquivo com download forcado dentro de um iframe dispara o
 * download e deixa o quadro EM BRANCO - e o aluno conclui que a pagina quebrou.
 *
 * A decisao sai de sinais publicos do cm_info, e nao de consulta a tabela de
 * outro plugin:
 *
 * - onclick preenchido: o proprio modulo pediu janela nova ou popup
 *   (mod_resource e mod_url gravam isso no get_coursemodule_info)
 * - customdata['display'] igual a RESOURCELIB_DISPLAY_DOWNLOAD: e download. A
 *   constante e do CORE, em lib/resourcelib.php, e nao do modulo
 * - sem URL: nao ha o que abrir
 *
 * @package    format_ldg
 * @copyright  2026 LeoDG <callme@leodg.dev>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class materiallist implements named_templatable, renderable {
    /** @var course_format */
    protected course_format $format;

    /** @var catalog */
    protected catalog $catalog;

    /**
     * Construtor.
     *
     * @param course_format $format
     * @param catalog $catalog
     */
    public function __construct(course_format $format, catalog $catalog) {
        $this->format = $format;
        $this->catalog = $catalog;
    }

    /**
     * Template desta lista.
     *
     * @param renderer_base $renderer
     * @return string
     */
    public function get_template_name(renderer_base $renderer): string {
        return 'format_ldg/local/content/materiallist';
    }

    /**
     * Monta os dados.
     *
     * @param renderer_base $output
     * @return stdClass
     */
    public function export_for_template(renderer_base $output): stdClass {
        global $CFG;

        require_once($CFG->libdir . '/resourcelib.php');

        $modinfo = $this->format->get_modinfo();
        $materiais = [];

        foreach ($this->catalog->get(catalog::MATERIAL) as $cm) {
            if (!$cm->uservisible) {
                continue;
            }

            $secao = $modinfo->get_section_info($cm->sectionnum);

            $materiais[] = $this->export_material($cm, $this->format->get_section_name($secao));
        }

        return (object) [
            'hasmaterials' => !empty($materiais),
            'materials' => $materiais,
        ];
    }

    /**
     * Um material.
     *
     * @param cm_info $cm
     * @param string $secao Nome do modulo a que ele pertence.
     * @return stdClass
     */
    protected function export_material(cm_info $cm, string $secao): stdClass {
        $abrefora = !empty($cm->onclick);
        $display = $cm->customdata['display'] ?? null;
        $baixa = ($display !== null && (int) $display === RESOURCELIB_DISPLAY_DOWNLOAD);

        return (object) [
            'cmid' => $cm->id,
            'name' => $cm->get_formatted_name(),
            'modname' => $cm->modname,
            'modfullname' => $cm->modfullname,
            'iconurl' => $cm->get_icon_url()->out(false),
            'url' => $cm->url ? $cm->url->out(false) : '',
            'section' => $secao,
            'isdownload' => $baixa,
            'opensnewwindow' => $abrefora,

            // So entra no quadro o que tem pagina propria e nao pediu janela
            // nova. O resto e link, e quem resolve e o navegador.
            'inframe' => !$baixa && !$abrefora && !empty($cm->url),
        ];
    }
}
