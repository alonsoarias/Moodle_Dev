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
$string['question'] = 'Pregunta';
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

// Categorias.
$string['categories'] = 'Categorias';
$string['managecategories'] = 'Gestionar Categorias';
$string['category'] = 'Categoria';
$string['categoryname'] = 'Nombre de Categoria';
$string['categorydescription'] = 'Descripcion';
$string['parentcategory'] = 'Categoria Padre';
$string['addcategory'] = 'Agregar Categoria';
$string['editcategory'] = 'Editar Categoria';
$string['deletecategory'] = 'Eliminar Categoria';
$string['nocategories'] = 'No hay categorias definidas aun';
$string['categorycreated'] = 'Categoria creada exitosamente';
$string['categoryupdated'] = 'Categoria actualizada exitosamente';
$string['categorydeleted'] = 'Categoria eliminada exitosamente';
$string['categoryhasrules'] = 'No se puede eliminar la categoria: contiene {$a} regla(s). Muevelas o eliminalas primero.';
$string['categoryhaschildren'] = 'No se puede eliminar la categoria: tiene {$a} subcategoria(s). Eliminalas primero.';
$string['uncategorized'] = 'Sin categoria';
$string['rules'] = 'Reglas';
$string['viewrules'] = 'Ver Reglas';

// Etiquetas.
$string['tags'] = 'Etiquetas';
$string['tags_help'] = 'Etiquetas separadas por comas para busqueda (ej: inscripcion, registro, curso)';

// Importar/Exportar.
$string['importexport'] = 'Importar/Exportar';
$string['exportkb'] = 'Exportar Base de Conocimiento';
$string['exportkb_desc'] = 'Descargar la base de conocimiento completa (categorias, reglas y opciones) como archivo JSON.';
$string['importkb'] = 'Importar Base de Conocimiento';
$string['importkb_desc'] = 'Subir un archivo JSON para importar categorias, reglas y opciones.';
$string['exportfile'] = 'Exportar a JSON';
$string['import'] = 'Importar';
$string['selectfile'] = 'Seleccionar archivo';
$string['clearexisting'] = 'Borrar datos existentes';
$string['clearexisting_help'] = 'Eliminar todas las categorias, reglas y opciones existentes antes de importar. ¡Usar con precaucion!';
$string['importsuccess'] = '¡Importacion exitosa! Se importaron {$a->categories} categorias, {$a->rules} reglas y {$a->options} opciones.';
$string['importerror'] = 'Error al leer el archivo de importacion';
$string['importinvalidjson'] = 'Formato JSON invalido en el archivo de importacion';
$string['importinvalidversion'] = 'Version invalida o faltante en el archivo de importacion';
$string['currentstats'] = 'Base de conocimiento actual: {$a->categories} categorias, {$a->rules} reglas, {$a->options} opciones.';

// Duplicar.
$string['duplicate'] = 'Duplicar';
$string['duplicaterule'] = 'Duplicar Regla';
$string['confirmduplicaterule'] = '¿Crear una copia de esta regla con sus opciones?';
$string['ruleduplicated'] = 'Regla duplicada exitosamente';
$string['copy'] = 'Copia';

// Busqueda.
$string['searchrules'] = 'Buscar reglas...';

// Version 1.6.1 - Preguntas sugeridas.
$string['suggestedquestions'] = 'Preguntas frecuentes';
$string['selectanoption'] = 'Selecciona una opcion o escribe tu pregunta';
$string['anotherquestion'] = 'Otra pregunta';
$string['resourcescategory'] = 'Recursos y Materiales';

// Version 1.7.0 - Contexto Moodle y Shortcuts.
// Shortcuts.
$string['shortcuts'] = 'Accesos Rapidos';
$string['manageshortcuts'] = 'Gestionar Accesos Rapidos';
$string['addshortcut'] = 'Agregar Acceso Rapido';
$string['editshortcut'] = 'Editar Acceso Rapido';
$string['deleteshortcut'] = 'Eliminar Acceso Rapido';
$string['noshortcuts'] = 'No hay accesos rapidos definidos aun';
$string['shortcutname'] = 'Nombre del Acceso';
$string['shortcutname_help'] = 'Nombre descriptivo del acceso rapido';
$string['shortcutkeywords'] = 'Palabras Clave';
$string['shortcutkeywords_help'] = 'Palabras o frases que activan este acceso (una por linea)';
$string['actiontype'] = 'Tipo de Accion';
$string['actiontype_help'] = 'El tipo de datos de Moodle que mostrara este acceso';
$string['shortcutcreated'] = 'Acceso rapido creado exitosamente';
$string['shortcutupdated'] = 'Acceso rapido actualizado exitosamente';
$string['shortcutdeleted'] = 'Acceso rapido eliminado exitosamente';
$string['shortcutshelp'] = 'Ayuda de Accesos Rapidos';
$string['shortcutshelp_desc'] = 'Los accesos rapidos permiten a los usuarios obtener informacion de Moodle directamente. Cuando un usuario escribe una frase que coincide con las palabras clave, el bot mostrara datos dinamicos del sistema.';
$string['availableactiontypes'] = 'Tipos de accion disponibles';
$string['nametoolong'] = 'El nombre es demasiado largo (maximo 100 caracteres)';
$string['keywordsrequired'] = 'Las palabras clave son requeridas';
$string['sortorder'] = 'Orden';
$string['unknownshortcut'] = 'Acceso rapido desconocido';

