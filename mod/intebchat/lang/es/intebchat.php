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
 * Spanish language strings for intebchat
 *
 * @package    mod_intebchat
 * @copyright  2025 Alonso Arias <soporte@ingeweb.co>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

// General strings
$string['pluginname'] = 'INTEB Chat';
$string['modulename'] = 'INTEB Chat';
$string['modulenameplural'] = 'INTEB Chats';
$string['intebchat'] = 'intebchat';
$string['intebchatname'] = 'Nombre del chat';
$string['intebchatname_help'] = 'Este es el nombre que aparecerá en la página del curso.';
$string['intebchat:view'] = 'Ver INTEB Chat';
$string['intebchat:addinstance'] = 'Agregar un nuevo INTEB Chat';
$string['intebchat:viewownconversations'] = 'Ver conversaciones propias';
$string['intebchat:viewstudentconversations'] = 'Ver conversaciones de estudiantes';
$string['intebchat:viewallconversations'] = 'Ver todas las conversaciones';
$string['intebchat:managetokenlimits'] = 'Gestionar límites de tokens';
$string['pluginadministration'] = 'Administración de INTEB Chat';
$string['noopenaichats'] = 'No hay INTEB Chats en este curso';

// Conversation management
$string['newconversation'] = 'Nueva conversación';
$string['conversations'] = 'Conversaciones';
$string['clearconversation'] = 'Limpiar conversación';
$string['edittitle'] = 'Editar título';
$string['conversationtitle'] = 'Título de la conversación';
$string['searchconversations'] = 'Buscar conversaciones...';
$string['noconversations'] = 'No hay conversaciones aún';
$string['confirmclear'] = '¿Estás seguro de que deseas limpiar esta conversación?';
$string['confirmclearmessage'] = '¿Estás seguro de que deseas limpiar esta conversación? Esta acción no se puede deshacer.';
$string['conversationcleared'] = 'Conversación limpiada';
$string['loadingconversation'] = 'Cargando conversación...';
$string['defaultconversation'] = 'Conversación migrada';
$string['errorloadingconversation'] = 'Error al cargar la conversación';
$string['errorclearingconversation'] = 'Error al limpiar la conversación';
$string['conversationtitleupdated'] = 'Título actualizado exitosamente';

// Settings strings
$string['generalsettings'] = 'Configuración general';
$string['generalsettingsdesc'] = 'Estas configuraciones aplican a todas las instancias de INTEB Chat en el sitio.';
$string['apikey'] = 'Clave API de OpenAI';
$string['apikeydesc'] = 'La clave API de tu cuenta de OpenAI';
$string['apikeymissing'] = 'Por favor configura tu clave API de OpenAI en la configuración del plugin.';
$string['type'] = 'Tipo de API';
$string['typedesc'] = 'Qué API de OpenAI usar';
$string['assistant'] = 'Asistente';
$string['assistantdesc'] = 'El asistente a usar si estás usando la API de Asistentes';
$string['chatcompletions'] = 'Chat Completions';
$string['persistconvo'] = 'Persistir conversación';
$string['persistconvodesc'] = 'Persistir la conversación del usuario entre sesiones';
$string['noassistants'] = 'No hay asistentes disponibles. Por favor crea uno en tu cuenta de OpenAI.';

// Instance settings
$string['chatsettings'] = 'Configuración del chat';
$string['showlabels'] = 'Mostrar etiquetas de nombre';
$string['sourceoftruth'] = 'Fuente de verdad';
$string['config_sourceoftruth'] = 'Información que la IA debe usar como base para sus respuestas';
$string['config_sourceoftruth_help'] = 'Proporciona información específica que la IA debe considerar como factual y priorizar en sus respuestas. Esto ayuda a garantizar respuestas precisas y consistentes.';
$string['prompt'] = 'Prompt personalizado';
$string['config_prompt'] = 'Instrucciones adicionales para personalizar el comportamiento de la IA';
$string['config_prompt_help'] = 'Proporciona instrucciones específicas para guiar cómo debe responder la IA. Esto se agregará al prompt del sistema.';
$string['instructions'] = 'Instrucciones';
$string['config_instructions'] = 'Instrucciones personalizadas para el asistente';
$string['config_instructions_help'] = 'Proporciona instrucciones específicas para el asistente. Estas instrucciones anularán las instrucciones predeterminadas del asistente para esta instancia.';
$string['assistantname'] = 'Nombre del asistente';
$string['config_assistantname'] = 'Cómo se mostrará el nombre del asistente en el chat';
$string['config_assistantname_help'] = 'Ingresa un nombre personalizado para el asistente. Este nombre se mostrará en la interfaz del chat en lugar del nombre predeterminado.';
$string['advanced'] = 'Configuración avanzada';

