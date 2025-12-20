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
 * Strings for component 'paygw_epayco', language 'es'.
 *
 * @package    paygw_epayco
 * @copyright  2025 ingeweb.co <soporte@ingeweb.co>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['pluginname'] = 'ePayco';
$string['pluginname_desc'] = 'El plugin ePayco permite recibir pagos a traves de la pasarela de pagos ePayco (Colombia).';
$string['gatewayname'] = 'ePayco';
$string['gatewaydescription'] = 'ePayco es una pasarela de pagos colombiana que soporta tarjetas de credito/debito, PSE, pagos en efectivo y mas.';

// Configuracion.
$string['environment'] = 'Ambiente';
$string['environment_help'] = 'Selecciona el ambiente de ePayco. Usa Pruebas para desarrollo y Produccion para transacciones reales.';
$string['environment:test'] = 'Pruebas (Sandbox)';
$string['environment:production'] = 'Produccion (Real)';

$string['p_cust_id_cliente'] = 'P_CUST_ID_CLIENTE';
$string['p_cust_id_cliente_help'] = 'Tu ID de cliente de ePayco. Se encuentra en Configuracion > Integraciones > Llaves API en tu panel de ePayco.';
$string['p_key'] = 'P_KEY';
$string['p_key_help'] = 'Tu P_KEY de ePayco. Se encuentra en Configuracion > Integraciones > Llaves API en tu panel de ePayco.';
$string['public_key'] = 'PUBLIC_KEY';
$string['public_key_help'] = 'Tu llave publica de API de ePayco. Se encuentra en Configuracion > Integraciones > Llaves API en tu panel de ePayco.';
$string['private_key'] = 'PRIVATE_KEY';
$string['private_key_help'] = 'Tu llave privada de API de ePayco. Se encuentra en Configuracion > Integraciones > Llaves API en tu panel de ePayco.';

$string['language'] = 'Idioma de la pagina de pago';
$string['language:es'] = 'Espanol';
$string['language:en'] = 'Ingles';

$string['collectcustomerdata'] = 'Recopilar datos del cliente';
$string['collectcustomerdata_desc'] = 'Pre-llenar informacion del cliente (email, nombre) en el formulario de pago.';

$string['callback_urls'] = 'URLs de Configuracion ePayco';
$string['confirmationurl'] = 'URL de Confirmacion';
$string['responseurl'] = 'URL de Respuesta';

// Proceso de pago.
$string['redirecting'] = 'Redirigiendo a ePayco...';
$string['clicktopay'] = 'Haz clic en el boton de abajo para proceder con el pago';
$string['paynow'] = 'Pagar Ahora';
$string['reference'] = 'Referencia';

$string['paymentsuccessful'] = 'El pago fue exitoso';
$string['paymentpending'] = 'Tu pago esta siendo procesado. Te notificaremos cuando se confirme.';
$string['paymentfailed'] = 'El pago fallo: {$a}';
$string['paymentdeclined'] = 'Tu pago fue rechazado. Por favor intenta de nuevo o usa otro metodo de pago.';
$string['paymentcancelled'] = 'El pago fue cancelado';
$string['paymentnotcompleted'] = 'El pago no fue completado. Por favor intenta de nuevo.';
$string['paymentunknownstatus'] = 'Estado de pago desconocido: {$a}. Por favor contacta a soporte.';

$string['transactionnotfound'] = 'Transaccion no encontrada. Por favor contacta a soporte si se te cobro.';
$string['alreadydelivered'] = 'Este pago ya ha sido procesado y entregado.';
$string['deliveryerror'] = 'Ocurrio un error al procesar tu inscripcion. Por favor contacta a soporte.';
$string['signatureinvalid'] = 'Firma de transaccion invalida. Por favor contacta a soporte.';

// Mensajes.
$string['messageprovider:payment_failed'] = 'Notificacion de pago fallido';
$string['messageprovider:payment_successful'] = 'Confirmacion de pago exitoso';
$string['messageprovider:payment_pending'] = 'Notificacion de pago pendiente';
$string['messageprovider:payment_status'] = 'Notificaciones de estado de pago';

$string['payment:successful:subject'] = 'Pago exitoso';
$string['payment:successful:message'] = 'Tu pago fue exitoso. Ahora puedes acceder a tu contenido en: {$a->url}';
$string['payment:failed:subject'] = 'Pago fallido';
$string['payment:failed:message'] = 'Tu pago no pudo ser procesado. Por favor intenta de nuevo o usa otro metodo de pago.';
$string['payment:pending:subject'] = 'Pago pendiente';
$string['payment:pending:message'] = 'Tu pago esta siendo procesado. Te notificaremos cuando se confirme.';

// Privacidad.
$string['privacy:metadata:paygw_epayco_transactions'] = 'Almacena datos de transacciones para pagos de ePayco.';
$string['privacy:metadata:paygw_epayco_transactions:userid'] = 'El ID del usuario que realizo el pago.';
$string['privacy:metadata:paygw_epayco_transactions:transactionid'] = 'El ID de transaccion de ePayco.';
$string['privacy:metadata:paygw_epayco_transactions:reference'] = 'La referencia unica del pago.';
$string['privacy:metadata:paygw_epayco_transactions:amount'] = 'El monto del pago.';
$string['privacy:metadata:paygw_epayco_transactions:status'] = 'El estado de la transaccion.';
$string['privacy:metadata:paygw_epayco_transactions:timecreated'] = 'La hora en que se creo la transaccion.';

