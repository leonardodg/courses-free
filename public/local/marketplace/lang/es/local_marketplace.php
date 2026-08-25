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
 * Cadenas de local_marketplace.
 *
 * @package    local_marketplace
 * @copyright  2026 Leonardo Della Giustina
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['accessdays'] = 'Acceso por {$a} días';
$string['accessgranted'] = 'Acceso habilitado. ¡Disfrutá el curso!';
$string['accesslifetime'] = 'Acceso de por vida';
$string['accessrecurring'] = 'Suscripción, se renueva cada {$a} días';
$string['accessrecurringlimited'] = 'Suscripción: {$a->billing} días por pago, hasta {$a->cycles} pagos';
$string['accessrecurringopen'] = 'Suscripción: {$a->billing} días por pago, sin fecha de fin';
$string['accessuntil'] = 'Acceso hasta {$a}';
$string['addmember'] = 'Agregar vendedor';
$string['addmember_help'] = 'Vincula a esta empresa un usuario que ya existe en la plataforma y le otorga el rol de vendedor en la categoría de la empresa. La persona debe tener cuenta.';
$string['alreadyowned'] = 'Ya tenés acceso a esta oferta.';
$string['buynow'] = 'Comprar ahora';
$string['cancelconfirm'] = '¿Cancelar <strong>{$a->offer}</strong>? Conservás el acceso hasta {$a->date} — pagaste por ese período y no se te quita. Después el acceso simplemente termina, y dejamos de recordarte que renueves.';
$string['cancelconfirmlifetime'] = '¿Detener los avisos de renovación de <strong>{$a}</strong>? Tu acceso no vence, así que no cambia nada salvo los avisos.';
$string['canceldone'] = 'Suscripción cancelada. Tu acceso vale hasta {$a}.';
$string['cancelledbut'] = 'Cancelada. Tu acceso vale hasta {$a} — el período que pagaste no se te quita.';
$string['cancelledlifetime'] = 'Avisos de renovación desactivados. Tu acceso no vence.';
$string['cancelsubscription'] = 'Cancelar suscripción';
$string['cancelundo'] = 'Reactivar';
$string['cancelundone'] = 'Suscripción reactivada. Te avisaremos antes del vencimiento.';
$string['cannotsell'] = 'Solo cursos gratuitos';
$string['cansell'] = 'Vendiendo';
$string['cansellyes'] = 'Lista para vender. Pasarela activa: {$a}';
$string['commissionpct'] = 'Comisión de la plataforma (%)';
$string['companies'] = 'Empresas';
$string['company'] = 'Empresa';
$string['companycnpj'] = 'CUIT/CNPJ';
$string['companycnpj_help'] = 'Opcional. Una persona física puede vender sin identificación fiscal.';
$string['companycommission'] = 'Comisión (%)';
$string['companycommission_help'] = 'Porcentaje que la plataforma retiene sobre las ventas de esta empresa, de 0 a 100. Dejalo vacío para usar el valor por defecto del sitio — vacío y cero son distintos: vacío significa que no se negoció nada, cero significa que el socio está exento.';
$string['companycreated'] = 'Empresa {$a} creada. Agregá abajo los demás vendedores.';
$string['companyhostname'] = 'Dominio propio';
$string['companyname'] = 'Nombre';
$string['companyowner'] = 'Responsable';
$string['companyowner_help'] = 'Quien administra la empresa y vincula su cuenta de Mercado Pago. Debe tener cuenta en la plataforma.';
$string['companypanel'] = 'Marketplace: panel de la empresa';
$string['companyshortname'] = 'Nombre corto';
$string['companyshortname_help'] = 'Se usa en la URL de la empresa. Solo letras, números y guiones.';
$string['companystatus'] = 'Estado';
$string['companytheme'] = 'Tema';
$string['companyupdated'] = 'Empresa {$a} actualizada.';
$string['configurepayment'] = 'Configurar Mercado Pago';
$string['createcompany'] = 'Crear empresa';
$string['createcompanyintro'] = 'Crear una empresa aprovisiona una categoría de cursos, asigna el rol de vendedor al responsable en esa categoría y crea una cuenta de pago. Cerrá la alianza antes — esta pantalla solo la ejecuta.';
$string['defaultthemename'] = 'Tema por defecto del sitio';
$string['editcompanyintro'] = 'El nombre corto no se puede cambiar: es el número de identificación de la categoría y aparece en enlaces de la vidriera y del panel que el vendedor puede haber compartido. El responsable se cambia en la pantalla de vendedores, donde podés promover a otra persona primero.';
$string['erroraccessdays'] = 'Ingresá al menos un día de acceso.';
$string['erroralreadymember'] = 'Esta persona ya es vendedora de esta empresa.';
$string['errorbillingdays'] = 'Ingresá el intervalo de cobro en días.';
$string['errorcannotremoveowner'] = 'El responsable no se puede quitar. Promové a otra persona primero — una empresa sin responsable queda sin nadie a cargo de su cuenta de pago.';
$string['errorcannotsell'] = 'Esta empresa todavía no puede vender: configurá primero un medio de pago.';
$string['errorcommissionrange'] = 'Usá un número de 0 a 100, o dejalo vacío para heredar el valor del sitio.';
$string['errorcurrencymismatch'] = 'Esta empresa cobra en {$a->expected}, por lo que no puede vender una oferta con precio en {$a->given}. Para vender en {$a->given}, vinculá una cuenta de Mercado Pago de ese país.';
$string['errorhostnametaken'] = 'Este dominio ya está vinculado a otra empresa.';
$string['errormaxcycles'] = 'Usá cero para no poner límite, o un número positivo.';
$string['errornoaccount'] = 'Esta empresa no tiene cuenta de pago. Reinstalá o volvé a crear la empresa.';
$string['errornocourses'] = 'Elegí al menos un curso, o usá el tipo Catálogo completo.';
$string['errorplatformhostingunavailable'] = 'Alojar video en la plataforma todavía no está disponible.';
$string['errorrecurringfree'] = 'Una suscripción necesita precio. Gratuita, vencería sin forma de renovar.';
$string['errorsellerrolemissing'] = 'Falta el rol de vendedor. Reinstalá el plugin Marketplace.';
$string['errorshortnametaken'] = 'Este nombre corto ya está en uso.';
$string['errorsinglemanycourses'] = 'Una oferta de curso único libera un curso. Usá Combo para más de uno.';
$string['expiringbody'] = 'Hola:

