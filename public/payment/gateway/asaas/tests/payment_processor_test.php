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

namespace paygw_asaas;

/**
 * As regras que decidem dinheiro e acesso.
 *
 * @package    paygw_asaas
 * @copyright  2026 Leonardo Della Giustina
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \paygw_asaas\payment_processor
 */
final class payment_processor_test extends \advanced_testcase {
    /**
     * A comissao sai do netValue, nao do valor cheio.
     *
     * O Asaas tira a propria taxa antes de dividir. Calcular 25% de R$ 100
     * daria R$ 25,00, mas o que de fato e repassado sao 25% de R$ 99,01. A
     * diferenca e pequena por venda e vira divergencia de conciliacao no mes.
     *
     * @return void
     */
    public function test_fee_uses_net_value(): void {
        $fee = payment_processor::fee_from(['netValue' => 99.01], 100.0, 25.0);

        $this->assertEqualsWithDelta(24.75, $fee, 0.001);
    }

    /**
     * Sem netValue na resposta, cai no valor cheio.
     *
     * Erra por centavos, o que e melhor do que nao registrar comissao nenhuma.
     *
     * @return void
     */
    public function test_fee_falls_back_to_gross(): void {
        $this->assertEqualsWithDelta(25.0, payment_processor::fee_from([], 100.0, 25.0), 0.001);
        $this->assertEqualsWithDelta(25.0, payment_processor::fee_from(['netValue' => 0], 100.0, 25.0), 0.001);
    }

    /**
     * Comissao zero da zero, e nao o padrao.
     *
     * @return void
     */
    public function test_zero_commission_gives_zero_fee(): void {
        $this->assertEqualsWithDelta(0.0, payment_processor::fee_from(['netValue' => 99.01], 100.0, 0.0), 0.001);
    }

    /**
     * Quais status liberam o acesso.
     *
     * CONFIRMED e o cartao autorizado com o credito ainda por cair. Segurar o
     * curso ate a liquidacao puniria o aluno por um prazo bancario.
     *
     * @return void
     */
    public function test_paid_statuses(): void {
        $this->assertTrue(payment_processor::is_paid('RECEIVED'));
        $this->assertTrue(payment_processor::is_paid('CONFIRMED'));
        $this->assertTrue(payment_processor::is_paid('received'), 'aceita minusculas');

        $this->assertFalse(payment_processor::is_paid('PENDING'));
        $this->assertFalse(payment_processor::is_paid('OVERDUE'));
        $this->assertFalse(payment_processor::is_paid('REFUNDED'));
        $this->assertFalse(payment_processor::is_paid(''));
    }

    /**
     * A forma de cobranca invalida cai em "deixa o aluno escolher".
     *
     * @return void
     */
    public function test_billing_type_falls_back(): void {
        $this->resetAfterTest();

        unset_config('billingtype', 'paygw_asaas');
        $this->assertSame('UNDEFINED', payment_processor::billing_type());

        set_config('billingtype', 'CARTAO_MAGICO', 'paygw_asaas');
        $this->assertSame('UNDEFINED', payment_processor::billing_type());

        set_config('billingtype', 'pix', 'paygw_asaas');
        $this->assertSame('PIX', payment_processor::billing_type(), 'normaliza para maiusculas');
    }

    /**
     * Vencimento zero ou negativo cai no padrao.
     *
     * Uma cobranca com vencimento no passado e recusada pelo Asaas, e o aluno
     * seria quem descobriria.
     *
     * @return void
     */
    public function test_due_days_never_zero(): void {
        $this->resetAfterTest();

        unset_config('duedays', 'paygw_asaas');
        $this->assertSame(3, payment_processor::due_days());

        set_config('duedays', 0, 'paygw_asaas');
        $this->assertSame(3, payment_processor::due_days());

        set_config('duedays', -5, 'paygw_asaas');
        $this->assertSame(3, payment_processor::due_days());

        set_config('duedays', 10, 'paygw_asaas');
        $this->assertSame(10, payment_processor::due_days());
    }

    /**
     * O retorno ao Moodle vem ligado, e pode ser desligado.
     *
     * O padrao e ligado porque e a experiencia certa para o aluno. O
     * interruptor existe porque o Asaas so aceita URL de retorno de uma conta
     * com SITE cadastrado - sem isso ele recusa a cobranca INTEIRA, e nao so o
     * retorno. Um vendedor que nao consiga cadastrar o dominio precisa
     * continuar vendendo.
     *
     * @return void
     */
    public function test_callback_defaults_to_on(): void {
        $this->resetAfterTest();

        unset_config('usecallback', 'paygw_asaas');
        $this->assertTrue(payment_processor::use_callback());

        set_config('usecallback', 0, 'paygw_asaas');
        $this->assertFalse(payment_processor::use_callback());

        set_config('usecallback', 1, 'paygw_asaas');
        $this->assertTrue(payment_processor::use_callback());
    }

    /**
     * A URL do webhook e a que o administrador cadastra no Asaas.
     *
     * @return void
     */
    public function test_webhook_url(): void {
        $this->assertStringEndsWith(
            '/payment/gateway/asaas/webhook.php',
            payment_processor::webhook_url()->out(false)
        );
    }
}