// Help strings for instance settings
$string['config_assistant'] = 'Asistente';
$string['config_assistant_help'] = 'Selecciona qué asistente usar de tu cuenta de OpenAI. Debes haber creado asistentes en tu cuenta de OpenAI primero.';
$string['config_persistconvo'] = 'Persistir conversación';
$string['config_persistconvo_help'] = 'Si está habilitado, el historial de conversación se mantendrá entre sesiones. Los usuarios pueden continuar conversaciones anteriores.';
$string['config_apikey'] = 'Clave API (nivel de instancia)';
$string['config_apikey_help'] = 'Opcionalmente proporciona una clave API específica de la instancia. Esto anulará la clave API global solo para esta instancia.';

// Model settings
$string['model'] = 'Modelo';
$string['config_model'] = 'Qué modelo de OpenAI usar';
$string['config_model_help'] = 'Selecciona el modelo de IA. Diferentes modelos tienen diferentes capacidades y costos. Los modelos GPT-4 son más capaces pero más caros.';
$string['temperature'] = 'Temperatura';
$string['config_temperature'] = 'Controla la aleatoriedad (0-2)';
$string['config_temperature_help'] = 'Controla la aleatoriedad de las respuestas. Valores más bajos (0.0-0.5) hacen las respuestas más enfocadas y deterministas. Valores más altos (0.5-2.0) hacen las respuestas más creativas y variadas.';
$string['maxlength'] = 'Longitud máxima';
$string['config_maxlength'] = 'Número máximo de tokens en la respuesta';
$string['config_maxlength_help'] = 'El número máximo de tokens a generar en la respuesta. Un token es aproximadamente 4 caracteres. Rango: 1-4000.';
$string['topp'] = 'Top P';
$string['config_topp'] = 'Muestreo del núcleo (0-1)';
$string['config_topp_help'] = 'Una alternativa al muestreo por temperatura. El modelo considera tokens con masa de probabilidad top_p. 0.1 significa que solo se consideran los tokens del 10% superior de probabilidad.';
$string['frequency'] = 'Penalización de frecuencia';
$string['config_frequency'] = 'Reduce la repetición de tokens (-2 a 2)';
$string['config_frequency_help'] = 'Los valores positivos penalizan los nuevos tokens según su frecuencia existente en el texto, disminuyendo la probabilidad de repetir la misma línea.';
$string['presence'] = 'Penalización de presencia';
$string['config_presence'] = 'Reduce la repetición de temas (-2 a 2)';
$string['config_presence_help'] = 'Los valores positivos penalizan los nuevos tokens según si aparecen en el texto hasta ahora, aumentando la probabilidad de hablar sobre nuevos temas.';

// Token limit settings
$string['tokenlimitsettings'] = 'Configuración de límite de tokens';
$string['tokenlimitsettingsdesc'] = 'Controla el uso de tokens por los usuarios';
$string['enabletokenlimit'] = 'Habilitar límite de tokens';
$string['enabletokenlimitdesc'] = 'Limitar el número de tokens que los usuarios pueden usar';
$string['maxtokensperuser'] = 'Máximo de tokens por usuario';
$string['maxtokensperuserdesc'] = 'Número máximo de tokens que un usuario puede usar en el período especificado';
$string['tokenlimitperiod'] = 'Período del límite de tokens';
$string['tokenlimitperioddesc'] = 'El período de tiempo para el límite de tokens';
$string['tokensused'] = 'Tokens usados: {$a->used} / {$a->limit}';
$string['tokensusedformat'] = 'Tokens usados: {used} / {limit}';
$string['tokenlimitexceeded'] = 'Has excedido tu límite de tokens. Usado: {$a->used}, Límite: {$a->limit}. Se restablece en: {$a->reset}';
$string['totaltokensused'] = 'Total de tokens usados: {$a}';
$string['tokensreset'] = 'Los tokens se restablecerán en: {$a}';
$string['tokensresetcountdown'] = 'Se restablece en: {$a}';
$string['audiotokens'] = 'Audio';
$string['texttokens'] = 'Texto';
$string['totaltokensdetailed'] = 'Tokens totales - Texto: {$a->text}, Audio: {$a->audio}';
$string['tokenlimitwarning'] = '¡Solo quedan {$a} tokens!';

