<?php
// Este archivo forma parte de Moodle - http://moodle.org/
//
// Moodle es software libre: puedes redistribuirlo y/o modificarlo
// bajo los términos de la Licencia Pública General GNU publicada por
// la Free Software Foundation, ya sea la versión 3 de la licencia o
// (a tu elección) cualquier versión posterior.
//
// Moodle se distribuye con la esperanza de que resulte útil,
// pero SIN NINGUNA GARANTÍA; ni siquiera la garantía implícita
// de COMERCIABILIDAD o IDONEIDAD PARA UN PROPÓSITO PARTICULAR.
// Consulta la Licencia Pública General GNU para más detalles.
//
// Deberías haber recibido una copia de la Licencia Pública General GNU
// junto con Moodle. En caso contrario, consulta <http://www.gnu.org/licenses/>.

/**
 * Cadenas en español para el plugin local_chatbot.
 *
 * @package    local_chatbot
 */

$string['pluginname'] = 'Asistente de chatbot';

// Capacidades.
$string['chatbot:use'] = 'Usar el chatbot flotante';
$string['chatbot:manage'] = 'Configurar el chatbot';
$string['chatbot:export'] = 'Exportar conversaciones del chatbot';

// Textos generales.
$string['chatbot_title'] = 'Asistente virtual';
$string['chatbot_placeholder'] = 'Escribe tu mensaje…';
$string['chatbot_send'] = 'Enviar';
$string['chatbot_error'] = 'Lo sentimos, ocurrió un error. Intenta nuevamente.';
$string['chatbot_typing_indicator'] = 'El asistente está escribiendo…';
$string['chatbot_status_online'] = 'En línea y listo para ayudarte';
$string['chatbot_toggle_label'] = 'Hablar con {$a}';
$string['chatbot_voice_input_label'] = 'Entrada de voz';
$string['chatbot_emoji_picker_label'] = 'Selector de emojis';
$string['chatbot_export_conversation_label'] = 'Exportar conversación';
$string['chatbot_minimize'] = 'Minimizar';
$string['chatbot_close'] = 'Cerrar';
$string['chatbot_quick_actions_region'] = 'Accesos rápidos del chatbot';
$string['chatbot_suggestions_region'] = 'Sugerencias del asistente';
$string['chatbot_welcome_template'] = '¡Hola {name}! Estoy aquí para acompañarte en Moodle.';
$string['default_nomatch'] = 'Todavía no tengo información sobre eso. ¿Podrías reformular la pregunta?';

// Sugerencias y accesos rápidos.
$string['chatbot_suggestion_courses'] = '¿Dónde veo mis cursos?';
$string['chatbot_suggestion_grades'] = '¿Cómo consulto mis calificaciones?';
$string['chatbot_suggestion_support'] = 'Contactar soporte';
$string['chatbot_action_profile'] = 'Mi perfil';
$string['chatbot_action_profile_desc'] = 'Abre tu página de perfil';
$string['chatbot_action_calendar'] = 'Calendario';
$string['chatbot_action_calendar_desc'] = 'Abre la vista del calendario';
$string['chatbot_action_support'] = 'Centro de soporte';
$string['chatbot_action_support_desc'] = 'Muestra las opciones de contacto del soporte institucional';
$string['chatbot_action_generic'] = 'Acción ejecutada: {$a}';

// Respuestas automáticas.
$string['chatbot_response_greeting'] = '¡Hola! 👋 ¿En qué puedo ayudarte hoy?';
$string['chatbot_response_help'] = 'Puedo orientarte sobre cursos, calificaciones y enlaces útiles. Por ejemplo pregunta “¿Dónde veo mis calificaciones?”';
$string['chatbot_response_courses'] = 'Abre el menú “Mis cursos” en la parte superior para ver tus asignaturas inscritas.';
$string['chatbot_response_grades'] = 'Accede al reporte de calificaciones desde el menú de usuario o dentro de cada curso.';
$string['chatbot_response_support'] = 'Puedes contactar al equipo de soporte mediante el centro de ayuda o escribiendo al correo oficial de asistencia.';

