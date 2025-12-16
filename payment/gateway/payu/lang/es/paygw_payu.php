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
 * @copyright  2025 Alonso Arias <soporte@ingeweb.co>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['pluginname'] = 'PayU Colombia';
$string['pluginname_desc'] = 'El plugin PayU le permite recibir pagos a través de la plataforma PayU para Colombia.';
$string['gatewayname'] = 'PayU';
$string['gatewaydescription'] = 'PayU es una pasarela de pago para Colombia que permite tarjetas de crédito/débito, PSE, efectivo y otros métodos de pago locales.';

// Configuración.
$string['environment'] = 'Ambiente';
$string['environment_help'] = 'Seleccione el ambiente de PayU. Use Sandbox para pruebas y Producción para transacciones reales.';
$string['environment:sandbox'] = 'Sandbox (Pruebas)';
$string['environment:production'] = 'Producción (En vivo)';

$string['merchantid'] = 'Merchant ID';
$string['merchantid_help'] = 'El ID de comercio de su cuenta de PayU. Se encuentra en el panel de PayU.';
$string['accountid'] = 'Account ID';
$string['accountid_help'] = 'El ID de cuenta de PayU para Colombia. Se encuentra en el panel de PayU.';
$string['apikey'] = 'API Key';
$string['apikey_help'] = 'La llave API de su cuenta de PayU. Se encuentra en la configuración técnica del panel de PayU.';
$string['apilogin'] = 'API Login';
$string['apilogin_help'] = 'El login API de su cuenta de PayU. Se encuentra en la configuración técnica del panel de PayU.';

$string['invalidmerchantid'] = 'El Merchant ID debe ser numérico';
$string['invalidaccountid'] = 'El Account ID debe ser numérico';

$string['language'] = 'Idioma de la página de pago';
$string['language:es'] = 'Español';
$string['language:en'] = 'Inglés';

$string['collectcustomerdata'] = 'Recopilar datos del cliente';
$string['collectcustomerdata_desc'] = 'Pre-llenar información del cliente (email, nombre) en el formulario de pago.';

$string['callback_urls'] = 'URLs de configuración en PayU';
$string['confirmationurl'] = 'URL de confirmación';
$string['responseurl'] = 'URL de respuesta';

$string['sandbox_notice'] = '<strong>Nota:</strong> En ambiente Sandbox se utilizan automáticamente las credenciales de prueba de PayU. No necesita ingresar credenciales.';

// Proceso de pago.
$string['redirecting'] = 'Redirigiendo a PayU...';
$string['redirecting_message'] = 'Está siendo redirigido a la página segura de pago de PayU. Por favor espere...';
$string['reference'] = 'Referencia';
$string['javascript_required'] = 'JavaScript es requerido para el pago automático.';
$string['continue_to_payu'] = 'Continuar a PayU';

// Estados de pago.
$string['paymentsuccessful'] = '¡Pago exitoso! Su inscripción ha sido procesada.';
$string['paymentpending'] = 'Su pago está siendo procesado. Será notificado una vez confirmado.';
$string['paymentdeclined'] = 'Su pago fue rechazado. Por favor intente de nuevo o use otro método de pago.';
$string['paymentexpired'] = 'El pago ha expirado. Por favor intente de nuevo.';
$string['paymenterror'] = 'Ocurrió un error procesando su pago. Por favor intente de nuevo.';
$string['paymentunknownstatus'] = 'Estado de pago desconocido. Por favor contacte a soporte.';
$string['signatureinvalid'] = '(Advertencia: No se pudo verificar la firma)';

// Errores.
$string['error_transaction_create'] = 'Error al crear la transacción en la base de datos.';

// Mensajes de notificación.
$string['messageprovider:payment_successful'] = 'Confirmación de pago exitoso';
$string['messageprovider:payment_failed'] = 'Notificación de pago fallido';
$string['messageprovider:payment_pending'] = 'Notificación de pago pendiente';

$string['payment:successful:subject'] = 'Pago exitoso';
$string['payment:successful:message'] = 'Su pago fue exitoso. Ahora puede acceder a su contenido en: {$a->url}';
$string['payment:failed:subject'] = 'Pago fallido';
$string['payment:failed:message'] = 'Su pago no pudo ser procesado. Por favor intente de nuevo o use otro método de pago.';
$string['payment:pending:subject'] = 'Pago pendiente';
$string['payment:pending:message'] = 'Su pago está siendo procesado. Será notificado una vez sea confirmado.';

// Privacidad.
$string['privacy:metadata:paygw_payu_transactions'] = 'Almacena datos de transacciones para pagos de PayU.';
$string['privacy:metadata:paygw_payu_transactions:userid'] = 'El ID del usuario que realizó el pago.';
$string['privacy:metadata:paygw_payu_transactions:transactionid'] = 'El ID de transacción de PayU.';
$string['privacy:metadata:paygw_payu_transactions:referencecode'] = 'La referencia única del pago.';
$string['privacy:metadata:paygw_payu_transactions:amount'] = 'El monto del pago.';
$string['privacy:metadata:paygw_payu_transactions:state'] = 'El estado de la transacción.';
$string['privacy:metadata:paygw_payu_transactions:timecreated'] = 'La hora en que se creó la transacción.';

$string['privacy:metadata:payu'] = 'Para procesar pagos, algunos datos del usuario son enviados a PayU.';
$string['privacy:metadata:payu:email'] = 'Su dirección de correo electrónico.';
$string['privacy:metadata:payu:fullname'] = 'Su nombre completo.';
