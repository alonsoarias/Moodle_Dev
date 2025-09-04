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
 * Privacy Subsystem implementation for enrol_nexuspay.
 *
 * @package    enrol_nexuspay
 * @category   privacy
 * @copyright  2025 Alonso Arias <soporte@nexuslabs.com.co>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace enrol_nexuspay\privacy;

use core_privacy\local\metadata\collection;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\approved_userlist;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\userlist;
use core_privacy\local\request\writer;
use core_payment\helper as payment_helper;

defined('MOODLE_INTERNAL') || die();

/**
 * Privacy Subsystem for enrol_nexuspay implementing required providers.
 *
 * @copyright  2025 Alonso Arias <soporte@nexuslabs.com.co>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class provider implements 
    \core_privacy\local\metadata\provider,
    \core_privacy\local\request\plugin\provider,
    \core_privacy\local\request\core_userlist_provider,
    \core_payment\privacy\consumer_provider {

    /**
     * Returns meta data about this system.
     *
     * @param collection $collection The initialised collection to add items to.
     * @return collection A listing of user data stored through this system.
     */
    public static function get_metadata(collection $collection): collection {
        // Add database table metadata.
        $collection->add_database_table(
            'enrol_nexuspay',
            [
                'paymentid' => 'privacy:metadata:enrol_nexuspay:paymentid',
                'courseid' => 'privacy:metadata:enrol_nexuspay:courseid',
                'userid' => 'privacy:metadata:enrol_nexuspay:userid',
                'timecreated' => 'privacy:metadata:enrol_nexuspay:timecreated',
            ],
            'privacy:metadata:enrol_nexuspay'
        );

        $collection->add_database_table(
            'enrol_nexuspay_groups',
            [
                'courseid' => 'privacy:metadata:enrol_nexuspay_groups:courseid',
                'instanceid' => 'privacy:metadata:enrol_nexuspay_groups:instanceid',
                'userid' => 'privacy:metadata:enrol_nexuspay_groups:userid',
                'groupid' => 'privacy:metadata:enrol_nexuspay_groups:groupid',
                'timecreated' => 'privacy:metadata:enrol_nexuspay_groups:timecreated',
            ],
            'privacy:metadata:enrol_nexuspay_groups'
        );

        // Link to core payment subsystem.
        $collection->link_subsystem('core_payment', 'privacy:metadata:core_payment');

        return $collection;
    }

    /**
     * Get the list of contexts that contain user information for the specified user.
     *
     * @param int $userid The user to search.
     * @return contextlist The contextlist containing the list of contexts used in this plugin.
     */
    public static function get_contexts_for_user(int $userid): contextlist {
        $contextlist = new contextlist();

        // Get contexts where user has NexusPay payment records.
        $sql = "SELECT DISTINCT ctx.id
                FROM {context} ctx
                JOIN {course} c ON ctx.instanceid = c.id AND ctx.contextlevel = :contextcourse
                JOIN {enrol_nexuspay} np ON np.courseid = c.id
                WHERE np.userid = :userid";

        $params = [
            'contextcourse' => CONTEXT_COURSE,
            'userid' => $userid,
        ];

        $contextlist->add_from_sql($sql, $params);

        // Also check for group assignments.
        $sql = "SELECT DISTINCT ctx.id
                FROM {context} ctx
                JOIN {course} c ON ctx.instanceid = c.id AND ctx.contextlevel = :contextcourse
                JOIN {enrol_nexuspay_groups} npg ON npg.courseid = c.id
                WHERE npg.userid = :userid";

        $contextlist->add_from_sql($sql, $params);

        // Get payment-related contexts.
        $sql = "SELECT DISTINCT ctx.id
                FROM {context} ctx
                JOIN {course} c ON ctx.instanceid = c.id AND ctx.contextlevel = :contextcourse
                JOIN {enrol} e ON e.courseid = c.id AND e.enrol = 'nexuspay'
                JOIN {payments} p ON p.component = 'enrol_nexuspay' AND p.itemid = e.id
                WHERE p.userid = :userid";

        $contextlist->add_from_sql($sql, $params);

        return $contextlist;
    }

    /**
     * Get the list of users who have data within a context.
     *
     * @param userlist $userlist The userlist containing the list of users who have data in this context/plugin combination.
     */
    public static function get_users_in_context(userlist $userlist) {
        $context = $userlist->get_context();

        if (!$context instanceof \context_course) {
            return;
        }

        $params = ['courseid' => $context->instanceid];

        // Get users with NexusPay payment records.
        $sql = "SELECT DISTINCT userid
                FROM {enrol_nexuspay}
                WHERE courseid = :courseid";
        $userlist->add_from_sql('userid', $sql, $params);

        // Get users with group assignments.
        $sql = "SELECT DISTINCT userid
                FROM {enrol_nexuspay_groups}
                WHERE courseid = :courseid";
        $userlist->add_from_sql('userid', $sql, $params);

        // Get users with payments.
        $sql = "SELECT DISTINCT p.userid
                FROM {payments} p
                JOIN {enrol} e ON p.component = 'enrol_nexuspay' AND p.itemid = e.id
                WHERE e.courseid = :courseid";
        $userlist->add_from_sql('userid', $sql, $params);
    }

    /**
     * Export all user data for the specified user, in the specified contexts.
     *
     * @param approved_contextlist $contextlist The approved contexts to export information for.
     */
    public static function export_user_data(approved_contextlist $contextlist) {
        global $DB;

        if (empty($contextlist->count())) {
            return;
        }

        $user = $contextlist->get_user();
        $userid = $user->id;

        list($contextsql, $contextparams) = $DB->get_in_or_equal($contextlist->get_contextids(), SQL_PARAMS_NAMED);

        // Export NexusPay payment records.
        $sql = "SELECT np.*, c.fullname as coursename, ctx.id as contextid
                FROM {enrol_nexuspay} np
                JOIN {course} c ON np.courseid = c.id
                JOIN {context} ctx ON ctx.instanceid = c.id AND ctx.contextlevel = :contextcourse
                WHERE np.userid = :userid AND ctx.id {$contextsql}
                ORDER BY np.timecreated";

        $params = ['userid' => $userid, 'contextcourse' => CONTEXT_COURSE] + $contextparams;
        $payments = $DB->get_records_sql($sql, $params);

        foreach ($payments as $payment) {
            $context = \context::instance_by_id($payment->contextid);
            $subcontext = [
                get_string('pluginname', 'enrol_nexuspay'),
                'payments'
            ];

            $data = (object) [
                'coursename' => format_string($payment->coursename),
                'paymentid' => $payment->paymentid,
                'timecreated' => transform::datetime($payment->timecreated),
            ];

            writer::with_context($context)
                ->export_data($subcontext, $data);
        }

        // Export group assignments.
        $sql = "SELECT npg.*, c.fullname as coursename, g.name as groupname, ctx.id as contextid
                FROM {enrol_nexuspay_groups} npg
                JOIN {course} c ON npg.courseid = c.id
                JOIN {groups} g ON npg.groupid = g.id
                JOIN {context} ctx ON ctx.instanceid = c.id AND ctx.contextlevel = :contextcourse
                WHERE npg.userid = :userid AND ctx.id {$contextsql}
                ORDER BY npg.timecreated";

        $groupassignments = $DB->get_records_sql($sql, $params);

        foreach ($groupassignments as $assignment) {
            $context = \context::instance_by_id($assignment->contextid);
            $subcontext = [
                get_string('pluginname', 'enrol_nexuspay'),
                'group_assignments'
            ];

            $data = (object) [
                'coursename' => format_string($assignment->coursename),
                'groupname' => format_string($assignment->groupname),
                'timecreated' => transform::datetime($assignment->timecreated),
            ];

            writer::with_context($context)
                ->export_data($subcontext, $data);
        }

        // Export payment data via payment subsystem.
        foreach ($contextlist as $context) {
            if ($context instanceof \context_course) {
                $instances = $DB->get_records('enrol', [
                    'courseid' => $context->instanceid,
                    'enrol' => 'nexuspay'
                ]);

                foreach ($instances as $instance) {
                    \core_payment\privacy\provider::export_payment_data_for_user_in_context(
                        $context,
                        [get_string('pluginname', 'enrol_nexuspay')],
                        $userid,
                        'enrol_nexuspay',
                        'fee',
                        $instance->id
                    );
                }
            }
        }
    }

    /**
     * Delete all data for all users in the specified context.
     *
     * @param \context $context The specific context to delete data for.
     */
    public static function delete_data_for_all_users_in_context(\context $context) {
        global $DB;

        if (!$context instanceof \context_course) {
            return;
        }

        // Delete NexusPay payment records.
        $DB->delete_records('enrol_nexuspay', ['courseid' => $context->instanceid]);

        // Delete group assignments.
        $DB->delete_records('enrol_nexuspay_groups', ['courseid' => $context->instanceid]);

        // Delete payment data via payment subsystem.
        $instances = $DB->get_records('enrol', [
            'courseid' => $context->instanceid,
            'enrol' => 'nexuspay'
        ]);

        foreach ($instances as $instance) {
            $sql = "SELECT p.id
                    FROM {payments} p
                    WHERE p.component = :component AND p.itemid = :itemid";
            $params = [
                'component' => 'enrol_nexuspay',
                'itemid' => $instance->id
            ];

            \core_payment\privacy\provider::delete_data_for_payment_sql($sql, $params);
        }
    }

    /**
     * Delete all user data for the specified user, in the specified contexts.
     *
     * @param approved_contextlist $contextlist The approved contexts and user information to delete information for.
     */
    public static function delete_data_for_user(approved_contextlist $contextlist) {
        global $DB;

        if (empty($contextlist->count())) {
            return;
        }

        $userid = $contextlist->get_user()->id;

        foreach ($contextlist as $context) {
            if (!$context instanceof \context_course) {
                continue;
            }

            // Delete NexusPay payment records.
            $DB->delete_records('enrol_nexuspay', [
                'courseid' => $context->instanceid,
                'userid' => $userid
            ]);

            // Delete group assignments.
            $DB->delete_records('enrol_nexuspay_groups', [
                'courseid' => $context->instanceid,
                'userid' => $userid
            ]);

            // Delete payment data via payment subsystem.
            $instances = $DB->get_records('enrol', [
                'courseid' => $context->instanceid,
                'enrol' => 'nexuspay'
            ]);

            foreach ($instances as $instance) {
                $sql = "SELECT p.id
                        FROM {payments} p
                        WHERE p.component = :component 
                          AND p.itemid = :itemid 
                          AND p.userid = :userid";
                $params = [
                    'component' => 'enrol_nexuspay',
                    'itemid' => $instance->id,
                    'userid' => $userid
                ];

                \core_payment\privacy\provider::delete_data_for_payment_sql($sql, $params);
            }
        }
    }

    /**
     * Delete multiple users within a single context.
     *
     * @param approved_userlist $userlist The approved context and user information to delete information for.
     */
    public static function delete_data_for_users(approved_userlist $userlist) {
        global $DB;

        $context = $userlist->get_context();

        if (!$context instanceof \context_course) {
            return;
        }

        $userids = $userlist->get_userids();

        if (empty($userids)) {
            return;
        }

        list($usersql, $userparams) = $DB->get_in_or_equal($userids, SQL_PARAMS_NAMED);

        // Delete NexusPay payment records.
        $params = ['courseid' => $context->instanceid] + $userparams;
        $DB->delete_records_select('enrol_nexuspay', 
            "courseid = :courseid AND userid {$usersql}", $params);

        // Delete group assignments.
        $DB->delete_records_select('enrol_nexuspay_groups',
            "courseid = :courseid AND userid {$usersql}", $params);

        // Delete payment data via payment subsystem.
        $instances = $DB->get_records('enrol', [
            'courseid' => $context->instanceid,
            'enrol' => 'nexuspay'
        ]);

        foreach ($instances as $instance) {
            foreach ($userids as $userid) {
                $sql = "SELECT p.id
                        FROM {payments} p
                        WHERE p.component = :component 
                          AND p.itemid = :itemid 
                          AND p.userid = :userid";
                $params = [
                    'component' => 'enrol_nexuspay',
                    'itemid' => $instance->id,
                    'userid' => $userid
                ];

                \core_payment\privacy\provider::delete_data_for_payment_sql($sql, $params);
            }
        }
    }

    /**
     * Get the context for a payment area and item ID.
     *
     * @param string $paymentarea Payment area
     * @param int $itemid Item ID
     * @return int|null Context ID or null
     */
    public static function get_contextid_for_payment(string $paymentarea, int $itemid): ?int {
        global $DB;

        $sql = "SELECT ctx.id
                FROM {enrol} e
                JOIN {context} ctx ON e.courseid = ctx.instanceid AND ctx.contextlevel = :contextcourse
                WHERE e.id = :enrolid AND e.enrol = :enrolname";

        $params = [
            'contextcourse' => CONTEXT_COURSE,
            'enrolid' => $itemid,
            'enrolname' => 'nexuspay',
        ];

        $contextid = $DB->get_field_sql($sql, $params);

        return $contextid ?: null;
    }
}