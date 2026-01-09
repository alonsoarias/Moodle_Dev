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
 * Table override mechanism
 *
 * @package    local_assign_no_submission_filter
 * @copyright  2024 Your Organization
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_assign_no_submission_filter;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/mod/assign/gradingtable.php');
require_once($CFG->dirroot . '/local/assign_no_submission_filter/lib.php');

// Only override if not already done
if (!class_exists('\local_assign_no_submission_filter\filtered_grading_table', false)) {
    
    /**
     * Filtered grading table
     */
    class filtered_grading_table extends \assign_grading_table {
        
        /**
         * Override constructor to apply filter
         */
        public function __construct($assignment, $perpage, $filter, $rowoffset, $quickgrading, $downloadfilename = null) {
            parent::__construct($assignment, $perpage, $filter, $rowoffset, $quickgrading, $downloadfilename);
            
            // Apply filter only if user has selected role
            if ($this->should_filter()) {
                $this->apply_submission_filter();
            }
        }
        
        /**
         * Check if filter should be applied
         */
        private function should_filter() {
            // Don't filter downloads unless configured
            if ($this->is_downloading()) {
                if (!get_config('local_assign_no_submission_filter', 'filter_downloads')) {
                    return false;
                }
            }
            
            // Check if user has selected role
            $context = $this->assignment->get_context();
            return local_assign_no_submission_filter_should_filter($context);
        }
        
        /**
         * Apply the submission filter
         */
        private function apply_submission_filter() {
            global $DB;
            
            $assignid = $this->assignment->get_instance()->id;
            
            // Get users with submissions
            $userswithsubmissions = $this->get_users_with_submissions($assignid);
            
            if (empty($userswithsubmissions)) {
                // If no submissions, show empty table
                $userswithsubmissions = [-999999];
            }
            
            // Apply filter to SQL
            list($insql, $inparams) = $DB->get_in_or_equal($userswithsubmissions, SQL_PARAMS_NAMED, 'ufilter');
            
            if (!empty($this->sql->where)) {
                $this->sql->where .= " AND u.id $insql";
            } else {
                $this->sql->where = "u.id $insql";
            }
            
            $this->sql->params = array_merge($this->sql->params, $inparams);
        }
        
        /**
         * Get users who have submitted
         */
        private function get_users_with_submissions($assignid) {
            global $DB;
            
            $users = [];
            
            // Get users with submissions (not NEW status)
            $sql = "SELECT DISTINCT s.userid
                    FROM {assign_submission} s
                    WHERE s.assignment = :assignid
                      AND s.status <> :statusnew
                      AND s.latest = 1
                      AND s.userid > 0";
            
            $params = [
                'assignid' => $assignid,
                'statusnew' => ASSIGN_SUBMISSION_STATUS_NEW
            ];
            
            $submittedusers = $DB->get_fieldset_sql($sql, $params);
            $users = array_merge($users, $submittedusers);
            
            // Also include users with grades
            $sql = "SELECT DISTINCT g.userid
                    FROM {assign_grades} g
                    WHERE g.assignment = :assignid
                      AND g.grade IS NOT NULL
                      AND g.grade >= 0";
            
            $gradedusers = $DB->get_fieldset_sql($sql, ['assignid' => $assignid]);
            $users = array_merge($users, $gradedusers);
            
            // Handle team submissions
            if ($this->assignment->get_instance()->teamsubmission) {
                $sql = "SELECT DISTINCT gm.userid
                        FROM {assign_submission} s
                        JOIN {groups_members} gm ON gm.groupid = s.groupid
                        WHERE s.assignment = :assignid
                          AND s.status <> :statusnew
                          AND s.latest = 1
                          AND s.groupid > 0";
                
                $teamusers = $DB->get_fieldset_sql($sql, [
                    'assignid' => $assignid,
                    'statusnew' => ASSIGN_SUBMISSION_STATUS_NEW
                ]);
                
                $users = array_merge($users, $teamusers);
            }
            
            return array_unique($users);
        }
    }
    
    // Try to override the original class
    if (!class_exists('assign_grading_table_original', false)) {
        class_alias('assign_grading_table', 'assign_grading_table_original');
    }
    
    // Override using eval
    if (class_exists('assign_grading_table', false) && 
        !defined('ASSIGN_GRADING_TABLE_OVERRIDDEN')) {
        
        eval('
        class assign_grading_table_override extends \local_assign_no_submission_filter\filtered_grading_table {
            // This extends our filtered version
        }
        ');
        
        define('ASSIGN_GRADING_TABLE_OVERRIDDEN', true);
    }
}