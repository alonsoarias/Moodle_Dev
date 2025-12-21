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
 * @author      Alonso Arias <soporte@ingeweb.co>
 * @copyright   2025 Ingeweb <https://ingeweb.co>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('AJAX_SCRIPT', true);

require_once(__DIR__ . '/../../config.php');

use local_educambot\bot\context_handler;
use local_educambot\bot\shortcut_handler;
use local_educambot\bot\response_builder;

// Require login and valid session.
require_login();
require_sesskey();

$context = context_system::instance();
$PAGE->set_context($context);
require_capability('local/educambot:use', $context);

// Get parameters.
$question = required_param('question', PARAM_TEXT);
$courseid = optional_param('courseid', SITEID, PARAM_INT);

// Validate question length.
if (empty(trim($question)) || strlen($question) > 1000) {
    echo json_encode([
        'success' => false,
        'error' => get_string('invalidquestion', 'local_educambot'),
    ]);
    exit;
}

// Initialize context handler with course context.
$contexthandler = new context_handler($courseid, $USER->id);

// Initialize shortcut handler (reads from database).
$shortcuthandler = new shortcut_handler($contexthandler);

// First, check if this is a shortcut command.
$shortcutresult = $shortcuthandler->process_shortcut($question);

if ($shortcutresult !== null) {
    // This is a shortcut response.
    $response = [
        'success' => true,
        'response' => $shortcutresult['response'],
        'ruleid' => null,
        'confidence' => 1.0,
        'options' => $shortcutresult['options'] ?? [],
        'type' => 'shortcut',
    ];
    $matched = 1;
    $responsetext = $shortcutresult['response'];
    $ruleid = null;
    $confidence = 1.0;
} else {
    // Regular engine processing - pass courseid and userid for proper context filtering.
    $engine = new \local_educambot\bot\engine($courseid, $USER->id);
    $result = $engine->respond($question);

    // Prepare response.
    if ($result['response'] !== null) {
        $responsetext = $result['response'];
        $ruleid = $result['ruleid'];
        $confidence = $result['confidence'];

        // Always process placeholders in responses (v2.2.2).
        // Placeholders like {{site.name}}, {{user.firstname}}, etc. are processed for all responses.
        $builder = new response_builder($contexthandler);
        $responsetext = $builder->build_response($responsetext);

        // Check if rule is context-aware.
        if ($ruleid) {
            $rule = $DB->get_record('local_educambot_rule', ['id' => $ruleid]);

            // Check if context is required and available.
            if ($rule && $rule->requiredcontext) {
                $currentcontext = $contexthandler->get_context_type();
                if ($rule->requiredcontext === 'course' && !$contexthandler->is_in_course()) {
                    // Context required but not available - show alternative message.
                    $responsetext = get_string('requirescoursecontext', 'local_educambot');
                }
            }
        }

        $response = [
            'success' => true,
            'response' => $responsetext,
            'ruleid' => $ruleid,
            'confidence' => $confidence,
            'options' => [],
            'type' => 'rule',
        ];
        $matched = 1;

        // Get options for this rule if showoptions is enabled.
        if ($ruleid) {
            $showoptions = $DB->get_field('local_educambot_rule', 'showoptions', ['id' => $ruleid]);
            if ($showoptions) {
                $options = $DB->get_records('local_educambot_option',
                    ['ruleid' => $ruleid, 'enabled' => 1],
                    'sortorder ASC',
                    'id, text, action, targetruleid, icon');
                if ($options) {
                    // Get target rule patterns for each option.
                    foreach ($options as $option) {
                        if ($option->targetruleid) {
                            $option->targetpattern = $DB->get_field('local_educambot_rule', 'pattern', ['id' => $option->targetruleid]);
                        }
                    }
                    $response['options'] = array_values($options);
                }
            }
        }
    } else {
        $response = [
            'success' => true,
            'response' => get_string('noresponse', 'local_educambot'),
            'ruleid' => null,
            'confidence' => 0,
            'options' => [],
            'type' => 'nomatch',
        ];
        $matched = 0;
        $responsetext = get_string('noresponse', 'local_educambot');
        $ruleid = null;
        $confidence = 0;
    }
}

// Add context info to response.
$response['context'] = [
    'type' => $contexthandler->get_context_type(),
    'courseid' => $contexthandler->get_course_id(),
    'incourse' => $contexthandler->is_in_course(),
];

// Log the conversation.
$log = new stdClass();
$log->userid = $USER->id;
$log->question = $question;
$log->response = $responsetext;
$log->ruleid = $ruleid ?? null;
$log->confidence = $confidence ?? 0;
$log->matched = $matched;
$log->timecreated = time();
$DB->insert_record('local_educambot_log', $log);

// Send JSON response.
header('Content-Type: application/json');
echo json_encode($response);
