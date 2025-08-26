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
 * Strings for component 'paygw_payu', language 'es'.
 *
 * @package    paygw_payu
 * @copyright  2024 Orion Cloud Consulting SAS
 * @author     Alonso Arias <soporte@orioncloud.com.co>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['pluginname'] = 'PayU Colombia';
$string['pluginname_desc'] = 'La pasarela de pagos PayU Colombia permite pagos en línea a través de varios métodos incluyendo tarjetas de crédito, transferencias bancarias PSE, Nequi y pagos en efectivo.';
$string['gatewayname'] = 'PayU Colombia';
$string['gatewaydescription'] = 'PayU es un proveedor líder de servicios de pago en América Latina, procesando pagos seguros en línea en Colombia.';

// Configuración.
$string['merchantid'] = 'ID de Comercio';
$string['merchantid_help'] = 'Tu número identificador de comercio en PayU.';
$string['accountid'] = 'ID de Cuenta';
$string['accountid_help'] = 'Tu identificador de cuenta PayU para Colombia.';
$string['apikey'] = 'Llave API';
$string['apikey_help'] = 'Tu llave API secreta proporcionada por PayU. ¡Mantenla segura!';
$string['apilogin'] = 'Login API';
$string['apilogin_help'] = 'Tu credencial de login para los servicios API de PayU.';
$string['testmode'] = 'Modo de prueba';
$string['testmode_help'] = 'Habilita el modo de prueba para usar el ambiente sandbox de PayU.';

// Configuración de métodos de pago.
$string['paymentmethods'] = 'Configuración de Métodos de Pago';
$string['enabledmethods'] = 'Métodos de pago habilitados';
$string['enabledmethods_help'] = 'Selecciona qué métodos de pago estarán disponibles para los usuarios. Al menos un método debe estar habilitado.';

// Configuración de caché.
$string['cachesettings'] = 'Configuración de Caché';
$string['enablecache'] = 'Habilitar caché';
$string['enablecache_help'] = 'Habilita el caché de listas de bancos PSE y otros datos para mejorar el rendimiento.';

// Configuración de notificaciones.
$string['notificationsettings'] = 'Configuración de Notificaciones';
$string['enablenotifications'] = 'Habilitar notificaciones por correo';
$string['enablenotifications_help'] = 'Envía notificaciones por correo a los usuarios sobre el estado de sus pagos.';

// URL de callback.
$string['callbackurl'] = 'URL de Confirmación';
$string['callbackurl_help'] = 'Configura esta URL en tu cuenta PayU para confirmaciones de pago. Copia esta URL en el campo URL de confirmación en tu panel de comercio PayU.';

// Métodos de pago.
$string['paymentmethod'] = 'Método de pago';
$string['creditcard'] = 'Tarjeta de crédito/débito';
$string['pse'] = 'PSE - Transferencia bancaria';
$string['nequi'] = 'Nequi';
$string['bancolombia'] = 'Botón Bancolombia';
$string['googlepay'] = 'Google Pay';
$string['cash'] = 'Pago en efectivo';

// Campos del formulario.
$string['cardholder'] = 'Nombre del titular';
$string['cardnumber'] = 'Número de tarjeta';
$string['expmonth'] = 'Mes de vencimiento';
$string['expyear'] = 'Año de vencimiento';
$string['cvv'] = 'Código de seguridad (CVV)';
$string['cardnetwork'] = 'Tipo de tarjeta';
$string['installments'] = 'Cuotas';
$string['documenttype'] = 'Tipo de documento';
$string['documentnumber'] = 'Número de documento';
$string['phone'] = 'Número de teléfono';
$string['email'] = 'Correo electrónico';
$string['psebank'] = 'Selecciona tu banco';
$string['usertype'] = 'Tipo de persona';
$string['personnatural'] = 'Persona natural';
$string['personjuridica'] = 'Persona jurídica';
$string['cashmethod'] = 'Lugar de pago en efectivo';
$string['efecty'] = 'Efecty';
$string['otherscash'] = 'Su Red (Otros)';
$string['bankreferenced'] = 'Referencia bancaria';
$string['googlepaytoken'] = 'Token de Google Pay';

// Campos de dirección.
$string['street1'] = 'Dirección línea 1';
$string['street2'] = 'Dirección línea 2';
$string['city'] = 'Ciudad';
$string['state'] = 'Departamento';
$string['postalcode'] = 'Código postal';

// Botones y acciones.
$string['submitpayment'] = 'Procesar pago';
$string['processingpayment'] = 'Procesando tu pago...';
$string['continuetopayment'] = 'Continuar al pago';
$string['returntocourse'] = 'Volver al curso';
$string['viewreceipt'] = 'Ver recibo';
$string['viewpayment'] = 'Ver detalles del pago';

// Mensajes y notificaciones.
$string['messagesubject_payment_receipt'] = 'Recibo de Pago - PayU';
$string['messagesubject_payment_pending'] = 'Pago Pendiente - PayU';
$string['messagesubject_payment_error'] = 'Error en el Pago - PayU';
$string['messagesubject_cashreminder'] = 'Recordatorio de Pago en Efectivo - PayU';

