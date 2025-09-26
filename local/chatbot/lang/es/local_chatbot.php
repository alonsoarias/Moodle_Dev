<?php
// Este archivo forma parte de Moodle - http://moodle.org/
//
// Moodle es software libre: puedes redistribuirlo y/o modificarlo
// bajo los términos de la Licencia Pública General de GNU publicada por
// la Free Software Foundation, ya sea la versión 3 de la licencia o
// (a tu elección) cualquier versión posterior.
//
// Moodle se distribuye con la esperanza de que sea útil,
// pero SIN NINGUNA GARANTÍA; ni siquiera la garantía implícita
// de COMERCIABILIDAD o IDONEIDAD PARA UN PROPÓSITO PARTICULAR.  Véase la
// Licencia Pública General de GNU para más detalles.
//
// Deberías haber recibido una copia de la Licencia Pública General de GNU
// junto con Moodle.  Si no, consulta <http://www.gnu.org/licenses/>.

/**
 * Cadenas en español para el plugin local_chatbot.
 *
 * @package    local_chatbot
 * @copyright  2024 Moodle Community
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 o posterior
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
$string['chatbot_error'] = 'Lo sentimos, ha ocurrido un error. Intenta nuevamente.';
$string['chatbot_typing_indicator'] = 'El asistente está escribiendo…';
$string['chatbot_status_online'] = 'En línea y listo para ayudar';
$string['chatbot_toggle_label'] = 'Hablar con {$a}';
$string['chatbot_voice_input_label'] = 'Entrada de voz';
$string['chatbot_emoji_picker_label'] = 'Selector de emojis';
$string['chatbot_export_conversation_label'] = 'Exportar conversación';
$string['chatbot_minimize'] = 'Minimizar';
$string['chatbot_close'] = 'Cerrar';
$string['chatbot_quick_actions_region'] = 'Accesos rápidos del chatbot';
$string['chatbot_suggestions_region'] = 'Sugerencias';
$string['chatbot_welcome_template'] = '¡Hola {name}! Estoy aquí para acompañarte en Moodle.';
$string['default_nomatch'] = 'Aún no tengo información sobre eso. ¿Podrías reformular tu pregunta?';

// Sugerencias y accesos rápidos.
$string['chatbot_suggestion_courses'] = '¿Dónde veo mis cursos?';
$string['chatbot_suggestion_grades'] = '¿Cómo consulto mis calificaciones?';
$string['chatbot_suggestion_support'] = 'Contactar soporte';
$string['chatbot_action_profile'] = 'Mi perfil';
$string['chatbot_action_profile_desc'] = 'Abre tu página de perfil';
$string['chatbot_action_calendar'] = 'Calendario';
$string['chatbot_action_calendar_desc'] = 'Abre la vista del calendario';
$string['chatbot_action_generic'] = 'Acción ejecutada: {$a}';

// Respuestas rápidas.
$string['chatbot_response_greeting'] = '¡Hola! 👋 ¿En qué puedo ayudarte hoy?';
$string['chatbot_response_help'] = 'Puedo ayudarte a encontrar cursos, calificaciones y enlaces útiles. Por ejemplo pregunta “¿Dónde veo mis calificaciones?”';
$string['chatbot_response_courses'] = 'Abre el menú “Mis cursos” en la parte superior para ver tus asignaturas inscritas.';
$string['chatbot_response_grades'] = 'Accede al reporte de calificaciones desde el menú de usuario o dentro de cada curso.';

// Exportación.
$string['chatbot_export_heading'] = 'Historial de la conversación';
$string['chatbot_export_empty'] = 'Todavía no se ha registrado ningún mensaje.';
$string['message'] = 'Mensaje';
$string['response'] = 'Respuesta';
$string['intent'] = 'Intención';
$string['feedback'] = 'Retroalimentación';

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
$string['setting_welcome_desc'] = 'Mensaje que se muestra al abrir el chatbot por primera vez.';
$string['setting_nomatch'] = 'Respuesta por defecto';
$string['setting_nomatch_desc'] = 'Mensaje enviado cuando el chatbot no reconoce la pregunta.';
$string['setting_maxlength'] = 'Longitud máxima del mensaje';
$string['setting_maxlength_desc'] = 'Limita el número de caracteres que puede enviar el usuario.';
$string['setting_allow_export'] = 'Permitir exportar conversaciones';
$string['setting_allow_export_desc'] = 'Muestra el botón de exportar cuando el usuario tiene el permiso correspondiente.';

// Pantallas administrativas provisionales.
$string['admin_placeholder'] = 'El panel de gestión está en construcción. El widget y los servicios ya están disponibles.';
$string['admin_placeholder_help'] = 'Mientras tanto utiliza la página de configuración en Administración del sitio → Plugins → Plugins locales → Asistente de chatbot.';
$string['manage_intents'] = 'Gestionar intenciones';
$string['manage_entities'] = 'Gestionar entidades';
$string['training'] = 'Entrenamiento y aprendizaje';
$string['analytics'] = 'Analíticas e informes';
$string['dialogues'] = 'Flujos de diálogo';
$string['test_chatbot'] = 'Probar el chatbot';

// Botones de retroalimentación.
$string['chatbot_feedback_helpful'] = 'Útil';
$string['chatbot_feedback_not_helpful'] = 'No útil';
$string['chatbot_feedback_thanks'] = '¡Gracias por tu comentario!';
