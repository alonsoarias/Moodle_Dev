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
 * Streaming API endpoint for GPT completion using Server-Sent Events (SSE)
 *
 * This endpoint streams responses in real-time for better UX.
 *
 * @package    mod_intebchat
 * @copyright  2025 Alonso Arias <soporte@ingeweb.co>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../../config.php');
require_once($CFG->libdir . '/filelib.php');
require_once($CFG->dirroot . '/mod/intebchat/lib.php');
require_once($CFG->dirroot . '/mod/intebchat/locallib.php');

global $DB, $PAGE, $USER;

// Require login if configured
if (get_config('mod_intebchat', 'restrictusage') !== "0") {
    require_login();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: $CFG->wwwroot");
    die();
}

// Set SSE headers
header('Content-Type: text/event-stream');
header('Cache-Control: no-cache');
header('Connection: keep-alive');
header('X-Accel-Buffering: no'); // Disable nginx buffering

// Flush headers immediately
if (ob_get_level()) {
    ob_end_flush();
}
flush();

/**
 * Send SSE event
 */
function send_sse_event($event, $data) {
    echo "event: {$event}\n";
    echo "data: " . json_encode($data) . "\n\n";
    flush();
}

/**
 * Send SSE error and exit
 */
function send_sse_error($type, $message) {
    send_sse_event('error', ['type' => $type, 'message' => $message]);
    send_sse_event('done', ['success' => false]);
    exit;
}

// Parse request body
$body = json_decode(file_get_contents('php://input'), true);

// CSRF Protection
$sesskey = $body['sesskey'] ?? $_SERVER['HTTP_X_SESSKEY'] ?? null;
if (!$sesskey || !confirm_sesskey($sesskey)) {
    send_sse_error('security_error', get_string('invalidsesskey', 'error'));
}

// Extract and validate parameters
$message = clean_param($body['message'] ?? '', PARAM_NOTAGS);
$history = isset($body['history']) ? clean_param_array($body['history'], PARAM_NOTAGS, true) : [];
$instance_id = clean_param($body['instanceId'] ?? 0, PARAM_INT);
$conversation_id = isset($body['conversationId']) ? clean_param($body['conversationId'], PARAM_INT) : null;

// Rate limiting check
require_once($CFG->dirroot . '/mod/intebchat/classes/ratelimiter.php');
$ratelimiter = new \mod_intebchat\ratelimiter();
$ratelimit_result = $ratelimiter->check($USER->id);

if (!$ratelimit_result['allowed']) {
    send_sse_error('rate_limit_exceeded', get_string('ratelimitexceeded', 'mod_intebchat'));
}

// Validate input
$validation = intebchat_validate_input($message);
if (!$validation['valid']) {
    send_sse_error('validation_error', $validation['message']);
}
$message = $validation['sanitized'];

// Get instance and validate
try {
    $instance = $DB->get_record('intebchat', ['id' => $instance_id], '*', MUST_EXIST);
    $course = $DB->get_record('course', ['id' => $instance->course], '*', MUST_EXIST);
    $cm = get_coursemodule_from_instance('intebchat', $instance->id, $course->id, false, MUST_EXIST);
    $context = context_module::instance($cm->id);
} catch (Exception $e) {
    send_sse_error('configuration_error', 'Invalid instance');
}

// Check token limit
$config = get_config('mod_intebchat');
if (!empty($config->enabletokenlimit)) {
    $token_limit_info = intebchat_check_token_limit($USER->id);
    if (!$token_limit_info['allowed']) {
        send_sse_error('token_limit_exceeded', get_string('tokenlimitexceeded', 'mod_intebchat', [
            'used' => $token_limit_info['used'],
            'limit' => $token_limit_info['limit'],
            'reset' => userdate($token_limit_info['reset_time'])
        ]));
    }
}

// Create conversation if needed
if (!$conversation_id && $config->logging && isloggedin()) {
    $conversation_id = intebchat_create_conversation($instance_id, $USER->id);
    send_sse_event('conversation', ['id' => $conversation_id]);
}

// Get API configuration
$apiconfig = intebchat_get_api_config($instance);

if (empty($apiconfig['apikey'])) {
    send_sse_error('configuration_error', get_string('apikeymissing', 'mod_intebchat'));
}

// Build messages array
$messages = [];

// Add system prompt
$prompt = $instance->prompt ?: ($config->prompt ?: get_string('defaultprompt', 'mod_intebchat'));
$messages[] = ['role' => 'system', 'content' => $prompt];

