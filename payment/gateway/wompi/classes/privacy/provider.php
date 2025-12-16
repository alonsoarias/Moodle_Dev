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
 * Privacy Subsystem implementation for paygw_wompi.
 *
 * @package    paygw_wompi
 * @copyright  2025 Alonso Arias <soporte@ingeweb.co>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace paygw_wompi\privacy;

use core_privacy\local\metadata\collection;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\approved_userlist;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\transform;
use core_privacy\local\request\userlist;
use core_privacy\local\request\writer;

/**
 * Privacy Subsystem for paygw_wompi implementing metadata and request providers.
 *
 * @copyright  2025 Alonso Arias <soporte@ingeweb.co>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class provider implements
    \core_privacy\local\metadata\provider,
    \core_privacy\local\request\plugin\provider,
    \core_privacy\local\request\core_userlist_provider {

    /**
     * Returns meta data about this system.
     *
     * @param collection $collection The initialised collection to add items to.
     * @return collection A listing of user data stored through this system.
     */
    public static function get_metadata(collection $collection): collection {
        // Database table.
        $collection->add_database_table(
            'paygw_wompi_transactions',
            [
                'userid' => 'privacy:metadata:paygw_wompi_transactions:userid',
                'transactionid' => 'privacy:metadata:paygw_wompi_transactions:transactionid',
                'reference' => 'privacy:metadata:paygw_wompi_transactions:reference',
                'amount' => 'privacy:metadata:paygw_wompi_transactions:amount',
                'status' => 'privacy:metadata:paygw_wompi_transactions:status',
                'timecreated' => 'privacy:metadata:paygw_wompi_transactions:timecreated',
            ],
            'privacy:metadata:paygw_wompi_transactions'
        );

        // External location - Wompi.
        $collection->add_external_location_link(
            'wompi',
            [
                'email' => 'privacy:metadata:wompi:email',
                'fullname' => 'privacy:metadata:wompi:fullname',
            ],
            'privacy:metadata:wompi'
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

        // Add system context if user has transactions.
        $sql = "SELECT c.id
                  FROM {context} c
                  JOIN {paygw_wompi_transactions} t ON c.contextlevel = :contextlevel
                 WHERE t.userid = :userid";

        $params = [
            'contextlevel' => CONTEXT_SYSTEM,
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

        if ($context->contextlevel != CONTEXT_SYSTEM) {
            return;
        }

        $sql = "SELECT userid FROM {paygw_wompi_transactions}";
        $userlist->add_from_sql('userid', $sql, []);
    }

    /**
     * Export all user data for the specified user, in the specified contexts.
     *
     * @param approved_contextlist $contextlist The approved contexts to export information for.
     */
    public static function export_user_data(approved_contextlist $contextlist): void {
        global $DB;

        $userid = $contextlist->get_user()->id;
        $contexts = $contextlist->get_contexts();

        foreach ($contexts as $context) {
            if ($context->contextlevel != CONTEXT_SYSTEM) {
                continue;
            }

            $transactions = $DB->get_records('paygw_wompi_transactions', ['userid' => $userid]);

            foreach ($transactions as $transaction) {
                $data = (object) [
                    'transactionid' => $transaction->transactionid,
                    'reference' => $transaction->reference,
                    'component' => $transaction->component,
                    'paymentarea' => $transaction->paymentarea,
                    'itemid' => $transaction->itemid,
                    'amount' => $transaction->amount / 100, // Convert from cents.
                    'currency' => $transaction->currency,
                    'status' => $transaction->status,
                    'paymentmethod' => $transaction->paymentmethod,
                    'timecreated' => transform::datetime($transaction->timecreated),
                    'timemodified' => transform::datetime($transaction->timemodified),
                ];

                writer::with_context($context)->export_data(
                    [get_string('pluginname', 'paygw_wompi'), $transaction->id],
                    $data
                );
            }
        }
    }

    /**
     * Delete all data for all users in the specified context.
     *
     * @param \context $context The specific context to delete data for.
     */
    public static function delete_data_for_all_users_in_context(\context $context): void {
        global $DB;

        if ($context->contextlevel != CONTEXT_SYSTEM) {
            return;
        }

        // We don't delete payment transaction records as they are needed for accounting purposes.
        // This is intentional and follows payment gateway best practices.
    }

    /**
     * Delete all user data for the specified user, in the specified contexts.
     *
     * @param approved_contextlist $contextlist The approved contexts and user information to delete information for.
     */
    public static function delete_data_for_user(approved_contextlist $contextlist): void {
        // We don't delete payment transaction records as they are needed for accounting purposes.
        // This is intentional and follows payment gateway best practices.
    }

    /**
     * Delete multiple users within a single context.
     *
     * @param approved_userlist $userlist The approved context and user information to delete information for.
     */
    public static function delete_data_for_users(approved_userlist $userlist): void {
        // We don't delete payment transaction records as they are needed for accounting purposes.
        // This is intentional and follows payment gateway best practices.
    }
}
