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
 * External functions for the local_chatbot plugin.
 *
 * @package    local_chatbot
 * @copyright  2024 Moodle Community
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/externallib.php');
require_once($CFG->dirroot . '/local/chatbot/lib.php');

/**
 * External service endpoints for the local chatbot widget.
 */
class local_chatbot_external extends external_api {

    /**
     * Parameters for process_message.
     *
     * @return external_function_parameters
     */
    public static function process_message_parameters(): external_function_parameters {
        return new external_function_parameters([
            'message' => new external_value(PARAM_RAW_TRIMMED, 'User message'),
            'sessionid' => new external_value(PARAM_TEXT, 'Chat session id', VALUE_DEFAULT, ''),
            'context' => new external_value(PARAM_TEXT, 'Optional JSON encoded context', VALUE_DEFAULT, '{}'),
        ]);
    }

    /**
     * Process a message sent from the widget.
     *
     * @param string $message
     * @param string $sessionid
     * @param string $context
     * @return array
     */
    public static function process_message(string $message, string $sessionid = '', string $context = '{}'): array {
        $params = self::validate_parameters(self::process_message_parameters(), [
            'message' => $message,
            'sessionid' => $sessionid,
            'context' => $context,
        ]);

        $systemcontext = context_system::instance();
        self::validate_context($systemcontext);
        require_capability('local/chatbot:use', $systemcontext);

        $result = local_chatbot_process_message($params['message'], $params['sessionid'] ?: null);

        $suggestions = [];
        foreach (local_chatbot_get_suggestions() as $suggestion) {
            $suggestions[] = [
                'text' => $suggestion['text'],
                'action' => $suggestion['action'],
                'icon' => $suggestion['icon'] ?? '',
            ];
        }

        $actions = [];
        foreach (local_chatbot_get_quick_actions() as $action) {
            $actions[] = [
                'action' => $action['action'],
                'label' => $action['label'],
                'icon' => $action['icon'],
                'description' => $action['description'],
                'url' => $action['url']->out(false),
            ];
        }

        return [
            'response' => $result['response'],
            'response_time' => $result['response_time'],
            'sessionid' => $result['sessionid'],
            'intent' => $result['intent'],
            'logid' => $result['logid'],
            'suggestions' => $suggestions,
            'actions' => $actions,
        ];
    }

    /**
     * Returns description for process_message.
     *
     * @return external_single_structure
     */
    public static function process_message_returns(): external_single_structure {
        return new external_single_structure([
            'response' => new external_value(PARAM_RAW, 'Chatbot response'),
            'response_time' => new external_value(PARAM_INT, 'Response time in milliseconds'),
            'sessionid' => new external_value(PARAM_TEXT, 'Session identifier'),
            'intent' => new external_value(PARAM_TEXT, 'Matched intent'),
            'logid' => new external_value(PARAM_INT, 'Log identifier'),
            'suggestions' => new external_multiple_structure(new external_single_structure([
                'text' => new external_value(PARAM_TEXT, 'Suggestion text'),
                'action' => new external_value(PARAM_TEXT, 'Action key'),
                'icon' => new external_value(PARAM_TEXT, 'Emoji icon', VALUE_DEFAULT, ''),
            ]), 'Contextual suggestions'),
            'actions' => new external_multiple_structure(new external_single_structure([
                'action' => new external_value(PARAM_TEXT, 'Action identifier'),
                'label' => new external_value(PARAM_TEXT, 'Action label'),
                'icon' => new external_value(PARAM_TEXT, 'Icon to display'),
                'description' => new external_value(PARAM_TEXT, 'Tooltip description'),
                'url' => new external_value(PARAM_URL, 'Destination URL'),
            ]), 'Quick actions'),
        ]);
    }

    /**
     * Parameters for get_suggestions.
     */
    public static function get_suggestions_parameters(): external_function_parameters {
        return new external_function_parameters([
            'context' => new external_value(PARAM_TEXT, 'Optional context (unused for now)', VALUE_DEFAULT, '{}'),
        ]);
    }

