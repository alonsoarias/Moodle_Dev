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
 * Helper functions for the local_chatbot plugin.
 *
 * @package    local_chatbot
 * @copyright  2025 Your Name
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();


/**
 * Backwards compatibility stub for legacy callback.
 */
function local_chatbot_extend_navigation() {
    // No-op. The widget is injected using the before_footer hook.
}

/**
 * Hook callback executed before the footer is rendered.
 */
function local_chatbot_before_footer_html_generation() {
    global $PAGE, $OUTPUT;

    $data = local_chatbot_get_widget_bootstrap();
    if (!$data) {
        return;
    }

    [$templatecontext, $jsconfig] = $data;

    $PAGE->requires->css('/local/chatbot/styles.css');
    $PAGE->requires->js_call_amd('local_chatbot/chatbot', 'init', [$jsconfig]);

    echo $OUTPUT->render_from_template('local_chatbot/widget', $templatecontext);
}

/**
 * Build the template context and JS configuration for the widget.
 *
 * @return array|null
 */
function local_chatbot_get_widget_bootstrap(): ?array {
    global $PAGE, $USER, $CFG;

    static $cached = null;

    if ($cached !== null) {
        return $cached;
    }

    if (!isloggedin() || isguestuser()) {
        $cached = null;
        return null;
    }

    if (!$PAGE->get_popup_notification_allowed()) {
        $cached = null;
        return null;
    }

    if (!get_config('local_chatbot', 'enabled')) {
        $cached = null;
        return null;
    }

    $position = get_config('local_chatbot', 'position') ?: 'bottom_right';
    $theme = get_config('local_chatbot', 'theme') ?: 'modern';
    $maxlength = (int)(get_config('local_chatbot', 'maxlength') ?: 500);
    $firstname = trim($USER->firstname ?? '');
    $avatar = core_text::strtoupper(core_text::substr($firstname ?: fullname($USER), 0, 1));

    $assistantname = format_string(get_config('local_chatbot', 'assistantname')
        ?: get_string('chatbot_title', 'local_chatbot'));

    $strings = [
        'title' => $assistantname,
        'status' => get_string('chatbot_status_online', 'local_chatbot'),
        'toggle' => get_string('chatbot_toggle_label', 'local_chatbot', $assistantname),
        'placeholder' => get_string('chatbot_placeholder', 'local_chatbot'),
        'typing' => get_string('chatbot_typing_indicator', 'local_chatbot'),
        'voice' => get_string('chatbot_voice_input_label', 'local_chatbot'),
        'emoji' => get_string('chatbot_emoji_picker_label', 'local_chatbot'),
        'send' => get_string('chatbot_send', 'local_chatbot'),
        'export' => get_string('chatbot_export_conversation_label', 'local_chatbot'),
        'minimize' => get_string('chatbot_minimize', 'local_chatbot'),
        'close' => get_string('chatbot_close', 'local_chatbot'),
        'welcome' => get_string('chatbot_welcome_template', 'local_chatbot'),
    ];

    $features = [
        'voice_input' => (bool)get_config('local_chatbot', 'voice_input'),
        'emoji_picker' => (bool)get_config('local_chatbot', 'emoji_picker'),
        'quick_actions' => (bool)get_config('local_chatbot', 'quick_actions'),
        'suggestions' => (bool)get_config('local_chatbot', 'suggestions'),
        'typing_animation' => (bool)get_config('local_chatbot', 'typing_animation'),
        'sound_notifications' => (bool)get_config('local_chatbot', 'sound_notifications'),
    ];

    $permissions = [
        'canexport' => (bool)get_config('local_chatbot', 'allow_export'),
    ];

    $sessionid = 'cb_' . md5($USER->id . '-' . sesskey());

    $templatecontext = [
        'position' => $position,
        'theme' => $theme,
        'title' => $strings['title'],
        'status' => $strings['status'],
        'togglelabel' => $strings['toggle'],
        'placeholder' => $strings['placeholder'],
        'typing' => $strings['typing'],
        'voicelabel' => $strings['voice'],
        'emojilabel' => $strings['emoji'],
        'sendlabel' => $strings['send'],
        'exportlabel' => $strings['export'],
        'minimizelabel' => $strings['minimize'],
        'closelabel' => $strings['close'],
        'voiceenabled' => $features['voice_input'],
        'emojienabled' => $features['emoji_picker'],
        'canexport' => $permissions['canexport'],
        'maxlength' => $maxlength,
        'initial' => $avatar,
    ];

    $jsconfig = [
        'userid' => $USER->id,
        'username' => fullname($USER),
        'sessionid' => $sessionid,
        'position' => $position,
        'theme' => $theme,
        'courseid' => $PAGE->course->id ?? 0,
        'contextid' => $PAGE->context->id ?? context_system::instance()->id,
        'wwwroot' => $CFG->wwwroot,
        'features' => $features,
        'permissions' => $permissions,
        'maxlength' => $maxlength,
        'avatar' => $avatar,
        'language' => current_language(),
        'strings' => $strings,
    ];

    $cached = [$templatecontext, $jsconfig];
    return $cached;
}

