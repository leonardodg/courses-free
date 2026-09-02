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
 * Testes do progresso por secao.
 *
 * @package    format_ldg
 * @category   test
 * @author     LeoDG <callme@leodg.dev>
 * @copyright  2026 LeoDG <callme@leodg.dev>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace format_ldg;

use completion_info;

/**
 * Testes do progresso por secao.
 *
 * ESTE E O TESTE MAIS IMPORTANTE DO PLUGIN, e a razao nao e a complexidade: a
 * classe testada e COPIA de codigo do core que e protected
 * (cmsummary::calculate_section_stats). Se o Moodle mudar a regra de contagem,
 * nada quebra - a nossa conta so passa a divergir da dele, em silencio, e o
 * aluno ve um progresso que nao bate com o resto do site.
 *
 * Cada teste aqui fixa uma regra do core. Quando um deles falhar depois de um
 * upgrade, e sinal de que a regra mudou la, e nao de que o teste esta ruim.
 *
 * @package    format_ldg
 * @copyright  2026 LeoDG <callme@leodg.dev>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[\PHPUnit\Framework\Attributes\CoversClass(\format_ldg\section_progress::class)]
final class section_progress_test extends \advanced_testcase {
    /**
     * Monta um curso com conclusao ligada e um aluno matriculado.
     *
     * @return array [curso, aluno]
     */
    protected function make_course(): array {
        global $CFG;

        $CFG->enablecompletion = 1;

        $generator = $this->getDataGenerator();
        $curso = $generator->create_course(['format' => 'ldg', 'enablecompletion' => 1, 'numsections' => 3]);
        $aluno = $generator->create_user();
        $generator->enrol_user($aluno->id, $curso->id);

        return [$curso, $aluno];
    }

    /**
     * Cria uma page com conclusao manual.
     *
     * @param \stdClass $curso
     * @param int $section
     * @return \stdClass
     */
    protected function make_manual(\stdClass $curso, int $section): \stdClass {
        return $this->getDataGenerator()->get_plugin_generator('mod_page')->create_instance([
            'course' => $curso->id,
            'section' => $section,
            'completion' => COMPLETION_TRACKING_MANUAL,
        ]);
    }

    /**
     * Secao sem nenhuma conclusao ligada nao tem progresso.
     *
     * Nao e o mesmo que zero por cento: a barra nao deve aparecer, senao um
     * modulo que simplesmente nao acompanha conclusao se le como "voce nao fez
     * nada".
     *
     * @return void
     */
    public function test_secao_sem_conclusao_nao_tem_progresso(): void {
        $this->resetAfterTest();

        [$curso, $aluno] = $this->make_course();

        $this->getDataGenerator()->get_plugin_generator('mod_page')->create_instance([
            'course' => $curso->id,
            'section' => 1,
            'completion' => COMPLETION_TRACKING_NONE,
        ]);

        $this->setUser($aluno);

        $progresso = $this->progress_for($curso, $aluno, 1);

        $this->assertFalse($progresso->has_tracking());
        $this->assertSame(0, $progresso->total);
        $this->assertSame(0, $progresso->percentage());
    }

    /**
     * Conta so o que tem conclusao ligada.
     *
     * @return void
     */
    public function test_conta_apenas_aulas_com_conclusao(): void {
        $this->resetAfterTest();

        [$curso, $aluno] = $this->make_course();

        $this->make_manual($curso, 1);
        $this->make_manual($curso, 1);

        // Sem conclusao: nao entra no denominador.
        $this->getDataGenerator()->get_plugin_generator('mod_page')->create_instance([
            'course' => $curso->id,
            'section' => 1,
            'completion' => COMPLETION_TRACKING_NONE,
        ]);

        $this->setUser($aluno);

        $progresso = $this->progress_for($curso, $aluno, 1);

        $this->assertSame(2, $progresso->total);
        $this->assertSame(0, $progresso->complete);
    }

    /**
     * Concluir uma de duas da cinquenta por cento.
     *
     * @return void
     */
    public function test_percentual(): void {
        $this->resetAfterTest();

        [$curso, $aluno] = $this->make_course();

        $primeira = $this->make_manual($curso, 1);
        $this->make_manual($curso, 1);

        $this->setUser($aluno);

        $completion = new completion_info(get_course($curso->id));
        $modinfo = get_fast_modinfo($curso, $aluno->id);
        $completion->update_state($modinfo->get_cm($primeira->cmid), COMPLETION_COMPLETE, $aluno->id);

        $progresso = $this->progress_for($curso, $aluno, 1);

        $this->assertSame(1, $progresso->complete);
        $this->assertSame(2, $progresso->total);
        $this->assertSame(50, $progresso->percentage());
        $this->assertFalse($progresso->is_complete_section());
    }

    /**
     * Concluir todas fecha a secao.
     *
     * @return void
     */
    public function test_secao_completa(): void {
        $this->resetAfterTest();

        [$curso, $aluno] = $this->make_course();

        $a = $this->make_manual($curso, 1);
        $b = $this->make_manual($curso, 1);

        $this->setUser($aluno);

        $completion = new completion_info(get_course($curso->id));
        $modinfo = get_fast_modinfo($curso, $aluno->id);
        $completion->update_state($modinfo->get_cm($a->cmid), COMPLETION_COMPLETE, $aluno->id);
        $completion->update_state($modinfo->get_cm($b->cmid), COMPLETION_COMPLETE, $aluno->id);

        $progresso = $this->progress_for($curso, $aluno, 1);

        $this->assertSame(100, $progresso->percentage());
        $this->assertTrue($progresso->is_complete_section());
    }

    /**
     * Aula BLOQUEADA nao entra no denominador.
     *
     * Esta e a regra que mais importa para este projeto. Se a aula que o aluno
     * ainda nao comprou contasse, o progresso dele travaria abaixo de cem por
     * cento sem nenhuma explicacao na tela - e a explicacao verdadeira seria
     * "ha conteudo a venda que voce nao viu".
     *
     * @return void
     */
    public function test_aula_bloqueada_fica_fora_da_conta(): void {
        $this->resetAfterTest();

        global $CFG;
        $CFG->enableavailability = 1;

        [$curso, $aluno] = $this->make_course();

        $visivel = $this->make_manual($curso, 1);

        // Bloqueada por uma data futura: e a condicao mais simples que existe,
        // e o que se testa aqui e a CONTAGEM, nao a condicao.
        $this->getDataGenerator()->get_plugin_generator('mod_page')->create_instance([
            'course' => $curso->id,
            'section' => 1,
            'completion' => COMPLETION_TRACKING_MANUAL,
            'availability' => json_encode((object) [
                'op' => '&',
                'c' => [(object) ['type' => 'date', 'd' => '>=', 't' => time() + WEEKSECS]],
                'showc' => [true],
            ]),
        ]);

        $this->setUser($aluno);

        $progresso = $this->progress_for($curso, $aluno, 1);

        $this->assertSame(1, $progresso->total, 'A aula bloqueada nao pode entrar no denominador.');

        $completion = new completion_info(get_course($curso->id));
        $modinfo = get_fast_modinfo($curso, $aluno->id);
        $completion->update_state($modinfo->get_cm($visivel->cmid), COMPLETION_COMPLETE, $aluno->id);

        $progresso = $this->progress_for($curso, $aluno, 1);

        $this->assertSame(100, $progresso->percentage(), 'Com a unica aula disponivel concluida, o modulo esta completo.');
    }

    /**
     * Visitante nao tem progresso.
     *
     * @return void
     */
    public function test_visitante_nao_tem_progresso(): void {
        $this->resetAfterTest();

        [$curso, $aluno] = $this->make_course();

        $this->make_manual($curso, 1);

        $this->setGuestUser();

        $progresso = $this->progress_for($curso, $aluno, 1);

        $this->assertFalse($progresso->has_tracking());
        $this->assertSame(0, $progresso->total);
    }

    /**
     * Atalho para calcular o progresso de uma secao.
     *
     * @param \stdClass $curso
     * @param \stdClass $aluno
     * @param int $sectionnum
     * @return section_progress
     */
    protected function progress_for(\stdClass $curso, \stdClass $aluno, int $sectionnum): section_progress {
        $curso = get_course($curso->id);
        $modinfo = get_fast_modinfo($curso, $aluno->id);
        $completion = new completion_info($curso);
        $section = $modinfo->get_section_info($sectionnum);

        return section_progress::for_section($section, $modinfo, $completion);
    }
}
