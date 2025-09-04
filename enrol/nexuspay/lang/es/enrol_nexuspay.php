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
 * Strings for component 'enrol_nexuspay', language 'es'.
 *
 * @package    enrol_nexuspay
 * @copyright  2025 NexusPay Development Team
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// Nombre y descripción del plugin.
$string['pluginname'] = 'Matriculación NexusPay';
$string['pluginname_desc'] = 'El método de matriculación NexusPay le permite configurar cursos de pago con opciones de pago flexibles. Soporta múltiples monedas (USD, COP) y varios períodos de matriculación.';

// Configuración general.
$string['assignrole'] = 'Asignar rol';
$string['defaultrole'] = 'Asignación de rol por defecto';
$string['defaultrole_desc'] = 'Seleccione el rol que se asignará a los usuarios después de realizar el pago.';
$string['status'] = 'Permitir matriculaciones NexusPay';
$string['status_desc'] = 'Permitir que los usuarios realicen pagos para matricularse en cursos de forma predeterminada.';

// Configuración de costo y moneda.
$string['cost'] = 'Costo de matriculación';
$string['costerror'] = 'El costo de matriculación debe ser un número mayor que cero con hasta dos decimales.';
$string['currency'] = 'Moneda';
$string['nocost'] = '¡No hay costo para matricularse en este curso!';

// Configuración de grupos.
$string['defaultgroup'] = 'Grupo por defecto';
$string['groupkey'] = 'Usar claves de matriculación de grupo';
$string['groupkey_desc'] = 'Usar claves de matriculación de grupo de forma predeterminada.';
$string['groupkeytext'] = 'Haga clic aquí para ingresar la contraseña del grupo si es necesario.';
$string['groupkeytextforce'] = 'Se requiere una contraseña de grupo para matricularse en este curso.';
$string['groupsuccess'] = 'Contraseña de grupo aceptada exitosamente';

// Configuración del período de matriculación.
$string['enrolperiod'] = 'Duración de la matriculación';
$string['enrolperiod_desc'] = 'Duración predeterminada del tiempo que la matriculación es válida. Si se establece en cero, la duración será ilimitada por defecto.';
$string['enrolperiod_help'] = 'Tiempo que la matriculación es válida, comenzando desde el momento en que el usuario se matricula. Si está deshabilitado, la duración será ilimitada.';
$string['enrolstartdate'] = 'Fecha de inicio';
$string['enrolstartdate_help'] = 'Si está habilitado, los usuarios solo pueden matricularse a partir de esta fecha.';
$string['enrolenddate'] = 'Fecha de fin';
$string['enrolenddate_help'] = 'Si está habilitado, los usuarios pueden matricularse solo hasta esta fecha.';
$string['enrolenddaterror'] = 'La fecha de fin de matriculación no puede ser anterior a la fecha de inicio.';
$string['enrolperiodend'] = 'La suscripción se extenderá hasta {$a->date} {$a->time}';

// Configuración del período de prueba.
$string['freetrial'] = 'Período de prueba gratuito';
$string['freetrial_desc'] = 'Período de prueba disponible ({$a->count} {$a->desc})';
$string['freetrial_help'] = 'Permite a los usuarios acceder al curso una vez por un período específico sin pago.';
$string['freetrialbutton'] = 'Iniciar prueba gratuita';

// Configuración de pagos.
$string['paymentaccount'] = 'Cuenta de pago';
$string['paymentaccount_help'] = 'Los costos de matriculación se pagarán a esta cuenta.';
$string['paymentrequired'] = 'Este curso requiere un pago para ingresar.';
$string['purchasedescription'] = 'Matriculación en el curso {$a}';
$string['sendpaymentbutton'] = 'Realizar pago';

// Configuración de nuevas matriculaciones.
$string['newenrols'] = 'Permitir nuevas matriculaciones';
$string['newenrols_desc'] = 'Permitir que los usuarios se automatriculen en cursos nuevos de forma predeterminada.';
$string['newenrols_help'] = 'Esta configuración determina si los usuarios nuevos pueden matricularse en este curso, o solo los usuarios matriculados pueden renovar su matriculación.';