// Messages
$string['askaquestion'] = 'Haz una pregunta...';
$string['erroroccurred'] = '¡Ocurrió un error! Por favor intenta de nuevo más tarde.';
$string['new_chat'] = 'Nuevo chat';
$string['loggingenabled'] = 'El registro está habilitado - tus conversaciones se guardarán';
$string['messagecount'] = 'Número de mensajes: {$a}';
$string['firstmessage'] = 'Primer mensaje';
$string['lastmessage'] = 'Último mensaje';
$string['nomessages'] = 'No hay mensajes';
$string['messages'] = 'Mensajes';
$string['created'] = 'Creado';
$string['transcribing'] = 'Transcribiendo...';

// Audio mode strings
$string['enableaudio'] = 'Habilitar audio';
$string['enableaudio_help'] = 'Habilitar características de audio para esta instancia de chat';
$string['enableaudio_desc'] = 'Permitir grabación y respuestas de audio';
$string['audiomode'] = 'Modo';
$string['audiomode_help'] = 'Selecciona cómo los usuarios pueden interactuar con el chat: Solo texto, Solo audio, o Texto y audio';
$string['audiomode_text'] = 'Solo texto';
$string['audiomode_audio'] = 'Solo audio';
$string['audiomode_both'] = 'Texto y audio';
$string['voice'] = 'Voz para respuestas de audio';
$string['voice_desc'] = 'Selecciona la voz predeterminada para las respuestas de texto a voz. Esto puede ser anulado a nivel de instancia.';
$string['voice_help'] = 'Selecciona la voz para las respuestas de texto a voz en esta instancia del chat. Cada voz tiene características diferentes:
• Alloy: Neutral y profesional
• Ash: Serena y moderna
• Ballad: Melódica y narrativa
• Coral: Brillante y nítida
• Echo: Cálida y conversacional
• Fable: Expresiva y dinámica
• Onyx: Profunda y autoritaria
• Nova: Energética y brillante
• Sage: Serena y reflexiva
• Shimmer: Suave y tranquilizadora
• Verse: Poética y rítmica

Esta configuración anula la voz predeterminada global para esta instancia específica del chat.';
$string['recordaudio'] = 'Grabar audio';
$string['stoprecording'] = 'Detener grabación';
$string['switchtoaudiomode'] = 'Cambiar a modo de audio';
$string['switchtotextmode'] = 'Cambiar a modo de texto';
$string['inputmode'] = 'Modo de entrada';
$string['responsemode'] = 'Modo de respuesta';
$string['audioresponse'] = 'Respuesta de audio';
$string['textresponse'] = 'Respuesta de texto';

// Theme strings
$string['switchtheme'] = 'Cambiar tema';
$string['darkmode'] = 'Modo oscuro';
$string['lightmode'] = 'Modo claro';

// Default strings
$string['defaultassistantname'] = 'Asistente';
$string['defaultusername'] = 'Usuario';
$string['defaultprompt'] = 'Eres un asistente útil.';
$string['sourceoftruthpreamble'] = 'Se te ha proporcionado la siguiente información como contexto:';
$string['sourceoftruthreinforcement'] = ' En tus respuestas, prioriza siempre la información proporcionada en el contexto.';

// Validation messages
$string['temperaturerange'] = 'La temperatura debe estar entre 0 y 2';
$string['topprange'] = 'Top P debe estar entre 0 y 1';
$string['maxlengthrange'] = 'La longitud máxima debe estar entre 1 y 4000';
$string['reasoningmodelwarning'] = 'Has seleccionado el modelo "{$a}". Este es un modelo de razonamiento avanzado diseñado para resolver problemas complejos mediante un análisis paso a paso. Ten en cuenta que puede tener un mayor costo y tiempo de respuesta.';

// Other settings
$string['restrictusage'] = 'Restringir a usuarios autenticados';
$string['restrictusagedesc'] = 'Solo usuarios autenticados pueden usar el chat';
$string['logging'] = 'Registrar conversaciones';
$string['loggingdesc'] = 'Registrar todas las conversaciones para análisis posterior';
$string['allowinstancesettings'] = 'Permitir configuración por instancia';
$string['allowinstancesettingsdesc'] = 'Permitir que los profesores anulen la configuración global en instancias individuales';
$string['defaultvalues'] = 'Valores predeterminados';
$string['defaultvaluesdesc'] = 'Valores predeterminados para nuevas instancias';