// Add source of truth if present
if (!empty($instance->sourceoftruth)) {
    $sourceoftruth = format_string($instance->sourceoftruth, true, ['context' => $context]);
    $messages[] = ['role' => 'system', 'content' => $sourceoftruth];
}

// Add history (limited to last 20 messages)
$historyLimit = 20;
$historySlice = array_slice($history, -$historyLimit);
foreach ($historySlice as $index => $item) {
    $role = $index % 2 === 0 ? 'user' : 'assistant';
    if (isset($item['message'])) {
        $messages[] = ['role' => $role, 'content' => $item['message']];
    }
}

// Add current message
$messages[] = ['role' => 'user', 'content' => $message];

// Prepare API request with streaming enabled
$curlbody = [
    'model' => $apiconfig['model'] ?: INTEBCHAT_DEFAULT_MODEL,
    'messages' => $messages,
    'temperature' => (float)($apiconfig['temperature'] ?? INTEBCHAT_DEFAULT_TEMPERATURE),
    'max_tokens' => (int)($apiconfig['maxlength'] ?? INTEBCHAT_DEFAULT_MAX_TOKENS),
    'stream' => true
];

// Initialize cURL for streaming
$ch = curl_init('https://api.openai.com/v1/chat/completions');
curl_setopt_array($ch, [
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => json_encode($curlbody),
    CURLOPT_HTTPHEADER => [
        'Authorization: Bearer ' . intebchat_decrypt_apikey($apiconfig['apikey']),
        'Content-Type: application/json',
        'Accept: text/event-stream'
    ],
    CURLOPT_RETURNTRANSFER => false,
    CURLOPT_TIMEOUT => INTEBCHAT_API_TIMEOUT,
    CURLOPT_CONNECTTIMEOUT => INTEBCHAT_API_CONNECT_TIMEOUT,
    CURLOPT_WRITEFUNCTION => function($ch, $data) use (&$fullResponse, &$totalTokens) {
        static $buffer = '';
        $buffer .= $data;

        // Process complete SSE messages
        while (($pos = strpos($buffer, "\n\n")) !== false) {
            $chunk = substr($buffer, 0, $pos);
            $buffer = substr($buffer, $pos + 2);

            // Parse SSE data
            if (preg_match('/^data: (.+)$/m', $chunk, $matches)) {
                $jsonData = $matches[1];

                if ($jsonData === '[DONE]') {
                    continue;
                }

                $parsed = json_decode($jsonData, true);
                if ($parsed && isset($parsed['choices'][0]['delta']['content'])) {
                    $content = $parsed['choices'][0]['delta']['content'];
                    $fullResponse .= $content;

                    // Send chunk to client
                    send_sse_event('chunk', ['content' => $content]);
                }

                // Capture usage if present (usually in final message)
                if (isset($parsed['usage'])) {
                    $totalTokens = $parsed['usage'];
                }
            }
        }

        return strlen($data);
    }
]);

// Initialize response tracking
$fullResponse = '';
$totalTokens = null;

// Execute streaming request
send_sse_event('start', ['message' => 'Streaming started']);

$result = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);
curl_close($ch);

if ($result === false || $httpCode !== 200) {
    send_sse_error('api_error', 'API request failed: ' . ($curlError ?: "HTTP $httpCode"));
}

// Process final response
$formattedResponse = format_text($fullResponse, FORMAT_MARKDOWN, ['context' => $context]);

// Log message if conversation exists
$tokeninfo = null;
if ($totalTokens) {
    $tokeninfo = intebchat_normalize_usage($totalTokens);

    // Update token usage
    if (!empty($config->enabletokenlimit) && $tokeninfo) {
        intebchat_update_token_usage($USER->id, $tokeninfo['total']);
    }
}

if ($conversation_id && $config->logging) {
    intebchat_log_message($instance_id, $conversation_id, $message, $fullResponse, $context, $tokeninfo);

    // Update conversation title if first message
    $messagecount = $DB->count_records('intebchat_log', ['conversationid' => $conversation_id]);
    if ($messagecount <= 2) {
        $title = intebchat_generate_conversation_title($message);
        intebchat_update_conversation($conversation_id, $title);
    }
}

// Send completion event
send_sse_event('complete', [
    'message' => $formattedResponse,
    'conversationId' => $conversation_id,
    'tokenInfo' => $tokeninfo
]);

send_sse_event('done', ['success' => true]);
