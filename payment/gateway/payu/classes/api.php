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
 * PayU API client implementation with COMPLETE functionality.
 *
 * @package    paygw_payu
 * @copyright  2024 Orion Cloud Consulting SAS
 * @author     Alonso Arias <soporte@orioncloud.com.co>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace paygw_payu;

defined('MOODLE_INTERNAL') || die();

/**
 * PayU API client class with COMPLETE implementation.
 */
class api {
    
    /** @var string PayU API endpoint for production */
    const ENDPOINT_PRODUCTION = 'https://api.payulatam.com/payments-api/4.0/service.cgi';
    
    /** @var string PayU API endpoint for sandbox */
    const ENDPOINT_SANDBOX = 'https://sandbox.api.payulatam.com/payments-api/4.0/service.cgi';
    
    /** @var string PayU Reports API endpoint for production */
    const REPORTS_PRODUCTION = 'https://api.payulatam.com/reports-api/4.0/service.cgi';
    
    /** @var string PayU Reports API endpoint for sandbox */
    const REPORTS_SANDBOX = 'https://sandbox.api.payulatam.com/reports-api/4.0/service.cgi';
    
    /** @var string PayU Tokenization API endpoint for production */
    const TOKEN_PRODUCTION = 'https://api.payulatam.com/payments-api/4.0/service.cgi';
    
    /** @var string PayU Tokenization API endpoint for sandbox */
    const TOKEN_SANDBOX = 'https://sandbox.api.payulatam.com/payments-api/4.0/service.cgi';
    
    /** @var string PayU Airlines API endpoint for production */
    const AIRLINES_PRODUCTION = 'https://api.payulatam.com/payments-api/rest/v4.3/payments/airline';
    
    /** @var string PayU Airlines API endpoint for sandbox */
    const AIRLINES_SANDBOX = 'https://sandbox.api.payulatam.com/payments-api/rest/v4.3/payments/airline';

    /** @var \stdClass Gateway configuration */
    protected $config;
    
    /** @var string Current endpoint based on mode */
    protected $endpoint;
    
    /** @var string Reports endpoint */
    protected $reportsendpoint;
    
    /** @var string Tokenization endpoint */
    protected $tokenendpoint;
    
    /** @var string Airlines endpoint */
    protected $airlinesendpoint;
    
    /**
     * Constructor.
     *
     * @param \stdClass $config Gateway configuration
     */
    public function __construct(\stdClass $config) {
        $this->config = $config;
        $this->endpoint = !empty($config->testmode) ? self::ENDPOINT_SANDBOX : self::ENDPOINT_PRODUCTION;
        $this->reportsendpoint = !empty($config->testmode) ? self::REPORTS_SANDBOX : self::REPORTS_PRODUCTION;
        $this->tokenendpoint = !empty($config->testmode) ? self::TOKEN_SANDBOX : self::TOKEN_PRODUCTION;
        $this->airlinesendpoint = !empty($config->testmode) ? self::AIRLINES_SANDBOX : self::AIRLINES_PRODUCTION;
        
        // Auto-apply test credentials if in test mode and not configured
        if (!empty($config->testmode)) {
            $this->apply_test_credentials();
        }
    }
    
    /**
     * Apply test credentials automatically when in test mode.
     * IMPLEMENTACIÓN COMPLETA del modo de prueba automático.
     */
    protected function apply_test_credentials() {
        // Official test credentials from PayU documentation
        if (empty($this->config->merchantid) || $this->config->merchantid == '') {
            $this->config->merchantid = '508029';
        }
        if (empty($this->config->payuaccountid) || $this->config->payuaccountid == '') {
            $this->config->payuaccountid = '512321'; // Colombia test account
        }
        if (empty($this->config->apilogin) || $this->config->apilogin == '') {
            $this->config->apilogin = 'pRRXKOl8ikMmt9u';
        }
        if (empty($this->config->apikey) || $this->config->apikey == '') {
            $this->config->apikey = '4Vj8eK4rloUd272L48hsrarnUA';
        }
    }

