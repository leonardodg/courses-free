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

namespace mod_ldgvideo;

use PHPUnit\Framework\Attributes\CoversClass;

/**
 * A biblioteca do modulo: ciclo de vida, visita e conclusao.
 *
 * @package    mod_ldgvideo
 * @copyright  2026 LeoDG <callme@leodg.dev>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[CoversClass(\mod_ldgvideo\url::class)]
final class lib_test extends \advanced_testcase {
    /**
     * Carrega a lib, que e de funcoes soltas.
     *
     * @return void
     */
    public static function setUpBeforeClass(): void {
        global $CFG;

        require_once($CFG->dirroot . '/mod/ldgvideo/lib.php');

        parent::setUpBeforeClass();
    }

    /**
     * O modulo se declara material, sem nota e sem grupos.
     *
     * @return void
     */
    public function test_supports(): void {
        $this->assertSame(MOD_ARCHETYPE_RESOURCE, ldgvideo_supports(FEATURE_MOD_ARCHETYPE));
        $this->assertSame(MOD_PURPOSE_CONTENT, ldgvideo_supports(FEATURE_MOD_PURPOSE));
        $this->assertTrue(ldgvideo_supports(FEATURE_MOD_INTRO));
        $this->assertTrue(ldgvideo_supports(FEATURE_COMPLETION_TRACKS_VIEWS));
        $this->assertTrue(ldgvideo_supports(FEATURE_BACKUP_MOODLE2));
        $this->assertFalse(ldgvideo_supports(FEATURE_GRADE_HAS_GRADE));
        $this->assertFalse(ldgvideo_supports(FEATURE_GROUPS));
    }

    /**
     * Criar, atualizar e apagar.
     *
     * @return void
     */
    public function test_ciclo_de_vida(): void {
        global $DB;

        $this->resetAfterTest();

        $curso = $this->getDataGenerator()->create_course();
        $cm = $this->getDataGenerator()->create_module('ldgvideo', [
            'course' => $curso->id,
            'name' => 'Aula 1',
            'videourl' => 'https://vimeo.com/226053498',
            'aspectratio' => url::RATIO_PORTRAIT,
        ]);

        $linha = $DB->get_record('ldgvideo', ['id' => $cm->id], '*', MUST_EXIST);
        $this->assertSame('https://vimeo.com/226053498', $linha->videourl);
        $this->assertSame(url::RATIO_PORTRAIT, $linha->aspectratio);

        $atualizado = clone $linha;
        $atualizado->instance = $linha->id;
        $atualizado->coursemodule = $cm->cmid;
        $atualizado->videourl = 'https://www.youtube.com/watch?v=d2bq9QW7fZg';
        $atualizado->aspectratio = url::RATIO_CLASSIC;
        $atualizado->printintro = 0;

        ldgvideo_update_instance($atualizado, null);

        $linha = $DB->get_record('ldgvideo', ['id' => $cm->id], '*', MUST_EXIST);
        $this->assertSame('https://www.youtube.com/watch?v=d2bq9QW7fZg', $linha->videourl);
        $this->assertSame(url::RATIO_CLASSIC, $linha->aspectratio);

        // O printintro viaja serializado, como no mod_page.
        $opcoes = (array) unserialize_array($linha->displayoptions);
        $this->assertSame(0, $opcoes['printintro']);

        $this->assertTrue(ldgvideo_delete_instance($linha->id));
        $this->assertFalse($DB->record_exists('ldgvideo', ['id' => $linha->id]));
    }

    /**
     * A visita dispara o evento e conta para a conclusao.
     *
     * @return void
     */
    public function test_view_dispara_evento_e_marca_conclusao(): void {
        global $DB;

        $this->resetAfterTest();

        $curso = $this->getDataGenerator()->create_course(['enablecompletion' => 1]);
        $cm = $this->getDataGenerator()->create_module('ldgvideo', [
            'course' => $curso->id,
            'completion' => COMPLETION_TRACKING_AUTOMATIC,
            'completionview' => 1,
        ]);

        $aluno = $this->getDataGenerator()->create_and_enrol($curso, 'student');
        $this->setUser($aluno);

        $video = $DB->get_record('ldgvideo', ['id' => $cm->id], '*', MUST_EXIST);
        $modulo = get_coursemodule_from_instance('ldgvideo', $video->id);
        $contexto = \context_module::instance($modulo->id);

        $sink = $this->redirectEvents();
        ldgvideo_view($video, $curso, $modulo, $contexto);
        $eventos = $sink->get_events();
        $sink->close();

        $este = array_values(array_filter($eventos, function ($e) {
            return $e instanceof \mod_ldgvideo\event\course_module_viewed;
        }));

        $this->assertCount(1, $este);
        $this->assertSame($contexto->id, $este[0]->get_context()->id);

        $completion = new \completion_info($curso);
        $dados = $completion->get_data(\cm_info::create($modulo), false, $aluno->id);
        $this->assertEquals(COMPLETION_COMPLETE, $dados->completionstate);
    }

    /**
     * A instalacao semeia "marcar manualmente como feito".
     *
     * NAO E CONFIGURACAO DE PLUGIN: o settings.php nao alcanca isso. Quem
     * decide e get_default_completion(), lendo course_completion_defaults - e
     * sem linha la o padrao seria "nenhuma".
     *
     * @return void
     */
    public function test_a_conclusao_padrao_nasce_manual(): void {
        global $DB;

        $moduleid = $DB->get_field('modules', 'id', ['name' => 'ldgvideo'], MUST_EXIST);

        $padrao = $DB->get_record('course_completion_defaults', [
            'course' => SITEID,
            'module' => $moduleid,
        ]);

        $this->assertNotFalse($padrao, 'o db/install.php tinha que ter semeado o padrao do site');
        $this->assertEquals(COMPLETION_TRACKING_MANUAL, $padrao->completion);
    }

    /**
     * Sem area de arquivo, o check_updates nao pede por uma.
     *
     * @return void
     */
    public function test_check_updates_nao_procura_arquivo(): void {
        $this->resetAfterTest();

        $curso = $this->getDataGenerator()->create_course();
        $cm = $this->getDataGenerator()->create_module('ldgvideo', ['course' => $curso->id]);
        $aluno = $this->getDataGenerator()->create_and_enrol($curso, 'student');
        $this->setUser($aluno);

        $info = \cm_info::create(get_coursemodule_from_instance('ldgvideo', $cm->id));
        $updates = ldgvideo_check_updates_since($info, 0);

        $this->assertObjectNotHasProperty('content', $updates);
    }

    /**
     * O export para o aplicativo devolve o endereco, e nao arquivo.
     *
     * @return void
     */
    public function test_export_contents_devolve_o_endereco(): void {
        $this->resetAfterTest();

        $curso = $this->getDataGenerator()->create_course();
        $cm = $this->getDataGenerator()->create_module('ldgvideo', [
            'course' => $curso->id,
            'videourl' => 'https://vimeo.com/226053498',
        ]);

        $conteudo = ldgvideo_export_contents(
            get_coursemodule_from_instance('ldgvideo', $cm->id),
            ''
        );

        $this->assertCount(1, $conteudo);
        $this->assertSame('url', $conteudo[0]['type']);
        $this->assertSame('https://vimeo.com/226053498', $conteudo[0]['fileurl']);
    }
}
