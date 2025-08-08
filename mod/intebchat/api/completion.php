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
 * API endpoint for retrieving GPT completion with conversation support and enhanced token tracking
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
$response_mode = isset($body['responseMode']) ? clean_param($body['responseMode'], PARAM_TEXT) : 'text';

// Get the instance record
$instance = $DB->get_record('intebchat', ['id' => $instance_id], '*', MUST_EXIST);
$course = $DB->get_record('course', ['id' => $instance->course], '*', MUST_EXIST);
$cm = get_coursemodule_from_instance('intebchat', $instance->id, $course->id, false, MUST_EXIST);

$context = context_module::instance($cm->id);
$PAGE->set_context($context);

// Si tenemos conversation_id pero no thread_id, intentar recuperarlo
$api_type = get_config('mod_intebchat', 'type') ?: 'chat';
if ($conversation_id && !$thread_id && $api_type === 'assistant') {
    $conversation = $DB->get_record('mod_intebchat_conversations', ['id' => $conversation_id]);
    if ($conversation && !empty($conversation->threadid)) {
        $thread_id = $conversation->threadid;
    }
}

// Handle audio transcription if provided with enhanced token tracking
$transcription = null;
$useraudio = null;
$audio_input_tokens = 0; // Track audio input tokens

if ($audio && !empty($instance->enableaudio)) {
    require_once($CFG->dirroot . '/mod/intebchat/classes/audio.php');
    $trans = \mod_intebchat\audio::transcribe($audio, current_language());
    $message = $trans['text'];
    $transcription = $trans['text'];
    $useraudio = $CFG->wwwroot . '/mod/intebchat/load-audio-temp.php?filename=' . $trans['filename'];
    
    // Calculate audio tokens based on duration (approximation based on OpenAI pricing)
    // Whisper typically uses ~0.006 per second of audio
    if (isset($trans['duration'])) {
        $audio_input_tokens = ceil($trans['duration'] * 10); // Approximate token count for audio
    }
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

    // Guardar thread_id en la conversación si es nueva (para Assistant API)
    if ($api_type === 'assistant' && $conversation_id) {
        // El thread_id puede venir en la respuesta con diferentes nombres según el completion engine
        $response_thread_id = null;
        
        if (isset($response['thread_id'])) {
            $response_thread_id = $response['thread_id'];
        } elseif (isset($response['threadId'])) {
            $response_thread_id = $response['threadId'];
        }
        
        // Si tenemos un thread_id nuevo, guardarlo en la conversación
        if ($response_thread_id) {
            $conversation_record = $DB->get_record('mod_intebchat_conversations', ['id' => $conversation_id]);
            if ($conversation_record && empty($conversation_record->threadid)) {
                $DB->set_field('mod_intebchat_conversations', 'threadid', $response_thread_id, ['id' => $conversation_id]);
            }
        }
    }

    // Handle audio response generation if requested
    $audio_output_tokens = 0;
    if (!empty($instance->enableaudio) && $response_mode === 'audio') {
        require_once($CFG->dirroot . '/mod/intebchat/classes/audio.php');
        $voice = get_config('mod_intebchat', 'voice') ?: 'alloy';
        
        // Use the enhanced speech function with tracking
        $audio_result = \mod_intebchat\audio::speech_with_tracking(strip_tags($response['message']), $voice);
        
        if (!empty($audio_result['url'])) {
            $audio_output_tokens = $audio_result['tokens'];
            $response['message'] = "<audio controls autoplay src='{$audio_result['url']}'></audio><div class='transcription'>{$response['message']}</div>";
        }
    }

    // Enhanced token normalization with audio tokens
    $tokeninfo = null;
    if (isset($response['usage']) && is_array($response['usage'])) {
        $tokeninfo = intebchat_normalize_usage_enhanced($response['usage'], $audio_input_tokens, $audio_output_tokens);
    } elseif ($audio_input_tokens > 0 || $audio_output_tokens > 0) {
        // Even if no text tokens, track audio tokens
        $tokeninfo = intebchat_normalize_usage_enhanced([], $audio_input_tokens, $audio_output_tokens);
    }

    if ($tokeninfo) {
        $response['tokenInfo'] = $tokeninfo;
        
        // Update user's token usage with enhanced tracking
        if (!empty($config->enabletokenlimit)) {
            intebchat_update_token_usage($USER->id, $tokeninfo['total']);
        }
    }
    
    unset($response['usage']); // Remove internal usage data from response

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

    // Add conversation ID and transcription to response
    $response['conversationId'] = $conversation_id;
    if ($transcription) {
        $response['transcription'] = $transcription;
    }
    if (!empty($useraudio)) {
        $response['useraudio'] = $useraudio;
    }
    
    // Asegurar que el thread_id se incluya en la respuesta
    if ($api_type === 'assistant' && !empty($response_thread_id)) {
        $response['threadId'] = $response_thread_id;
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

/**
 * Enhanced token normalization with audio token tracking
 * 
 * @param array $usage Raw usage data from API response
 * @param int $audio_input_tokens Audio input tokens
 * @param int $audio_output_tokens Audio output tokens
 * @return array|null Normalized token info or null if empty
 */
function intebchat_normalize_usage_enhanced($usage, $audio_input_tokens = 0, $audio_output_tokens = 0) {
    if (empty($usage) || !is_array($usage)) {
        // Si no hay usage pero tenemos tokens de audio, crear estructura
        if ($audio_input_tokens > 0 || $audio_output_tokens > 0) {
            return [
                'prompt' => $audio_input_tokens,
                'completion' => $audio_output_tokens,
                'total' => $audio_input_tokens + $audio_output_tokens,
                'audio_input' => $audio_input_tokens,
                'audio_output' => $audio_output_tokens
            ];
        }
        return null;
    }

    // Handle both old format (prompt_tokens/completion_tokens) and new format (input_tokens/output_tokens)
    $prompt = $usage['prompt_tokens'] ?? $usage['input_tokens'] ?? 0;
    $completion = $usage['completion_tokens'] ?? $usage['output_tokens'] ?? 0;
    $total = $usage['total_tokens'] ?? ($prompt + $completion);
    
    // Add audio tokens to the total
    $total += $audio_input_tokens + $audio_output_tokens;

    // Only return if we have actual token data
    if ($total > 0) {
        return [
            'prompt' => (int)$prompt + (int)$audio_input_tokens,
            'completion' => (int)$completion + (int)$audio_output_tokens,
            'total' => (int)$total,
            'audio_input' => (int)$audio_input_tokens,
            'audio_output' => (int)$audio_output_tokens
        ];
    }

    return null;
}