    /**
     * Test connectivity with PayU API using PING command.
     *
     * @return bool True if connection successful
     * @throws \moodle_exception If connection fails
     */
    public function ping(): bool {
        $request = [
            'test' => !empty($this->config->testmode),
            'language' => 'es',
            'command' => 'PING',
            'merchant' => [
                'apiLogin' => $this->config->apilogin,
                'apiKey' => $this->config->apikey,
            ],
        ];
        
        $response = $this->send_request($request);
        
        if (!$response || $response->code !== 'SUCCESS') {
            throw new \moodle_exception('errorconnection', 'paygw_payu');
        }
        
        return true;
    }

    /**
     * Get available payment methods for Colombia.
     * IMPLEMENTACIÓN COMPLETA según documentación.
     *
     * @return array List of payment methods
     * @throws \moodle_exception If request fails
     */
    public function get_payment_methods(): array {
        $request = [
            'test' => !empty($this->config->testmode),
            'language' => 'es',
            'command' => 'GET_PAYMENT_METHODS',
            'merchant' => [
                'apiLogin' => $this->config->apilogin,
                'apiKey' => $this->config->apikey,
            ],
        ];
        
        $response = $this->send_request($request);
        
        if (!$response || $response->code !== 'SUCCESS') {
            throw new \moodle_exception('errorgetmethods', 'paygw_payu', '', 
                $response->error ?? 'Unknown error');
        }
        
        $methods = [];
        if (!empty($response->paymentMethods)) {
            foreach ($response->paymentMethods as $method) {
                if ($method->country === 'CO' && $method->enabled) {
                    $methods[$method->id] = $method->description;
                }
            }
        }
        
        return $methods;
    }

    /**
     * Get list of banks for PSE payments.
     * IMPLEMENTACIÓN COMPLETA con cache.
     *
     * @return array List of banks [code => name]
     * @throws \moodle_exception If request fails
     */
    public function get_pse_banks(): array {
        global $CFG;
        require_once($CFG->libdir . '/filelib.php');
        
        // Check cache first
        $cache = \cache::make('paygw_payu', 'psebanks');
        $banks = $cache->get('banks');
        
        if ($banks !== false && !empty($this->config->enablecache)) {
            return $banks;
        }
        
        $request = [
            'test' => !empty($this->config->testmode),
            'language' => 'es',
            'command' => 'GET_BANKS_LIST',
            'merchant' => [
                'apiLogin' => $this->config->apilogin,
                'apiKey' => $this->config->apikey,
            ],
            'bankListInformation' => [
                'paymentMethod' => 'PSE',
                'paymentCountry' => 'CO',
            ],
        ];
        
        $response = $this->send_request($request);
        
        if (!$response || $response->code !== 'SUCCESS') {
            throw new \moodle_exception('errorgetbanks', 'paygw_payu', '', 
                $response->error ?? 'Unknown error');
        }
        
        $banks = [];
        if (!empty($response->banks)) {
            foreach ($response->banks as $bank) {
                $banks[$bank->pseCode] = $bank->description;
            }
        }
        
        // Cache the result
        if (!empty($this->config->enablecache)) {
            $cache->set('banks', $banks);
        }
        
        return $banks;
    }

    /**
     * Process payment transaction.
     * IMPLEMENTACIÓN COMPLETA de todos los métodos de pago.
     *
     * @param int $paymentid Payment ID
     * @param float $amount Amount to charge
     * @param string $currency Currency code
     * @param \stdClass $data Payment data
     * @return \stdClass Transaction response
     * @throws \moodle_exception If payment fails
     */
    public function process_payment(int $paymentid, float $amount, string $currency, \stdClass $data): \stdClass {
        global $USER, $CFG;
        
        // Apply test data if in test mode
        if (!empty($this->config->testmode)) {
            $data = $this->auto_fill_test_data($data);
        }
        
        // Build payment request
        $request = $this->build_payment_request($paymentid, $amount, $currency, $data);
        
        // Send request
        $response = $this->send_request($request);
        
        if (!$response || $response->code !== 'SUCCESS') {
            throw new \moodle_exception('errortransaction', 'paygw_payu', '', 
                $response->error ?? 'Transaction failed');
        }
        
        return $response->transactionResponse;
    }

