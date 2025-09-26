<?php
defined('MOODLE_INTERNAL') || die();

/**
 * Inject chatbot via extend_navigation - NO before_footer!
 */
function local_chatbot_extend_navigation() {
    global $PAGE, $USER, $CFG;
    
    if (!isloggedin() || isguestuser()) {
        return;
    }
    
    $enabled = get_config('local_chatbot', 'enabled');
    if (!$enabled) {
        return;
    }
    
    // Add CSS
    $PAGE->requires->css(new moodle_url('/local/chatbot/styles.css'));
    
    // Config for JS
    $config = [
        'userid' => $USER->id,
        'username' => fullname($USER),
        'sessionid' => 'cb_' . md5($USER->id . date('Ymd')),
        'position' => get_config('local_chatbot', 'position') ?: 'bottom_right',
        'theme' => get_config('local_chatbot', 'theme') ?: 'modern',
        'wwwroot' => $CFG->wwwroot,
        'courseid' => $PAGE->course->id ?? 0
    ];
    
    // Add JS to auto-inject widget
    $PAGE->requires->js_call_amd('local_chatbot/chatbot', 'init', [$config]);
}

function local_chatbot_process_message($message, $sessionid = null) {
    global $DB;
    
    $message = strtolower(trim($message));
    
    // Simple keyword matching
    $responses = [
        'hola' => '¡Hola! ¿En qué puedo ayudarte?',
        'ayuda' => 'Puedo ayudarte con cursos, tareas y navegación.',
        'curso' => 'Ve a "Mis cursos" en el panel principal.',
        'gracias' => '¡De nada! Estoy aquí para ayudar.'
    ];
    
    foreach ($responses as $keyword => $response) {
        if (strpos($message, $keyword) !== false) {
            return ['response' => $response, 'response_time' => 100, 'sessionid' => $sessionid];
        }
    }
    
    return ['response' => 'No entiendo. ¿Puedes reformular?', 'response_time' => 100, 'sessionid' => $sessionid];
}

function local_chatbot_get_suggestions() {
    return [
        ['text' => 'Ver cursos', 'action' => 'courses', 'icon' => '📚'],
        ['text' => 'Calificaciones', 'action' => 'grades', 'icon' => '📊']
    ];
}

function local_chatbot_get_quick_actions() {
    global $USER;
    return [
        ['icon' => '👤', 'label' => 'Mi perfil', 'action' => 'navigate', 
         'url' => new moodle_url('/user/profile.php', ['id' => $USER->id])]
    ];
}

function local_chatbot_get_conversation_history() { return []; }
function local_chatbot_feedback() { return true; }
function local_chatbot_export_conversation() { return ''; }
