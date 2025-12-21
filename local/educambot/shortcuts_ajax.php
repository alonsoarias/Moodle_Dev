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
 * AJAX endpoint for getting shortcuts (v3.0.4).
 *
 * Returns enabled shortcuts from database for display in the widget.
 * Filters shortcuts based on context (course vs site) AND user role archetype.
 * Site administrators get access to all shortcuts.
 *
 * @package     local_educambot
 * @author      Alonso Arias <soporte@ingeweb.co>
 * @copyright   2025 Ingeweb <https://ingeweb.co>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('AJAX_SCRIPT', true);

require_once(__DIR__ . '/../../config.php');

use local_educambot\bot\context_handler;

// Require login and valid session.
require_login();
require_sesskey();

$context = context_system::instance();
$PAGE->set_context($context);
require_capability('local/educambot:use', $context);

// Get parameters from request.
$courseid = optional_param('courseid', SITEID, PARAM_INT);
$incourse = ($courseid != SITEID);

// Initialize context handler.
$contexthandler = new context_handler($courseid, $USER->id);

// Get user's role archetype.
$userrole = $contexthandler->get_user_archetype();

// Check if user is site administrator.
$issiteadmin = is_siteadmin();

// Determine effective role: site admin overrides other roles.
$effectiverole = $issiteadmin ? 'siteadmin' : $userrole;

$response = [
    'success' => true,
    'shortcuts' => [],
    'context' => $incourse ? 'course' : 'site',
    'role' => $effectiverole,
    'issiteadmin' => $issiteadmin,
];

// Get shortcuts from database.
$shortcuts = $DB->get_records('local_educambot_shortcut', ['enabled' => 1], 'sortorder ASC');

foreach ($shortcuts as $shortcut) {
    // Get shortcut roles from database.
    $shortcutroles = [];
    if (!empty($shortcut->roles)) {
        $shortcutroles = array_map('trim', explode(',', $shortcut->roles));
    }
    $shortcutcontext = $shortcut->context ?? 'any';

    // Filter by role - check if user's role is in shortcut's allowed roles.
    // Site admin gets access to all shortcuts.
    // Empty roles means accessible to everyone.
    if (!$issiteadmin && !empty($shortcutroles) && !in_array($userrole, $shortcutroles)) {
        continue;
    }

    // Filter by context.
    // If at site level: only show shortcuts with context "any".
    // If in course: show shortcuts with context "any" or "course".
    if (!$incourse && $shortcutcontext === 'course') {
        continue;
    }

    // Get first keyword as action.
    $keywords = explode("\n", $shortcut->keywords);
    $action = trim($keywords[0] ?? $shortcut->name);

    $response['shortcuts'][] = [
        'id' => $shortcut->id,
        'name' => $shortcut->name,
        'description' => $shortcut->description ?? '',
        'icon' => $shortcut->icon ?? '',
        'action' => $action,
        'actiontype' => $shortcut->actiontype,
        'sortorder' => $shortcut->sortorder ?? 0,
    ];
}

// Send JSON response.
header('Content-Type: application/json');
echo json_encode($response);
