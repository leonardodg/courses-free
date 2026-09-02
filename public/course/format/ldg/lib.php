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
 * Formato de curso LDG - portal do aluno.
 *
 * A secao e o MODULO do curso, e cada atividade dentro dela e uma AULA. A tela
 * mostra uma aula por vez, com a lista completa ao lado - e nao todas as
 * atividades empilhadas, como o Topics faz.
 *
 * @package    format_ldg
 * @author     LeoDG <callme@leodg.dev>
 * @copyright  2026 LeoDG <callme@leodg.dev>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Formato de curso LDG.
 *
 * @package    format_ldg
 * @copyright  2026 LeoDG <callme@leodg.dev>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class format_ldg extends core_courseformat\base {
    /**
     * O formato usa secoes.
     *
     * Elas sao os modulos do curso, e a lista de aulas agrupa por elas.
     *
     * @return bool
     */
    public function uses_sections() {
        return true;
    }

    /**
     * O formato NAO usa o indice lateral do curso.
     *
     * A lista de aulas ja cumpre esse papel, e na mesma tela. Duas navegacoes
     * concorrentes disputariam o mesmo espaco e a mesma atencao.
     *
     * Vale saber por que nao e so estetica: o course index e renderizado NO
     * CLIENTE, a partir do state reativo, com o template fixo
     * core_courseformat/local/courseindex/courseindex. Trocar o CONTEUDO dele
     * exigiria um modulo AMD proprio - custo que nao se paga quando a lista ao
     * lado ja mostra o mesmo.
     *
     * @return bool
     */
    public function uses_course_index() {
        return false;
    }

    /**
     * Sem indentacao de atividade.
     *
     * A lista de aulas e plana dentro do modulo: aula recuada sugere hierarquia
     * que nao existe no percurso.
     *
     * @return bool
     */
    public function uses_indentation(): bool {
        return false;
    }

    /**
     * O formato suporta o editor reativo.
     *
     * @return bool
     */
    public function supports_components() {
        return true;
    }

    /**
     * Nome da secao, com o padrao "Modulo N" quando ninguem nomeou.
     *
     * @param stdClass|section_info $section
     * @return string
     */
    public function get_section_name($section) {
        $section = $this->get_section($section);

        if (!empty($section->name)) {
            return format_string($section->name, true, ['context' => $this->get_context()]);
        }

        return $this->get_default_section_name($section);
    }

    /**
     * Nome padrao da secao.
     *
     * A secao 0 e a abertura do curso, e nao um modulo - ela costuma guardar
     * avisos e a apresentacao, entao numera-la como "Modulo 0" seria mentira.
     *
     * @param stdClass|section_info $section
     * @return string
     */
    public function get_default_section_name($section) {
        if ($section->sectionnum == 0) {
            return get_string('section0name', 'format_ldg');
        }

        return get_string('sectionname', 'format_ldg') . ' ' . $section->sectionnum;
    }

    /**
     * URL da pagina do curso.
     *
     * Diferente do Topics, a secao NAO leva a uma pagina propria: tudo acontece
     * na mesma tela, e o que muda e a aula em foco. Por isso o parametro e o
     * cmid da aula, e nao o numero da secao.
     *
     * @param int|stdClass|section_info $section
     * @param array $options
     * @return moodle_url
     */
    public function get_view_url($section, $options = []) {
        $url = new moodle_url('/course/view.php', ['id' => $this->courseid]);

        if (!empty($options['lesson'])) {
            $url->param('lesson', (int) $options['lesson']);
        }

        return $url;
    }

    /**
     * Opcoes do formato, por curso.
     *
     * @param bool $foreditform
     * @return array
     */
    public function course_format_options($foreditform = false) {
        static $courseformatoptions = false;

        if ($courseformatoptions === false) {
            $courseformatoptions = [
                'hiddensections' => [
                    'default' => 1,
                    'type' => PARAM_INT,
                ],
                'coursedisplay' => [
                    'default' => COURSE_DISPLAY_SINGLEPAGE,
                    'type' => PARAM_INT,
                ],
            ];
        }

        if ($foreditform && !isset($courseformatoptions['hiddensections']['label'])) {
            $courseformatoptions['hiddensections']['label'] = new lang_string('hiddensections');
            $courseformatoptions['hiddensections']['help'] = 'hiddensections';
            $courseformatoptions['hiddensections']['help_component'] = 'moodle';
            $courseformatoptions['hiddensections']['element_type'] = 'select';
            $courseformatoptions['hiddensections']['element_attributes'] = [[
                0 => new lang_string('hiddensectionscollapsed'),
                1 => new lang_string('hiddensectionsinvisible'),
            ]];

            // O aluno nunca escolhe entre pagina unica e por secao: a tela do
            // portal e uma so, por desenho. O campo fica fora do formulario.
            $courseformatoptions['coursedisplay']['element_type'] = 'hidden';
        }

        return $courseformatoptions;
    }

    /**
     * A secao 0 fica sempre visivel.
     *
     * @param int|stdClass|section_info $section
     * @return bool
     */
    public function is_section_visible($section): bool {
        $section = $this->get_section($section);

        if ($section->sectionnum == 0) {
            return true;
        }

        return parent::is_section_visible($section);
    }
}
