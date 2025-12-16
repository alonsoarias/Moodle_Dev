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
 * Various helper methods for interacting with the PayU Colombia API.
 *
 * @package    paygw_payu
 * @copyright  2025 ingeweb.co <soporte@ingeweb.co>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace paygw_payu;

use core_payment\helper;

defined('MOODLE_INTERNAL') || die();

/**
 * The helper class for PayU Colombia payment gateway.
 *
 * Implements WebCheckout integration with signature validation
 * according to PayU Latam documentation.
 *
 * @copyright  2025 ingeweb.co <soporte@ingeweb.co>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class payu_helper {

    /**
     * PayU WebCheckout URLs.
     */
    const CHECKOUT_URL_SANDBOX = 'https://sandbox.checkout.payulatam.com/ppp-web-gateway-payu/';
    const CHECKOUT_URL_PRODUCTION = 'https://checkout.payulatam.com/ppp-web-gateway-payu/';

    /**
     * PayU API URLs.
     */
    const API_URL_SANDBOX = 'https://sandbox.api.payulatam.com/payments-api/4.0/service.cgi';
    const API_URL_PRODUCTION = 'https://api.payulatam.com/payments-api/4.0/service.cgi';

    /**
     * Test credentials for Colombia sandbox environment.
     */
    const TEST_CREDENTIALS = [
        'merchantId' => '508029',
        'accountId' => '512321',
        'apiKey' => '4Vj8eK4rloUd272L48hsrarnUA',
        'apiLogin' => 'pRRXKOl8ikMmt9u',
        'publicKey' => 'PKaC6H4cEDJD919n705L544kSU',
    ];

    /**
     * Transaction states from PayU.
     */
    const STATE_APPROVED = 4;
    const STATE_EXPIRED = 5;
    const STATE_DECLINED = 6;
    const STATE_PENDING = 7;
    const STATE_ERROR = 104;

    /** @var string Merchant ID. */
    private $merchantid;

    /** @var string Account ID. */
    private $accountid;

    /** @var string API Key. */
    private $apikey;

    /** @var string API Login. */
    private $apilogin;

    /** @var string Environment (sandbox or production). */
    private $environment;

    /**
     * Constructor.
     *
     * @param string $merchantid Merchant ID.
     * @param string $accountid Account ID.
     * @param string $apikey API Key.
     * @param string $apilogin API Login.
     * @param string $environment Environment (sandbox or production).
     */
    public function __construct(
        string $merchantid,
        string $accountid,
        string $apikey,
        string $apilogin,
        string $environment = 'sandbox'
    ) {
        $this->merchantid = $merchantid;
        $this->accountid = $accountid;
        $this->apikey = $apikey;
        $this->apilogin = $apilogin;
        $this->environment = $environment;
    }

    /**
     * Create helper instance from gateway configuration.
     *
     * @param object $config Gateway configuration.
     * @return self
     */
    public static function from_config(object $config): self {
        if (($config->environment ?? 'sandbox') === 'sandbox') {
            return new self(
                self::TEST_CREDENTIALS['merchantId'],
                self::TEST_CREDENTIALS['accountId'],
                self::TEST_CREDENTIALS['apiKey'],
                self::TEST_CREDENTIALS['apiLogin'],
                'sandbox'
            );
        }

        return new self(
            $config->merchantid,
            $config->accountid,
            $config->apikey,
            $config->apilogin,
            'production'
        );
    }

    /**
     * Get the checkout URL based on environment.
     *
     * @return string Checkout URL.
     */
    public function get_checkout_url(): string {
        return $this->environment === 'production'
            ? self::CHECKOUT_URL_PRODUCTION
            : self::CHECKOUT_URL_SANDBOX;
    }

    /**
     * Get the API URL based on environment.
     *
     * @return string API URL.
     */
    public function get_api_url(): string {
        return $this->environment === 'production'
            ? self::API_URL_PRODUCTION
            : self::API_URL_SANDBOX;
    }

    /**
     * Generate payment form signature.
     *
     * Formula: MD5(apiKey~merchantId~referenceCode~amount~currency)
     *
     * @param string $referencecode Transaction reference.
     * @param float $amount Transaction amount.
     * @param string $currency Currency code.
     * @return string MD5 signature.
     */
    public function generate_signature(string $referencecode, float $amount, string $currency): string {
        $signaturestring = sprintf(
            '%s~%s~%s~%s~%s',
            $this->apikey,
            $this->merchantid,
            $referencecode,
            $amount,
            $currency
        );
        return md5($signaturestring);
    }

    /**
     * Generate confirmation/response signature for validation.
     *
     * Formula: MD5(apiKey~merchantId~referenceCode~value~currency~statePol)
     *
     * @param string $referencecode Transaction reference.
     * @param float $value Transaction value.
     * @param string $currency Currency code.
     * @param int $statepol Transaction state.
     * @return string MD5 signature.
     */
    public function generate_response_signature(
        string $referencecode,
        float $value,
        string $currency,
        int $statepol
    ): string {
        // Format value according to PayU rules.
        $formattedvalue = $this->format_value_for_signature($value);

        $signaturestring = sprintf(
            '%s~%s~%s~%s~%s~%s',
            $this->apikey,
            $this->merchantid,
            $referencecode,
            $formattedvalue,
            $currency,
            $statepol
        );
        return md5($signaturestring);
    }

    /**
     * Format value for signature according to PayU rules.
     *
     * If second decimal is 0: format with 1 decimal (150.00 -> 150.0)
     * If second decimal is not 0: format with 2 decimals (150.25 -> 150.25)
     *
     * @param float $value Value to format.
     * @return string Formatted value.
     */
    public function format_value_for_signature(float $value): string {
        $rounded = round($value, 2);
        $decimals = $rounded - floor($rounded);

        // Check if second decimal is zero.
        $seconddecimal = (int)(($decimals * 100) % 10);

        if ($seconddecimal == 0) {
            return number_format($rounded, 1, '.', '');
        }
        return number_format($rounded, 2, '.', '');
    }

    /**
     * Verify signature from PayU response.
     *
     * @param string $receivedSignature Signature received from PayU.
     * @param string $referencecode Reference code.
     * @param float $value Transaction value.
     * @param string $currency Currency code.
     * @param int $statepol Transaction state.
     * @return bool True if signature is valid.
     */
    public function verify_signature(
        string $receivedSignature,
        string $referencecode,
        float $value,
        string $currency,
        int $statepol
    ): bool {
        $calculated = $this->generate_response_signature($referencecode, $value, $currency, $statepol);
        return hash_equals(strtoupper($calculated), strtoupper($receivedSignature));
    }

    /**
     * Generate unique reference code for transaction.
     *
     * @param string $component Component name.
     * @param string $paymentarea Payment area.
     * @param int $itemid Item ID.
     * @param int $userid User ID.
     * @return string Unique reference code.
     */
    public function generate_reference(string $component, string $paymentarea, int $itemid, int $userid): string {
        return sprintf(
            'MOODLE_%s_%d_%d_%d',
            substr(str_replace('_', '', $component . $paymentarea), 0, 15),
            $itemid,
            $userid,
            time()
        );
    }

    /**
     * Build payment form parameters for WebCheckout.
     *
     * @param array $params Payment parameters.
     * @return array Form parameters.
     */
    public function build_form_params(array $params): array {
        $signature = $this->generate_signature(
            $params['referenceCode'],
            $params['amount'],
            $params['currency']
        );

        $formparams = [
            'merchantId' => $this->merchantid,
            'accountId' => $this->accountid,
            'description' => substr($params['description'] ?? '', 0, 255),
            'referenceCode' => $params['referenceCode'],
            'amount' => $params['amount'],
            'currency' => $params['currency'],
            'signature' => $signature,
            'test' => $this->environment === 'sandbox' ? 1 : 0,
            'buyerEmail' => $params['buyerEmail'] ?? '',
            'buyerFullName' => $params['buyerFullName'] ?? '',
            'telephone' => $params['telephone'] ?? '',
            'confirmationUrl' => $params['confirmationUrl'],
            'responseUrl' => $params['responseUrl'],
            'lng' => $params['language'] ?? 'es',
            // Colombia tax fields.
            'tax' => $params['tax'] ?? 0,
            'taxReturnBase' => $params['taxReturnBase'] ?? 0,
        ];

        // Add extra fields for tracking.
        if (!empty($params['extra1'])) {
            $formparams['extra1'] = $params['extra1'];
        }
        if (!empty($params['extra2'])) {
            $formparams['extra2'] = $params['extra2'];
        }
        if (!empty($params['extra3'])) {
            $formparams['extra3'] = $params['extra3'];
        }

        return $formparams;
    }

    /**
     * Check if transaction state is approved.
     *
     * @param int $state Transaction state.
     * @return bool True if approved.
     */
    public function is_approved(int $state): bool {
        return $state === self::STATE_APPROVED;
    }

    /**
     * Check if transaction state is pending.
     *
     * @param int $state Transaction state.
     * @return bool True if pending.
     */
    public function is_pending(int $state): bool {
        return $state === self::STATE_PENDING;
    }

    /**
     * Check if transaction state is declined or error.
     *
     * @param int $state Transaction state.
     * @return bool True if failed.
     */
    public function is_failed(int $state): bool {
        return in_array($state, [self::STATE_DECLINED, self::STATE_ERROR, self::STATE_EXPIRED]);
    }

    /**
     * Get human-readable state name.
     *
     * @param int $state Transaction state.
     * @return string State name.
     */
    public function get_state_name(int $state): string {
        $states = [
            self::STATE_APPROVED => 'APPROVED',
            self::STATE_EXPIRED => 'EXPIRED',
            self::STATE_DECLINED => 'DECLINED',
            self::STATE_PENDING => 'PENDING',
            self::STATE_ERROR => 'ERROR',
        ];
        return $states[$state] ?? 'UNKNOWN';
    }

    /**
     * Deliver the order to the user.
     *
     * @param string $component Component name.
     * @param string $paymentarea Payment area.
     * @param int $itemid Item ID.
     * @param int $userid User ID.
     * @param float $cost Payment cost.
     * @param string $currency Currency code.
     * @return int Payment ID.
     */
    public function deliver_order(
        string $component,
        string $paymentarea,
        int $itemid,
        int $userid,
        float $cost,
        string $currency
    ): int {
        $payable = helper::get_payable($component, $paymentarea, $itemid);

        $paymentid = helper::save_payment(
            $payable->get_account_id(),
            $component,
            $paymentarea,
            $itemid,
            $userid,
            $cost,
            $currency,
            'payu'
        );

        helper::deliver_order($component, $paymentarea, $itemid, $paymentid, $userid);

        return $paymentid;
    }

    /**
     * Save transaction to database.
     *
     * @param array $data Transaction data.
     * @return int Record ID.
     */
    public function save_transaction(array $data): int {
        global $DB;

        $record = new \stdClass();
        $record->userid = $data['userid'];
        $record->transactionid = $data['transactionid'] ?? '';
        $record->referencecode = $data['referencecode'];
        $record->orderid = $data['orderid'] ?? '';
        $record->component = $data['component'];
        $record->paymentarea = $data['paymentarea'];
        $record->itemid = $data['itemid'];
        $record->currency = $data['currency'];
        $record->amount = $data['amount'];
        $record->state = $data['state'] ?? 'PENDING';
        $record->paymentmethod = $data['paymentmethod'] ?? '';
        $record->timecreated = time();
        $record->timemodified = time();

        // Check if transaction already exists by reference code.
        $existing = $DB->get_record('paygw_payu_transactions', ['referencecode' => $data['referencecode']]);
        if ($existing) {
            $record->id = $existing->id;
            $DB->update_record('paygw_payu_transactions', $record);
            return $existing->id;
        }

        return $DB->insert_record('paygw_payu_transactions', $record);
    }

    /**
     * Update transaction status.
     *
     * @param string $referencecode Reference code.
     * @param string $state New state.
     * @param string|null $transactionid PayU transaction ID.
     * @param string|null $orderid PayU order ID.
     * @return bool Success.
     */
    public function update_transaction(
        string $referencecode,
        string $state,
        ?string $transactionid = null,
        ?string $orderid = null
    ): bool {
        global $DB;

        $record = $DB->get_record('paygw_payu_transactions', ['referencecode' => $referencecode]);
        if (!$record) {
            return false;
        }

        $record->state = $state;
        $record->timemodified = time();

        if ($transactionid !== null) {
            $record->transactionid = $transactionid;
        }
        if ($orderid !== null) {
            $record->orderid = $orderid;
        }

        return $DB->update_record('paygw_payu_transactions', $record);
    }

    /**
     * Get transaction by reference code.
     *
     * @param string $referencecode Reference code.
     * @return \stdClass|false Transaction record or false.
     */
    public function get_transaction_by_reference(string $referencecode) {
        global $DB;
        return $DB->get_record('paygw_payu_transactions', ['referencecode' => $referencecode]);
    }

    /**
     * Check if transaction was already delivered.
     *
     * @param string $referencecode Reference code.
     * @return bool True if delivered.
     */
    public function is_already_delivered(string $referencecode): bool {
        $record = $this->get_transaction_by_reference($referencecode);
        return $record && $record->state === 'DELIVERED';
    }

    /**
     * Mark transaction as delivered.
     *
     * @param string $referencecode Reference code.
     * @return bool Success.
     */
    public function mark_as_delivered(string $referencecode): bool {
        return $this->update_transaction($referencecode, 'DELIVERED');
    }

    /**
     * Get merchant ID.
     *
     * @return string Merchant ID.
     */
    public function get_merchant_id(): string {
        return $this->merchantid;
    }

    /**
     * Get account ID.
     *
     * @return string Account ID.
     */
    public function get_account_id(): string {
        return $this->accountid;
    }

    /**
     * Get environment.
     *
     * @return string Environment.
     */
    public function get_environment(): string {
        return $this->environment;
    }

    /**
     * Is sandbox environment.
     *
     * @return bool True if sandbox.
     */
    public function is_sandbox(): bool {
        return $this->environment === 'sandbox';
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
        $eventdata->component = 'paygw_payu';
        $eventdata->name = 'payment_' . $status;
        $eventdata->notification = 1;
        $eventdata->userfrom = \core_user::get_noreply_user();
        $eventdata->userto = $userid;
        $eventdata->subject = get_string('payment:' . $status . ':subject', 'paygw_payu', $data);
        $eventdata->fullmessage = get_string('payment:' . $status . ':message', 'paygw_payu', $data);
        $eventdata->fullmessageformat = FORMAT_PLAIN;
        $eventdata->fullmessagehtml = '';
        $eventdata->smallmessage = '';

        if (isset($data['url'])) {
            $eventdata->contexturl = $data['url'];
        }

        message_send($eventdata);
    }
}