// Exportación.
$string['chatbot_export_heading'] = 'Historial de la conversación';
$string['chatbot_export_empty'] = 'Todavía no se ha registrado ningún mensaje.';
$string['message'] = 'Mensaje';
$string['response'] = 'Respuesta';
$string['intent'] = 'Intención';
$string['feedback'] = 'Retroalimentación';
$string['download'] = 'Descargar';
$string['time'] = 'Hora';
$string['user'] = 'Usuario';

// Configuración.
$string['setting_enabled'] = 'Habilitar chatbot';
$string['setting_enabled_desc'] = 'Si está deshabilitado el widget no se mostrará en ninguna página.';
$string['setting_assistantname'] = 'Nombre del asistente';
$string['setting_assistantname_desc'] = 'Se muestra en el encabezado del widget y en los textos accesibles.';
$string['setting_position'] = 'Posición del widget';
$string['setting_position_desc'] = 'Selecciona dónde debe aparecer el botón flotante.';
$string['position_bottom_right'] = 'Abajo a la derecha';
$string['position_bottom_left'] = 'Abajo a la izquierda';
$string['setting_theme'] = 'Tema visual';
$string['setting_theme_desc'] = 'Elige la combinación de colores del widget.';
$string['theme_modern'] = 'Moderno';
$string['theme_minimal'] = 'Minimalista';
$string['theme_dark'] = 'Oscuro';
$string['setting_welcome'] = 'Mensaje de bienvenida';
$string['setting_welcome_desc'] = 'Mensaje mostrado la primera vez que se abre el chatbot. Usa {name} para personalizar con el nombre de la persona.';
$string['setting_nomatch'] = 'Respuesta por defecto';
$string['setting_nomatch_desc'] = 'Mensaje enviado cuando el chatbot no reconoce la pregunta.';
$string['setting_maxlength'] = 'Longitud máxima del mensaje';
$string['setting_maxlength_desc'] = 'Limita el número de caracteres que puede enviar el usuario.';
$string['setting_allow_export'] = 'Permitir exportar conversaciones';
$string['setting_allow_export_desc'] = 'Muestra el botón de exportar cuando el usuario tiene el permiso correspondiente.';

// Gestión de intenciones.
$string['manage_intents'] = 'Gestionar intenciones';
$string['intents_intro'] = 'Administra las intenciones que alimentan el clasificador del chatbot. Las palabras clave se evalúan en orden y una intención puede actuar como fallback global.';
$string['intent_name'] = 'Nombre de la intención';
$string['intent_keywords'] = 'Palabras clave';
$string['intent_keywords_help'] = 'Introduce una palabra clave por línea. La coincidencia no distingue mayúsculas de minúsculas.';
$string['intent_response'] = 'Respuesta a enviar';
$string['intent_fallback'] = 'Usar como intención por defecto';
$string['intent_fallback_help'] = 'Si está activado esta respuesta se devolverá cuando ninguna otra intención coincida.';
$string['intent_enabled'] = 'Habilitada';
$string['intent_sortorder'] = 'Orden';
$string['intent_status'] = 'Estado';
$string['intent_status_fallback'] = 'Fallback';
$string['intent_no_keywords'] = 'Sin palabras clave';
$string['intent_keywords_required'] = 'Proporciona al menos una palabra clave o marca la intención como fallback.';
$string['intent_unknown'] = 'Sin clasificar';
$string['intent_edit_heading'] = 'Editar intención';
$string['intent_add_heading'] = 'Nueva intención';
$string['intent_table_heading'] = 'Intenciones configuradas';
$string['intent_saved'] = 'La intención se guardó correctamente.';
$string['intent_deleted'] = 'La intención se eliminó.';
$string['intent_delete_confirm'] = '¿Seguro que deseas eliminar la intención "{$a}"?';

