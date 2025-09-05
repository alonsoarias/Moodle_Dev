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
 * Event observer for local_assign_no_submission_filter
 *
 * @package    local_assign_no_submission_filter
 * @copyright  2024 Your Organization
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_assign_no_submission_filter;

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/../lib.php');

/**
 * Event observer class
 */
class observer {
    
    /**
     * Handle grading table viewed event
     *
     * @param \mod_assign\event\grading_table_viewed $event
     */
    public static function grading_table_viewed(\mod_assign\event\grading_table_viewed $event) {
        global $SESSION, $USER;
        
        // Store in session that we're viewing grading table
        $SESSION->assign_grading_active = true;
        $SESSION->assign_grading_assignid = $event->objectid;
        
        // Log filter usage
        if (get_config('local_assign_no_submission_filter', 'enabled') &&
            local_assign_no_submission_filter_user_has_role(\context::instance_by_id($event->contextid))) {
            self::log_filter_usage($event);
        }

        // Apply filter preference
        self::apply_filter_preference($event->contextid);
    }
    
    /**
     * Handle submission graded event
     *
     * @param \mod_assign\event\submission_graded $event
     */
    public static function submission_graded(\mod_assign\event\submission_graded $event) {
        // Clear any cached filtering data when a submission is graded
        self::clear_filter_cache($event->objectid);
    }
    
    /**
     * Handle submission status updated
     *
     * @param \mod_assign\event\submission_status_updated $event
     */
    public static function submission_status_updated(\mod_assign\event\submission_status_updated $event) {
        // Clear cache when submission status changes
        self::clear_filter_cache($event->objectid);
    }
    
    /**
     * Apply filter preference for current user
     */
    protected static function apply_filter_preference(int $contextid) {
        global $USER, $SESSION;

        if (!get_config('local_assign_no_submission_filter', 'autoapply')) {
            return;
        }

        $context = \context::instance_by_id($contextid);
        if (!local_assign_no_submission_filter_user_has_role($context)) {
            return;
        }
        set_user_preference('assign_filter', ASSIGN_FILTER_NONE, $USER);
        $SESSION->assign_filter_applied = true;
    }
    
    /**
     * Clear filter cache for an assignment
     *
     * @param int $assignid
     */
    protected static function clear_filter_cache($assignid) {
        global $SESSION;
        
        if (isset($SESSION->assign_filter_cache)) {
            unset($SESSION->assign_filter_cache[$assignid]);
        }
    }
    
    /**
     * Log filter usage for analytics
     *
     * @param \core\event\base $event
     */
    protected static function log_filter_usage($event) {
        global $DB, $USER;
        
        // Optional: Log usage statistics
        $record = new \stdClass();
        $record->userid = $USER->id;
        $record->assignid = $event->objectid;
        $record->timecreated = time();
        $record->filterenabled = 1;
        
        // Only log if we have a logging table (optional)
        if ($DB->get_manager()->table_exists('local_assign_nsf_log')) {
            $DB->insert_record('local_assign_nsf_log', $record);
        }
    }
}