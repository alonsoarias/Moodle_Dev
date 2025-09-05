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
 * Filter service for local_assign_no_submission_filter
 *
 * @package    local_assign_no_submission_filter
 * @copyright  2024 Your Organization
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_assign_no_submission_filter;

defined('MOODLE_INTERNAL') || die();

/**
 * Service class for filtering logic
 */
class filter_service {
    
    /** @var \moodle_database Database connection */
    protected $db;
    
    /**
     * Constructor with dependency injection
     */
    public function __construct() {
        global $DB;
        $this->db = $DB;
    }
    
    /**
     * Check if user has submission for assignment
     *
     * @param int $userid
     * @param int $assignmentid
     * @return bool
     */
    public function user_has_submission($userid, $assignmentid) {
        return $this->db->record_exists_sql(
            "SELECT 1 
             FROM {assign_submission} 
             WHERE userid = :userid 
             AND assignment = :assignmentid 
             AND latest = 1 
             AND status = 'submitted'
             AND timemodified IS NOT NULL",
            ['userid' => $userid, 'assignmentid' => $assignmentid]
        );
    }
    
    /**
     * Get users with submissions for an assignment
     *
     * @param int $assignmentid
     * @return array
     */
    public function get_users_with_submissions($assignmentid) {
        $sql = "SELECT DISTINCT userid 
                FROM {assign_submission} 
                WHERE assignment = :assignmentid 
                AND latest = 1 
                AND status = 'submitted'
                AND timemodified IS NOT NULL";
        
        return $this->db->get_fieldset_sql($sql, ['assignmentid' => $assignmentid]);
    }
    
    /**
     * Check if advanced filter should be applied
     *
     * @return bool
     */
    public function should_apply_advanced_filter() {
        // Check configuration
        $mode = get_config('local_assign_no_submission_filter', 'mode');
        return ($mode === 'hide');
    }
    
    /**
     * Get filter statistics for an assignment
     *
     * @param int $assignmentid
     * @return object
     */
    public function get_filter_stats($assignmentid) {
        $stats = new \stdClass();
        
        // Total enrolled users
        $sql = "SELECT COUNT(DISTINCT u.id) 
                FROM {user} u
                JOIN {user_enrolments} ue ON u.id = ue.userid
                JOIN {enrol} e ON ue.enrolid = e.id
                JOIN {course_modules} cm ON e.courseid = cm.course
                JOIN {assign} a ON cm.instance = a.id
                WHERE a.id = :assignmentid
                AND cm.module = (SELECT id FROM {modules} WHERE name = 'assign')";
        
        $stats->total_users = $this->db->count_records_sql($sql, ['assignmentid' => $assignmentid]);
        
        // Users with submissions
        $stats->submitted_users = count($this->get_users_with_submissions($assignmentid));
        
        // Users without submissions
        $stats->no_submission_users = $stats->total_users - $stats->submitted_users;
        
        return $stats;
    }
}