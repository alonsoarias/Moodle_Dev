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
 * AJAX endpoint for getting startup options (suggested questions).
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

// Get the startup rule (special rule with __startup__ pattern).
$startuprule = $DB->get_record('local_educambot_rule', ['pattern' => '__startup__', 'enabled' => 1]);

$response = [
    'success' => true,
    'options' => [],
];

if ($startuprule && $startuprule->showoptions) {
    // Get options for startup rule.
    $options = $DB->get_records('local_educambot_option',
        ['ruleid' => $startuprule->id, 'enabled' => 1],
        'sortorder ASC',
        'id, text, targetruleid, icon');

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

// Send JSON response.
header('Content-Type: application/json');
echo json_encode($response);
