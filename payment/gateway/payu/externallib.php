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
 * External functions for PayU payment gateway - COMPLETE implementation.
 *
 * @package    paygw_payu
 * @copyright  2024 Orion Cloud Consulting SAS
 * @author     Alonso Arias <soporte@orioncloud.com.co>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/externallib.php');

/**
 * External functions class for PayU payment gateway - COMPLETE.
 */
class paygw_payu_external extends external_api {
    
    /**
     * Returns description of get_pse_banks parameters.
     *
     * @return external_function_parameters
     */
    public static function get_pse_banks_parameters() {
        return new external_function_parameters([]);
    }
    
    /**
     * Get list of PSE banks.
     *
     * @return array List of banks
     */
    public static function get_pse_banks() {
        global $USER;
        
        // Check user is logged in.
        require_login();
        
        // Get any gateway configuration to fetch banks.
        $config = get_config('paygw_payu');
        
        if (empty($config->apilogin) || empty($config->apikey)) {
            return [];
        }
        
        try {
            $api = new \paygw_payu\api($config);
            $banks = $api->get_pse_banks();
            
            $result = [];
            foreach ($banks as $code => $name) {
                $result[] = [
                    'code' => $code,
                    'name' => $name,
                ];
            }
            
            return $result;
            
        } catch (Exception $e) {
            debugging('Error fetching PSE banks: ' . $e->getMessage(), DEBUG_DEVELOPER);
            return [];
        }
    }
    
    /**
     * Returns description of get_pse_banks return value.
     *
     * @return external_multiple_structure
     */
    public static function get_pse_banks_returns() {
        return new external_multiple_structure(
            new external_single_structure([
                'code' => new external_value(PARAM_TEXT, 'Bank code'),
                'name' => new external_value(PARAM_TEXT, 'Bank name'),
            ])
        );
    }
    
    /**
     * Returns description of get_payment_methods parameters.
     *
     * @return external_function_parameters
     */
    public static function get_payment_methods_parameters() {
        return new external_function_parameters([]);
    }
    
    /**
     * Get available payment methods.
     *
     * @return array List of payment methods
     */
    public static function get_payment_methods() {
        global $USER;
        
        require_login();
        
        $config = get_config('paygw_payu');
        
        if (empty($config->apilogin) || empty($config->apikey)) {
            return [];
        }
        
        try {
            $api = new \paygw_payu\api($config);
            $methods = $api->get_payment_methods();
            
            $result = [];
            foreach ($methods as $id => $description) {
                $result[] = [
                    'id' => (string)$id,
                    'description' => $description,
                ];
            }
            
            return $result;
            
        } catch (Exception $e) {
            debugging('Error fetching payment methods: ' . $e->getMessage(), DEBUG_DEVELOPER);
            return [];
        }
    }
    
    /**
     * Returns description of get_payment_methods return value.
     *
     * @return external_multiple_structure
     */
    public static function get_payment_methods_returns() {
        return new external_multiple_structure(
            new external_single_structure([
                'id' => new external_value(PARAM_TEXT, 'Method ID'),
                'description' => new external_value(PARAM_TEXT, 'Method description'),
            ])
        );
    }
    
    /**
     * Returns description of get_airlines parameters.
     *
     * @return external_function_parameters
     */
    public static function get_airlines_parameters() {
        return new external_function_parameters([]);
    }
    
    /**
     * Get list of airlines.
     *
     * @return array List of airlines
     */
    public static function get_airlines() {
        global $USER;
        
        require_login();
        
        $config = get_config('paygw_payu');
        
        if (empty($config->apilogin) || empty($config->apikey)) {
            return [];
        }
        
        try {
            $api = new \paygw_payu\api($config);
            $airlines = $api->get_airlines();
            
            $result = [];
            foreach ($airlines as $code => $description) {
                $result[] = [
                    'code' => $code,
                    'description' => $description,
                ];
            }
            
            return $result;
            
        } catch (Exception $e) {
            debugging('Error fetching airlines: ' . $e->getMessage(), DEBUG_DEVELOPER);
            return [];
        }
    }
    
