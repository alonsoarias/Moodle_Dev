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
 * AJAX endpoint for submitting feedback on bot responses (v2.0.0).
 *
 * @package     local_educambot
 * @author      Alonso Arias <soporte@ingeweb.co>
 * @copyright   2025 Ingeweb <https://ingeweb.co>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('AJAX_SCRIPT', true);

require_once(__DIR__ . '/../../config.php');

// Verify session - require_login must come first.
require_login();
require_sesskey();

// Get parameters.
$ruleid = required_param('ruleid', PARAM_INT);
$helpful = required_param('helpful', PARAM_INT);

header('Content-Type: application/json; charset=utf-8');

try {
    global $DB, $USER;

    // Validate helpful value.
    $helpful = $helpful ? 1 : 0;

    // Check if rule exists.
    $rule = $DB->get_record('local_educambot_rule', ['id' => $ruleid]);
    if (!$rule) {
        echo json_encode([
            'success' => false,
            'error' => get_string('rulenotfound', 'local_educambot')
        ]);
        die();
    }

    // Check if user already submitted feedback for this rule recently (last 24 hours).
    $recentfeedback = $DB->get_record_select(
        'local_educambot_feedback',
        'userid = :userid AND ruleid = :ruleid AND timecreated > :timeago',
        [
            'userid' => $USER->id,
            'ruleid' => $ruleid,
            'timeago' => time() - 86400 // 24 hours.
        ]
    );

    if ($recentfeedback) {
        // Update existing feedback.
        $recentfeedback->helpful = $helpful;
        $recentfeedback->timemodified = time();
        $DB->update_record('local_educambot_feedback', $recentfeedback);
    } else {
        // Insert new feedback.
        $feedback = new stdClass();
        $feedback->userid = $USER->id;
        $feedback->ruleid = $ruleid;
        $feedback->helpful = $helpful;
        $feedback->timecreated = time();
        $feedback->timemodified = time();
        $DB->insert_record('local_educambot_feedback', $feedback);
    }

    // Update rule statistics.
    if ($helpful) {
        $rule->helpfulcount = ($rule->helpfulcount ?? 0) + 1;
    } else {
        $rule->nothelpfulcount = ($rule->nothelpfulcount ?? 0) + 1;
    }
    $rule->timemodified = time();
    $DB->update_record('local_educambot_rule', $rule);

    echo json_encode([
        'success' => true,
        'message' => get_string('feedback_thanks', 'local_educambot')
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
