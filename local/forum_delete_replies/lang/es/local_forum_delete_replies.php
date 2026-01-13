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
 * Language strings for local_forum_delete_replies (Spanish)
 *
 * @package    local_forum_delete_replies
 * @copyright  2025 Your Organization
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['pluginname'] = 'Eliminar respuestas del foro';
$string['deleteallreplies'] = 'Eliminar todas las respuestas';
$string['deletefromforum'] = 'Eliminar respuestas del foro';
$string['selectforum'] = 'Seleccionar un foro';
$string['selectforumhelp'] = 'Seleccione el foro del cual desea eliminar todas las respuestas. Solo se conservara el post inicial de cada discusion.';
$string['nodiscussions'] = 'Este foro no tiene discusiones.';
$string['noforums'] = 'No hay foros en este curso.';
$string['noreplies'] = 'No hay respuestas para eliminar en este foro.';
$string['confirmdelete'] = 'Confirmar eliminacion';
$string['confirmdeletemessage'] = 'Esta seguro de que desea eliminar todas las respuestas del foro "{$a->forumname}"? Esta accion eliminara {$a->replycount} respuestas de {$a->discussioncount} discusiones. Solo se conservara el post inicial de cada discusion. Esta accion no se puede deshacer.';
$string['deletesuccess'] = 'Se eliminaron exitosamente {$a->deleted} respuestas de {$a->discussions} discusiones.';
$string['deleteerror'] = 'Error al eliminar algunas respuestas. Se eliminaron {$a->deleted} de {$a->total} respuestas.';
$string['deletepartial'] = '{$a->deleted} respuestas eliminadas. Ocurrieron {$a->errors} errores.';
$string['cancel'] = 'Cancelar';
$string['delete'] = 'Eliminar respuestas';
$string['deleting'] = 'Eliminando respuestas...';
$string['forum'] = 'Foro';
$string['discussions'] = 'Discusiones';
$string['replies'] = 'Respuestas a eliminar';
$string['firstpost'] = 'Post inicial (se conserva)';
$string['preview'] = 'Vista previa';
$string['previewmessage'] = 'Las siguientes respuestas seran eliminadas:';
$string['backtocourse'] = 'Volver al curso';
$string['backtoforum'] = 'Volver al foro';
$string['nopermission'] = 'No tiene permiso para eliminar respuestas de este foro.';
$string['invalidforum'] = 'Foro especificado no valido.';
$string['invalidcourse'] = 'Curso especificado no valido.';
$string['discussionname'] = 'Discusion';
$string['replycount'] = 'Respuestas';
$string['author'] = 'Autor';
$string['created'] = 'Creado';
$string['willbedeleted'] = 'Sera eliminado';
$string['preserved'] = 'Conservado';
$string['privacy:metadata'] = 'El plugin Eliminar respuestas del foro no almacena ningun dato personal.';
$string['forum_delete_replies:delete'] = 'Eliminar todas las respuestas de los foros';
$string['forum_delete_replies:view'] = 'Ver pagina de eliminacion de respuestas';
$string['summary'] = 'Resumen';
$string['totalreplies'] = 'Total de respuestas a eliminar';
$string['totaldiscussions'] = 'Total de discusiones afectadas';
$string['warning'] = 'Advertencia';
$string['warningmessage'] = 'Esta operacion es irreversible. Todas las respuestas, incluyendo sus archivos adjuntos y valoraciones, seran eliminadas permanentemente.';
$string['processing'] = 'Procesando discusion {$a->current} de {$a->total}...';
$string['completed'] = 'Operacion completada';
$string['deletedrepliesfromdiscussion'] = 'Se eliminaron {$a->count} respuestas de la discusion "{$a->name}"';
$string['bulkdelete'] = 'Eliminar todas las respuestas del curso';
$string['bulkwarning'] = 'Esto eliminara TODAS las respuestas de TODOS los foros de este curso con una sola accion.';
$string['bulkconfirmmessage'] = 'Esta a punto de eliminar {$a->replycount} respuestas de {$a->forumcount} foros en el curso "{$a->coursename}". Esta accion no se puede deshacer.';
$string['deleteallnow'] = 'Eliminar todas las respuestas ahora';
$string['totalforums'] = 'Total de foros';
$string['forumswithReplies'] = 'Foros con respuestas';
$string['forumlist'] = 'Foros en el curso';
$string['sitewidedelete'] = 'Eliminar respuestas de todo el sitio';
$string['sitewidedeletewarning'] = 'ADVERTENCIA: Esto eliminara TODAS las respuestas de TODOS los foros de TODO el sitio!';
$string['sitewideconfirm'] = 'Esta absolutamente seguro? Esto eliminara {$a} respuestas de todos los cursos.';
$string['oneclickdelete'] = 'Eliminar con un clic';