    /**
     * Returns description of get_airlines return value.
     *
     * @return external_multiple_structure
     */
    public static function get_airlines_returns() {
        return new external_multiple_structure(
            new external_single_structure([
                'code' => new external_value(PARAM_TEXT, 'Airline code'),
                'description' => new external_value(PARAM_TEXT, 'Airline description'),
            ])
        );
    }
    
    /**
     * Returns description of process_payment parameters.
     *
     * @return external_function_parameters
     */
    public static function process_payment_parameters() {
        return new external_function_parameters([
            'component' => new external_value(PARAM_COMPONENT, 'Component'),
            'paymentarea' => new external_value(PARAM_AREA, 'Payment area'),
            'itemid' => new external_value(PARAM_INT, 'Item ID'),
            'description' => new external_value(PARAM_TEXT, 'Description'),
            'paymentmethod' => new external_value(PARAM_ALPHA, 'Payment method'),
            'cardnumber' => new external_value(PARAM_TEXT, 'Card number', VALUE_OPTIONAL),
            'cardexpmonth' => new external_value(PARAM_TEXT, 'Card expiry month', VALUE_OPTIONAL),
            'cardexpyear' => new external_value(PARAM_TEXT, 'Card expiry year', VALUE_OPTIONAL),
            'cvv' => new external_value(PARAM_TEXT, 'CVV', VALUE_OPTIONAL),
            'cardholder' => new external_value(PARAM_TEXT, 'Cardholder name', VALUE_OPTIONAL),
            'phone' => new external_value(PARAM_TEXT, 'Phone number', VALUE_OPTIONAL),
            'documentnumber' => new external_value(PARAM_TEXT, 'Document number', VALUE_OPTIONAL),
            'email' => new external_value(PARAM_EMAIL, 'Email', VALUE_OPTIONAL),
            'psebank' => new external_value(PARAM_TEXT, 'PSE bank code', VALUE_OPTIONAL),
            'cashmethod' => new external_value(PARAM_TEXT, 'Cash method', VALUE_OPTIONAL),
            'gp_token' => new external_value(PARAM_TEXT, 'Google Pay token', VALUE_OPTIONAL),
            'airline_code' => new external_value(PARAM_TEXT, 'Airline code', VALUE_OPTIONAL),
        ]);
    }
    
