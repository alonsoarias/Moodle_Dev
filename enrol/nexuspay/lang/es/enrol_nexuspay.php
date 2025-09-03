<?php
// This file is part of Moodle - https://moodle.org/
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
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * Strings for component 'enrol_nexuspay', language 'es'.
 *
 * @package     enrol_nexuspay
 * @category    string
 * @copyright 2024 Alonso Arias <soporte@nexuslabs.com.co>
 * @author    Alonso Arias
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['assignrole'] = 'Asignar rol';
$string['cost'] = 'Costo de inscripción';
$string['costerror'] = 'El costo debe ser un número mayor que cero con máximo dos decimales.';
$string['currency'] = 'Moneda';
$string['defaultgroup'] = 'Grupo predeterminado';
$string['defaultrole'] = 'Rol predeterminado';
$string['defaultrole_desc'] = 'Seleccione el rol que se asignará a los usuarios después de realizar el pago';
$string['donate'] = '<div>Versión del plugin: {$a->release} ({$a->versiondisk})<br>
Puede encontrar nuevas versiones del plugin en <a href=https://github.com/NexusLabs/moodle-enrol_nexuspay>GitHub.com</a>
<img src="https://img.shields.io/github/v/release/NexusLabs/moodle-enrol_nexuspay.svg"><br>
Para soporte, contacte a <a href="mailto:soporte@nexuslabs.com.co">NexusLabs</a></div>';
$string['editselectedusers'] = 'Editar inscripciones de usuarios seleccionados';
$string['enrolenddate'] = 'Fecha de finalización';
$string['enrolenddate_help'] = 'Si está habilitado, los usuarios solo pueden inscribirse hasta esta fecha.';
$string['enrolenddaterror'] = 'La fecha de finalización de inscripción no puede ser anterior a la fecha de inicio';
$string['enrolperiod'] = 'Duración del entrenamiento ({$a->desc}): {$a->count}';
$string['enrolperiod_desc'] = 'Duración predeterminada del entrenamiento. Si se establece en cero, la duración del entrenamiento será ilimitada de forma predeterminada.';
$string['enrolperiod_help'] = 'Duración del entrenamiento, a partir del momento en que el usuario se inscribe en el curso. Si no se habilita este parámetro, la duración del entrenamiento será ilimitada.';
$string['enrolperiodend'] = 'Renovación hasta {$a->date} {$a->time}';
$string['enrolstartdate'] = 'Fecha de inicio';
$string['enrolstartdate_help'] = 'Si está habilitado, los usuarios solo pueden inscribirse a partir de esta fecha.';
$string['expiredaction'] = 'Acción de expiración de inscripción';
$string['expiredaction_help'] = 'Seleccione la acción a realizar cuando expire la inscripción del usuario. Tenga en cuenta que algunos datos y configuraciones del usuario se eliminan del curso durante la cancelación de la inscripción.';
$string['expirynotify'] = 'Notificar antes de que expire la inscripción';
$string['expirynotify_help'] = 'Esta configuración determina si se envían mensajes de notificación de expiración de inscripción.';
$string['expirythreshold'] = 'Umbral de notificación';
$string['expirythreshold_help'] = '¿Cuánto tiempo antes de que expire la inscripción se debe notificar a los usuarios?';
$string['defaultenrolperiod'] = 'Duración predeterminada de la inscripción';
$string['defaultenrolperiod_desc'] = 'Duración predeterminada del período de inscripción válido. Si se establece en cero, la duración de la inscripción será ilimitada de forma predeterminada.';
$string['forcepayment'] = 'Forzar pago';
$string['forcepayment_desc'] = 'Forzar la apertura de la página de pago cuando se accede al curso';
$string['forcepayment_help'] = 'Forzar la apertura de la página de pago cuando se accede al curso';
$string['freetrial'] = 'Periodo de prueba gratuito';
$string['freetrial_desc'] = 'Primer mes gratis en cursos con duración establecida para nuevas inscripciones.';
$string['groupkey'] = 'Usar claves de inscripción de grupo';
$string['groupkey_desc'] = 'Usar claves de inscripción de grupo de forma predeterminada.';
$string['groupkeytext'] = 'Si tiene una clave de grupo, ingrésela aquí';
$string['groupkeytextforce'] = 'Si tiene una clave de grupo, ingrésela aquí. De lo contrario se requerirá pago';
$string['groupsuccess'] = 'Clave de grupo aceptada';
$string['managemanualenrolements'] = 'Administrar inscripciones NexusPay';
$string['menuname'] = 'Pago NexusPay';
$string['menunameshort'] = 'NexusPay';
$string['newenrols'] = 'Permitir nuevas inscripciones sin pago';
$string['newenrols_desc'] = 'Permitir a los usuarios inscribirse en el curso sin pago de forma predeterminada si nunca se han inscrito antes.';
$string['newenrolswithoutpayment'] = 'La inscripción al curso está disponible sin pago para quienes se inscriben por primera vez';
$string['nocost'] = '¡No hay costo asociado con la inscripción en este curso!';
$string['notenrollable'] = 'No puede inscribirse en este curso.';
$string['notrequired'] = 'No requerido';
$string['paymentrequired'] = 'Se requiere un pago para participar en este curso.';
$string['pluginname'] = 'Inscripción NexusPay';
$string['pluginname_desc'] = 'El método de inscripción NexusPay le permite configurar cursos que requieren un pago. Hay una tarifa para todo el sitio que se establece como predeterminada para todo el sitio y luego una configuración de curso que puede establecer para cada curso individualmente. La tarifa del curso anula la tarifa del sitio.';
$string['privacy:metadata'] = 'El plugin no almacena ningún dato personal.';
$string['renewenrolment'] = 'Renovar suscripción paga';
$string['renewenrolment_text'] = 'Costo de renovación';
$string['role'] = 'Rol asignado por defecto';
$string['sendexpirynotificationstask'] = 'Tarea de envío de notificaciones de expiración de inscripción NexusPay';
$string['sendpaymentbutton'] = 'Seleccionar método de pago';
$string['showduration'] = 'Mostrar duración del entrenamiento en la página';
$string['status'] = 'Permitir inscripciones NexusPay';
$string['status_desc'] = 'Permitir a los usuarios usar NexusPay para inscribirse en un curso de forma predeterminada.';
$string['syncenrolmentstask'] = 'Sincronizar inscripciones NexusPay';
$string['thisyear'] = 'Este año';
$string['uninterrupted'] = 'Pagar tiempo perdido';
$string['uninterrupted_desc'] = 'El precio del curso se forma teniendo en cuenta el tiempo perdido del período que no ha pagado ({$a}).';
$string['uninterrupted_help'] = 'Al precio del curso se agrega el costo del tiempo de interrupción desde el último pago. Solo funciona en cursos con duración de estudio establecida.';
$string['uninterrupted_warn'] = '<font color=red>¡Solo funciona con las pasarelas de pago PayU, bePaid, Robokassa, YooKassa, PayAnyWay!</font>';
$string['validationerror'] = 'Las inscripciones no se pueden habilitar sin especificar la cuenta de pago';