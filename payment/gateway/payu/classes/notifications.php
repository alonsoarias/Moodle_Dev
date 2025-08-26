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
 * Notifications handler for PayU payment gateway.
 *
 * @package    paygw_payu
 * @copyright  2024 Orion Cloud Consulting SAS
 * @author     Alonso Arias <soporte@orioncloud.com.co>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace paygw_payu;

defined('MOODLE_INTERNAL') || die();

/**
 * Notifications class for handling payment notifications.
 *
 * @copyright  2024 Orion Cloud Consulting SAS
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class notifications {
    
    /**
     * Send payment receipt notification.
     *
     * @param int $userid User ID
     * @param float $amount Payment amount
     * @param string $currency Currency code
     * @param int $paymentid Payment ID
     * @param string $state Transaction state
     * @param array $extradata Additional data for the notification
     * @return int|false Message ID or false on error
     */
    public static function send_payment_receipt(int $userid, float $amount, string $currency, 
            int $paymentid, string $state, array $extradata = []) {
        global $DB;
        
        $user = \core_user::get_user($userid);
        if (empty($user) || isguestuser($user) || !empty($user->deleted)) {
            return false;
        }
        
        // Prepare message data.
        $a = (object)[
            'fullname' => fullname($user),
            'amount' => \core_payment\helper::get_cost_as_string($amount, $currency),
            'paymentid' => $paymentid,
            'state' => $state,
            'transactionid' => $extradata['transactionid'] ?? '',
            'paymentmethod' => $extradata['paymentmethod'] ?? '',
            'date' => userdate(time()),
        ];
        
        // Choose message based on state.
        $messagetype = 'payment_receipt';
        if ($state === 'PENDING') {
            $messagetype = 'payment_pending';
            $messagebody = get_string('message_payment_pending', 'paygw_payu', $a);
        } else if ($state === 'APPROVED') {
            $messagebody = get_string('message_payment_success', 'paygw_payu', $a);
        } else {
            $messagetype = 'payment_error';
            $messagebody = get_string('message_payment_error', 'paygw_payu', $a);
        }
        
        // Create message.
        $message = new \core\message\message();
        $message->component = 'paygw_payu';
        $message->name = $messagetype;
        $message->userfrom = \core_user::get_noreply_user();
        $message->userto = $user;
        $message->subject = get_string('messagesubject_' . $messagetype, 'paygw_payu');
        $message->fullmessage = $messagebody;
        $message->fullmessageformat = FORMAT_MARKDOWN;
        $message->fullmessagehtml = markdown_to_html($messagebody);
        $message->notification = 1;
        
        // Add context URL if available.
        if (!empty($extradata['contexturl'])) {
            $message->contexturl = $extradata['contexturl'];
            $message->contexturlname = get_string('viewpayment', 'paygw_payu');
        }
        
        // Send the message.
        return message_send($message);
    }
    
    /**
     * Send payment reminder for pending cash payments.
     *
     * @param int $userid User ID
     * @param float $amount Payment amount
     * @param string $currency Currency code
     * @param string $reference Payment reference
     * @param string $expirationdate Expiration date
     * @param string $receipturl Receipt URL
     * @return int|false Message ID or false on error
     */
    public static function send_cash_payment_reminder(int $userid, float $amount, string $currency,
            string $reference, string $expirationdate, string $receipturl) {
        global $DB;
        
        $user = \core_user::get_user($userid);
        if (empty($user) || isguestuser($user) || !empty($user->deleted)) {
            return false;
        }
        
        $a = (object)[
            'fullname' => fullname($user),
            'amount' => \core_payment\helper::get_cost_as_string($amount, $currency),
            'reference' => $reference,
            'expirationdate' => userdate(strtotime($expirationdate)),
            'receipturl' => $receipturl,
        ];
        
        $message = new \core\message\message();
        $message->component = 'paygw_payu';
        $message->name = 'payment_pending';
        $message->userfrom = \core_user::get_noreply_user();
        $message->userto = $user;
        $message->subject = get_string('messagesubject_cashreminder', 'paygw_payu');
        $message->fullmessage = get_string('message_cash_reminder', 'paygw_payu', $a);
        $message->fullmessageformat = FORMAT_MARKDOWN;
        $message->fullmessagehtml = markdown_to_html($message->fullmessage);
        $message->notification = 1;
        $message->contexturl = $receipturl;
        $message->contexturlname = get_string('viewreceipt', 'paygw_payu');
        
        return message_send($message);
    }
}