    /**
     * Process payment transaction.
     *
     * @param string $component Component
     * @param string $paymentarea Payment area
     * @param int $itemid Item ID
     * @param string $description Description
     * @param string $paymentmethod Payment method
     * @param string $cardnumber Card number
     * @param string $cardexpmonth Card expiry month
     * @param string $cardexpyear Card expiry year
     * @param string $cvv CVV
     * @param string $cardholder Cardholder name
     * @param string $phone Phone number
     * @param string $documentnumber Document number
     * @param string $email Email
     * @param string $psebank PSE bank code
     * @param string $cashmethod Cash method
     * @param string $gp_token Google Pay token
     * @param string $airline_code Airline code
     * @return array Transaction result
     */
    public static function process_payment($component, $paymentarea, $itemid, $description, $paymentmethod,
            $cardnumber = '', $cardexpmonth = '', $cardexpyear = '', $cvv = '', $cardholder = '',
            $phone = '', $documentnumber = '', $email = '', $psebank = '', $cashmethod = '',
            $gp_token = '', $airline_code = '') {
        
        global $USER, $DB;
        
        require_login();
        
        // Validate parameters.
        $params = self::validate_parameters(self::process_payment_parameters(), [
            'component' => $component,
            'paymentarea' => $paymentarea,
            'itemid' => $itemid,
            'description' => $description,
            'paymentmethod' => $paymentmethod,
            'cardnumber' => $cardnumber,
            'cardexpmonth' => $cardexpmonth,
            'cardexpyear' => $cardexpyear,
            'cvv' => $cvv,
            'cardholder' => $cardholder,
            'phone' => $phone,
            'documentnumber' => $documentnumber,
            'email' => $email,
            'psebank' => $psebank,
            'cashmethod' => $cashmethod,
            'gp_token' => $gp_token,
            'airline_code' => $airline_code,
        ]);
        
        // Get payment details.
        $payable = \core_payment\helper::get_payable($component, $paymentarea, $itemid);
        $currency = $payable->get_currency();
        $surcharge = \core_payment\helper::get_gateway_surcharge('payu');
        $amount = \core_payment\helper::get_rounded_cost($payable->get_amount(), $currency, $surcharge);
        
        // Save payment record.
        $paymentid = \core_payment\helper::save_payment(
            $payable->get_account_id(),
            $component,
            $paymentarea,
            $itemid,
            $USER->id,
            $amount,
            $currency,
            'payu'
        );
        
        // Get config.
        $config = (object)\core_payment\helper::get_gateway_configuration($component, $paymentarea, $itemid, 'payu');
        
        // Build payment data.
        $data = new stdClass();
        $data->paymentmethod = $paymentmethod;
        $data->description = $description;
        $data->cardnumber = $cardnumber;
        $data->expmonth = $cardexpmonth;
        $data->expyear = $cardexpyear;
        $data->cvv = $cvv;
        $data->cardholder = $cardholder ?: fullname($USER);
        $data->phone = $phone;
        $data->documentnumber = $documentnumber;
        $data->email = $email ?: $USER->email;
        $data->psebank = $psebank;
        $data->cashmethod = $cashmethod;
        $data->gp_token = $gp_token;
        $data->airline_code = $airline_code;
        
        try {
            $api = new \paygw_payu\api($config);
            $response = $api->process_payment($paymentid, $amount, $currency, $data);
            
            // Save transaction record.
            $transaction = new stdClass();
            $transaction->paymentid = $paymentid;
            $transaction->payu_order_id = $response->orderId ?? null;
            $transaction->payu_transaction_id = $response->transactionId ?? null;
            $transaction->state = $response->state ?? 'PENDING';
            $transaction->payment_method = $paymentmethod;
            $transaction->amount = $amount;
            $transaction->currency = $currency;
            $transaction->response_code = $response->responseCode ?? '';
            $transaction->timecreated = time();
            $transaction->timemodified = time();
            
            $DB->insert_record('paygw_payu', $transaction);
            
            $result = [
                'success' => ($response->state === 'APPROVED'),
                'paymentid' => $paymentid,
                'transactionid' => $response->transactionId ?? '',
                'orderid' => $response->orderId ?? '',
                'state' => $response->state ?? 'UNKNOWN',
                'message' => $response->responseMessage ?? '',
            ];
            
            // Add redirect URL if needed (PSE, Bancolombia).
            if (!empty($response->extraParameters->BANK_URL)) {
                $result['redirect_url'] = $response->extraParameters->BANK_URL;
            }
            
            // Add receipt URL for cash payments.
            if ($paymentmethod === 'cash' && !empty($response->orderId)) {
                $result['receipt_url'] = new moodle_url('/payment/gateway/payu/receipt.php', 
                    ['orderid' => $response->orderId]);
                $result['receipt_url'] = $result['receipt_url']->out(false);
            }
            
            return $result;
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'paymentid' => $paymentid,
                'message' => $e->getMessage(),
            ];
        }
    }
    
    /**
     * Returns description of process_payment return value.
     *
     * @return external_single_structure
     */
    public static function process_payment_returns() {
        return new external_single_structure([
            'success' => new external_value(PARAM_BOOL, 'Success status'),
            'paymentid' => new external_value(PARAM_INT, 'Payment ID'),
            'transactionid' => new external_value(PARAM_TEXT, 'Transaction ID', VALUE_OPTIONAL),
            'orderid' => new external_value(PARAM_TEXT, 'Order ID', VALUE_OPTIONAL),
            'state' => new external_value(PARAM_TEXT, 'Transaction state', VALUE_OPTIONAL),
            'message' => new external_value(PARAM_TEXT, 'Response message', VALUE_OPTIONAL),
            'redirect_url' => new external_value(PARAM_URL, 'Redirect URL', VALUE_OPTIONAL),
            'receipt_url' => new external_value(PARAM_URL, 'Receipt URL', VALUE_OPTIONAL),
        ]);
    }
    
    /**
     * Returns description of query_transaction parameters.
     *
     * @return external_function_parameters
     */
    public static function query_transaction_parameters() {
        return new external_function_parameters([
            'referencecode' => new external_value(PARAM_TEXT, 'Reference code'),
        ]);
    }
    
    /**
     * Query transaction status.
     *
     * @param string $referencecode Reference code
     * @return array Transaction details
     */
    public static function query_transaction($referencecode) {
        global $USER;
        
        require_login();
        
        $params = self::validate_parameters(self::query_transaction_parameters(), [
            'referencecode' => $referencecode,
        ]);
        
        $config = get_config('paygw_payu');
        
        try {
            $api = new \paygw_payu\api($config);
            $result = $api->query_transaction($referencecode);
            
            return [
                'success' => true,
                'orderid' => $result->id ?? '',
                'state' => $result->state ?? 'UNKNOWN',
                'responsecode' => $result->responseCode ?? '',
                'amount' => $result->additionalValues->TX_VALUE->value ?? 0,
                'currency' => $result->additionalValues->TX_VALUE->currency ?? '',
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }
    
    /**
     * Returns description of query_transaction return value.
     *
     * @return external_single_structure
     */
    public static function query_transaction_returns() {
        return new external_single_structure([
            'success' => new external_value(PARAM_BOOL, 'Success status'),
            'orderid' => new external_value(PARAM_TEXT, 'Order ID', VALUE_OPTIONAL),
            'state' => new external_value(PARAM_TEXT, 'Transaction state', VALUE_OPTIONAL),
            'responsecode' => new external_value(PARAM_TEXT, 'Response code', VALUE_OPTIONAL),
            'amount' => new external_value(PARAM_FLOAT, 'Amount', VALUE_OPTIONAL),
            'currency' => new external_value(PARAM_TEXT, 'Currency', VALUE_OPTIONAL),
            'message' => new external_value(PARAM_TEXT, 'Error message', VALUE_OPTIONAL),
        ]);
    }
    
    /**
     * Returns description of process_refund parameters.
     *
     * @return external_function_parameters
     */
    public static function process_refund_parameters() {
        return new external_function_parameters([
            'orderid' => new external_value(PARAM_TEXT, 'Order ID'),
            'transactionid' => new external_value(PARAM_TEXT, 'Transaction ID'),
            'reason' => new external_value(PARAM_TEXT, 'Refund reason', VALUE_OPTIONAL),
        ]);
    }
    
    /**
     * Process refund for a transaction.
     *
     * @param string $orderid Order ID
     * @param string $transactionid Transaction ID
     * @param string $reason Refund reason
     * @return array Refund result
     */
    public static function process_refund($orderid, $transactionid, $reason = '') {
        global $USER;
        
        require_login();
        
        // Check capability.
        $context = context_system::instance();
        require_capability('moodle/payment:manageaccounts', $context);
        
        $params = self::validate_parameters(self::process_refund_parameters(), [
            'orderid' => $orderid,
            'transactionid' => $transactionid,
            'reason' => $reason,
        ]);
        
        $config = get_config('paygw_payu');
        
        try {
            $api = new \paygw_payu\api($config);
            $result = $api->process_refund($orderid, $transactionid, $reason);
            
            return [
                'success' => ($result->state === 'APPROVED'),
                'transactionid' => $result->transactionId ?? '',
                'state' => $result->state ?? 'UNKNOWN',
                'message' => $result->responseMessage ?? '',
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }
    
    /**
     * Returns description of process_refund return value.
     *
     * @return external_single_structure
     */
    public static function process_refund_returns() {
        return new external_single_structure([
            'success' => new external_value(PARAM_BOOL, 'Success status'),
            'transactionid' => new external_value(PARAM_TEXT, 'Transaction ID', VALUE_OPTIONAL),
            'state' => new external_value(PARAM_TEXT, 'Transaction state', VALUE_OPTIONAL),
            'message' => new external_value(PARAM_TEXT, 'Response message', VALUE_OPTIONAL),
        ]);
    }
    
    /**
     * Returns description of autofill_test_data parameters.
     *
     * @return external_function_parameters
     */
    public static function autofill_test_data_parameters() {
        return new external_function_parameters([
            'paymentmethod' => new external_value(PARAM_TEXT, 'Payment method'),
            'result' => new external_value(PARAM_TEXT, 'Desired result (approved/declined)', VALUE_OPTIONAL),
        ]);
    }
    
    /**
     * Get test data for sandbox mode.
     *
     * @param string $paymentmethod Payment method
     * @param string $result Desired result
     * @return array Test data
     */
    public static function autofill_test_data($paymentmethod, $result = 'approved') {
        global $USER;
        
        require_login();
        
        $params = self::validate_parameters(self::autofill_test_data_parameters(), [
            'paymentmethod' => $paymentmethod,
            'result' => $result,
        ]);
        
        $testdata = [];
        
        // Test credentials from PayU documentation.
        $testdata['apilogin'] = 'pRRXKOl8ikMmt9u';
        $testdata['apikey'] = '4Vj8eK4rloUd272L48hsrarnUA';
        $testdata['merchantid'] = '508029';
        $testdata['accountid'] = '512321'; // Colombia account.
        
        switch ($paymentmethod) {
            case 'creditcard':
                if ($result === 'approved') {
                    $testdata['cardnumber'] = '4111111111111111'; // Visa approved.
                    $testdata['cardholder'] = 'APPROVED';
                } else {
                    $testdata['cardnumber'] = '4000000000000002'; // Visa declined.
                    $testdata['cardholder'] = 'DECLINED';
                }
                $testdata['expmonth'] = '12';
                $testdata['expyear'] = date('Y', strtotime('+5 years'));
                $testdata['cvv'] = '123';
                $testdata['documentnumber'] = '1234567890';
                break;
                
            case 'pse':
                $testdata['psebank'] = '1022'; // Test bank code.
                $testdata['documentnumber'] = '1234567890';
                $testdata['phone'] = '3001234567';
                break;
                
            case 'nequi':
                $testdata['phone'] = '3001234567';
                $testdata['documentnumber'] = '1234567890';
                break;
                
            case 'cash':
                $testdata['documentnumber'] = '1234567890';
                $testdata['phone'] = '3001234567';
                break;
        }
        
        $testdata['email'] = 'test_buyer@test.com';
        
        return $testdata;
    }
    
    /**
     * Returns description of autofill_test_data return value.
     *
     * @return external_single_structure
     */
    public static function autofill_test_data_returns() {
        return new external_single_structure([
            'apilogin' => new external_value(PARAM_TEXT, 'Test API login', VALUE_OPTIONAL),
            'apikey' => new external_value(PARAM_TEXT, 'Test API key', VALUE_OPTIONAL),
            'merchantid' => new external_value(PARAM_TEXT, 'Test merchant ID', VALUE_OPTIONAL),
            'accountid' => new external_value(PARAM_TEXT, 'Test account ID', VALUE_OPTIONAL),
            'cardnumber' => new external_value(PARAM_TEXT, 'Test card number', VALUE_OPTIONAL),
            'cardholder' => new external_value(PARAM_TEXT, 'Test cardholder', VALUE_OPTIONAL),
            'expmonth' => new external_value(PARAM_TEXT, 'Test expiry month', VALUE_OPTIONAL),
            'expyear' => new external_value(PARAM_TEXT, 'Test expiry year', VALUE_OPTIONAL),
            'cvv' => new external_value(PARAM_TEXT, 'Test CVV', VALUE_OPTIONAL),
            'documentnumber' => new external_value(PARAM_TEXT, 'Test document', VALUE_OPTIONAL),
            'phone' => new external_value(PARAM_TEXT, 'Test phone', VALUE_OPTIONAL),
            'email' => new external_value(PARAM_EMAIL, 'Test email', VALUE_OPTIONAL),
            'psebank' => new external_value(PARAM_TEXT, 'Test PSE bank', VALUE_OPTIONAL),
        ]);
    }
}