// Entidades (accesos rápidos y sugerencias).
$string['manage_entities'] = 'Gestionar entidades';
$string['entities_intro'] = 'Configura los accesos rápidos y sugerencias que ve el usuario en el widget.';
$string['entities_quickactions_tab'] = 'Accesos rápidos';
$string['entities_suggestions_tab'] = 'Sugerencias';
$string['quickaction_actionkey'] = 'Clave de acción';
$string['quickaction_name'] = 'Etiqueta';
$string['quickaction_type'] = 'Tipo de acción';
$string['quickaction_type_help'] = 'Define si la acción navega a una página, rellena el cuadro de texto o responde desde el servidor.';
$string['quickaction_type_navigate'] = 'Navegar a una página';
$string['quickaction_type_inject'] = 'Prefijar un mensaje';
$string['quickaction_type_server'] = 'Respuesta del servidor';
$string['quickaction_payload'] = 'Contenido';
$string['quickaction_payload_help'] = 'Para navegar usa rutas relativas como /user/profile.php?id={userid}. Para los otros tipos ingresa el texto a mostrar.';
$string['quickaction_description'] = 'Descripción';
$string['quickaction_icon'] = 'Icono / emoji';
$string['quickaction_icon_help'] = 'Opcional: emoji o texto corto usado como icono en el widget.';
$string['quickaction_enabled'] = 'Habilitada';
$string['quickaction_sortorder'] = 'Orden';
$string['quickaction_status'] = 'Estado';
$string['quickaction_payload_required'] = 'Debes indicar una URL o ruta para las acciones de navegación.';
$string['quickaction_payload_text_required'] = 'Debes indicar el texto que enviará esta acción.';
$string['quickaction_actionkey_unique'] = 'La clave de acción debe ser única.';
$string['quickaction_edit_heading'] = 'Editar acceso rápido';
$string['quickaction_add_heading'] = 'Nuevo acceso rápido';
$string['quickaction_table_heading'] = 'Accesos rápidos configurados';
$string['quickaction_saved'] = 'Acceso rápido guardado.';
$string['quickaction_deleted'] = 'Acceso rápido eliminado.';
$string['quickaction_delete_confirm'] = '¿Eliminar el acceso rápido "{$a}"?';

$string['suggestion_text'] = 'Texto de la sugerencia';
$string['suggestion_mode'] = 'Comportamiento';
$string['suggestion_mode_help'] = 'Las sugerencias tipo mensaje rellenan el cuadro de texto; las de tipo acción ejecutan un acceso rápido.';
$string['suggestion_mode_message'] = 'Enviar mensaje';
$string['suggestion_mode_action'] = 'Ejecutar acceso rápido';
$string['suggestion_target'] = 'Destino / clave de acción';
$string['suggestion_target_help'] = 'Para sugerencias de acción indica la clave del acceso rápido a ejecutar. Para mensajes puede quedar vacío.';
$string['suggestion_icon'] = 'Icono / emoji';
$string['suggestion_enabled'] = 'Habilitada';
$string['suggestion_sortorder'] = 'Orden';
$string['suggestion_status'] = 'Estado';
$string['suggestion_target_required'] = 'Selecciona la acción a ejecutar cuando el modo sea “Ejecutar acceso rápido”.';
$string['suggestion_edit_heading'] = 'Editar sugerencia';
$string['suggestion_add_heading'] = 'Nueva sugerencia';
$string['suggestion_table_heading'] = 'Sugerencias configuradas';
$string['suggestion_saved'] = 'Sugerencia guardada.';
$string['suggestion_deleted'] = 'Sugerencia eliminada.';
$string['suggestion_delete_confirm'] = '¿Eliminar la sugerencia "{$a}"?';

// Consola de entrenamiento.
$string['training'] = 'Entrenamiento y aprendizaje';
$string['training_intro'] = 'Prueba mensajes y revisa cómo responden las intenciones sin salir de Moodle.';
$string['training_message'] = 'Mensaje a analizar';
$string['training_logmessage'] = 'Guardar en el historial';
$string['training_logmessage_help'] = 'Si está activado el mensaje se almacenará en la tabla de conversaciones como si llegara desde el widget.';
$string['training_sessionid'] = 'Identificador de sesión';
$string['training_sessionid_help'] = 'Introduce un identificador para continuar una conversación existente o déjalo vacío para crear una nueva sesión.';
$string['training_run'] = 'Analizar mensaje';
$string['training_logged'] = 'Guardado en el historial';
$string['training_preview'] = 'Solo vista previa';
$string['training_result_heading'] = 'Resultado del análisis';
$string['training_result_status'] = 'Modo: {$a}';
$string['training_result_intent'] = 'Intención detectada: {$a}';
$string['training_result_response'] = 'Respuesta: {$a}';
$string['training_result_session'] = 'Sesión: {$a}';
$string['training_result_keywords'] = 'Palabras clave consideradas: {$a}';
$string['training_context_heading'] = 'Contexto del widget';
$string['training_context_suggestions'] = 'Sugerencias visibles:';
$string['training_context_actions'] = 'Accesos rápidos disponibles:';
$string['training_history_heading'] = 'Mensajes recientes de esta sesión';
$string['training_result_time'] = 'Tiempo de respuesta: {$a}';

