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
 * @copyright  2024 Moodle Community
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/user/lib.php');

/**
 * Legacy callback stub kept for completeness.
 */
function local_chatbot_extend_navigation() {
    // The widget is injected using the before_footer hook.
}

/**
 * Hook callback executed before the footer is rendered.
 */
function local_chatbot_before_footer_html_generation(): void {
    global $PAGE, $OUTPUT;

    $data = local_chatbot_get_widget_bootstrap();
    if (!$data) {
        return;
    }

    [$templatecontext, $jsconfig] = $data;

    $PAGE->requires->css('/local/chatbot/styles.css');
    // Load the unminified AMD module explicitly so the plugin can operate without
    // shipping the compiled chatbot.min.js asset.
    $PAGE->requires->js(new moodle_url('/local/chatbot/amd/src/chatbot.js'), true);
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

    $systemcontext = context_system::instance();
    if (!has_capability('local/chatbot:use', $systemcontext)) {
        $cached = null;
        return null;
    }

    $position = get_config('local_chatbot', 'position') ?: 'bottom_right';
    $theme = get_config('local_chatbot', 'theme') ?: 'modern';
    $maxlength = (int)(get_config('local_chatbot', 'maxlength') ?: 500);
    $assistantname = trim((string)get_config('local_chatbot', 'assistantname'));
    if ($assistantname === '') {
        $assistantname = get_string('chatbot_title', 'local_chatbot');
    }

    $firstname = trim($USER->firstname ?? '');
    $avatar = core_text::strtoupper(core_text::substr($firstname ?: fullname($USER), 0, 1));

    $strings = [
        'title' => format_string($assistantname),
        'status' => get_string('chatbot_status_online', 'local_chatbot'),
        'toggle' => get_string('chatbot_toggle_label', 'local_chatbot', $assistantname),
        'placeholder' => get_string('chatbot_placeholder', 'local_chatbot'),
        'typing' => get_string('chatbot_typing_indicator', 'local_chatbot'),
        'emoji' => get_string('chatbot_emoji_picker_label', 'local_chatbot'),
        'send' => get_string('chatbot_send', 'local_chatbot'),
        'export' => get_string('chatbot_export_conversation_label', 'local_chatbot'),
        'minimize' => get_string('chatbot_minimize', 'local_chatbot'),
        'close' => get_string('chatbot_close', 'local_chatbot'),
        'error' => get_string('chatbot_error', 'local_chatbot'),
        'welcome' => get_config('local_chatbot', 'welcome_message')
            ?: get_string('chatbot_welcome_template', 'local_chatbot'),
        'quickactionslabel' => get_string('chatbot_quick_actions_region', 'local_chatbot'),
        'suggestionslabel' => get_string('chatbot_suggestions_region', 'local_chatbot'),
    ];

    $features = [
        'emoji_picker' => false,
        'quick_actions' => true,
        'suggestions' => true,
        'typing_animation' => true,
        'sound_notifications' => false,
    ];

    $permissions = [
        'canexport' => (bool)get_config('local_chatbot', 'allow_export') &&
            has_capability('local/chatbot:export', $systemcontext),
    ];

    $sessionid = local_chatbot_generate_sessionid($USER->id);

    $templatecontext = [
        'position' => $position,
        'theme' => $theme,
        'mode' => 'geniai-inspired',
        'title' => $strings['title'],
        'status' => $strings['status'],
        'togglelabel' => $strings['toggle'],
        'talklabel' => $strings['toggle'],
        'placeholder' => $strings['placeholder'],
        'typing' => $strings['typing'],
        'emojilabel' => $strings['emoji'],
        'sendlabel' => $strings['send'],
        'exportlabel' => $strings['export'],
        'minimizelabel' => $strings['minimize'],
        'closelabel' => $strings['close'],
        'welcome' => $strings['welcome'],
        'quickactionslabel' => $strings['quickactionslabel'],
        'suggestionslabel' => $strings['suggestionslabel'],
        'emojienabled' => $features['emoji_picker'],
        'showquickactions' => $features['quick_actions'],
        'showsuggestions' => $features['suggestions'],
        'canexport' => $permissions['canexport'],
        'maxlength' => $maxlength,
        'initial' => $avatar,
    ];

    $jsconfig = [
        'userid' => (int)$USER->id,
        'username' => fullname($USER),
        'sessionid' => $sessionid,
        'position' => $position,
        'theme' => $theme,
        'courseid' => $PAGE->course->id ?? 0,
        'contextid' => $PAGE->context->id ?? $systemcontext->id,
        'wwwroot' => $CFG->wwwroot,
        'features' => $features,
        'permissions' => $permissions,
        'maxlength' => $maxlength,
        'avatar' => $avatar,
        'language' => current_language(),
        'strings' => $strings,
        'storagekey' => 'local_chatbot_widget_state',
    ];

    $cached = [$templatecontext, $jsconfig];
    return $cached;
}

