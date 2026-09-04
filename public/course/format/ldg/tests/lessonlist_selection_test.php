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
 * Testes de que so aula entra na lista de aulas.
 *
 * @package    format_ldg
 * @author     LeoDG <callme@leodg.dev>
 * @copyright  2026 LeoDG <callme@leodg.dev>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace format_ldg;

/**
 * Testes de que so aula entra na lista de aulas.
 *
 * O portal tem destino proprio para material, forum e certificado. Sem estes
 * testes, a apostila apareceria em Materiais E na lista de aulas, e um link
 * velho abriria um PDF no lugar da aula.
 *
 * @package    format_ldg
 * @copyright  2026 LeoDG <callme@leodg.dev>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[\PHPUnit\Framework\Attributes\CoversClass(\format_ldg\catalog::class)]
final class lessonlist_selection_test extends \advanced_testcase {
    /**
     * Material nao pode ser escolhido como aula em foco.
     *
     * @return void
     */
    public function test_material_nao_vira_aula_em_foco(): void {
        $this->resetAfterTest();
        $this->setAdminUser();

        $gerador = $this->getDataGenerator();
        $curso = $gerador->create_course(['format' => 'ldg']);
        $material = $gerador->create_module('resource', [
            'course' => $curso->id, 'section' => 1, 'name' => 'Apostila',
        ]);
        $gerador->create_module('page', [
            'course' => $curso->id, 'section' => 1, 'name' => 'Aula um',
        ]);

        // Pede o material pela URL, como faria um link velho.
        $_GET['lesson'] = $material->cmid;

        try {
            $selecionada = course_get_format($curso)->get_selected_cm();
        } finally {
            unset($_GET['lesson']);
        }

        $this->assertNotNull($selecionada);
        $this->assertSame('Aula um', $selecionada->name);
    }

    /**
     * O forum tambem nao, e nem o rotulo.
     *
     * @return void
     */
    public function test_curso_so_com_material_nao_tem_aula(): void {
        $this->resetAfterTest();
        $this->setAdminUser();

        $gerador = $this->getDataGenerator();
        $curso = $gerador->create_course(['format' => 'ldg']);
        $gerador->create_module('resource', ['course' => $curso->id, 'section' => 1]);
        $gerador->create_module('forum', ['course' => $curso->id, 'section' => 1]);
        $gerador->create_module('label', ['course' => $curso->id, 'section' => 1]);

        $this->assertNull(course_get_format($curso)->get_selected_cm());
    }
}
