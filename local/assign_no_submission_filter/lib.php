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
 * Library functions for local_assign_no_submission_filter
 *
 * @package    local_assign_no_submission_filter
 * @copyright  2024 Your Organization
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Get the filtered user list for an assignment
 * 
 * @param int $assignid Assignment ID
 * @return array Array of user IDs who have submissions
 */
function local_assign_no_submission_filter_get_submitted_users($assignid) {
    global $DB;
    
    $sql = "SELECT DISTINCT s.userid
            FROM {assign_submission} s
            WHERE s.assignment = :assignid
              AND s.latest = 1
              AND s.status = 'submitted'
              AND s.timemodified IS NOT NULL";
    
    return $DB->get_fieldset_sql($sql, ['assignid' => $assignid]);
}

/**
 * Override the grading table class
 */
function local_assign_no_submission_filter_override_grading_table() {
    global $CFG;
    
    // Check if we should override
    if (!get_config('local_assign_no_submission_filter', 'enabled')) {
        return;
    }
    
    // Register our custom grading table class
    if (!class_exists('\assign_grading_table', false)) {
        // Register autoloader for our custom table
        spl_autoload_register(function($classname) use ($CFG) {
            if ($classname === 'assign_grading_table') {
                // First load the original class with a different name
                require_once($CFG->dirroot . '/mod/assign/gradingtable.php');
                // Then load our custom class
                require_once($CFG->dirroot . '/local/assign_no_submission_filter/classes/custom_grading_table.php');
                // Create alias
                class_alias('\local_assign_no_submission_filter\custom_grading_table', 'assign_grading_table');
                return true;
            }
            return false;
        }, true, true);
    }
}