    /**
     * Fetch suggestions for the widget.
     *
     * @param string $context
     * @return array
     */
    public static function get_suggestions(string $context = '{}'): array {
        $params = self::validate_parameters(self::get_suggestions_parameters(), ['context' => $context]);

        $systemcontext = context_system::instance();
        self::validate_context($systemcontext);
        require_capability('local/chatbot:use', $systemcontext);

        $suggestions = [];
        foreach (local_chatbot_get_suggestions() as $suggestion) {
            $suggestions[] = [
                'text' => $suggestion['text'],
                'action' => $suggestion['action'],
                'icon' => $suggestion['icon'] ?? '',
            ];
        }

        return $suggestions;
    }

    /**
     * Return description for get_suggestions.
     */
    public static function get_suggestions_returns(): external_multiple_structure {
        return new external_multiple_structure(new external_single_structure([
            'text' => new external_value(PARAM_TEXT, 'Suggestion text'),
            'action' => new external_value(PARAM_TEXT, 'Action key'),
            'icon' => new external_value(PARAM_TEXT, 'Emoji icon', VALUE_DEFAULT, ''),
        ]));
    }

    /**
     * Parameters for get_quick_actions.
     */
    public static function get_quick_actions_parameters(): external_function_parameters {
        return new external_function_parameters([
            'context' => new external_value(PARAM_TEXT, 'Optional context (unused for now)', VALUE_DEFAULT, '{}'),
        ]);
    }

    /**
     * Fetch quick actions.
     *
     * @param string $context
     * @return array
     */
    public static function get_quick_actions(string $context = '{}'): array {
        $params = self::validate_parameters(self::get_quick_actions_parameters(), ['context' => $context]);

        $systemcontext = context_system::instance();
        self::validate_context($systemcontext);
        require_capability('local/chatbot:use', $systemcontext);

        $actions = [];
        foreach (local_chatbot_get_quick_actions() as $action) {
            $actions[] = [
                'action' => $action['action'],
                'label' => $action['label'],
                'icon' => $action['icon'],
                'description' => $action['description'],
                'url' => $action['url']->out(false),
            ];
        }

        return $actions;
    }

    /**
     * Return description for get_quick_actions.
     */
    public static function get_quick_actions_returns(): external_multiple_structure {
        return new external_multiple_structure(new external_single_structure([
            'action' => new external_value(PARAM_TEXT, 'Action identifier'),
            'label' => new external_value(PARAM_TEXT, 'Display label'),
            'icon' => new external_value(PARAM_TEXT, 'Icon'),
            'description' => new external_value(PARAM_TEXT, 'Tooltip description'),
            'url' => new external_value(PARAM_URL, 'Destination URL'),
        ]));
    }

    /**
     * Parameters for feedback.
     */
    public static function feedback_parameters(): external_function_parameters {
        return new external_function_parameters([
            'logid' => new external_value(PARAM_INT, 'Conversation log identifier'),
            'feedback' => new external_value(PARAM_TEXT, 'Feedback label'),
        ]);
    }

    /**
     * Store feedback from the widget.
     *
     * @param int $logid
     * @param string $feedback
     * @return array
     */
    public static function feedback(int $logid, string $feedback): array {
        $params = self::validate_parameters(self::feedback_parameters(), [
            'logid' => $logid,
            'feedback' => $feedback,
        ]);

        $systemcontext = context_system::instance();
        self::validate_context($systemcontext);
        require_capability('local/chatbot:use', $systemcontext);

        $success = local_chatbot_feedback($params['logid'], $params['feedback']);

        return ['success' => $success];
    }

    /**
     * Return description for feedback.
     */
    public static function feedback_returns(): external_single_structure {
        return new external_single_structure([
            'success' => new external_value(PARAM_BOOL, 'Whether the feedback was stored'),
        ]);
    }

    /**
     * Parameters for get_history.
     */
    public static function get_history_parameters(): external_function_parameters {
        return new external_function_parameters([
            'sessionid' => new external_value(PARAM_TEXT, 'Session identifier'),
            'limit' => new external_value(PARAM_INT, 'Number of messages to fetch', VALUE_DEFAULT, 10),
        ]);
    }

