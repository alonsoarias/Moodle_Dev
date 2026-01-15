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
 * Generate ephemeral token for OpenAI Realtime API
 *
 * @package    mod_intebchat
 * @copyright  2025 Alonso Arias <soporte@ingeweb.co>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// Define AJAX_SCRIPT before requiring config.php to avoid redirect issues
define('AJAX_SCRIPT', true);

require_once(__DIR__ . '/../../../config.php');
require_once($CFG->dirroot . '/mod/intebchat/lib.php');

// Set up proper headers early
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');

try {
    // Check if user is logged in
    require_login(null, false, null, false, true);
    
    // Get parameters
    $instanceid = required_param('instanceid', PARAM_INT);
    $voice = optional_param('voice', 'alloy', PARAM_TEXT);
    $audiomode = optional_param('audiomode', 'conversacional', PARAM_TEXT);
    
    // Verify instance exists
    $instance = $DB->get_record('intebchat', ['id' => $instanceid]);
    if (!$instance) {
        throw new Exception('Instance not found');
    }
    
    // Get course module
    $cm = get_coursemodule_from_instance('intebchat', $instance->id, $instance->course, false);
    if (!$cm) {
        throw new Exception('Course module not found');
    }
    
    // Check permissions
    $context = context_module::instance($cm->id);
    require_capability('mod/intebchat:view', $context);
    
    // Get API key - check instance first, then global
    $config = get_config('mod_intebchat');
    $apikey = '';
    
    if (!empty($instance->apikey)) {
        $apikey = $instance->apikey;
    } else if (!empty($config->apikey)) {
        $apikey = $config->apikey;
    }
    
    if (empty($apikey)) {
        throw new Exception('API key not configured');
    }
    
    // Get model to use - prefer realtime model
    $model = 'gpt-4o-realtime-preview-2024-12-17';
    
    // Get user language
    $userlang = current_language();
    $language = substr($userlang, 0, 2); // es, en, etc.
    if ($language !== 'es' && $language !== 'en') {
        $language = 'es'; // Default to Spanish
    }
    
    // Get instructions and assistant name
    $instructions = 'Eres un asistente útil y amigable.';
    if (!empty($instance->instructions)) {
        $instructions = $instance->instructions;
    } else if (!empty($config->prompt)) {
        $instructions = $config->prompt;
    }
    
    $assistantname = 'Asistente';
    if (!empty($instance->assistantname)) {
        $assistantname = $instance->assistantname;
    } else if (!empty($config->assistantname)) {
        $assistantname = $config->assistantname;
    }
    
    // Get voice setting
    if (!empty($instance->voice)) {
        $voice = $instance->voice;
    }
    
    // Check if using conversacional_assistant mode
    $use_assistant_tool = ($audiomode === 'conversacional_assistant');

    // Get assistant info if using assistant mode
    $assistant_id = null;
    if ($use_assistant_tool) {
        $assistant_id = !empty($instance->assistant) ? $instance->assistant : ($config->assistant ?? null);
        if (empty($assistant_id)) {
            throw new Exception('No assistant configured for conversacional_assistant mode');
        }
    }

    // Build base instructions
    $base_instructions = $instructions . ' Tu nombre es ' . $assistantname . '. Responde en ' . ($language === 'es' ? 'español' : 'inglés') . ' de manera clara y útil.';

    // Add assistant delegation instructions if using that mode
    if ($use_assistant_tool) {
        $assistant_instructions = $language === 'es'
            ? ' IMPORTANTE: Para TODAS las preguntas del usuario, SIEMPRE debes usar la función ask_assistant para consultar al experto. Cuando recibas su respuesta, comunícala de forma natural y conversacional, manteniendo el contenido y significado pero adaptándola a una conversación fluida de voz. No añadas información que no esté en la respuesta original.'
            : ' IMPORTANT: For ALL user questions, you MUST ALWAYS use the ask_assistant function to consult the expert. When you receive their response, communicate it naturally and conversationally, keeping the content and meaning but adapting it to a fluid voice conversation. Do not add information not present in the original response.';
        $base_instructions .= $assistant_instructions;
    }

    // Build session configuration
    $session_config = [
        'model' => $model,
        'voice' => $voice,
        'modalities' => ['audio', 'text'],
        'instructions' => $base_instructions,
        'input_audio_transcription' => [
            'model' => 'gpt-4o-mini-transcribe',
            'language' => $language
        ],
        'turn_detection' => [
            'type' => 'server_vad',
            'create_response' => true,
            'interrupt_response' => true,
            'silence_duration_ms' => 200,
            'prefix_padding_ms' => 300,
            'threshold' => 0.5
        ]
    ];

    // Add assistant tool if using conversacional_assistant mode
    if ($use_assistant_tool) {
        $session_config['tools'] = [
            [
                'type' => 'function',
                'name' => 'ask_assistant',
                'description' => $language === 'es'
                    ? 'OBLIGATORIO para TODAS las preguntas. Consulta al asistente especializado y comunica su respuesta de forma natural y conversacional.'
                    : 'REQUIRED for ALL questions. Consult the specialized assistant and communicate their response naturally and conversationally.',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'question' => [
                            'type' => 'string',
                            'description' => $language === 'es'
                                ? 'La pregunta o consulta del usuario'
                                : 'The user question or query'
                        ]
                    ],
                    'required' => ['question']
                ]
            ]
        ];
    }
    
    // Make request to OpenAI
    $ch = curl_init('https://api.openai.com/v1/realtime/sessions');
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_HTTPHEADER => [
            'Authorization: Bearer ' . $apikey,
            'Content-Type: application/json',
            'OpenAI-Beta: realtime=v1'
        ],
        CURLOPT_POSTFIELDS => json_encode($session_config, JSON_UNESCAPED_UNICODE)
    ]);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_error = curl_error($ch);
    curl_close($ch);
    
    if ($response === false) {
        throw new Exception('CURL error: ' . $curl_error);
    }
    
    if ($http_code >= 400) {
        $error_data = json_decode($response, true);
        $error_message = isset($error_data['error']['message']) ? 
                        $error_data['error']['message'] : 
                        'HTTP ' . $http_code . ' error from OpenAI';
        throw new Exception($error_message);
    }
    
    $data = json_decode($response, true);
    if (!$data || !isset($data['client_secret']['value'])) {
        throw new Exception('Invalid response from OpenAI');
    }
    
    // Return success response
    echo json_encode([
        'model' => $data['model'] ?? $model,
        'ephemeral' => $data['client_secret']['value'],
        'instanceId' => $instanceid,
        'voice' => $voice,
        'audiomode' => $audiomode,
        'useAssistant' => $use_assistant_tool,
        'sesskey' => sesskey()
    ], JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => $e->getMessage(),
        'detail' => 'Server error in realtime_token.php'
    ]);
}
exit;