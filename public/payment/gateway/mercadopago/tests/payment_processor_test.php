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

namespace paygw_mercadopago;

use PHPUnit\Framework\Attributes\CoversClass;

/**
 * A comissao e o corpo da preferencia.
 *
 * O marketplace_fee e o unico numero deste plugin que move dinheiro, e ficou
 * sem teste enquanto so existia dentro do start_payment(), que precisa de
 * banco, sessao e rede. Aqui ele e exercitado sozinho.
 *
 * @package    paygw_mercadopago
 * @copyright  2026 LeoDG <callme@leodg.dev>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[CoversClass(\paygw_mercadopago\payment_processor::class)]
final class payment_processor_test extends \advanced_testcase {
    /**
     * A comissao incide sobre o bruto, e sai em moeda.
     *
     * Nao ha escolha de base aqui: o marketplace_fee e valor absoluto, e a taxa
     * do Mercado Pago so e conhecida depois do pagamento. Ver docs/adr/0007.
     *
     * @return void
     */
    public function test_comissao_e_absoluta_sobre_o_bruto(): void {
        $this->assertSame(25.0, payment_processor::fee_for(100.0, 25.0));
        $this->assertSame(12.5, payment_processor::fee_for(50.0, 25.0));
    }

    /**
     * A comissao para em centavos.
     *
     * Mandar mais de duas casas faz o Mercado Pago recusar a preferencia
     * inteira, e a recusa apareceria no checkout, diante do aluno.
     *
     * @return void
     */
    public function test_comissao_arredonda_para_centavos(): void {
        $this->assertSame(9.9, payment_processor::fee_for(100.0, 9.9));
        $this->assertSame(8.33, payment_processor::fee_for(33.33, 25.0));
        $this->assertSame(0.25, payment_processor::fee_for(1.0, 25.0));
    }

    /**
     * A comissao nao passa do bruto.
     *
     * Percentual acima de 100 e configuracao errada, e produziria uma
     * preferencia que o Mercado Pago recusa. E a mesma trava do
     * asaas_client::build_split().
     *
     * @return void
     */
    public function test_comissao_nao_passa_do_bruto(): void {
        $this->assertSame(100.0, payment_processor::fee_for(100.0, 150.0));
    }

    /**
     * Sem comissao a cobrar, o valor e zero e nao um numero inventado.
     *
     * @return void
     */
    public function test_sem_comissao_o_valor_e_zero(): void {
        $this->assertSame(0.0, payment_processor::fee_for(100.0, 0.0));
        $this->assertSame(0.0, payment_processor::fee_for(100.0, -5.0));
        $this->assertSame(0.0, payment_processor::fee_for(0.0, 25.0));
    }

    /**
     * O marketplace_fee chega ao corpo da preferencia.
     *
     * No nivel raiz, e nao dentro de items: o Mercado Pago ignora a chave fora
     * do lugar, e o resultado seria uma venda sem comissao que nao acusa erro.
     *
     * @return void
     */
    public function test_marketplace_fee_vai_na_preferencia(): void {
        $body = payment_processor::build_preference_body(
            100.0,
            'BRL',
            'mdl-1-2-abc',
            25.0,
            'https://exemplo.test',
            false
        );

        $this->assertArrayHasKey('marketplace_fee', $body);
        $this->assertSame(25.0, $body['marketplace_fee']);
    }

    /**
     * O item carrega valor e moeda como o Mercado Pago espera.
     *
     * @return void
     */
    public function test_o_item_carrega_valor_e_moeda(): void {
        $body = payment_processor::build_preference_body(
            100.0,
            'BRL',
            'mdl-1-2-abc',
            25.0,
            'https://exemplo.test',
            false
        );

        $this->assertCount(1, $body['items']);
        $this->assertSame(100.0, $body['items'][0]['unit_price']);
        $this->assertSame('BRL', $body['items'][0]['currency_id']);
        $this->assertSame(1, $body['items'][0]['quantity']);
    }

    /**
     * As quatro URLs saem do wwwroot, e a referencia viaja com elas.
     *
     * Endereco escrito a mao aqui mandaria o aluno de volta para outro site, e
     * o webhook para um endpoint que nao existe - o pagamento aconteceria sem
     * ninguem receber a confirmacao.
     *
     * @return void
     */
    public function test_as_urls_saem_do_wwwroot(): void {
        $body = payment_processor::build_preference_body(
            100.0,
            'BRL',
            'mdl-7-9-xyz',
            25.0,
            'https://exemplo.test',
            false
        );

        $esperada = 'https://exemplo.test/payment/gateway/mercadopago/return.php?ref=mdl-7-9-xyz';

        $this->assertSame($esperada, $body['back_urls']['success']);
        $this->assertSame($esperada, $body['back_urls']['pending']);
        $this->assertSame($esperada, $body['back_urls']['failure']);
        $this->assertSame(
            'https://exemplo.test/payment/gateway/mercadopago/webhook.php',
            $body['notification_url']
        );
        $this->assertSame('mdl-7-9-xyz', $body['external_reference']);
    }

    /**
     * O wallet_purchase fica preso ao modo de teste.
     *
     * Em teste ele existe porque um visitante nao e usuario de teste, e o
     * Mercado Pago recusa a compra. Em producao ele cortaria pagamento sem
     * cadastro, boleto e dinheiro - ou seja, conversao real, para resolver um
     * problema que so existe no sandbox.
     *
     * @return void
     */
    public function test_purpose_so_em_modo_teste(): void {
        $teste = payment_processor::build_preference_body(
            100.0,
            'BRL',
            'mdl-1-2-abc',
            25.0,
            'https://exemplo.test',
            true
        );
        $producao = payment_processor::build_preference_body(
            100.0,
            'BRL',
            'mdl-1-2-abc',
            25.0,
            'https://exemplo.test',
            false
        );

        $this->assertSame('wallet_purchase', $teste['purpose']);
        $this->assertArrayNotHasKey('purpose', $producao);
    }
}
