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
 * Strings do local_partners.
 *
 * ORDEM ALFABETICA OBRIGATORIA, e paridade exata de chaves entre os idiomas.
 *
 * @package    local_partners
 * @copyright  2026 LeoDG <callme@leodg.dev>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['alreadyapproved'] = 'Esta candidatura ya fue aprobada y la empresa {$a} existe.';
$string['alreadydecided'] = 'Esta candidatura ya fue decidida.';
$string['applicationmessage'] = 'Algo más que debamos saber';
$string['applications'] = 'Candidaturas de socios';
$string['applicationstatus'] = 'Estado';
$string['applylead'] = 'Cuéntenos sobre su operación. Respondemos todas las candidaturas.';
$string['applytitle'] = 'Postule para vender en la plataforma';
$string['approvalpending'] = 'Aprobar una candidatura crea una empresa y una categoría de cursos. Ese paso todavía no está construido: por ahora, contacte al candidato directamente.';
$string['approvedbody'] = 'Su candidatura para {$a->company} fue aprobada.

Ya puede iniciar sesion y preparar sus cursos:
{$a->url}

{$a->note}';
$string['approvedmessage'] = 'Candidatura aprobada. La empresa {$a} fue creada.';
$string['approvedsubject'] = 'Su candidatura para {$a} fue aprobada';
$string['backtohome'] = 'Volver a la página de inicio';
$string['cnpj'] = 'CNPJ';
$string['cnpj_help'] = 'Opcional. Una persona física vende sin CNPJ. Si lo indica, debe ser un número válido.';
$string['companyname'] = 'Nombre de la empresa';
$string['companyowner'] = 'Propietario de la empresa';
$string['companyowner_help'] = 'El usuario del sitio que administrará la empresa y conectará la cuenta de cobro. Si quien se postuló todavía no tiene cuenta, créela antes: crear usuarios desde un formulario público es un riesgo de spam, y esta pantalla no lo hace.';
$string['companyshortname'] = 'Nombre corto';
$string['companyshortname_help'] = 'Va a la URL de la empresa y al nombre de la categoría de cursos. Se sugiere a partir del nombre de la empresa, y usted lo confirma: la categoría es un objeto global del sitio.';
$string['confirmbody'] = 'Confirme su correo para enviar la candidatura de {$a->company}.

Abra este enlace:
{$a->url}

