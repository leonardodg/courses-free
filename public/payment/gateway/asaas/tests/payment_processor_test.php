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

use PHPUnit\Framework\Attributes\CoversClass;

/**
 * As regras que decidem dinheiro e acesso.
 *
 * @package    paygw_asaas
 * @copyright  2026 LeoDG <callme@leodg.dev>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[CoversClass(\paygw_asaas\payment_processor::class)]
final class payment_processor_test extends \advanced_testcase {
    /**
     * A comissao sai do valor BRUTO, e o netValue da resposta e ignorado.
     *
     * Foi o contrario ate a comissao passar a ser sobre o bruto: enquanto o
     * split ia como percentualValue, o Asaas dividia o liquido e a estimativa
     * tinha que acompanhar. Agora o split vai como fixedValue calculado sobre o
     * cheio, e estimar sobre o liquido criaria divergencia com o que foi
     * efetivamente enviado ao gateway.
     *
     * @return void
     */
    public function test_fee_uses_gross_value(): void {
        $fee = payment_processor::fee_from(['netValue' => 99.01], 100.0, 25.0);

        $this->assertEqualsWithDelta(25.00, $fee, 0.001);
    }

    /**
     * Com split na resposta, vale o split - e nao o percentual.
     *
     * @return void
     */
    public function test_fee_reads_the_real_split(): void {
        $payment = [
            'netValue' => 97.52,
            'split' => [
                ['walletId' => 'plataforma', 'status' => 'AWAITING_CREDIT', 'totalValue' => 24.38],
            ],
        ];

        $this->assertEqualsWithDelta(24.38, payment_processor::fee_from($payment, 100.0, 25.0, 'plataforma'), 0.001);
    }

    /**
     * Split CANCELLED da comissao ZERO, e nao o percentual.
     *
     * Este teste existe por causa de um furo concreto: se o vendedor der baixa
     * manual na cobranca (receiveInCash), o Asaas cancela o split - dinheiro
     * que nao passou por ele nao tem como ser dividido - e a plataforma recebe
     * nada. Calculando o percentual, o relatorio anunciaria R$ 25,00 de
     * comissao que nunca chegariam.
     *
     * Relatorio financeiro que discorda do extrato e pior que nenhum.
     *
     * @return void
     */
    public function test_cancelled_split_means_zero_commission(): void {
        $payment = [
            'netValue' => 100.0,
            'split' => [
                ['walletId' => 'plataforma', 'status' => 'CANCELLED', 'totalValue' => 25.00],
            ],
        ];

        $this->assertEqualsWithDelta(0.0, payment_processor::fee_from($payment, 100.0, 25.0, 'plataforma'), 0.001);
    }

    /**
     * Split recusado tambem nao conta.
     *
     * @return void
     */
    public function test_refused_split_means_zero_commission(): void {
        $payment = [
            'split' => [['walletId' => 'plataforma', 'status' => 'REFUSED', 'totalValue' => 25.00]],
        ];

        $this->assertEqualsWithDelta(0.0, payment_processor::fee_from($payment, 100.0, 25.0, 'plataforma'), 0.001);
    }

    /**
     * Split de outra carteira nao vira comissao nossa.
     *
     * @return void
     */
    public function test_other_wallets_are_not_our_commission(): void {
        $payment = [
            'netValue' => 97.52,
            'split' => [
                ['walletId' => 'de-outra-pessoa', 'status' => 'DONE', 'totalValue' => 40.00],
                ['walletId' => 'plataforma', 'status' => 'DONE', 'totalValue' => 24.38],
            ],
        ];

        $this->assertEqualsWithDelta(24.38, payment_processor::fee_from($payment, 100.0, 25.0, 'plataforma'), 0.001);
    }

    /**
     * Sem split na resposta, o percentual sobre o BRUTO e a estimativa certa.
     *
     * E o caso da criacao da cobranca, quando o split ainda nao existe. O
     * numero tem que bater com o fixedValue que o build_split acabou de montar,
     * senao a tela mostra uma comissao e o gateway recebe outra.
     *
     * @return void
     */
    public function test_without_split_falls_back_to_percentage(): void {
        $this->assertEqualsWithDelta(
            25.00,
            payment_processor::fee_from(['netValue' => 97.52], 100.0, 25.0, 'plataforma'),
            0.001
        );
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
     * Evento de webhook nao e status de cobranca.
     *
     * A confusao custou uma volta: existe o STATUS RECEIVED_IN_CASH, mas nao
     * existe o EVENTO PAYMENT_RECEIVED_IN_CASH. O Asaas recusa quem tenta
     * cadastra-lo com "O evento [PAYMENT_RECEIVED_IN_CASH] e invalido", e a
     * baixa manual chega como PAYMENT_RECEIVED.
     *
     * @return void
     */
    public function test_relevant_events_are_not_statuses(): void {
        $this->assertTrue(payment_processor::is_relevant_event('PAYMENT_RECEIVED'));
        $this->assertTrue(payment_processor::is_relevant_event('PAYMENT_CONFIRMED'));

        $this->assertFalse(
            payment_processor::is_relevant_event('PAYMENT_RECEIVED_IN_CASH'),
            'este evento nao existe no Asaas'
        );
        $this->assertFalse(
            payment_processor::is_relevant_event('RECEIVED_IN_CASH'),
            'isto e um status, nao um evento'
        );
        $this->assertFalse(payment_processor::is_relevant_event('PAYMENT_CREATED'));
        $this->assertFalse(payment_processor::is_relevant_event('PAYMENT_OVERDUE'));
        $this->assertFalse(
            payment_processor::is_relevant_event('PAYMENT_REFUNDED'),
            'estorno nao revoga acesso por automacao'
        );
        $this->assertFalse(payment_processor::is_relevant_event(''));
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

    /**
     * Com base liquida, a estimativa usa o netValue da resposta.
     *
     * Tem que acompanhar o split enviado: mandamos percentualValue, entao a
     * estimativa que mostramos precisa ser do liquido tambem.
     *
     * @return void
     */
    public function test_base_liquida_estima_sobre_o_net(): void {
        $fee = payment_processor::fee_from(['netValue' => 97.52], 100.0, 25.0, '', 'net');

        $this->assertEqualsWithDelta(24.38, $fee, 0.001);
    }

    /**
     * Base liquida sem netValue ainda na resposta superestima, de proposito.
     *
     * E o momento da criacao da cobranca. O numero certo chega pelo webhook com
     * o split real; ate la e melhor prometer menos ao vendedor do que mais.
     *
     * @return void
     */
    public function test_base_liquida_sem_net_usa_o_cheio(): void {
        $fee = payment_processor::fee_from([], 100.0, 25.0, '', 'net');

        $this->assertEqualsWithDelta(25.00, $fee, 0.001);
    }
}
