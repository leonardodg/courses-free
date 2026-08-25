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

        $mform->addElement('static', 'oauthstatus', get_string('oauthstatus', 'paygw_mercadopago'),
            self::describe_oauth_status($form));

        $mform->addElement('advcheckbox', 'sandbox', get_string('sandbox', 'paygw_mercadopago'));
        $mform->setType('sandbox', PARAM_BOOL);
        $mform->addHelpButton('sandbox', 'sandbox', 'paygw_mercadopago');

        // Guardados pelo callback do OAuth, nunca digitados.
        foreach (['mpuserid', 'accesstoken', 'refreshtoken', 'tokenexpires'] as $field) {
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
        global $OUTPUT;

        $gateway = $form->get_gateway_persistent();
        $config = $gateway && $gateway->get('id') ? $gateway->get_configuration() : [];

        if (empty($config['accesstoken'])) {
            $url = new \moodle_url('/payment/gateway/mercadopago/oauth_start.php', [
                'accountid' => $gateway && $gateway->get('id') ? $gateway->get('accountid') : 0,
            ]);
            return $OUTPUT->render(new \single_button($url, get_string('linkaccount', 'paygw_mercadopago'), 'get'));
        }

        $expires = (int) ($config['tokenexpires'] ?? 0);
        if ($expires && $expires <= time()) {
            return get_string('oauthexpired', 'paygw_mercadopago');
        }

        return get_string('oauthlinked', 'paygw_mercadopago', [
            'mpuserid' => s($config['mpuserid'] ?? '?'),
            'expires' => $expires ? userdate($expires) : '-',
        ]);
    }
}
