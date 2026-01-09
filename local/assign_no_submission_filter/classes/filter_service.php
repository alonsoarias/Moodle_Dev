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
 * Provides centralized filtering logic for assignment submissions.
 *
 * @package    local_assign_no_submission_filter
 * @copyright  2024 Your Organization
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_assign_no_submission_filter;

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/../lib.php');

/**
 * Service class for filtering logic
 *
 * This service provides methods to check submission status and filter
 * users based on their assignment activity.
 */
class filter_service {

    /** @var \moodle_database Database connection */
    protected $db;

    /** @var string Plugin component name */
    const COMPONENT = 'local_assign_no_submission_filter';

    /**
     * Constructor with dependency injection
     *
     * @param \moodle_database|null $db Optional database connection for testing
     */
    public function __construct(\moodle_database $db = null) {
        global $DB;
        $this->db = $db ?? $DB;
    }

    /**
     * Check if a specific user has made a submission for an assignment
     *
     * @param int $userid The user ID
     * @param int $assignmentid The assignment ID
     * @return bool True if user has a valid submission
     */
    public function user_has_submission(int $userid, int $assignmentid): bool {
        $sql = "SELECT 1
                FROM {assign_submission}
                WHERE userid = :userid
                  AND assignment = :assignmentid
                  AND latest = 1
                  AND status <> :status_new
                  AND userid > 0";

        return $this->db->record_exists_sql($sql, [
            'userid' => $userid,
            'assignmentid' => $assignmentid,
            'status_new' => ASSIGN_SUBMISSION_STATUS_NEW,
        ]);
    }

    /**
     * Get all users who have made submissions for an assignment
     *
     * @param int $assignmentid The assignment ID
     * @return array Array of user IDs with submissions
     */
    public function get_users_with_submissions(int $assignmentid): array {
        $sql = "SELECT DISTINCT s.userid
                FROM {assign_submission} s
                WHERE s.assignment = :assignmentid
                  AND s.latest = 1
                  AND s.status <> :status_new
                  AND s.userid > 0";

        return $this->db->get_fieldset_sql($sql, [
            'assignmentid' => $assignmentid,
            'status_new' => ASSIGN_SUBMISSION_STATUS_NEW,
        ]);
    }

    /**
     * Get all users who have grades for an assignment
     *
     * @param int $assignmentid The assignment ID
     * @return array Array of user IDs with grades
     */
    public function get_users_with_grades(int $assignmentid): array {
        $sql = "SELECT DISTINCT g.userid
                FROM {assign_grades} g
                WHERE g.assignment = :assignmentid
                  AND g.grade IS NOT NULL
                  AND g.grade >= 0";

        return $this->db->get_fieldset_sql($sql, [
            'assignmentid' => $assignmentid,
        ]);
    }

    /**
     * Get users who are members of groups with team submissions
     *
     * @param int $assignmentid The assignment ID
     * @return array Array of user IDs in teams with submissions
     */
    public function get_team_submission_users(int $assignmentid): array {
        $sql = "SELECT DISTINCT gm.userid
                FROM {assign_submission} s
                JOIN {groups_members} gm ON gm.groupid = s.groupid
                WHERE s.assignment = :assignmentid
                  AND s.status <> :status_new
                  AND s.latest = 1
                  AND s.groupid > 0";

        return $this->db->get_fieldset_sql($sql, [
            'assignmentid' => $assignmentid,
            'status_new' => ASSIGN_SUBMISSION_STATUS_NEW,
        ]);
    }

    /**
     * Get all users with any activity (submission or grade) for an assignment
     *
     * @param int $assignmentid The assignment ID
     * @param bool $includeteams Whether to include team submission members
     * @return array Array of unique user IDs with activity
     */
    public function get_users_with_activity(int $assignmentid, bool $includeteams = false): array {
        $users = [];

        // Users with submissions
        $users = array_merge($users, $this->get_users_with_submissions($assignmentid));

        // Users with grades
        $users = array_merge($users, $this->get_users_with_grades($assignmentid));

        // Team submission members
        if ($includeteams) {
            $users = array_merge($users, $this->get_team_submission_users($assignmentid));
        }

        return array_unique(array_filter($users));
    }

    /**
     * Check if the filter should be applied based on plugin configuration
     *
     * @param \context|null $context The context to check roles against
     * @return bool True if filter should be applied
     */
    public function should_apply_filter(\context $context = null): bool {
        // Check if plugin is enabled
        if (!get_config(self::COMPONENT, 'enabled')) {
            return false;
        }

        // Check if user has selected role
        if ($context !== null) {
            return local_assign_no_submission_filter_user_has_selected_role($context);
        }

        return true;
    }

    /**
     * Check if participant count should be hidden
     *
     * @param \context|null $context The context to check roles against
     * @return bool True if participant count should be hidden
     */
    public function should_hide_participant_count(\context $context = null): bool {
        if (!get_config(self::COMPONENT, 'hide_participant_count')) {
            return false;
        }

        if ($context !== null) {
            return local_assign_no_submission_filter_user_has_selected_role($context);
        }

        return true;
    }

    /**
     * Check if submitted count should be hidden
     *
     * @param \context|null $context The context to check roles against
     * @return bool True if submitted count should be hidden
     */
    public function should_hide_submitted_count(\context $context = null): bool {
        if (!get_config(self::COMPONENT, 'hide_submitted_count')) {
            return false;
        }

        if ($context !== null) {
            return local_assign_no_submission_filter_user_has_selected_role($context);
        }

        return true;
    }

    /**
     * Get filter statistics for an assignment
     *
     * @param int $assignmentid The assignment ID
     * @param int $courseid The course ID
     * @return \stdClass Object with total_users, submitted_users, no_submission_users
     */
    public function get_filter_stats(int $assignmentid, int $courseid = 0): \stdClass {
        $stats = new \stdClass();
        $stats->total_users = 0;
        $stats->submitted_users = 0;
        $stats->no_submission_users = 0;

        // Get course ID from assignment if not provided
        if ($courseid === 0) {
            $assign = $this->db->get_record('assign', ['id' => $assignmentid], 'course');
            if ($assign) {
                $courseid = $assign->course;
            }
        }

        if ($courseid > 0) {
            // Count enrolled users with student role
            $context = \context_course::instance($courseid);
            $students = get_enrolled_users($context, 'mod/assign:submit');
            $stats->total_users = count($students);
        }

        // Users with submissions
        $stats->submitted_users = count($this->get_users_with_submissions($assignmentid));

        // Users without submissions
        $stats->no_submission_users = max(0, $stats->total_users - $stats->submitted_users);

        return $stats;
    }

    /**
     * Get all plugin configuration values
     *
     * @return \stdClass Object with all configuration values
     */
    public function get_config(): \stdClass {
        $config = new \stdClass();
        $config->enabled = (bool)get_config(self::COMPONENT, 'enabled');
        $config->filter_downloads = (bool)get_config(self::COMPONENT, 'filter_downloads');
        $config->autoapply = (bool)get_config(self::COMPONENT, 'autoapply');
        $config->hide_participant_count = (bool)get_config(self::COMPONENT, 'hide_participant_count');
        $config->hide_submitted_count = (bool)get_config(self::COMPONENT, 'hide_submitted_count');
        $config->selected_roles = get_config(self::COMPONENT, 'selected_roles');

        return $config;
    }
}
