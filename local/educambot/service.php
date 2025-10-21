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
 * AJAX endpoint for the Educam Bot widget.
 *
 * @package     local_educambot
 * @copyright   2024 Educam
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('AJAX_SCRIPT', true);

require_once(__DIR__ . '/../../config.php');

require_sesskey();

$question = required_param('question', PARAM_RAW_TRIMMED);
$sessionid = optional_param('sessionid', '', PARAM_ALPHANUMEXT);
$page = optional_param('page', '', PARAM_RAW_TRIMMED);

if ($sessionid === '') {
    $sessionid = sesskey();
}

$userid = isloggedin() && !isguestuser() ? (int)$USER->id : null;

$engine = new \local_educambot\bot\engine($userid, $page);
$result = $engine->respond($question);

$response = $result['response'] ?? null;
if (!is_string($response) || trim($response) === '') {
    $response = get_string('noanswer', 'local_educambot');
}

$suggestions = $result['suggestions'] ?? [];
if (!is_array($suggestions)) {
    $suggestions = [];
}

$confidence = isset($result['confidence']) ? (float)$result['confidence'] : 0.0;
$confidence = max(0.0, min(1.0, $confidence));

$logger = new \local_educambot\local\logger();
$logger->log($sessionid, $question, $response, $result['ruleid'], $confidence, $userid, $page);

$ruleid = $result['ruleid'] ?? null;

if ($result['response'] === null) {
    $logger->record_unanswered($question, $userid, $page);
    $response = get_string('noanswer', 'local_educambot');
}

$payload = [
    'response' => $response,
    'ruleid' => $ruleid,
    'confidence' => $confidence,
    'sessionid' => $sessionid,
    'suggestions' => array_values($suggestions),
];

@header('Content-Type: application/json');
echo json_encode($payload);
die;
