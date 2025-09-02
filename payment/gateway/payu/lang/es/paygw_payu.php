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
 * Strings for component 'paygw_payu', language 'es' - COMPLETO.
 *
 * @package    paygw_payu
 * @copyright  2024 Orion Cloud Consulting SAS
 * @author     Alonso Arias <soporte@orioncloud.com.co>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['pluginname'] = 'PayU Colombia';
$string['pluginname_desc'] = 'El gateway de pago PayU Colombia permite pagos en línea a través de varios métodos incluyendo tarjetas de crédito, transferencias bancarias PSE, Nequi y pagos en efectivo.';
$string['gatewayname'] = 'PayU Colombia';
$string['gatewaydescription'] = 'PayU es un proveedor líder de servicios de pago en América Latina, procesando pagos en línea seguros en Colombia.';

// Cadenas de configuración.
$string['merchantid'] = 'ID del Comercio';
$string['merchantid_help'] = 'Tu número identificador de comercio PayU.';
$string['payuaccountid'] = 'ID de Cuenta';
$string['accountid'] = 'ID de Cuenta';
$string['accountid_help'] = 'Tu identificador de cuenta PayU para Colombia.';
$string['apikey'] = 'Clave API';
$string['apikey_help'] = 'Tu clave API secreta proporcionada por PayU. ¡Mantenla segura!';
$string['apilogin'] = 'Login API';
$string['apilogin_help'] = 'Tu credencial de login API para los servicios PayU.';
$string['testmode'] = 'Modo de prueba';
$string['testmode_help'] = 'Habilita el modo de prueba para usar el entorno sandbox de PayU para probar pagos.';
$string['debugmode'] = 'Modo de depuración';
$string['debugmode_help'] = 'Habilita el modo de depuración para registrar información detallada de transacciones para solución de problemas.';

// Configuración de métodos de pago.
$string['paymentmethods'] = 'Configuración de Métodos de Pago';
$string['enabledmethods'] = 'Métodos de pago habilitados';
$string['enabledmethods_help'] = 'Selecciona qué métodos de pago deben estar disponibles para los usuarios. Al menos un método debe estar habilitado.';

// Métodos de pago.
$string['creditcard'] = 'Tarjeta de Crédito/Débito';
$string['visa'] = 'Visa';
$string['mastercard'] = 'Mastercard';
$string['amex'] = 'American Express';
$string['diners'] = 'Diners Club';
$string['pse'] = 'PSE - Transferencia Bancaria';
$string['nequi'] = 'Nequi';
$string['bancolombia'] = 'Botón Bancolombia';
$string['googlepay'] = 'Google Pay';
$string['cash'] = 'Pago en Efectivo';
$string['efecty'] = 'Efecty';
$string['baloto'] = 'Baloto';
$string['bankreferenced'] = 'Referencia Bancaria';
$string['otherscash'] = 'Otros Métodos en Efectivo';

// Campos del formulario.
$string['paymentmethod'] = 'Método de Pago';
$string['choosepaymentmethod'] = 'Elige un método de pago';
$string['cardholder'] = 'Nombre del Titular';
$string['cardnumber'] = 'Número de Tarjeta';
$string['expmonth'] = 'Mes de Vencimiento';
$string['expyear'] = 'Año de Vencimiento';
$string['cvv'] = 'CVV';
$string['cardnetwork'] = 'Red de Tarjeta';
$string['phone'] = 'Número de Teléfono';
$string['documentnumber'] = 'Número de Documento';
$string['documenttype'] = 'Tipo de Documento';
$string['usertype'] = 'Tipo de Persona';
$string['personnatural'] = 'Persona Natural';
$string['personjuridica'] = 'Persona Jurídica';
$string['psebank'] = 'Selecciona Tu Banco';
$string['selectbank'] = 'Selecciona un banco...';
$string['cashmethod'] = 'Método de Pago en Efectivo';
$string['googlepaytoken'] = 'Token de Google Pay';

