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
 * Library callbacks for local_assignhideunsubmitted.
 *
 * @package   local_assignhideunsubmitted
 * @copyright 2024
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Enforce the "submitted" grading filter for selected roles.
 */
function local_assignhideunsubmitted_before_http_headers() {
    global $PAGE, $USER, $CFG;

    if (empty($PAGE->cm) || $PAGE->cm->modname !== 'assign') {
        return;
    }

    $action = optional_param('action', '', PARAM_ALPHA);
    if ($action !== 'grading') {
        return;
    }

    $roleid = (int)get_config('local_assignhideunsubmitted', 'hiderole');
    if (!$roleid) {
        return;
    }

    $context = context_module::instance($PAGE->cm->id);
    $roles = get_user_roles($context, $USER->id, true);
    $hasrole = false;
    foreach ($roles as $role) {
        if ((int)$role->roleid === $roleid) {
            $hasrole = true;
            break;
        }
    }
    if (!$hasrole) {
        return;
    }

    require_once($CFG->dirroot . '/mod/assign/locallib.php');
    set_user_preference('assign_filter', ASSIGN_FILTER_SUBMITTED);
}
