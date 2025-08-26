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
 * PayU API client for payment processing.
 *
 * @package    paygw_payu
 * @copyright  2024 Orion Cloud Consulting SAS
 * @author     Alonso Arias <soporte@orioncloud.com.co>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace paygw_payu;

defined('MOODLE_INTERNAL') || die();

/**
 * PayU API client class.
 *
 * Handles all communication with PayU payment gateway API for Colombia.
 *
 * @copyright  2024 Orion Cloud Consulting SAS
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class api {
    
    /** @var string API endpoint for sandbox environment */
    const ENDPOINT_SANDBOX = 'https://sandbox.api.payulatam.com/payments-api/4.0/service.cgi';
    
    /** @var string API endpoint for production environment */
    const ENDPOINT_PRODUCTION = 'https://api.payulatam.com/payments-api/4.0/service.cgi';
    
    /** @var \stdClass Gateway configuration */
    protected $config;
    
    /** @var string API endpoint based on test mode */
    protected $endpoint;
    
    /**
     * Constructor.
     *
     * @param \stdClass $config Gateway configuration
     */
    public function __construct(\stdClass $config) {
        $this->config = $config;
        $this->endpoint = !empty($config->testmode) ? self::ENDPOINT_SANDBOX : self::ENDPOINT_PRODUCTION;
    }

    /**
     * Get list of PSE banks.
     *
     * @return array Array of banks with code and description
     * @throws \moodle_exception
     */
    public function get_pse_banks(): array {
        global $CFG;
        
        // Check cache first.
        $cache = \cache::make('paygw_payu', 'psebanks');
        $cachekey = 'banks_' . md5($this->config->merchantid);
        
        if (!empty($this->config->enablecache)) {
            $banks = $cache->get($cachekey);
            if ($banks !== false) {
                return $banks;
            }
        }
        
        $request = [
            'language' => 'es',
            'command' => 'GET_BANKS_LIST',
            'merchant' => [
                'apiLogin' => $this->config->apilogin,
                'apiKey' => $this->config->apikey,
            ],
            'test' => !empty($this->config->testmode),
            'bankListInformation' => [
                'paymentMethod' => 'PSE',
                'paymentCountry' => 'CO',
            ],
        ];
        
        $response = $this->send_request($request);
        
        if ($response->code !== 'SUCCESS' || empty($response->banks)) {
            throw new \moodle_exception('errorgetbanks', 'paygw_payu', '', 
                $response->error ?? 'Unknown error');
        }
        
        $banks = [];
        foreach ($response->banks as $bank) {
            if (!empty($bank->pseCode) && $bank->pseCode !== '0') {
                $banks[$bank->pseCode] = $bank->description;
            }
        }
        
        // Cache for 24 hours.
        if (!empty($this->config->enablecache)) {
            $cache->set($cachekey, $banks, 86400);
        }
        
        return $banks;
    }

    /**
     * Submit a transaction to PayU.
     *
     * @param int $paymentid Local payment record ID
     * @param float $amount Amount to charge
     * @param string $currency Currency code
     * @param \stdClass $data Form data from checkout
     * @return \stdClass Transaction response
     * @throws \moodle_exception
     */
    public function submit_transaction(int $paymentid, float $amount, string $currency, \stdClass $data): \stdClass {
        global $USER, $CFG;
        
        // Build buyer/payer information.
        $buyer = [
            'fullName' => $data->cardholder ?? fullname($USER),
            'emailAddress' => $data->email ?? $USER->email,
            'contactPhone' => $data->phone ?? '',
            'dniNumber' => $data->documentnumber ?? '',
            'shippingAddress' => $this->build_address($data),
        ];
        
        $payer = [
            'fullName' => $data->cardholder ?? fullname($USER),
            'emailAddress' => $data->email ?? $USER->email,
            'contactPhone' => $data->phone ?? '',
            'dniNumber' => $data->documentnumber ?? '',
            'billingAddress' => $this->build_address($data),
        ];
        
        // Generate signature.
        $signature = $this->generate_signature($paymentid, $amount, $currency);
        
        // Build base request.
        $request = [
            'language' => 'es',
            'command' => 'SUBMIT_TRANSACTION',
            'merchant' => [
                'apiLogin' => $this->config->apilogin,
                'apiKey' => $this->config->apikey,
            ],
            'test' => !empty($this->config->testmode),
            'transaction' => [
                'order' => [
                    'accountId' => $this->config->accountid,
                    'referenceCode' => (string)$paymentid,
                    'description' => substr($data->description ?? '', 0, 255),
                    'language' => 'es',
                    'signature' => $signature,
                    'notifyUrl' => $CFG->wwwroot . '/payment/gateway/payu/callback.php',
                    'additionalValues' => [
                        'TX_VALUE' => [
                            'value' => $amount,
                            'currency' => $currency,
                        ],
                    ],
                    'buyer' => $buyer,
                    'shippingAddress' => $this->build_address($data),
                ],
                'type' => 'AUTHORIZATION_AND_CAPTURE',
                'paymentCountry' => 'CO',
                'ipAddress' => $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1',
                'deviceSessionId' => $this->get_device_session_id(),
                'cookie' => session_id(),
                'userAgent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
                'payer' => $payer,
            ],
        ];
        
        // Add IVA if applicable.
        if (!empty($data->includeiva)) {
            $ivarate = 0.19; // 19% IVA in Colombia.
            $basevalue = $amount / (1 + $ivarate);
            $ivavalue = $amount - $basevalue;
            
            $request['transaction']['order']['additionalValues']['TX_TAX'] = [
                'value' => round($ivavalue, 2),
                'currency' => $currency,
            ];
            $request['transaction']['order']['additionalValues']['TX_TAX_RETURN_BASE'] = [
                'value' => round($basevalue, 2),
                'currency' => $currency,
            ];
        }
        
        // Add payment method specific data.
        $this->add_payment_method_data($request, $data);
        
        // Send the request.
        $response = $this->send_request($request);
        
        if ($response->code !== 'SUCCESS') {
            throw new \moodle_exception('errortransaction', 'paygw_payu', '', 
                $response->error ?? 'Unknown error');
        }
        
        // Store transaction in local database.
        $this->store_transaction($paymentid, $response->transactionResponse, $data->paymentmethod);
        
        return $response->transactionResponse;
    }

    /**
     * Query transaction status.
     *
     * @param string $transactionid PayU transaction ID
     * @return \stdClass Transaction details
     * @throws \moodle_exception
     */
    public function query_transaction(string $transactionid): \stdClass {
        $request = [
            'language' => 'es',
            'command' => 'ORDER_DETAIL_BY_REFERENCE_CODE',
            'merchant' => [
                'apiLogin' => $this->config->apilogin,
                'apiKey' => $this->config->apikey,
            ],
            'test' => !empty($this->config->testmode),
            'details' => [
                'referenceCode' => $transactionid,
            ],
        ];
        
        $response = $this->send_request($request);
        
        if ($response->code !== 'SUCCESS') {
            throw new \moodle_exception('errorquerytransaction', 'paygw_payu', '', 
                $response->error ?? 'Unknown error');
        }
        
        return $response->result;
    }

    /**
     * Get available payment methods.
     *
     * @return array Array of payment methods
     * @throws \moodle_exception
     */
    public function get_payment_methods(): array {
        $request = [
            'language' => 'es',
            'command' => 'GET_PAYMENT_METHODS',
            'merchant' => [
                'apiLogin' => $this->config->apilogin,
                'apiKey' => $this->config->apikey,
            ],
            'test' => !empty($this->config->testmode),
        ];
        
        $response = $this->send_request($request);
        
        if ($response->code !== 'SUCCESS') {
            throw new \moodle_exception('errorgetmethods', 'paygw_payu', '', 
                $response->error ?? 'Unknown error');
        }
        
        $methods = [];
        foreach ($response->paymentMethods as $method) {
            if ($method->country === 'CO' && $method->enabled) {
                $methods[$method->id] = $method->description;
            }
        }
        
        return $methods;
    }

    /**
     * Validate callback signature.
     *
     * @param array $data Callback data
     * @return bool True if signature is valid
     */
    public function validate_callback_signature(array $data): bool {
        $merchantid = $data['merchant_id'] ?? '';
        $referencesale = $data['reference_sale'] ?? '';
        $value = $data['value'] ?? '';
        $currency = $data['currency'] ?? '';
        $state = $data['state_pol'] ?? '';
        $signature = $data['sign'] ?? '';
        
        // Format value with 1 decimal for signature.
        $formattedvalue = number_format((float)$value, 1, '.', '');
        
        // Generate local signature.
        $localsign = md5($this->config->apikey . '~' . $merchantid . '~' . 
                        $referencesale . '~' . $formattedvalue . '~' . 
                        $currency . '~' . $state);
        
        return strtoupper($localsign) === strtoupper($signature);
    }

    /**
     * Send request to PayU API.
     *
     * @param array $data Request data
     * @return \stdClass Response object
     * @throws \moodle_exception
     */
    protected function send_request(array $data): \stdClass {
        $ch = curl_init($this->endpoint);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Accept: application/json',
        ]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        
        $response = curl_exec($ch);
        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($error) {
            throw new \moodle_exception('errorcurlconnection', 'paygw_payu', '', $error);
        }
        
        if ($httpcode !== 200) {
            throw new \moodle_exception('errorhttpcode', 'paygw_payu', '', $httpcode);
        }
        
        $result = json_decode($response);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \moodle_exception('errorjsonparse', 'paygw_payu');
        }
        
        // Log the transaction for debugging if in test mode.
        if (!empty($this->config->testmode)) {
            mtrace('PayU Request: ' . json_encode($data));
            mtrace('PayU Response: ' . $response);
        }
        
        return $result;
    }

    /**
     * Generate signature for transaction.
     *
     * @param int $referencecode Reference code (payment ID)
     * @param float $amount Amount
     * @param string $currency Currency code
     * @return string MD5 signature
     */
    protected function generate_signature(int $referencecode, float $amount, string $currency): string {
        $formattedamount = number_format($amount, 2, '.', '');
        return md5($this->config->apikey . '~' . $this->config->merchantid . '~' . 
                  $referencecode . '~' . $formattedamount . '~' . $currency);
    }

    /**
     * Get device session ID.
     *
     * @return string Device session ID
     */
    protected function get_device_session_id(): string {
        // Generate a unique device session ID based on user session and timestamp.
        return substr(md5(session_id() . microtime(true)), 0, 32);
    }

    /**
     * Build address array from form data.
     *
     * @param \stdClass $data Form data
     * @return array Address array
     */
    protected function build_address(\stdClass $data): array {
        global $USER;
        
        return [
            'street1' => $data->street1 ?? $USER->address ?? 'N/A',
            'street2' => $data->street2 ?? '',
            'city' => $data->city ?? $USER->city ?? 'Bogotá',
            'state' => $data->state ?? 'Bogotá D.C.',
            'country' => 'CO',
            'postalCode' => $data->postalcode ?? '000000',
            'phone' => $data->phone ?? '',
        ];
    }

    /**
     * Add payment method specific data to request.
     *
     * @param array $request Request array (passed by reference)
     * @param \stdClass $data Form data
     */
    protected function add_payment_method_data(array &$request, \stdClass $data): void {
        switch ($data->paymentmethod) {
            case 'creditcard':
                $request['transaction']['paymentMethod'] = strtoupper($data->cardnetwork);
                $request['transaction']['creditCard'] = [
                    'number' => $data->ccnumber,
                    'securityCode' => $data->cvv,
                    'expirationDate' => $data->ccexpyear . '/' . str_pad($data->ccexpmonth, 2, '0', STR_PAD_LEFT),
                    'name' => $data->cardholder,
                ];
                $request['transaction']['extraParameters'] = [
                    'INSTALLMENTS_NUMBER' => $data->installments ?? 1,
                ];
                break;
                
            case 'pse':
                $request['transaction']['paymentMethod'] = 'PSE';
                $request['transaction']['extraParameters'] = [
                    'RESPONSE_URL' => (new \moodle_url('/payment/gateway/payu/return.php', 
                        ['paymentid' => $data->paymentid]))->out(false),
                    'FINANCIAL_INSTITUTION_CODE' => $data->psebank,
                    'USER_TYPE' => $data->usertype,
                    'PSE_REFERENCE1' => $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1',
                    'PSE_REFERENCE2' => $data->documenttype,
                    'PSE_REFERENCE3' => $data->documentnumber,
                ];
                break;
                
            case 'nequi':
                $request['transaction']['paymentMethod'] = 'NEQUI';
                $request['transaction']['payer']['contactPhone'] = $this->format_phone_number($data->phone);
                break;
                
            case 'bancolombia':
                $request['transaction']['paymentMethod'] = 'BANCOLOMBIA_BUTTON';
                break;
                
            case 'googlepay':
                $request['transaction']['paymentMethod'] = strtoupper($data->gp_network);
                $request['transaction']['creditCard'] = [
                    'name' => $data->cardholder,
                ];
                $request['transaction']['digitalWallet'] = [
                    'type' => 'GOOGLE_PAY',
                    'message' => $data->gp_token,
                ];
                $request['transaction']['extraParameters'] = [
                    'INSTALLMENTS_NUMBER' => 1,
                ];
                break;
                
            case 'cash':
                $request['transaction']['paymentMethod'] = strtoupper($data->cashmethod);
                $request['transaction']['expirationDate'] = date('Y-m-d\TH:i:s', strtotime('+7 days'));
                break;
        }
    }

    /**
     * Format phone number for Nequi.
     *
     * @param string $phone Phone number
     * @return string Formatted phone number
     */
    protected function format_phone_number(string $phone): string {
        // Remove all non-digits.
        $phone = preg_replace('/[^0-9]/', '', $phone);
        
        // If starts with 57, separate country code.
        if (substr($phone, 0, 2) === '57') {
            return '57 ' . substr($phone, 2);
        }
        
        // If 10 digits, assume Colombian number without country code.
        if (strlen($phone) === 10) {
            return '57 ' . $phone;
        }
        
        return $phone;
    }

    /**
     * Store transaction in local database.
     *
     * @param int $paymentid Payment ID
     * @param \stdClass $response Transaction response
     * @param string $paymentmethod Payment method
     */
    protected function store_transaction(int $paymentid, \stdClass $response, string $paymentmethod): void {
        global $DB;
        
        $record = new \stdClass();
        $record->paymentid = $paymentid;
        $record->payu_order_id = $response->orderId ?? null;
        $record->payu_transaction_id = $response->transactionId ?? null;
        $record->state = $response->state ?? 'UNKNOWN';
        $record->payment_method = $paymentmethod;
        $record->response_code = $response->responseCode ?? null;
        $record->extra_parameters = json_encode($response->extraParameters ?? []);
        $record->timecreated = time();
        $record->timemodified = time();
        
        $DB->insert_record('paygw_payu', $record);
    }
}