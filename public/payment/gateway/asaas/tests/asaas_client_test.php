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

defined('MOODLE_INTERNAL') || die();

// O transporte falso nao e autocarregado: vive em tests/fixtures.
require_once(__DIR__ . '/fixtures/fake_asaas_client.php');

/**
 * A camada HTTP, sem rede.
 *
 * Isto so e possivel por causa da costura make_curl(). O cliente equivalente do
 * Mercado Pago instancia curl inline, e por isso a montagem de corpo e o
 * mapeamento de erro dele nunca foram testados - o unico jeito de exercitar
 * seria bater na API de verdade.
 *
 * @package    paygw_asaas
 * @copyright  2026 Leonardo Della Giustina
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \paygw_asaas\asaas_client
 */
final class asaas_client_test extends \advanced_testcase {
    /**
     * O split e um percentual sobre a carteira da plataforma.
     *
     * @return void
     */
    public function test_build_split(): void {
        $split = asaas_client::build_split('wallet-abc', 25.0);

        $this->assertCount(1, $split);
        $this->assertSame('wallet-abc', $split[0]['walletId']);
        $this->assertEqualsWithDelta(25.0, $split[0]['percentualValue'], 0.0001);
    }

    /**
     * Comissao zero NAO vira split.
     *
     * Um parceiro isento e caso real - a empresa pode ter commissionpct 0. Um
     * split de 0% seria recusado pelo Asaas, e a compra inteira falharia por
     * causa de uma isencao negociada.
     *
     * @return void
     */
    public function test_zero_commission_produces_no_split(): void {
        $this->assertSame([], asaas_client::build_split('wallet-abc', 0.0));
        $this->assertSame([], asaas_client::build_split('wallet-abc', -5.0));
    }

    /**
     * Sem carteira da plataforma nao ha split.
     *
     * Acontece quando o administrador ainda nao configurou a carteira. Melhor
     * cobrar sem comissao - dinheiro na conta do vendedor, conciliavel depois -
     * do que recusar a venda.
     *
     * @return void
     */
    public function test_missing_wallet_produces_no_split(): void {
        $this->assertSame([], asaas_client::build_split('', 25.0));
        $this->assertSame([], asaas_client::build_split('   ', 25.0));
    }

    /**
     * Percentual acima de 100 e contido.
     *
     * @return void
     */
    public function test_split_is_capped_at_one_hundred(): void {
        $split = asaas_client::build_split('wallet-abc', 150.0);

        $this->assertEqualsWithDelta(100.0, $split[0]['percentualValue'], 0.0001);
    }

    /**
     * O prefixo da chave diz o ambiente.
     *
     * @return void
     */
    public function test_environment_of_key(): void {
        $sandbox = '$aact_hmlg_chave-de-exemplo-nao-usada';
        $production = '$aact_chave-de-exemplo-nao-usada';

        $this->assertSame(asaas_client::ENV_SANDBOX, asaas_client::environment_of_key($sandbox));
        $this->assertSame(asaas_client::ENV_PRODUCTION, asaas_client::environment_of_key($production));
        $this->assertSame(asaas_client::ENV_SANDBOX, asaas_client::environment_of_key('  ' . $sandbox . '  '));
    }

    /**
     * Cada ambiente tem a propria base, e nunca a do outro.
     *
     * Uma chave de homologacao apontando para a base de producao devolve 401 -
     * e o inverso e pior: dado de teste indo para a base real.
     *
     * @return void
     */
    public function test_base_urls_are_distinct(): void {
        $this->assertStringContainsString('sandbox', asaas_client::BASE_URL[asaas_client::ENV_SANDBOX]);
        $this->assertStringNotContainsString('sandbox', asaas_client::BASE_URL[asaas_client::ENV_PRODUCTION]);
    }

    /**
     * Ambiente desconhecido cai em homologacao, e nao em producao.
     *
     * Se um valor invalido chegar aqui, errar para o lado do sandbox custa um
     * teste que nao funciona; errar para producao custa uma cobranca real.
     *
     * @return void
     */
    public function test_unknown_environment_falls_back_to_sandbox(): void {
        $client = new asaas_client('chave', 'seja-la-o-que-for');

        $this->assertSame(asaas_client::ENV_SANDBOX, $client->get_environment());
    }

    /**
     * O corpo enviado carrega split, referencia e retorno.
     *
     * @return void
     */
    public function test_create_payment_body(): void {
        $client = new fake_asaas_client('chave', asaas_client::ENV_SANDBOX);
        $client->nextresponse = ['id' => 'pay_1', 'status' => 'PENDING', 'invoiceUrl' => 'https://x/i/1'];

        $client->create_payment([
            'customer' => 'cus_1',
            'billingtype' => 'PIX',
            'value' => 100.0,
            'duedate' => '2026-09-01',
            'description' => 'Curso',
            'externalreference' => 'mdl-1-2-abc',
            'returnurl' => 'https://moodle.test/return.php?ref=mdl-1-2-abc',
            'splitwalletid' => 'wallet-plataforma',
            'splitpercent' => 25.0,
        ]);

        $body = $client->lastbody;

        $this->assertSame('cus_1', $body['customer']);
        $this->assertSame('PIX', $body['billingType']);
        $this->assertSame(100.0, $body['value']);
        $this->assertSame('mdl-1-2-abc', $body['externalReference']);
        $this->assertSame('wallet-plataforma', $body['split'][0]['walletId']);
        $this->assertTrue($body['callback']['autoRedirect']);
    }

