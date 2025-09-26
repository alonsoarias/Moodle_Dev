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

use core\hook\output\before_footer_html_generation;

require_once($CFG->dirroot . '/user/lib.php');

/**
 * Reset runtime caches for dynamic chatbot data.
 */
function local_chatbot_reset_runtime_cache(): void {
    local_chatbot_get_intents(true);
    local_chatbot_get_suggestions(true);
    local_chatbot_get_quick_actions(true);
}

/**
 * Fetch chatbot intents.
 *
 * @param bool $forcereload
 * @return array
 */
function local_chatbot_get_intents(bool $forcereload = false): array {
    global $DB;

    static $cache = null;
    if ($forcereload) {
        $cache = null;
    }

    if ($cache === null) {
        $records = $DB->get_records('local_chatbot_intents', ['enabled' => 1], 'sortorder ASC, name ASC');
        $cache = array_values($records);
    }

    return $cache;
}

/**
 * Parse keyword string into an array.
 *
 * @param string|null $keywords
 * @return array
 */
function local_chatbot_parse_keywords(?string $keywords): array {
    if ($keywords === null) {
        return [];
    }

    $lines = preg_split('/[\n,]+/', core_text::strtolower($keywords));
    $lines = array_map('trim', $lines);
    $lines = array_filter($lines, static function($value) {
        return $value !== '';
    });

    return array_values(array_unique($lines));
}

/**
 * Classify a message and return the response configuration.
 *
 * @param string $message
 * @return array{intent:?stdClass,response:string,matched:string}
 */
function local_chatbot_classify_message(string $message): array {
    $message = trim($message);
    $lcmessage = core_text::strtolower($message);

    $fallback = null;
    foreach (local_chatbot_get_intents() as $intent) {
        if (!empty($intent->isfallback)) {
            $fallback = $intent;
            continue;
        }

        foreach (local_chatbot_parse_keywords($intent->keywords) as $keyword) {
            if ($keyword !== '' && core_text::strpos($lcmessage, $keyword) !== false) {
                return [$intent, $intent->response, $keyword];
            }
        }
    }

    if ($fallback) {
        return [$fallback, $fallback->response, ''];
    }

    $response = (string)(get_config('local_chatbot', 'default_nomatch')
        ?: get_string('default_nomatch', 'local_chatbot'));

    return [null, $response, ''];
}

/**
 * Retrieve configured suggestions.
 *
 * @param bool $forcereload
 * @return array
 */
function local_chatbot_get_suggestions(bool $forcereload = false): array {
    global $DB;

    static $cache = null;
    if ($forcereload) {
        $cache = null;
    }

    if ($cache === null) {
        $records = $DB->get_records('local_chatbot_suggestions', ['enabled' => 1], 'sortorder ASC, text ASC');
        $cache = array_values($records);
    }

    return $cache;
}

/**
 * Resolve a quick action URL replacing special tokens.
 *
 * @param string $payload
 * @param stdClass $user
 * @param int $courseid
 * @return moodle_url
 */
function local_chatbot_resolve_quick_action_url(string $payload, stdClass $user, int $courseid): moodle_url {
    global $CFG;

    $replacements = [
        '{userid}' => $user->id,
        '{courseid}' => $courseid ?: 0,
        '{wwwroot}' => $CFG->wwwroot,
    ];

    $resolved = str_replace(array_keys($replacements), array_values($replacements), trim($payload));
    if ($resolved === '') {
        return new moodle_url('/');
    }

    return new moodle_url($resolved);
}

/**
 * Retrieve quick actions.
 *
 * @param bool $forcereload
 * @return array
 */
