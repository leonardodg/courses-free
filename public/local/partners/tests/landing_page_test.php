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

namespace local_partners\output;

use PHPUnit\Framework\Attributes\CoversClass;
use local_marketplace\plan;

/**
 * A pagina de captacao, do lado do servidor.
 *
 * O que estes testes protegem: a landing e a unica pagina PUBLICA do projeto, e
 * tudo que ela mostra sobre preco vem do banco. Um numero errado aqui e uma
 * oferta errada na cara de quem nunca entrou no site - nao ha login entre o
 * defeito e o visitante.
 *
 * @package    local_partners
 * @copyright  2026 LeoDG <callme@leodg.dev>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[CoversClass(\local_partners\output\landing_page::class)]
final class landing_page_test extends \advanced_testcase {
    /**
     * O contexto que o template recebe.
     *
     * O RENDERER_TARGET_GENERAL nao e enfeite: sem ele o Moodle entrega o
     * core_renderer_cli quando o PHPUnit roda em linha de comando, e os testes
     * de negacao passariam verificando nada.
     *
     * @return array
     */
    private function contexto(): array {
        global $PAGE;

        return (new landing_page())->export_for_template(
            $PAGE->get_renderer('local_partners', null, RENDERER_TARGET_GENERAL)
        );
    }

    /**
     * O HTML que a landing realmente produz.
     *
     * @return string
     */
    private function html(): string {
        global $PAGE;

        $output = $PAGE->get_renderer('local_partners', null, RENDERER_TARGET_GENERAL);

        return $output->render_from_template('local_partners/landing', $this->contexto());
    }

    /**
     * Sem plano publico, a secao de precos nao e anunciada.
     *
     * O portao existe porque uma secao "Planos" vazia numa pagina publica
     * parece defeito, e nao ausencia de oferta.
     *
     * @return void
     */
    public function test_sem_plano_publico_a_secao_de_planos_nao_e_anunciada(): void {
        global $DB;

        $this->resetAfterTest();
        $DB->set_field('local_marketplace_plan', 'ispublic', 0, []);

        $contexto = $this->contexto();

        $this->assertFalse($contexto['hasplans']);
        $this->assertEmpty($contexto['plans']);
    }

    /**
     * O preco vem do banco, e mensalidade zero vira palavra.
     *
     * "R$ 0,00" nao comunica o argumento central do plano de entrada. Quem le
     * um zero formatado pensa em erro de cadastro.
     *
     * @return void
     */
    public function test_o_preco_vem_do_banco_e_nao_do_template(): void {
        $this->resetAfterTest();

        $pornome = [];
        foreach ($this->contexto()['plans'] as $plano) {
            $pornome[$plano['name']] = $plano;
        }

        $starter = plan::get_record_by_shortname('starter');
        $pro = plan::get_record_by_shortname('pro');
        $this->assertNotFalse($starter, 'o seed da instalacao deveria ter criado o plano starter');

        $this->assertTrue($pornome[$starter->get('name')]['isfree']);
        $this->assertFalse($pornome[$pro->get('name')]['isfree']);
        // A comissao sai do registro, com duas casas.
        $this->assertSame('9.90', $pornome[$starter->get('name')]['commissionpct']);
    }

    /**
     * A faixa final e descrita pelo teto da ANTERIOR.
     *
     * Dizer "sem limite de preco" nao ajudaria quem esta comparando planos, e
     * este e o ramo mais sutil da classe - o unico que depende do estado do
     * laco anterior.
     *
     * @return void
     */
    public function test_a_faixa_final_e_descrita_pelo_teto_anterior(): void {
        $this->resetAfterTest();

        $starter = plan::get_record_by_shortname('starter');

        $tiers = [];
        foreach ($this->contexto()['plans'] as $plano) {
            if ($plano['name'] === $starter->get('name')) {
                $tiers = $plano['tiers'];
            }
        }

        $this->assertCount(3, $tiers, 'o starter do seed tem tres faixas');

        $ultima = end($tiers);

        $this->assertSame('4k', $ultima['resolution']);
        // O teto da faixa do meio e 200,00, entao a ultima e "acima de" esse
        // valor - e nao "acima de" o teto dela propria, que e nulo.
        $this->assertStringContainsString('200', $ultima['label']);
    }

    /**
     * Quatro passos e quatro perguntas, com texto de verdade.
     *
     * Uma chave de idioma faltando renderiza [[step5title]] numa pagina
     * publica, em silencio: o Moodle nao lanca, so imprime a chave.
     *
     * @return void
     */
    public function test_quatro_passos_e_quatro_perguntas(): void {
        $this->resetAfterTest();

        $contexto = $this->contexto();

        $this->assertCount(4, $contexto['steps']);
        $this->assertCount(4, $contexto['faq']);

        foreach ($contexto['steps'] as $passo) {
            $this->assertStringNotContainsString('[[', $passo['title']);
            $this->assertStringNotContainsString('[[', $passo['text']);
        }

        foreach ($contexto['faq'] as $pergunta) {
            $this->assertStringNotContainsString('[[', $pergunta['question']);
            $this->assertStringNotContainsString('[[', $pergunta['answer']);
        }
    }

    /**
     * As ancoras da barra apontam para secoes que existem na pagina.
     *
     * A lista de secoes e exportada UMA vez e alimenta os dois lados: os links
     * da barra e os id= das secoes. Escrever a lista duas vezes e exatamente
     * como uma ancora passa a apontar para lugar nenhum sem ninguem notar.
     *
     * @return void
     */
    public function test_as_ancoras_saem_da_mesma_fonte_que_a_barra(): void {
        $this->resetAfterTest();

        $secoes = $this->contexto()['sections'];
        $html = $this->html();

        $this->assertNotEmpty($secoes);

        $ids = array_column($secoes, 'id');
        $this->assertSame($ids, array_unique($ids), 'id de secao repetido faz a ancora pular para a primeira');

        foreach ($secoes as $secao) {
            $this->assertStringNotContainsString('[[', $secao['label']);
            $this->assertStringContainsString('id="' . $secao['id'] . '"', $html);
        }
    }

    /**
     * Quem nunca escolheu ve a pagina escura.
     *
     * @return void
     */
    public function test_modo_escuro_e_o_padrao_para_quem_nunca_escolheu(): void {
        $this->resetAfterTest();

        $this->assertSame('dark', $this->contexto()['colormode']);
    }

    /**
     * Quem escolheu claro ve claro desde o servidor.
     *
     * O estado inicial precisa sair pronto do servidor. Deixar o JavaScript
     * corrigir depois produz um pisca de escuro para claro a cada carregamento.
     *
     * @return void
     */
    public function test_preferencia_do_usuario_manda_no_estado_inicial(): void {
        $this->resetAfterTest();

        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);

        set_user_preference('dark-mode-on', 0, $user);
        $this->assertSame('light', $this->contexto()['colormode']);

        set_user_preference('dark-mode-on', 1, $user);
        $this->assertSame('dark', $this->contexto()['colormode']);
    }

    /**
     * O template renderiza sem lancar.
     *
     * Teste de contexto nao ve erro de mustache: uma chave errada, um bloco nao
     * fechado ou um parcial inexistente so aparecem quando alguem renderiza.
     *
     * @return void
     */
    public function test_o_template_renderiza(): void {
        $this->resetAfterTest();

        $html = $this->html();

        $this->assertStringContainsString('ldgp', $html);
        $this->assertStringNotContainsString('[[', $html);
    }

    /**
     * O docblock do template nao vaza para a tela.
     *
     * Um comentario de mustache termina no PRIMEIRO fecha-chaves duplo. Citar
     * uma tag dentro do comentario - escrever o nome de um bloco entre chaves
     * para explicar de onde vem o dado - encerra o comentario ali, e todo o
     * resto do texto vai para a pagina publica como paragrafo.
     *
     * Aconteceu, e nenhum teste pegou: o contexto estava certo, o phpunit
     * passava, e o defeito so apareceu na captura de tela. Este teste e a rede.
     *
     * @return void
     */
    public function test_o_comentario_do_template_nao_vaza_para_a_tela(): void {
        $this->resetAfterTest();

        $html = $this->html();

        foreach (['@template', 'Context variables', 'Example context'] as $marca) {
            $this->assertStringNotContainsString($marca, $html, 'o docblock do template escapou para a pagina');
        }
    }
}
