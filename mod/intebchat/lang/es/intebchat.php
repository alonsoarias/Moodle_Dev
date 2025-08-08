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
$string['tokenlimitexceeded'] = 'Has excedido tu límite de tokens. Usado: {$a->used}, Límite: {$a->limit}. Se restablece en: {$a->reset}';
$string['totaltokensused'] = 'Total de tokens usados: {$a}';
$string['tokensreset'] = 'Los tokens se restablecerán en: {$a}';
$string['audiotokens'] = 'Tokens de audio usados: {$a}';
$string['texttokens'] = 'Tokens de texto usados: {$a}';
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
$string['voice'] = 'Voz usada en la respuesta de audio';
$string['voice_desc'] = 'Selecciona la voz para las respuestas de texto a voz';
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