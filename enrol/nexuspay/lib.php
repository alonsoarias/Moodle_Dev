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
 * NexusPay enrolment plugin.
 *
 * This plugin allows you to set up paid courses.
 *
 * @package   enrol_nexuspay
 * @copyright 2024 Alonso Arias <soporte@nexuslabs.com.co>
 * @author    Alonso Arias
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Check if the given password match a group enrolment key in the specified course.
 *
 * @param  int $courseid            course id
 * @param  string $password    group password
 * @return string                 Groupid if match
 * @since  Moodle 3.0
 */
function enrol_nexuspay_check_group_enrolment_key($courseid, $password) {
    global $DB;

    $found = false;
    $groups = $DB->get_records('groups', ['courseid' => $courseid], 'id ASC', 'id, enrolmentkey');

    foreach ($groups as $group) {
        if (empty($group->enrolmentkey)) {
            continue;
        }
        if ($group->enrolmentkey === $password) {
            $found = $group->id;
            break;
        }
    }
    return $found;
}