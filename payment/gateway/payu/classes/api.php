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
    
    /** @var string Reports API endpoint for sandbox */
    const REPORTS_SANDBOX = 'https://sandbox.api.payulatam.com/reports-api/4.0/service.cgi';
    
    /** @var string Reports API endpoint for production */
    const REPORTS_PRODUCTION = 'https://api.payulatam.com/reports-api/4.0/service.cgi';
    
    /** @var \stdClass Gateway configuration */
    protected $config;
    
    /** @var string API endpoint based on test mode */
    protected $endpoint;
    
    /** @var string Reports endpoint based on test mode */
    protected $reportsendpoint;
    
    /**
     * Constructor.
     *
     * @param \stdClass $config Gateway configuration
     */
    public function __construct(\stdClass $config) {
        $this->config = $config;
        $this->endpoint = !empty($config->testmode) ? self::ENDPOINT_SANDBOX : self::ENDPOINT_PRODUCTION;
        $this->reportsendpoint = !empty($config->testmode) ? self::REPORTS_SANDBOX : self::REPORTS_PRODUCTION;
    }

    /**
     * Test connectivity with PayU API.
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
     * Get list of PSE banks.
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
     * Process payment transaction.
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
        $request = $this->add_payment_method_data($request, $data);
        
        // Send request.
        $response = $this->send_request($request);
        
        if ($response->code !== 'SUCCESS') {
            throw new \moodle_exception('errortransaction', 'paygw_payu', '', 
                $response->error ?? 'Transaction failed');
        }
        
        return $response->transactionResponse;
    }

    /**
     * Query transaction status.
     *
     * @param string $transactionid Transaction ID
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
        
        $response = $this->send_request($request, $this->reportsendpoint);
        
        if ($response->code !== 'SUCCESS') {
            throw new \moodle_exception('errorquerytransaction', 'paygw_payu', '', 
                $response->error ?? 'Query failed');
        }
        
        return $response->result;
    }

    /**
     * Process refund.
     *
     * @param string $orderid Order ID
     * @param string $reason Refund reason
     * @return \stdClass Refund response
     * @throws \moodle_exception
     */
    public function process_refund(string $orderid, string $reason = ''): \stdClass {
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
                'reason' => $reason ?: 'Refund requested',
                'paymentCountry' => 'CO',
            ],
        ];
        
        $response = $this->send_request($request);
        
        if ($response->code !== 'SUCCESS') {
            throw new \moodle_exception('errorrefund', 'paygw_payu', '', 
                $response->error ?? 'Refund failed');
        }
        
        return $response->transactionResponse;
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
                $request['transaction']['paymentMethod'] = 'BANCOLOMBIA';
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
        }
        
        return $request;
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
     * @param \stdClass $data Payment data
     * @return array Address structure
     */
    protected function build_address(\stdClass $data): array {
        return [
            'street1' => $data->address1 ?? 'N/A',
            'street2' => $data->address2 ?? '',
            'city' => $data->city ?? 'Bogotá',
            'state' => $data->state ?? 'Bogotá D.C.',
            'country' => 'CO',
            'postalCode' => $data->postalcode ?? '000000',
            'phone' => $this->validate_phone($data->phone ?? ''),
        ];
    }

    /**
     * Send request to PayU API.
     *
     * @param array $data Request data
     * @param string|null $endpoint Optional endpoint override
     * @return \stdClass Response object
     * @throws \moodle_exception
     */
    protected function send_request(array $data, string $endpoint = null): \stdClass {
        $endpoint = $endpoint ?: $this->endpoint;
        
        $ch = curl_init($endpoint);
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
        if (!empty($this->config->testmode) || !empty($this->config->debugmode)) {
            $this->log_transaction($data, $result);
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
        return md5(session_id() . microtime());
    }

    /**
     * Log transaction for debugging.
     *
     * @param array $request Request data
     * @param \stdClass $response Response data
     */
    protected function log_transaction(array $request, \stdClass $response): void {
        global $CFG;
        
        // Remove sensitive data.
        if (isset($request['merchant']['apiKey'])) {
            $request['merchant']['apiKey'] = '***HIDDEN***';
        }
        if (isset($request['transaction']['creditCard'])) {
            $request['transaction']['creditCard']['number'] = '***HIDDEN***';
            $request['transaction']['creditCard']['securityCode'] = '***';
        }
        
        $logentry = [
            'timestamp' => time(),
            'endpoint' => $this->endpoint,
            'request' => json_encode($request),
            'response' => json_encode($response),
        ];
        
        // Write to debug log.
        if (!empty($CFG->debugdisplay)) {
            mtrace('PayU Transaction: ' . print_r($logentry, true));
        }
        
        // Log to file if debug mode.
        if (!empty($this->config->debugmode)) {
            $logfile = $CFG->dataroot . '/payu_debug.log';
            file_put_contents($logfile, date('Y-m-d H:i:s') . ' - ' . 
                json_encode($logentry) . PHP_EOL, FILE_APPEND);
        }
    }
}