    /**
     * Sem comissao, a chave split nao vai no corpo.
     *
     * Um split vazio nao e "sem comissao": e um corpo invalido, e o Asaas
     * recusa a cobranca inteira.
     *
     * @return void
     */
    public function test_create_payment_omits_empty_split(): void {
        $client = new fake_asaas_client('chave', asaas_client::ENV_SANDBOX);
        $client->nextresponse = ['id' => 'pay_1', 'invoiceUrl' => 'https://x/i/1'];

        $client->create_payment([
            'customer' => 'cus_1',
            'billingtype' => 'PIX',
            'value' => 100.0,
            'duedate' => '2026-09-01',
            'externalreference' => 'mdl-1-2-abc',
            'splitwalletid' => 'wallet-plataforma',
            'splitpercent' => 0.0,
        ]);

        $this->assertArrayNotHasKey('split', $client->lastbody);
    }

    /**
     * A descricao e cortada em 500, que e o limite do Asaas.
     *
     * @return void
     */
    public function test_description_is_truncated(): void {
        $client = new fake_asaas_client('chave', asaas_client::ENV_SANDBOX);
        $client->nextresponse = ['id' => 'pay_1', 'invoiceUrl' => 'https://x/i/1'];

        $client->create_payment([
            'customer' => 'cus_1',
            'billingtype' => 'PIX',
            'value' => 10.0,
            'duedate' => '2026-09-01',
            'description' => str_repeat('a', 900),
            'externalreference' => 'mdl-1-2-abc',
        ]);

        $this->assertSame(500, \core_text::strlen($client->lastbody['description']));
    }

    /**
     * A carteira do vendedor e descoberta, nao digitada.
     *
     * @return void
     */
    public function test_wallet_id_is_read_from_the_list(): void {
        $client = new fake_asaas_client('chave', asaas_client::ENV_SANDBOX);
        $client->nextresponse = ['data' => [['id' => '11111111-2222-3333-4444-555555555555']]];

        $this->assertSame('11111111-2222-3333-4444-555555555555', $client->get_wallet_id());
    }

    /**
     * Conta sem carteira devolve vazio em vez de inventar um id.
     *
     * @return void
     */
    public function test_wallet_id_empty_when_there_is_none(): void {
        $client = new fake_asaas_client('chave', asaas_client::ENV_SANDBOX);
        $client->nextresponse = ['data' => []];

        $this->assertSame('', $client->get_wallet_id());
    }

    /**
     * Cliente ja existente e reaproveitado.
     *
     * Criar um cliente novo a cada compra encheria a conta do vendedor de
     * duplicatas do mesmo aluno, atrapalhando a conciliacao dele.
     *
     * @return void
     */
    public function test_existing_customer_is_reused(): void {
        $client = new fake_asaas_client('chave', asaas_client::ENV_SANDBOX);
        $client->nextresponse = ['data' => [['id' => 'cus_ja_existe']]];

        $id = $client->find_or_create_customer('Aluno', 'aluno@example.com', '24971563792');

        $this->assertSame('cus_ja_existe', $id);
        $this->assertCount(1, $client->calls, 'nao deveria ter chamado o POST de criacao');
    }

    /**
     * O erro do Asaas vira mensagem legivel, e nao "erro na API".
     *
     * @return void
     */
    public function test_api_error_carries_the_description(): void {
        $client = new fake_asaas_client('chave', asaas_client::ENV_SANDBOX);
        $client->nextstatus = 400;
        $client->nextresponse = ['errors' => [[
            'code' => 'invalid_action',
            'description' => 'Nao e permitido split para sua propria carteira.',
        ]]];

        try {
            $client->get_payment('pay_1');
            $this->fail('deveria ter lancado');
        } catch (\moodle_exception $e) {
            $this->assertStringContainsString('invalid_action', $e->getMessage());
            $this->assertStringContainsString('propria carteira', $e->getMessage());
        }
    }

    /**
     * Resposta que nao e JSON nao passa como sucesso.
     *
     * @return void
     */
    public function test_non_json_response_is_rejected(): void {
        $client = new fake_asaas_client('chave', asaas_client::ENV_SANDBOX);
        $client->rawresponse = '<html>gateway timeout</html>';

        $this->expectException(\moodle_exception::class);
        $client->get_payment('pay_1');
    }

    /**
     * Falha de rede nao vira resposta vazia.
     *
     * @return void
     */
    public function test_curl_failure_is_reported(): void {
        $client = new fake_asaas_client('chave', asaas_client::ENV_SANDBOX);
        $client->nexterrno = 28;

        $this->expectException(\moodle_exception::class);
        $client->get_payment('pay_1');
    }
}
