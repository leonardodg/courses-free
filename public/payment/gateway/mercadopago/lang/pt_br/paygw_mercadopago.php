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
 * Strings for paygw_mercadopago.
 *
 * @package    paygw_mercadopago
 * @copyright  2026 Leonardo Della Giustina
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['pluginname'] = 'Mercado Pago';
$string['gatewayname'] = 'Mercado Pago';
$string['gatewaydescription'] = 'Pague com Pix, cartão ou boleto pelo Checkout Pro do Mercado Pago. O valor é dividido automaticamente entre o vendedor e a plataforma.';

// Site settings.
$string['appheading'] = 'Aplicação da plataforma';
$string['appheading_desc'] = 'Credenciais da aplicação do Mercado Pago que pertence à plataforma, não ao vendedor. Cadastre exatamente esta URL de redirecionamento no painel do Mercado Pago: <code>{$a}</code>';
$string['clientid'] = 'Client ID';
$string['clientid_desc'] = 'Número da aplicação, mostrado no painel de desenvolvedor do Mercado Pago.';
$string['clientsecret'] = 'Client secret';
$string['clientsecret_desc'] = 'Usado apenas para trocar o código de autorização pelo token do vendedor. Nunca é enviado ao navegador.';
$string['defaultfeepercent'] = 'Comissão padrão (%)';
$string['defaultfeepercent_desc'] = 'Percentual que a plataforma retém em cada venda. O Mercado Pago desconta a taxa dele primeiro, e esta comissão incide sobre o restante.';

// Account settings.
$string['oauthstatus'] = 'Conta Mercado Pago';
$string['linkaccount'] = 'Vincular conta Mercado Pago';
$string['oauthlinked'] = 'Vinculada ao usuário {$a->mpuserid} do Mercado Pago. Autorização válida até {$a->expires}.';
$string['oauthexpired'] = 'A autorização expirou. Vincule a conta novamente.';
$string['sandbox'] = 'Modo de teste';
$string['sandbox_help'] = 'Usa credenciais e usuários de teste. Nenhum dinheiro real é movimentado.';

// Errors.
$string['errornotlinked'] = 'Vincule a conta Mercado Pago antes de habilitar este gateway.';
$string['errorcurl'] = 'Não foi possível falar com o Mercado Pago: {$a}';
$string['errorinvalidresponse'] = 'O Mercado Pago devolveu uma resposta inesperada para {$a}';
$string['errorapi'] = 'O Mercado Pago recusou a requisição. {$a}';
$string['errormissingappconfig'] = 'A aplicação da plataforma não está configurada. Preencha o client ID e o secret nas configurações do plugin.';
$string['errorstatemismatch'] = 'Não foi possível verificar a autorização. Recomece o processo.';

// Task.
$string['taskrefreshtokens'] = 'Renovar tokens dos vendedores no Mercado Pago';

// Checkout.
$string['errorcreatingpreference'] = 'Não foi possível iniciar o pagamento. Tente de novo em instantes.';
$string['paymentapproved'] = 'Pagamento aprovado. Bom curso!';
$string['paymentpending'] = 'Estamos aguardando a confirmação do Mercado Pago. Com Pix isso costuma levar alguns segundos. O acesso é liberado automaticamente assim que cair — não é preciso pagar de novo.';
$string['paymentrejected'] = 'O pagamento não foi concluído. Nada foi cobrado.';

$string['privacy:metadata'] = 'O plugin Mercado Pago guarda o token de autorização do vendedor na conta de pagamento e envia ao Mercado Pago o valor do pagamento e o e-mail do comprador.';
