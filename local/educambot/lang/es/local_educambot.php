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
 * Cadenas para el componente 'local_educambot'.
 *
 * @package     local_educambot
 * @copyright   2024 Educam
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 o posterior
 */

defined('MOODLE_INTERNAL') || die();

$string['pluginname'] = 'Educam Bot';
$string['privacy:metadata'] = 'El plugin Educam Bot almacena datos de conversación para mejorar la base de conocimiento.';
$string['privacy:metadata:log'] = 'Registros de conversaciones almacenados por Educam Bot.';
$string['privacy:metadata:log:userid'] = 'Identificador del usuario que interactúa con el bot.';
$string['privacy:metadata:log:sessionid'] = 'Identificador interno de sesión del chatbot para agrupar mensajes.';
$string['privacy:metadata:log:question'] = 'Mensaje enviado por el usuario.';
$string['privacy:metadata:log:response'] = 'Respuesta proporcionada por el bot.';
$string['privacy:metadata:log:ruleid'] = 'Regla que generó la respuesta, cuando existe.';
$string['privacy:metadata:log:confidence'] = 'Confianza calculada para la coincidencia.';
$string['privacy:metadata:log:page'] = 'Página en la que se inició la conversación.';
$string['privacy:metadata:log:timecreated'] = 'Momento de la interacción.';
$string['privacy:metadata:unanswered'] = 'Preguntas que no obtuvieron respuesta automática.';
$string['privacy:metadata:unanswered:userid'] = 'Identificador del usuario que realizó la pregunta.';
$string['privacy:metadata:unanswered:question'] = 'Contenido de la pregunta sin respuesta.';
$string['privacy:metadata:unanswered:page'] = 'Página desde la que se registró la pregunta.';
$string['privacy:metadata:unanswered:timecreated'] = 'Momento en que se almacenó la pregunta.';
$string['manageentries'] = 'Base de conocimiento';
$string['manageentriesdesc'] = 'Configura respuestas, patrones y sugerencias proactivas del chatbot.';
$string['addentry'] = 'Añadir entrada';
$string['editentry'] = 'Editar entrada';
$string['deleteentry'] = 'Eliminar entrada';
$string['confirmdelete'] = '¿Seguro que deseas eliminar "{$a}"?';
$string['pattern'] = 'Pregunta/patrón principal';
$string['synonyms'] = 'Sinónimos o frases alternativas';
$string['synonyms_help'] = 'Indica cada sinónimo o frase alternativa en una línea diferente.';
$string['keywords'] = 'Palabras clave';
$string['keywords_help'] = 'Introduce palabras clave separadas por comas para mejorar la coincidencia flexible.';
$string['response'] = 'Respuesta';
$string['roles'] = 'Restringir a roles';
$string['roles_help'] = 'Si se indica, la entrada solo se usará cuando el usuario tenga alguno de los roles seleccionados.';
$string['contexts'] = 'Contextos de página';
$string['contexts_help'] = 'Lista opcional de rutas o componentes de Moodle (uno por línea) para priorizar respuestas relacionadas con la zona visitada.';
$string['suggested'] = 'Marcar como sugerencia proactiva';
$string['enabled'] = 'Habilitada';
$string['saved'] = 'Entrada guardada';
$string['deleted'] = 'Entrada eliminada';
$string['logview'] = 'Registro de conversaciones';
$string['unansweredview'] = 'Preguntas sin respuesta';
$string['question'] = 'Pregunta';
$string['matchedpattern'] = 'Patrón asociado';
$string['confidence'] = 'Confianza';
$string['timecreated'] = 'Fecha';
$string['responsepreview'] = 'Vista previa de la respuesta';
$string['searchplaceholder'] = 'Pregunta a Educam Bot...';
$string['noanswer'] = 'No encontré una respuesta. Avisaré al equipo administrador.';
$string['suggestedquestions'] = 'Preguntas sugeridas';
$string['loading'] = 'Procesando...';
$string['actions'] = 'Acciones';
$string['settingsheading'] = 'Configuración de Educam Bot';
$string['loggingenabled'] = 'Activar registro de conversaciones';
$string['loggingenabled_desc'] = 'Cuando está activo, el plugin guarda cada interacción para su revisión posterior.';
$string['retentionperiod'] = 'Retención del registro (días)';
$string['retentionperiod_desc'] = 'Número de días que se conservarán los registros de conversación. Los más antiguos se eliminan automáticamente.';
$string['widgettitle'] = '¿Necesitas ayuda?';
$string['widgetintro'] = 'Pregúntame lo que quieras sobre la plataforma';
$string['knowledgebase'] = 'Base de conocimiento';
$string['savechanges'] = 'Guardar cambios';
$string['cancel'] = 'Cancelar';
$string['noentries'] = 'Todavía no hay entradas configuradas. ¡Añade tu primera regla para comenzar a ayudar!';
$string['unansweredquestion'] = 'Pregunta sin respuesta';
$string['usercontext'] = 'Contexto del usuario';
$string['cleanuptask'] = 'Limpieza de registros de Educam Bot';
$string['sessionid'] = 'Sesión';
$string['roleany'] = 'Cualquier rol';
$string['contextany'] = 'Cualquier página';
$string['status'] = 'Estado';
$string['enabledyes'] = 'Habilitada';
$string['enabledno'] = 'Deshabilitada';
$string['formerrorpatternrequired'] = 'Debes definir la pregunta o patrón principal.';
$string['formerrorresponcerequired'] = 'Debes proporcionar una respuesta.';
$string['generalsettings'] = 'Configuración general';
$string['chatheader'] = 'Educam Bot';
$string['startplaceholder'] = 'Escribe tu pregunta...';
$string['noconversationsfound'] = 'Aún no hay registros.';
$string['export'] = 'Exportar';
$string['import'] = 'Importar';
$string['faqtitle'] = 'Preguntas frecuentes';