// Campos de transacción.
$string['reference'] = 'Referencia';
$string['transactionid'] = 'ID de Transacción';
$string['orderid'] = 'ID de Orden';
$string['amount'] = 'Monto';
$string['status'] = 'Estado';
$string['date'] = 'Fecha';
$string['description'] = 'Descripción';
$string['for'] = 'Para';

// Instrucciones y mensajes.
$string['instruction_creditcard'] = 'Ingresa la información de tu tarjeta de crédito o débito para completar el pago.';
$string['instruction_pse'] = 'Serás redirigido al sitio web de tu banco para completar el pago de forma segura.';
$string['instruction_nequi'] = 'Recibirás una notificación push en tu app de Nequi para autorizar el pago.';
$string['instruction_bancolombia'] = 'Serás redirigido a Bancolombia para completar el pago.';
$string['instruction_googlepay'] = 'Completa el pago usando tus tarjetas guardadas en Google Pay.';
$string['instruction_cash'] = 'Imprime o guarda el recibo de pago y paga en cualquier ubicación autorizada.';

// Instrucciones de pago en efectivo.
$string['cash_instructions_efecty'] = 'Presenta este recibo en cualquier punto Efecty a nivel nacional para completar tu pago.';
$string['cash_instructions_baloto'] = 'Paga en cualquier punto autorizado Baloto usando el número de referencia proporcionado.';
$string['cash_instructions_bank_referenced'] = 'Usa el número de referencia para pagar en cualquier sucursal bancaria o a través de banca en línea.';
$string['cash_instructions_others_cash'] = 'Completa tu pago en cualquier punto de pago autorizado usando el número de referencia.';

// Mensajes de estado.
$string['paymentsuccess'] = 'Pago Exitoso';
$string['paymentpending'] = 'Pago Pendiente';
$string['paymenterror'] = 'Error en el Pago';
$string['paymentdeclined'] = 'Pago Rechazado';
$string['paymentexpired'] = 'Pago Expirado';
$string['paymentcancelled'] = 'Pago Cancelado';

// Mensajes de respuesta.
$string['response_approved'] = 'Transacción aprobada exitosamente.';
$string['response_network_rejected'] = 'Transacción rechazada por la red de pago.';
$string['response_entity_declined'] = 'Transacción rechazada por el banco.';
$string['response_insufficient_funds'] = 'Fondos insuficientes en la cuenta.';
$string['response_invalid_card'] = 'Número de tarjeta inválido.';
$string['response_contact_entity'] = 'Por favor contacta a tu banco.';
$string['response_expired_card'] = 'La tarjeta ha expirado.';
$string['response_restricted_card'] = 'La tarjeta está restringida.';
$string['response_invalid_expiry_cvv'] = 'Fecha de vencimiento o código de seguridad inválido.';
$string['response_partial_approval'] = 'Aprobación parcial recibida.';
$string['response_not_authorized_internet'] = 'Tarjeta no autorizada para transacciones por internet.';
$string['response_antifraud_rejected'] = 'Transacción rechazada por sistema antifraude.';
$string['response_certificate_not_found'] = 'Certificado digital no encontrado.';
$string['response_bank_unreachable'] = 'No se pudo conectar con el banco.';
$string['response_time_expired'] = 'Tiempo de transacción expirado.';
$string['response_pending_review'] = 'Transacción pendiente de revisión.';
$string['response_error'] = 'Ocurrió un error procesando el pago.';
$string['response_unknown'] = 'Respuesta desconocida del procesador de pagos.';

// Acciones.
$string['submitpayment'] = 'Realizar Pago';
$string['processingpayment'] = 'Procesando pago...';
$string['tryagain'] = 'Intentar de Nuevo';
$string['viewreceipt'] = 'Ver Recibo';
$string['viewhtmlreceipt'] = 'Ver Recibo en Línea';
$string['downloadpdfreceipt'] = 'Descargar Recibo PDF';
$string['gotobanksite'] = 'Ir al Sitio del Banco';
$string['backtohome'] = 'Volver al Inicio';
$string['continue'] = 'Continuar';