// Action types.
$string['actiontype_assignments'] = 'Tareas pendientes del curso';
$string['actiontype_grades'] = 'Calificaciones del curso';
$string['actiontype_calendar'] = 'Proximos eventos del calendario';
$string['actiontype_messages'] = 'Mensajes recientes';
$string['actiontype_teachers'] = 'Profesores del curso';
$string['actiontype_course'] = 'Informacion del curso';
$string['actiontype_progress'] = 'Progreso en el curso';

// Shortcut responses.
$string['shortcut_nocourse'] = 'Esta funcion solo esta disponible dentro de un curso. Por favor, navega a un curso primero.';
$string['shortcut_noassignments'] = '¡No tienes tareas pendientes en este curso! ¡Buen trabajo!';
$string['shortcut_nogrades'] = 'Aun no hay calificaciones disponibles en este curso.';
$string['shortcut_noevents'] = 'No hay eventos programados para los proximos 7 dias.';
$string['shortcut_noteachers'] = 'No hay profesores asignados a este curso.';
$string['shortcut_assignmentsheader'] = 'Tus tareas pendientes:';
$string['shortcut_gradesheader'] = 'Tus calificaciones en {$a}:';
$string['shortcut_eventsheader'] = 'Proximos eventos (7 dias):';
$string['shortcut_messagesheader'] = 'Tus mensajes:';
$string['shortcut_teachersheader'] = 'Profesores de {$a}:';
$string['shortcut_progressheader'] = 'Tu progreso en {$a}:';

// Context and placeholders.
$string['contextaware'] = 'Sensible al contexto';
$string['contextaware_help'] = 'Esta regla usa informacion del curso o pagina actual';
$string['dynamicresponse'] = 'Respuesta dinamica';
$string['dynamicresponse_help'] = 'La respuesta contiene marcadores que seran reemplazados con datos reales';
$string['requiredcontext'] = 'Contexto requerido';
$string['requiredcontext_help'] = 'Contexto necesario: site (sitio), course (curso), activity (actividad)';
$string['placeholders'] = 'Marcadores disponibles';
$string['placeholders_help'] = 'Usa estos marcadores en las respuestas dinamicas para mostrar informacion del contexto actual';
$string['requirescoursecontext'] = 'Esta informacion solo esta disponible dentro de un curso. Por favor, navega a un curso primero.';

// Placeholder labels.
$string['notavailable'] = 'No disponible';
$string['theteacher'] = 'el profesor';
$string['noteachersassigned'] = 'Sin profesores asignados';
$string['noenddate'] = 'Sin fecha de fin';
$string['notgraded'] = 'Sin calificar';
$string['noduedate'] = 'Sin fecha limite';
$string['nopendingassignments'] = 'Sin tareas pendientes';
$string['nopendingquizzes'] = 'Sin cuestionarios pendientes';
$string['andmore'] = '... y {$a} mas';
$string['noupcomingevents'] = 'Sin eventos proximos';
$string['noeventthisweek'] = 'Sin eventos esta semana';

// Grade and progress.
$string['overallgrade'] = 'Calificacion general';
$string['notgradedyet'] = 'Aun no hay calificacion general';
$string['recentgrades'] = 'Calificaciones recientes';
$string['viewallgrades'] = 'Ver todas las calificaciones';
$string['currentgrade'] = 'Calificacion actual';
$string['pendingtasks'] = 'Tareas pendientes';
$string['completion'] = 'Completado';
$string['teacher'] = 'Profesor';

// Messages.
$string['unreadmessages'] = 'Tienes {$a} mensaje(s) no leido(s)';
$string['nounreadmessages'] = 'No tienes mensajes no leidos';
$string['recentmessages'] = 'Mensajes recientes';
$string['viewallmessages'] = 'Ver todos los mensajes';
$string['sendmessage'] = 'Enviar mensaje';

