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
 * Strings for component 'paygw_payu', language 'es'
 *
 * @package     paygw_payu
 * @copyright   2025 Alonso Arias <soporte@nexuslabs.com.co>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// Plugin name and description
$string['pluginname'] = 'PayU Latinoamérica';
$string['pluginname_desc'] = 'El plugin PayU le permite recibir pagos a través de la plataforma PayU para países de América Latina.';
$string['gatewayname'] = 'PayU';
$string['gatewaydescription'] = 'PayU es un proveedor de pasarela de pago autorizado para procesar transacciones con tarjeta de crédito en América Latina.';

// Countries
$string['country'] = 'País de operación';
$string['country_ar'] = 'Argentina';
$string['country_br'] = 'Brasil';
$string['country_cl'] = 'Chile';
$string['country_co'] = 'Colombia';
$string['country_mx'] = 'México';
$string['country_pa'] = 'Panamá';
$string['country_pe'] = 'Perú';

// Environment settings
$string['environment'] = 'Entorno';
$string['environment_sandbox'] = 'Sandbox (Pruebas)';
$string['environment_production'] = 'Producción (Pagos reales)';

// Credentials
$string['merchantid'] = 'ID de Comercio';
$string['merchantid_help'] = 'Su ID de Comercio en PayU. Requerido para el entorno de producción.';
$string['accountid'] = 'ID de Cuenta';
$string['accountid_help'] = 'Su ID de Cuenta PayU para el país seleccionado. Requerido para el entorno de producción.';
$string['apikey'] = 'Llave API';
$string['apikey_help'] = 'Su Llave API de PayU. ¡Manténgala segura! Requerida para el entorno de producción.';
$string['apilogin'] = 'API Login';
$string['apilogin_help'] = 'Su API Login de PayU. Requerido para el entorno de producción.';
$string['publickey'] = 'Llave Pública';
$string['publickey_help'] = 'Su Llave Pública de PayU para tokenización (opcional).';

// Language settings
$string['language'] = 'Idioma de la página de pago';
$string['language_es'] = 'Español';
$string['language_en'] = 'Inglés';
$string['language_pt'] = 'Portugués';

// Payment settings
$string['abouttopay'] = 'Está a punto de pagar por';
$string['payment'] = 'Pago';
$string['sendpaymentbutton'] = 'Pagar con PayU';
$string['redirecting'] = 'Redirigiendo a PayU...';
$string['redirecting_message'] = 'Está siendo redirigido a la página segura de pago de PayU. Por favor espere...';

// Status messages
$string['payment_success'] = '¡Pago exitoso!';
$string['payment_error'] = 'Error en el pago';
$string['payment_declined'] = 'El pago fue rechazado';
$string['payment_pending'] = 'El pago está pendiente de aprobación';
$string['payment_expired'] = 'El pago expiró';
$string['payment_unknown'] = 'Estado de pago desconocido';
$string['signature_invalid'] = '(Advertencia: Firma inválida)';

// Test mode
$string['autofilltest'] = 'Auto-completar datos de prueba';
$string['autofilltest_help'] = 'Completa automáticamente los datos de tarjeta de prueba en modo sandbox para facilitar las pruebas.';
$string['sandbox_note'] = '<strong>Nota:</strong> Al usar el entorno Sandbox, se utilizarán automáticamente las credenciales de prueba. No necesita ingresar credenciales de producción.';

// Optional payment modes
$string['skipmode'] = 'Permitir omitir pago';
$string['skipmode_help'] = 'Muestra un botón para omitir el pago. Útil para pagos opcionales en cursos públicos.';
$string['skipmode_text'] = 'Si no puede realizar un pago a través del sistema de pagos, puede hacer clic en este botón.';
$string['skippaymentbutton'] = 'Omitir pago';

$string['passwordmode'] = 'Habilitar contraseña de bypass';
$string['password'] = 'Contraseña de bypass';
$string['password_help'] = 'Los usuarios pueden omitir el pago usando esta contraseña. Útil cuando el sistema de pagos no está disponible.';
$string['password_text'] = 'Si no puede realizar un pago, solicite la contraseña al administrador e ingrésela aquí.';
$string['password_error'] = 'Contraseña de pago inválida';
$string['password_success'] = 'Contraseña de pago aceptada';
$string['password_required'] = 'La contraseña es requerida cuando el modo de contraseña está habilitado';

// Cost settings
$string['fixcost'] = 'Modo de precio fijo';
$string['fixcost_help'] = 'Desactiva la capacidad de los estudiantes para pagar con una cantidad personalizada.';
$string['suggest'] = 'Precio sugerido';
$string['maxcost'] = 'Costo máximo';
$string['maxcosterror'] = 'El precio máximo debe ser mayor que el precio sugerido';
$string['paymore'] = 'Si desea pagar más, simplemente ingrese su cantidad en lugar de la cantidad sugerida.';

// URLs
$string['callback_urls'] = 'URLs de configuración';
$string['confirmation_url'] = 'URL de confirmación';
$string['response_url'] = 'URL de respuesta';

// Errors
$string['error_txdatabase'] = 'Error al escribir la transacción en la base de datos';
$string['error_notvalidtxid'] = 'ID de transacción inválido';
$string['error_notvalidpayment'] = 'Pago inválido';
$string['error_notvalidpaymentid'] = 'ID de pago inválido';
$string['production_fields_required'] = 'Todas las credenciales son requeridas para el entorno de producción';

// Privacy
$string['privacy:metadata'] = 'El plugin PayU almacena datos personales para procesar pagos.';
$string['privacy:metadata:paygw_payu:paygw_payu'] = 'Almacena datos de transacciones de pago';
$string['privacy:metadata:paygw_payu:userid'] = 'ID de usuario';
$string['privacy:metadata:paygw_payu:courseid'] = 'ID del curso';
$string['privacy:metadata:paygw_payu:groupnames'] = 'Nombres de grupos';
$string['privacy:metadata:paygw_payu:country'] = 'País de la transacción';
$string['privacy:metadata:paygw_payu:transactionid'] = 'ID de transacción PayU';
$string['privacy:metadata:paygw_payu:referencecode'] = 'Código de referencia';
$string['privacy:metadata:paygw_payu:amount'] = 'Monto del pago';
$string['privacy:metadata:paygw_payu:currency'] = 'Moneda';
$string['privacy:metadata:paygw_payu:state'] = 'Estado de la transacción';

// Notifications
$string['messagesubject'] = 'Notificación de pago';
$string['messageprovider:payment_receipt'] = 'Recibo de pago';
$string['message_payment_completed'] = 'Hola {$a->firstname},
Su pago de {$a->fee} {$a->currency} (ID: {$a->orderid}) ha sido completado exitosamente.
Si no puede acceder al curso, por favor contacte al administrador.';
$string['message_payment_pending'] = 'Hola {$a->firstname},
Su pago de {$a->fee} {$a->currency} (ID: {$a->orderid}) está pendiente de aprobación.
Le notificaremos una vez que el pago sea confirmado.';