$string['privacy:metadata:epayco'] = 'Para procesar pagos, algunos datos del usuario se envian a ePayco.';
$string['privacy:metadata:epayco:email'] = 'Tu direccion de correo electronico.';
$string['privacy:metadata:epayco:fullname'] = 'Tu nombre completo.';

// Tareas programadas.
$string['task_check_pending'] = 'Verificar transacciones pendientes de ePayco';
$string['task_cleanup_expired'] = 'Limpiar transacciones expiradas de ePayco';

// Notificaciones de estado.
$string['payment_notification'] = 'Notificacion de pago ePayco';
$string['payment_approved'] = 'Tu pago ha sido aprobado. Ahora puedes acceder a tu contenido.';
$string['payment_declined'] = 'Tu pago ha sido rechazado. Por favor intenta con otro metodo de pago.';
$string['payment_expired_subject'] = 'Pago expirado';
$string['payment_expired_message'] = 'Tu pago de {$a->amount} {$a->currency} iniciado el {$a->date} ha expirado. Si deseas completar la compra, por favor inicia un nuevo pago.';

// Configuracion de plantillas de email.
$string['emailtemplates'] = 'Plantillas de Notificacion por Email';
$string['availableplaceholders'] = 'Placeholders Disponibles';
$string['placeholder:firstname'] = 'Nombre del usuario';
$string['placeholder:fullname'] = 'Nombre completo del usuario';
$string['placeholder:amount'] = 'Monto del pago con moneda';
$string['placeholder:currency'] = 'Codigo de moneda';
$string['placeholder:orderid'] = 'ID de Orden/Referencia';

$string['email_completed_subject'] = 'Pago Completado - Asunto';
$string['email_completed_subject_default'] = 'Pago Exitoso - Orden #{orderid}';
$string['email_completed_body'] = 'Pago Completado - Cuerpo';
$string['email_completed_body_default'] = 'Hola {firstname},

Tu pago de {amount} (ID de Orden: {orderid}) fue completado exitosamente.

Si no puedes acceder al curso, por favor contacta al administrador.';

$string['email_pending_subject'] = 'Pago Pendiente - Asunto';
$string['email_pending_subject_default'] = 'Pago Pendiente - Orden #{orderid}';
$string['email_pending_body'] = 'Pago Pendiente - Cuerpo';
$string['email_pending_body_default'] = 'Hola {firstname},

Tu pago de {amount} (ID de Orden: {orderid}) esta pendiente de aprobacion.

Te notificaremos cuando el pago sea confirmado.';

// Boton de pago y modal.
$string['paywitepayco'] = 'Pagar con ePayco';
$string['amounttopay'] = 'Monto a pagar';
$string['redirectepayco'] = 'Seras redirigido a ePayco para completar tu pago.';

// Pagina de respuesta.
$string['paymentresult'] = 'Resultado del Pago';
$string['paymentapproved'] = 'Pago Aprobado';
$string['paymentapproved_desc'] = 'Tu pago ha sido procesado exitosamente. Ahora puedes acceder a tu contenido.';
$string['paymentpending_desc'] = 'Tu pago esta siendo procesado. Te notificaremos cuando se confirme.';
$string['paymentrejected'] = 'Pago Rechazado';
$string['paymentrejected_desc'] = 'Tu pago fue rechazado. Por favor intenta de nuevo con otro metodo de pago.';
$string['paymentfailed_desc'] = 'Ocurrio un error durante el procesamiento del pago. Por favor intenta de nuevo.';
$string['transactiondetails'] = 'Detalles de la Transaccion';
$string['refpayco'] = 'Referencia ePayco';
$string['amount'] = 'Monto';

// Errores.
$string['error:noreference'] = 'No se proporciono referencia de transaccion.';
$string['error:queryingepayco'] = 'No se pudo obtener informacion de la transaccion de ePayco. Por favor contacta a soporte.';

// Tareas programadas.
$string['task:checkpendingtransactions'] = 'Verificar transacciones pendientes de ePayco';
$string['task:cleanupexpiredtransactions'] = 'Limpiar transacciones expiradas de ePayco';

// Metadatos de privacidad para campos adicionales.
$string['privacy:metadata:paygw_epayco_transactions:component'] = 'El nombre del componente.';
$string['privacy:metadata:paygw_epayco_transactions:paymentarea'] = 'El area de pago.';
$string['privacy:metadata:paygw_epayco_transactions:itemid'] = 'El ID del item.';
$string['privacy:metadata:paygw_epayco_transactions:ref_payco'] = 'La referencia de pago de ePayco.';
$string['privacy:metadata:paygw_epayco_transactions:currency'] = 'La moneda del pago.';
$string['privacy:metadata:epayco:firstname'] = 'Tu nombre.';
$string['privacy:metadata:epayco:lastname'] = 'Tu apellido.';
$string['privacy:metadata:epayco:phone'] = 'Tu numero de telefono.';
$string['privacy:metadata:epayco:address'] = 'Tu direccion.';
$string['privacy:metadata:epayco:city'] = 'Tu ciudad.';