Si no fue usted, ignore este mensaje y no ocurre nada.';
$string['confirmdone'] = 'Correo confirmado. Su candidatura está en la cola y respondemos por correo.';
$string['confirminvalid'] = 'Este enlace no es válido, o ya fue usado.';
$string['confirmsubject'] = 'Confirme su correo para postularse como socio';
$string['confirmtitle'] = 'Confirmación de correo';
$string['contactemail'] = 'Correo electrónico';
$string['contactname'] = 'Nombre de contacto';
$string['contactphone'] = 'Teléfono';
$string['ctalead'] = 'Sin cuota mensual para empezar y sin permanencia. Solo paga cuando vende.';
$string['ctatitle'] = '¿Listo para publicar su primer curso?';
$string['decision'] = 'Decisión';
$string['decisionapprove'] = 'Aprobar y crear la empresa';
$string['decisionreject'] = 'Rechazar';
$string['enablelanding'] = 'Mostrar la página de captación de socios';
$string['enablelanding_desc'] = 'Activado, la página se sirve y el tema puede usarla como inicio para el visitante anónimo. Desactivado, la página devuelve un error y el tema vuelve a la portada estándar.';
$string['enablerecaptcha'] = 'Usar reCAPTCHA en el formulario de candidatura';
$string['enablerecaptcha_desc'] = 'Añade al formulario público el reCAPTCHA que Moodle ya trae. Las claves del sitio están configuradas, así que activarlo aquí surte efecto de inmediato. El campo oculto y el límite por IP siguen activos en ambos casos: no cuestan nada y nunca molestan a una persona.';
$string['enablerecaptcha_nokeys'] = 'Añade al formulario público el reCAPTCHA que Moodle ya trae. El sitio no tiene claves de reCAPTCHA configuradas, así que esto todavía no surte efecto. El campo oculto y el límite por IP siguen activos en ambos casos.';
$string['erroralreadyconfirmed'] = 'Esta candidatura ya fue confirmada.';
$string['erroralreadydecided'] = 'Esta candidatura ya fue decidida.';
$string['errorapplicationnotfound'] = 'Candidatura no encontrada.';
$string['errorcnpjinvalid'] = 'Este CNPJ no es válido.';
$string['errorduplicatepending'] = 'Ya hay una candidatura abierta para este correo o CNPJ. Nos pondremos en contacto.';
$string['erroremailinvalid'] = 'Introduzca un correo electrónico válido.';
$string['errorownerrequired'] = 'Elija el usuario del sitio que será propietario de la empresa.';
$string['errorplannotfound'] = 'El plan seleccionado no existe.';
$string['errorshortnamerequired'] = 'Indique un nombre corto para la empresa.';
$string['errorshortnametaken'] = 'Otra empresa ya usa este nombre corto.';
$string['errortoolong'] = 'Use como máximo {$a} caracteres.';
$string['errortoomany'] = 'Demasiadas candidaturas desde esta conexión. Inténtelo de nuevo en una hora.';
$string['faq1answer'] = 'La venta es suya. La plataforma se lleva solo la comisión, y el dinero entra en su propia cuenta de cobro.';
$string['faq1question'] = '¿Quién recibe el dinero de la venta?';
$string['faq2answer'] = 'Usted. El cobro nace en su cuenta, así que la factura la emite usted. La plataforma nunca factura en su nombre.';
$string['faq2question'] = '¿Quién emite la factura?';
$string['faq3answer'] = 'Sí. Un curso gratuito no cuesta nada y no necesita plan. La comisión solo existe en la venta de pago.';
$string['faq3question'] = '¿Puedo publicar cursos gratuitos?';
$string['faq4answer'] = 'En el plan Starter la plataforma paga el ancho de banda del vídeo, así que la resolución máxima sigue el precio del curso. En los planes en los que usted conecta su propio almacenamiento no hay tope.';
$string['faq4question'] = '¿Por qué la calidad del vídeo depende del precio del curso?';
$string['faqtitle'] = 'Preguntas que nos llegan siempre';
$string['frontpagemode'] = 'Página de inicio para quien no ha iniciado sesión';
$string['frontpagemode_desc'] = 'Elija lo que ve el visitante anónimo en la dirección del sitio. La página de captación sustituye toda la portada para él; quien ha iniciado sesión sigue viendo la portada normal en ambos casos. Nunca sustituye la portada de un dominio de vendedor.';
$string['frontpagemodedefault'] = 'Portada de Moodle';
$string['frontpagemodelanding'] = 'Página de captación de socios';
$string['heroctatext'] = 'Quiero ser socio';
$string['herolead'] = 'Publique sus cursos, cobre en su propio nombre y pague solo cuando venda.';
$string['herotitle'] = 'Venda sus cursos sin construir una plataforma';
$string['honeypotlabel'] = 'Deje este campo vacío';
$string['howtitle'] = 'Cómo funciona';
$string['landingdisabled'] = 'La página de captación de socios está desactivada.';
$string['landingtitle'] = 'Sea socio';
$string['maxperhour'] = 'Candidaturas por hora, por conexión';
$string['maxperhour_desc'] = 'Límite de frecuencia del formulario público. Es la capa antispam que siempre vale: a diferencia del captcha, no depende de ninguna clave configurada.';
$string['metadescription'] = 'Publique y venda sus cursos en línea. Sin cuota mensual para empezar, cobro en su propia cuenta y comisión solo sobre lo que venda.';
$string['newapplicationbody'] = 'Llegó una candidatura de socio de {$a->company}, enviada por {$a->contact}.

Revísela aquí:
{$a->url}';
$string['newapplicationsubject'] = 'Nueva candidatura de socio: {$a}';
$string['noapplications'] = 'Todavía no hay candidaturas.';
$string['opencompany'] = 'Abrir la empresa';
$string['ownermatched'] = 'Ya existe una cuenta para {$a} y está seleccionada arriba.';
$string['ownerwillbecreated'] = 'No existe cuenta para {$a}. Deje el propietario en blanco y se creará una al aprobar, con un correo invitando a definir la contraseña. Elija otro usuario solo si la empresa debe pertenecer a otra persona.';
$string['plan'] = 'Plan';
$string['plancommission'] = '{$a}% de comisión por venta';
$string['planctatext'] = 'Empezar con este plan';
$string['planfree'] = 'Sin cuota mensual';
$string['planhostingbyos'] = 'Usted conecta su propio almacenamiento de vídeo';
$string['planhostingnative'] = 'Alojamiento de vídeo incluido';
$string['planofinterest'] = 'Plan de interés';
$string['planslead'] = 'Empiece sin cuota mensual y cambie de plan cuando compense.';
$string['plansnote'] = 'La comisión se aplica sobre el importe bruto de la venta. Un curso gratuito no tiene comisión ni necesita plan.';
$string['planstitle'] = 'Planes';
$string['planundecided'] = 'Todavía no lo sé';
$string['pluginname'] = 'Captación de socios';
$string['privacy:metadata:application'] = 'Candidaturas de empresas que quieren vender en la plataforma. Quien se postula normalmente no es un usuario registrado.';
$string['privacy:metadata:application:cnpj'] = 'El CNPJ de la empresa, cuando se indica.';
$string['privacy:metadata:application:companyname'] = 'El nombre de la empresa tal como se escribió.';
$string['privacy:metadata:application:contactemail'] = 'El correo electrónico de contacto.';
$string['privacy:metadata:application:contactname'] = 'El nombre de quien se postuló.';
$string['privacy:metadata:application:contactphone'] = 'El teléfono de contacto, cuando se indica.';
$string['privacy:metadata:application:message'] = 'El mensaje de texto libre enviado con la candidatura.';
$string['privacy:metadata:application:reviewerid'] = 'El usuario del sitio que revisó la candidatura.';
$string['privacy:metadata:application:submitterip'] = 'La dirección IP desde la que llegó la candidatura, usada solo para el límite de frecuencia.';
$string['privacy:metadata:application:timecreated'] = 'Cuándo se envió la candidatura.';
$string['privacy:metadata:application:userid'] = 'El usuario del sitio que envió la candidatura, cuando vino de alguien autenticado.';
$string['privacy:path:applications'] = 'Candidaturas de socio enviadas';
$string['privacy:path:reviews'] = 'Candidaturas de socio revisadas';
$string['rejectedbody'] = 'Su candidatura para {$a->company} no fue aprobada esta vez.

