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

/**
 * Partes puras do cliente do Mercado Pago.
 *
 * Testa o que nao depende de rede: PKCE, mapa de moeda e montagem da URL de
 * autorizacao. Sao justamente os pontos onde um erro nao aparece em
 * desenvolvimento - o Mercado Pago recusa em producao, com mensagem generica.
 *
 * @package    paygw_mercadopago
 * @copyright  2026 Leonardo Della Giustina
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \paygw_mercadopago\mp_client
 */
final class mp_client_test extends \advanced_testcase {
    /**
     * O verifier respeita o tamanho exigido pela RFC 7636.
     *
     * O Mercado Pago exige entre 43 e 128 caracteres.
     *
     * @return void
     */
    public function test_code_verifier_length_and_charset(): void {
        $verifier = mp_client::create_code_verifier();

        $this->assertGreaterThanOrEqual(43, strlen($verifier));
        $this->assertLessThanOrEqual(128, strlen($verifier));
        $this->assertMatchesRegularExpression(
            '/^[A-Za-z0-9\-._~]+$/',
            $verifier,
            'so caracteres unreserved, senao precisaria de escape na URL'
        );
    }

    /**
     * Dois verifiers seguidos nao se repetem.
     *
     * Um verifier previsivel anula o PKCE: quem capturasse o codigo de
     * autorizacao conseguiria adivinhar o par e trocar por token.
     *
     * @return void
     */
    public function test_code_verifier_is_not_predictable(): void {
        $this->assertNotSame(mp_client::create_code_verifier(), mp_client::create_code_verifier());
    }

    /**
     * O challenge e base64url do SHA-256, sem padding.
     *
     * Vetor da propria RFC 7636, secao A.
     *
     * @return void
     */
    public function test_code_challenge_matches_rfc_vector(): void {
        $verifier = 'dBjftJeZ4CVP-mB92K27uhbUJU1p1r_wW1gFWFOEjXk';
        $expected = 'E9Melhoa2OwvFrEMTJguCHaoeK1t8URWbuGJSstw-cM';

        $this->assertSame($expected, mp_client::create_code_challenge($verifier));
    }

    /**
     * O challenge nao carrega +, / nem =.
     *
     * base64 comum faria o Mercado Pago recusar a autorizacao.
     *
     * @return void
     */
    public function test_code_challenge_is_url_safe(): void {
        $challenge = mp_client::create_code_challenge(mp_client::create_code_verifier());

        $this->assertStringNotContainsString('+', $challenge);
        $this->assertStringNotContainsString('/', $challenge);
        $this->assertStringNotContainsString('=', $challenge);
    }

    /**
     * Cada site tem a moeda do seu pais.
     *
     * @return void
     */
    public function test_currency_for_site(): void {
        $this->assertSame('BRL', mp_client::currency_for_site('MLB'));
        $this->assertSame('ARS', mp_client::currency_for_site('MLA'));
        $this->assertSame('BRL', mp_client::currency_for_site('mlb'), 'aceita minusculas');
        $this->assertSame('', mp_client::currency_for_site('XXX'), 'site desconhecido nao inventa moeda');
    }

    /**
     * A URL de autorizacao vai para o dominio do PAIS.
     *
     * O OAuth do Mercado Pago nao tem dominio unico. Mandar um vendedor
     * argentino para .com.br nao da erro claro: a tela apenas nao reconhece a
     * conta dele.
     *
     * @return void
     */
    public function test_authorization_url_uses_country_domain(): void {
        $br = mp_client::build_authorization_url('123', 'https://x.test/cb', 'st', 'ch', 'MLB');
        $ar = mp_client::build_authorization_url('123', 'https://x.test/cb', 'st', 'ch', 'MLA');

        $this->assertStringStartsWith('https://auth.mercadopago.com.br/authorization?', $br);
        $this->assertStringStartsWith('https://auth.mercadopago.com.ar/authorization?', $ar);
    }

    /**
     * Site desconhecido cai no Brasil em vez de gerar URL invalida.
     *
     * @return void
     */
    public function test_authorization_url_falls_back_to_brazil(): void {
        $url = mp_client::build_authorization_url('123', 'https://x.test/cb', 'st', 'ch', 'XXX');

        $this->assertStringStartsWith('https://auth.mercadopago.com.br/authorization?', $url);
    }

    /**
     * A autorizacao leva o challenge e declara S256.
     *
     * Sem o metodo declarado, o servidor assume "plain" e o hash enviado nao
     * casaria com o verifier na troca.
     *
     * @return void
     */
    public function test_authorization_url_carries_pkce(): void {
        $url = mp_client::build_authorization_url('cid', 'https://x.test/cb', 'state123', 'chall123', 'MLB');

        parse_str(parse_url($url, PHP_URL_QUERY), $query);

        $this->assertSame('cid', $query['client_id']);
        $this->assertSame('code', $query['response_type']);
        $this->assertSame('state123', $query['state']);
        $this->assertSame('chall123', $query['code_challenge']);
        $this->assertSame('S256', $query['code_challenge_method']);
        $this->assertSame('https://x.test/cb', $query['redirect_uri']);
    }
}