// Cadenas adicionales de UI.
$string['paymentdetails'] = 'Detalles del Pago';
$string['ordersummary'] = 'Resumen del Pedido';
$string['paymentreceipt'] = 'Recibo de Pago';
$string['paymentinstructions'] = 'Instrucciones de Pago';
$string['pseinstructions'] = 'Transferencia Bancaria PSE';
$string['expirationdate'] = 'Fecha de Vencimiento';
$string['loadingpaymentgateway'] = 'Cargando gateway de pago...';

// Insignias de estado.
$string['statusbadge_APPROVED'] = 'success';
$string['statusbadge_PENDING'] = 'warning';
$string['statusbadge_DECLINED'] = 'danger';
$string['statusbadge_ERROR'] = 'danger';
$string['statusbadge_EXPIRED'] = 'secondary';

// Configuración de caché.
$string['cachesettings'] = 'Configuración de Caché';
$string['enablecache'] = 'Habilitar caché';
$string['enablecache_help'] = 'Cachear listas de bancos PSE y métodos de pago para mejorar el rendimiento.';
$string['cachettl'] = 'Tiempo de vida del caché';
$string['cachettl_help'] = 'Cuánto tiempo cachear datos en segundos (predeterminado: 86400 = 24 horas).';

// Configuración de notificaciones.
$string['notificationsettings'] = 'Configuración de Notificaciones';
$string['enablenotifications'] = 'Habilitar notificaciones';
$string['enablenotifications_help'] = 'Enviar notificaciones por correo electrónico a los usuarios sobre el estado de su pago.';

// Configuración de callback.
$string['callbackurl'] = 'URL de Callback';
$string['callbackurl_help'] = 'Configura esta URL en tu panel de comercio PayU para notificaciones de pago.';

// Privacidad.
$string['privacy:metadata:paygw_payu:payu'] = 'Información enviada a PayU para procesamiento de pagos.';
$string['privacy:metadata:paygw_payu:payu:fullname'] = 'El nombre completo del usuario que realiza el pago.';
$string['privacy:metadata:paygw_payu:payu:email'] = 'La dirección de correo electrónico del usuario.';
$string['privacy:metadata:paygw_payu:payu:phone'] = 'El número de teléfono proporcionado para la transacción.';
$string['privacy:metadata:paygw_payu:payu:documentnumber'] = 'El número de documento proporcionado para identificación.';
$string['privacy:metadata:paygw_payu:payu:address'] = 'La dirección de facturación del usuario.';
$string['privacy:metadata:paygw_payu:payu:creditcard'] = 'Información de tarjeta de crédito para procesamiento (tokenizada).';
$string['privacy:metadata:paygw_payu:payu:amount'] = 'El monto del pago.';
$string['privacy:metadata:paygw_payu:payu:currency'] = 'La moneda del pago.';

$string['privacy:metadata:paygw_payu:database'] = 'Información sobre transacciones PayU almacenada localmente.';
$string['privacy:metadata:paygw_payu:database:paymentid'] = 'El ID de pago interno.';
$string['privacy:metadata:paygw_payu:database:payu_order_id'] = 'El identificador de orden PayU.';
$string['privacy:metadata:paygw_payu:database:payu_transaction_id'] = 'El identificador de transacción PayU.';
$string['privacy:metadata:paygw_payu:database:state'] = 'El estado de la transacción.';
$string['privacy:metadata:paygw_payu:database:payment_method'] = 'El método de pago utilizado.';
$string['privacy:metadata:paygw_payu:database:amount'] = 'El monto de la transacción.';
$string['privacy:metadata:paygw_payu:database:currency'] = 'La moneda de la transacción.';
$string['privacy:metadata:paygw_payu:database:timecreated'] = 'Cuándo se creó la transacción.';

// Mensajes para notificaciones.
$string['messagesubject_payment_receipt'] = 'Recibo de Pago - PayU';
$string['messagesubject_payment_pending'] = 'Pago Pendiente - PayU';
$string['messagesubject_payment_error'] = 'Pago Fallido - PayU';
$string['messagesubject_cashreminder'] = 'Recordatorio de Pago en Efectivo - PayU';
$string['messageprovider:payment_receipt'] = 'Recibos de pago';