    /**
     * Build payment request array.
     * IMPLEMENTACIÓN COMPLETA según documentación oficial.
     *
     * @param int $paymentid Payment ID
     * @param float $amount Amount
     * @param string $currency Currency
     * @param \stdClass $data Payment data
     * @return array Request array
     */
    protected function build_payment_request(int $paymentid, float $amount, string $currency, \stdClass $data): array {
        global $USER, $CFG;
        
        // Generate reference code
        $referencecode = 'MOODLE-' . $paymentid . '-' . time();
        
        // Generate signature
        $signature = $this->generate_signature($referencecode, $amount, $currency);
        
        // Base request structure
        $request = [
            'test' => !empty($this->config->testmode),
            'language' => 'es',
            'command' => 'SUBMIT_TRANSACTION',
            'merchant' => [
                'apiLogin' => $this->config->apilogin,
                'apiKey' => $this->config->apikey,
            ],
            'transaction' => [
                'order' => [
                    'accountId' => $this->config->payuaccountid,
                    'referenceCode' => $referencecode,
                    'description' => $data->description ?? 'Moodle Payment',
                    'language' => 'es',
                    'signature' => $signature,
                    'notifyUrl' => $CFG->wwwroot . '/payment/gateway/payu/callback.php',
                    'additionalValues' => [
                        'TX_VALUE' => [
                            'value' => $amount,
                            'currency' => $currency,
                        ],
                        'TX_TAX' => [
                            'value' => 0,
                            'currency' => $currency,
                        ],
                        'TX_TAX_RETURN_BASE' => [
                            'value' => 0,
                            'currency' => $currency,
                        ],
                    ],
                    'buyer' => [
                        'merchantBuyerId' => (string)$USER->id,
                        'fullName' => $data->cardholder ?? fullname($USER),
                        'emailAddress' => $data->email ?? $USER->email,
                        'contactPhone' => $this->validate_phone($data->phone ?? ''),
                        'dniNumber' => $this->validate_document($data->documentnumber ?? ''),
                        'shippingAddress' => $this->build_address($data),
                    ],
                ],
                'payer' => [
                    'merchantPayerId' => (string)$USER->id,
                    'fullName' => $data->cardholder ?? fullname($USER),
                    'emailAddress' => $data->email ?? $USER->email,
                    'contactPhone' => $this->validate_phone($data->phone ?? ''),
                    'dniNumber' => $this->validate_document($data->documentnumber ?? ''),
                    'billingAddress' => $this->build_address($data),
                ],
                'type' => 'AUTHORIZATION_AND_CAPTURE',
                'paymentCountry' => 'CO',
                'deviceSessionId' => $this->generate_device_session_id(),
                'ipAddress' => $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1',
                'cookie' => session_id(),
                'userAgent' => $_SERVER['HTTP_USER_AGENT'] ?? 'Moodle PayU Gateway',
            ],
        ];
        
        // Add payment method specific fields
        $request = $this->add_payment_method_fields($request, $data);
        
        // Add airline data if present
        if (!empty($data->airline_code)) {
            $request = $this->add_airline_data($request, $data);
        }
        
        return $request;
    }

