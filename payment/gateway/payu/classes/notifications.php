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
 * Notifications class.
 *
 * Handles email notifications for PayU payment transactions.
 */
class notifications {

    /**
     * Send payment notification to user.
     *
     * @param int $userid User ID
     * @param float $amount Payment amount
     * @param string $currency Currency code
     * @param int $paymentid Payment ID
     * @param string $state Transaction state
     * @param array $extradata Additional data for the notification
     * @return bool True if notification sent successfully
     */
    public static function notify($userid, $amount, $currency, $paymentid, $state, $extradata = []) {
        global $DB;

        // Get user object.
        $user = \core_user::get_user($userid);
        if (empty($user) || isguestuser($user) || !empty($user->deleted)) {
            return false;
        }

        // Prepare notification data.
        $a = (object)[
            'fullname' => fullname($user),
            'amount' => \core_payment\helper::get_cost_as_string($amount, $currency),
            'paymentid' => $paymentid,
            'state' => $state,
            'transactionid' => $extradata['transactionid'] ?? '',
            'paymentmethod' => $extradata['paymentmethod'] ?? '',
            'reference' => $extradata['reference'] ?? '',
        ];

        // Create message object.
        $message = new \core\message\message();
        $message->component = 'paygw_payu';
        $message->name = 'payment_receipt';
        $message->userfrom = \core_user::get_noreply_user();
        $message->userto = $user;
        $message->subject = get_string('messagesubject_payment_receipt', 'paygw_payu');
        $message->fullmessageformat = FORMAT_MARKDOWN;
        $message->notification = 1;
        $message->contexturl = new \moodle_url('/');
        $message->contexturlname = get_string('viewpayment', 'paygw_payu');

        // Set message based on state.
        switch ($state) {
            case 'APPROVED':
                $message->fullmessage = get_string('message_payment_success', 'paygw_payu', $a);
                $message->fullmessagehtml = get_string('message_payment_success', 'paygw_payu', $a);
                break;
            case 'PENDING':
                $message->fullmessage = get_string('message_payment_pending', 'paygw_payu', $a);
                $message->fullmessagehtml = get_string('message_payment_pending', 'paygw_payu', $a);
                break;
            case 'DECLINED':
            case 'ERROR':
                $message->fullmessage = get_string('message_payment_error', 'paygw_payu', $a);
                $message->fullmessagehtml = get_string('message_payment_error', 'paygw_payu', $a);
                break;
            default:
                return false;
        }

        // Send notification.
        return message_send($message);
    }

    /**
     * Send cash payment reminder.
     *
     * @param int $userid User ID
     * @param string $reference Payment reference
     * @param \DateTime $expiry Expiry date
     * @param float $amount Amount to pay
     * @param string $currency Currency code
     * @return bool
     */
    public static function send_cash_reminder($userid, $reference, $expiry, $amount, $currency) {
        global $DB;

        $user = \core_user::get_user($userid);
        if (empty($user) || isguestuser($user)) {
            return false;
        }

        $a = (object)[
            'fullname' => fullname($user),
            'reference' => $reference,
            'expiry' => userdate($expiry->getTimestamp()),
            'amount' => \core_payment\helper::get_cost_as_string($amount, $currency),
        ];

        $message = new \core\message\message();
        $message->component = 'paygw_payu';
        $message->name = 'cash_reminder';
        $message->userfrom = \core_user::get_noreply_user();
        $message->userto = $user;
        $message->subject = get_string('messagesubject_cashreminder', 'paygw_payu');
        $message->fullmessage = get_string('message_cashreminder', 'paygw_payu', $a);
        $message->fullmessagehtml = get_string('message_cashreminder_html', 'paygw_payu', $a);
        $message->fullmessageformat = FORMAT_MARKDOWN;
        $message->notification = 1;

        return message_send($message);
    }
}