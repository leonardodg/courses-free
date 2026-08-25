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

use core_payment\helper;
use moodle_exception;

/**
 * Cria a cobranca e processa a confirmacao.
 *
 * @package    paygw_mercadopago
 * @copyright  2026 Leonardo Della Giustina
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class payment_processor {

    /** @var string Tabela do gateway. */
    const TABLE = 'paygw_mercadopago';

    /**
     * Cria a preferencia e devolve para onde mandar o aluno.
     *
     * @param string $component
     * @param string $paymentarea
     * @param int $itemid
     * @param int $userid
     * @return string init_point do Checkout Pro
     */
    public static function start_payment(string $component, string $paymentarea, int $itemid, int $userid): string {
        global $DB, $CFG;

        $payable = helper::get_payable($component, $paymentarea, $itemid);
        $accountid = $payable->get_account_id();
        $amount = (float) $payable->get_amount();
        $currency = $payable->get_currency();

        $config = self::get_gateway_config($accountid);
        $appconfig = get_config('paygw_mercadopago');

        // A referencia externa e a chave de ligacao: o ID do pagamento so
        // existe DEPOIS que o aluno paga, entao precisamos de algo nosso que
        // acompanhe a transacao desde a criacao da preferencia.
        $reference = 'mdl-' . $userid . '-' . $itemid . '-' . random_string(12);

        // A comissao e regra do marketplace, nao do gateway. Perguntamos a ele
        // quando ele esta presente, e caimos no padrao do site quando outro
        // componente usa este gateway - assim o plugin continua servindo a
        // qualquer componente do core_payment, sem depender do marketplace.
        $feepercent = (float) ($appconfig->defaultfeepercent ?? 25);
        if ($component === 'local_marketplace' && class_exists('\local_marketplace\api')) {
            $offer = \local_marketplace\offer::get_record(['id' => $itemid]);
            if ($offer) {
                $feepercent = \local_marketplace\api::resolve_commission_percent($offer);
            }
        }
        $fee = round($amount * ($feepercent / 100), 2);

        $record = (object) [
            'preferenceid' => '',
            'externalreference' => $reference,
            'component' => $component,
            'paymentarea' => $paymentarea,
            'itemid' => $itemid,
            'userid' => $userid,
            'accountid' => $accountid,
            'amount' => $amount,
            'currency' => $currency,
            'feeamount' => $fee,
            'status' => 'pending',
            'timecreated' => time(),
            'timemodified' => time(),
        ];
        $record->id = $DB->insert_record(self::TABLE, $record);

        $client = new mp_client($config['accesstoken']);

        $preferencebody = [
            'items' => [[
                'title' => helper::get_cost_as_string($amount, $currency),
                'quantity' => 1,
                'unit_price' => $amount,
                'currency_id' => $currency,
            ]],
            'external_reference' => $reference,
            // O marketplace_fee e a comissao da plataforma. So funciona porque
            // o token acima veio do fluxo OAuth da NOSSA aplicacao.
            'marketplace_fee' => $fee,
            'back_urls' => [
                'success' => $CFG->wwwroot . '/payment/gateway/mercadopago/return.php?ref=' . $reference,
                'pending' => $CFG->wwwroot . '/payment/gateway/mercadopago/return.php?ref=' . $reference,
                'failure' => $CFG->wwwroot . '/payment/gateway/mercadopago/return.php?ref=' . $reference,
            ],
            // O webhook e a fonte da verdade, nao a volta do navegador: o aluno
            // pode fechar a aba antes de voltar, e com Pix a aprovacao chega
            // depois do redirecionamento.
            'notification_url' => $CFG->wwwroot . '/payment/gateway/mercadopago/webhook.php',
            'auto_return' => 'approved',
        ];

        // Em teste, exige que o comprador entre na conta dele.
        //
        // Sem isto o Checkout Pro oferece pagar como visitante, e o pagador
        // fica sem identidade - o Mercado Pago recusa a compra com "uma das
        // partes e de teste", porque um visitante nao e usuario de teste. Nao
        // existe forma de o comprador se identificar como conta de teste sem
        // fazer login.
        //
        // Fica preso ao modo de teste de proposito. Em producao, wallet_purchase
        // elimina pagamento sem cadastro, boleto e dinheiro - ou seja, corta
        // conversao real para resolver um problema que so existe no sandbox.
        if (!empty($appconfig->testmode)) {
            $preferencebody['purpose'] = 'wallet_purchase';
        }

        $preference = $client->create_preference($preferencebody);

        $record->preferenceid = (string) ($preference['id'] ?? '');
        $record->timemodified = time();
        $DB->update_record(self::TABLE, $record);

        // O ambiente vem da configuracao do SITE, nao da conta. Havia uma
        // caixa por conta com o mesmo rotulo da de site, e desligar so uma
        // mandava o aluno para o sandbox segurando um token de producao - o
        // checkout abria em sandbox.mercadopago.com.br e o Pix nem aparecia,
        // sem nada na tela indicando o porque.
        $point = !empty($appconfig->testmode) && !empty($preference['sandbox_init_point'])
            ? $preference['sandbox_init_point']
            : ($preference['init_point'] ?? '');

        if (empty($point)) {
            throw new moodle_exception('errorinvalidresponse', 'paygw_mercadopago', '', 'preference');
        }

        return $point;
    }

    /**
     * Processa uma notificacao do Mercado Pago.
     *
     * @param string $mppaymentid
     * @return bool Verdadeiro se a entrega aconteceu agora.
     */
    public static function process_notification(string $mppaymentid): bool {
        global $DB;

        // O token para consultar e o do VENDEDOR, e nao sabemos qual e antes de
        // achar a transacao. Por isso a consulta e feita em duas etapas: acha o
        // registro pela referencia externa que volta no pagamento.
        //
        // Numa primeira notificacao o mppaymentid ainda nao esta gravado, entao
        // e preciso perguntar ao Mercado Pago com QUALQUER token valido de uma
        // conta que tenha gateway configurado. Usamos o da transacao assim que
        // ela e localizada; para localiza-la, consultamos com cada conta ativa
        // ate uma responder - sao poucas, e so na primeira notificacao.
        $existing = $DB->get_record(self::TABLE, ['mppaymentid' => $mppaymentid]);
        if ($existing) {
            $payment = (new mp_client(self::get_gateway_config((int) $existing->accountid)['accesstoken']))
                ->get_payment($mppaymentid);
            $record = $existing;
        } else {
            [$payment, $record] = self::locate_transaction($mppaymentid);
        }

        if (!$record) {
            // Pagamento que nao e nosso, ou transacao ja removida.
            return false;
        }

        $status = (string) ($payment['status'] ?? 'pending');

        if ($record->status === 'approved' && $status === 'approved') {
            // Reenvio do Mercado Pago de algo ja entregue.
            return false;
        }

        $record->mppaymentid = $mppaymentid;
        $record->status = $status;
        $record->timemodified = time();

        if ($status === 'approved' && empty($record->paymentid)) {
            $record->paymentid = helper::save_payment(
                (int) $record->accountid,
                $record->component,
                $record->paymentarea,
                (int) $record->itemid,
                (int) $record->userid,
                (float) $record->amount,
                $record->currency,
                'mercadopago'
            );
            $DB->update_record(self::TABLE, $record);

            helper::deliver_order(
                $record->component,
                $record->paymentarea,
                (int) $record->itemid,
                (int) $record->paymentid,
                (int) $record->userid
            );

            return true;
        }

        $DB->update_record(self::TABLE, $record);
        return false;
    }

    /**
     * Descobre a qual transacao um pagamento pertence.
     *
     * @param string $mppaymentid
     * @return array [dados do pagamento, registro da transacao|null]
     */
    protected static function locate_transaction(string $mppaymentid): array {
        global $DB;

        $gateways = $DB->get_records('payment_gateways', ['gateway' => 'mercadopago', 'enabled' => 1]);
        foreach ($gateways as $gw) {
            $config = @json_decode($gw->config, true);
            if (empty($config['accesstoken'])) {
                continue;
            }
            try {
                $payment = (new mp_client($config['accesstoken']))->get_payment($mppaymentid);
            } catch (\Throwable $e) {
                // Pagamento de outro vendedor: o token desta conta nao o
                // enxerga. Seguir para a proxima e o comportamento correto.
                continue;
            }
            $reference = (string) ($payment['external_reference'] ?? '');
            if ($reference === '') {
                continue;
            }
            $record = $DB->get_record(self::TABLE, ['externalreference' => $reference]);
            if ($record) {
                return [$payment, $record];
            }
        }

        return [[], null];
    }

    /**
     * Configuracao do gateway numa conta.
     *
     * @param int $accountid
     * @return array
     */
    protected static function get_gateway_config(int $accountid): array {
        $gateway = \core_payment\account_gateway::get_record([
            'accountid' => $accountid,
            'gateway' => 'mercadopago',
        ]);
        if (!$gateway) {
            throw new moodle_exception('errornotlinked', 'paygw_mercadopago');
        }
        $config = $gateway->get_configuration();
        if (empty($config['accesstoken'])) {
            throw new moodle_exception('errornotlinked', 'paygw_mercadopago');
        }
        return $config;
    }
}