/**
 * Generate a deterministic session id for the current user/browser.
 *
 * @param int $userid
 * @return string
 */
function local_chatbot_generate_sessionid(int $userid): string {
    $remoteaddr = $_SERVER['REMOTE_ADDR'] ?? 'cli';
    $fingerprint = sesskey() . '-' . $remoteaddr;
    return 'cb_' . substr(sha1($userid . $fingerprint . session_id()), 0, 20);
}

/**
 * Process a message sent by the user and log the response.
 *
 * @param string $message
 * @param string|null $sessionid
 * @return array
 */
function local_chatbot_process_message(string $message, ?string $sessionid = null): array {
    global $USER, $DB;

    $message = trim($message);
    $sessionid = $sessionid ?: local_chatbot_generate_sessionid($USER->id);
    $start = microtime(true);

    $intent = 'general';
    $response = '';

    $lcmessage = core_text::strtolower($message);
    $responses = [
        'greeting' => [
            'keywords' => ['hola', 'hello', 'buenos', 'buenas', 'hi'],
            'response' => get_string('chatbot_response_greeting', 'local_chatbot'),
        ],
        'help' => [
            'keywords' => ['ayuda', 'help', 'assist', 'no puedo'],
            'response' => get_string('chatbot_response_help', 'local_chatbot'),
        ],
        'courses' => [
            'keywords' => ['curso', 'course', 'materia', 'class'],
            'response' => get_string('chatbot_response_courses', 'local_chatbot'),
        ],
        'grades' => [
            'keywords' => ['nota', 'grade', 'calificación'],
            'response' => get_string('chatbot_response_grades', 'local_chatbot'),
        ],
    ];

    foreach ($responses as $label => $data) {
        foreach ($data['keywords'] as $keyword) {
            if (core_text::strpos($lcmessage, $keyword) !== false) {
                $intent = $label;
                $response = $data['response'];
                break 2;
            }
        }
    }

    if ($response === '') {
        $intent = 'nomatch';
        $response = (string)(get_config('local_chatbot', 'default_nomatch')
            ?: get_string('default_nomatch', 'local_chatbot'));
    }

    $responsetime = (int)round((microtime(true) - $start) * 1000);

    $record = (object) [
        'userid' => $USER->id,
        'sessionid' => $sessionid,
        'message' => $message,
        'response' => $response,
        'intent' => $intent,
        'responsetime' => $responsetime,
        'metadata' => json_encode(['language' => current_language()]),
        'timecreated' => time(),
    ];
    $logid = $DB->insert_record('local_chatbot_logs', $record);

    return [
        'response' => $response,
        'response_time' => $responsetime,
        'sessionid' => $sessionid,
        'intent' => $intent,
        'logid' => $logid,
    ];
}

/**
 * Return contextual suggestions.
 *
 * @return array
 */
function local_chatbot_get_suggestions(): array {
    return [
        ['text' => get_string('chatbot_suggestion_courses', 'local_chatbot'), 'action' => 'show_courses', 'icon' => '📚'],
        ['text' => get_string('chatbot_suggestion_grades', 'local_chatbot'), 'action' => 'show_grades', 'icon' => '📊'],
        ['text' => get_string('chatbot_suggestion_support', 'local_chatbot'), 'action' => '', 'icon' => '🆘'],
    ];
}