    /**
     * Add payment method specific fields to request.
     * IMPLEMENTACIÓN COMPLETA de todos los métodos.
     *
     * @param array $request Base request
     * @param \stdClass $data Payment data
     * @return array Modified request
     */
    protected function add_payment_method_fields(array $request, \stdClass $data): array {
        switch ($data->paymentmethod) {
            case 'creditcard':
                $request['transaction']['creditCard'] = [
                    'number' => str_replace(' ', '', $data->cardnumber ?? ''),
                    'securityCode' => $data->cvv ?? '',
                    'expirationDate' => sprintf('%s/%s', 
                        $data->expyear ?? date('Y'), 
                        str_pad($data->expmonth ?? '12', 2, '0', STR_PAD_LEFT)),
                    'name' => $data->cardholder ?? '',
                ];
                $request['transaction']['paymentMethod'] = strtoupper($data->cardnetwork ?? 'VISA');
                
                // Add installments if specified
                if (!empty($data->installments) && $data->installments > 1) {
                    $request['transaction']['extraParameters'] = [
                        'INSTALLMENTS_NUMBER' => (int)$data->installments,
                    ];
                }
                break;
                
            case 'pse':
                $request['transaction']['paymentMethod'] = 'PSE';
                $request['transaction']['extraParameters'] = [
                    'FINANCIAL_INSTITUTION_CODE' => $data->psebank ?? '',
                    'USER_TYPE' => $data->pseusertype ?? 'N', // N=Natural, J=Juridical
                    'PSE_REFERENCE1' => $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1',
                    'PSE_REFERENCE2' => $data->psereference2 ?? 'CC',
                    'PSE_REFERENCE3' => $data->documentnumber ?? '',
                ];
                $request['transaction']['type'] = 'AUTHORIZATION';
                break;
                
            case 'nequi':
                $request['transaction']['paymentMethod'] = 'NEQUI';
                $request['transaction']['extraParameters'] = [
                    'NEQUI_PUSH_NOTIFICATION_URL' => $this->validate_phone($data->phone ?? ''),
                ];
                break;
                
            case 'bancolombia':
                $request['transaction']['paymentMethod'] = 'BANCOLOMBIA_TRANSFER';
                $request['transaction']['extraParameters'] = [
                    'BANCOLOMBIA_TRANSFER_TYPE' => 'A', // A=Authorization
                ];
                break;
                
            case 'googlepay':
                $request['transaction']['paymentMethod'] = 'GOOGLEPAY';
                $request['transaction']['googlePayMerchantId'] = $this->config->merchantid;
                $request['transaction']['digitalWallet'] = [
                    'message' => $data->gp_token ?? '',
                    'type' => 'GOOGLE_PAY',
                ];
                $request['transaction']['paymentMethod'] = strtoupper($data->gp_network ?? 'VISA');
                break;
                
            case 'cash':
                $cashmethod = $data->cashmethod ?? 'EFECTY';
                $request['transaction']['paymentMethod'] = $cashmethod;
                $request['transaction']['type'] = 'AUTHORIZATION';
                $request['transaction']['expirationDate'] = date('c', strtotime('+7 days'));
                break;
                
            case 'baloto':
                $request['transaction']['paymentMethod'] = 'BALOTO';
                $request['transaction']['type'] = 'AUTHORIZATION';
                $request['transaction']['expirationDate'] = date('c', strtotime('+7 days'));
                break;
                
            case 'bank_referenced':
                $request['transaction']['paymentMethod'] = 'BANK_REFERENCED';
                $request['transaction']['type'] = 'AUTHORIZATION';
                break;
        }
        
        return $request;
    }

    /**
     * Add airline transaction data.
     * NUEVA IMPLEMENTACIÓN para aerolíneas.
     *
     * @param array $request Base request
     * @param \stdClass $data Payment data
     * @return array Modified request
     */
    protected function add_airline_data(array $request, \stdClass $data): array {
        $request['transaction']['extraParameters']['AIRLINE_CODE'] = $data->airline_code;
        
        if (!empty($data->passenger_name)) {
            $request['transaction']['extraParameters']['PASSENGER_NAME'] = $data->passenger_name;
        }
        
        if (!empty($data->passenger_id)) {
            $request['transaction']['extraParameters']['PASSENGER_ID'] = $data->passenger_id;
        }
        
        if (!empty($data->flight_reservation_code)) {
            $request['transaction']['extraParameters']['FLIGHT_RESERVATION_CODE'] = $data->flight_reservation_code;
        }
        
        return $request;
    }

    /**
     * Query transaction status.
     * IMPLEMENTACIÓN COMPLETA de consultas.
     *
     * @param string $transactionid Transaction ID
     * @return \stdClass Transaction details
     * @throws \moodle_exception If query fails
     */
    public function query_transaction(string $transactionid): \stdClass {
        $request = [
            'test' => !empty($this->config->testmode),
            'language' => 'es',
            'command' => 'ORDER_DETAIL_BY_REFERENCE_CODE',
            'merchant' => [
                'apiLogin' => $this->config->apilogin,
                'apiKey' => $this->config->apikey,
            ],
            'details' => [
                'referenceCode' => $transactionid,
            ],
        ];
        
        $response = $this->send_request($request, $this->reportsendpoint);
        
        if (!$response || $response->code !== 'SUCCESS') {
            throw new \moodle_exception('errorquerytransaction', 'paygw_payu', '', 
                $response->error ?? 'Query failed');
        }
        
        return $response->result ?? new \stdClass();
    }

