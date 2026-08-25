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
 * Strings do paygw_mercadopago.
 *
 * @package    paygw_mercadopago
 * @copyright  2026 Leonardo Della Giustina
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['appheading'] = 'Aplicação da plataforma';
$string['appheading_desc'] = 'Credenciais da aplicação Mercado Pago que pertence à plataforma, não ao vendedor. Cadastre esta URL de redirecionamento exata no painel do Mercado Pago: <code>{$a}</code>';
$string['clientid'] = 'Client ID';
$string['clientid_desc'] = 'Número da aplicação exibido no painel de desenvolvedor do Mercado Pago.';
$string['clientsecret'] = 'Client secret';
$string['clientsecret_desc'] = 'Usado apenas para trocar o código de autorização pelo token do vendedor. Nunca é enviado ao navegador.';
$string['defaultfeepercent'] = 'Comissão padrão (%)';
$string['defaultfeepercent_desc'] = 'Percentual que a plataforma retém em cada venda. O Mercado Pago desconta a taxa dele primeiro, e esta comissão incide sobre o restante.';
$string['errorapi'] = 'O Mercado Pago recusou a requisição. {$a}';
$string['errorcreatingpreference'] = 'Não foi possível iniciar o pagamento. Tente de novo em instantes.';
$string['errorcurl'] = 'Não foi possível alcançar o Mercado Pago: {$a}';
$string['errorinvalidresponse'] = 'O Mercado Pago devolveu uma resposta inesperada para {$a}';
$string['errormissingappconfig'] = 'A aplicação da plataforma não está configurada. Defina o client ID e o secret nas configurações do plugin.';
$string['errornotlinked'] = 'Vincule a conta Mercado Pago antes de habilitar este gateway.';
$string['errorsitemismatch'] = 'Este marketplace opera em {$a->platform} e a conta que você autorizou é de {$a->seller}. O Mercado Pago só divide pagamentos entre contas do mesmo país, então esta conta não pode ser vinculada. Use uma conta de {$a->platform}.';
$string['errorstatemismatch'] = 'Não foi possível verificar a autorização. Comece o processo de novo.';
$string['errorverifyaccount'] = 'A conta foi autorizada mas não pôde ser verificada com o Mercado Pago, então não foi vinculada. Tente de novo. ({$a})';
$string['gatewaydescription'] = 'Pague com Pix, cartão ou boleto pelo Checkout Pro do Mercado Pago. O valor é dividido automaticamente entre o vendedor e a plataforma.';
$string['gatewayname'] = 'Mercado Pago';
$string['linkaccount'] = 'Vincular conta Mercado Pago';
$string['oauthcurrency'] = 'Recebimento em {$a}.';
$string['oauthexpired'] = 'A autorização venceu. Vincule a conta de novo.';
$string['oauthlinked'] = 'Vinculado ao usuário {$a->mpuserid} do Mercado Pago. Autorização válida até {$a->expires}.';
$string['oauthstatus'] = 'Conta Mercado Pago';
$string['paymentapproved'] = 'Pagamento aprovado. Bom curso!';
$string['paymentpending'] = 'Estamos aguardando o Mercado Pago confirmar seu pagamento. Com Pix isso costuma levar alguns segundos. O acesso é liberado automaticamente assim que compensar — você não precisa pagar de novo.';
$string['paymentrejected'] = 'O pagamento não foi concluído. Nada foi cobrado.';
$string['platformsite'] = 'País do marketplace';
$string['platformsite_desc'] = 'País da conta Mercado Pago que recebe a comissão. Define onde os vendedores autorizam e quais contas podem ser vinculadas: o split só funciona entre contas do mesmo país, porque a comissão cai na conta da plataforma e uma conta só guarda a moeda do próprio país. Vendedores de outros países exigem um marketplace separado, com aplicação própria.';
$string['pluginname'] = 'Mercado Pago';
$string['privacy:metadata'] = 'O plugin Mercado Pago guarda o token de autorização do vendedor na conta de pagamento e envia o valor do pagamento e o e-mail do comprador ao Mercado Pago.';
$string['privacy:metadata:mercadopago'] = 'Dados enviados ao Mercado Pago para que o pagamento possa ser feito. O Mercado Pago é o controlador do que recebe.';
$string['privacy:metadata:mercadopago:amount'] = 'O valor a cobrar.';
$string['privacy:metadata:mercadopago:currency'] = 'A moeda da cobrança.';
$string['privacy:metadata:mercadopago:itemname'] = 'Uma descrição do que está sendo comprado.';
$string['privacy:metadata:paygw_mercadopago'] = 'Transações de pagamento tratadas por este gateway.';
$string['privacy:metadata:paygw_mercadopago:amount'] = 'O valor cobrado.';
$string['privacy:metadata:paygw_mercadopago:currency'] = 'A moeda cobrada.';
$string['privacy:metadata:paygw_mercadopago:mppaymentid'] = 'O identificador do pagamento no Mercado Pago.';
$string['privacy:metadata:paygw_mercadopago:status'] = 'Se o pagamento foi aprovado, recusado ou está pendente.';
$string['privacy:metadata:paygw_mercadopago:timecreated'] = 'Quando o pagamento foi iniciado.';
$string['privacy:metadata:paygw_mercadopago:userid'] = 'A pessoa que pagou.';
$string['relinkaccount'] = 'Vincular outra conta';
$string['savebeforelinking'] = 'Salve este gateway primeiro, depois volte para vincular a conta Mercado Pago.';
$string['taskrefreshtokens'] = 'Renovar tokens dos vendedores no Mercado Pago';
$string['testmode'] = 'Modo de teste';
$string['testmode_desc'] = 'Emite tokens de teste quando os vendedores vinculam a conta, para que todo o fluxo rode no sandbox do Mercado Pago. Comprador, vendedor e a aplicação da plataforma precisam estar todos do mesmo lado: uma aplicação real com um vendedor de teste é recusada com "uma das partes é de teste". Alterar isto não converte vínculos existentes — os vendedores precisam vincular de novo. Nunca deixe ligado em produção: os pagamentos reais parariam de funcionar.';
$string['unlinkaccount'] = 'Desvincular conta';
$string['unlinkconfirm'] = 'Desvincular o usuário {$a} do Mercado Pago desta conta de pagamento? O gateway será desabilitado e esta empresa deixará de vender até que uma conta seja vinculada de novo. Cursos já comprados mantêm o acesso. Isto não revoga a autorização dentro do Mercado Pago — o vendedor pode removê-la nas configurações da conta dele.';
$string['unlinkdone'] = 'Conta desvinculada. O gateway foi desabilitado.';