/**
 * Return contextual quick actions.
 *
 * @return array
 */
function local_chatbot_get_quick_actions(): array {
    global $USER;

    return [
        [
            'icon' => '👤',
            'label' => get_string('chatbot_action_profile', 'local_chatbot'),
            'action' => 'navigate',
            'description' => get_string('chatbot_action_profile_desc', 'local_chatbot'),
            'url' => new moodle_url('/user/profile.php', ['id' => $USER->id]),
        ],
        [
            'icon' => '🗓️',
            'label' => get_string('chatbot_action_calendar', 'local_chatbot'),
            'action' => 'navigate',
            'description' => get_string('chatbot_action_calendar_desc', 'local_chatbot'),
            'url' => new moodle_url('/calendar/view.php', ['view' => 'month']),
        ],
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
    global $DB;

    $records = $DB->get_records('local_chatbot_logs', ['sessionid' => $sessionid], 'timecreated DESC', '*', 0, $limit);
    return array_reverse(array_values($records));
}

/**
 * Store feedback about a chatbot message.
 *
 * @param int $logid
 * @param string $feedback
 * @return bool
 */
function local_chatbot_feedback(int $logid, string $feedback): bool {
    global $DB;

    if (!$DB->record_exists('local_chatbot_logs', ['id' => $logid])) {
        return false;
    }

    $DB->update_record('local_chatbot_logs', (object) [
        'id' => $logid,
        'feedback' => substr($feedback, 0, 20),
    ]);

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
    $history = local_chatbot_get_conversation_history($sessionid, 100);

    if ($format === 'json') {
        return json_encode($history, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }

    if ($format === 'csv') {
        $lines = ["time;sender;message;response;intent;feedback"];
        foreach ($history as $item) {
            $lines[] = sprintf(
                '%s;%s;%s;%s;%s;%s',
                userdate($item->timecreated),
                fullname(core_user::get_user($item->userid)),
                str_replace(['"', "\n"], ['""', ' '], $item->message),
                str_replace(['"', "\n"], ['""', ' '], $item->response),
                $item->intent,
                $item->feedback ?? ''
            );
        }
        return implode("\n", $lines);
    }

    $html = html_writer::tag('h1', get_string('chatbot_export_heading', 'local_chatbot'));
    $html .= html_writer::start_tag('table', ['class' => 'generaltable local-chatbot-export']);
    $html .= html_writer::start_tag('thead');
    $html .= html_writer::tag('tr',
        html_writer::tag('th', get_string('time')) .
        html_writer::tag('th', get_string('user')) .
        html_writer::tag('th', get_string('message', 'local_chatbot')) .
        html_writer::tag('th', get_string('response', 'local_chatbot')) .
        html_writer::tag('th', get_string('intent', 'local_chatbot')) .
        html_writer::tag('th', get_string('feedback', 'local_chatbot'))
    );
    $html .= html_writer::end_tag('thead');
    $html .= html_writer::start_tag('tbody');

    foreach ($history as $item) {
        $html .= html_writer::tag('tr',
            html_writer::tag('td', userdate($item->timecreated)) .
            html_writer::tag('td', fullname(core_user::get_user($item->userid))) .
            html_writer::tag('td', format_text($item->message, FORMAT_PLAIN)) .
            html_writer::tag('td', format_text($item->response, FORMAT_PLAIN)) .
            html_writer::tag('td', s($item->intent)) .
            html_writer::tag('td', s($item->feedback ?? ''))
        );
    }

    if (empty($history)) {
        $html .= html_writer::tag('tr',
            html_writer::tag('td', get_string('chatbot_export_empty', 'local_chatbot'), ['colspan' => 6])
        );
    }

    $html .= html_writer::end_tag('tbody');
    $html .= html_writer::end_tag('table');

    return $html;
}