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
 * Strings for component 'paygw_wompi', language 'es'.
 *
 * @package    paygw_wompi
 * @copyright  2024 Alonso Arias <soporte@ingeweb.co>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['pluginname'] = 'Wompi';
$string['pluginname_desc'] = 'El plugin Wompi le permite recibir pagos a través de la pasarela de pago Wompi (Colombia).';
$string['gatewaydescription'] = 'Wompi es una pasarela de pago para Colombia que permite tarjetas de crédito, PSE, Nequi y otros métodos de pago locales.';
$string['gatewayname'] = 'Wompi';

// Configuración.
$string['environment'] = 'Ambiente';
$string['environment_help'] = 'Seleccione el ambiente de Wompi. Use Sandbox para pruebas y Producción para transacciones reales.';
$string['environment:sandbox'] = 'Sandbox (Pruebas)';
$string['environment:production'] = 'Producción (En vivo)';

$string['publickey'] = 'Llave Pública';
$string['publickey_help'] = 'La llave pública de su panel de Wompi. Comienza con pub_test_ para sandbox o pub_prod_ para producción.';
$string['privatekey'] = 'Llave Privada';
$string['privatekey_help'] = 'La llave privada de su panel de Wompi. Comienza con prv_test_ para sandbox o prv_prod_ para producción.';
$string['eventskey'] = 'Llave Privada de Eventos';
$string['eventskey_help'] = 'La llave privada de eventos para verificación de firma de webhooks. Se encuentra en su panel de Wompi en la configuración de Eventos.';
$string['integritykey'] = 'Llave de Integridad';
$string['integritykey_help'] = 'La llave de integridad usada para generar firmas de transacciones. Se encuentra en su panel de Wompi.';

$string['invalidpublickey:sandbox'] = 'Llave pública inválida para ambiente sandbox. Debe comenzar con pub_test_';
$string['invalidpublickey:production'] = 'Llave pública inválida para ambiente de producción. Debe comenzar con pub_prod_';
$string['invalidprivatekey:sandbox'] = 'Llave privada inválida para ambiente sandbox. Debe comenzar con prv_test_';
$string['invalidprivatekey:production'] = 'Llave privada inválida para ambiente de producción. Debe comenzar con prv_prod_';

$string['paymentmethods'] = 'Métodos de Pago';
$string['paymentmethods_help'] = 'Seleccione los métodos de pago que desea ofrecer a sus clientes.';

$string['paymentmethod:card'] = 'Tarjeta de Crédito/Débito';
$string['paymentmethod:nequi'] = 'Nequi';
$string['paymentmethod:pse'] = 'PSE (Transferencia Bancaria)';
$string['paymentmethod:bancolombia_transfer'] = 'Transferencia Bancolombia';
$string['paymentmethod:bancolombia_collect'] = 'Corresponsal Bancolombia (Efectivo)';
$string['paymentmethod:daviplata'] = 'Daviplata';
$string['paymentmethod:pcol'] = 'Puntos Colombia';

$string['collectcustomerdata'] = 'Recopilar datos del cliente';
$string['collectcustomerdata_desc'] = 'Pre-llenar información del cliente (email, nombre) en el formulario de pago.';

// Proceso de pago.
$string['redirecting'] = 'Redirigiendo al pago...';
$string['clicktopay'] = 'Haga clic en el botón de abajo para proceder con el pago';
$string['paynow'] = 'Pagar Ahora';
$string['reference'] = 'Referencia';

$string['paymentsuccessful'] = 'Pago exitoso';
$string['paymentpending'] = 'Su pago está siendo procesado. Será notificado una vez confirmado.';
$string['paymentfailed'] = 'Pago fallido: {$a}';
$string['paymentdeclined'] = 'Su pago fue rechazado. Por favor intente de nuevo o use otro método de pago.';
$string['paymentcancelled'] = 'Pago cancelado';
$string['paymentnotcompleted'] = 'El pago no fue completado. Por favor intente de nuevo.';
$string['paymentunknownstatus'] = 'Estado de pago desconocido: {$a}. Por favor contacte a soporte.';

$string['transactionnotfound'] = 'Transacción no encontrada. Por favor contacte a soporte si se le realizó un cobro.';
$string['alreadydelivered'] = 'Este pago ya fue procesado y entregado.';
$string['deliveryerror'] = 'Ocurrió un error al procesar su inscripción. Por favor contacte a soporte.';

// Mensajes.
$string['messageprovider:payment_failed'] = 'Notificación de pago fallido';
$string['messageprovider:payment_successful'] = 'Confirmación de pago exitoso';
$string['messageprovider:payment_pending'] = 'Notificación de pago pendiente';

$string['payment:successful:subject'] = 'Pago exitoso';
$string['payment:successful:message'] = 'Su pago fue exitoso. Ahora puede acceder a su contenido en: {$a->url}';
$string['payment:failed:subject'] = 'Pago fallido';
$string['payment:failed:message'] = 'Su pago no pudo ser procesado. Por favor intente de nuevo o use otro método de pago.';
$string['payment:pending:subject'] = 'Pago pendiente';
$string['payment:pending:message'] = 'Su pago está siendo procesado. Será notificado una vez sea confirmado.';

// Privacidad.
$string['privacy:metadata:paygw_wompi_transactions'] = 'Almacena datos de transacciones para pagos de Wompi.';
$string['privacy:metadata:paygw_wompi_transactions:userid'] = 'El ID del usuario que realizó el pago.';
$string['privacy:metadata:paygw_wompi_transactions:transactionid'] = 'El ID de transacción de Wompi.';
$string['privacy:metadata:paygw_wompi_transactions:reference'] = 'La referencia única del pago.';
$string['privacy:metadata:paygw_wompi_transactions:amount'] = 'El monto del pago en centavos.';
$string['privacy:metadata:paygw_wompi_transactions:status'] = 'El estado de la transacción.';
$string['privacy:metadata:paygw_wompi_transactions:timecreated'] = 'La hora en que se creó la transacción.';

$string['privacy:metadata:wompi'] = 'Para procesar pagos, algunos datos del usuario son enviados a Wompi.';
$string['privacy:metadata:wompi:email'] = 'Su dirección de correo electrónico.';
$string['privacy:metadata:wompi:fullname'] = 'Su nombre completo.';
