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

namespace paygw_mercadopago\task;

use core\task\scheduled_task;
use paygw_mercadopago\mp_client;

/**
 * Renova os tokens dos vendedores antes de vencerem.
 *
 * O token do Mercado Pago vale cerca de seis meses. Sem renovacao o repasse
 * daquele vendedor simplesmente para - e o sintoma aparece no checkout, diante
 * do aluno, meses depois de alguem ter configurado tudo corretamente.
 *
 * @package    paygw_mercadopago
 * @copyright  2026 Leonardo Della Giustina
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class refresh_tokens extends scheduled_task {

    /** @var int Antecedencia da renovacao, em dias. */
    const RENEW_BEFORE_DAYS = 15;

    /**
     * Nome exibido no admin.
     *
     * @return string
     */
    public function get_name() {
        return get_string('taskrefreshtokens', 'paygw_mercadopago');
    }

    /**
     * Executa.
     *
     * @return void
     */
    public function execute() {
        global $DB;

        $config = get_config('paygw_mercadopago');
        if (empty($config->clientid) || empty($config->clientsecret)) {
            mtrace('Aplicacao da plataforma nao configurada; nada a renovar.');
            return;
        }

        $limite = time() + (self::RENEW_BEFORE_DAYS * DAYSECS);
        $renovados = $falhas = 0;

        foreach ($DB->get_records('payment_gateways', ['gateway' => 'mercadopago']) as $gw) {
            $gwconfig = @json_decode($gw->config, true);
            if (empty($gwconfig['refreshtoken'])) {
                continue;
            }

            $expira = (int) ($gwconfig['tokenexpires'] ?? 0);
            if ($expira === 0 || $expira > $limite) {
                continue;
            }

            try {
                $token = mp_client::refresh_token(
                    $config->clientid,
                    $config->clientsecret,
                    $gwconfig['refreshtoken']
                );

                $gwconfig['accesstoken'] = (string) ($token['access_token'] ?? '');
                // O Mercado Pago pode devolver um refresh_token novo. Manter o
                // antigo nesse caso quebraria a renovacao seguinte.
                if (!empty($token['refresh_token'])) {
                    $gwconfig['refreshtoken'] = (string) $token['refresh_token'];
                }
                $gwconfig['tokenexpires'] = time() + (int) ($token['expires_in'] ?? 0);

                $DB->set_field('payment_gateways', 'config', json_encode($gwconfig), ['id' => $gw->id]);
                $renovados++;
                mtrace("Conta {$gw->accountid}: token renovado ate " . userdate($gwconfig['tokenexpires']));
            } catch (\Throwable $e) {
                // Uma falha nao pode interromper as outras contas: se o
                // vendedor revogou a autorizacao no painel do Mercado Pago,
                // aquela conta nunca mais renova, e as demais seguem validas.
                $falhas++;
                mtrace("Conta {$gw->accountid}: FALHA ao renovar - " . $e->getMessage());
            }
        }

        mtrace("Renovados: $renovados. Falhas: $falhas.");

        if ($falhas > 0) {
            // Falha aqui e silenciosa por natureza: so apareceria numa venda.
            // Deixar registrado no log da task e o minimo; a Fase 4 acrescenta
            // aviso ao vendedor.
            mtrace('ATENCAO: contas com falha nao conseguirao receber pagamento.');
        }
    }
}
