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
 * Testes da decisao de trocar o layout pelo do portal.
 *
 * @package    format_ldg
 * @author     LeoDG <callme@leodg.dev>
 * @copyright  2026 LeoDG <callme@leodg.dev>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace format_ldg;

/**
 * Testes da decisao de trocar o layout pelo do portal.
 *
 * A decisao recebe os layouts do tema por PARAMETRO, entao estes testes rodam
 * sem o theme_ldg instalado - que e exatamente como o moodle-plugin-ci roda o
 * formato.
 *
 * @package    format_ldg
 * @copyright  2026 LeoDG <callme@leodg.dev>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[\PHPUnit\Framework\Attributes\CoversClass(\format_ldg\hook_callbacks::class)]
final class hook_callbacks_test extends \advanced_testcase {
    /** @var array Layouts de um tema que declara o portal. */
    private const COM_PORTAL = ['ldgportal' => ['file' => 'ldgportal.php', 'regions' => []]];

    /** @var array Layouts de um tema que nao declara. */
    private const SEM_PORTAL = ['course' => ['file' => 'drawers.php', 'regions' => ['side-pre']]];

    /**
     * Monta uma pagina de curso no formato pedido.
     *
     * O show_editor() do core le o $PAGE GLOBAL, e nao o que recebemos por
     * parametro. Sem apontar o global para o mesmo curso, o teste do professor
     * editando passaria por engano.
     *
     * @param string $formato
     * @return \moodle_page
     */
    private function pagina_de_curso(string $formato): \moodle_page {
        global $PAGE;

        $curso = $this->getDataGenerator()->create_course(['format' => $formato]);

        $PAGE->set_course($curso);
        $PAGE->set_pagelayout('course');

        return $PAGE;
    }

    /**
     * Aluno num curso ldg, com tema que declara o layout: portal.
     *
     * @return void
     */
    public function test_aluno_no_curso_ldg_usa_o_portal(): void {
        $this->resetAfterTest();
        $this->setUser($this->getDataGenerator()->create_user());

        $pagina = $this->pagina_de_curso('ldg');

        $this->assertTrue(hook_callbacks::should_use_portal($pagina, self::COM_PORTAL));
    }

    /**
     * Tema sem o layout: nao troca, para nao cair no standard com debugging.
     *
     * @return void
     */
    public function test_tema_sem_o_layout_nao_troca(): void {
        $this->resetAfterTest();
        $this->setUser($this->getDataGenerator()->create_user());

        $pagina = $this->pagina_de_curso('ldg');

        $this->assertFalse(hook_callbacks::should_use_portal($pagina, self::SEM_PORTAL));
    }

    /**
     * Outro formato de curso nao vira portal.
     *
     * @return void
     */
    public function test_outro_formato_nao_usa_o_portal(): void {
        $this->resetAfterTest();
        $this->setUser($this->getDataGenerator()->create_user());

        $pagina = $this->pagina_de_curso('topics');

        $this->assertFalse(hook_callbacks::should_use_portal($pagina, self::COM_PORTAL));
    }

    /**
     * Com a edicao ligada o professor volta para o chrome do Moodle.
     *
     * @return void
     */
    public function test_edicao_ligada_nao_usa_o_portal(): void {
        global $USER;

        $this->resetAfterTest();
        $this->setAdminUser();

        $pagina = $this->pagina_de_curso('ldg');
        $USER->editing = 1;

        $this->assertFalse(hook_callbacks::should_use_portal($pagina, self::COM_PORTAL));
    }

    /**
     * Pagina que nao e a do curso - relatorio, perfil - fica como esta.
     *
     * @return void
     */
    public function test_outro_layout_nao_usa_o_portal(): void {
        $this->resetAfterTest();
        $this->setUser($this->getDataGenerator()->create_user());

        $pagina = $this->pagina_de_curso('ldg');
        $pagina->set_pagelayout('report');

        $this->assertFalse(hook_callbacks::should_use_portal($pagina, self::COM_PORTAL));
    }
}
