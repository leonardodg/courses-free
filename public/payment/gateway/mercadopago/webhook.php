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

/**
 * Notificacao do Mercado Pago.
 *
 * Chamado pelo Mercado Pago, nao por um navegador: nao ha sessao, nao ha
 * usuario logado e nao ha sesskey. A autenticacao e feita de outro jeito -
 * consultando o pagamento de volta na API com o token do vendedor.
 *
 * @package    paygw_mercadopago
 * @copyright  2026 LeoDG <callme@leodg.dev>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('NO_MOODLE_COOKIES', true);
define('NO_DEBUG_DISPLAY', true);

require(__DIR__ . '/../../../config.php');

use paygw_mercadopago\mp_client;
use paygw_mercadopago\payment_processor;

// O Mercado Pago espera 200 rapido. Se demorar ou falhar, ele reenvia - e o
// reenvio e justamente por isso que a entrega precisa ser idempotente.
$raw = file_get_contents('php://input');
$payload = json_decode($raw, true);

$type = $payload['type'] ?? optional_param('type', '', PARAM_ALPHANUMEXT);
$paymentid = $payload['data']['id'] ?? optional_param('data_id', '', PARAM_ALPHANUMEXT);

if ($type !== 'payment' || empty($paymentid)) {
    // Notificacao de outro assunto (merchant_order, etc). Responder 200 evita
    // que o Mercado Pago fique reenviando algo que nunca vamos processar.
    header('HTTP/1.1 200 OK');
    echo 'ignored';
    exit;
}

try {
    // NUNCA confiar no status que vier no corpo. O Mercado Pago manda so o ID
    // de proposito: se aceitassemos "status: approved" do payload, qualquer um
    // conseguiria acesso gratis com um POST neste endpoint.
    $processed = payment_processor::process_notification((string) $paymentid);

    header('HTTP/1.1 200 OK');
    echo $processed ? 'processed' : 'ignored';
} catch (\Throwable $e) {
    // 500 faz o Mercado Pago reenviar, que e o desejado numa falha transitoria
    // (banco fora do ar, timeout). O erro fica no log do Moodle para
    // diagnostico; a mensagem nao volta para o Mercado Pago.
    debugging('paygw_mercadopago webhook: ' . $e->getMessage(), DEBUG_DEVELOPER);
    header('HTTP/1.1 500 Internal Server Error');
    echo 'error';
}