    /**
     * Process refund for a transaction.
     * IMPLEMENTACIÓN COMPLETA de reembolsos.
     *
     * @param string $transactionid Original transaction ID
     * @param float $amount Amount to refund
     * @param string $reason Refund reason
     * @return \stdClass Refund response
     * @throws \moodle_exception If refund fails
     */
    public function process_refund(string $transactionid, float $amount, string $reason = ''): \stdClass {
        $request = [
            'test' => !empty($this->config->testmode),
            'language' => 'es',
            'command' => 'SUBMIT_TRANSACTION',
            'merchant' => [
                'apiLogin' => $this->config->apilogin,
                'apiKey' => $this->config->apikey,
            ],
            'transaction' => [
                'order' => [
                    'id' => $transactionid,
                ],
                'type' => 'REFUND',
                'reason' => $reason ?: 'Refund requested',
            ],
        ];
        
        $response = $this->send_request($request);
        
        if ($response->code !== 'SUCCESS') {
            throw new \moodle_exception('errorrefund', 'paygw_payu', '', 
                $response->error ?? 'Refund failed');
        }
        
        return $response->transactionResponse ?? $response;
    }

    /**
     * Create credit card token for recurring payments.
     * NUEVA IMPLEMENTACIÓN de tokenización.
     *
     * @param \stdClass $carddata Card data
     * @return string Token ID
     * @throws \moodle_exception If tokenization fails
     */
    public function create_token(\stdClass $carddata): string {
        global $USER;
        
        $request = [
            'test' => !empty($this->config->testmode),
            'language' => 'es',
            'command' => 'CREATE_TOKEN',
            'merchant' => [
                'apiLogin' => $this->config->apilogin,
                'apiKey' => $this->config->apikey,
            ],
            'creditCardToken' => [
                'payerId' => (string)$USER->id,
                'name' => $carddata->cardholder ?? fullname($USER),
                'identificationNumber' => $carddata->documentnumber ?? '',
                'paymentMethod' => strtoupper($carddata->cardnetwork ?? 'VISA'),
                'number' => str_replace(' ', '', $carddata->cardnumber ?? ''),
                'expirationDate' => sprintf('%s/%s',
                    $carddata->expyear ?? date('Y'),
                    str_pad($carddata->expmonth ?? '12', 2, '0', STR_PAD_LEFT)),
            ],
        ];
        
        $response = $this->send_request($request, $this->tokenendpoint);
        
        if (!$response || $response->code !== 'SUCCESS') {
            throw new \moodle_exception('errortokenization', 'paygw_payu', '',
                $response->error ?? 'Tokenization failed');
        }
        
        return $response->creditCardToken->creditCardTokenId ?? '';
    }