// API specific headers
$string['chatheading'] = 'Configuración de Chat API';
$string['chatheadingdesc'] = 'Configuración para la API de Chat';
$string['assistantheading'] = 'Configuración de Assistant API';
$string['assistantheadingdesc'] = 'Configuración para la API de Asistente';

// Privacy
$string['privacy:metadata:intebchat_log'] = 'Registros de conversaciones de INTEB Chat';
$string['privacy:metadata:intebchat_log:userid'] = 'El ID del usuario que envió el mensaje';
$string['privacy:metadata:intebchat_log:instanceid'] = 'El ID de la instancia del módulo';
$string['privacy:metadata:intebchat_log:usermessage'] = 'El mensaje enviado por el usuario';
$string['privacy:metadata:intebchat_log:airesponse'] = 'La respuesta generada por la IA';
$string['privacy:metadata:intebchat_log:timecreated'] = 'El momento en que se envió el mensaje';
$string['privacy:chatmessagespath'] = 'Mensajes del chat';

// OpenAI specific
$string['openaitimedout'] = 'La solicitud a OpenAI ha excedido el tiempo de espera. Por favor intenta de nuevo.';

// Report strings
$string['intebchat_logs'] = 'Registros de INTEB Chat';
$string['viewreport'] = 'Ver informe';
$string['viewallreports'] = 'Ver todos los informes';
$string['userid'] = 'ID de usuario';
$string['username'] = 'Nombre de usuario';
$string['usermessage'] = 'Mensaje del usuario';
$string['airesponse'] = 'Respuesta de IA';
$string['tokens'] = 'Tokens';
$string['prompt'] = 'Prompt';
$string['completion'] = 'Completado';
$string['nopermission'] = 'No tienes permiso para ver esto';

// Cadenas de confirmación de audio
$string['audiorecorded'] = 'Audio Grabado';
$string['confirmaudiosend'] = '¿Deseas enviar este mensaje de audio?';
$string['playaudio'] = 'Reproducir Audio';
$string['rerecord'] = 'Volver a grabar';
$string['send'] = 'Enviar';
$string['cancel'] = 'Cancelar';
$string['cancelrecording'] = 'Cancelar Grabación';
$string['audioduration'] = 'Duración: {$a}';
$string['confirmdelete'] = '¿Estás seguro de que deseas eliminar esta grabación?';

// Cadenas de modo conversacional
$string['audiomode_conversacional'] = 'Conversacional (Tiempo real)';
$string['audiomode_conversacional_help'] = 'Modo conversacional en tiempo real usando WebRTC para interacción natural por voz con transcripción bidireccional';
$string['audiomode_conversacional_assistant'] = 'Conversacional con Asistente (Tiempo real)';
$string['audiomode_conversacional_assistant_help'] = 'Conversación por voz en tiempo real que delega consultas complejas a tu Asistente de OpenAI configurado. Combina la experiencia de voz natural de la API de Realtime con las capacidades de tu Asistente.';
$string['conversacional_not_with_assistant'] = 'El modo conversacional no está disponible cuando se usa la API de Assistant. Por favor, usa el modo "Conversacional con Asistente".';
$string['conversacional_assistant_only'] = 'El modo Conversacional con Asistente solo está disponible cuando se usa la API de Assistant. Por favor, usa el modo "Conversacional" estándar.';
$string['conversacional_warning'] = 'Este modo usa la API de Realtime de OpenAI para conversación por voz en tiempo real. NO usa tu Asistente de OpenAI configurado. La IA responderá usando instrucciones genéricas, no la base de conocimiento de tu asistente entrenado.';
$string['conversacional_assistant_info'] = 'Este modo usa la API de Realtime de OpenAI para interacción por voz, pero delega consultas complejas a tu Asistente de OpenAI configurado. La IA consultará a tu asistente entrenado cuando se necesite conocimiento especializado.';
$string['realtime_mic_start'] = 'Haz clic para comenzar a hablar';
$string['realtime_mic_enabled'] = 'Micrófono activo - habla ahora';
$string['realtime_assistant_thinking'] = 'Consultando al asistente...';
$string['realtime_connecting'] = 'Conectando al servicio en tiempo real...';
$string['realtime_connected'] = 'Conectado - Habla naturalmente';
$string['realtime_disconnected'] = 'Desconectado del servicio en tiempo real';
$string['realtime_error'] = 'Error al conectar con el servicio en tiempo real';
$string['realtime_you_speaking'] = 'Estás hablando...';
$string['realtime_ai_speaking'] = 'La IA está hablando...';
$string['realtime_listening'] = 'Escuchando...';
$string['realtime_processing'] = 'Procesando...';
$string['transcribing'] = 'Transcribiendo...';

