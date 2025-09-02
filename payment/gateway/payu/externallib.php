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
            'paymentid' => new external_value(PARAM_INT, 'Payment ID'),
            'paymentmethod' => new external_value(PARAM_ALPHA, 'Payment method'),
            'formdata' => new external_value(PARAM_RAW, 'Form data JSON'),
        ]);
    }
    
    /**
     * Process payment transaction.
     *
     * @param int $paymentid Payment ID
     * @param string $paymentmethod Payment method
     * @param string $formdata Form data as JSON
     * @return array Result
     */
    public static function process_payment($paymentid, $paymentmethod, $formdata) {
        global $DB, $USER;
        
        // Parameter validation.
        $params = self::validate_parameters(self::process_payment_parameters(), [
            'paymentid' => $paymentid,
            'paymentmethod' => $paymentmethod,
            'formdata' => $formdata,
        ]);
        
        // Check login.
        require_login();
        
        // Get payment record.
        $payment = $DB->get_record('payments', ['id' => $paymentid], '*', MUST_EXIST);
        
        // Verify user owns this payment.
        if ($payment->userid != $USER->id) {
            throw new moodle_exception('invaliduser', 'paygw_payu');
        }
        
        // Parse form data.
        $data = json_decode($formdata);
        if (!$data) {
            throw new moodle_exception('invalidformdata', 'paygw_payu');
        }
        
        // Get gateway configuration.
        $config = (object) \core_payment\helper::get_gateway_configuration(
            $payment->component,
            $payment->paymentarea,
            $payment->itemid,
            'payu'
        );
        
        // Process payment via API.
        try {
            $api = new \paygw_payu\api($config);
            $response = $api->process_payment($paymentid, $payment->amount, $payment->currency, $data);
            
            // Build result.
            $result = [
                'success' => ($response->state === 'APPROVED' || $response->state === 'PENDING'),
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
        global $DB;
        
        // Parameter validation.
        $params = self::validate_parameters(self::query_transaction_parameters(), [
            'referencecode' => $referencecode,
        ]);
        
        require_login();
        
        // Find transaction.
        $transaction = $DB->get_record('paygw_payu', ['payu_order_id' => $referencecode]);
        if (!$transaction) {
            throw new moodle_exception('transactionnotfound', 'paygw_payu');
        }
        
        // Get payment record.
        $payment = $DB->get_record('payments', ['id' => $transaction->paymentid], '*', MUST_EXIST);
        
        // Get config.
        $config = (object) \core_payment\helper::get_gateway_configuration(
            $payment->component,
            $payment->paymentarea,
            $payment->itemid,
            'payu'
        );
        
        // Query via API.
        try {
            $api = new \paygw_payu\api($config);
            $response = $api->query_transaction($referencecode);
            
            return [
                'success' => true,
                'transactionid' => $response->transactionId ?? '',
                'orderid' => $response->orderId ?? '',
                'state' => $response->state ?? '',
                'responsecode' => $response->responseCode ?? '',
                'amount' => $response->amount ?? 0,
                'currency' => $response->currency ?? '',
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
            'transactionid' => new external_value(PARAM_TEXT, 'Transaction ID', VALUE_OPTIONAL),
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
            'paymentid' => new external_value(PARAM_INT, 'Payment ID'),
            'amount' => new external_value(PARAM_FLOAT, 'Refund amount', VALUE_OPTIONAL),
            'reason' => new external_value(PARAM_TEXT, 'Refund reason', VALUE_OPTIONAL),
        ]);
    }
    
    /**
     * Process refund for a transaction.
     *
     * @param int $paymentid Payment ID
     * @param float $amount Refund amount (optional, defaults to full)
     * @param string $reason Refund reason
     * @return array Result
     */
    public static function process_refund($paymentid, $amount = null, $reason = '') {
        global $DB;
        
        // Parameter validation.
        $params = self::validate_parameters(self::process_refund_parameters(), [
            'paymentid' => $paymentid,
            'amount' => $amount,
            'reason' => $reason,
        ]);
        
        // Check capability.
        require_capability('paygw/payu:managerefunds', context_system::instance());
        
        // Get transaction.
        $transaction = $DB->get_record('paygw_payu', ['paymentid' => $paymentid], '*', MUST_EXIST);
        
        // Get payment.
        $payment = $DB->get_record('payments', ['id' => $paymentid], '*', MUST_EXIST);
        
        // Get config.
        $config = (object) \core_payment\helper::get_gateway_configuration(
            $payment->component,
            $payment->paymentarea,
            $payment->itemid,
            'payu'
        );
        
        // Default to full refund if amount not specified.
        if ($amount === null) {
            $amount = $payment->amount;
        }
        
        // Process refund via API.
        try {
            $api = new \paygw_payu\api($config);
            $response = $api->process_refund($transaction->payu_order_id, $amount, $reason);
            
            // Update transaction state.
            if ($response->state === 'REFUNDED') {
                $transaction->state = 'REFUNDED';
                $transaction->timemodified = time();
                $DB->update_record('paygw_payu', $transaction);
            }
            
            return [
                'success' => ($response->state === 'REFUNDED'),
                'transactionid' => $response->transactionId ?? '',
                'state' => $response->state ?? '',
                'message' => $response->responseMessage ?? '',
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
            'paymentmethod' => new external_value(PARAM_ALPHA, 'Payment method'),
        ]);
    }
    
    /**
     * Auto-fill test data for sandbox mode.
     *
     * @param string $paymentmethod Payment method
     * @return array Test data
     */
    public static function autofill_test_data($paymentmethod) {
        // Parameter validation.
        $params = self::validate_parameters(self::autofill_test_data_parameters(), [
            'paymentmethod' => $paymentmethod,
        ]);
        
        require_login();
        
        // Only works in test mode.
        $config = get_config('paygw_payu');
        if (empty($config->testmode)) {
            return [];
        }
        
        $testdata = [];
        
        switch ($paymentmethod) {
            case 'creditcard':
                $testdata = [
                    'cardnumber' => '4111111111111111',
                    'cardholder' => 'APPROVED',
                    'expmonth' => '12',
                    'expyear' => '2030',
                    'cvv' => '123',
                    'documentnumber' => '1234567890',
                    'phone' => '3001234567',
                    'email' => 'test@example.com',
                ];
                break;
                
            case 'pse':
                $testdata = [
                    'psebank' => '1022',
                    'usertype' => 'N',
                    'documenttype' => 'CC',
                    'documentnumber' => '1234567890',
                    'phone' => '3001234567',
                    'email' => 'test@example.com',
                ];
                break;
                
            case 'nequi':
                $testdata = [
                    'phone' => '3009876543',
                    'documentnumber' => '1234567890',
                ];
                break;
                
            case 'cash':
                $testdata = [
                    'documentnumber' => '1234567890',
                    'phone' => '3001234567',
                    'email' => 'test@example.com',
                ];
                break;
        }
        
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