    /**
     * Process payment with token.
     * NUEVA IMPLEMENTACIÓN para pagos con token.
     *
     * @param string $token Token ID
     * @param int $paymentid Payment ID
     * @param float $amount Amount
     * @param string $currency Currency
     * @param \stdClass $data Additional data
     * @return \stdClass Transaction response
     * @throws \moodle_exception If payment fails
     */
    public function process_token_payment(string $token, int $paymentid, float $amount, 
                                         string $currency, \stdClass $data): \stdClass {
        global $USER, $CFG;
        
        $referencecode = 'MOODLE-TOKEN-' . $paymentid . '-' . time();
        $signature = $this->generate_signature($referencecode, $amount, $currency);
        
        $request = [
            'test' => !empty($this->config->testmode),
            'language' => 'es',
            'command' => 'SUBMIT_TRANSACTION',
            'merchant' => [
                'apiLogin' => $this->config->apilogin,
                'apiKey' => $this->config->apikey,
            ],
            'transaction' => [
                'order' => [
                    'accountId' => $this->config->payuaccountid,
                    'referenceCode' => $referencecode,
                    'description' => $data->description ?? 'Moodle Token Payment',
                    'language' => 'es',
                    'signature' => $signature,
                    'notifyUrl' => $CFG->wwwroot . '/payment/gateway/payu/callback.php',
                    'additionalValues' => [
                        'TX_VALUE' => [
                            'value' => $amount,
                            'currency' => $currency,
                        ],
                    ],
                    'buyer' => [
                        'merchantBuyerId' => (string)$USER->id,
                        'fullName' => fullname($USER),
                        'emailAddress' => $USER->email,
                    ],
                ],
                'creditCardTokenId' => $token,
                'type' => 'AUTHORIZATION_AND_CAPTURE',
                'paymentCountry' => 'CO',
                'deviceSessionId' => $this->generate_device_session_id(),
                'ipAddress' => $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1',
            ],
        ];
        
        // Add CVV if provided
        if (!empty($data->cvv)) {
            $request['transaction']['creditCard'] = [
                'securityCode' => $data->cvv,
            ];
        }
        
        $response = $this->send_request($request);
        
        if (!$response || $response->code !== 'SUCCESS') {
            throw new \moodle_exception('errortransaction', 'paygw_payu', '',
                $response->error ?? 'Token payment failed');
        }
        
        return $response->transactionResponse;
    }

    /**
     * Get list of airlines for airline transactions.
     * IMPLEMENTACIÓN COMPLETA de aerolíneas.
     *
     * @return array List of airlines
     * @throws \moodle_exception If request fails
     */
    public function get_airlines(): array {
        global $CFG;
        require_once($CFG->libdir . '/filelib.php');
        
        $curl = new \curl();
        $curl->setHeader(['Content-Type: application/json']);
        
        $response = $curl->get($this->airlinesendpoint);
        
        if ($curl->errno) {
            throw new \moodle_exception('errorgetairlines', 'paygw_payu', '', $curl->error);
        }
        
        $data = json_decode($response);
        if (!$data) {
            throw new \moodle_exception('errorgetairlines', 'paygw_payu', '', 'Invalid response');
        }
        
        $airlines = [];
        if (!empty($data->airlines)) {
            foreach ($data->airlines as $airline) {
                $airlines[$airline->code] = $airline->description;
            }
        }
        
        return $airlines;
    }

    /**
     * Send request to PayU API.
     * IMPLEMENTACIÓN MEJORADA con mejor manejo de errores.
     *
     * @param array $request Request data
     * @param string $endpoint Optional endpoint override
     * @return \stdClass Response object
     * @throws \moodle_exception If request fails
     */
    protected function send_request(array $request, string $endpoint = null): \stdClass {
        global $CFG;
        require_once($CFG->libdir . '/filelib.php');
        
        $endpoint = $endpoint ?: $this->endpoint;
        
        // Debug logging if enabled
        if (!empty($this->config->debugmode)) {
            debugging('PayU Request to ' . $endpoint . ': ' . json_encode($request), DEBUG_DEVELOPER);
        }
        
        $curl = new \curl();
        $curl->setHeader(['Content-Type: application/json', 'Accept: application/json']);
        
        $response = $curl->post($endpoint, json_encode($request));
        
        // Debug response if enabled
        if (!empty($this->config->debugmode)) {
            debugging('PayU Response: ' . $response, DEBUG_DEVELOPER);
        }
        
        if ($curl->errno) {
            throw new \moodle_exception('errorcurlconnection', 'paygw_payu', '', $curl->error);
        }
        
        $httpcode = $curl->get_info()['http_code'];
        if ($httpcode !== 200) {
            throw new \moodle_exception('errorhttpcode', 'paygw_payu', '', $httpcode);
        }
        
        $data = json_decode($response);
        if (!$data) {
            throw new \moodle_exception('errorjsonparse', 'paygw_payu');
        }
        
        return $data;
    }