// Notificaciones de vencimiento.
$string['expirynotify'] = 'Notificar antes de que venza la matriculación';
$string['expirynotify_help'] = 'Esta configuración determina si se envían mensajes de notificación de vencimiento de matriculación.';
$string['expirynotifyall'] = 'Matriculador y usuario matriculado';
$string['expirynotifyenroller'] = 'Solo matriculador';
$string['expirythreshold'] = 'Umbral de notificación';
$string['expirythreshold_help'] = '¿Cuánto tiempo antes de que venza la matriculación se debe notificar a los usuarios?';
$string['expirynotifyhour'] = 'Hora para enviar notificaciones de vencimiento';

// Mensajes.
$string['expiredaction'] = 'Acción al vencer la matriculación';
$string['expiredaction_help'] = 'Seleccione la acción a realizar cuando vence la matriculación del usuario. Tenga en cuenta que algunos datos y configuraciones del usuario se eliminan del curso durante la desmatriculación.';
$string['expiredmessagebody'] = 'Estimado/a {$a->fullname},

Esta es una notificación de que su matriculación en el curso \'{$a->course}\' ha sido suspendida.

Para renovar su matriculación, por favor visite: {$a->payurl}

Si necesita ayuda, contacte al administrador del curso.';
$string['expiredmessagesubject'] = 'Notificación de vencimiento de matriculación';

$string['expirymessageenrolledbody'] = 'Estimado/a {$a->user},

Esta es una notificación de que su matriculación en el curso \'{$a->course}\' vencerá el {$a->timeend}.

Si necesita ayuda, por favor contacte a {$a->enroller}.';
$string['expirymessageenrolledsubject'] = 'Notificación de vencimiento de matriculación NexusPay';

$string['expirymessageenrollerbody'] = 'La matriculación NexusPay en el curso \'{$a->course}\' vencerá dentro de {$a->threshold} para los siguientes usuarios:

{$a->users}

Para extender su matriculación, vaya a {$a->extendurl}';
$string['expirymessageenrollersubject'] = 'Notificación de vencimiento de matriculación NexusPay';

// Pago ininterrumpido.
$string['uninterrupted'] = 'Pagar por tiempo perdido';
$string['uninterrupted_desc'] = 'El precio del curso incluye el costo de los períodos no pagados ({$a}).';
$string['uninterrupted_help'] = 'El costo del curso incluye el costo del tiempo perdido desde el último pago. Solo funciona en cursos con duración establecida.';

// Forzar pago.
$string['forcepayment'] = 'Ignorar fechas de matriculación para pago';
$string['forcepayment_help'] = 'Si está activado, el formulario de pago estará disponible independientemente de las fechas de inicio o fin de matriculación. Por ejemplo, cuando la matriculación está cerrada, los estudiantes previamente matriculados pueden continuar pagando.';

// Configuración adicional.
$string['showduration'] = 'Mostrar duración de matriculación en la página';
$string['renewenrolment'] = 'Renovar suscripción';
$string['renewenrolment_text'] = 'Costo de renovación';
$string['enrolperiod_duration'] = 'Duración ({$a->desc}): {$a->count}';
$string['thisyear'] = 'Este año';
$string['extremovedsuspendnoroles'] = 'Suspender matriculación del curso y eliminar roles';

// Interfaz de gestión.
$string['manageenrolements'] = 'Gestionar matriculaciones NexusPay';
$string['editselectedusers'] = 'Editar matriculaciones de usuarios seleccionados';
$string['menuname'] = 'Opciones de pago';
$string['menunameshort'] = 'Pagar';

// Tareas.
$string['sendexpirynotificationstask'] = 'Tarea de envío de notificaciones de vencimiento NexusPay';
$string['syncenrolmentstask'] = 'Tarea de sincronización de matriculaciones NexusPay';
$string['expirynotifyperiod'] = 'Intervalo de notificación de vencimiento';
$string['expirynotifyperiod_desc'] = 'Con qué frecuencia enviar notificaciones sobre el vencimiento de matriculación. Este valor debe coincidir con la frecuencia de la tarea programada.';

// Privacidad.
$string['privacy:metadata'] = 'El plugin de matriculación NexusPay no almacena ningún dato personal.';

// Capacidades.
$string['nexuspay:config'] = 'Configurar instancias de matriculación NexusPay';
$string['nexuspay:enrol'] = 'Matricular usuarios';
$string['nexuspay:manage'] = 'Gestionar usuarios matriculados';
$string['nexuspay:unenrol'] = 'Desmatricular usuarios del curso';
$string['nexuspay:unenrolself'] = 'Desmatricularse del curso';

// Errores de validación.
$string['validationerror'] = 'Las matriculaciones no pueden habilitarse sin especificar una cuenta de pago';