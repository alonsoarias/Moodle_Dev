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
 * API endpoint for retrieving GPT completion with conversation support
 *
 * @package    mod_intebchat
 * @copyright  2025 Alonso Arias <soporte@ingeweb.co>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use \mod_intebchat\completion;

require_once('../../../config.php');
require_once($CFG->libdir . '/filelib.php');
require_once($CFG->dirroot . '/mod/intebchat/lib.php');
require_once($CFG->dirroot . '/mod/intebchat/locallib.php');

global $DB, $PAGE, $USER;

if (get_config('mod_intebchat', 'restrictusage') !== "0") {
    require_login();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: $CFG->wwwroot");
    die();
}

$body = json_decode(file_get_contents('php://input'), true);
$message = clean_param($body['message'], PARAM_NOTAGS);
$history = isset($body['history']) ? clean_param_array($body['history'], PARAM_NOTAGS, true) : [];
$instance_id = clean_param($body['instanceId'], PARAM_INT);
$conversation_id = isset($body['conversationId']) ? clean_param($body['conversationId'], PARAM_INT) : null;
$thread_id = isset($body['threadId']) ? clean_param($body['threadId'], PARAM_NOTAGS) : null;
$audio = isset($body['audio']) ? $body['audio'] : null;

// Get the instance record
$instance = $DB->get_record('intebchat', ['id' => $instance_id], '*', MUST_EXIST);
$course = $DB->get_record('course', ['id' => $instance->course], '*', MUST_EXIST);
$cm = get_coursemodule_from_instance('intebchat', $instance->id, $course->id, false, MUST_EXIST);

$context = context_module::instance($cm->id);
$PAGE->set_context($context);

// Handle audio transcription if provided
$transcription = null;
if ($audio && !empty($instance->enableaudio)) {
    require_once($CFG->dirroot . '/mod/intebchat/classes/audio.php');
    $trans = \mod_intebchat\audio::transcribe($audio, current_language());
    $message = $trans['text'];
    $transcription = $trans['text'];
}

// Check token limit before processing
$config = get_config('mod_intebchat');
if (!empty($config->enabletokenlimit)) {
    $token_limit_info = intebchat_check_token_limit($USER->id);
    
    if (!$token_limit_info['allowed']) {
        $response = [
            'error' => [
                'type' => 'token_limit_exceeded',
                'message' => get_string('tokenlimitexceeded', 'mod_intebchat', [
                    'used' => $token_limit_info['used'],
                    'limit' => $token_limit_info['limit'],
                    'reset' => userdate($token_limit_info['reset_time'])
                ])
            ]
        ];
        header('Content-Type: application/json');
        echo json_encode($response);
        exit;
    }
}

// Create conversation if not provided and logging is enabled
if (!$conversation_id && $config->logging && isloggedin()) {
    $conversation_id = intebchat_create_conversation($instance_id, $USER->id);
}

// Prepare instance settings
$instance_settings = [];
$setting_names = [
    'sourceoftruth', 
    'prompt',
    'instructions',
    'assistantname', 
    'apikey', 
    'model', 
    'temperature', 
    'maxlength', 
    'topp', 
    'frequency', 
    'presence',
    'assistant'
];
foreach ($setting_names as $setting) {
    if (property_exists($instance, $setting)) {
        $instance_settings[$setting] = $instance->$setting ? $instance->$setting : "";
    } else {
        $instance_settings[$setting] = "";
    }
}

// Get API configuration
$apiconfig = intebchat_get_api_config($instance);
$api_type = $config->type ?: 'chat';
$model = $apiconfig['model'];

// Validate API key
if (empty($apiconfig['apikey'])) {
    $response = [
        'error' => [
            'type' => 'configuration_error',
            'message' => get_string('apikeymissing', 'mod_intebchat')
        ]
    ];
    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}

// Create completion engine
$engine_class = "\mod_intebchat\completion\\$api_type";

if (!class_exists($engine_class)) {
    $response = [
        'error' => [
            'type' => 'configuration_error',
            'message' => 'Invalid API type configuration'
        ]
    ];
    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}

try {
    $completion = new $engine_class($model, $message, $history, $instance_settings, $thread_id);
    $response = $completion->create_completion($context);

    // Format the markdown of each completion message into HTML.
    $response["message"] = format_text($response["message"], FORMAT_MARKDOWN, ['context' => $context]);

    // Normalize token usage from different API response formats
    $tokeninfo = null;
    if (isset($response['usage']) && is_array($response['usage'])) {
        $tokeninfo = intebchat_normalize_usage($response['usage']);
        if ($tokeninfo) {
            $response['tokenInfo'] = $tokeninfo;
        }
        unset($response['usage']); // Remove internal usage data from response
    }

    // Log the message with conversation support if conversation exists
    if ($conversation_id && $config->logging) {
        intebchat_log_message($instance_id, $conversation_id, $message, $response['message'], $context, $tokeninfo);
        
        // Update conversation title if it's the first message
        $messagecount = $DB->count_records('mod_intebchat_log', ['conversationid' => $conversation_id]);
        if ($messagecount <= 2) { // User message + AI response
            $title = intebchat_generate_conversation_title($message);
            intebchat_update_conversation($conversation_id, $title);
        }
    }
    
    // Add audio response if enabled
    if (!empty($instance->enableaudio) && ($instance->audiomode === 'audio' || $instance->audiomode === 'both')) {
        $voice = get_config('mod_intebchat', 'voice') ?: 'alloy';
        $audiosrc = \mod_intebchat\audio::speech(strip_tags($response['message']), $voice);
        $response['message'] = "<audio controls autoplay src='{$audiosrc}'></audio><div class='transcription'>{$response['message']}</div>";
    }
    
    // Add conversation ID and transcription to response
    $response['conversationId'] = $conversation_id;
    if ($transcription) {
        $response['transcription'] = $transcription;
    }

} catch (Exception $e) {
    $response = [
        'error' => [
            'type' => 'api_error',
            'message' => $e->getMessage()
        ]
    ];
}

header('Content-Type: application/json');
echo json_encode($response);