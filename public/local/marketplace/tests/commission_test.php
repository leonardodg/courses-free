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

namespace local_marketplace;

/**
 * Hierarquia da comissao: curso, empresa, site.
 *
 * Cada asserção aqui vale dinheiro real de alguem. Um erro nesta resolucao
 * cobra a mais do vendedor ou deixa a plataforma sem receber, e so apareceria
 * na conciliacao do mes seguinte.
 *
 * @package    local_marketplace
 * @copyright  2026 Leonardo Della Giustina
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \local_marketplace\api::resolve_commission_percent
 */
final class commission_test extends \advanced_testcase {
    /** @var company */
    protected $company;

    /** @var \stdClass */
    protected $course;

    /**
     * Empresa e curso de teste, com o padrao do site em 25.
     *
     * @return void
     */
    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
        $this->setAdminUser();

        set_config('defaultfeepercent', 25, 'paygw_mercadopago');

        $owner = $this->getDataGenerator()->create_user();
        $this->company = api::create_company((object) [
            'name' => 'Empresa Teste',
            'shortname' => 'com' . random_int(1000, 9999),
        ], (int) $owner->id);

        $this->course = $this->getDataGenerator()->create_course([
            'category' => $this->company->get('categoryid'),
        ]);
    }

    /**
     * Cria uma oferta da empresa.
     *
     * @param string $type
     * @param int[] $courseids
     * @return offer
     */
    protected function make_offer(string $type, array $courseids): offer {
        $o = new offer();
        $o->set('companyid', (int) $this->company->get('id'));
        $o->set('name', 'Oferta');
        $o->set('offertype', $type);
        $o->set('price', 100.0);
        $o->set('currency', 'BRL');
        $o->set('accessmode', offer::ACCESS_LIFETIME);
        $o->set('status', offer::STATUS_PUBLISHED);
        $o->create();
        foreach ($courseids as $cid) {
            $o->add_course($cid);
        }
        return $o;
    }

    /**
     * Grava uma politica para o curso.
     *
     * @param float $pct
     * @return void
     */
    protected function set_course_policy(float $pct): void {
        $p = new course_policy();
        $p->set('courseid', (int) $this->course->id);
        $p->set('companyid', (int) $this->company->get('id'));
        $p->set('hostingtype', course_policy::HOSTING_EXTERNAL);
        $p->set('commissionpct', $pct);
        $p->create();
    }

    /**
     * Sem nada negociado, vale o padrao do site.
     *
     * @return void
     */
    public function test_falls_back_to_site_default(): void {
        $offer = $this->make_offer(offer::TYPE_SINGLE, [(int) $this->course->id]);

        $this->assertEquals(25.0, api::resolve_commission_percent($offer));
    }

    /**
     * A comissao da empresa vence o padrao do site.
     *
     * @return void
     */
    public function test_company_overrides_site(): void {
        $this->company->set('commissionpct', 15.0);
        $this->company->update();

        $offer = $this->make_offer(offer::TYPE_SINGLE, [(int) $this->course->id]);

        $this->assertEquals(15.0, api::resolve_commission_percent($offer));
    }

    /**
     * Zero e um valor, nao ausencia.
     *
     * Se zero fosse tratado como vazio, o parceiro isento voltaria a pagar o
     * padrao do site sem ninguem ter mudado nada. E o bug mais silencioso
     * possivel neste codigo.
     *
     * @return void
     */
    public function test_zero_percent_is_honoured(): void {
        $this->company->set('commissionpct', 0.0);
        $this->company->update();

        $offer = $this->make_offer(offer::TYPE_SINGLE, [(int) $this->course->id]);

        $this->assertEquals(0.0, api::resolve_commission_percent($offer));
    }

    /**
     * A politica do curso vence a da empresa em oferta de curso unico.
     *
     * @return void
     */
    public function test_course_policy_wins_for_single_course(): void {
        $this->company->set('commissionpct', 15.0);
        $this->company->update();
        $this->set_course_policy(5.0);

        $offer = $this->make_offer(offer::TYPE_SINGLE, [(int) $this->course->id]);

        $this->assertEquals(5.0, api::resolve_commission_percent($offer));
    }

    /**
     * Em combo, a politica de curso NAO se aplica.
     *
     * Tres cursos com percentuais diferentes nao produzem um percentual do
     * pacote. Vale o que a empresa negociou.
     *
     * @return void
     */
    public function test_course_policy_ignored_in_bundle(): void {
        $this->company->set('commissionpct', 15.0);
        $this->company->update();
        $this->set_course_policy(5.0);

        $second = $this->getDataGenerator()->create_course([
            'category' => $this->company->get('categoryid'),
        ]);
        $offer = $this->make_offer(offer::TYPE_BUNDLE, [(int) $this->course->id, (int) $second->id]);

        $this->assertEquals(15.0, api::resolve_commission_percent($offer));
    }

    /**
     * Valores fora da faixa sao contidos em 0 e 100.
     *
     * Um percentual acima de 100 produziria marketplace_fee maior que o preco,
     * e o Mercado Pago recusaria a preferencia com o aluno ja no checkout.
     *
     * @return void
     */
    public function test_out_of_range_is_clamped(): void {
        $offer = $this->make_offer(offer::TYPE_SINGLE, [(int) $this->course->id]);

        $this->set_course_policy(150.0);
        $this->assertEquals(100.0, api::resolve_commission_percent($offer));
    }
}
