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
 * @copyright  2024 Orion Cloud Consulting SAS
 * @author     Alonso Arias <soporte@orioncloud.com.co>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace paygw_payu\privacy;

use core_payment\privacy\paygw_provider;
use core_privacy\local\metadata\collection;
use core_privacy\local\request\writer;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\approved_userlist;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\userlist;

defined('MOODLE_INTERNAL') || die();

/**
 * Privacy Subsystem for paygw_payu.
 *
 * @copyright  2024 Orion Cloud Consulting SAS
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class provider implements 
    \core_privacy\local\request\data_provider,
    \core_privacy\local\metadata\provider,
    paygw_provider {

    /**
     * Returns metadata about this plugin's privacy.
     *
     * @param collection $collection The metadata collection to add to
     * @return collection The updated collection
     */
    public static function get_metadata(collection $collection): collection {
        
        // External location - PayU.
        $collection->add_external_location_link(
            'payu',
            [
                'fullname' => 'privacy:metadata:paygw_payu:payu:fullname',
                'email' => 'privacy:metadata:paygw_payu:payu:email',
                'phone' => 'privacy:metadata:paygw_payu:payu:phone',
                'documentnumber' => 'privacy:metadata:paygw_payu:payu:documentnumber',
                'address' => 'privacy:metadata:paygw_payu:payu:address',
                'creditcard' => 'privacy:metadata:paygw_payu:payu:creditcard',
                'amount' => 'privacy:metadata:paygw_payu:payu:amount',
                'currency' => 'privacy:metadata:paygw_payu:payu:currency',
            ],
            'privacy:metadata:paygw_payu:payu'
        );
        
        // Database table.
        $collection->add_database_table(
            'paygw_payu',
            [
                'paymentid' => 'privacy:metadata:paygw_payu:database:paymentid',
                'payu_order_id' => 'privacy:metadata:paygw_payu:database:payu_order_id',
                'payu_transaction_id' => 'privacy:metadata:paygw_payu:database:payu_transaction_id',
                'state' => 'privacy:metadata:paygw_payu:database:state',
                'payment_method' => 'privacy:metadata:paygw_payu:database:payment_method',
                'amount' => 'privacy:metadata:paygw_payu:database:amount',
                'currency' => 'privacy:metadata:paygw_payu:database:currency',
                'timecreated' => 'privacy:metadata:paygw_payu:database:timecreated',
            ],
            'privacy:metadata:paygw_payu:database'
        );
        
        return $collection;
    }

    /**
     * Export all user data for the specified payment record.
     *
     * @param \context $context Context
     * @param array $subcontext The location within the context
     * @param \stdClass $payment The payment record
     */
    public static function export_payment_data(\context $context, array $subcontext, \stdClass $payment) {
        global $DB;
        
        $subcontext[] = get_string('gatewayname', 'paygw_payu');
        
        $record = $DB->get_record('paygw_payu', ['paymentid' => $payment->id]);
        if ($record) {
            $data = (object) [
                'payu_order_id' => $record->payu_order_id,
                'payu_transaction_id' => $record->payu_transaction_id,
                'state' => $record->state,
                'payment_method' => $record->payment_method,
                'amount' => $record->amount,
                'currency' => $record->currency,
                'response_code' => $record->response_code,
                'timecreated' => transform::datetime($record->timecreated),
                'timemodified' => transform::datetime($record->timemodified),
            ];
            
            writer::with_context($context)->export_data($subcontext, $data);
        }
    }

    /**
     * Delete all user data for the specified payments.
     *
     * @param string $paymentsql SQL query selecting payment IDs
     * @param array $paymentparams Parameters for the SQL query
     */
    public static function delete_data_for_payment_sql(string $paymentsql, array $paymentparams) {
        global $DB;
        
        $DB->delete_records_select('paygw_payu', "paymentid IN ({$paymentsql})", $paymentparams);
    }
}