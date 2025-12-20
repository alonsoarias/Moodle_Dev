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
 * Privacy provider for paygw_epayco.
 *
 * @package    paygw_epayco
 * @copyright  2025 ingeweb.co <soporte@ingeweb.co>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace paygw_epayco\privacy;

use core_privacy\local\metadata\collection;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\approved_userlist;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\userlist;
use core_privacy\local\request\writer;

/**
 * Privacy provider for paygw_epayco.
 *
 * @copyright  2025 ingeweb.co <soporte@ingeweb.co>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class provider implements
    \core_privacy\local\metadata\provider,
    \core_privacy\local\request\plugin\provider,
    \core_privacy\local\request\core_userlist_provider {

    /**
     * Returns metadata about this system.
     *
     * @param collection $collection The collection to add metadata to.
     * @return collection A collection of metadata items.
     */
    public static function get_metadata(collection $collection): collection {
        $collection->add_database_table(
            'paygw_epayco_transactions',
            [
                'userid' => 'privacy:metadata:paygw_epayco_transactions:userid',
                'component' => 'privacy:metadata:paygw_epayco_transactions:component',
                'paymentarea' => 'privacy:metadata:paygw_epayco_transactions:paymentarea',
                'itemid' => 'privacy:metadata:paygw_epayco_transactions:itemid',
                'reference' => 'privacy:metadata:paygw_epayco_transactions:reference',
                'ref_payco' => 'privacy:metadata:paygw_epayco_transactions:ref_payco',
                'amount' => 'privacy:metadata:paygw_epayco_transactions:amount',
                'currency' => 'privacy:metadata:paygw_epayco_transactions:currency',
                'status' => 'privacy:metadata:paygw_epayco_transactions:status',
                'timecreated' => 'privacy:metadata:paygw_epayco_transactions:timecreated',
            ],
            'privacy:metadata:paygw_epayco_transactions'
        );

        $collection->add_external_location_link(
            'epayco',
            [
                'email' => 'privacy:metadata:epayco:email',
                'firstname' => 'privacy:metadata:epayco:firstname',
                'lastname' => 'privacy:metadata:epayco:lastname',
                'phone' => 'privacy:metadata:epayco:phone',
                'address' => 'privacy:metadata:epayco:address',
                'city' => 'privacy:metadata:epayco:city',
            ],
            'privacy:metadata:epayco'
        );

        return $collection;
    }

    /**
     * Get the list of contexts that contain user information for the specified user.
     *
     * @param int $userid The user to search.
     * @return contextlist The contextlist containing the list of contexts used in this plugin.
     */
    public static function get_contexts_for_userid(int $userid): contextlist {
        $contextlist = new contextlist();

        $sql = "SELECT c.id
                  FROM {context} c
                  JOIN {paygw_epayco_transactions} t ON c.instanceid = t.userid AND c.contextlevel = :contextlevel
                 WHERE t.userid = :userid";

        $params = [
            'contextlevel' => CONTEXT_USER,
            'userid' => $userid,
        ];

        $contextlist->add_from_sql($sql, $params);

        return $contextlist;
    }

    /**
     * Get the list of users who have data within a context.
     *
     * @param userlist $userlist The userlist containing the list of users who have data in this context/plugin combination.
     */
    public static function get_users_in_context(userlist $userlist): void {
        $context = $userlist->get_context();

        if (!$context instanceof \context_user) {
            return;
        }

        $sql = "SELECT userid
                  FROM {paygw_epayco_transactions}
                 WHERE userid = :userid";

        $params = ['userid' => $context->instanceid];

        $userlist->add_from_sql('userid', $sql, $params);
    }

    /**
     * Export all user data for the specified user, in the specified contexts.
     *
     * @param approved_contextlist $contextlist The approved contexts to export information for.
     */
    public static function export_user_data(approved_contextlist $contextlist): void {
        global $DB;

        $userid = $contextlist->get_user()->id;

        $transactions = $DB->get_records('paygw_epayco_transactions', ['userid' => $userid]);

        foreach ($transactions as $transaction) {
            $context = \context_user::instance($userid);

            $data = (object) [
                'reference' => $transaction->reference,
                'ref_payco' => $transaction->ref_payco,
                'amount' => $transaction->amount,
                'currency' => $transaction->currency,
                'status' => $transaction->status,
                'component' => $transaction->component,
                'paymentarea' => $transaction->paymentarea,
                'itemid' => $transaction->itemid,
                'timecreated' => transform::datetime($transaction->timecreated),
            ];

            writer::with_context($context)->export_data(
                [get_string('pluginname', 'paygw_epayco')],
                $data
            );
        }
    }

    /**
     * Delete all data for all users in the specified context.
     *
     * @param \context $context The specific context to delete data for.
     */
    public static function delete_data_for_all_users_in_context(\context $context): void {
        global $DB;

        if (!$context instanceof \context_user) {
            return;
        }

        $DB->delete_records('paygw_epayco_transactions', ['userid' => $context->instanceid]);
    }

    /**
     * Delete all user data for the specified user, in the specified contexts.
     *
     * @param approved_contextlist $contextlist The approved contexts and user information to delete information for.
     */
    public static function delete_data_for_user(approved_contextlist $contextlist): void {
        global $DB;

        $userid = $contextlist->get_user()->id;

        $DB->delete_records('paygw_epayco_transactions', ['userid' => $userid]);
    }

    /**
     * Delete multiple users within a single context.
     *
     * @param approved_userlist $userlist The approved context and user information to delete information for.
     */
    public static function delete_data_for_users(approved_userlist $userlist): void {
        global $DB;

        $context = $userlist->get_context();

        if (!$context instanceof \context_user) {
            return;
        }

        $userids = $userlist->get_userids();

        foreach ($userids as $userid) {
            $DB->delete_records('paygw_epayco_transactions', ['userid' => $userid]);
        }
    }
}