function local_chatbot_get_quick_actions(bool $forcereload = false): array {
    global $DB, $USER, $PAGE;

    static $cache = null;
    if ($forcereload) {
        $cache = null;
    }

    if ($cache === null) {
        $records = $DB->get_records('local_chatbot_quickacts', ['enabled' => 1], 'sortorder ASC, name ASC');
        $courseid = $PAGE->course->id ?? 0;

        $cache = [];
        foreach ($records as $record) {
            $action = [
                'actionkey' => $record->actionkey,
                'label' => $record->name,
                'description' => (string)$record->description,
                'icon' => (string)$record->icon,
                'type' => $record->type,
                'message' => '',
                'url' => null,
            ];

            switch ($record->type) {
                case 'inject':
                case 'server':
                    $action['message'] = (string)$record->payload;
                    break;
                case 'navigate':
                default:
                    $action['url'] = local_chatbot_resolve_quick_action_url($record->payload ?? '/', $USER, $courseid);
                    $action['type'] = 'navigate';
                    break;
            }

            $cache[] = $action;
        }
    }

    return $cache;
}

/**
 * Legacy callback stub kept for completeness.
 */
function local_chatbot_extend_navigation() {
    // The widget is injected using the before_footer hook.
}

/**
 * Hook callback executed before the footer is rendered.
 *
 * @param before_footer_html_generation $hook
 */
function local_chatbot_before_footer_html_generation(before_footer_html_generation $hook): void {
    global $PAGE, $OUTPUT;

    $data = local_chatbot_get_widget_bootstrap();
    if (!$data) {
        return;
    }

    [$templatecontext, $jsconfig] = $data;

    $PAGE->requires->css('/local/chatbot/styles.css');
    $PAGE->requires->js_call_amd('local_chatbot/chatbot', 'init', [$jsconfig]);

    $hook->add_html($OUTPUT->render_from_template('local_chatbot/widget', $templatecontext));
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

    $enabled = get_config('local_chatbot', 'enabled');
    if ($enabled === null) {
        $enabled = true;
    }
    if (!$enabled) {
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

    [$intentrecord, $response, $matchedkeyword] = local_chatbot_classify_message($message);
    $intent = $intentrecord ? (string)$intentrecord->name : 'nomatch';

    $responsetime = (int)round((microtime(true) - $start) * 1000);

    $record = (object) [
        'userid' => $USER->id,
        'sessionid' => $sessionid,
        'message' => $message,
        'response' => $response,
        'intent' => $intent,
        'responsetime' => $responsetime,
        'metadata' => json_encode([
            'language' => current_language(),
            'matchedkeyword' => $matchedkeyword,
        ]),
        'timecreated' => time(),
    ];
    $logid = $DB->insert_record('local_chatbot_logs', $record);

    return [
        'response' => $response,
        'response_time' => $responsetime,
        'sessionid' => $sessionid,
        'intent' => $intent,
        'logid' => $logid,
        'timestamp' => $record->timecreated,
    ];
}

/**
 * Preview a message without logging it.
 *
 * @param string $message
 * @return array
 */
function local_chatbot_preview_message(string $message): array {
    [$intent, $response, $matchedkeyword] = local_chatbot_classify_message($message);

    return [
        'intent' => $intent ? $intent->name : 'nomatch',
        'response' => $response,
        'matchedkeyword' => $matchedkeyword,
        'intentrecord' => $intent,
    ];
}

/**
 * Return contextual suggestions.
 *
 * @return array
 */
function local_chatbot_get_suggestions_payload(): array {
    $results = [];
    foreach (local_chatbot_get_suggestions() as $suggestion) {
        $results[] = [
            'text' => $suggestion->text,
            'mode' => $suggestion->mode,
            'target' => (string)$suggestion->target,
            'icon' => (string)$suggestion->icon,
        ];
    }

    return $results;
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
            $user = core_user::get_user($item->userid);
            $username = $user ? fullname($user) : get_string('deleteduser', 'core');

            $lines[] = sprintf(
                '%s;%s;%s;%s;%s;%s',
                userdate($item->timecreated),
                $username,
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
        $user = core_user::get_user($item->userid);
        $username = $user ? fullname($user) : get_string('deleteduser', 'core');

        $html .= html_writer::tag('tr',
            html_writer::tag('td', userdate($item->timecreated)) .
            html_writer::tag('td', $username) .
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
