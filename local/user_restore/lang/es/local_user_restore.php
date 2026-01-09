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
 * Language strings for local_user_restore plugin (Spanish).
 *
 * @package    local_user_restore
 * @copyright  2024 Your Institution
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['pluginname'] = 'Restaurar Usuarios';
$string['user_restore:view'] = 'Ver usuarios eliminados';
$string['user_restore:restore'] = 'Restaurar usuarios eliminados';

// Títulos de página.
$string['deletedusers'] = 'Usuarios Eliminados';
$string['restoreuser'] = 'Restaurar Usuario';
$string['managedeletedusers'] = 'Gestionar Usuarios Eliminados';

// Encabezados de tabla.
$string['userid'] = 'ID de Usuario';
$string['originalusername'] = 'Nombre de Usuario Original';
$string['currentusername'] = 'Nombre de Usuario Actual';
$string['firstname'] = 'Nombre';
$string['lastname'] = 'Apellido';
$string['email'] = 'Correo Electrónico';
$string['timedeleted'] = 'Fecha de Eliminación';
$string['actions'] = 'Acciones';

// Campos del formulario.
$string['newusername'] = 'Nuevo Nombre de Usuario';
$string['newusername_help'] = 'Ingrese un nuevo nombre de usuario para el usuario restaurado. El nombre de usuario original fue anonimizado al eliminarse.';
$string['newemail'] = 'Nuevo Correo Electrónico';
$string['newemail_help'] = 'Ingrese una dirección de correo electrónico válida para el usuario restaurado. El correo original fue anonimizado al eliminarse.';
$string['newpassword'] = 'Nueva Contraseña';
$string['newpassword_help'] = 'Ingrese una nueva contraseña para el usuario. Esto es requerido ya que la contraseña original fue eliminada.';
$string['sendnotification'] = 'Enviar correo de notificación';
$string['sendnotification_help'] = 'Si está habilitado, se enviará un correo al usuario con sus nuevas credenciales.';

// Botones.
$string['restore'] = 'Restaurar';
$string['cancel'] = 'Cancelar';
$string['back'] = 'Volver a la lista';

// Mensajes.
$string['userrestored'] = 'El usuario "{$a}" ha sido restaurado exitosamente.';
$string['userrestoreerror'] = 'Error al restaurar usuario: {$a}';
$string['nodeletedusers'] = 'No se encontraron usuarios eliminados.';
$string['confirmrestore'] = '¿Está seguro de que desea restaurar este usuario?';
$string['usernameexists'] = 'Este nombre de usuario ya está en uso. Por favor elija uno diferente.';
$string['emailexists'] = 'Este correo electrónico ya está en uso. Por favor elija uno diferente.';
$string['invaliduser'] = 'Usuario inválido o el usuario no está eliminado.';
$string['restoredsuccessfully'] = 'Usuario restaurado exitosamente';
$string['restoreinfo'] = 'Restaurar un usuario reactivará su cuenta. Debe proporcionar un nuevo nombre de usuario y correo electrónico ya que los valores originales fueron anonimizados durante la eliminación.';

// Configuración.
$string['settings'] = 'Configuración de Restaurar Usuarios';
$string['enablesnapshot'] = 'Habilitar captura de datos';
$string['enablesnapshot_desc'] = 'Capturar automáticamente los datos del usuario (matrículas, grupos, roles, calificaciones) antes de la eliminación para permitir su restauración posterior.';
$string['enablelogging'] = 'Habilitar registro';
$string['enablelogging_desc'] = 'Registrar todas las acciones de restauración de usuarios para propósitos de auditoría.';
$string['allowbulkrestore'] = 'Permitir restauración masiva';
$string['allowbulkrestore_desc'] = 'Permitir a los administradores restaurar múltiples usuarios a la vez.';

// Privacidad.
$string['privacy:metadata'] = 'El plugin Restaurar Usuarios almacena capturas de datos de usuario antes de la eliminación para permitir su restauración completa. Esto incluye datos de matrícula, membresías de grupo, membresías de cohorte, asignaciones de rol y calificaciones.';

// Eventos.
$string['eventuserrestored'] = 'Usuario restaurado';

// Capacidades.
$string['user_restore:viewdeletedusers'] = 'Ver lista de usuarios eliminados';
$string['user_restore:restoreusers'] = 'Restaurar usuarios eliminados';

// Errores.
$string['nopermission'] = 'No tiene permiso para realizar esta acción.';
$string['usernotfound'] = 'Usuario no encontrado.';
$string['usernotdeleted'] = 'Este usuario no ha sido eliminado.';

// Cadenas de restauración de datos.
$string['restoredata'] = 'Restaurar Datos del Usuario';
$string['restoreuserdata'] = 'Restaurar matrículas, grupos y calificaciones';
$string['restoreuserdata_help'] = 'Si está habilitado, las matrículas en cursos, membresías de grupo, membresías de cohorte, asignaciones de rol y calificaciones del usuario serán restauradas desde la captura guardada.';
$string['snapshotavailable'] = 'Datos de usuario guardados disponibles para restauración:';
$string['nosnapshotavailable'] = 'No hay datos guardados disponibles. El usuario será restaurado pero sus matrículas, grupos y calificaciones deberán ser agregados manualmente.';
$string['enrolments'] = 'Matrículas en cursos';
$string['groups'] = 'Membresías de grupo';
$string['cohorts'] = 'Membresías de cohorte';
$string['roles'] = 'Asignaciones de rol';
$string['grades'] = 'Calificaciones';

// Mensajes de resultado de restauración.
$string['enrolmentsrestored'] = '{$a} matrícula(s) restaurada(s)';
$string['groupsrestored'] = '{$a} membresía(s) de grupo restaurada(s)';
$string['cohortsrestored'] = '{$a} membresía(s) de cohorte restaurada(s)';
$string['rolesrestored'] = '{$a} asignación(es) de rol restaurada(s)';
$string['gradesrestored'] = '{$a} calificación(es) restaurada(s)';

// Cadenas de metadatos de privacidad.
$string['privacy:metadata:userid'] = 'El ID del usuario cuya captura de datos fue guardada.';
$string['privacy:metadata:datatype'] = 'El tipo de datos almacenados (matrícula, grupo, cohorte, rol, calificación).';
$string['privacy:metadata:datajson'] = 'Captura codificada en JSON de los datos del usuario antes de la eliminación.';
$string['privacy:metadata:timecreated'] = 'La hora en que se creó la captura.';