// Cadenas de seguridad
$string['invalidinput'] = 'Entrada inválida detectada';
$string['inputtoolong'] = 'La entrada excede la longitud máxima permitida';
$string['invalidrole'] = 'Rol de mensaje inválido especificado';

// Cadenas de mascota
$string['mascot'] = 'Mascota del chat';
$string['mascot_help'] = 'Selecciona el personaje animado que aparecerá en la interfaz del chat para representar al asistente de IA.';
$string['mascot_assistant'] = 'Asistente INTEB';
$string['mascot_robot'] = 'Robot';
$string['mascot_cat'] = 'Gato';
$string['mascot_owl'] = 'Búho';
$string['mascot_clippy'] = 'Clippy';
$string['mascot_lightbulb'] = 'Bombilla';

// Cadenas de animaciones de mascota
$string['assistant'] = 'Asistente';
$string['thinking'] = 'Pensando...';
$string['mascothelp'] = '¿En qué puedo ayudarte?';
$string['mascotneedmore'] = '¿Necesitas algo más?';
$string['mascotconfused'] = 'Algo salió mal...';
$string['mascotgreeting'] = '¡Hola! Estoy aquí para ayudarte.';
$string['mascotlistening'] = 'Te escucho...';

// Cadenas de seguridad y validación
$string['nosaltconfigured'] = 'No hay salt secreto configurado en Moodle. Por favor contacta a tu administrador.';
$string['cryptoerror'] = 'Error de encriptación/desencriptación. Por favor contacta a tu administrador.';
$string['audiofiletoolarge'] = 'El archivo de audio es muy grande ({$a->size}). El tamaño máximo permitido es {$a->max}.';
$string['audiofiletoosmall'] = 'El archivo de audio es muy pequeño o está vacío.';
$string['invalidaudioformat'] = 'Formato de audio inválido: {$a}. Formatos permitidos: mp3, mp4, wav, webm, ogg.';
$string['invalidaudiodata'] = 'Datos de audio inválidos o corruptos.';
$string['ratelimitexceeded'] = 'Demasiadas solicitudes. Por favor espera un momento antes de intentar de nuevo.';

// Configuración de limitación de solicitudes
$string['enableratelimit'] = 'Habilitar limitación de solicitudes';
$string['enableratelimitdesc'] = 'Habilita la limitación de solicitudes para prevenir abuso de la API. Limita las solicitudes por minuto por usuario.';
$string['ratelimit_user'] = 'Límite por usuario';
$string['ratelimit_userdesc'] = 'Máximo de solicitudes por minuto por usuario (predeterminado: 60).';
$string['ratelimit_ip'] = 'Límite por IP';
$string['ratelimit_ipdesc'] = 'Máximo de solicitudes por minuto por dirección IP (predeterminado: 30).';

// Cadenas de analíticas
$string['analytics'] = 'Analíticas';
$string['intebchat:viewanalytics'] = 'Ver panel de analíticas';
$string['backtochat'] = 'Volver al chat';
$string['period'] = 'Período';
$string['period_day'] = 'Hoy';
$string['period_week'] = 'Últimos 7 días';
$string['period_month'] = 'Últimos 30 días';
$string['period_all'] = 'Todo el tiempo';
$string['analytics_overview'] = 'Resumen';
$string['messages'] = 'Mensajes';
$string['tokens'] = 'Tokens';
$string['users'] = 'Usuarios';
$string['user'] = 'Usuario';
$string['email'] = 'Correo electrónico';
$string['actions'] = 'Acciones';
$string['courses'] = 'Cursos';
$string['avg_messages_per_user'] = 'Prom/usuario';
$string['avg_tokens_per_message'] = 'Prom/msg';
$string['top_users'] = 'Usuarios Destacados';
$string['usage'] = 'Uso';
$string['daily_activity'] = 'Actividad Diaria';
$string['nodata'] = 'No hay datos disponibles para este período';