{$a->note}';
$string['rejectedmessage'] = 'Candidatura de {$a} rechazada.';
$string['rejectedsubject'] = 'Sobre su candidatura para {$a}';
$string['requireemailconfirmation'] = 'Exigir confirmación de correo al visitante anónimo';
$string['requireemailconfirmation_desc'] = 'La candidatura solo llega a la cola después de que la persona abra un enlace enviado a la dirección que escribió. Es la capa antirrobot que las otras no sustituyen: el límite de frecuencia y el captcha le cuestan tiempo al robot, esta le cuesta un buzón real y funcional por candidatura. Depende de que el sitio pueda enviar correo: con el SMTP roto, activarlo bloquea la cola. Nunca se aplica al usuario autenticado: el sitio ya confirmó su dirección.';
$string['reviewnote'] = 'Observación';
$string['reviewnote_help'] = 'Va en el correo a quien se postuló. En un rechazo es la única explicación que recibe, así que escríbala pensando en esa persona.';
$string['savedecision'] = 'Guardar decisión';
$string['statusapproved'] = 'Aprobada';
$string['statuspending'] = 'En cola';
$string['statusrejected'] = 'Rechazada';
$string['statusunconfirmed'] = 'Esperando confirmación de correo';
$string['step1text'] = 'Envíe el formulario. Lleva un minuto y solo pregunta lo necesario para hablar con usted.';
$string['step1title'] = 'Postúlese';
$string['step2text'] = 'Respondemos, acordamos el plan y preparamos su espacio en la plataforma.';
$string['step2title'] = 'Conversamos';
$string['step3text'] = 'Usted conecta su propia cuenta de cobro. El dinero de cada venta entra allí.';
$string['step3title'] = 'Conecte su cuenta';
$string['step4text'] = 'Suba sus cursos y empiece a vender. La comisión se cobra por venta, nunca por adelantado.';
$string['step4title'] = 'Publique y venda';
$string['submitapplication'] = 'Enviar candidatura';
$string['submittedon'] = 'Enviada el';
$string['taskpurgeunconfirmed'] = 'Eliminar candidaturas de asociación no confirmadas';
$string['thanksbody'] = 'Su candidatura llegó. Las leemos todas y respondemos por correo.';
$string['thanksbodyunconfirmed'] = 'Revise su bandeja de entrada. Su candidatura nos llega en cuanto abra el enlace que acabamos de enviar.';
$string['thankstitle'] = 'Candidatura recibida';
$string['tierabove'] = 'Por encima de {$a}';
$string['tierany'] = 'Cualquier precio';
$string['tierupto'] = 'Hasta {$a}';
$string['unconfirmedretentiondays'] = 'Conservar candidaturas no confirmadas durante (días)';
$string['unconfirmedretentiondays_desc'] = 'La candidatura cuyo correo nunca se confirme se elimina después de este plazo, junto con el nombre, el teléfono y la dirección IP que traía. Use 0 para conservarlas indefinidamente.';
$string['value1text'] = 'Sin cuota mensual en el plan de entrada. La plataforma gana cuando usted gana, y no antes.';
$string['value1title'] = 'Paga cuando vende';
$string['value2text'] = 'El cobro nace en su propia cuenta. El dinero es suyo desde el principio, y la factura la emite usted.';
$string['value2title'] = 'El dinero entra en su cuenta';
$string['value3text'] = 'Matrícula, progreso, certificado e informes vienen de Moodle, usado por universidades de todo el mundo.';
$string['value3title'] = 'Una plataforma que no tiene que construir';
$string['valuetitle'] = 'Por qué vender aquí';
$string['website'] = 'Sitio web';
