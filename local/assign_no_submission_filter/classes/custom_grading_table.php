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
 * Custom grading table that filters out students without submissions
 *
 * @package    local_assign_no_submission_filter
 * @copyright  2024 Your Organization
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_assign_no_submission_filter;

defined('MOODLE_INTERNAL') || die();

// The original class is already loaded, we just need to extend it
if (class_exists('\assign_grading_table')) {
    
    /**
     * Extended grading table with submission filtering
     */
    class custom_grading_table extends \assign_grading_table {
        
        /**
         * Constructor - applies filtering
         */
        public function __construct($assignment, $perpage, $filter, $rowoffset, $quickgrading, $downloadfilename = null) {
            // Force filter to 'submitted' if auto-apply is enabled
            if (get_config('local_assign_no_submission_filter', 'autoapply')) {
                $filter = ASSIGN_FILTER_SUBMITTED;
            }
            
            parent::__construct($assignment, $perpage, $filter, $rowoffset, $quickgrading, $downloadfilename);
        }
        
        /**
         * Setup the table - add our filtering
         */
        public function setup() {
            parent::setup();
            
            // Only apply if enabled
            if (!get_config('local_assign_no_submission_filter', 'enabled')) {
                return;
            }
            
            // Modify the WHERE clause to exclude students without submissions
            $this->add_submission_filter();
        }
        
        /**
         * Add submission filter to SQL
         */
        protected function add_submission_filter() {
            global $DB;
            
            $assignid = $this->assignment->get_instance()->id;
            
            // Add condition to only show users with submissions
            $submissionexists = "EXISTS (
                SELECT 1 
                FROM {assign_submission} s 
                WHERE s.userid = u.id 
                AND s.assignment = :assignid_filter_nsf 
                AND s.latest = 1 
                AND s.status = 'submitted'
            )";
            
            // Add to existing WHERE clause
            if (empty($this->sql->where)) {
                $this->sql->where = $submissionexists;
            } else {
                $this->sql->where .= " AND " . $submissionexists;
            }
            
            // Add parameter
            if (!isset($this->sql->params)) {
                $this->sql->params = [];
            }
            $this->sql->params['assignid_filter_nsf'] = $assignid;
        }
    }
    
} else {
    // Fallback if the parent class is not available
    class custom_grading_table {
        public function __construct() {
            debugging('Parent class assign_grading_table not available', DEBUG_DEVELOPER);
        }
    }
}