    /**
     * Generate payment signature.
     * IMPLEMENTACIÓN según especificación PayU.
     *
     * @param string $referencecode Reference code
     * @param float $amount Amount
     * @param string $currency Currency
     * @return string MD5 signature
     */
    protected function generate_signature(string $referencecode, float $amount, string $currency): string {
        $formattedamount = number_format($amount, 1, '.', '');
        $signature = md5($this->config->apikey . '~' . $this->config->merchantid . '~' . 
                        $referencecode . '~' . $formattedamount . '~' . $currency);
        return $signature;
    }

    /**
     * Generate device session ID for fraud prevention.
     *
     * @return string Device session ID
     */
    protected function generate_device_session_id(): string {
        return md5(session_id() . microtime());
    }

    /**
     * Validate phone number format for Colombia.
     *
     * @param string $phone Phone number
     * @return string Validated phone
     */
    protected function validate_phone(string $phone): string {
        // Remove non-numeric characters
        $phone = preg_replace('/[^0-9]/', '', $phone);
        
        // Colombian phone validation
        if (strlen($phone) === 10 && substr($phone, 0, 1) === '3') {
            return $phone;
        }
        
        // Return empty if invalid
        return '';
    }

    /**
     * Validate document number.
     *
     * @param string $document Document number
     * @return string Validated document
     */
    protected function validate_document(string $document): string {
        // Remove special characters but keep alphanumeric
        $document = preg_replace('/[^A-Za-z0-9]/', '', $document);
        
        // Basic validation for Colombian documents
        if (strlen($document) >= 6 && strlen($document) <= 20) {
            return $document;
        }
        
        return '';
    }

    /**
     * Build address structure.
     *
     * @param \stdClass $data Form data
     * @return array Address structure
     */
    protected function build_address(\stdClass $data): array {
        return [
            'street1' => $data->address ?? 'N/A',
            'street2' => $data->address2 ?? '',
            'city' => $data->city ?? 'Bogotá',
            'state' => $data->state ?? 'Bogotá D.C.',
            'country' => 'CO',
            'postalCode' => $data->postalcode ?? '111111',
            'phone' => $this->validate_phone($data->phone ?? ''),
        ];
    }

    /**
     * Validate callback signature from PayU.
     * IMPLEMENTACIÓN CORRECTA de validación.
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
        
        // Format value with 1 decimal for signature
        $formattedvalue = number_format((float)$value, 1, '.', '');
        
        // Generate local signature
        $localsign = md5($this->config->apikey . '~' . $merchantid . '~' . 
                        $referencesale . '~' . $formattedvalue . '~' . 
                        $currency . '~' . $state);
        
        return strtoupper($localsign) === strtoupper($signature);
    }

    /**
     * Auto-fill test data when in test mode.
     * IMPLEMENTACIÓN COMPLETA del modo de prueba automático.
     *
     * @param \stdClass $data Original data
     * @return \stdClass Modified data with test values
     */
    protected function auto_fill_test_data(\stdClass $data): \stdClass {
        if (!empty($this->config->testmode)) {
            // Test credentials from documentation
            if (empty($data->cardholder)) {
                $data->cardholder = 'APPROVED';
            }
            
            // Test credit cards based on desired result
            if (!empty($data->paymentmethod) && $data->paymentmethod === 'creditcard') {
                if (empty($data->cardnumber)) {
                    // Visa test card that approves
                    $data->cardnumber = '4111111111111111';
                    $data->expmonth = '12';
                    $data->expyear = date('Y', strtotime('+5 years'));
                    $data->cvv = '123';
                    $data->cardnetwork = 'VISA';
                }
            }
            
            // Test phone for Nequi
            if (!empty($data->paymentmethod) && $data->paymentmethod === 'nequi') {
                if (empty($data->phone)) {
                    $data->phone = '3001234567';
                }
            }
            
            // Test document
            if (empty($data->documentnumber)) {
                $data->documentnumber = '1234567890';
            }
            
            // Test email
            if (empty($data->email)) {
                global $USER;
                $data->email = $USER->email ?: 'test@example.com';
            }
            
            // Test PSE bank
            if (!empty($data->paymentmethod) && $data->paymentmethod === 'pse') {
                if (empty($data->psebank)) {
                    $data->psebank = '1022'; // Test bank code
                }
            }
        }
        
        return $data;
    }
}