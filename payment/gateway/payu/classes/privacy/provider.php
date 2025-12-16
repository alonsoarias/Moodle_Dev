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
 * Privacy Subsystem implementation for paygw_payu.
 *
 * @package    paygw_payu
 * @category   privacy
 * @copyright  2025 ingeweb.co <soporte@ingeweb.co>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace paygw_payu\privacy;

use core_payment\privacy\paygw_provider;
use core_privacy\local\metadata\collection;
use core_privacy\local\request\writer;
use core_privacy\local\request\transform;

defined('MOODLE_INTERNAL') || die();

/**
 * Privacy Subsystem implementation for paygw_payu.
 *
 * @copyright  2025 ingeweb.co <soporte@ingeweb.co>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class provider implements
    \core_privacy\local\request\data_provider,
    paygw_provider,
    \core_privacy\local\metadata\provider {

    /**
     * Returns meta data about this system.
     *
     * @param collection $collection The initialised collection to add items to.
     * @return collection A listing of user data stored through this system.
     */
    public static function get_metadata(collection $collection): collection {

        // Data may be exported to an external location (PayU).
        $collection->add_external_location_link(
            'payu',
            [
                'fullname' => 'privacy:metadata:payu:fullname',
                'email' => 'privacy:metadata:payu:email',
            ],
            'privacy:metadata:payu'
        );

        // The paygw_payu_transactions table stores transaction data.
        $collection->add_database_table(
            'paygw_payu_transactions',
            [
                'userid' => 'privacy:metadata:paygw_payu_transactions:userid',
                'transactionid' => 'privacy:metadata:paygw_payu_transactions:transactionid',
                'referencecode' => 'privacy:metadata:paygw_payu_transactions:referencecode',
                'amount' => 'privacy:metadata:paygw_payu_transactions:amount',
                'state' => 'privacy:metadata:paygw_payu_transactions:state',
                'timecreated' => 'privacy:metadata:paygw_payu_transactions:timecreated',
            ],
            'privacy:metadata:paygw_payu_transactions'
        );

        return $collection;
    }

    /**
     * Export all user data for the specified payment record, and the given context.
     *
     * @param \context $context Context
     * @param array $subcontext The location within the current context that the payment data belongs
     * @param \stdClass $payment The payment record
     */
    public static function export_payment_data(\context $context, array $subcontext, \stdClass $payment) {
        global $DB;

        $subcontext[] = get_string('gatewayname', 'paygw_payu');

        // Find transaction by component, paymentarea and itemid.
        $record = $DB->get_record('paygw_payu_transactions', [
            'component' => $payment->component,
            'paymentarea' => $payment->paymentarea,
            'itemid' => $payment->itemid,
            'userid' => $payment->userid,
        ]);

        if ($record) {
            $data = (object) [
                'transactionid' => $record->transactionid,
                'referencecode' => $record->referencecode,
                'amount' => $record->amount,
                'currency' => $record->currency,
                'state' => $record->state,
                'timecreated' => transform::datetime($record->timecreated),
                'timemodified' => transform::datetime($record->timemodified),
            ];

            writer::with_context($context)->export_data($subcontext, $data);
        }
    }

    /**
     * Delete all user data related to the given payments.
     *
     * @param string $paymentsql SQL query that selects payment.id field for the payments
     * @param array $paymentparams Array of parameters for $paymentsql
     */
    public static function delete_data_for_payment_sql(string $paymentsql, array $paymentparams) {
        global $DB;

        // Get payment records.
        $payments = $DB->get_records_sql("SELECT * FROM {payments} WHERE id IN ({$paymentsql})", $paymentparams);

        foreach ($payments as $payment) {
            $DB->delete_records('paygw_payu_transactions', [
                'component' => $payment->component,
                'paymentarea' => $payment->paymentarea,
                'itemid' => $payment->itemid,
                'userid' => $payment->userid,
            ]);
        }
    }
}