    /**
     * Fetch conversation history.
     *
     * @param string $sessionid
     * @param int $limit
     * @return array
     */
    public static function get_history(string $sessionid, int $limit = 10): array {
        $params = self::validate_parameters(self::get_history_parameters(), [
            'sessionid' => $sessionid,
            'limit' => $limit,
        ]);

        $systemcontext = context_system::instance();
        self::validate_context($systemcontext);
        require_capability('local/chatbot:use', $systemcontext);

        $history = local_chatbot_get_conversation_history($params['sessionid'], $params['limit']);

        $items = [];
        foreach ($history as $entry) {
            $items[] = [
                'message' => $entry->message,
                'response' => $entry->response,
                'intent' => $entry->intent,
                'timestamp' => $entry->timecreated,
            ];
        }

        return $items;
    }

    /**
     * Return description for get_history.
     */
    public static function get_history_returns(): external_multiple_structure {
        return new external_multiple_structure(new external_single_structure([
            'message' => new external_value(PARAM_RAW, 'User message'),
            'response' => new external_value(PARAM_RAW, 'Chatbot response'),
            'intent' => new external_value(PARAM_TEXT, 'Intent label'),
            'timestamp' => new external_value(PARAM_INT, 'Creation time'),
        ]));
    }

    /**
     * Parameters for export_conversation.
     */
    public static function export_conversation_parameters(): external_function_parameters {
        return new external_function_parameters([
            'sessionid' => new external_value(PARAM_TEXT, 'Session identifier'),
            'format' => new external_value(PARAM_TEXT, 'Export format (html|csv|json)', VALUE_DEFAULT, 'html'),
        ]);
    }

    /**
     * Export conversation history.
     *
     * @param string $sessionid
     * @param string $format
     * @return array
     */
    public static function export_conversation(string $sessionid, string $format = 'html'): array {
        $params = self::validate_parameters(self::export_conversation_parameters(), [
            'sessionid' => $sessionid,
            'format' => $format,
        ]);

        $systemcontext = context_system::instance();
        self::validate_context($systemcontext);
        require_capability('local/chatbot:export', $systemcontext);

        $content = local_chatbot_export_conversation($params['sessionid'], $params['format']);

        return [
            'content' => $content,
            'format' => $params['format'],
        ];
    }

    /**
     * Return description for export_conversation.
     */
    public static function export_conversation_returns(): external_single_structure {
        return new external_single_structure([
            'content' => new external_value(PARAM_RAW, 'Exported content'),
            'format' => new external_value(PARAM_TEXT, 'Format used'),
        ]);
    }

    /**
     * Parameters for execute_action.
     */
    public static function execute_action_parameters(): external_function_parameters {
        return new external_function_parameters([
            'action' => new external_value(PARAM_TEXT, 'Action identifier'),
            'params' => new external_value(PARAM_TEXT, 'JSON encoded parameters', VALUE_DEFAULT, '{}'),
        ]);
    }

    /**
     * Execute a quick action on behalf of the user.
     *
     * @param string $action
     * @param string $params
     * @return array
     */
    public static function execute_action(string $action, string $params = '{}'): array {
        $params = self::validate_parameters(self::execute_action_parameters(), [
            'action' => $action,
            'params' => $params,
        ]);

        $systemcontext = context_system::instance();
        self::validate_context($systemcontext);
        require_capability('local/chatbot:use', $systemcontext);

        $decodedparams = json_decode($params['params'], true) ?: [];

        $message = get_string('chatbot_action_generic', 'local_chatbot', $params['action']);

        return [
            'success' => true,
            'message' => $message,
        ];
    }

    /**
     * Return description for execute_action.
     */
    public static function execute_action_returns(): external_single_structure {
        return new external_single_structure([
            'success' => new external_value(PARAM_BOOL, 'Success status'),
            'message' => new external_value(PARAM_RAW, 'Action result message', VALUE_OPTIONAL),
        ]);
    }
}