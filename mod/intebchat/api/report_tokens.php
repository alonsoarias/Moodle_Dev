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
 * API endpoint for reporting token usage from realtime mode
 *
 * @package    mod_intebchat
 * @copyright  2025 Alonso Arias <soporte@ingeweb.co>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('AJAX_SCRIPT', true);

require_once(dirname(dirname(dirname(dirname(__FILE__)))) . '/config.php');
require_once(dirname(dirname(__FILE__)) . '/lib.php');

// Ensure user is logged in
require_login();

// Set JSON content type
header('Content-Type: application/json; charset=utf-8');

// Get JSON input
$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (!$data) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid JSON input']);
    exit;
}

// Validate sesskey
$sesskey = $data['sesskey'] ?? '';
if (!confirm_sesskey($sesskey)) {
    http_response_code(403);
    echo json_encode(['error' => 'Invalid session key']);
    exit;
}

// Get parameters
$instanceid = clean_param($data['instanceId'] ?? 0, PARAM_INT);
$tokens = clean_param($data['tokens'] ?? 0, PARAM_INT);
$inputtokens = clean_param($data['inputTokens'] ?? 0, PARAM_INT);
$outputtokens = clean_param($data['outputTokens'] ?? 0, PARAM_INT);
$audioinputtokens = clean_param($data['audioInputTokens'] ?? 0, PARAM_INT);
$audiooutputtokens = clean_param($data['audioOutputTokens'] ?? 0, PARAM_INT);

// Validate instance
if ($instanceid <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid instance ID']);
    exit;
}

// Validate tokens
if ($tokens <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid token count']);
    exit;
}

try {
    global $USER, $DB;

    // Get instance to verify it exists
    $instance = $DB->get_record('intebchat', ['id' => $instanceid], '*', MUST_EXIST);

    // Get config
    $config = get_config('mod_intebchat');

    // Update token usage
    $limitexceeded = false;
    $message = '';

    if (!empty($config->enabletokenlimit)) {
        // Update the user's token usage
        intebchat_update_token_usage($USER->id, $tokens);

        // Check if limit is now exceeded
        $tokeninfo = intebchat_check_token_limit($USER->id);

        if (!$tokeninfo['allowed']) {
            $limitexceeded = true;
            $message = get_string('tokenlimitexceeded', 'mod_intebchat', [
                'used' => $tokeninfo['used'],
                'limit' => $tokeninfo['limit'],
                'reset' => userdate($tokeninfo['reset_time'])
            ]);
        }
    }

    // Return success response with token info
    echo json_encode([
        'success' => true,
        'tokensRecorded' => $tokens,
        'limitExceeded' => $limitexceeded,
        'message' => $message,
        'tokenInfo' => [
            'total' => $tokens,
            'prompt' => $inputtokens,
            'completion' => $outputtokens,
            'audio_input' => $audioinputtokens,
            'audio_output' => $audiooutputtokens
        ]
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Server error',
        'message' => $e->getMessage()
    ]);
}