$string['message_payment_success'] = 'Hola {$a->fullname},

Tu pago de {$a->amount} ha sido procesado exitosamente.

ID de Transacción: {$a->transactionid}
Método de Pago: {$a->paymentmethod}
ID de Pago: {$a->paymentid}
Fecha: {$a->date}

Gracias por tu pago.';

$string['message_payment_pending'] = 'Hola {$a->fullname},

Tu pago de {$a->amount} está pendiente de procesamiento.

ID de Transacción: {$a->transactionid}
Método de Pago: {$a->paymentmethod}
ID de Pago: {$a->paymentid}
Fecha: {$a->date}

Te notificaremos una vez que el pago sea confirmado.';

$string['message_payment_error'] = 'Hola {$a->fullname},

Tu pago de {$a->amount} no pudo ser procesado.

ID de Transacción: {$a->transactionid}
Método de Pago: {$a->paymentmethod}
ID de Pago: {$a->paymentid}
Fecha: {$a->date}

Por favor intenta nuevamente o contacta a soporte.';

$string['message_cash_reminder'] = 'Hola {$a->fullname},

Este es un recordatorio sobre tu pago en efectivo pendiente.

Referencia: {$a->reference}
Monto: {$a->amount}
Fecha de Vencimiento: {$a->expirationdate}

Por favor completa tu pago antes de la fecha de vencimiento.';

// Errores.
$string['gatewaynotconfigured'] = 'El gateway de pago no está configurado correctamente.';
$string['currencynotsupported'] = 'La moneda {$a} no es soportada por PayU Colombia.';
$string['invalidpayment'] = 'Registro de pago inválido.';
$string['invaliduser'] = 'Usuario inválido para este pago.';
$string['invalidpaymentmethod'] = 'Método de pago seleccionado inválido.';
$string['invalidphone'] = 'Formato de número de teléfono inválido.';
$string['invaliddocument'] = 'Número de documento inválido.';
$string['invalidcard'] = 'Información de tarjeta de crédito inválida.';
$string['merchantidinvalid'] = 'El ID del comercio debe ser numérico.';
$string['accountidinvalid'] = 'El ID de cuenta debe ser numérico.';
$string['atleastonemethodrequired'] = 'Al menos un método de pago debe estar habilitado.';
$string['transactionnotfound'] = 'Transacción no encontrada.';
$string['missingparameters'] = 'Faltan parámetros requeridos.';
$string['unknownstate'] = 'Estado de transacción desconocido: {$a}';

// Modo de prueba.
$string['testmodeactive'] = 'Modo de Prueba Activo';
$string['testmodewarning'] = 'Estás usando PayU en modo de prueba. No se procesarán transacciones reales.';

// Capacidades.
$string['paygw/payu:receivepaymentnotifications'] = 'Recibir notificaciones de pago';
$string['paygw/payu:managerefunds'] = 'Gestionar reembolsos';

// Códigos de error para API.
$string['errorconnection'] = 'No se pudo conectar con la API de PayU.';
$string['errorcurlconnection'] = 'Error de red: {$a}';
$string['errorhttpcode'] = 'Código de error HTTP: {$a}';
$string['errorjsonparse'] = 'Respuesta inválida de la API de PayU.';
$string['errortransaction'] = 'Error de transacción: {$a}';
$string['errorgetbanks'] = 'No se pudo obtener la lista de bancos: {$a}';
$string['errorgetmethods'] = 'No se pudo obtener los métodos de pago: {$a}';
$string['errorgetairlines'] = 'No se pudo obtener la lista de aerolíneas: {$a}';
$string['errorrefund'] = 'Error de reembolso: {$a}';
$string['errorquery'] = 'Error de consulta: {$a}';
$string['invalidreference'] = 'Referencia de pago inválida';
$string['invalidformdata'] = 'Datos del formulario inválidos';