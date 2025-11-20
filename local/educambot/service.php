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
 * AJAX service endpoint for bot questions.
 *
 * @package     local_educambot
 * @copyright   2025 EducamBot Team
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('AJAX_SCRIPT', true);

require_once(__DIR__ . '/../../config.php');

// Require login and valid session.
require_login();
require_sesskey();

$context = context_system::instance();
require_capability('local/educambot:use', $context);

// Get parameters.
$question = required_param('question', PARAM_TEXT);

// Validate question length.
if (empty(trim($question)) || strlen($question) > 1000) {
    echo json_encode([
        'success' => false,
        'error' => get_string('invalidquestion', 'local_educambot'),
    ]);
    exit;
}

// Create engine instance and get response.
$engine = new \local_educambot\bot\engine();
$result = $engine->respond($question);

// Prepare response.
if ($result['response'] !== null) {
    $response = [
        'success' => true,
        'response' => $result['response'],
        'ruleid' => $result['ruleid'],
        'confidence' => $result['confidence'],
    ];
} else {
    $response = [
        'success' => true,
        'response' => get_string('noresponse', 'local_educambot'),
        'ruleid' => null,
        'confidence' => 0,
    ];
}

// Send JSON response.
header('Content-Type: application/json');
echo json_encode($response);