// Calendar.
$string['viewcalendar'] = 'Ver calendario completo';
$string['duedate'] = 'Vence';
$string['overdue'] = 'Vencida';
$string['duein'] = 'Vence en {$a}';

// v1.8.0 - Personalizacion avanzada.
// Horarios.
$string['schedule_heading'] = 'Horario de Disponibilidad';
$string['manageschedule'] = 'Gestionar Horario';
$string['scheduleenabled'] = 'Habilitar horario';
$string['scheduleenabled_desc'] = 'Restringir la disponibilidad del bot a horas especificas. Cuando esta deshabilitado, el bot esta siempre disponible.';
$string['schedule_help'] = 'Configure las horas en que el chatbot esta disponible. Fuera de estas horas, el widget no se mostrara.';
$string['schedule_disabled_notice'] = 'Nota: El control de horario esta actualmente deshabilitado en la configuracion. Habilite "Habilitar horario" en la configuracion del plugin para activarlo.';
$string['scheduleupdated'] = 'Horario actualizado correctamente';
$string['dayofweek'] = 'Dia';
$string['timefrom'] = 'Desde';
$string['timeto'] = 'Hasta';
$string['currentstatus'] = 'Estado Actual';
$string['botonline'] = 'El bot esta actualmente EN LINEA y disponible.';
$string['botoffline'] = 'El bot esta actualmente fuera de linea. Estara disponible nuevamente: {$a}';
$string['todayat'] = 'Hoy a las {$a}';
$string['dayat'] = '{$a->day} a las {$a->time}';
$string['notscheduled'] = 'Sin disponibilidad programada';

// Configuracion de idioma.
$string['language_heading'] = 'Configuracion de Idioma';
$string['autolang'] = 'Auto-detectar idioma';
$string['autolang_desc'] = 'Seleccionar automaticamente las reglas basado en el idioma preferido del usuario. Cuando esta habilitado, se prefieren las reglas en el idioma del usuario.';
$string['language'] = 'Idioma';
$string['language_help'] = 'Seleccione el idioma para esta regla. Las reglas se filtraran segun la preferencia de idioma del usuario.';
$string['multilanguage'] = 'Multi-idioma';
$string['parentrule'] = 'Regla padre (para traducciones)';
$string['parentrule_help'] = 'Si esta regla es una traduccion de otra regla, seleccione la regla padre aqui. Deje vacio para reglas originales.';
$string['translations'] = 'Traducciones';
$string['addtranslation'] = 'Agregar Traduccion';

// Restricciones.
$string['restrictions'] = 'Restricciones';
$string['roles'] = 'Roles';
$string['roles_help'] = 'Nombres cortos de roles separados por comas (ej: student,teacher). Deje vacio para todos los roles.';
$string['courses'] = 'Cursos';
$string['courses_help'] = 'IDs de cursos separados por comas. Deje vacio para todos los cursos.';

// Seccion avanzada.
$string['advanced'] = 'Avanzado';
$string['contextaware'] = 'Sensible al contexto';
$string['contextaware_help'] = 'Si esta habilitado, la respuesta puede incluir datos dinamicos del contexto actual.';
$string['dynamicresponse'] = 'Respuesta dinamica';
$string['dynamicresponse_help'] = 'Si esta habilitado, la respuesta contiene marcadores que seran reemplazados con datos reales.';
$string['requiredcontext'] = 'Contexto requerido';
$string['requiredcontext_help'] = 'Especifique donde debe aplicarse esta regla. "Cualquiera" significa que aplica en todas partes.';
$string['anycontext'] = 'Cualquier contexto';
$string['sitecontext'] = 'Solo nivel sitio';
$string['coursecontext'] = 'Solo nivel curso';
$string['activitycontext'] = 'Solo nivel actividad';

