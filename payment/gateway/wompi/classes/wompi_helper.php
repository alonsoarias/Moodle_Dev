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
 * Various helper methods for interacting with the Wompi API.
 *
 * @package    paygw_wompi
 * @copyright  2025 Alonso Arias <soporte@ingeweb.co>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace paygw_wompi;

use core_payment\helper;
use moodle_url;

defined('MOODLE_INTERNAL') || die();

/**
 * The helper class for Wompi payment gateway.
 *
 * @copyright  2025 Alonso Arias <soporte@ingeweb.co>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class wompi_helper {

    /** @var string Public API key. */
    private $publickey;

    /** @var string Private API key. */
    private $privatekey;

    /** @var string Events private key for webhook verification. */
    private $eventskey;

    /** @var string Integrity key for signature generation. */
    private $integritykey;

    /** @var string Environment (sandbox or production). */
    private $environment;

    /** @var string API base URL. */
    private $apiurl;

    /**
     * Constructor.
     *
     * @param string $publickey Public API key.
     * @param string $privatekey Private API key.
     * @param string $integritykey Integrity key for signatures.
     * @param string $environment Environment (sandbox or production).
     * @param string $eventskey Events private key (optional).
     */
    public function __construct(
        string $publickey,
        string $privatekey,
        string $integritykey,
        string $environment = 'sandbox',
        string $eventskey = ''
    ) {
        $this->publickey = $publickey;
        $this->privatekey = $privatekey;
        $this->integritykey = $integritykey;
        $this->environment = $environment;
        $this->eventskey = $eventskey;
        $this->apiurl = gateway::get_api_url($environment);
    }

    /**
     * Generate the integrity signature for a transaction.
     *
     * The signature is: SHA256(reference + amount_in_cents + currency + integrity_key)
     *
     * @param string $reference Transaction reference.
     * @param int $amountincents Amount in cents.
     * @param string $currency Currency code (COP).
     * @return string SHA256 hash signature.
     */
    public function generate_integrity_signature(string $reference, int $amountincents, string $currency = 'COP'): string {
        $concatenated = $reference . $amountincents . $currency . $this->integritykey;
        return hash('sha256', $concatenated);
    }

    /**
     * Generate a unique transaction reference.
     *
     * @param string $component Component name.
     * @param string $paymentarea Payment area.
     * @param int $itemid Item ID.
     * @param int $userid User ID.
     * @return string Unique reference.
     */
    public function generate_reference(string $component, string $paymentarea, int $itemid, int $userid): string {
        return sprintf(
            '%s_%s_%d_%d_%d',
            substr($component, 0, 10),
            substr($paymentarea, 0, 10),
            $itemid,
            $userid,
            time()
        );
    }

    /**
     * Convert amount to cents (Wompi requires amounts in cents).
     *
     * @param float $amount Amount in currency units.
     * @return int Amount in cents.
     */
    public function amount_to_cents(float $amount): int {
        return (int) round($amount * 100);
    }

    /**
     * Convert cents to amount.
     *
     * @param int $cents Amount in cents.
     * @return float Amount in currency units.
     */
    public function cents_to_amount(int $cents): float {
        return $cents / 100;
    }

    /**
     * Make an API request to Wompi.
     *
     * @param string $endpoint API endpoint.
     * @param string $method HTTP method (GET, POST, etc.).
     * @param array|null $data Request data.
     * @param bool $usebearer Use Bearer token authentication.
     * @return array|null Response data or null on error.
     */
    public function api_request(string $endpoint, string $method = 'GET', ?array $data = null, bool $usebearer = true): ?array {
        $url = $this->apiurl . $endpoint;

        $curl = curl_init();

        $headers = [
            'Content-Type: application/json',
            'Accept: application/json',
        ];

        if ($usebearer) {
            $headers[] = 'Authorization: Bearer ' . $this->privatekey;
        }

        $options = [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_TIMEOUT => 30,
        ];

        if ($method === 'POST') {
            $options[CURLOPT_POST] = true;
            if ($data) {
                $options[CURLOPT_POSTFIELDS] = json_encode($data);
            }
        } else if ($method !== 'GET') {
            $options[CURLOPT_CUSTOMREQUEST] = $method;
            if ($data) {
                $options[CURLOPT_POSTFIELDS] = json_encode($data);
            }
        }

        curl_setopt_array($curl, $options);

        $response = curl_exec($curl);
        $httpcode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $error = curl_error($curl);

        curl_close($curl);

        if ($error) {
            debugging('Wompi API Error: ' . $error, DEBUG_DEVELOPER);
            return null;
        }

        $decoded = json_decode($response, true);

        if ($httpcode >= 400) {
            debugging('Wompi API HTTP Error: ' . $httpcode . ' - ' . $response, DEBUG_DEVELOPER);
            return null;
        }

        return $decoded;
    }

    /**
     * Get transaction details from Wompi.
     *
     * @param string $transactionid Transaction ID.
     * @return array|null Transaction data or null.
     */
    public function get_transaction(string $transactionid): ?array {
        $response = $this->api_request('/transactions/' . $transactionid, 'GET', null, false);
        return $response['data'] ?? null;
    }

    /**
     * Get transaction by reference.
     *
     * @param string $reference Transaction reference.
     * @return array|null Transaction data or null.
     */
    public function get_transaction_by_reference(string $reference): ?array {
        $response = $this->api_request('/transactions?reference=' . urlencode($reference), 'GET', null, false);
        if (!empty($response['data']) && is_array($response['data'])) {
            return $response['data'][0] ?? null;
        }
        return null;
    }

    /**
     * Create a transaction using the API (server-side).
     *
     * @param array $transactiondata Transaction data.
     * @return array|null Response data or null.
     */
    public function create_transaction(array $transactiondata): ?array {
        return $this->api_request('/transactions', 'POST', $transactiondata, true);
    }

    /**
     * Tokenize a credit card.
     *
     * @param array $carddata Card data (number, exp_month, exp_year, cvc, card_holder).
     * @return array|null Token data or null.
     */
    public function tokenize_card(array $carddata): ?array {
        return $this->api_request('/tokens/cards', 'POST', $carddata, true);
    }

    /**
     * Get acceptance token for terms and conditions.
     *
     * @return string|null Acceptance token or null.
     */
    public function get_acceptance_token(): ?string {
        $response = $this->api_request('/merchants/' . $this->publickey, 'GET', null, false);
        return $response['data']['presigned_acceptance']['acceptance_token'] ?? null;
    }

    /**
     * Get available payment methods from the merchant.
     *
     * @return array Payment methods.
     */
    public function get_payment_methods(): array {
        $response = $this->api_request('/merchants/' . $this->publickey, 'GET', null, false);
        return $response['data']['payment_methods'] ?? [];
    }

    /**
     * Verify webhook event signature.
     *
     * @param array $eventdata Event data from webhook.
     * @param string $signature Signature from webhook header.
     * @return bool True if signature is valid.
     */
    public function verify_webhook_signature(array $eventdata, string $signature): bool {
        if (empty($this->eventskey)) {
            return false;
        }

        // Build the signature string according to Wompi documentation.
        $properties = $eventdata['signature']['properties'] ?? [];
        $timestamp = $eventdata['timestamp'] ?? '';

        $signaturestring = '';
        foreach ($properties as $property) {
            $value = $this->get_nested_value($eventdata, $property);
            $signaturestring .= $value;
        }
        $signaturestring .= $timestamp . $this->eventskey;

        $calculatedsignature = hash('sha256', $signaturestring);

        return hash_equals($calculatedsignature, $signature);
    }

    /**
     * Get a nested value from an array using dot notation.
     *
     * @param array $data Data array.
     * @param string $path Path in dot notation (e.g., 'data.transaction.id').
     * @return mixed Value or empty string.
     */
    private function get_nested_value(array $data, string $path) {
        $keys = explode('.', $path);
        $value = $data;

        foreach ($keys as $key) {
            if (!isset($value[$key])) {
                return '';
            }
            $value = $value[$key];
        }

        return $value;
    }

    /**
     * Check if a transaction is approved.
     *
     * @param string $status Transaction status.
     * @return bool True if approved.
     */
    public function is_transaction_approved(string $status): bool {
        return strtoupper($status) === 'APPROVED';
    }

    /**
     * Check if a transaction is pending.
     *
     * @param string $status Transaction status.
     * @return bool True if pending.
     */
    public function is_transaction_pending(string $status): bool {
        return strtoupper($status) === 'PENDING';
    }

    /**
     * Check if a transaction is declined/error.
     *
     * @param string $status Transaction status.
     * @return bool True if declined or error.
     */
    public function is_transaction_failed(string $status): bool {
        $status = strtoupper($status);
        return in_array($status, ['DECLINED', 'ERROR', 'VOIDED']);
    }

    /**
     * Deliver the order/course to the user.
     *
     * @param string $component Component name.
     * @param string $paymentarea Payment area.
     * @param int $itemid Item ID.
     * @param int $userid User ID.
     * @param float $cost Cost in currency units.
     * @param string $currency Currency code.
     */
    public function deliver_order(
        string $component,
        string $paymentarea,
        int $itemid,
        int $userid,
        float $cost,
        string $currency
    ): void {
        $payable = helper::get_payable($component, $paymentarea, $itemid);

        $paymentid = helper::save_payment(
            $payable->get_account_id(),
            $component,
            $paymentarea,
            $itemid,
            $userid,
            $cost,
            $currency,
            'wompi'
        );

        helper::deliver_order($component, $paymentarea, $itemid, $paymentid, $userid);
    }

    /**
     * Save transaction data to the database.
     *
     * @param array $data Transaction data.
     * @return int|bool Record ID or false on failure.
     */
    public function save_transaction(array $data) {
        global $DB;

        $record = new \stdClass();
        $record->userid = $data['userid'];
        $record->transactionid = $data['transactionid'];
        $record->reference = $data['reference'];
        $record->component = $data['component'];
        $record->paymentarea = $data['paymentarea'];
        $record->itemid = $data['itemid'];
        $record->amount = $data['amount'];
        $record->currency = $data['currency'] ?? 'COP';
        $record->status = $data['status'];
        $record->paymentmethod = $data['paymentmethod'] ?? '';
        $record->timecreated = time();
        $record->timemodified = time();

        // Check if transaction already exists.
        $existing = $DB->get_record('paygw_wompi_transactions', ['transactionid' => $data['transactionid']]);
        if ($existing) {
            $record->id = $existing->id;
            $DB->update_record('paygw_wompi_transactions', $record);
            return $existing->id;
        }

        return $DB->insert_record('paygw_wompi_transactions', $record);
    }

    /**
     * Update transaction status in the database.
     *
     * @param string $transactionid Transaction ID.
     * @param string $status New status.
     * @return bool Success.
     */
    public function update_transaction_status(string $transactionid, string $status): bool {
        global $DB;

        $record = $DB->get_record('paygw_wompi_transactions', ['transactionid' => $transactionid]);
        if (!$record) {
            return false;
        }

        $record->status = $status;
        $record->timemodified = time();

        return $DB->update_record('paygw_wompi_transactions', $record);
    }

    /**
     * Get transaction from database by reference.
     *
     * @param string $reference Transaction reference.
     * @return \stdClass|false Transaction record or false.
     */
    public function get_local_transaction_by_reference(string $reference) {
        global $DB;
        return $DB->get_record('paygw_wompi_transactions', ['reference' => $reference]);
    }

    /**
     * Get transaction from database by transaction ID.
     *
     * @param string $transactionid Wompi transaction ID.
     * @return \stdClass|false Transaction record or false.
     */
    public function get_local_transaction(string $transactionid) {
        global $DB;
        return $DB->get_record('paygw_wompi_transactions', ['transactionid' => $transactionid]);
    }

    /**
     * Check if a transaction has already been processed and delivered.
     *
     * @param string $transactionid Transaction ID.
     * @return bool True if already delivered.
     */
    public function is_already_delivered(string $transactionid): bool {
        global $DB;

        $record = $DB->get_record('paygw_wompi_transactions', ['transactionid' => $transactionid]);
        return $record && $record->status === 'DELIVERED';
    }

    /**
     * Mark a transaction as delivered.
     *
     * @param string $transactionid Transaction ID.
     * @return bool Success.
     */
    public function mark_as_delivered(string $transactionid): bool {
        global $DB;

        $record = $DB->get_record('paygw_wompi_transactions', ['transactionid' => $transactionid]);
        if (!$record) {
            return false;
        }

        $record->status = 'DELIVERED';
        $record->timemodified = time();

        return $DB->update_record('paygw_wompi_transactions', $record);
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
     * Get the environment.
     *
     * @return string Environment.
     */
    public function get_environment(): string {
        return $this->environment;
    }

    /**
     * Send notification to user about payment status.
     *
     * @param int $userid User ID.
     * @param string $status Payment status (successful, failed, pending).
     * @param array $data Additional data for the message.
     */
    public function notify_user(int $userid, string $status, array $data = []): void {
        $eventdata = new \core\message\message();
        $eventdata->courseid = SITEID;
        $eventdata->component = 'paygw_wompi';
        $eventdata->name = 'payment_' . $status;
        $eventdata->notification = 1;
        $eventdata->userfrom = \core_user::get_noreply_user();
        $eventdata->userto = $userid;
        $eventdata->subject = get_string('payment:' . $status . ':subject', 'paygw_wompi', $data);
        $eventdata->fullmessage = get_string('payment:' . $status . ':message', 'paygw_wompi', $data);
        $eventdata->fullmessageformat = FORMAT_PLAIN;
        $eventdata->fullmessagehtml = '';
        $eventdata->smallmessage = '';

        if (isset($data['url'])) {
            $eventdata->contexturl = $data['url'];
        }

        message_send($eventdata);
    }
}
