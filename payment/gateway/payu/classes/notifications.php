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
 * Notifications handler class.
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
     * @param array $extradata Additional data
     * @return bool Success status
     */
    public static function send_payment_receipt(int $userid, float $amount, string $currency, 
            int $paymentid, string $state, array $extradata = []): bool {
        global $DB, $CFG;
        
        $user = $DB->get_record('user', ['id' => $userid]);
        if (!$user) {
            return false;
        }
        
        $subject = get_string('messagesubject_payment_receipt', 'paygw_payu');
        
        $a = new \stdClass();
        $a->fullname = fullname($user);
        $a->amount = $currency . ' ' . number_format($amount, 2);
        $a->paymentid = $paymentid;
        $a->transactionid = $extradata['transactionid'] ?? '';
        $a->paymentmethod = $extradata['paymentmethod'] ?? '';
        $a->date = userdate(time());
        $a->state = $state;
        
        $messagehtml = '';
        $messagetext = '';
        
        switch ($state) {
            case 'APPROVED':
                $messagetext = get_string('message_payment_success', 'paygw_payu', $a);
                break;
            case 'PENDING':
                $subject = get_string('messagesubject_payment_pending', 'paygw_payu');
                $messagetext = get_string('message_payment_pending', 'paygw_payu', $a);
                break;
            case 'DECLINED':
            case 'ERROR':
                $subject = get_string('messagesubject_payment_error', 'paygw_payu');
                $messagetext = get_string('message_payment_error', 'paygw_payu', $a);
                break;
        }
        
        $messagehtml = text_to_html($messagetext);
        
        $message = new \core\message\message();
        $message->component = 'paygw_payu';
        $message->name = 'payment_receipt';
        $message->userfrom = \core_user::get_noreply_user();
        $message->userto = $user;
        $message->subject = $subject;
        $message->fullmessage = $messagetext;
        $message->fullmessageformat = FORMAT_PLAIN;
        $message->fullmessagehtml = $messagehtml;
        $message->smallmessage = $subject;
        $message->notification = 1;
        
        return message_send($message);
    }
    
    /**
     * Send cash payment reminder.
     *
     * @param int $userid User ID
     * @param float $amount Payment amount
     * @param string $currency Currency code
     * @param string $reference Payment reference
     * @param int $expirationtime Expiration timestamp
     * @return bool Success status
     */
    public static function send_cash_reminder(int $userid, float $amount, string $currency, 
            string $reference, int $expirationtime): bool {
        global $DB, $CFG;
        
        $user = $DB->get_record('user', ['id' => $userid]);
        if (!$user) {
            return false;
        }
        
        $subject = get_string('messagesubject_cashreminder', 'paygw_payu');
        
        $a = new \stdClass();
        $a->fullname = fullname($user);
        $a->amount = $currency . ' ' . number_format($amount, 2);
        $a->reference = $reference;
        $a->expirationdate = userdate($expirationtime);
        $a->receipturl = $CFG->wwwroot . '/payment/gateway/payu/receipt.php?reference=' . $reference;
        
        $messagetext = get_string('message_cash_reminder', 'paygw_payu', $a);
        $messagehtml = text_to_html($messagetext);
        
        $message = new \core\message\message();
        $message->component = 'paygw_payu';
        $message->name = 'payment_receipt';
        $message->userfrom = \core_user::get_noreply_user();
        $message->userto = $user;
        $message->subject = $subject;
        $message->fullmessage = $messagetext;
        $message->fullmessageformat = FORMAT_PLAIN;
        $message->fullmessagehtml = $messagehtml;
        $message->smallmessage = $subject;
        $message->notification = 1;
        
        return message_send($message);
    }
    
    /**
     * Send admin notification for failed payments.
     *
     * @param int $paymentid Payment ID
     * @param string $error Error message
     * @param array $data Transaction data
     * @return bool Success status
     */
    public static function notify_admin_error(int $paymentid, string $error, array $data = []): bool {
        global $CFG;
        
        $admins = get_admins();
        if (empty($admins)) {
            return false;
        }
        
        $subject = 'PayU Payment Error - Payment #' . $paymentid;
        
        $messagetext = "A payment error has occurred:\n\n";
        $messagetext .= "Payment ID: $paymentid\n";
        $messagetext .= "Error: $error\n";
        $messagetext .= "Time: " . userdate(time()) . "\n";
        
        if (!empty($data)) {
            $messagetext .= "\nAdditional Data:\n";
            foreach ($data as $key => $value) {
                $messagetext .= "$key: $value\n";
            }
        }
        
        $messagehtml = text_to_html($messagetext);
        
        $message = new \core\message\message();
        $message->component = 'paygw_payu';
        $message->name = 'payment_receipt';
        $message->userfrom = \core_user::get_noreply_user();
        $message->subject = $subject;
        $message->fullmessage = $messagetext;
        $message->fullmessageformat = FORMAT_PLAIN;
        $message->fullmessagehtml = $messagehtml;
        $message->smallmessage = $subject;
        $message->notification = 1;
        
        $success = true;
        foreach ($admins as $admin) {
            $message->userto = $admin;
            $success = message_send($message) && $success;
        }
        
        return $success;
    }
    
    /**
     * Process and send notification based on transaction state.
     *
     * @param \stdClass $transaction Transaction record
     * @param \stdClass $payment Payment record
     * @return bool Success status
     */
    public static function process_transaction_notification(\stdClass $transaction, \stdClass $payment): bool {
        $extradata = [
            'transactionid' => $transaction->payu_transaction_id ?? '',
            'paymentmethod' => $transaction->payment_method ?? '',
            'responsecode' => $transaction->response_code ?? '',
        ];
        
        return self::send_payment_receipt(
            $payment->userid,
            $payment->amount,
            $payment->currency,
            $payment->id,
            $transaction->state,
            $extradata
        );
    }
}