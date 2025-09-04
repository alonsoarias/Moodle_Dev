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
 * Hook function for filtering participants (used when patch is applied)
 *
 * @package   local_assignhideunsubmitted
 * @copyright 2024 Your Organization
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Filter participants to show only those who have submitted
 */
function local_assignhideunsubmitted_filter_participants($assign, $currentgroup, $idsonly, $tablesort) {
    global $USER, $DB, $CFG;
    
    // Check if enabled
    if (!get_config('local_assignhideunsubmitted', 'enabled')) {
        return null; // Let core handle it
    }
    
    // Check role
    $roleid = (int)get_config('local_assignhideunsubmitted', 'hiderole');
    if (!$roleid) {
        return null;
    }
    
    // Check if user has the role
    $context = $assign->get_context();
    $roles = get_user_roles($context, $USER->id, true);
    $hasrole = false;
    
    foreach ($roles as $role) {
        if ((int)$role->roleid === $roleid) {
            $hasrole = true;
            break;
        }
    }
    
    if (!$hasrole) {
        return null; // User doesn't have the configured role
    }
    
    // Now we need to get the original participant list
    // We'll do this by temporarily disabling our hook
    static $recursion = false;
    if ($recursion) {
        return null; // Prevent infinite recursion
    }
    
    $recursion = true;
    
    // Get participants using database query
    $sql = "SELECT DISTINCT u.*
            FROM {user} u
            JOIN {user_enrolments} ue ON u.id = ue.userid
            JOIN {enrol} e ON ue.enrolid = e.id
            WHERE e.courseid = :courseid
              AND u.deleted = 0
              AND u.suspended = 0
            ORDER BY u.lastname, u.firstname";
    
    $course = $assign->get_course();
    $allparticipants = $DB->get_records_sql($sql, ['courseid' => $course->id]);
    
    $recursion = false;
    
    if (empty($allparticipants)) {
        return $idsonly ? [] : [];
    }
    
    // Filter by submission status
    $participantids = array_keys($allparticipants);
    list($insql, $params) = $DB->get_in_or_equal($participantids, SQL_PARAMS_NAMED);
    
    $params['assignid'] = $assign->get_instance()->id;
    $params['submitted'] = ASSIGN_SUBMISSION_STATUS_SUBMITTED;
    
    $sql = "SELECT DISTINCT s.userid
            FROM {assign_submission} s
            WHERE s.assignment = :assignid
              AND s.latest = 1
              AND s.status = :submitted
              AND s.userid $insql";
    
    $submittedusers = $DB->get_records_sql($sql, $params);
    
    // Build filtered list
    $filtered = [];
    foreach ($submittedusers as $record) {
        if (isset($allparticipants[$record->userid])) {
            if ($idsonly) {
                $filtered[$record->userid] = (object)['id' => $record->userid];
            } else {
                $filtered[$record->userid] = $allparticipants[$record->userid];
            }
        }
    }
    
    return $filtered;
}