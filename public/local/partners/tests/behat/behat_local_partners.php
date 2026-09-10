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

/**
 * Passos de teste da captacao de parceiros.
 *
 * @package    local_partners
 * @category   test
 * @author     LeoDG <callme@leodg.dev>
 * @copyright  2026 LeoDG <callme@leodg.dev>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../../../lib/behat/behat_base.php');

use Behat\Mink\Exception\ExpectationException;

/**
 * O que so o navegador prova sobre estas paginas.
 *
 * A promessa central desta reforma - "renderiza igual sob theme_boost,
 * theme_moove e theme_ldg, e cabe num celular" - nao aparece em teste nenhum de
 * servidor. O HTML sai identico em qualquer largura e em qualquer tema; o que
 * muda e o que o navegador calcula a partir do CSS.
 *
 * Sete defeitos desta reforma so apareceram medindo: o miolo preso em 720px, a
 * moldura do tema em volta, o texto do botao saindo azul, o botao com 110px de
 * altura, oito campos numa coluna so, a coluna com 644px num viewport de 390, e
 * o docblock do template virando paragrafo na tela. Nenhum quebrou um teste.
 * Estes passos sao a rede para a proxima vez.
 *
 * @package    local_partners
 * @category   test
 * @copyright  2026 LeoDG <callme@leodg.dev>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class behat_local_partners extends behat_base {
    /**
     * Os elementos indicados ficam em uma coluna so.
     *
     * A afirmacao e sobre POSICAO, e nao sobre classe de CSS: todos comecam na
     * mesma coordenada horizontal e descem na vertical. Conferir se a media
     * query "existe" nao prova que ela venceu.
     *
     * @Then /^the "(?P<selector_string>(?:[^"]|\\")*)" elements should be stacked in one column$/
     *
     * @param string $selector Seletor CSS.
     * @return void
     */
    public function the_elements_should_be_stacked_in_one_column(string $selector): void {
        $caixas = $this->medir_caixas($selector);
        $esquerdas = array_unique(array_column($caixas, 'left'));

        if (count($esquerdas) !== 1) {
            throw new ExpectationException(
                sprintf(
                    'Esperava "%s" em uma coluna, e os elementos comecam em %d posicoes horizontais: %s.',
                    $selector,
                    count($esquerdas),
                    implode(', ', $esquerdas)
                ),
                $this->getSession()
            );
        }
    }

    /**
     * Os elementos indicados ficam lado a lado, no numero de colunas pedido.
     *
     * @Then /^the "(?P<selector_string>(?:[^"]|\\")*)" elements should sit in (?P<count_number>\d+) columns$/
     *
     * @param string $selector Seletor CSS.
     * @param int $esperado Numero de colunas.
     * @return void
     */
    public function the_elements_should_sit_in_columns(string $selector, int $esperado): void {
        $caixas = $this->medir_caixas($selector);
        $colunas = count(array_unique(array_column($caixas, 'left')));

        if ($colunas !== (int) $esperado) {
            throw new ExpectationException(
                sprintf('Esperava %d colunas em "%s", e encontrei %d.', $esperado, $selector, $colunas),
                $this->getSession()
            );
        }
    }

    /**
     * A pagina nao rola na horizontal.
     *
     * E o defeito mais comum de porte de layout para celular, e o que nenhum
     * outro teste pega: a pagina "funciona", so que o visitante precisa
     * arrastar para o lado para ler.
     *
     * A comparacao e com a largura de CADA elemento, e nao com o scrollWidth do
     * documento: um overflow:hidden em qualquer ancestral esconde o estouro do
     * scrollWidth e deixa o defeito passar.
     *
     * @Then the page should not scroll sideways
     * @return void
     */
    public function the_page_should_not_scroll_sideways(): void {
        $estouro = $this->evaluate_script(
            '(function() {'
            . 'var largura = document.documentElement.clientWidth;'
            . 'var piores = [];'
            . 'var todos = document.querySelectorAll(".ldgp, .ldgp *");'
            . 'for (var i = 0; i < todos.length; i++) {'
            . '  var r = todos[i].getBoundingClientRect();'
            . '  if (r.width > 0 && Math.round(r.right) > largura + 1) {'
            . '    piores.push(todos[i].className + " ate " + Math.round(r.right));'
            . '  }'
            . '}'
            . 'return {largura: largura, piores: piores.slice(0, 5)};'
            . '})()'
        );

        if (!empty($estouro['piores'])) {
            throw new ExpectationException(
                sprintf(
                    'A pagina tem %dpx de largura e algo passa disso: %s.',
                    $estouro['largura'],
                    implode(' | ', $estouro['piores'])
                ),
                $this->getSession()
            );
        }
    }

    /**
     * A barra de secoes gruda logo abaixo do cabecalho ao rolar.
     *
     * E o unico jeito de pegar "o sticky parou de funcionar porque um ancestral
     * ganhou overflow: hidden" - falha que nao produz erro, nao produz log, e
     * so aparece para quem rola a pagina.
     *
     * @Then the section bar should stick below the header
     * @return void
     */
    public function the_section_bar_should_stick_below_the_header(): void {
        $medida = $this->evaluate_script(
            '(function() {'
            . 'var barra = document.querySelector(".ldgp-bar");'
            . 'if (!barra) { return null; }'
            . 'var cabecalho = document.querySelector(".navbar.fixed-top");'
            . 'var alturaCabecalho = cabecalho ? cabecalho.getBoundingClientRect().height : 0;'
            . 'return {topo: barra.getBoundingClientRect().top, cabecalho: alturaCabecalho};'
            . '})()'
        );

        if ($medida === null) {
            throw new ExpectationException('Nao ha barra de secoes nesta pagina.', $this->getSession());
        }

        // Dois pixels de folga: o navegador arredonda a posicao quando a pagina
        // esta a meio caminho de um pixel logico.
        if (abs($medida['topo'] - $medida['cabecalho']) > 2) {
            throw new ExpectationException(
                sprintf(
                    'A barra deveria parar em %dpx, logo abaixo do cabecalho, e parou em %dpx.',
                    round($medida['cabecalho']),
                    round($medida['topo'])
                ),
                $this->getSession()
            );
        }
    }

    /**
     * Todo alvo de toque tem pelo menos a altura pedida.
     *
     * @Then /^every "(?P<selector_string>(?:[^"]|\\")*)" touch target should be at least (?P<pixels_number>\d+) pixels tall$/
     *
     * @param string $selector Seletor CSS.
     * @param int $minimo Altura minima em pixels.
     * @return void
     */
    public function every_touch_target_should_be_at_least_pixels_tall(string $selector, int $minimo): void {
        $alturas = array_column($this->medir_caixas($selector), 'height');
        $menor = min($alturas);

        if ($menor < (int) $minimo) {
            throw new ExpectationException(
                sprintf('O menor alvo "%s" tem %dpx de altura, e o minimo e %dpx.', $selector, $menor, $minimo),
                $this->getSession()
            );
        }
    }

    /**
     * O campo-armadilha esta fora da tela.
     *
     * A regra que o esconde ja morou no tema, e por isso o campo aparecia para
     * todo visitante sob theme_boost e theme_moove. Este passo roda nos tres
     * temas justamente por causa disso.
     *
     * @Then the honeypot field should be off screen
     * @return void
     */
    public function the_honeypot_field_should_be_off_screen(): void {
        $esquerda = $this->evaluate_script(
            '(function() {'
            . 'var el = document.querySelector("#fitem_id_fax") || document.querySelector(".local-partners-honeypot");'
            . 'if (!el) { return null; }'
            . 'return el.getBoundingClientRect().left;'
            . '})()'
        );

        if ($esquerda === null) {
            throw new ExpectationException('Nao encontrei o campo-armadilha nesta pagina.', $this->getSession());
        }

        if ($esquerda > -1000) {
            throw new ExpectationException(
                sprintf(
                    'O campo-armadilha esta em %dpx da esquerda, ou seja, visivel. '
                    . 'A regra que o esconde nao chegou a este tema.',
                    round($esquerda)
                ),
                $this->getSession()
            );
        }
    }

    /**
     * A pagina esta no modo de cor indicado.
     *
     * @Then /^the partner page should be in "(?P<mode_string>dark|light)" mode$/
     *
     * @param string $esperado 'dark' ou 'light'.
     * @return void
     */
    public function the_partner_page_should_be_in_mode(string $esperado): void {
        $modo = $this->evaluate_script(
            '(function() {'
            . 'var el = document.querySelector(".ldgp");'
            . 'return el ? el.getAttribute("data-bs-theme") : null;'
            . '})()'
        );

        if ($modo !== $esperado) {
            throw new ExpectationException(
                sprintf('Esperava a pagina em modo "%s", e ela esta em "%s".', $esperado, (string) $modo),
                $this->getSession()
            );
        }
    }

    /**
     * Mede a caixa de cada elemento que casa com o seletor.
     *
     * @param string $selector
     * @return array
     */
    protected function medir_caixas(string $selector): array {
        // ENVOLVIDO NUMA FUNCAO de proposito: o evaluate_script avalia uma
        // EXPRESSAO, e nao um bloco. Declarar const ali devolve
        // "Unexpected token 'const'" vindo do Chrome, longe da causa.
        $caixas = $this->evaluate_script(
            '(function() {'
            . 'var todos = document.querySelectorAll(' . json_encode($selector) . ');'
            . 'var saida = [];'
            . 'for (var i = 0; i < todos.length; i++) {'
            . '  var r = todos[i].getBoundingClientRect();'
            . '  saida.push({left: Math.round(r.left), top: Math.round(r.top),'
            . '              width: Math.round(r.width), height: Math.round(r.height)});'
            . '}'
            . 'return saida;'
            . '})()'
        );

        if (empty($caixas)) {
            throw new ExpectationException(
                sprintf('Nao ha nenhum elemento "%s" nesta pagina.', $selector),
                $this->getSession()
            );
        }

        return $caixas;
    }
}
