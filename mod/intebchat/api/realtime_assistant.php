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
 * API endpoint for Assistant calls from Realtime mode
 *
 * This endpoint allows the Realtime API to delegate complex queries
 * to the configured OpenAI Assistant.
 *
 * @package    mod_intebchat
 * @copyright  2025 Alonso Arias <soporte@ingeweb.co>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('AJAX_SCRIPT', true);

require_once(__DIR__ . '/../../../config.php');
require_once($CFG->libdir . '/filelib.php'); // Required for curl class
require_once($CFG->dirroot . '/mod/intebchat/lib.php');
require_once($CFG->dirroot . '/mod/intebchat/locallib.php');

global $DB, $USER;

// Set JSON content type
header('Content-Type: application/json; charset=utf-8');

try {
    // Check if user is logged in
    require_login(null, false, null, false, true);

    // Get JSON input
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);

    if (!$data) {
        throw new Exception('Invalid JSON input');
    }

    // Validate sesskey
    $sesskey = $data['sesskey'] ?? '';
    if (!confirm_sesskey($sesskey)) {
        throw new Exception('Invalid session key');
    }

    // Get parameters
    $instanceid = clean_param($data['instanceId'] ?? 0, PARAM_INT);
    $question = clean_param($data['question'] ?? '', PARAM_TEXT);
    $threadid = isset($data['threadId']) ? clean_param($data['threadId'], PARAM_TEXT) : null;
    $conversationid = isset($data['conversationId']) ? clean_param($data['conversationId'], PARAM_INT) : null;

    if (empty($instanceid) || empty($question)) {
        throw new Exception('Missing required parameters');
    }

    // Validate instance
    $instance = $DB->get_record('intebchat', ['id' => $instanceid], '*', MUST_EXIST);
    $course = $DB->get_record('course', ['id' => $instance->course], '*', MUST_EXIST);
    $cm = get_coursemodule_from_instance('intebchat', $instance->id, $course->id, false, MUST_EXIST);
    $context = context_module::instance($cm->id);

    // Check permissions
    require_capability('mod/intebchat:view', $context);

    // Get API configuration
    $config = get_config('mod_intebchat');
    $apikey = !empty($instance->apikey) ? $instance->apikey : $config->apikey;

    if (empty($apikey)) {
        throw new Exception('API key not configured');
    }

    // Verify we're using Assistant API
    $api_type = $config->type ?: 'chat';
    if ($api_type !== 'assistant') {
        throw new Exception('This endpoint requires Assistant API configuration');
    }

    // Get assistant ID
    $assistant_id = !empty($instance->assistant) ? $instance->assistant : $config->assistant;
    if (empty($assistant_id)) {
        throw new Exception('No assistant configured');
    }

    // Try to get thread_id from conversation if not provided
    if (empty($threadid) && $conversationid) {
        $conversation = $DB->get_record('intebchat_conversations', ['id' => $conversationid]);
        if ($conversation && !empty($conversation->threadid)) {
            $threadid = $conversation->threadid;
        }
    }

    // Create conversation if needed and logging is enabled
    if (!$conversationid && $config->logging && isloggedin()) {
        $conversationid = intebchat_create_conversation($instanceid, $USER->id);
    }

    // Prepare instance settings for the completion engine
    $instance_settings = [
        'sourceoftruth' => $instance->sourceoftruth ?? '',
        'prompt' => $instance->prompt ?? $config->prompt ?? '',
        'instructions' => $instance->instructions ?? '',
        'assistantname' => $instance->assistantname ?? $config->assistantname ?? 'Assistant',
        'apikey' => $apikey,
        'assistant' => $assistant_id,
    ];

    // Use the Assistant completion engine
    require_once($CFG->dirroot . '/mod/intebchat/classes/completion/assistant.php');
    $completion = new \mod_intebchat\completion\assistant(
        'gpt-4o', // Model is determined by the Assistant
        $question,
        [], // No history needed - Assistant maintains its own thread
        $instance_settings,
        $threadid
    );

    // Create completion
    $result = $completion->create_completion($context);

    // Get the new thread_id if it was created
    $new_threadid = $result['thread_id'] ?? $threadid;

    // Save thread_id to conversation if we have one
    if ($conversationid && $new_threadid) {
        $DB->set_field('intebchat_conversations', 'threadid', $new_threadid, ['id' => $conversationid]);
    }

    // Log the message if logging is enabled
    $tokeninfo = null;
    if (isset($result['usage'])) {
        $tokeninfo = [
            'prompt' => $result['usage']['prompt_tokens'] ?? 0,
            'completion' => $result['usage']['completion_tokens'] ?? 0,
            'total' => $result['usage']['total_tokens'] ?? 0,
        ];
    }

    if ($conversationid && $config->logging) {
        intebchat_log_message($instanceid, $conversationid, $question, $result['message'] ?? '', $context, $tokeninfo);
    }

    // Update token usage
    if (!empty($config->enabletokenlimit) && $tokeninfo) {
        intebchat_update_token_usage($USER->id, $tokeninfo['total']);
    }

    // Return success response
    echo json_encode([
        'success' => true,
        'message' => $result['message'] ?? '',
        'threadId' => $new_threadid,
        'conversationId' => $conversationid,
        'tokenInfo' => $tokeninfo,
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
    ]);
}
