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
 * Handles events related to assignment grading and submissions
 * to manage filter state and cache.
 *
 * @package    local_assign_no_submission_filter
 * @copyright  2024 Your Organization
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_assign_no_submission_filter;

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/../lib.php');

/**
 * Event observer class for assignment events
 */
class observer {

    /**
     * Handle grading table viewed event
     *
     * Stores session information and applies filter preferences when
     * a user views the grading table.
     *
     * @param \mod_assign\event\grading_table_viewed $event The event object
     */
    public static function grading_table_viewed(\mod_assign\event\grading_table_viewed $event): void {
        global $SESSION;

        // Check if plugin is enabled first
        if (!get_config('local_assign_no_submission_filter', 'enabled')) {
            return;
        }

        // Store session state for grading table view
        $SESSION->assign_grading_active = true;
        $SESSION->assign_grading_assignid = $event->objectid;

        // Get context for role checking
        try {
            $context = \context::instance_by_id($event->contextid);
        } catch (\Exception $e) {
            return;
        }

        // Check if user has selected role before proceeding
        if (!local_assign_no_submission_filter_user_has_selected_role($context)) {
            return;
        }

        // Apply auto-filter preference if enabled
        if (get_config('local_assign_no_submission_filter', 'autoapply')) {
            self::apply_auto_filter_preference();
        }
    }

    /**
     * Handle submission graded event
     *
     * Clears cached filter data when a submission is graded.
     *
     * @param \mod_assign\event\submission_graded $event The event object
     */
    public static function submission_graded(\mod_assign\event\submission_graded $event): void {
        self::clear_filter_cache($event->objectid);
    }

    /**
     * Handle submission status updated event
     *
     * Clears cached filter data when submission status changes.
     *
     * @param \mod_assign\event\submission_status_updated $event The event object
     */
    public static function submission_status_updated(\mod_assign\event\submission_status_updated $event): void {
        self::clear_filter_cache($event->objectid);
    }

    /**
     * Apply automatic filter preference for current user
     *
     * Sets the user preference to show only submissions that need grading.
     */
    protected static function apply_auto_filter_preference(): void {
        global $USER, $SESSION;

        // Use the Moodle constant if available, otherwise use the numeric value
        // ASSIGN_FILTER_SUBMITTED = 1 in mod/assign/locallib.php
        $filtervalue = defined('ASSIGN_FILTER_SUBMITTED') ? ASSIGN_FILTER_SUBMITTED : 1;

        set_user_preference('assign_filter', $filtervalue, $USER);
        $SESSION->assign_filter_applied = true;
    }

    /**
     * Clear filter cache for an assignment
     *
     * Removes cached filtering data from the session when submission
     * data changes to ensure fresh data on next view.
     *
     * @param int $assignid The assignment ID
     */
    protected static function clear_filter_cache(int $assignid): void {
        global $SESSION;

        if (isset($SESSION->assign_filter_cache[$assignid])) {
            unset($SESSION->assign_filter_cache[$assignid]);
        }
    }
}
