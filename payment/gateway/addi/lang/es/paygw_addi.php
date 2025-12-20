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
 * Strings for component 'paygw_addi', language 'es'.
 *
 * @package    paygw_addi
 * @copyright  2025 ingeweb.co <soporte@ingeweb.co>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['pluginname'] = 'Addi';
$string['pluginname_desc'] = 'El plugin Addi permite recibir pagos a traves del servicio Compra Ahora Paga Despues (BNPL) de Addi (Colombia).';
$string['gatewayname'] = 'Addi';
$string['gatewaydescription'] = 'Addi es un servicio colombiano de Compra Ahora Paga Despues que permite a los clientes dividir sus pagos en cuotas.';

// Configuracion.
$string['environment'] = 'Ambiente';
$string['environment_help'] = 'Selecciona el ambiente de Addi. Usa Sandbox para pruebas y Produccion para transacciones reales.';
$string['environment:sandbox'] = 'Sandbox (Pruebas)';
$string['environment:production'] = 'Produccion (Real)';

$string['clientid'] = 'Client ID';
$string['clientid_help'] = 'Tu Client ID de Addi. Proporcionado por Addi cuando te registras como comercio.';
$string['clientsecret'] = 'Client Secret';
$string['clientsecret_help'] = 'Tu Client Secret de Addi. Proporcionado por Addi cuando te registras como comercio.';
$string['allyslug'] = 'Ally Slug';
$string['allyslug_help'] = 'Tu identificador de comercio (Ally Slug). Proporcionado por Addi durante la integracion.';

$string['minamount'] = 'Monto minimo (COP)';
$string['minamount_help'] = 'Monto minimo de orden permitido para pagos con Addi. Por defecto es 50,000 COP.';
$string['maxamount'] = 'Monto maximo (COP)';
$string['maxamount_help'] = 'Monto maximo de orden permitido para pagos con Addi. Por defecto es 5,000,000 COP.';
$string['invalidminamount'] = 'El monto minimo debe ser un numero positivo.';
$string['invalidmaxamount'] = 'El monto maximo debe ser un numero positivo.';
$string['maxmustbegreaterthanmin'] = 'El monto maximo debe ser mayor que el monto minimo.';

$string['callback_urls'] = 'URLs de Configuracion Addi';
$string['redirecturl'] = 'URL de Redireccion';
$string['webhookurl'] = 'URL de Webhook';

// Proceso de pago.
$string['redirecting'] = 'Redirigiendo a Addi...';
$string['clicktopay'] = 'Haz clic en el boton de abajo para pagar con Addi';
$string['paynow'] = 'Pagar con Addi';
$string['paywithaddi'] = 'Pagar con Addi';
$string['reference'] = 'Referencia';
$string['amounttopay'] = 'Monto a pagar';
$string['redirectaddi'] = 'Seras redirigido a Addi para completar tu pago en cuotas.';

$string['amountbelowminimum'] = 'El monto ({$a->amount} {$a->currency}) esta por debajo del minimo permitido para Addi ({$a->min} {$a->currency}).';
$string['amountabovemaximum'] = 'El monto ({$a->amount} {$a->currency}) esta por encima del maximo permitido para Addi ({$a->max} {$a->currency}).';

// Pagina de respuesta.
$string['paymentresult'] = 'Resultado del Pago';
$string['paymentapproved'] = 'Pago Aprobado';
$string['paymentapproved_desc'] = 'Tu financiacion con Addi ha sido aprobada. Ahora puedes acceder a tu contenido.';
$string['paymentpending'] = 'Pago Pendiente';
$string['paymentpending_desc'] = 'Tu solicitud de Addi esta siendo procesada. Te notificaremos cuando se confirme.';
$string['paymentrejected'] = 'Pago Rechazado';
$string['paymentrejected_desc'] = 'Tu solicitud de financiacion con Addi fue rechazada. Por favor intenta con otro metodo de pago.';
$string['paymentfailed'] = 'Pago Fallido';
$string['paymentfailed_desc'] = 'Ocurrio un error durante el procesamiento del pago. Por favor intenta de nuevo.';
$string['paymentcancelled'] = 'Pago Cancelado';
$string['paymentcancelled_desc'] = 'Cancelaste la solicitud de financiacion con Addi.';
$string['transactiondetails'] = 'Detalles de la Transaccion';
$string['applicationid'] = 'ID de Aplicacion Addi';
$string['amount'] = 'Monto';

