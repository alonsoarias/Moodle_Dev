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
 * @copyright   2024 Alonso Arias <soporte@nexuslabs.com.co>
 * @author      Alonso Arias
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['abouttopay'] = 'Está a punto de pagar por';
$string['accountid'] = 'ID de Cuenta PayU';
$string['apikey'] = 'Clave API';
$string['apilogin'] = 'API Login';
$string['apilogin_help'] = 'API Login para integraciones PayU (opcional para web checkout)';
$string['callback'] = 'URL de confirmación:';
$string['callback_help'] = 'Copie esta URL y configúrela en su cuenta PayU como URL de confirmación.';
$string['cost'] = 'Costo de inscripción';
$string['currency'] = 'Moneda';
$string['donate'] = '<div>Versión del plugin: {$a->release} ({$a->versiondisk})<br>
Para soporte y actualizaciones, contacte a <a href="mailto:soporte@nexuslabs.com.co">NexusLabs</a><br>
Documentación: <a href="https://developers.payulatam.com">Portal de Desarrolladores PayU</a></div>';
$string['error_notvalidpayment'] = 'FALLO. Pago no válido';
$string['error_notvalidtxid'] = 'FALLO. ID de transacción no válido';
$string['error_txdatabase'] = 'Error al escribir datos de transacción en la base de datos';
$string['fixcost'] = 'Modo de precio fijo';
$string['fixcost_help'] = 'Desactiva la capacidad de los estudiantes para pagar con un monto arbitrario.';
$string['fixdesc'] = 'Descripción fija del pago';
$string['fixdesc_help'] = 'Esta configuración establece una descripción fija para todos los pagos.';
$string['gatewaydescription'] = 'PayU es un proveedor autorizado de pasarela de pago para procesar transacciones con tarjeta de crédito en América Latina.';
$string['gatewayname'] = 'PayU';
$string['internalerror'] = 'Ha ocurrido un error interno. Por favor contáctenos.';
$string['maxcost'] = 'Precio máximo';
$string['maxcosterror'] = 'El precio máximo debe ser mayor que el precio recomendado';
$string['merchantid'] = 'ID del Comercio';
$string['message'] = 'Mensaje';
$string['message_invoice_created'] = '¡Hola {$a->firstname}!
Su enlace de pago {$a->orderid} por {$a->fee} {$a->currency} ha sido creado exitosamente.
Puede pagarlo dentro de una hora.';
$string['message_success_completed'] = 'Hola {$a->firstname},
Su transacción con ID de pago {$a->orderid} por un valor de {$a->fee} {$a->currency} se completó exitosamente.
Si el elemento no es accesible, por favor contacte al administrador.';
$string['messageprovider:payment_receipt'] = 'Recibo de pago';
$string['messagesubject'] = 'Notificación de pago';
$string['password'] = 'Contraseña de respaldo';
$string['password_error'] = 'Contraseña de pago inválida';
$string['password_help'] = 'Usando esta contraseña puede omitir el proceso de pago. Puede ser útil cuando no es posible realizar un pago.';
$string['password_success'] = 'Contraseña de pago aceptada';
$string['password_text'] = 'Si no puede realizar un pago, solicite una contraseña a su administrador e ingrésela.';
$string['passwordmode'] = 'Permitir entrada de contraseña de respaldo';
$string['payment'] = 'Pago';
$string['payment_error'] = 'Error en el pago';
$string['payment_success'] = 'Pago exitoso';
$string['paymentdeclined'] = 'Su pago fue rechazado por el procesador de pagos.';
$string['paymentexpired'] = 'Pago expirado';
$string['paymentexpireddesc'] = 'Su sesión de pago ha expirado. Por favor intente nuevamente.';
$string['paymentpending'] = 'Pago pendiente';
$string['paymentpendingdesc'] = 'Su pago está siendo procesado. Recibirá una confirmación una vez que se complete.';
$string['paymentresponse'] = 'Respuesta del pago';
$string['paymentserver'] = 'URL del servidor de pagos';
$string['paymentsuccessful'] = 'Su pago ha sido procesado exitosamente.';
$string['paymore'] = 'Si desea pagar más, simplemente ingrese su monto en lugar del monto indicado.';
$string['pluginname'] = 'Pagos PayU';
$string['pluginname_desc'] = 'El plugin PayU le permite recibir pagos a través de PayU para América Latina.';
$string['privacy:metadata'] = 'El plugin PayU almacena algunos datos personales.';
$string['privacy:metadata:paygw_payu:courseid'] = 'ID del curso';
$string['privacy:metadata:paygw_payu:email'] = 'Correo electrónico';
$string['privacy:metadata:paygw_payu:groupnames'] = 'Nombres de grupos';
$string['privacy:metadata:paygw_payu:paygw_payu'] = 'Almacena algunos datos';
$string['privacy:metadata:paygw_payu:payu_latam'] = 'Enviar datos de pago a PayU';
$string['privacy:metadata:paygw_payu:success'] = 'Estado';
$string['publickey'] = 'Clave Pública';
$string['publickey_help'] = 'Clave pública para tokenización (opcional para web checkout)';
$string['referencecode'] = 'Código de referencia';
$string['sendpaymentbutton'] = 'Pagar con PayU';
$string['showduration'] = 'Mostrar duración del entrenamiento';
$string['skipmode'] = 'Permitir omitir pago';
$string['skipmode_help'] = 'Esta configuración permite un botón para omitir el pago, útil en cursos públicos con pago opcional.';
$string['skipmode_text'] = 'Si no puede realizar un pago a través del sistema de pagos, puede hacer clic en este botón.';
$string['skippaymentbutton'] = 'Omitir pago :(';
$string['suggest'] = 'Precio recomendado';
$string['testcredential_auto'] = 'Credencial de prueba (auto-completada)';
$string['testmode'] = 'Modo de prueba';
$string['testmode_active'] = 'Modo de Prueba Activo';
$string['testmode_description'] = 'Usando credenciales sandbox de PayU Colombia. Las transacciones no serán reales.';
$string['testmode_help'] = 'Habilitar modo de prueba para usar credenciales sandbox de PayU automáticamente';
$string['transactionid'] = 'ID de transacción';
$string['uninterrupted_desc'] = 'El precio del curso se forma teniendo en cuenta el tiempo perdido del período que no ha pagado.';
$string['unknownstate'] = 'Estado de transacción desconocido';
$string['usedetails'] = 'Hacer plegable';
$string['usedetails_help'] = 'Mostrar un botón o contraseña en un bloque plegable.';
$string['usedetails_text'] = 'Haga clic aquí si no puede pagar.';
$string['validationerror'] = 'Error de validación';