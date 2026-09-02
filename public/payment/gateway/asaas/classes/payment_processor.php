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

use core_payment\helper;
use moodle_exception;
use moodle_url;

/**
 * Cria a cobranca e processa a confirmacao.
 *
 * A direcao do dinheiro e o ponto inteiro desta classe: a cobranca nasce com a
 * chave do VENDEDOR, entao o liquido fica com ele, ele aparece como recebedor
 * no proprio payload do Pix e e ele quem emite a nota. O split leva so a
 * comissao para a carteira da plataforma.
 *
 * @package    paygw_asaas
 * @copyright  2026 LeoDG <callme@leodg.dev>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class payment_processor {
    /** @var string Tabela de transacoes do plugin. */
    const TABLE = 'paygw_asaas';

    /**
     * Cria a cobranca e devolve para onde mandar o aluno.
     *
     * @param string $component
     * @param string $paymentarea
     * @param int $itemid
     * @param int $userid
     * @return string URL da fatura hospedada.
     */
    public static function start_payment(string $component, string $paymentarea, int $itemid, int $userid): string {
        global $DB;

        $payable = helper::get_payable($component, $paymentarea, $itemid);
        $accountid = (int) $payable->get_account_id();
        $amount = (float) $payable->get_amount();
        $currency = $payable->get_currency();

        $environment = credentials::current_environment();
        $apikey = credentials::api_key($accountid, $environment);
        if ($apikey === '') {
            throw new moodle_exception('errornotlinked', 'paygw_asaas', '', gateway::environment_label($environment));
        }

        // A comissao e regra do marketplace, nao do gateway. O guarda de
        // componente vive la dentro, para nao existir em copia em cada gateway.
        //
        // Vem taxa E base: o Asaas consegue aplicar as duas bases, entao aqui a
        // base configurada e sempre a aplicada - o que nem sempre vale para
        // outro gateway.
        $feepercent = 25.0;
        $feebase = 'gross';
        $feesource = 'site';
        if (class_exists('\local_marketplace\api')) {
            $terms = \local_marketplace\api::commission_terms_for($component, $itemid);
            $feepercent = $terms->percent;
            $feebase = $terms->base;
            $feesource = $terms->source;
        }

        $reference = 'mdl-' . $userid . '-' . $itemid . '-' . random_string(12);

        // A linha nasce ANTES da chamada. Se a API responder e nos perdermos a
        // resposta, existe rastro para conciliar; o contrario seria uma cobranca
        // criada no Asaas que o Moodle nunca soube que existiu.
        $record = (object) [
            'asaaspaymentid' => '',
            'externalreference' => $reference,
            'customerid' => '',
            'component' => $component,
            'paymentarea' => $paymentarea,
            'itemid' => $itemid,
            'userid' => $userid,
            'accountid' => $accountid,
            'amount' => $amount,
            'currency' => $currency,
            'feeamount' => 0,
            'feepercent' => $feepercent,
            'feebase' => $feebase,
            'feesource' => $feesource,
            'billingtype' => self::billing_type(),
            'environment' => $environment,
            'status' => 'PENDING',
            'timecreated' => time(),
            'timemodified' => time(),
        ];
        $record->id = $DB->insert_record(self::TABLE, $record);

        $client = new asaas_client($apikey, $environment);

        $buyer = \core_user::get_user($userid, '*', MUST_EXIST);
        $document = self::buyer_document($buyer);

        // O Asaas RECUSA a cobranca de um cliente sem CPF/CNPJ - "Para criar
        // esta cobranca e necessario preencher o CPF ou CNPJ do cliente". Ele
        // aceita criar o cliente sem documento, o que torna o erro tardio e
        // confuso: a compra so quebra na segunda chamada.
        //
        // Falhamos aqui, com mensagem propria, para o administrador saber o que
        // configurar em vez de ler um erro cru da API no meio do checkout.
        if ($document === '') {
            throw new moodle_exception('errornodocument', 'paygw_asaas');
        }

        $customerid = $client->find_or_create_customer(
            fullname($buyer),
            $buyer->email,
            $document
        );

        $response = $client->create_payment([
            'customer' => $customerid,
            'billingtype' => self::billing_type(),
            'value' => $amount,
            'duedate' => date('Y-m-d', time() + (self::due_days() * DAYSECS)),
            'description' => self::describe_item($component, $itemid),
            'externalreference' => $reference,
            'returnurl' => self::use_callback()
                ? (new moodle_url('/payment/gateway/asaas/return.php', ['ref' => $reference]))->out(false)
                : '',
            'splitwalletid' => credentials::platform_wallet($environment),
            'splitpercent' => $feepercent,
            'splitbase' => $feebase,
        ]);

        $invoiceurl = (string) ($response['invoiceUrl'] ?? '');
        if ($invoiceurl === '') {
            throw new moodle_exception('errorinvalidresponse', 'paygw_asaas');
        }

        $record->asaaspaymentid = (string) ($response['id'] ?? '');
        $record->customerid = $customerid;
        $record->status = (string) ($response['status'] ?? 'PENDING');
        // Guardamos o que o gateway devolveu, e nao o que calculamos: na criacao
        // os dois batem, mas estorno parcial e split recusado mudam o numero
        // depois, e o relatorio tem que seguir o extrato.
        $record->feeamount = self::fee_from($response, $amount, $feepercent, '', $feebase);
        $record->timemodified = time();
        $DB->update_record(self::TABLE, $record);

        return $invoiceurl;
    }

    /**
     * Processa uma notificacao do Asaas.
     *
     * O payload NUNCA e a fonte da verdade: mesmo com o header validado, o
     * status vem de uma consulta a API com a chave do vendedor. Um webhook e
     * uma dica de que algo mudou, nao a prova do que mudou.
     *
     * @param string $asaaspaymentid
     * @return bool Verdadeiro quando a entrega aconteceu agora.
     */
    public static function process_notification(string $asaaspaymentid): bool {
        global $DB;

        $record = $DB->get_record(self::TABLE, ['asaaspaymentid' => $asaaspaymentid]);
        if (!$record) {
            return false;
        }

        $apikey = credentials::api_key((int) $record->accountid, $record->environment);
        if ($apikey === '') {
            throw new moodle_exception('errornotlinked', 'paygw_asaas', '', $record->environment);
        }

        $client = new asaas_client($apikey, $record->environment);
        $payment = $client->get_payment($asaaspaymentid);
        $status = (string) ($payment['status'] ?? 'PENDING');

        if (self::is_paid($record->status) && self::is_paid($status)) {
            // Reenvio de algo ja entregue.
            return false;
        }

        $record->status = $status;
        $record->timemodified = time();

        if (!self::is_paid($status) || !empty($record->paymentid)) {
            $DB->update_record(self::TABLE, $record);
            return false;
        }

        // Aqui o split ja existe na resposta, entao le-se o valor real em vez
        // de recalcular o percentual - inclusive o zero de um split cancelado.
        $record->feeamount = self::fee_from(
            $payment,
            (float) $record->amount,
            (float) $record->feepercent,
            credentials::platform_wallet($record->environment)
        );

        // Curso entregue sem comissao nenhuma nao e erro tecnico, e um fato do
        // negocio que alguem precisa ver. Acontece quando o vendedor da baixa
        // manual na cobranca: o aluno pagou por fora, o Asaas cancela o split,
        // e a plataforma nao recebe.
        if ((float) $record->feeamount <= 0 && (float) $record->feepercent > 0) {
            debugging(
                'paygw_asaas: cobranca ' . $asaaspaymentid . ' entregue com comissao zero - '
                    . 'split cancelado ou ausente. Status: ' . $status,
                DEBUG_NORMAL
            );
        }
        $record->paymentid = helper::save_payment(
            (int) $record->accountid,
            $record->component,
            $record->paymentarea,
            (int) $record->itemid,
            (int) $record->userid,
            (float) $record->amount,
            $record->currency,
            'asaas'
        );
        $DB->update_record(self::TABLE, $record);

        if (class_exists('\local_marketplace\api')) {
            \local_marketplace\api::record_sale(
                $record->component,
                (int) $record->paymentid,
                (int) $record->itemid,
                (float) $record->feeamount,
                $asaaspaymentid,
                // Os termos vem da LINHA, e nao de uma nova resolucao: entre a
                // criacao da cobranca e o webhook a configuracao pode ter
                // mudado, e a venda tem que registrar o que foi cobrado.
                class_exists('\local_marketplace\commission')
                    ? new \local_marketplace\commission(
                        (float) $record->feepercent,
                        (string) $record->feebase,
                        (string) $record->feesource
                    )
                    : null
            );
        }

        helper::deliver_order(
            $record->component,
            $record->paymentarea,
            (int) $record->itemid,
            (int) $record->paymentid,
            (int) $record->userid
        );

        return true;
    }

    /**
     * Este EVENTO de webhook merece uma consulta a API?
     *
     * Cuidado com a diferenca, que custou uma volta: o nome do EVENTO nao e o
     * mesmo do STATUS da cobranca. Existe o status RECEIVED_IN_CASH, mas nao
     * existe evento PAYMENT_RECEIVED_IN_CASH - o Asaas responde "O evento
     * [PAYMENT_RECEIVED_IN_CASH] e invalido" a quem tentar cadastrar. Receber
     * em dinheiro chega como PAYMENT_RECEIVED, com o status na cobranca.
     *
     * Por isso a lista aqui e curta e a de is_paid() e maior: uma fala de
     * eventos, a outra de status.
     *
     * Estorno e desfazimento (PAYMENT_REFUNDED, PAYMENT_RECEIVED_IN_CASH_UNDONE)
     * ficam de fora de proposito: revogar acesso e decisao de negocio deste
     * projeto, tomada em entitlement::revoke(), e nunca por automacao.
     *
     * @param string $event
     * @return bool
     */
    public static function is_relevant_event(string $event): bool {
        return in_array(strtoupper($event), ['PAYMENT_RECEIVED', 'PAYMENT_CONFIRMED'], true);
    }

    /**
     * O STATUS do Asaas significa "dinheiro entrou"?
     *
     * RECEIVED e o valor creditado; CONFIRMED e o cartao autorizado, com o
     * credito ainda por cair; RECEIVED_IN_CASH e a baixa manual, que e como o
     * sandbox confirma cobranca. Os tres liberam o acesso: segurar o curso ate
     * a liquidacao puniria o aluno por um prazo bancario.
     *
     * @param string $status
     * @return bool
     */
    public static function is_paid(string $status): bool {
        return in_array(strtoupper($status), ['RECEIVED', 'CONFIRMED', 'RECEIVED_IN_CASH'], true);
    }

    /**
     * Comissao que a plataforma vai DE FATO receber.
     *
     * Le o split da propria resposta quando ele existe, e nao um percentual
     * calculado por nos. A diferenca nao e cosmetica: se o vendedor der baixa
     * manual na cobranca (receiveInCash), o Asaas marca o split como CANCELLED -
     * dinheiro que nao passou por ele nao tem como ser dividido - e a plataforma
     * recebe ZERO. Calculando o percentual, o relatorio anunciaria uma comissao
     * que nunca vai chegar, e relatorio financeiro que discorda do extrato e
     * pior que nenhum.
     *
     * Sem split na resposta - na criacao da cobranca, quando ele ainda nao
     * existe - cai no percentual sobre o valor BRUTO, que e a base combinada
     * com o parceiro e a mesma que o build_split usa para montar o fixedValue.
     *
     * @param array $payment Resposta da API.
     * @param float $amount
     * @param float $feepercent
     * @param string $walletid Carteira da plataforma. Vazio ignora o split.
     * @return float
     */
    public static function fee_from(
        array $payment,
        float $amount,
        float $feepercent,
        string $walletid = '',
        string $base = 'gross'
    ): float {
        $splits = $payment['split'] ?? [];

        if ($walletid !== '' && is_array($splits) && $splits) {
            $total = 0.0;
            foreach ($splits as $split) {
                if ((string) ($split['walletId'] ?? '') !== $walletid) {
                    continue;
                }
                // CANCELLED e REFUSED nao transferem nada. Somar o valor deles
                // faria o relatorio prometer dinheiro que nao vem.
                if (in_array(strtoupper((string) ($split['status'] ?? '')), ['CANCELLED', 'REFUSED'], true)) {
                    continue;
                }
                $total += (float) ($split['totalValue'] ?? $split['fixedValue'] ?? 0);
            }

            return round($total, 2);
        }

        // A estimativa tem que usar a MESMA base do split que foi enviado, senao
        // a tela mostra uma comissao e o gateway recebe outra.
        if ($base === 'net') {
            $net = (float) ($payment['netValue'] ?? 0);

            // Sem netValue na resposta - acontece na criacao da cobranca - o
            // valor cheio e a unica base disponivel. Superestima a comissao, e
            // e o erro menos ruim: o numero certo chega pelo webhook, com o
            // split real, e ate la e melhor prometer menos ao vendedor do que
            // mais.
            return round(($net > 0 ? $net : $amount) * ($feepercent / 100), 2);
        }

        return round($amount * ($feepercent / 100), 2);
    }

    /**
     * Forma de cobranca configurada no site.
     *
     * UNDEFINED abre a fatura com todas as formas e deixa o aluno escolher.
     *
     * @return string
     */
    public static function billing_type(): string {
        $configured = strtoupper((string) get_config('paygw_asaas', 'billingtype'));
        $allowed = ['UNDEFINED', 'PIX', 'BOLETO', 'CREDIT_CARD'];

        return in_array($configured, $allowed, true) ? $configured : 'UNDEFINED';
    }

    /**
     * Mandar o aluno de volta ao Moodle depois de pagar?
     *
     * Existe como interruptor porque o Asaas so aceita URL de retorno se a
     * conta do vendedor tiver um SITE cadastrado - sem isso ele recusa a
     * cobranca inteira com "Nao ha nenhum dominio configurado em sua conta".
     * Um vendedor que nao consiga cadastrar o dominio continua vendendo; o
     * aluno e que volta pela fatura em vez de voltar sozinho.
     *
     * O vinculo ja barra esse caso, entao aqui e a segunda linha de defesa.
     *
     * @return bool
     */
    public static function use_callback(): bool {
        $configured = get_config('paygw_asaas', 'usecallback');

        return $configured === false || (bool) $configured;
    }

    /**
     * Dias ate o vencimento da cobranca.
     *
     * @return int
     */
    public static function due_days(): int {
        $configured = (int) get_config('paygw_asaas', 'duedays');

        return $configured > 0 ? $configured : 3;
    }

    /**
     * CPF/CNPJ do comprador.
     *
     * OBRIGATORIO, e nao por escolha nossa: o Asaas emite cliente sem
     * documento, mas recusa a COBRANCA desse cliente. Como o Moodle nao pede
     * documento no cadastro, o site precisa ter um campo de perfil para isso e
     * apontar aqui - e o formulario de inscricao deve exigi-lo, senao a falha
     * aparece so na hora de pagar.
     *
     * @param \stdClass $user
     * @return string Somente digitos, ou vazio quando nao ha.
     */
    protected static function buyer_document(\stdClass $user): string {
        global $CFG;

        $field = trim((string) get_config('paygw_asaas', 'documentfield'));
        if ($field === '') {
            return '';
        }

        require_once($CFG->dirroot . '/user/profile/lib.php');
        $profile = profile_user_record($user->id);
        $value = (string) ($profile->{$field} ?? '');

        return preg_replace('/\D/', '', $value) ?? '';
    }

    /**
     * Descricao que o aluno ve na fatura.
     *
     * @param string $component
     * @param int $itemid
     * @return string
     */
    protected static function describe_item(string $component, int $itemid): string {
        if ($component === 'local_marketplace' && class_exists('\local_marketplace\offer')) {
            $offer = \local_marketplace\offer::get_record(['id' => $itemid]);
            if ($offer) {
                return (string) $offer->get('name');
            }
        }

        return get_string('defaultdescription', 'paygw_asaas');
    }

    /**
     * URL do webhook deste site.
     *
     * @return moodle_url
     */
    public static function webhook_url(): moodle_url {
        return new moodle_url('/payment/gateway/asaas/webhook.php');
    }
}
