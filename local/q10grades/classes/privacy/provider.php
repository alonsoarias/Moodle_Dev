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
 * Privacy provider for Q10 Grades Sync plugin.
 *
 * @package    local_q10grades
 * @copyright  2025 Your Institution
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_q10grades\privacy;

defined('MOODLE_INTERNAL') || die();

use core_privacy\local\metadata\collection;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\approved_userlist;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\userlist;
use core_privacy\local\request\writer;

/**
 * Privacy provider class implementing the privacy API.
 */
class provider implements
    \core_privacy\local\metadata\provider,
    \core_privacy\local\request\plugin\provider,
    \core_privacy\local\request\core_userlist_provider {

    /**
     * Get metadata about the data stored.
     *
     * @param collection $collection The collection to add to.
     * @return collection The updated collection.
     */
    public static function get_metadata(collection $collection): collection {
        // External system - Q10.
        $collection->add_external_location_link('q10', [
            'student_id' => 'privacy:metadata:q10:student_id',
            'grade' => 'privacy:metadata:q10:grade',
        ], 'privacy:metadata:q10');

        // User mapping table.
        $collection->add_database_table('local_q10grades_usermapping', [
            'userid' => 'privacy:metadata:usermapping:userid',
            'q10_student_id' => 'privacy:metadata:usermapping:q10_student_id',
            'q10_student_code' => 'privacy:metadata:usermapping:q10_student_code',
        ], 'privacy:metadata:usermapping');

        // Sync log table.
        $collection->add_database_table('local_q10grades_sync_log', [
            'userid' => 'privacy:metadata:synclog:userid',
            'grade_sent' => 'privacy:metadata:synclog:grade_sent',
            'status' => 'privacy:metadata:synclog:status',
            'timecreated' => 'privacy:metadata:synclog:timecreated',
        ], 'privacy:metadata:synclog');

        return $collection;
    }

    /**
     * Get the list of contexts that contain user information.
     *
     * @param int $userid The user ID.
     * @return contextlist The list of contexts.
     */
    public static function get_contexts_for_userid(int $userid): contextlist {
        $contextlist = new contextlist();

        // Get contexts from sync logs.
        $sql = "SELECT DISTINCT ctx.id
                FROM {context} ctx
                JOIN {course} c ON c.id = ctx.instanceid AND ctx.contextlevel = :contextlevel
                JOIN {local_q10grades_sync_log} sl ON sl.courseid = c.id
                WHERE sl.userid = :userid OR sl.synced_by = :syncedby";

        $contextlist->add_from_sql($sql, [
            'contextlevel' => CONTEXT_COURSE,
            'userid' => $userid,
            'syncedby' => $userid,
        ]);

        // Get contexts from user mapping.
        $sql = "SELECT ctx.id
                FROM {context} ctx
                WHERE ctx.contextlevel = :contextlevel AND ctx.instanceid = :userid";

        $contextlist->add_from_sql($sql, [
            'contextlevel' => CONTEXT_USER,
            'userid' => $userid,
        ]);

        return $contextlist;
    }

    /**
     * Get users in context.
     *
     * @param userlist $userlist The userlist to populate.
     */
    public static function get_users_in_context(userlist $userlist): void {
        $context = $userlist->get_context();

        if ($context->contextlevel === CONTEXT_COURSE) {
            $sql = "SELECT DISTINCT sl.userid
                    FROM {local_q10grades_sync_log} sl
                    WHERE sl.courseid = :courseid AND sl.userid IS NOT NULL";

            $userlist->add_from_sql('userid', $sql, ['courseid' => $context->instanceid]);

            $sql = "SELECT DISTINCT sl.synced_by
                    FROM {local_q10grades_sync_log} sl
                    WHERE sl.courseid = :courseid AND sl.synced_by IS NOT NULL";

            $userlist->add_from_sql('synced_by', $sql, ['courseid' => $context->instanceid]);
        }

        if ($context->contextlevel === CONTEXT_USER) {
            $sql = "SELECT userid FROM {local_q10grades_usermapping} WHERE userid = :userid";
            $userlist->add_from_sql('userid', $sql, ['userid' => $context->instanceid]);
        }
    }

    /**
     * Export user data.
     *
     * @param approved_contextlist $contextlist The approved context list.
     */
    public static function export_user_data(approved_contextlist $contextlist): void {
        global $DB;

        $userid = $contextlist->get_user()->id;

        foreach ($contextlist->get_contexts() as $context) {
            if ($context->contextlevel === CONTEXT_USER && $context->instanceid === $userid) {
                // Export user mapping.
                $mapping = $DB->get_record('local_q10grades_usermapping', ['userid' => $userid]);
                if ($mapping) {
                    writer::with_context($context)->export_data(
                        ['Q10 Grades Sync', 'User Mapping'],
                        (object) [
                            'q10_student_id' => $mapping->q10_student_id,
                            'q10_student_code' => $mapping->q10_student_code,
                            'timecreated' => userdate($mapping->timecreated),
                        ]
                    );
                }
            }

            if ($context->contextlevel === CONTEXT_COURSE) {
                // Export sync logs.
                $logs = $DB->get_records_select(
                    'local_q10grades_sync_log',
                    'courseid = :courseid AND (userid = :userid OR synced_by = :syncedby)',
                    ['courseid' => $context->instanceid, 'userid' => $userid, 'syncedby' => $userid]
                );

                if ($logs) {
                    $exportdata = [];
                    foreach ($logs as $log) {
                        $exportdata[] = (object) [
                            'grade_sent' => $log->grade_sent,
                            'status' => $log->status,
                            'sync_type' => $log->sync_type,
                            'timecreated' => userdate($log->timecreated),
                        ];
                    }

                    writer::with_context($context)->export_data(
                        ['Q10 Grades Sync', 'Sync History'],
                        (object) ['logs' => $exportdata]
                    );
                }
            }
        }
    }

    /**
     * Delete all data for all users in context.
     *
     * @param \context $context The context.
     */
    public static function delete_data_for_all_users_in_context(\context $context): void {
        global $DB;

        if ($context->contextlevel === CONTEXT_COURSE) {
            $DB->delete_records('local_q10grades_sync_log', ['courseid' => $context->instanceid]);
        }
    }

    /**
     * Delete data for user.
     *
     * @param approved_contextlist $contextlist The approved context list.
     */
    public static function delete_data_for_user(approved_contextlist $contextlist): void {
        global $DB;

        $userid = $contextlist->get_user()->id;

        foreach ($contextlist->get_contexts() as $context) {
            if ($context->contextlevel === CONTEXT_USER && $context->instanceid === $userid) {
                $DB->delete_records('local_q10grades_usermapping', ['userid' => $userid]);
            }

            if ($context->contextlevel === CONTEXT_COURSE) {
                $DB->delete_records('local_q10grades_sync_log', [
                    'courseid' => $context->instanceid,
                    'userid' => $userid,
                ]);
            }
        }
    }

    /**
     * Delete data for users.
     *
     * @param approved_userlist $userlist The approved user list.
     */
    public static function delete_data_for_users(approved_userlist $userlist): void {
        global $DB;

        $context = $userlist->get_context();
        $userids = $userlist->get_userids();

        if (empty($userids)) {
            return;
        }

        list($usersql, $userparams) = $DB->get_in_or_equal($userids, SQL_PARAMS_NAMED);

        if ($context->contextlevel === CONTEXT_USER) {
            $DB->delete_records_select('local_q10grades_usermapping', "userid {$usersql}", $userparams);
        }

        if ($context->contextlevel === CONTEXT_COURSE) {
            $userparams['courseid'] = $context->instanceid;
            $DB->delete_records_select(
                'local_q10grades_sync_log',
                "courseid = :courseid AND userid {$usersql}",
                $userparams
            );
        }
    }
}
