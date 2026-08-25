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
 * Cadenas de paygw_mercadopago.
 *
 * @package    paygw_mercadopago
 * @copyright  2026 Leonardo Della Giustina
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['appheading'] = 'Aplicación de la plataforma';
$string['appheading_desc'] = 'Credenciales de la aplicación de Mercado Pago que pertenece a la plataforma, no al vendedor. Registrá esta URL de redirección exacta en el panel de Mercado Pago: <code>{$a}</code>';
$string['clientid'] = 'Client ID';
$string['clientid_desc'] = 'Número de la aplicación que aparece en el panel de desarrolladores de Mercado Pago.';
$string['clientsecret'] = 'Client secret';
$string['clientsecret_desc'] = 'Se usa solo para intercambiar el código de autorización por el token del vendedor. Nunca se envía al navegador.';
$string['defaultfeepercent'] = 'Comisión por defecto (%)';
$string['defaultfeepercent_desc'] = 'Porcentaje que la plataforma retiene en cada venta. Mercado Pago descuenta su propia comisión primero, y esta comisión se aplica sobre el resto.';
$string['errorapi'] = 'Mercado Pago rechazó la solicitud. {$a}';
$string['errorcreatingpreference'] = 'No se pudo iniciar el pago. Probá de nuevo en unos instantes.';
$string['errorcurl'] = 'No se pudo llegar a Mercado Pago: {$a}';
$string['errorinvalidresponse'] = 'Mercado Pago devolvió una respuesta inesperada para {$a}';
$string['errormissingappconfig'] = 'La aplicación de la plataforma no está configurada. Definí el client ID y el secret en la configuración del plugin.';
$string['errornotlinked'] = 'Vinculá la cuenta de Mercado Pago antes de habilitar esta pasarela.';
$string['errorsitemismatch'] = 'Este marketplace opera en {$a->platform} y la cuenta que autorizaste es de {$a->seller}. Mercado Pago solo divide pagos entre cuentas del mismo país, así que esta cuenta no se puede vincular. Usá una cuenta de {$a->platform}.';
$string['errorstatemismatch'] = 'No se pudo verificar la autorización. Empezá el proceso de nuevo.';
$string['errorverifyaccount'] = 'La cuenta fue autorizada pero no se pudo verificar con Mercado Pago, así que no se vinculó. Probá de nuevo. ({$a})';
$string['gatewaydescription'] = 'Pagá con Pix, tarjeta o efectivo mediante Checkout Pro de Mercado Pago. El monto se divide automáticamente entre el vendedor y la plataforma.';
$string['gatewayname'] = 'Mercado Pago';
$string['linkaccount'] = 'Vincular cuenta de Mercado Pago';
$string['oauthcurrency'] = 'Cobros en {$a}.';
$string['oauthexpired'] = 'La autorización venció. Vinculá la cuenta de nuevo.';
$string['oauthlinked'] = 'Vinculado al usuario {$a->mpuserid} de Mercado Pago. Autorización válida hasta {$a->expires}.';
$string['oauthstatus'] = 'Cuenta de Mercado Pago';
$string['paymentapproved'] = 'Pago aprobado. ¡Disfrutá el curso!';
$string['paymentpending'] = 'Estamos esperando que Mercado Pago confirme tu pago. Con Pix suele tardar unos segundos. El acceso se habilita automáticamente en cuanto se acredite — no hace falta que pagues de nuevo.';
$string['paymentrejected'] = 'El pago no se completó. No se cobró nada.';
$string['platformsite'] = 'País del marketplace';
$string['platformsite_desc'] = 'País de la cuenta de Mercado Pago que recibe la comisión. Define dónde autorizan los vendedores y qué cuentas se pueden vincular: el split solo funciona entre cuentas del mismo país, porque la comisión cae en la cuenta de la plataforma y una cuenta solo guarda la moneda de su propio país. Los vendedores de otros países necesitan un marketplace aparte, con su propia aplicación.';
$string['pluginname'] = 'Mercado Pago';
$string['privacy:metadata'] = 'El plugin Mercado Pago guarda el token de autorización del vendedor en la cuenta de pago y envía el monto del pago y el correo del comprador a Mercado Pago.';
$string['privacy:metadata:mercadopago'] = 'Datos enviados a Mercado Pago para poder cobrar el pago. Mercado Pago es el responsable de lo que recibe.';
$string['privacy:metadata:mercadopago:amount'] = 'El monto a cobrar.';
$string['privacy:metadata:mercadopago:currency'] = 'La moneda del cobro.';
$string['privacy:metadata:mercadopago:itemname'] = 'Una descripción de lo que se está comprando.';
$string['privacy:metadata:paygw_mercadopago'] = 'Transacciones de pago gestionadas por esta pasarela.';
$string['privacy:metadata:paygw_mercadopago:amount'] = 'El monto cobrado.';
$string['privacy:metadata:paygw_mercadopago:currency'] = 'La moneda cobrada.';
$string['privacy:metadata:paygw_mercadopago:mppaymentid'] = 'El identificador del pago en Mercado Pago.';
$string['privacy:metadata:paygw_mercadopago:status'] = 'Si el pago fue aprobado, rechazado o está pendiente.';
$string['privacy:metadata:paygw_mercadopago:timecreated'] = 'Cuándo se inició el pago.';
$string['privacy:metadata:paygw_mercadopago:userid'] = 'La persona que pagó.';
$string['relinkaccount'] = 'Vincular otra cuenta';
$string['savebeforelinking'] = 'Guardá esta pasarela primero, después volvé para vincular la cuenta de Mercado Pago.';
$string['taskrefreshtokens'] = 'Renovar tokens de los vendedores en Mercado Pago';
$string['testmode'] = 'Modo de prueba';
$string['testmode_desc'] = 'Emite tokens de prueba cuando los vendedores vinculan su cuenta, para que todo el flujo corra en el sandbox de Mercado Pago. Comprador, vendedor y la aplicación de la plataforma tienen que estar todos del mismo lado: una aplicación real con un vendedor de prueba se rechaza con "una de las partes es de prueba". Cambiar esto no convierte los vínculos existentes — los vendedores tienen que vincular de nuevo. Nunca lo dejes activado en producción: los pagos reales dejarían de funcionar.';
$string['unlinkaccount'] = 'Desvincular cuenta';
$string['unlinkconfirm'] = '¿Desvincular al usuario {$a} de Mercado Pago de esta cuenta de pago? La pasarela se deshabilitará y esta empresa dejará de vender hasta que se vincule una cuenta de nuevo. Los cursos ya comprados conservan su acceso. Esto no revoca la autorización dentro de Mercado Pago — el vendedor puede quitarla desde la configuración de su cuenta.';
$string['unlinkdone'] = 'Cuenta desvinculada. La pasarela quedó deshabilitada.';
