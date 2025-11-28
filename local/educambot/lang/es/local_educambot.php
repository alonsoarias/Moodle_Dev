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
 * Plugin strings are defined here.
 *
 * @package     local_educambot
 * @copyright   2025 EducamBot Team
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

// Plugin general.
$string['pluginname'] = 'Nexo Bot';
$string['educambot'] = 'Nexo Bot';

// Capabilities.
$string['educambot:use'] = 'Usar Nexo Bot';
$string['educambot:manage'] = 'Gestionar Nexo Bot';

// Settings.
$string['settings_header'] = 'Configuración de Nexo Bot';
$string['general_heading'] = 'General';
$string['widgetenabled'] = 'Habilitar widget';
$string['widgetenabled_desc'] = 'Mostrar el widget de chat en todas las páginas para usuarios con los permisos apropiados';
$string['identity_heading'] = 'Identidad del Bot';
$string['appearance_heading'] = 'Apariencia';

$string['botname'] = 'Nombre del bot';
$string['botname_desc'] = 'El nombre que se muestra para el bot';
$string['botname_default'] = 'Nexo Bot';

$string['widgetlabel'] = 'Etiqueta del widget';
$string['widgetlabel_desc'] = 'La etiqueta mostrada en el botón del widget';
$string['widgetlabel_default'] = 'Chat Bot';

$string['greetingtemplate'] = 'Mensaje de saludo';
$string['greetingtemplate_desc'] = 'El mensaje de saludo al abrir el chat. Puede usar: {{userfirstname}}, {{userlastname}}, {{botname}}';
$string['greeting_default'] = '¡Hola {{userfirstname}}! Soy {{botname}}, tu asistente virtual. ¿En qué puedo ayudarte hoy?';

$string['primarycolor'] = 'Color primario';
$string['primarycolor_desc'] = 'El color principal del widget (botones, encabezado)';

// Management page.
$string['managerules'] = 'Gestionar Reglas';
$string['managerules_desc'] = 'Crear y gestionar reglas de respuesta del bot';
$string['addrule'] = 'Agregar Regla';
$string['editrule'] = 'Editar Regla';
$string['deleterule'] = 'Eliminar Regla';
$string['norules'] = 'No hay reglas definidas aún';
$string['confirmdelete'] = '¿Está seguro de que desea eliminar esta regla?';

// Rule form.
$string['pattern'] = 'Patrón de Pregunta';
$string['pattern_help'] = 'La pregunta o frase principal que activará esta respuesta. Ejemplo: "¿Cómo me inscribo en un curso?"';
$string['keywords'] = 'Palabras Clave Adicionales';
$string['keywords_help'] = 'Palabras o frases adicionales para coincidir (una por línea). Ejemplo: inscribir, inscripción, matricular';
$string['response'] = 'Respuesta';
$string['response_help'] = 'La respuesta que proporcionará el bot cuando esta regla coincida';
$string['enabled'] = 'Habilitado';
$string['enabled_help'] = 'Habilitar o deshabilitar esta regla';

// Service responses.
$string['noresponse'] = 'Lo siento, no tengo una respuesta para esa pregunta. Por favor intenta reformularla o contacta con soporte.';
$string['invalidquestion'] = 'Por favor ingresa una pregunta válida.';

// Table headers.
$string['pattern_header'] = 'Patrón';
$string['response_header'] = 'Respuesta';
$string['status_header'] = 'Estado';
$string['actions_header'] = 'Acciones';
$string['status_enabled'] = 'Habilitado';
$string['status_disabled'] = 'Deshabilitado';

// Actions.
$string['edit'] = 'Editar';
$string['delete'] = 'Eliminar';
$string['enable'] = 'Habilitar';
$string['disable'] = 'Deshabilitar';

// Messages.
$string['rulecreated'] = 'Regla creada exitosamente';
$string['ruleupdated'] = 'Regla actualizada exitosamente';
$string['ruledeleted'] = 'Regla eliminada exitosamente';
$string['error_savingrule'] = 'Error al guardar la regla';

// Widget.
$string['typeaquestion'] = 'Escribe tu pregunta...';
$string['online'] = 'En linea';
$string['clearhistory'] = 'Limpiar historial';

// Reports.
$string['reports'] = 'Reportes';
$string['viewreports'] = 'Ver Reportes';
$string['totalconversations'] = 'Total Conversaciones';
$string['matchedquestions'] = 'Preguntas Respondidas';
$string['unmatchedquestions'] = 'Sin Respuesta';
$string['successrate'] = 'Tasa de Exito';
$string['recentconversations'] = 'Conversaciones Recientes';
$string['averageconfidence'] = 'Confianza Prom.';
$string['topquestions'] = 'Preguntas Frecuentes';
$string['questionswithoutrule'] = 'Preguntas Sin Regla';
$string['nologs'] = 'No hay conversaciones registradas aun';
$string['nounmatchedquestions'] = 'Todas las preguntas han sido respondidas. No hay preguntas sin respuesta.';
$string['matched'] = 'Respondida';
$string['unmatched'] = 'Sin respuesta';
$string['uniqueusers'] = 'Usuarios Unicos';
$string['confidence'] = 'Confianza';
$string['viewdetails'] = 'Ver Detalles';
$string['conversationdetails'] = 'Detalles de la Conversacion';
$string['close'] = 'Cerrar';
$string['createrule'] = 'Crear Regla';
$string['frequency'] = 'Frecuencia';
$string['filters'] = 'Filtros';
$string['datefrom'] = 'Desde';
$string['dateto'] = 'Hasta';
$string['all'] = 'Todos';
$string['matchedonly'] = 'Solo respondidas';
$string['unmatchedonly'] = 'Solo sin respuesta';
$string['searchquestion'] = 'Buscar pregunta...';
$string['applyfilters'] = 'Aplicar';

// Options (Quick Replies).
$string['options'] = 'Opciones';
$string['manageoptions'] = 'Opciones';
$string['addoption'] = 'Agregar Opcion';
$string['editoption'] = 'Editar Opcion';
$string['deleteoption'] = 'Eliminar Opcion';
$string['optiontext'] = 'Texto del Boton';
$string['optiontext_help'] = 'Texto mostrado en el boton (max 100 caracteres)';
$string['targetrule'] = 'Regla Destino';
$string['targetrule_help'] = 'Regla que se activara al hacer clic en esta opcion';
$string['selecttargetrule'] = 'Seleccionar regla...';
$string['icon'] = 'Icono';
$string['icon_help'] = 'Emoji o icono para mostrar antes del texto (opcional). Ejemplo: iconos emoji';
$string['showoptions'] = 'Mostrar opciones';
$string['showoptions_help'] = 'Mostrar botones de respuesta rapida despues de esta respuesta';
$string['nooptions'] = 'No hay opciones definidas para esta regla';
$string['optionorder'] = 'Orden';
$string['optioncreated'] = 'Opcion creada exitosamente';
$string['optionupdated'] = 'Opcion actualizada exitosamente';
$string['optiondeleted'] = 'Opcion eliminada exitosamente';
$string['confirmdeleteoption'] = '¿Esta seguro de que desea eliminar esta opcion?';
$string['optionsfor'] = 'Opciones para la regla';
$string['backtorules'] = 'Volver a Reglas';
$string['moveup'] = 'Subir';
$string['movedown'] = 'Bajar';