/**
 * Process user messages.
 *
 * @param string $message
 * @param string|null $sessionid
 * @return array
 */
function local_chatbot_process_message(string $message, ?string $sessionid = null): array {
    $message = core_text::strtolower(trim($message));

    $responses = [
        'hola' => '¡Hola! ¿En qué puedo ayudarte?',
        'ayuda' => 'Puedo ayudarte con cursos, tareas y navegación.',
        'curso' => 'Ve a "Mis cursos" en el panel principal.',
        'gracias' => '¡De nada! Estoy aquí para ayudar.'
    ];

    foreach ($responses as $keyword => $response) {
        if (core_text::strpos($message, $keyword) !== false) {
            return ['response' => $response, 'response_time' => 100, 'sessionid' => $sessionid];
        }
    }

    return ['response' => get_string('default_nomatch', 'local_chatbot'), 'response_time' => 100, 'sessionid' => $sessionid];
}

/**
 * Return contextual suggestions.
 *
 * @param array $context
 * @return array
 */
function local_chatbot_get_suggestions(array $context = []): array {
    return [
        ['text' => get_string('chatbot_suggestion_courses', 'local_chatbot'), 'action' => 'courses', 'icon' => '📚'],
        ['text' => get_string('chatbot_suggestion_grades', 'local_chatbot'), 'action' => 'grades', 'icon' => '📊'],
    ];
}

/**
 * Return contextual quick actions.
 *
 * @param array $context
 * @return array
 */
function local_chatbot_get_quick_actions(array $context = []): array {
    global $USER;

    return [
        [
            'icon' => '👤',
            'label' => get_string('chatbot_action_profile', 'local_chatbot'),
            'action' => 'navigate',
            'description' => get_string('chatbot_action_profile_desc', 'local_chatbot'),
            'url' => new moodle_url('/user/profile.php', ['id' => $USER->id])
        ]
    ];
}

/**
 * Fetch conversation history.
 *
 * @param string $sessionid
 * @param int $limit
 * @return array
 */
function local_chatbot_get_conversation_history(string $sessionid, int $limit = 10): array {
    return [];
}

/**
 * Store feedback about a chatbot message.
 *
 * @param int $logid
 * @param string $feedback
 * @return bool
 */
function local_chatbot_feedback(int $logid, string $feedback): bool {
    // Placeholder for future persistence.
    return true;
}

/**
 * Export a conversation in a given format.
 *
 * @param string $sessionid
 * @param string $format
 * @return string
 */
function local_chatbot_export_conversation(string $sessionid, string $format = 'html'): string {
    $content = '<h1>' . get_string('chatbot_export_heading', 'local_chatbot') . '</h1>';
    $content .= '<p>' . get_string('chatbot_export_placeholder', 'local_chatbot', format_string($sessionid)) . '</p>';

    return $content;
}
