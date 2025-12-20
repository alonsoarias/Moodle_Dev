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
 * Various helper methods for interacting with the ePayco API.
 *
 * @package    paygw_epayco
 * @copyright  2025 ingeweb.co <soporte@ingeweb.co>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace paygw_epayco;

use core_payment\helper;
use moodle_url;

defined('MOODLE_INTERNAL') || die();

/**
 * The helper class for ePayco payment gateway.
 *
 * @copyright  2025 ingeweb.co <soporte@ingeweb.co>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class epayco_helper {

    /**
     * Transaction status constants from ePayco.
     */
    const STATUS_ACCEPTED = 1;      // Aceptada.
    const STATUS_REJECTED = 2;      // Rechazada.
    const STATUS_PENDING = 3;       // Pendiente.
    const STATUS_FAILED = 4;        // Fallida.
    const STATUS_REVERSED = 6;      // Reversada.
    const STATUS_HELD = 7;          // Retenida.
    const STATUS_INITIATED = 8;     // Iniciada.
    const STATUS_EXPIRED = 9;       // Expirada.
    const STATUS_ABANDONED = 10;    // Abandonada.
    const STATUS_CANCELLED = 11;    // Cancelada.
    const STATUS_ANTIFRAUD = 12;    // Antifraude.

    /** @var string P_CUST_ID_CLIENTE. */
    private $pcustidcliente;

    /** @var string P_KEY. */
    private $pkey;

    /** @var string PUBLIC_KEY. */
    private $publickey;

    /** @var string PRIVATE_KEY. */
    private $privatekey;

    /** @var string Environment (test or production). */
    private $environment;

    /** @var bool Test mode flag. */
    private $testmode;

    /**
     * Constructor.
     *
     * @param string $pcustidcliente Customer ID.
     * @param string $pkey P_KEY.
     * @param string $publickey Public key.
     * @param string $privatekey Private key.
     * @param string $environment Environment.
     */
    public function __construct(
        string $pcustidcliente,
        string $pkey,
        string $publickey,
        string $privatekey,
        string $environment = 'test'
    ) {
        $this->pcustidcliente = $pcustidcliente;
        $this->pkey = $pkey;
        $this->publickey = $publickey;
        $this->privatekey = $privatekey;
        $this->environment = $environment;
        $this->testmode = ($environment === 'test');
    }

    /**
     * Generate a unique reference code for the transaction.
     *
     * @param int $userid User ID.
     * @param string $component Payment component.
     * @param string $paymentarea Payment area.
     * @param int $itemid Item ID.
     * @return string Reference code.
     */
    public function generate_reference(int $userid, string $component, string $paymentarea, int $itemid): string {
        return sprintf('EP_%d_%s_%s_%d_%s',
            $userid,
            substr($component, 0, 10),
            substr($paymentarea, 0, 10),
            $itemid,
            substr(uniqid(), -6)
        );
    }

    /**
     * Calculate signature for transaction validation.
     *
     * @param string $refpayco Reference payco.
     * @param string $transactionid Transaction ID.
     * @param float $amount Amount.
     * @param string $currency Currency code.
     * @return string SHA256 signature.
     */
    public function calculate_signature(
        string $refpayco,
        string $transactionid,
        float $amount,
        string $currency
    ): string {
        $signaturestring = sprintf(
            '%s^%s^%s^%s^%s^%s',
            $this->pcustidcliente,
            $this->pkey,
            $refpayco,
            $transactionid,
            $amount,
            $currency
        );
        return hash('sha256', $signaturestring);
    }

    /**
     * Verify signature from ePayco response.
     *
     * @param string $refpayco Reference payco.
     * @param string $transactionid Transaction ID.
     * @param float $amount Amount.
     * @param string $currency Currency code.
     * @param string $receivedsignature Received signature.
     * @return bool True if signature is valid.
     */
    public function verify_signature(
        string $refpayco,
        string $transactionid,
        float $amount,
        string $currency,
        string $receivedsignature
    ): bool {
        $calculated = $this->calculate_signature($refpayco, $transactionid, $amount, $currency);
        return hash_equals(strtolower($calculated), strtolower($receivedsignature));
    }

    /**
     * Get transaction details from ePayco API.
     *
     * @param string $refpayco Reference payco.
     * @return array|null Transaction data or null on error.
     */
    public function get_transaction(string $refpayco): ?array {
        $url = 'https://secure.epayco.co/validation/v1/reference/' . urlencode($refpayco);

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
            ],
        ]);

        $response = curl_exec($ch);
        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            debugging('ePayco API Error: ' . $error, DEBUG_DEVELOPER);
            return null;
        }

        $decoded = json_decode($response, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            debugging('ePayco JSON Decode Error: ' . json_last_error_msg(), DEBUG_DEVELOPER);
            return null;
        }

        if ($httpcode >= 400 || !isset($decoded['success']) || !$decoded['success']) {
            debugging('ePayco API HTTP Error: ' . $httpcode . ' - ' . $response, DEBUG_DEVELOPER);
            return null;
        }

        return $decoded['data'] ?? null;
    }

    /**
     * Get status text from status code.
     *
     * @param int $statuscode Status code.
     * @return string Status text.
     */
    public static function get_status_text(int $statuscode): string {
        $statuses = [
            self::STATUS_ACCEPTED => 'Aceptada',
            self::STATUS_REJECTED => 'Rechazada',
            self::STATUS_PENDING => 'Pendiente',
            self::STATUS_FAILED => 'Fallida',
            self::STATUS_REVERSED => 'Reversada',
            self::STATUS_HELD => 'Retenida',
            self::STATUS_INITIATED => 'Iniciada',
            self::STATUS_EXPIRED => 'Expirada',
            self::STATUS_ABANDONED => 'Abandonada',
            self::STATUS_CANCELLED => 'Cancelada',
            self::STATUS_ANTIFRAUD => 'Antifraude',
        ];
        return $statuses[$statuscode] ?? 'Desconocido';
    }

    /**
     * Check if the transaction status is successful.
     *
     * @param int $statuscode Status code.
     * @return bool True if successful.
     */
    public static function is_successful(int $statuscode): bool {
        return $statuscode === self::STATUS_ACCEPTED;
    }

    /**
     * Check if the transaction status is pending.
     *
     * @param int $statuscode Status code.
     * @return bool True if pending.
     */
    public static function is_pending(int $statuscode): bool {
        return in_array($statuscode, [
            self::STATUS_PENDING,
            self::STATUS_INITIATED,
        ]);
    }

    /**
     * Check if the transaction status is failed/rejected.
     *
     * @param int $statuscode Status code.
     * @return bool True if failed.
     */
    public static function is_failed(int $statuscode): bool {
        return in_array($statuscode, [
            self::STATUS_REJECTED,
            self::STATUS_FAILED,
            self::STATUS_REVERSED,
            self::STATUS_EXPIRED,
            self::STATUS_ABANDONED,
            self::STATUS_CANCELLED,
            self::STATUS_ANTIFRAUD,
        ]);
    }

    /**
     * Deliver the order after successful payment.
     *
     * @param string $component Component name.
     * @param string $paymentarea Payment area.
     * @param int $itemid Item ID.
     * @param int $paymentid Payment ID.
     * @param int $userid User ID.
     * @return bool True if delivery was successful.
     */
    public function deliver_order(
        string $component,
        string $paymentarea,
        int $itemid,
        int $paymentid,
        int $userid
    ): bool {
        global $DB;

        try {
            $payable = helper::get_payable($component, $paymentarea, $itemid);
            $cost = helper::get_rounded_cost($payable->get_amount(), $payable->get_currency(),
                helper::get_gateway_surcharge('epayco'));
            $successurl = helper::get_success_url($component, $paymentarea, $itemid);

            helper::deliver_order($component, $paymentarea, $itemid, $paymentid, $userid);

            return true;
        } catch (\Exception $e) {
            debugging('ePayco deliver_order error: ' . $e->getMessage(), DEBUG_DEVELOPER);
            return false;
        }
    }

    /**
     * Save transaction to database.
     *
     * @param array $data Transaction data.
     * @return int|bool Record ID or false on failure.
     */
    public function save_transaction(array $data) {
        global $DB;

        $record = new \stdClass();
        $record->userid = $data['userid'];
        $record->component = $data['component'];
        $record->paymentarea = $data['paymentarea'];
        $record->itemid = $data['itemid'];
        $record->reference = $data['reference'];
        $record->refpayco = $data['refpayco'] ?? '';
        $record->transactionid = $data['transactionid'] ?? '';
        $record->amount = $data['amount'];
        $record->currency = $data['currency'];
        $record->status = $data['status'];
        $record->response = isset($data['response']) ? json_encode($data['response']) : '';
        $record->timecreated = time();
        $record->timemodified = time();

        return $DB->insert_record('paygw_epayco_transactions', $record);
    }

    /**
     * Update transaction in database.
     *
     * @param int $id Record ID.
     * @param array $data Data to update.
     * @return bool True on success.
     */
    public function update_transaction(int $id, array $data): bool {
        global $DB;

        $record = new \stdClass();
        $record->id = $id;

        foreach ($data as $key => $value) {
            if ($key === 'response') {
                $record->$key = json_encode($value);
            } else {
                $record->$key = $value;
            }
        }

        $record->timemodified = time();

        return $DB->update_record('paygw_epayco_transactions', $record);
    }

    /**
     * Get transaction by reference.
     *
     * @param string $reference Transaction reference.
     * @return object|null Transaction record or null.
     */
    public function get_transaction_by_reference(string $reference): ?object {
        global $DB;
        return $DB->get_record('paygw_epayco_transactions', ['reference' => $reference]) ?: null;
    }

    /**
     * Get transaction by refpayco.
     *
     * @param string $refpayco ePayco reference.
     * @return object|null Transaction record or null.
     */
    public function get_transaction_by_refpayco(string $refpayco): ?object {
        global $DB;
        return $DB->get_record('paygw_epayco_transactions', ['refpayco' => $refpayco]) ?: null;
    }

    /**
     * Get pending transactions.
     *
     * @param int $limit Maximum number of records.
     * @return array Array of transaction records.
     */
    public function get_pending_transactions(int $limit = 100): array {
        global $DB;

        return $DB->get_records_select(
            'paygw_epayco_transactions',
            "status IN (:pending, :initiated)",
            [
                'pending' => 'PENDING',
                'initiated' => 'INITIATED',
            ],
            'timecreated ASC',
            '*',
            0,
            $limit
        );
    }

    /**
     * Get the public key.
     *
     * @return string Public key.
     */
    public function get_public_key(): string {
        return $this->publickey;
    }

    /**
     * Get the P_CUST_ID_CLIENTE.
     *
     * @return string Customer ID.
     */
    public function get_p_cust_id_cliente(): string {
        return $this->pcustidcliente;
    }

    /**
     * Check if in test mode.
     *
     * @return bool True if test mode.
     */
    public function is_test_mode(): bool {
        return $this->testmode;
    }

    /**
     * Send notification to user.
     *
     * @param int $userid User ID.
     * @param string $subject Message subject.
     * @param string $message Message body.
     * @param array $data Additional data for placeholders.
     */
    public function notify_user(int $userid, string $subject, string $message, array $data = []): void {
        global $DB;

        $user = $DB->get_record('user', ['id' => $userid]);
        if (!$user) {
            return;
        }

        // Replace placeholders.
        $placeholders = [
            '{firstname}' => $user->firstname,
            '{fullname}' => fullname($user),
            '{amount}' => $data['amount'] ?? '',
            '{currency}' => $data['currency'] ?? '',
            '{orderid}' => $data['orderid'] ?? '',
        ];

        $subject = strtr($subject, $placeholders);
        $message = strtr($message, $placeholders);

        $eventdata = new \core\message\message();
        $eventdata->component = 'paygw_epayco';
        $eventdata->name = 'payment_status';
        $eventdata->userfrom = \core_user::get_noreply_user();
        $eventdata->userto = $user;
        $eventdata->subject = $subject;
        $eventdata->fullmessage = $message;
        $eventdata->fullmessageformat = FORMAT_PLAIN;
        $eventdata->fullmessagehtml = nl2br($message);
        $eventdata->smallmessage = '';

        if (isset($data['url'])) {
            $eventdata->contexturl = $data['url'];
        }

        message_send($eventdata);
    }

    /**
     * Map ePayco status code to internal status string.
     *
     * @param int $codresponse ePayco response code.
     * @return string Internal status string.
     */
    public function map_epayco_status(int $codresponse): string {
        return match($codresponse) {
            self::STATUS_ACCEPTED => 'APPROVED',
            self::STATUS_REJECTED => 'REJECTED',
            self::STATUS_PENDING => 'PENDING',
            self::STATUS_FAILED => 'FAILED',
            self::STATUS_REVERSED => 'REVERSED',
            self::STATUS_HELD => 'HELD',
            self::STATUS_INITIATED => 'INITIATED',
            self::STATUS_EXPIRED => 'EXPIRED',
            self::STATUS_ABANDONED => 'ABANDONED',
            self::STATUS_CANCELLED => 'CANCELLED',
            self::STATUS_ANTIFRAUD => 'ANTIFRAUD',
            default => 'UNKNOWN',
        };
    }

    /**
     * Send notification email to user.
     *
     * @param object $user User object.
     * @param object $config Plugin configuration.
     * @param string $type Notification type (completed, pending).
     * @param array $data Additional data for placeholders.
     */
    public function send_notification_email(object $user, object $config, string $type, array $data = []): void {
        $subjectfield = 'email_' . $type . '_subject';
        $bodyfield = 'email_' . $type . '_body';

        $subject = $config->$subjectfield ?? get_string('email_' . $type . '_subject_default', 'paygw_epayco');
        $body = $config->$bodyfield ?? get_string('email_' . $type . '_body_default', 'paygw_epayco');

        // Replace placeholders.
        $placeholders = [
            '{firstname}' => $user->firstname,
            '{fullname}' => fullname($user),
            '{amount}' => $data['amount'] ?? '',
            '{currency}' => $data['currency'] ?? '',
            '{orderid}' => $data['orderid'] ?? '',
        ];

        $subject = strtr($subject, $placeholders);
        $body = strtr($body, $placeholders);

        $eventdata = new \core\message\message();
        $eventdata->component = 'paygw_epayco';
        $eventdata->name = 'payment_status';
        $eventdata->userfrom = \core_user::get_noreply_user();
        $eventdata->userto = $user;
        $eventdata->subject = $subject;
        $eventdata->fullmessage = $body;
        $eventdata->fullmessageformat = FORMAT_PLAIN;
        $eventdata->fullmessagehtml = nl2br(s($body));
        $eventdata->smallmessage = $subject;

        message_send($eventdata);
    }
}
