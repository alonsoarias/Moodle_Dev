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

require_once(__DIR__ . '/../config.php');

authenticate_user_login(null, false); // Do not force login, allow guests.

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

$logger = new \local_educambot\local\logger();
$logger->log($sessionid, $question, $result['response'], $result['ruleid'], $result['confidence'], $userid, $page);

if ($result['response'] === null) {
    $logger->record_unanswered($question, $userid, $page);
    $result['response'] = get_string('noanswer', 'local_educambot');
}

$payload = [
    'response' => $result['response'],
    'ruleid' => $result['ruleid'],
    'confidence' => $result['confidence'],
    'sessionid' => $sessionid,
    'suggestions' => $result['suggestions'],
];

@header('Content-Type: application/json');
echo json_encode($payload);
die;
