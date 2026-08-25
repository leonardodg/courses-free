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

use curl;
use moodle_exception;

/**
 * Unico ponto de contato com a API do Mercado Pago.
 *
 * TODA chamada HTTP passa por aqui, de proposito. O Mercado Pago esta
 * migrando o Checkout Pro para a Orders API, e a API de Preferencias que
 * usamos pode ser depreciada. Concentrando as chamadas num arquivo, essa
 * migracao vira uma reescrita local em vez de uma cacada por todo o plugin.
 *
 * Nao usa o SDK oficial (mercadopago/dx-php) porque ele traria uma arvore de
 * dependencias via composer para tres endpoints. O curl do Moodle ja resolve,
 * respeita proxy e timeout do site, e nao acrescenta nada para manter.
 *
 * @package    paygw_mercadopago
 * @copyright  2026 Leonardo Della Giustina
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mp_client {

    /** @var string Base da API. */
    const API_BASE = 'https://api.mercadopago.com';

    /** @var string Onde o vendedor autoriza a plataforma. */
    const AUTH_URL = 'https://auth.mercadopago.com.br/authorization';

    /** @var int Timeout em segundos. Pagamento nao pode pendurar a requisicao. */
    const TIMEOUT = 20;

    /** @var string Token usado nas chamadas. */
    protected string $accesstoken;

    /**
     * @param string $accesstoken Token do VENDEDOR para criar preferencia; da
     *                            plataforma para o fluxo OAuth.
     */
    public function __construct(string $accesstoken) {
        $this->accesstoken = $accesstoken;
    }

    /**
     * Gera um code_verifier para o PKCE.
     *
     * O Mercado Pago exige entre 43 e 128 caracteres. random_string() do Moodle
     * produz apenas alfanumericos, que estao dentro do conjunto "unreserved" da
     * RFC 7636 - nao precisam de escape na URL nem no JSON, o que elimina uma
     * classe inteira de erro de codificacao.
     *
     * @return string
     */
    public static function create_code_verifier(): string {
        return random_string(64);
    }

    /**
     * Deriva o code_challenge do verifier, metodo S256.
     *
     * base64url e base64 comum com +/ trocados por -_ e sem o padding '=';
     * mandar base64 puro faz o Mercado Pago recusar a autorizacao.
     *
     * @param string $verifier
     * @return string
     */
    public static function create_code_challenge(string $verifier): string {
        return rtrim(strtr(base64_encode(hash('sha256', $verifier, true)), '+/', '-_'), '=');
    }

    /**
     * URL para o vendedor autorizar a plataforma.
     *
     * O PKCE e opcional no Mercado Pago, mas mandamos sempre: o servidor so
     * cobra o code_verifier na troca se a autorizacao trouxe o challenge, entao
     * enviar os dois mantem o fluxo coerente com a aplicacao configurada de
     * qualquer jeito - e protege o codigo de autorizacao caso ele vaze no
     * historico do navegador ou num log de proxy.
     *
     * @param string $clientid client_id da aplicacao da plataforma
     * @param string $redirecturi Precisa casar EXATAMENTE com a cadastrada no painel
     * @param string $state Devolvido no callback; usamos para saber qual conta vincular
     * @param string $codechallenge Derivado do verifier guardado na sessao
     * @return string
     */
    public static function build_authorization_url(
        string $clientid,
        string $redirecturi,
        string $state,
        string $codechallenge
    ): string {
        return self::AUTH_URL . '?' . http_build_query([
            'client_id' => $clientid,
            'response_type' => 'code',
            'platform_id' => 'mp',
            'redirect_uri' => $redirecturi,
            'state' => $state,
            'code_challenge' => $codechallenge,
            'code_challenge_method' => 'S256',
        ]);
    }

    /**
     * Troca o codigo de autorizacao pelo token do vendedor.
     *
     * @param string $clientid
     * @param string $clientsecret
     * @param string $code Codigo recebido no callback
     * @param string $redirecturi Precisa ser o MESMO usado na autorizacao
     * @param string $codeverifier O verifier cujo challenge foi enviado na autorizacao
     * @return array access_token, refresh_token, expires_in, user_id
     */
    public static function exchange_code(
        string $clientid,
        string $clientsecret,
        string $code,
        string $redirecturi,
        string $codeverifier
    ): array {
        return self::post_json(self::API_BASE . '/oauth/token', [
            'grant_type' => 'authorization_code',
            'client_id' => $clientid,
            'client_secret' => $clientsecret,
            'code' => $code,
            'redirect_uri' => $redirecturi,
            'code_verifier' => $codeverifier,
        ]);
    }

    /**
     * Renova o token de um vendedor.
     *
     * O token do Mercado Pago expira em cerca de seis meses. Sem renovar, o
     * repasse daquele vendedor para de funcionar - e o sintoma aparece no
     * checkout, diante do aluno.
     *
     * @param string $clientid
     * @param string $clientsecret
     * @param string $refreshtoken
     * @return array
     */
    public static function refresh_token(string $clientid, string $clientsecret, string $refreshtoken): array {
        return self::post_json(self::API_BASE . '/oauth/token', [
            'grant_type' => 'refresh_token',
            'client_id' => $clientid,
            'client_secret' => $clientsecret,
            'refresh_token' => $refreshtoken,
        ]);
    }

    /**
     * Moeda de cada pais onde o Mercado Pago opera.
     *
     * A conta do vendedor e presa a um pais, identificado pelo site_id, e so
     * recebe na moeda dele: uma conta MLB nao recebe ARS. Por isso a moeda da
     * empresa e DESCOBERTA da conta vinculada, nunca digitada - um campo livre
     * so produziria preferencia recusada no checkout, diante do aluno.
     *
     * @var array<string,string>
     */
    const SITE_CURRENCY = [
        'MLA' => 'ARS',  // Argentina.
        'MLB' => 'BRL',  // Brasil.
        'MLC' => 'CLP',  // Chile.
        'MCO' => 'COP',  // Colombia.
        'MLM' => 'MXN',  // Mexico.
        'MPE' => 'PEN',  // Peru.
        'MLU' => 'UYU',  // Uruguai.
    ];

    /**
     * Moeda correspondente a um site do Mercado Pago.
     *
     * @param string $siteid
     * @return string Vazio se o site for desconhecido.
     */
    public static function currency_for_site(string $siteid): string {
        return self::SITE_CURRENCY[strtoupper($siteid)] ?? '';
    }

    /**
     * Dados da conta dona do token.
     *
     * Usado logo apos o OAuth para saber em que pais o vendedor recebe.
     *
     * @return array Inclui id, site_id e country_id
     */
    public function get_me(): array {
        return $this->request('GET', '/users/me');
    }

    /**
     * Cria a preferencia de pagamento (Checkout Pro).
     *
     * O marketplace_fee e o que faz a comissao voltar para a plataforma. Vai
     * aqui, na preferencia, e nao no pagamento - e por isso que a integracao
     * declarada no painel do Mercado Pago precisa ser "API de Preferencias".
     *
     * ATENCAO: a ordem de deducao e fixa e nao configuravel. A taxa do proprio
     * Mercado Pago sai primeiro, e o marketplace_fee incide sobre o que sobra.
     * Um relatorio que calcule 25% do bruto vai divergir do extrato.
     *
     * @param array $preference Corpo da preferencia
     * @return array Resposta, com id e init_point
     */
    public function create_preference(array $preference): array {
        return $this->request('POST', '/checkout/preferences', $preference);
    }

    /**
     * Consulta um pagamento.
     *
     * O webhook do Mercado Pago manda so o ID: nunca o status. E de proposito -
     * confiar no corpo da notificacao permitiria a qualquer um POSTar
     * "aprovado" no nosso endpoint. O status tem que vir daqui.
     *
     * @param string $paymentid
     * @return array
     */
    public function get_payment(string $paymentid): array {
        return $this->request('GET', '/v1/payments/' . rawurlencode($paymentid));
    }

    /**
     * Chamada autenticada com o token da instancia.
     *
     * @param string $method
     * @param string $path
     * @param array|null $body
     * @return array
     */
    protected function request(string $method, string $path, ?array $body = null): array {
        global $CFG;
        require_once($CFG->libdir . '/filelib.php');

        $curl = new curl();
        $curl->setHeader([
            'Authorization: Bearer ' . $this->accesstoken,
            'Content-Type: application/json',
        ]);
        $options = [
            'CURLOPT_TIMEOUT' => self::TIMEOUT,
            'CURLOPT_CONNECTTIMEOUT' => 10,
            'CURLOPT_RETURNTRANSFER' => true,
        ];

        $url = self::API_BASE . $path;
        if ($method === 'GET') {
            $response = $curl->get($url, [], $options);
        } else {
            $response = $curl->post($url, json_encode($body), $options);
        }

        return self::decode($curl, $response, $url);
    }

    /**
     * POST sem autenticacao por Bearer, usado no fluxo OAuth.
     *
     * @param string $url
     * @param array $body
     * @return array
     */
    protected static function post_json(string $url, array $body): array {
        global $CFG;
        require_once($CFG->libdir . '/filelib.php');

        $curl = new curl();
        $curl->setHeader(['Content-Type: application/json']);
        $response = $curl->post($url, json_encode($body), [
            'CURLOPT_TIMEOUT' => self::TIMEOUT,
            'CURLOPT_CONNECTTIMEOUT' => 10,
            'CURLOPT_RETURNTRANSFER' => true,
        ]);

        return self::decode($curl, $response, $url);
    }

    /**
     * Interpreta a resposta, transformando erro de API em excecao.
     *
     * Erro silencioso aqui viraria "o aluno pagou e nao recebeu acesso", que e
     * o pior desfecho possivel - entao qualquer status fora de 2xx vira
     * excecao, com a mensagem do Mercado Pago preservada para o log.
     *
     * @param curl $curl
     * @param string|bool $response
     * @param string $url
     * @return array
     */
    protected static function decode(curl $curl, $response, string $url): array {
        $info = $curl->get_info();
        $status = (int) ($info['http_code'] ?? 0);

        if ($curl->get_errno()) {
            throw new moodle_exception('errorcurl', 'paygw_mercadopago', '', $curl->error);
        }

        $decoded = json_decode((string) $response, true);
        if (!is_array($decoded)) {
            throw new moodle_exception('errorinvalidresponse', 'paygw_mercadopago', '', $url);
        }

        if ($status < 200 || $status >= 300) {
            $message = $decoded['message'] ?? $decoded['error'] ?? 'HTTP ' . $status;
            throw new moodle_exception('errorapi', 'paygw_mercadopago', '', $status . ': ' . $message);
        }

        return $decoded;
    }
}