// Errores.
$string['error:noreference'] = 'No se proporciono referencia de transaccion.';
$string['error:applicationfailed'] = 'No se pudo crear la solicitud de Addi. Por favor intenta de nuevo.';
$string['error:queryingaddi'] = 'No se pudo obtener informacion de la transaccion de Addi. Por favor contacta a soporte.';
$string['error:invalidwebhook'] = 'Firma de webhook invalida.';

// Tareas programadas.
$string['task:checkpendingtransactions'] = 'Verificar transacciones pendientes de Addi';
$string['task:cleanupexpiredtransactions'] = 'Limpiar transacciones expiradas de Addi';

// Metadatos de privacidad.
$string['privacy:metadata:paygw_addi_transactions'] = 'Almacena datos de transacciones para pagos de Addi.';
$string['privacy:metadata:paygw_addi_transactions:userid'] = 'El ID del usuario que realizo el pago.';
$string['privacy:metadata:paygw_addi_transactions:component'] = 'El nombre del componente.';
$string['privacy:metadata:paygw_addi_transactions:paymentarea'] = 'El area de pago.';
$string['privacy:metadata:paygw_addi_transactions:itemid'] = 'El ID del item.';
$string['privacy:metadata:paygw_addi_transactions:reference'] = 'La referencia unica del pago.';
$string['privacy:metadata:paygw_addi_transactions:applicationid'] = 'El ID de aplicacion de Addi.';
$string['privacy:metadata:paygw_addi_transactions:amount'] = 'El monto del pago.';
$string['privacy:metadata:paygw_addi_transactions:currency'] = 'La moneda del pago.';
$string['privacy:metadata:paygw_addi_transactions:status'] = 'El estado de la transaccion.';
$string['privacy:metadata:paygw_addi_transactions:timecreated'] = 'La hora en que se creo la transaccion.';

$string['privacy:metadata:addi'] = 'Para procesar pagos, algunos datos del usuario se envian a Addi.';
$string['privacy:metadata:addi:email'] = 'Tu direccion de correo electronico.';
$string['privacy:metadata:addi:firstname'] = 'Tu nombre.';
$string['privacy:metadata:addi:lastname'] = 'Tu apellido.';
$string['privacy:metadata:addi:phone'] = 'Tu numero de telefono.';
$string['privacy:metadata:addi:address'] = 'Tu direccion.';

// Mensajes.
$string['messageprovider:payment_status'] = 'Notificaciones de estado de pago';

// Configuracion de plantillas de email.
$string['emailtemplates'] = 'Plantillas de Notificacion por Email';
$string['availableplaceholders'] = 'Placeholders Disponibles';
$string['placeholder:firstname'] = 'Nombre del usuario';
$string['placeholder:fullname'] = 'Nombre completo del usuario';
$string['placeholder:amount'] = 'Monto del pago con moneda';
$string['placeholder:currency'] = 'Codigo de moneda';
$string['placeholder:orderid'] = 'ID de Orden/Referencia';

$string['email_completed_subject'] = 'Pago Completado - Asunto';
$string['email_completed_subject_default'] = 'Financiacion Addi Aprobada - Orden #{orderid}';
$string['email_completed_body'] = 'Pago Completado - Cuerpo';
$string['email_completed_body_default'] = 'Hola {firstname},

Tu financiacion con Addi por {amount} (ID de Orden: {orderid}) ha sido aprobada.

Ahora puedes acceder a tu curso. Tu pago sera dividido en cuotas convenientes segun lo acordado con Addi.

Si tienes alguna pregunta, por favor contacta a soporte.';

$string['email_pending_subject'] = 'Pago Pendiente - Asunto';
$string['email_pending_subject_default'] = 'Solicitud Addi Pendiente - Orden #{orderid}';
$string['email_pending_body'] = 'Pago Pendiente - Cuerpo';
$string['email_pending_body_default'] = 'Hola {firstname},

Tu solicitud de financiacion con Addi por {amount} (ID de Orden: {orderid}) esta siendo procesada.

Te notificaremos cuando la solicitud sea aprobada.';
