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
 * Filtered grading table class for assignment submissions
 *
 * This class extends the standard assign_grading_table to filter out
 * students who have not made any submissions.
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

/**
 * Filtered grading table that only shows students with submissions
 */
class filtered_grading_table extends \assign_grading_table {

    /** @var filter_service The filter service instance */
    protected $filterservice;

    /**
     * Constructor - sets up the table with submission filtering
     *
     * @param \assign $assignment The assignment instance
     * @param int $perpage Number of rows per page
     * @param int $filter Current filter setting
     * @param int $rowoffset Row offset for pagination
     * @param bool $quickgrading Whether quick grading is enabled
     * @param string|null $downloadfilename Filename for download, null if not downloading
     */
    public function __construct($assignment, $perpage, $filter, $rowoffset, $quickgrading, $downloadfilename = null) {
        parent::__construct($assignment, $perpage, $filter, $rowoffset, $quickgrading, $downloadfilename);

        $this->filterservice = new filter_service();

        // Apply filter only if conditions are met
        if ($this->should_apply_filter()) {
            $this->apply_submission_filter();
        }
    }

    /**
     * Determine if the submission filter should be applied
     *
     * @return bool True if filter should be applied
     */
    protected function should_apply_filter(): bool {
        // Check if plugin is enabled
        if (!get_config('local_assign_no_submission_filter', 'enabled')) {
            return false;
        }

        // Don't filter downloads unless configured to do so
        if ($this->is_downloading()) {
            if (!get_config('local_assign_no_submission_filter', 'filter_downloads')) {
                return false;
            }
        }

        // Check if user has one of the selected roles
        $context = $this->assignment->get_context();
        return local_assign_no_submission_filter_user_has_selected_role($context);
    }

    /**
     * Apply the submission filter to the SQL query
     */
    protected function apply_submission_filter(): void {
        global $DB;

        $assignid = $this->assignment->get_instance()->id;

        // Get all users who have submissions or grades
        $userswithsubmissions = $this->get_users_with_activity($assignid);

        if (empty($userswithsubmissions)) {
            // No submissions - use impossible ID to show empty table
            $userswithsubmissions = [-1];
        }

        // Build SQL IN clause
        list($insql, $inparams) = $DB->get_in_or_equal(
            $userswithsubmissions,
            SQL_PARAMS_NAMED,
            'filteruser'
        );

        // Append to existing WHERE clause
        if (!empty($this->sql->where)) {
            $this->sql->where .= " AND u.id {$insql}";
        } else {
            $this->sql->where = "u.id {$insql}";
        }

        // Merge parameters
        if (!isset($this->sql->params)) {
            $this->sql->params = [];
        }
        $this->sql->params = array_merge($this->sql->params, $inparams);
    }

    /**
     * Get users who have any activity (submissions or grades) for the assignment
     *
     * @param int $assignid The assignment ID
     * @return array Array of user IDs
     */
    protected function get_users_with_activity(int $assignid): array {
        global $DB;

        $users = [];

        // 1. Users with actual submissions (not 'new' status)
        $sql = "SELECT DISTINCT s.userid
                FROM {assign_submission} s
                WHERE s.assignment = :assignid
                  AND s.status <> :statusnew
                  AND s.latest = 1
                  AND s.userid > 0";

        $params = [
            'assignid' => $assignid,
            'statusnew' => ASSIGN_SUBMISSION_STATUS_NEW,
        ];

        $submittedusers = $DB->get_fieldset_sql($sql, $params);
        $users = array_merge($users, $submittedusers);

        // 2. Users with grades (even without submission)
        $sql = "SELECT DISTINCT g.userid
                FROM {assign_grades} g
                WHERE g.assignment = :assignid
                  AND g.grade IS NOT NULL
                  AND g.grade >= 0";

        $gradedusers = $DB->get_fieldset_sql($sql, ['assignid' => $assignid]);
        $users = array_merge($users, $gradedusers);

        // 3. Team submission members
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
                'statusnew' => ASSIGN_SUBMISSION_STATUS_NEW,
            ]);

            $users = array_merge($users, $teamusers);
        }

        return array_unique(array_filter($users));
    }
}