// Temas.
$string['managethemes'] = 'Gestionar Temas';
$string['addtheme'] = 'Agregar Tema';
$string['edittheme'] = 'Editar Tema';
$string['deletetheme'] = 'Eliminar Tema';
$string['themename'] = 'Nombre del Tema';
$string['themecreated'] = 'Tema creado correctamente';
$string['themeupdated'] = 'Tema actualizado correctamente';
$string['themedeleted'] = 'Tema eliminado correctamente';
$string['themesetasdefault'] = 'Tema establecido como predeterminado';
$string['setasdefault'] = 'Establecer como predeterminado';
$string['cannotdeletedefault'] = 'No se puede eliminar el tema predeterminado. Establezca otro tema como predeterminado primero.';
$string['nothemes'] = 'No se encontraron temas';
$string['colors'] = 'Colores';
$string['primarycolor_help'] = 'Color de acento principal para el encabezado y botones del widget.';
$string['secondarycolor'] = 'Color Secundario';
$string['secondarycolor_help'] = 'Color de acento secundario para estados hover y acentos.';
$string['textcolor'] = 'Color de Texto';
$string['textcolor_help'] = 'Color para el contenido de texto en el widget.';
$string['backgroundcolor'] = 'Color de Fondo';
$string['backgroundcolor_help'] = 'Color de fondo para el area del chat.';
$string['usercolor'] = 'Color de Mensaje del Usuario';
$string['usercolor_help'] = 'Color de fondo para las burbujas de mensaje del usuario.';
$string['botcolor'] = 'Color de Mensaje del Bot';
$string['botcolor_help'] = 'Color de fondo para las burbujas de mensaje del bot.';
$string['invalidcolor'] = 'Formato de color invalido. Use formato hexadecimal (ej: #FF5500).';

// v1.8.1 - Personalizacion de Icono del Widget.
$string['widgeticonheading'] = 'Icono del Widget';
$string['widgeticontype'] = 'Tipo de icono';
$string['widgeticontype_help'] = 'Seleccione el tipo de icono que aparecera en el encabezado del widget.';
$string['icontype_default'] = 'Icono por defecto (globo)';
$string['icontype_emoji'] = 'Emoji';
$string['icontype_fontawesome'] = 'Font Awesome';
$string['icontype_custom'] = 'Imagen personalizada';
$string['widgeticonemoji'] = 'Emoji';
$string['widgeticonemoji_help'] = 'Ingrese un emoji Unicode (ej: 🤖, 💬, 🎓)';
$string['widgeticonfa'] = 'Clase Font Awesome';
$string['widgeticonfa_help'] = 'Ingrese la clase de Font Awesome sin el prefijo "fa-" (ej: robot, comments, graduation-cap)';
$string['widgeticonfile'] = 'Archivo de icono';
$string['widgeticonfile_help'] = 'Suba una imagen PNG, SVG, JPG o GIF. Tamano recomendado: 32x32px. Maximo: 100KB.';

// v1.8.1 - Personalizacion de Mascota.
$string['mascotheading'] = 'Mascota del Chatbot';
$string['mascotenabled'] = 'Habilitar mascota';
$string['mascotenabled_help'] = 'Mostrar una mascota animada en el widget que reacciona segun el estado del bot y ayuda a los usuarios con sugerencias y preguntas frecuentes.';
$string['mascottype'] = 'Tipo de mascota';
$string['mascottype_help'] = 'Seleccione el diseno de la mascota. Cada mascota tiene sus propias animaciones SVG.';
$string['mascot_none'] = 'Sin mascota';
$string['mascot_clippy'] = 'Clippy (clip de papel)';
$string['mascot_robot'] = 'Robot amigable';
$string['mascot_owl'] = 'Buho academico';
$string['mascot_custom'] = 'Mascota personalizada (SVG)';
$string['mascotfile'] = 'Archivo SVG de mascota';
$string['mascotfile_help'] = 'Suba un archivo SVG con estructura especifica (viewBox 80x80, IDs: body, eyes, arms). El SVG debe tener elementos animables mediante CSS. Maximo: 50KB.';
$string['mascot_aria_label'] = 'Mascota asistente del bot';

// v1.8.1 - Mensajes de mascota.
$string['mascot_greeting'] = '¡Hola! ¿En que puedo ayudarte?';
$string['mascot_needmore'] = '¿Necesitas algo mas?';
$string['mascot_tryagain'] = 'Intenta reformular tu pregunta o haz clic en mi para ver sugerencias';
$string['mascot_nopopular'] = 'Aun no hay preguntas populares';
$string['mascot_error'] = 'No se pudieron cargar las preguntas';
$string['mascot_popularheader'] = 'Preguntas populares:';
$string['mascot_similarheader'] = '¿Te refieres a:';
$string['mascot_suggest_tasks'] = '¿Necesitas ayuda con tus tareas?';
$string['mascot_suggest_grades'] = 'Puedo mostrarte tus calificaciones';
$string['mascot_suggest_calendar'] = '¿Quieres ver el calendario?';
$string['mascot_suggest_course'] = 'Preguntame sobre tu curso';
$string['mascot_suggest_help'] = '¡Haz clic en mi para ver preguntas populares!';
