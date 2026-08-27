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
 * Gateway do Mercado Pago, Checkout Pro.
 *
 * A configuracao e dividida em dois niveis, e a divisao e o que faz o split
 * funcionar:
 *
 *   SITE  - client_id e client_secret da aplicacao. Sao da PLATAFORMA, ficam
 *           em settings.php e valem para todos os vendedores.
 *   CONTA - access_token do vendedor, obtido por OAuth. Fica na config desta
 *           conta de pagamento, que pertence a empresa dele.
 *
 * A preferencia e criada com o token do VENDEDOR, mas pela aplicacao da
 * plataforma - e isso que autoriza o marketplace_fee a voltar para nos. Um
 * token colado a mao pelo vendedor, fora do fluxo OAuth, cria a preferencia
 * mas NAO habilita o split.
 *
 * @package    paygw_mercadopago
 * @copyright  2026 Leonardo Della Giustina
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class gateway extends \core_payment\gateway {
    /**
     * Moedas aceitas.
     *
     * Lista restrita aos paises onde o Mercado Pago opera. Fora deles a
     * preferencia e recusada pela API, entao oferecer a moeda so produziria
     * erro no checkout.
     *
     * @return string[]
     */
    public static function get_supported_currencies(): array {
        return ['BRL', 'ARS', 'MXN', 'CLP', 'COP', 'PEN', 'UYU'];
    }

    /**
     * Paises em que este gateway consegue receber, em ISO-3166 alpha-2.
     *
     * Nao faz parte do contrato do core_payment - ele so pergunta por moeda.
     * Existe porque a moeda nao identifica o pais em todos os casos, e porque o
     * marketplace precisa saber a que mercado uma conta pertence antes de
     * qualquer moeda estar definida. O nucleo chama via component_class_callback,
     * entao um gateway que nao declare isto simplesmente nao aparece na lista.
     *
     * @return string[]
     */
    public static function get_supported_countries(): array {
        return ['AR', 'BR', 'CL', 'CO', 'MX', 'PE', 'UY'];
    }

    /**
     * Campos da configuracao por conta de pagamento.
     *
     * Nao ha campo de token editavel: ele e escrito pelo fluxo OAuth. Deixar o
     * vendedor colar um token a mao pareceria mais simples e produziria um
     * split que nao funciona - falha que so apareceria na conciliacao, depois
     * do dinheiro ja ter caido errado.
     *
     * @param \core_payment\form\account_gateway $form
     * @return void
     */
    public static function add_configuration_to_gateway_form(\core_payment\form\account_gateway $form): void {
        $mform = $form->get_mform();

        $mform->addElement(
            'static',
            'oauthstatus',
            get_string('oauthstatus', 'paygw_mercadopago'),
            self::describe_oauth_status($form)
        );

        // NAO ha caixa de "modo de teste" aqui.
        //
        // Existia uma, por conta, com o MESMO rotulo da configuracao de site.
        // Duas caixas indistinguiveis governando a mesma coisa: desligar uma e
        // esquecer a outra mandava o aluno para o sandbox com um token de
        // producao, e a tela nao dava nenhuma pista de qual faltava.
        //
        // O ambiente e propriedade do SITE, nao da conta - comprador, vendedor
        // e aplicacao precisam estar todos do mesmo lado. Uma chave por conta
        // permitia justamente a mistura que o Mercado Pago recusa.

        // Guardados pelo callback do OAuth, nunca digitados. Precisam estar no
        // formulario mesmo escondidos: a configuracao salva e a que o
        // formulario devolve, entao um campo ausente aqui seria APAGADO na
        // primeira vez que o vendedor salvasse a tela.
        foreach (['mpuserid', 'accesstoken', 'refreshtoken', 'tokenexpires', 'siteid', 'currency'] as $field) {
            $mform->addElement('hidden', $field);
            $mform->setType($field, $field === 'tokenexpires' ? PARAM_INT : PARAM_RAW);
        }
    }

    /**
     * Valida a configuracao.
     *
     * @param \core_payment\form\account_gateway $form
     * @param \stdClass $data
     * @param array $files
     * @param array $errors
     * @return void
     */
    public static function validate_gateway_form(
        \core_payment\form\account_gateway $form,
        \stdClass $data,
        array $files,
        array &$errors
    ): void {
        // Habilitar a conta sem token concluido faria o aluno chegar ao
        // checkout e receber erro do Mercado Pago. Melhor recusar aqui.
        if ($data->enabled && empty($data->accesstoken)) {
            $errors['enabled'] = get_string('errornotlinked', 'paygw_mercadopago');
        }
    }

    /**
     * Texto do estado do vinculo, exibido no formulario.
     *
     * @param \core_payment\form\account_gateway $form
     * @return string
     */
    protected static function describe_oauth_status(\core_payment\form\account_gateway $form): string {
        $gateway = $form->get_gateway_persistent();
        $config = $gateway && $gateway->get('id') ? $gateway->get_configuration() : [];

        if (empty($config['accesstoken'])) {
            $accountid = $gateway ? (int) $gateway->get('accountid') : 0;
            if (!$accountid) {
                // A conta ainda nao existe: o gateway precisa ser salvo antes,
                // senao nao ha accountid para levar ao fluxo OAuth.
                return get_string('savebeforelinking', 'paygw_mercadopago');
            }

            $url = new \moodle_url('/payment/gateway/mercadopago/oauth_start.php', ['accountid' => $accountid]);

            // Link, NAO single_button. single_button renderiza um <form>, e
            // este texto vai DENTRO do formulario de configuracao do gateway.
            // Formulario aninhado e HTML invalido: o navegador descarta o
            // interno, e o clique submete o externo - que aponta para
            // manage_gateway.php sem accountid nem gateway, produzindo
            // "Gateway not found".
            return \html_writer::link($url, get_string('linkaccount', 'paygw_mercadopago'), [
                'class' => 'btn btn-secondary',
                'target' => '_self',
            ]);
        }

        $accountid = (int) $gateway->get('accountid');
        $expires = (int) ($config['tokenexpires'] ?? 0);

        // Trocar de conta nao exige desvincular - autorizar de novo sobrescreve
        // o token. Os dois botoes existem porque as intencoes sao diferentes:
        // um corrige a conta errada, o outro encerra a operacao.
        $actions = \html_writer::link(
            new \moodle_url('/payment/gateway/mercadopago/oauth_start.php', ['accountid' => $accountid]),
            get_string('relinkaccount', 'paygw_mercadopago'),
            ['class' => 'btn btn-secondary', 'target' => '_self']
        ) . ' ' . \html_writer::link(
            new \moodle_url('/payment/gateway/mercadopago/oauth_unlink.php', ['accountid' => $accountid]),
            get_string('unlinkaccount', 'paygw_mercadopago'),
            ['class' => 'btn btn-outline-danger', 'target' => '_self']
        );

        if ($expires && $expires <= time()) {
            return get_string('oauthexpired', 'paygw_mercadopago') . '<br>' . $actions;
        }

        $currency = (string) ($config['currency'] ?? '');

        return get_string('oauthlinked', 'paygw_mercadopago', [
            'mpuserid' => s($config['mpuserid'] ?? '?'),
            'expires' => $expires ? userdate($expires) : '-',
        ])
            . ($currency !== '' ? ' ' . get_string('oauthcurrency', 'paygw_mercadopago', s($currency)) : '')
            . '<br>' . $actions;
    }
}
