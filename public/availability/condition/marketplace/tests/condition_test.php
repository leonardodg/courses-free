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

namespace availability_marketplace;

use local_marketplace\api;
use local_marketplace\entitlement;
use local_marketplace\offer;
use PHPUnit\Framework\Attributes\CoversClass;

/**
 * Liberação de seção mediante compra.
 *
 * É o outro consumidor do direito de acesso, ao lado do `enrol_marketplace`. A
 * diferença é o alcance do erro: aqui um falso positivo mostra conteúdo pago
 * dentro de um curso em que o aluno já entrou legitimamente — não há matrícula
 * negada para servir de segunda barreira.
 *
 * @package    availability_marketplace
 * @copyright  2026 LeoDG <callme@leodg.dev>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[CoversClass(\availability_marketplace\condition::class)]
final class condition_test extends \advanced_testcase {
    /** @var \stdClass */
    protected $user;

    /** @var \local_marketplace\company */
    protected $company;

    /** @var \stdClass */
    protected $course;

    /**
     * Carrega o mock_info do core, que é o `info` mínimo que a condição pede.
     *
     * @return void
     */
    public static function setUpBeforeClass(): void {
        global $CFG;

        require_once($CFG->dirroot . '/availability/tests/fixtures/mock_info.php');
        parent::setUpBeforeClass();
    }

    /**
     * Empresa, curso e aluno.
     *
     * @return void
     */
    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
        $this->setAdminUser();

        $this->user = $this->getDataGenerator()->create_user();
        $this->company = api::create_company((object) [
            'name' => 'Empresa Teste',
            'shortname' => 'teste' . random_int(1000, 9999),
            'cnpj' => null,
            'themename' => null,
            'hostname' => null,
        ], (int) $this->user->id);

        $this->course = $this->getDataGenerator()->create_course([
            'category' => $this->company->get('categoryid'),
        ]);
    }

    /**
     * Cria uma oferta que inclui o curso de teste.
     *
     * @return offer
     */
    protected function make_offer(): offer {
        $o = new offer();
        $o->set('companyid', (int) $this->company->get('id'));
        $o->set('name', 'Oferta');
        $o->set('offertype', offer::TYPE_SINGLE);
        $o->set('price', 50.0);
        $o->set('currency', 'BRL');
        $o->set('accessmode', offer::ACCESS_LIFETIME);
        $o->set('accessdays', 0);
        $o->set('status', offer::STATUS_PUBLISHED);
        $o->create();
        $o->add_course((int) $this->course->id);

        return $o;
    }

    /**
     * Concede o direito ao aluno de teste.
     *
     * @param offer $offer
     * @param int $timeend 0 = vitalício.
     * @return entitlement
     */
    protected function grant(offer $offer, int $timeend = 0): entitlement {
        $e = new entitlement();
        $e->set('userid', (int) $this->user->id);
        $e->set('offerid', (int) $offer->get('id'));
        $e->set('companyid', (int) $this->company->get('id'));
        $e->set('timestart', time() - DAYSECS);
        $e->set('timeend', $timeend);
        $e->set('status', entitlement::STATUS_ACTIVE);
        $e->create();

        return $e;
    }

    /**
     * O `info` que a condição consulta.
     *
     * @return \core_availability\mock_info
     */
    protected function info(): \core_availability\mock_info {
        return new \core_availability\mock_info($this->course, (int) $this->user->id);
    }

    /**
     * Avalia a condição.
     *
     * @param int $offerid 0 = qualquer oferta que inclua o curso.
     * @param bool $not
     * @return bool
     */
    protected function available(int $offerid = 0, bool $not = false): bool {
        $cond = new condition(condition::get_json($offerid));

        return $cond->is_available($not, $this->info(), false, (int) $this->user->id);
    }

    /**
     * Sem direito, o conteúdo fica bloqueado.
     *
     * @return void
     */
    public function test_sem_direito_bloqueia(): void {
        $this->make_offer();

        $this->assertFalse($this->available());
    }

    /**
     * Qualquer oferta que inclua o curso libera.
     *
     * @return void
     */
    public function test_qualquer_oferta_libera(): void {
        $offer = $this->make_offer();
        $this->grant($offer);

        $this->assertTrue($this->available());
    }

    /**
     * Com oferta declarada, só ELA libera.
     *
     * É a diferença entre "comprou alguma coisa que dá este curso" e "comprou
     * este pacote". Sem essa distinção, quem levou a oferta básica veria a
     * seção reservada a quem levou a avançada.
     *
     * @return void
     */
    public function test_oferta_declarada_e_exclusiva(): void {
        $exigida = $this->make_offer();
        $outra = $this->make_offer();
        $this->grant($outra);

        $this->assertFalse($this->available((int) $exigida->get('id')));
        $this->assertTrue($this->available((int) $outra->get('id')));
    }

    /**
     * Direito vencido pela data não libera.
     *
     * @return void
     */
    public function test_vencido_nao_libera(): void {
        $offer = $this->make_offer();
        $ent = $this->grant($offer, time() - 1);

        $this->assertSame(entitlement::STATUS_ACTIVE, $ent->get('status'), 'o status ainda nao foi mexido');
        $this->assertFalse($this->available(), 'a data vale mais que o status');
    }

    /**
     * Direito cancelado não libera.
     *
     * @return void
     */
    public function test_cancelado_nao_libera(): void {
        $offer = $this->make_offer();
        $ent = $this->grant($offer);
        $ent->set('status', entitlement::STATUS_CANCELLED);
        $ent->update();

        $this->assertFalse($this->available());
    }

    /**
     * A negação inverte, nos dois modos.
     *
     * @return void
     */
    public function test_negacao_inverte(): void {
        $offer = $this->make_offer();

        $this->assertTrue($this->available(0, true), 'sem direito, o NOT libera');
        $this->assertTrue($this->available((int) $offer->get('id'), true));

        $this->grant($offer);

        $this->assertFalse($this->available(0, true), 'com direito, o NOT bloqueia');
        $this->assertFalse($this->available((int) $offer->get('id'), true));
    }

    /**
     * O que é gravado é o que volta.
     *
     * A condição vive como JSON na coluna `availability` do curso. Se o
     * `save()` perdesse o `offerid`, toda condição por oferta específica viraria
     * silenciosamente "qualquer oferta" no próximo salvamento do formulário —
     * afrouxando a regra sem ninguém notar.
     *
     * @return void
     */
    public function test_gravar_e_reler_preserva_a_oferta(): void {
        $cond = new condition(condition::get_json(42));
        $gravado = $cond->save();

        $this->assertSame('marketplace', $gravado->type);
        $this->assertSame(42, $gravado->offerid);

        $relido = new condition($gravado);
        $this->assertEquals($gravado, $relido->save());
    }

    /**
     * Estrutura sem `offerid`, ou com lixo, cai em "qualquer oferta".
     *
     * @return void
     */
    public function test_estrutura_invalida_vira_qualquer_oferta(): void {
        $this->assertSame(0, (new condition((object) []))->save()->offerid);
        $this->assertSame(0, (new condition((object) ['offerid' => 'abc']))->save()->offerid);
    }
}
