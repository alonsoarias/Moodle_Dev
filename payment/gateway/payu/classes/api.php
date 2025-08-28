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
        $this->airlinesendpoint = !empty($config->testmode) ? self::AIRLINES_SANDBOX : self::AIRLINES_PRODUCTION;
    }

    /**
     * Test connectivity with PayU API using PING command.
     *
     * @return bool True if connection successful
     * @throws \moodle_exception
     */
    public function ping(): bool {
        $request = [
            'language' => 'es',
            'command' => 'PING',
            'merchant' => [
                'apiLogin' => $this->config->apilogin,
                'apiKey' => $this->config->apikey,
            ],
            'test' => !empty($this->config->testmode),
        ];
        
        $response = $this->send_request($request);
        
        return ($response->code === 'SUCCESS');
    }

    /**
     * Get available payment methods using GET_PAYMENT_METHODS command.
     * NUEVA IMPLEMENTACIÓN según documentación oficial.
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
        if (!empty($response->paymentMethods)) {
            foreach ($response->paymentMethods as $method) {
                if ($method->enabled && $method->country === 'CO') {
                    $methods[$method->id] = $method->description;
                }
            }
        }
        
        return $methods;
    }

    /**
     * Get list of PSE banks using GET_BANKS_LIST command.
     *
     * @return array Array of banks with code and description
     * @throws \moodle_exception
     */
    public function get_pse_banks(): array {
        global $CFG;
        
        // Check cache first.
        $cache = \cache::make('paygw_payu', 'psebanks');
        $cachekey = 'banks_' . ($this->config->testmode ? 'test' : 'prod');
        
        $banks = $cache->get($cachekey);
        if ($banks !== false) {
            return $banks;
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
        
        if ($response->code !== 'SUCCESS') {
            throw new \moodle_exception('errorgetbanks', 'paygw_payu', '', 
                $response->error ?? 'Unknown error');
        }
        
        $banks = [];
        if (!empty($response->banks)) {
            foreach ($response->banks as $bank) {
                $banks[$bank->pseCode] = $bank->description;
            }
        }
        
        // Cache for 24 hours.
        $cache->set($cachekey, $banks, 86400);
        
        return $banks;
    }

    /**
     * Get list of airlines for airline/travel agency payments.
     * NUEVA IMPLEMENTACIÓN según documentación oficial.
     *
     * @return array Array of airlines
     * @throws \moodle_exception
     */
    public function get_airlines(): array {
        // Build authentication header.
        $authstring = $this->config->apilogin . ':' . $this->config->apikey;
        $authheader = 'Basic ' . base64_encode($authstring);
        
        $url = $this->airlinesendpoint . '?accountId=' . $this->config->payuaccountid;
        
        $options = [
            'CURLOPT_RETURNTRANSFER' => true,
            'CURLOPT_HTTPHEADER' => [
                'Authorization: ' . $authheader,
                'Accept: application/json',
            ],
            'CURLOPT_TIMEOUT' => 30,
        ];
        
        $curl = new \curl();
        $response = $curl->get($url, [], $options);
        
        if ($curl->error) {
            throw new \moodle_exception('errorgetairlines', 'paygw_payu', '', $curl->error);
        }
        
        $result = json_decode($response);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \moodle_exception('errorjsonparse', 'paygw_payu');
        }
        
        $airlines = [];
        if (!empty($result->airlines)) {
            foreach ($result->airlines as $airline) {
                $airlines[$airline->code] = $airline->description;
            }
        }
        
        return $airlines;
    }

    /**
     * Process payment transaction using SUBMIT_TRANSACTION command.
     *
     * @param int $paymentid Payment ID
     * @param float $amount Amount to charge
     * @param string $currency Currency code
     * @param \stdClass $data Payment data from form
     * @return \stdClass Transaction response
     * @throws \moodle_exception
     */
    public function process_payment(int $paymentid, float $amount, string $currency, \stdClass $data): \stdClass {
        global $CFG, $USER;
        
        // Auto-fill test data if in test mode.
        if (!empty($this->config->testmode)) {
            $data = $this->auto_fill_test_data($data);
        }
        
        // Build buyer information.
        $buyer = [
            'fullName' => $data->cardholder ?? fullname($USER),
            'emailAddress' => $data->email ?? $USER->email,
            'contactPhone' => $this->validate_phone($data->phone ?? ''),
            'dniNumber' => $this->validate_document($data->documentnumber ?? ''),
            'shippingAddress' => $this->build_address($data),
        ];
        
        // Build payer information.
        $payer = [
            'fullName' => $data->cardholder ?? fullname($USER),
            'emailAddress' => $data->email ?? $USER->email,
            'contactPhone' => $this->validate_phone($data->phone ?? ''),
            'dniNumber' => $this->validate_document($data->documentnumber ?? ''),
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
                    'accountId' => $this->config->payuaccountid,
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
                    'shippingAddress' => $buyer['shippingAddress'] ?? null,
                ],
                'payer' => $payer,
                'type' => 'AUTHORIZATION_AND_CAPTURE',
                'paymentCountry' => 'CO',
                'ipAddress' => $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1',
                'cookie' => session_id(),
                'userAgent' => $_SERVER['HTTP_USER_AGENT'] ?? 'Moodle PayU Gateway',
                'deviceSessionId' => $this->generate_device_session_id(),
            ],
        ];
        
        // Add airline information if provided.
        if (!empty($data->airline_code)) {
            $request = $this->add_airline_data($request, $data);
        }
        
        // Add payment method specific data.
        $request = $this->add_payment_method_data($request, $data);
        
        // Send request.
        $response = $this->send_request($request);
        
        if ($response->code !== 'SUCCESS') {
            throw new \moodle_exception('errortransaction', 'paygw_payu', '', 
                $response->error ?? 'Transaction failed');
        }
        
        return $response->transactionResponse ?? $response;
    }

    /**
     * Query transaction details by reference code using ORDER_DETAIL_BY_REFERENCE_CODE.
     *
     * @param string $referencecode Reference code
     * @return \stdClass Transaction details
     * @throws \moodle_exception
     */
    public function query_transaction(string $referencecode): \stdClass {
        $request = [
            'language' => 'es',
            'command' => 'ORDER_DETAIL_BY_REFERENCE_CODE',
            'merchant' => [
                'apiLogin' => $this->config->apilogin,
                'apiKey' => $this->config->apikey,
            ],
            'test' => !empty($this->config->testmode),
            'details' => [
                'referenceCode' => $referencecode,
            ],
        ];
        
        $response = $this->send_request($request, $this->reportsendpoint);
        
        if ($response->code !== 'SUCCESS') {
            throw new \moodle_exception('errorquerytransaction', 'paygw_payu', '', 
                $response->error ?? 'Query failed');
        }
        
        return $response->result ?? $response;
    }

    /**
     * Process refund for a transaction.
     * IMPLEMENTACIÓN COMPLETA de reembolsos.
     *
     * @param string $orderid Order ID
     * @param string $transactionid Transaction ID
     * @param string $reason Refund reason
     * @return \stdClass Refund response
     * @throws \moodle_exception
     */
    public function process_refund(string $orderid, string $transactionid, string $reason = ''): \stdClass {
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
                    'id' => $orderid,
                ],
                'type' => 'REFUND',
                'parentTransactionId' => $transactionid,
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
     * Validate callback signature from PayU.
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
     * Auto-fill test data when in test mode.
     * NUEVA IMPLEMENTACIÓN para modo de prueba automático.
     *
     * @param \stdClass $data Original data
     * @return \stdClass Modified data with test values
     */
    protected function auto_fill_test_data(\stdClass $data): \stdClass {
        if (!empty($this->config->testmode)) {
            // Test credentials from documentation.
            if (empty($data->cardholder)) {
                $data->cardholder = 'APPROVED';
            }
            
            // Test credit cards based on desired result.
            if (!empty($data->paymentmethod) && $data->paymentmethod === 'creditcard') {
                if (empty($data->cardnumber)) {
                    // Visa test card that approves.
                    $data->cardnumber = '4111111111111111';
                    $data->expmonth = '12';
                    $data->expyear = date('Y', strtotime('+5 years'));
                    $data->cvv = '123';
                    $data->cardnetwork = 'VISA';
                }
            }
            
            // Test phone for Nequi.
            if (!empty($data->paymentmethod) && $data->paymentmethod === 'nequi') {
                if (empty($data->phone)) {
                    $data->phone = '3001234567';
                }
            }
            
            // Test document.
            if (empty($data->documentnumber)) {
                $data->documentnumber = '1234567890';
            }
            
            // Test email.
            if (empty($data->email)) {
                $data->email = 'test_buyer@test.com';
            }
        }
        
        return $data;
    }

    /**
     * Add airline-specific data to transaction request.
     * NUEVA IMPLEMENTACIÓN para procesamiento de aerolíneas.
     *
     * @param array $request Base request
     * @param \stdClass $data Payment data
     * @return array Modified request
     */
    protected function add_airline_data(array $request, \stdClass $data): array {
        if (!empty($data->airline_code)) {
            $request['transaction']['airline'] = [
                'code' => $data->airline_code,
            ];
            
            // Add PNR data if provided.
            if (!empty($data->pnr_data)) {
                $request['transaction']['pnr'] = [
                    'bookingReference' => $data->pnr_booking_reference ?? '',
                    'passengers' => $data->pnr_passengers ?? [],
                    'flightSegments' => $data->pnr_flight_segments ?? [],
                ];
            }
            
            // Mark as airline transaction.
            $request['transaction']['extraParameters']['AIRLINE_TRANSACTION'] = '1';
        }
        
        return $request;
    }

    /**
     * Add payment method specific data to request.
     *
     * @param array $request Base request
     * @param \stdClass $data Payment data
     * @return array Modified request
     */
    protected function add_payment_method_data(array $request, \stdClass $data): array {
        $method = $data->paymentmethod ?? 'creditcard';
        
        switch ($method) {
            case 'creditcard':
                $request['transaction']['creditCard'] = [
                    'number' => preg_replace('/\s+/', '', $data->cardnumber ?? ''),
                    'securityCode' => $data->cvv ?? '',
                    'expirationDate' => sprintf('%s/%s', 
                        $data->expyear ?? date('Y'), 
                        $data->expmonth ?? '12'),
                    'name' => $data->cardholder ?? '',
                ];
                $request['transaction']['paymentMethod'] = strtoupper($data->cardnetwork ?? 'VISA');
                
                // Add installments if specified.
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
                    'NEQUI_PUSH_NOTIFICATION_URL' => $data->phone ?? '',
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
        // Remove non-numeric characters.
        $phone = preg_replace('/[^0-9]/', '', $phone);
        
        // Colombian phone validation.
        if (strlen($phone) === 10 && substr($phone, 0, 1) === '3') {
            return $phone;
        }
        
        // Return empty if invalid.
        return '';
    }

    /**
     * Validate document number.
     *
     * @param string $document Document number
     * @return string Validated document
     */
    protected function validate_document(string $document): string {
        // Remove special characters but keep alphanumeric.
        $document = preg_replace('/[^A-Za-z0-9]/', '', $document);
        
        // Basic validation for Colombian documents.
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
     * Generate signature for transaction.
     *
     * @param int $referencecode Reference code
     * @param float $amount Amount
     * @param string $currency Currency
     * @return string MD5 signature
     */
    protected function generate_signature(int $referencecode, float $amount, string $currency): string {
        $amount = number_format($amount, 2, '.', '');
        return md5($this->config->apikey . '~' . $this->config->merchantid . '~' . 
                  $referencecode . '~' . $amount . '~' . $currency);
    }

    /**
     * Send request to PayU API.
     *
     * @param array $request Request data
     * @param string|null $endpoint Custom endpoint
     * @return \stdClass Response object
     * @throws \moodle_exception
     */
    protected function send_request(array $request, ?string $endpoint = null): \stdClass {
        $endpoint = $endpoint ?? $this->endpoint;
        
        $options = [
            'CURLOPT_RETURNTRANSFER' => true,
            'CURLOPT_HTTPHEADER' => [
                'Content-Type: application/json',
                'Accept: application/json',
            ],
            'CURLOPT_TIMEOUT' => 60,
        ];
        
        $curl = new \curl();
        $response = $curl->post($endpoint, json_encode($request), $options);
        
        if ($curl->error) {
            throw new \moodle_exception('errorcurlconnection', 'paygw_payu', '', $curl->error);
        }
        
        $result = json_decode($response);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \moodle_exception('errorjsonparse', 'paygw_payu');
        }
        
        return $result;
    }
}