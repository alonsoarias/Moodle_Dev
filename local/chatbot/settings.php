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
 * Settings for intelligent chatbot
 *
 * @package    local_chatbot
 * @copyright  2025 Your Name
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) {
    
    // Create main category
    $ADMIN->add('localplugins', new admin_category('local_chatbot_settings', 
        get_string('pluginname', 'local_chatbot')));
    
    // General settings page
    $settings = new admin_settingpage('local_chatbot_general', 'Configuración General');
    
    // Enable/Disable chatbot
    $settings->add(new admin_setting_configcheckbox(
        'local_chatbot/enabled',
        'Habilitar Chatbot Inteligente',
        'Activa o desactiva el widget del chatbot en toda la plataforma',
        1
    ));
    
    // Widget position
    $positions = array(
        'bottom_right' => 'Abajo a la derecha',
        'bottom_left' => 'Abajo a la izquierda'
    );
    
    $settings->add(new admin_setting_configselect(
        'local_chatbot/position',
        'Posición del Widget',
        'Selecciona dónde aparecerá el widget del chatbot',
        'bottom_right',
        $positions
    ));
    
    // Theme selection
    $themes = array(
        'modern' => 'Moderno (Gradiente)',
        'minimal' => 'Minimalista',
        'dark' => 'Tema Oscuro',
        'colorful' => 'Colorido'
    );
    
    $settings->add(new admin_setting_configselect(
        'local_chatbot/theme',
        'Tema Visual',
        'Selecciona el tema visual del chatbot',
        'modern',
        $themes
    ));
    
    // Personality setting
    $personalities = array(
        'professional' => 'Profesional',
        'friendly' => 'Amigable',
        'casual' => 'Casual',
        'formal' => 'Formal'
    );
    
    $settings->add(new admin_setting_configselect(
        'local_chatbot/personality',
        'Personalidad del Chatbot',
        'Define el tono de las respuestas del chatbot',
        'professional',
        $personalities
    ));
    
    $ADMIN->add('local_chatbot_settings', $settings);
    
    // Intelligence settings page
    $intelligence = new admin_settingpage('local_chatbot_intelligence', 'Configuración de Inteligencia');
    
    // Confidence threshold
    $intelligence->add(new admin_setting_configtext(
        'local_chatbot/confidence_threshold',
        'Umbral de Confianza',
        'Puntuación mínima de confianza para aceptar una intención (0-10)',
        '3',
        PARAM_FLOAT
    ));
    
    // Learning mode
    $intelligence->add(new admin_setting_configcheckbox(
        'local_chatbot/learning_enabled',
        'Modo de Aprendizaje',
        'Permite al chatbot aprender de las interacciones (requiere aprobación manual)',
        1
    ));
    
    // Context memory size
    $intelligence->add(new admin_setting_configtext(
        'local_chatbot/context_memory_size',
        'Tamaño de Memoria Contextual',
        'Número de interacciones previas a recordar en una sesión',
        '10',
        PARAM_INT
    ));
    
    // Fuzzy matching
    $intelligence->add(new admin_setting_configcheckbox(
        'local_chatbot/fuzzy_matching',
        'Coincidencia Difusa',
        'Habilita la coincidencia aproximada para errores ortográficos',
        1
    ));
    
    // Multi-language support
    $intelligence->add(new admin_setting_configcheckbox(
        'local_chatbot/multilingual',
        'Soporte Multiidioma',
        'Detecta y responde en el idioma del usuario',
        0
    ));
    
    $ADMIN->add('local_chatbot_settings', $intelligence);
    
    // Features settings page
    $features = new admin_settingpage('local_chatbot_features', 'Características');
    
    // Voice input
    $features->add(new admin_setting_configcheckbox(
        'local_chatbot/voice_input',
        'Entrada de Voz',
        'Permite a los usuarios hablar con el chatbot',
        1
    ));
    
    // Quick actions
    $features->add(new admin_setting_configcheckbox(
        'local_chatbot/quick_actions',
        'Acciones Rápidas',
        'Muestra botones de acciones rápidas contextuales',
        1
    ));
    
    // Suggestions
    $features->add(new admin_setting_configcheckbox(
        'local_chatbot/suggestions',
        'Sugerencias Inteligentes',
        'Muestra sugerencias basadas en el contexto',
        1
    ));
    
    // Emoji picker
    $features->add(new admin_setting_configcheckbox(
        'local_chatbot/emoji_picker',
        'Selector de Emojis',
        'Incluye un selector de emojis en el chat',
        1
    ));
    
    // Typing animation
    $features->add(new admin_setting_configcheckbox(
        'local_chatbot/typing_animation',
        'Animación de Escritura',
        'Simula escritura progresiva en las respuestas',
        0
    ));
    
    // Sound notifications
    $features->add(new admin_setting_configcheckbox(
        'local_chatbot/sound_notifications',
        'Notificaciones de Sonido',
        'Reproduce sonidos al recibir mensajes',
        0
    ));
    
    // Export conversations
    $features->add(new admin_setting_configcheckbox(
        'local_chatbot/allow_export',
        'Permitir Exportar Conversaciones',
        'Los usuarios pueden exportar sus conversaciones',
        1
    ));
    
    $ADMIN->add('local_chatbot_settings', $features);
    
    // Response management page
    $responses = new admin_settingpage('local_chatbot_responses', 'Gestión de Respuestas');
    
    // Default no-match response
    $responses->add(new admin_setting_configtextarea(
        'local_chatbot/default_nomatch',
        'Respuesta Predeterminada',
        'Mensaje cuando no se encuentra coincidencia',
        'Disculpa, no entiendo completamente tu pregunta. ¿Podrías reformularla o ser más específico?',
        PARAM_RAW
    ));
    
    // Welcome message
    $responses->add(new admin_setting_configtextarea(
        'local_chatbot/welcome_message',
        'Mensaje de Bienvenida',
        'Mensaje inicial al abrir el chatbot',
        '¡Hola {name}! Soy tu asistente inteligente. ¿En qué puedo ayudarte hoy?',
        PARAM_RAW
    ));
    
    // Error message
    $responses->add(new admin_setting_configtextarea(
        'local_chatbot/error_message',
        'Mensaje de Error',
        'Mensaje cuando ocurre un error',
        'Lo siento, ha ocurrido un error al procesar tu mensaje. Por favor, intenta de nuevo.',
        PARAM_RAW
    ));
    
    // Idle message
    $responses->add(new admin_setting_configtextarea(
        'local_chatbot/idle_message',
        'Mensaje de Inactividad',
        'Mensaje después de período de inactividad',
        '¿Sigues ahí? Si necesitas algo más, no dudes en preguntarme.',
        PARAM_RAW
    ));
    
    $ADMIN->add('local_chatbot_settings', $responses);
    
    // Performance settings
    $performance = new admin_settingpage('local_chatbot_performance', 'Rendimiento');
    
    // Cache duration
    $performance->add(new admin_setting_configtext(
        'local_chatbot/cache_duration',
        'Duración del Caché',
        'Tiempo en segundos para cachear respuestas comunes',
        '3600',
        PARAM_INT
    ));
    
    // Max response time
    $performance->add(new admin_setting_configtext(
        'local_chatbot/max_response_time',
        'Tiempo Máximo de Respuesta',
        'Tiempo máximo en milisegundos antes de timeout',
        '5000',
        PARAM_INT
    ));
    
    // Rate limiting
    $performance->add(new admin_setting_configtext(
        'local_chatbot/rate_limit',
        'Límite de Mensajes',
        'Número máximo de mensajes por minuto por usuario',
        '20',
        PARAM_INT
    ));
    
    // Log retention
    $performance->add(new admin_setting_configtext(
        'local_chatbot/log_retention_days',
        'Retención de Registros',
        'Días para mantener los registros de conversaciones',
        '90',
        PARAM_INT
    ));
    
    $ADMIN->add('local_chatbot_settings', $performance);
    
    // Privacy settings
    $privacy = new admin_settingpage('local_chatbot_privacy', 'Privacidad');
    
    // Anonymous mode
    $privacy->add(new admin_setting_configcheckbox(
        'local_chatbot/anonymous_mode',
        'Modo Anónimo',
        'No registra información personal identificable',
        0
    ));
    
    // Data collection
    $privacy->add(new admin_setting_configcheckbox(
        'local_chatbot/collect_analytics',
        'Recopilar Analíticas',
        'Recopila datos para mejorar el servicio',
        1
    ));
    
    // User consent
    $privacy->add(new admin_setting_configcheckbox(
        'local_chatbot/require_consent',
        'Requerir Consentimiento',
        'Solicita consentimiento antes de usar el chatbot',
        0
    ));
    
    $ADMIN->add('local_chatbot_settings', $privacy);
    
    // Management links
    $ADMIN->add('local_chatbot_settings', new admin_externalpage(
        'local_chatbot_intents',
        'Gestionar Intenciones',
        new moodle_url('/local/chatbot/admin/intents.php'),
        'local/chatbot:manage'
    ));
    
    $ADMIN->add('local_chatbot_settings', new admin_externalpage(
        'local_chatbot_entities',
        'Gestionar Entidades',
        new moodle_url('/local/chatbot/admin/entities.php'),
        'local/chatbot:manage'
    ));
    
    $ADMIN->add('local_chatbot_settings', new admin_externalpage(
        'local_chatbot_training',
        'Entrenamiento y Aprendizaje',
        new moodle_url('/local/chatbot/admin/training.php'),
        'local/chatbot:manage'
    ));
    
    $ADMIN->add('local_chatbot_settings', new admin_externalpage(
        'local_chatbot_analytics',
        'Analíticas y Reportes',
        new moodle_url('/local/chatbot/admin/analytics.php'),
        'local/chatbot:viewanalytics'
    ));
    
    $ADMIN->add('local_chatbot_settings', new admin_externalpage(
        'local_chatbot_dialogues',
        'Flujos de Diálogo',
        new moodle_url('/local/chatbot/admin/dialogues.php'),
        'local/chatbot:manage'
    ));
    
    $ADMIN->add('local_chatbot_settings', new admin_externalpage(
        'local_chatbot_test',
        'Probar Chatbot',
        new moodle_url('/local/chatbot/admin/test.php'),
        'local/chatbot:manage'
    ));
}
