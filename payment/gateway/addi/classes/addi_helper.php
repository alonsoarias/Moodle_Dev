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
 * Various helper methods for interacting with the Addi API.
 *
 * @package    paygw_addi
 * @copyright  2025 ingeweb.co <soporte@ingeweb.co>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace paygw_addi;

defined('MOODLE_INTERNAL') || die();

/**
 * The helper class for Addi payment gateway.
 *
 * @copyright  2025 ingeweb.co <soporte@ingeweb.co>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class addi_helper {

    /**
     * Application status constants from Addi.
     */
    const STATUS_APPROVED = 'APPROVED';
    const STATUS_REJECTED = 'REJECTED';
    const STATUS_PENDING = 'PENDING';
    const STATUS_CANCELLED = 'CANCELLED';
    const STATUS_EXPIRED = 'EXPIRED';
    const STATUS_DECLINED = 'DECLINED';

    /** @var string Client ID. */
    private $clientid;

    /** @var string Client Secret. */
    private $clientsecret;

    /** @var string Ally Slug (merchant identifier). */
    private $allyslug;

    /** @var string Environment (sandbox or production). */
    private $environment;

    /** @var string Cached access token. */
    private $accesstoken = null;

    /** @var int Token expiration timestamp. */
    private $tokenexpires = 0;

    /**
     * Constructor.
     *
     * @param string $clientid Client ID.
     * @param string $clientsecret Client Secret.
     * @param string $allyslug Ally Slug.
     * @param string $environment Environment (sandbox or production).
     */
    public function __construct(
        string $clientid,
        string $clientsecret,
        string $allyslug,
        string $environment = 'sandbox'
    ) {
        $this->clientid = $clientid;
        $this->clientsecret = $clientsecret;
        $this->allyslug = $allyslug;
        $this->environment = $environment;
    }

    /**
     * Get the Auth URL.
     *
     * @return string
     */
    public function get_auth_url(): string {
        return gateway::get_auth_url($this->environment);
    }

    /**
     * Get the API URL.
     *
     * @return string
     */
    public function get_api_url(): string {
        return gateway::get_api_url($this->environment);
    }

    /**
     * Get OAuth access token.
     *
     * @return string|null Access token or null on failure.
     */
    public function get_access_token(): ?string {
        // Return cached token if still valid.
        if ($this->accesstoken && $this->tokenexpires > time()) {
            return $this->accesstoken;
        }

        $authurl = $this->get_auth_url();

        $postdata = [
            'grant_type' => 'client_credentials',
            'client_id' => $this->clientid,
            'client_secret' => $this->clientsecret,
            'audience' => $this->get_api_url(),
        ];

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $authurl,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query($postdata),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/x-www-form-urlencoded',
            ],
        ]);

        $response = curl_exec($ch);
        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            debugging('Addi Auth Error: ' . $error, DEBUG_DEVELOPER);
            return null;
        }

        $decoded = json_decode($response, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            debugging('Addi Auth JSON Error: ' . json_last_error_msg(), DEBUG_DEVELOPER);
            return null;
        }

        if ($httpcode !== 200 || !isset($decoded['access_token'])) {
            debugging('Addi Auth HTTP Error: ' . $httpcode . ' - ' . $response, DEBUG_DEVELOPER);
            return null;
        }

        $this->accesstoken = $decoded['access_token'];
        $this->tokenexpires = time() + ($decoded['expires_in'] ?? 3600) - 60; // Subtract 60s buffer.

        return $this->accesstoken;
    }

    /**
     * Create an online application (payment request) with Addi.
     *
     * @param array $orderdata Order data.
     * @return array|null Response data or null on failure.
     */
    public function create_application(array $orderdata): ?array {
        $token = $this->get_access_token();
        if (!$token) {
            return null;
        }

        $apiurl = $this->get_api_url() . '/v1/online-applications';

        $payload = [
            'orderId' => $orderdata['order_id'],
            'totalAmount' => (float)$orderdata['amount'],
            'currency' => $orderdata['currency'] ?? 'COP',
            'allySlug' => $this->allyslug,
            'redirectionUrls' => [
                'successUrl' => $orderdata['success_url'],
                'failureUrl' => $orderdata['failure_url'],
                'pendingUrl' => $orderdata['pending_url'] ?? $orderdata['success_url'],
            ],
            'customer' => [
                'email' => $orderdata['email'],
                'firstName' => $orderdata['first_name'],
                'lastName' => $orderdata['last_name'],
                'cellphone' => $orderdata['phone'] ?? '',
            ],
            'items' => $orderdata['items'] ?? [[
                'name' => $orderdata['description'] ?? 'Course enrollment',
                'quantity' => 1,
                'unitPrice' => (float)$orderdata['amount'],
                'sku' => $orderdata['item_id'] ?? 'course',
            ]],
        ];

        // Add optional billing address if provided.
        if (!empty($orderdata['address'])) {
            $payload['billingAddress'] = [
                'address' => $orderdata['address'],
                'city' => $orderdata['city'] ?? '',
                'country' => 'CO',
            ];
        }

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $apiurl,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($payload),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $token,
            ],
        ]);

        $response = curl_exec($ch);
        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            debugging('Addi API Error: ' . $error, DEBUG_DEVELOPER);
            return null;
        }

        $decoded = json_decode($response, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            debugging('Addi API JSON Error: ' . json_last_error_msg(), DEBUG_DEVELOPER);
            return null;
        }

        if ($httpcode >= 400) {
            debugging('Addi API HTTP Error: ' . $httpcode . ' - ' . $response, DEBUG_DEVELOPER);
            return null;
        }

        return $decoded;
    }

    /**
     * Get application status from Addi.
     *
     * @param string $applicationid Application ID.
     * @return array|null Application data or null on failure.
     */
    public function get_application(string $applicationid): ?array {
        $token = $this->get_access_token();
        if (!$token) {
            return null;
        }

        $apiurl = $this->get_api_url() . '/v1/online-applications/' . urlencode($applicationid);

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $apiurl,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $token,
            ],
        ]);

        $response = curl_exec($ch);
        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            debugging('Addi API Error: ' . $error, DEBUG_DEVELOPER);
            return null;
        }

        $decoded = json_decode($response, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            debugging('Addi API JSON Error: ' . json_last_error_msg(), DEBUG_DEVELOPER);
            return null;
        }

        if ($httpcode >= 400) {
            debugging('Addi API HTTP Error: ' . $httpcode . ' - ' . $response, DEBUG_DEVELOPER);
            return null;
        }

        return $decoded;
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
        return sprintf('ADDI_%d_%s_%s_%d_%s',
            $userid,
            substr($component, 0, 8),
            substr($paymentarea, 0, 8),
            $itemid,
            substr(uniqid(), -6)
        );
    }

    /**
     * Map Addi status to internal status.
     *
     * @param string $addistatus Addi status.
     * @return string Internal status.
     */
    public function map_addi_status(string $addistatus): string {
        return match(strtoupper($addistatus)) {
            self::STATUS_APPROVED => 'APPROVED',
            self::STATUS_REJECTED, self::STATUS_DECLINED => 'REJECTED',
            self::STATUS_PENDING => 'PENDING',
            self::STATUS_CANCELLED => 'CANCELLED',
            self::STATUS_EXPIRED => 'EXPIRED',
            default => 'UNKNOWN',
        };
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
        $record->applicationid = $data['applicationid'] ?? '';
        $record->amount = $data['amount'];
        $record->currency = $data['currency'];
        $record->status = $data['status'];
        $record->response = isset($data['response']) ? json_encode($data['response']) : '';
        $record->timecreated = time();
        $record->timemodified = time();

        return $DB->insert_record('paygw_addi_transactions', $record);
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

        return $DB->update_record('paygw_addi_transactions', $record);
    }

    /**
     * Get transaction by reference.
     *
     * @param string $reference Transaction reference.
     * @return object|null Transaction record or null.
     */
    public function get_transaction_by_reference(string $reference): ?object {
        global $DB;
        return $DB->get_record('paygw_addi_transactions', ['reference' => $reference]) ?: null;
    }

    /**
     * Get transaction by application ID.
     *
     * @param string $applicationid Addi application ID.
     * @return object|null Transaction record or null.
     */
    public function get_transaction_by_applicationid(string $applicationid): ?object {
        global $DB;
        return $DB->get_record('paygw_addi_transactions', ['applicationid' => $applicationid]) ?: null;
    }

    /**
     * Check if amount is within Addi limits.
     *
     * @param float $amount Amount to check.
     * @param object $config Plugin configuration.
     * @return bool True if within limits.
     */
    public function is_amount_valid(float $amount, object $config): bool {
        $min = $config->minamount ?? 50000;
        $max = $config->maxamount ?? 5000000;

        return $amount >= $min && $amount <= $max;
    }

    /**
     * Check if in sandbox mode.
     *
     * @return bool True if sandbox mode.
     */
    public function is_sandbox(): bool {
        return $this->environment === 'sandbox';
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

        $subject = $config->$subjectfield ?? get_string('email_' . $type . '_subject_default', 'paygw_addi');
        $body = $config->$bodyfield ?? get_string('email_' . $type . '_body_default', 'paygw_addi');

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
        $eventdata->component = 'paygw_addi';
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