$string['message_payment_success'] = 'Estimado/a {$a->fullname},

Tu pago de {$a->amount} ha sido procesado exitosamente.

ID de Pago: {$a->paymentid}
ID de Transacción: {$a->transactionid}
Método de Pago: {$a->paymentmethod}
Fecha: {$a->date}

Gracias por tu pago.';

$string['message_payment_pending'] = 'Estimado/a {$a->fullname},

Tu pago de {$a->amount} está actualmente pendiente.

ID de Pago: {$a->paymentid}
Estado: {$a->state}
Fecha: {$a->date}

Te notificaremos una vez que el pago sea confirmado.';

$string['message_payment_error'] = 'Estimado/a {$a->fullname},

Hubo un error procesando tu pago de {$a->amount}.

ID de Pago: {$a->paymentid}
Estado: {$a->state}
Fecha: {$a->date}

Por favor intenta nuevamente o contacta a soporte si el problema persiste.';

$string['message_cash_reminder'] = 'Estimado/a {$a->fullname},

Este es un recordatorio sobre tu pago en efectivo pendiente.

Monto: {$a->amount}
Referencia: {$a->reference}
Fecha de vencimiento: {$a->expirationdate}

Por favor completa tu pago en cualquier punto autorizado. Puedes ver e imprimir tu recibo de pago aquí:
{$a->receipturl}';

// Errores.
$string['merchantidinvalid'] = 'El ID de Comercio debe ser numérico.';
$string['accountidinvalid'] = 'El ID de Cuenta debe ser numérico.';
$string['atleastonemethodrequired'] = 'Al menos un método de pago debe estar habilitado.';
$string['errorgetbanks'] = 'Error obteniendo lista de bancos: {$a}';
$string['errorgetmethods'] = 'Error obteniendo métodos de pago: {$a}';
$string['errortransaction'] = 'Error en transacción: {$a}';
$string['errorquerytransaction'] = 'Error consultando transacción: {$a}';
$string['errorcurlconnection'] = 'Error de conexión: {$a}';
$string['errorhttpcode'] = 'Código de error HTTP: {$a}';
$string['errorjsonparse'] = 'Error procesando respuesta de PayU.';
$string['paymenterror'] = 'No se pudo procesar el pago. Por favor intenta nuevamente.';
$string['paymentpending'] = 'Tu pago está siendo procesado. Recibirás una confirmación pronto.';
$string['invalidphone'] = 'Formato de teléfono inválido. Debe tener 10 dígitos.';
$string['invalidsignature'] = 'Firma de pago inválida.';
$string['invalidmerchant'] = 'Configuración de comercio inválida.';
$string['invalidreference'] = 'Referencia de pago inválida.';

// Privacidad.
$string['privacy:metadata:paygw_payu:payu'] = 'Información enviada a PayU para procesar el pago.';
$string['privacy:metadata:paygw_payu:payu:fullname'] = 'El nombre completo del usuario que realiza el pago.';
$string['privacy:metadata:paygw_payu:payu:email'] = 'La dirección de correo electrónico del usuario.';
$string['privacy:metadata:paygw_payu:payu:phone'] = 'El número de teléfono proporcionado para el pago.';
$string['privacy:metadata:paygw_payu:payu:documentnumber'] = 'El número de documento de identificación.';
$string['privacy:metadata:paygw_payu:payu:address'] = 'La dirección de facturación o envío.';
$string['privacy:metadata:paygw_payu:payu:creditcard'] = 'Información de tarjeta de crédito (transmitida de forma segura a PayU).';
$string['privacy:metadata:paygw_payu:payu:amount'] = 'El monto del pago.';
$string['privacy:metadata:paygw_payu:payu:currency'] = 'La moneda del pago.';

$string['privacy:metadata:paygw_payu:database'] = 'Registros de transacciones almacenados localmente.';
$string['privacy:metadata:paygw_payu:database:paymentid'] = 'El ID de pago interno.';
$string['privacy:metadata:paygw_payu:database:payu_order_id'] = 'El identificador de orden de PayU.';
$string['privacy:metadata:paygw_payu:database:payu_transaction_id'] = 'El identificador de transacción de PayU.';
$string['privacy:metadata:paygw_payu:database:state'] = 'El estado de la transacción.';
$string['privacy:metadata:paygw_payu:database:payment_method'] = 'El método de pago utilizado.';
$string['privacy:metadata:paygw_payu:database:amount'] = 'El monto de la transacción.';
$string['privacy:metadata:paygw_payu:database:currency'] = 'La moneda de la transacción.';
$string['privacy:metadata:paygw_payu:database:timecreated'] = 'Cuando se creó la transacción.';

// Instrucciones de pago.
$string['instruction_pse'] = 'Serás redirigido al sitio web seguro de tu banco para completar el pago.';
$string['instruction_nequi'] = 'Recibirás una notificación push en tu app de Nequi para autorizar el pago.';
$string['instruction_cash'] = 'Imprime o guarda el recibo de pago y paga en cualquier punto autorizado.';
$string['instruction_bancolombia'] = 'Serás redirigido a Bancolombia para completar el pago.';