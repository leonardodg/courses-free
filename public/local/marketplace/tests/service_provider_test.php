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

use PHPUnit\Framework\Attributes\CoversClass;
use local_marketplace\payment\service_provider;

/**
 * Entrega do que foi pago.
 *
 * O Mercado Pago REENVIA webhook - varias vezes, para o mesmo pagamento. Se a
 * entrega nao for idempotente, o aluno ganha meses de acesso a cada reenvio e a
 * contagem de ciclos vira ficcao.
 *
 * @package    local_marketplace
 * @copyright  2026 LeoDG <callme@leodg.dev>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[CoversClass(\local_marketplace\payment\service_provider::class)]
final class service_provider_test extends \advanced_testcase {
    /** @var \stdClass */
    protected $user;

    /** @var company */
    protected $company;

    /**
     * Cria empresa, curso e aluno.
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
    }

    /**
     * Cria uma oferta da empresa de teste.
     *
     * @param string $mode
     * @param int $days
     * @return offer
     */
    protected function make_offer(string $mode, int $days): offer {
        $course = $this->getDataGenerator()->create_course([
            'category' => $this->company->get('categoryid'),
        ]);

        $o = new offer();
        $o->set('companyid', (int) $this->company->get('id'));
        $o->set('name', 'Oferta');
        $o->set('offertype', offer::TYPE_SINGLE);
        $o->set('price', 50.0);
        $o->set('currency', 'BRL');
        $o->set('accessmode', $mode);
        $o->set('accessdays', $days);
        $o->set('status', offer::STATUS_PUBLISHED);
        $o->create();
        $o->add_course((int) $course->id);

        return $o;
    }

    /**
     * A primeira entrega cria o direito com um ciclo.
     *
     * @return void
     */
    public function test_first_delivery_creates_entitlement(): void {
        $offer = $this->make_offer(offer::ACCESS_DAYS, 30);

        service_provider::deliver_order('offer', (int) $offer->get('id'), 1, (int) $this->user->id);

        $active = entitlement::get_active_for_user((int) $this->user->id);
        $this->assertCount(1, $active);

        $ent = reset($active);
        $this->assertSame(1, (int) $ent->get('cycles'));
        $this->assertGreaterThan(time(), (int) $ent->get('timeend'));
    }

    /**
     * Entregar de novo ESTENDE, e nao duplica.
     *
     * Um segundo direito para a mesma oferta faria o aluno aparecer duas vezes
     * no relatorio e quebraria a contagem de ciclos.
     *
     * @return void
     */
    public function test_second_delivery_extends_instead_of_duplicating(): void {
        $offer = $this->make_offer(offer::ACCESS_DAYS, 30);
        $userid = (int) $this->user->id;

        service_provider::deliver_order('offer', (int) $offer->get('id'), 1, $userid);
        $active = entitlement::get_active_for_user($userid);
        $firstend = (int) reset($active)->get('timeend');

        service_provider::deliver_order('offer', (int) $offer->get('id'), 2, $userid);

        $active = entitlement::get_active_for_user($userid);
        $this->assertCount(1, $active, 'nao pode nascer um segundo direito');

        $second = reset($active);
        $this->assertSame(2, (int) $second->get('cycles'));
        $this->assertSame(
            $firstend + (30 * DAYSECS),
            (int) $second->get('timeend'),
            'a renovacao soma ao vencimento atual, nao recomeca de agora'
        );
    }

    /**
     * Renovar antes de vencer nao encurta o que ja foi pago.
     *
     * Somar a partir de "agora" tiraria os dias restantes de quem se antecipa -
     * punindo exatamente o assinante mais organizado.
     *
     * @return void
     */
    public function test_early_renewal_does_not_shorten_paid_time(): void {
        $offer = $this->make_offer(offer::ACCESS_RECURRING, 30);
        $userid = (int) $this->user->id;

        service_provider::deliver_order('offer', (int) $offer->get('id'), 1, $userid);
        $active = entitlement::get_active_for_user($userid);
        $before = (int) reset($active)->get('timeend');

        service_provider::deliver_order('offer', (int) $offer->get('id'), 2, $userid);
        $active = entitlement::get_active_for_user($userid);
        $after = (int) reset($active)->get('timeend');

        $this->assertSame($before + (30 * DAYSECS), $after);
    }

    /**
     * Em oferta vitalicia o ciclo conta, mas a validade nao muda.
     *
     * O numero serve ao relatorio e ao limite de cobrancas, nao a data.
     *
     * @return void
     */
    public function test_lifetime_counts_cycle_without_expiry(): void {
        $offer = $this->make_offer(offer::ACCESS_LIFETIME, 0);
        $userid = (int) $this->user->id;

        service_provider::deliver_order('offer', (int) $offer->get('id'), 1, $userid);
        service_provider::deliver_order('offer', (int) $offer->get('id'), 2, $userid);

        $active = entitlement::get_active_for_user($userid);
        $ent = reset($active);
        $this->assertSame(2, (int) $ent->get('cycles'));
        $this->assertSame(0, (int) $ent->get('timeend'));
    }

    /**
     * O valor cobrado vem do servidor, nunca do navegador.
     *
     * @return void
     */
    public function test_payable_uses_offer_price(): void {
        $offer = $this->make_offer(offer::ACCESS_DAYS, 30);

        $payable = service_provider::get_payable('offer', (int) $offer->get('id'));

        $this->assertEquals(50.0, $payable->get_amount());
        $this->assertSame('BRL', $payable->get_currency());
    }
}
