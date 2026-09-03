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
 * Conteudo da pagina do curso.
 *
 * @package    format_ldg
 * @author     LeoDG <callme@leodg.dev>
 * @copyright  2026 LeoDG <callme@leodg.dev>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace format_ldg\output\courseformat;

use core\output\renderer_base;
use core_completion\progress;
use core_courseformat\output\local\content as content_base;
use format_ldg\catalog;
use format_ldg\output\courseformat\content\lessonlist;
use format_ldg\output\courseformat\content\lessonnav;
use format_ldg\output\courseformat\content\lessonviewer;
use format_ldg\output\courseformat\content\materiallist;
use format_ldg\portalnav;
use stdClass;

/**
 * O conteudo do curso no formato portal.
 *
 * O core encontra esta classe sozinho: get_output_classname() procura
 * format_ldg\output\courseformat\X para cada core_courseformat\output\local\X.
 * Basta existir e estender a base.
 *
 * Ja o TEMPLATE nao vem junto. O trait courseformat_named_templatable mapeia o
 * nome da classe de volta para core_courseformat/local/..., sempre - por isso o
 * get_template_name() abaixo, que e o mesmo caminho do format_onetopic.
 *
 * @package    format_ldg
 * @copyright  2026 LeoDG <callme@leodg.dev>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class content extends content_base {
    /**
     * Template proprio.
     *
     * @param renderer_base $renderer
     * @return string
     */
    public function get_template_name(renderer_base $renderer): string {
        return 'format_ldg/local/content';
    }

    /**
     * Dados do template.
     *
     * Acrescenta ao que o core ja monta, sem refazer nada: a lista de aulas e o
     * progresso do curso. As secoes continuam vindo do core porque sao elas que
     * carregam os ganchos de edicao reativa.
     *
     * @param renderer_base $output
     * @return stdClass
     */
    public function export_for_template(renderer_base $output): stdClass {
        global $USER;

        $data = parent::export_for_template($output);

        $selecionada = $this->format->get_selected_cm();

        $lista = new lessonlist($this->format, $selecionada);
        $data->lessonlist = $lista->export_for_template($output);

        // Com edicao ligada a tela volta a ser a pilha de secoes do core. Nao e
        // preferencia: arrastar atividade, renomear e o menu de acoes vivem nos
        // ganchos que o core poe naquela marcacao, e nenhum deles existe dentro
        // de um iframe. Professor edita, e depois desliga a edicao para ver o
        // curso como o aluno ve.
        $data->isediting = $this->format->show_editor();

        if (!$data->isediting) {
            $catalogo = new catalog($this->format);

            // O destino corrente vem da URL, como a aula. O portalnav valida: o
            // que nao existe neste curso cai em aulas, sem erro.
            $nav = new portalnav(
                $this->format,
                $catalogo,
                optional_param('ldgview', '', PARAM_ALPHA),
                $selecionada
            );

            $destino = $nav->current();

            $data->portalnav = ['destinations' => $nav->destinations()];
            $data->view = $destino;
            $data->islessons = ($destino === catalog::AULA);
            $data->ismaterials = ($destino === catalog::MATERIAL);

            if ($data->ismaterials) {
                $lista = new materiallist($this->format, $catalogo);
                $data->materiallist = $lista->export_for_template($output);
            } else {
                // Forum e certificado nao tem classe propria de proposito: o
                // quadro embutido ja sabe desenhar QUALQUER atividade, e e ele
                // que faz a visita valer - log, conclusao e restricao de acesso.
                // Uma classe por destino seria a mesma coisa tres vezes.
                $foco = $selecionada;

                if (!$data->islessons) {
                    $dodestino = $catalogo->get($destino);
                    $foco = $dodestino ? reset($dodestino) : null;
                }

                $visualizador = new lessonviewer($this->format, $foco);
                $data->lessonviewer = $visualizador->export_for_template($output);

                // A barra so faz sentido no destino Aulas: em Materiais, Forum e
                // Certificado nao ha "proxima" - sao telas, e nao sequencia.
                if ($data->islessons) {
                    $barra = new lessonnav($this->format, $catalogo, $foco);
                    $data->lessonnav = $barra->export_for_template($output);
                }
            }
        }

        // O progresso do CURSO o core sabe calcular - nao ha o que replicar
        // aqui. Devolve null quando o curso nao acompanha conclusao, e nesse
        // caso a barra simplesmente nao aparece.
        $percentual = progress::get_course_progress_percentage($this->format->get_course(), $USER->id);

        if ($percentual !== null) {
            $data->hascourseprogress = true;
            $data->courseprogress = (int) round($percentual);
            $data->courseprogresslabel = get_string('courseprogress', 'format_ldg', (int) round($percentual));
        }

        // A saudacao usa o PRIMEIRO nome, e nao o nome completo: e um cartao de
        // 280px, e "Bem-vindo de volta, Maria Aparecida da Silva Santos" quebra
        // em quatro linhas.
        //
        // NAO ha selo de plano aqui. O desenho tinha um "PREMIUM", e ele nao
        // entra: nao existe no Moodle nada que diga o plano do aluno nesta tela,
        // e um selo inventado seria mentir para ele.
        $data->studentgreeting = get_string('studentgreeting', 'format_ldg', $USER->firstname);

        return $data;
    }
}