// Panel de analíticas.
$string['analytics'] = 'Analíticas e informes';
$string['analytics_intro'] = 'Monitorea el uso del chatbot para comprender su adopción en la plataforma.';
$string['analytics_total_messages'] = 'Mensajes registrados';
$string['analytics_total_sessions'] = 'Sesiones activas';
$string['analytics_total_users'] = 'Usuarios únicos';
$string['analytics_average_response'] = 'Tiempo de respuesta promedio';
$string['analytics_intents_heading'] = 'Uso de intenciones';
$string['analytics_messages'] = 'Mensajes';
$string['analytics_intents_chart_title'] = 'Mensajes por intención';
$string['analytics_activity_chart_title'] = 'Mensajes por día';
$string['analytics_activity_heading'] = 'Actividad reciente';
$string['analytics_feedback_heading'] = 'Distribución de retroalimentación';
$string['analytics_feedback_unknown'] = 'Sin especificar';

// Visualizador de diálogos.
$string['dialogues'] = 'Flujos de diálogo';
$string['dialogue_filter_session'] = 'Id. de sesión';
$string['dialogue_filter_userid'] = 'Id. de usuario';
$string['dialogue_filter_intent'] = 'Intención';
$string['dialogue_filter_from'] = 'Desde';
$string['dialogue_filter_to'] = 'Hasta';
$string['dialogue_filter_feedback'] = 'Solo mensajes con retroalimentación';
$string['dialogue_filter_apply'] = 'Aplicar filtros';
$string['dialogue_filter_reset'] = 'Restablecer';
$string['dialogue_detail_heading'] = 'Conversación de la sesión {$a}';
$string['dialogue_export'] = 'Exportar conversación';
$string['dialogue_no_records'] = 'No se encontraron conversaciones con los filtros indicados.';
$string['dialogue_session'] = 'Sesión';
$string['viewdetails'] = 'Ver detalles';

// Consola de pruebas manuales.
$string['test_chatbot'] = 'Probar el chatbot';
$string['test_intro'] = 'Envía mensajes como el usuario actual para verificar coincidencias, respuestas y exportaciones. Los mensajes se guardan en el historial principal.';
$string['test_message'] = 'Mensaje';
$string['test_sessionid'] = 'Id. de sesión (opcional)';
$string['test_sessionid_help'] = 'Introduce un identificador personalizado para reutilizar una conversación existente. Déjalo vacío para generar uno nuevo.';
$string['test_send'] = 'Enviar mensaje';
$string['test_result_heading'] = 'Respuesta del servidor';
$string['test_result_response'] = 'Respuesta: {$a}';
$string['test_result_intent'] = 'Intención: {$a}';
$string['test_result_session'] = 'Sesión: {$a}';
$string['test_result_logid'] = 'Registro: {$a}';
$string['test_result_time'] = 'Tiempo de respuesta: {$a}';
$string['test_suggestions_heading'] = 'Sugerencias actuales';
$string['test_quickactions_heading'] = 'Resumen de accesos rápidos';
$string['test_history_heading'] = 'Historial de la conversación';

// Botones de retroalimentación.
$string['chatbot_feedback_helpful'] = 'Útil';
$string['chatbot_feedback_not_helpful'] = 'No útil';
$string['chatbot_feedback_thanks'] = '¡Gracias por tu comentario!';

// Cadenas heredadas (compatibilidad).
$string['admin_placeholder'] = 'El panel de gestión está en construcción. El widget y los servicios ya están disponibles.';
$string['admin_placeholder_help'] = 'Mientras tanto utiliza la página de configuración en Administración del sitio → Plugins → Plugins locales → Asistente de chatbot.';
$string['analytics_and_reports'] = 'Analíticas e informes';