// Cadenas de modo offline/cola
$string['offlinemode'] = 'Sin conexión - los mensajes serán encolados';
$string['messagequeued'] = 'Mensaje en cola - se enviará cuando haya conexión';
$string['messagesendfailed'] = 'El mensaje no pudo ser enviado después de varios intentos';
$string['sendingqueued'] = 'Enviando mensaje en cola...';

// Configuración de retención
$string['retentionsettings'] = 'Retención de conversaciones';
$string['retentionsettingsdesc'] = 'Configura la limpieza automática de conversaciones antiguas para gestionar el almacenamiento y la privacidad.';
$string['enableretention'] = 'Habilitar limpieza automática';
$string['enableretentiondesc'] = 'Eliminar automáticamente las conversaciones que han estado inactivas durante el período especificado.';
$string['retentiondays'] = 'Período de retención (días)';
$string['retentiondaysdesc'] = 'Número de días para mantener las conversaciones inactivas. Las conversaciones sin actividad durante este período serán eliminadas.';
$string['cleanupconversations'] = 'Limpiar conversaciones antiguas';
$string['conversationsdeleted'] = '{$a} conversaciones eliminadas';
$string['noconversationstoclean'] = 'No hay conversaciones para limpiar';

// Cadenas de reporte del sitio
$string['sitereport'] = 'Reporte de INTEB Chat del Sitio';
$string['intebchatreport'] = 'Reporte de Uso de INTEB Chat';
$string['intebchat:viewsitereport'] = 'Ver reportes de INTEB Chat a nivel de sitio';
$string['overview'] = 'Resumen';
$string['usagebycourse'] = 'Uso por Curso';
$string['usagebyuser'] = 'Uso por Usuario';
$string['usagebyinstance'] = 'Uso por Instancia';
$string['period_year'] = 'Últimos 12 meses';
$string['exportcsv'] = 'Exportar CSV';
$string['totalmessages'] = 'Mensajes Totales';
$string['totalusers'] = 'Usuarios Totales';
$string['totalcourses'] = 'Cursos Totales';
$string['totaltokens'] = 'Tokens Totales';
$string['totalconversations'] = 'Conversaciones Totales';
$string['tokenbreakdown'] = 'Desglose de Tokens';
$string['prompttokens'] = 'Tokens de Prompt';
$string['completiontokens'] = 'Tokens de Completado';
$string['averages'] = 'Promedios';
$string['avgtokensperuser'] = 'Tokens Promedio por Usuario';
$string['avgtokenspermessage'] = 'Tokens Promedio por Mensaje';
$string['avgmessagesperuser'] = 'Mensajes Promedio por Usuario';
$string['topcourses'] = 'Cursos Destacados';
$string['instances'] = 'Instancias';
$string['viewusers'] = 'Ver Usuarios';
$string['viewdetails'] = 'Ver Detalles';
$string['filteringbycourse'] = 'Filtrando por curso: {$a}';
$string['clearfilter'] = 'Limpiar filtro';
$string['instance'] = 'Instancia';
$string['allinstances'] = 'Todas las instancias';
$string['noinstancesincourse'] = 'No hay instancias de INTEB Chat en este curso.';
$string['lastactivity'] = 'Última Actividad';
$string['courses'] = 'Cursos';

// Carga dinámica de asistentes
$string['noassistantsfound'] = 'No se encontraron asistentes';
$string['loadingassistants'] = 'Cargando asistentes...';
$string['failedtoloadassistants'] = 'Error al obtener asistentes';

// Notificación de modelo de razonamiento
$string['reasoningmodelinfo'] = 'Has seleccionado el modelo "{$a}". Este es un modelo de razonamiento avanzado diseñado para resolver problemas complejos mediante un análisis paso a paso. Ten en cuenta que puede tener un mayor costo y tiempo de respuesta.';

// Additional UI strings
$string['recording'] = 'Grabando...';
$string['browsernotsupported'] = 'Tu navegador no soporta grabación de audio';
$string['recordingerror'] = 'Error durante la grabación: {$a}';
$string['microphoneerror'] = 'Error al acceder al micrófono: {$a}';
$string['tokensresetin'] = 'Se reinicia en: {$a}';
$string['required'] = 'Requerido';
$string['unknownerror'] = 'Error desconocido';
$string['transcriptionfailed'] = 'Error en la transcripción de audio: {$a}';