Tu acceso a {$a->offer}, de {$a->company}, termina el {$a->date}.

No hay cobro automático — para mantener el acceso, pagá de nuevo acá:
{$a->url}

Si preferís dejarlo, no hagas nada y el acceso simplemente termina.';
$string['expiringbodyhtml'] = '<p>Hola:</p><p>Tu acceso a <strong>{$a->offer}</strong>, de {$a->company}, termina el <strong>{$a->date}</strong>.</p><p>No hay cobro automático — para mantener el acceso, <a href="{$a->url}">pagá de nuevo acá</a>.</p><p>Si preferís dejarlo, no hagas nada y el acceso simplemente termina.</p>';
$string['expiringsubject'] = 'Tu acceso a {$a->offer} termina en {$a->days} día(s)';
$string['free'] = 'Gratuito';
$string['getfree'] = 'Obtener acceso gratuito';
$string['hostingexternal'] = 'Fuera de la plataforma';
$string['hostingplatform'] = 'En la plataforma';
$string['hostingtype'] = 'Alojamiento del video';
$string['linkednotenabled'] = 'La cuenta de Mercado Pago está vinculada, pero la pasarela está apagada, así que todavía no se puede vender nada. Abrí la configuración de pago y habilitala.';
$string['makeowner'] = 'Hacer responsable';
$string['makeseller'] = 'Hacer vendedor';
$string['managecourses'] = 'Gestionar cursos';
$string['managemembers'] = 'Vendedores';
$string['marketplace:createcompany'] = 'Crear una empresa';
$string['marketplace:manageall'] = 'Administrar todas las empresas de la plataforma';
$string['marketplace:managecompany'] = 'Administrar la empresa';
$string['marketplace:managepayment'] = 'Administrar la cuenta de pago de la empresa';
$string['marketplace:publishcourse'] = 'Publicar cursos para la empresa';
$string['marketplace:viewreport'] = 'Ver el informe financiero de la empresa';
$string['memberadded'] = 'Vendedor agregado.';
$string['memberowner'] = 'Responsable';
$string['memberremoved'] = 'Vendedor quitado.';
$string['memberrolechanged'] = 'Rol cambiado.';
$string['members'] = 'Vendedores';
$string['memberseller'] = 'Vendedor';
$string['membersof'] = 'Vendedores de {$a}';
$string['messageprovider:expiring'] = 'Acceso a punto de vencer';
$string['modedays'] = 'Plazo fijo';
$string['modelifetime'] = 'De por vida';
$string['moderecurring'] = 'Suscripción';
$string['mysubsactive'] = 'Suscripciones';
$string['mysubscriptions'] = 'Mis suscripciones';
$string['mysubspayments'] = 'Pagos';
$string['nocompanies'] = 'Todavía no hay empresas.';
$string['nocompany'] = 'No pertenecés a ninguna empresa.';
$string['nomembers'] = 'Esta empresa no tiene vendedores.';
$string['nooffers'] = 'Esta empresa todavía no tiene ofertas publicadas.';
$string['nopaymentaccount'] = 'Esta empresa no tiene medio de pago configurado, así que solo puede publicar cursos gratuitos.';
$string['nopayments'] = 'Todavía no hay pagos.';
$string['nosubscriptions'] = 'Todavía no compraste nada.';
$string['offeraccess'] = 'Acceso y cobro';
$string['offeraccessdays'] = 'Días de acceso por pago';
$string['offeraccessdays_help'] = 'Cuánto acceso habilita cada pago. En una suscripción puede superar el intervalo de cobro para dar un período de gracia: cobrar cada 30 días habilitando 35 deja pasar un pago atrasado sin cortarle el acceso al estudiante.';
$string['offeraccessmode'] = 'Modelo de acceso';
$string['offeraccessmode_help'] = 'De por vida nunca vence. Plazo fijo da una cantidad de días por compra. Suscripción da un período y espera renovación.';
$string['offerbillingdays'] = 'Intervalo de cobro (días)';
$string['offerbillingdays_help'] = 'Cada cuánto se espera el próximo pago. Se usa en el aviso de vencimiento.';
$string['offercourses'] = 'Cursos que libera';
$string['offercourses_help'] = 'Qué cursos libera esta oferta. No hace falta en Catálogo completo, que sigue la categoría de la empresa.';
$string['offercreate'] = 'Nueva oferta';
$string['offeredit'] = 'Oferta';
$string['offerincludes'] = 'Incluye {$a} curso(s)';
$string['offermaxcycles'] = 'Máximo de pagos';
$string['offermaxcycles_help'] = 'Cuántas veces se puede cobrar esta suscripción en total. Cero significa sin límite. Usá 12 para un plan mensual que dura un año, o 3 para un plan anual que dura tres años.';
$string['offername'] = 'Oferta';
$string['offerprice'] = 'Precio';
$string['offerprice_help'] = 'Cero hace la oferta gratuita. Las ofertas gratuitas no pasan por Mercado Pago.';
$string['offerpublication'] = 'Publicación';
$string['offerrecurringwarning'] = 'Mercado Pago no tiene cobro recurrente con split, así que no se debita nada automáticamente. El estudiante recibe un aviso antes del vencimiento, con enlace para pagar de nuevo.';
$string['offersaved'] = 'Oferta guardada.';
$string['offersortorder'] = 'Orden de exhibición';
$string['offerssection'] = 'Ofertas';
$string['offerstatus_help'] = 'Solo las ofertas publicadas aparecen en la vidriera. Archivar no revoca el acceso ya comprado.';
$string['offertype'] = 'Tipo';
$string['offertype_help'] = 'Curso único vende un curso. Combo vende un conjunto elegido — así se arman planes por nivel como Básico, Intermedio y Completo para la misma empresa. Catálogo completo sigue la categoría de la empresa, así que los cursos nuevos entran solos.';
$string['offerunlocks'] = 'Esta es la oferta que libera el contenido que estabas viendo.';
$string['paymentcurrency'] = 'Moneda de cobro: {$a}';
$string['paymentcurrencyunknown'] = 'Moneda de cobro desconocida. Vinculá la cuenta de Mercado Pago de nuevo para detectarla.';
$string['paymentsection'] = 'Medio de pago';
$string['pluginname'] = 'Marketplace';
$string['privacy:metadata'] = 'El plugin Marketplace guarda empresas, sus vendedores y credenciales de pago.';
$string['privacy:metadata:entitlement'] = 'Qué compró el estudiante y por cuánto tiempo vale su acceso.';
$string['privacy:metadata:entitlement:companyid'] = 'La empresa que vendió.';
$string['privacy:metadata:entitlement:cycles'] = 'Cuántos pagos se hicieron.';
$string['privacy:metadata:entitlement:offerid'] = 'La oferta comprada.';
$string['privacy:metadata:entitlement:status'] = 'Si el acceso está vigente, vencido o revocado.';
$string['privacy:metadata:entitlement:timeend'] = 'Cuándo termina el acceso. Cero significa que no vence.';
$string['privacy:metadata:entitlement:timestart'] = 'Cuándo empezó el acceso.';
$string['privacy:metadata:entitlement:userid'] = 'El estudiante.';
$string['privacy:metadata:member'] = 'Para qué empresas vende la persona.';
$string['privacy:metadata:member:companyid'] = 'La empresa.';
$string['privacy:metadata:member:memberrole'] = 'Si responde por la empresa o vende para ella.';
$string['privacy:metadata:member:timecreated'] = 'Cuándo se hizo el vínculo.';
$string['privacy:metadata:member:userid'] = 'La persona vinculada a la empresa.';
$string['renewnotice'] = 'Tu acceso termina el {$a}. Renovalo para mantenerlo.';
$string['renewnow'] = 'Renovar ahora';
$string['reportaccessuntil'] = 'Acceso hasta';
$string['reportall'] = 'Todo el período';
$string['reportcommission'] = 'Comisión de la plataforma';
$string['reportcoursesnotice'] = 'Un combo cuenta entero para cada curso que libera — nadie compra un tercio de un combo. Por eso esta columna suma más que tu facturación total, y sirve para comparar cursos entre sí, no para sumar.';
$string['reportdays'] = 'Últimos {$a} días';
$string['reportentries'] = 'Ventas';
$string['reportgross'] = 'Bruto';
$string['reportlastpayment'] = 'Último pago';
$string['reportmppayment'] = 'Pago en Mercado Pago';
$string['reportnetnotice'] = 'La comisión de Mercado Pago no aparece acá porque no se nos informa: varía según el medio de pago y el plazo de acreditación, y se descuenta de su lado antes de la comisión de la plataforma. Tu monto neto es el del resumen de Mercado Pago.';
$string['reportnocourses'] = 'Todavía no se vendió ningún curso.';
$string['reportnosales'] = 'No hay ventas aprobadas en este período.';
$string['reportnosubs'] = 'No hay ofertas de suscripción, o todavía nadie se suscribió.';
$string['reportpayments'] = 'Pagos';
$string['reportsales'] = 'Ventas aprobadas';
$string['reportsaleswith'] = 'Ventas que lo incluyen';
$string['reportsection'] = 'Ventas';
$string['reportsubactive'] = 'Vigente';
$string['reportsubcancelled'] = 'Cancelada';
$string['reportsubduesoon'] = 'Vence en {$a} d';
$string['reportsubexpired'] = 'Vencida';
$string['reportsubsnotice'] = 'No hay cronograma de cobro para mostrar: Mercado Pago no tiene pagos recurrentes con split, así que cada renovación es una compra separada que extiende el acceso. Esta pantalla muestra cuántas veces pagó cada estudiante y por cuánto tiempo vale todavía su acceso.';
$string['reportviewcourses'] = 'Cursos vendidos';
$string['reportviewsubscriptions'] = 'Suscripciones';
$string['reportviewtransactions'] = 'Transacciones';
$string['sellerrole'] = 'Vendedor';
$string['sellerroledesc'] = 'Publica cursos para una empresa. No puede subir archivos, así que los videos del curso deben alojarse fuera de la plataforma.';
$string['statusactive'] = 'Activa';
$string['statusarchived'] = 'Archivada';
$string['statusdraft'] = 'Borrador';
$string['statuspublished'] = 'Publicada';
$string['statussuspended'] = 'Suspendida';
$string['tasknotifyexpiring'] = 'Avisar a los estudiantes sobre accesos por vencer';
$string['typebundle'] = 'Combo';
$string['typecatalog'] = 'Catálogo completo';
$string['typesingle'] = 'Curso único';
$string['unavailable'] = 'Todavía no está disponible para comprar.';
$string['viewstorefront'] = 'Ver vidriera';
