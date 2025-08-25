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
 * Low level PayU API helper.
 *
 * This is a minimal client used to submit transactions directly to
 * PayU without using the hosted checkout.
 *
 * @package    paygw_payu
 * @copyright  2024 Example
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace paygw_payu;

defined('MOODLE_INTERNAL') || die();

/**
 * Helper class for communicating with PayU.
 */
class api {

    /**
     * Retrieves the list of PSE banks from PayU.
     *
     * @param \stdClass $config Gateway configuration.
     * @return array Array of pseCode => description.
     */
    public static function get_pse_banks(\stdClass $config): array {
        $request = [
            'language' => 'es',
            'command' => 'GET_BANKS_LIST',
            'merchant' => [
                'apiLogin' => $config->apilogin,
                'apiKey' => $config->apikey,
            ],
            'test' => !empty($config->testmode),
            'bankListInformation' => [
                'paymentMethod' => 'PSE',
                'paymentCountry' => 'CO',
            ],
        ];

        $endpoint = !empty($config->testmode) ?
            'https://sandbox.api.payulatam.com/payments-api/4.0/service.cgi' :
            'https://api.payulatam.com/payments-api/4.0/service.cgi';

        $ch = curl_init($endpoint);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($request));
        $response = curl_exec($ch);
        curl_close($ch);

        $banks = [];
        if ($response) {
            $result = json_decode($response);
            if (!empty($result->banks)) {
                foreach ($result->banks as $bank) {
                    if (!empty($bank->pseCode) && !empty($bank->description)) {
                        $banks[$bank->pseCode] = $bank->description;
                    }
                }
            }
        }

        return $banks;
    }

    /**
     * Submits a transaction to PayU.
     *
     * @param \stdClass $config Gateway configuration.
     * @param int $paymentid Local payment record id.
     * @param float $amount Amount to charge.
     * @param string $currency ISO currency code.
     * @param \stdClass $formdata Data collected from the checkout form.
     * @return \stdClass|null Transaction response or null on failure.
     */
    public static function submit_transaction(\stdClass $config, int $paymentid, float $amount,
            string $currency, \stdClass $formdata): ?\stdClass {
        global $USER;

        $address = [
            'street1' => $USER->address ?? 'N/A',
            'city' => $USER->city ?? 'Bogota',
            'state' => $USER->state ?? ($USER->city ?? 'Bogota'),
            'country' => $USER->country ?? 'CO',
            'postalCode' => '000000',
            'phone' => $formdata->phone ?? '',
        ];

        $request = [
            'language' => 'es',
            'command' => 'SUBMIT_TRANSACTION',
            'merchant' => [
                'apiLogin' => $config->apilogin,
                'apiKey' => $config->apikey,
            ],
            'test' => !empty($config->testmode),
            'transaction' => [
                'order' => [
                    'accountId' => $config->accountid,
                    'referenceCode' => (string) $paymentid,
                    'description' => $formdata->description ?? '',
                    'language' => 'es',
                    'signature' => md5($config->apikey . '~' . $config->merchantid . '~' . $paymentid .
                        '~' . number_format($amount, 2, '.', '') . '~' . $currency),
                    'notifyUrl' => (new \moodle_url('/payment/gateway/payu/callback.php'))->out(false),
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
                        'fullName' => $formdata->cardholder,
                        'emailAddress' => $formdata->email ?? '',
                        'contactPhone' => $formdata->phone ?? '',
                        'dniNumber' => $formdata->documentnumber ?? '',
                        'shippingAddress' => $address,
                    ],
                    'shippingAddress' => $address,
                ],
                'type' => 'AUTHORIZATION_AND_CAPTURE',
                'paymentCountry' => 'CO',
                'ipAddress' => $_SERVER['REMOTE_ADDR'] ?? '',
                'deviceSessionId' => session_id(),
                'cookie' => session_id(),
                'userAgent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
                'payer' => [
                    'fullName' => $formdata->cardholder,
                    'emailAddress' => $formdata->email ?? '',
                    'contactPhone' => $formdata->phone ?? '',
                    'dniNumber' => $formdata->documentnumber ?? '',
                    'billingAddress' => $address,
                ],
            ],
        ];

        if ($formdata->paymentmethod === 'creditcard') {
            $request['transaction']['paymentMethod'] = $formdata->cardnetwork;
            $request['transaction']['creditCard'] = [
                'number' => $formdata->ccnumber,
                'securityCode' => $formdata->cvv,
                'expirationDate' => $formdata->ccexpyear . '/' . $formdata->ccexpmonth,
                'name' => $formdata->cardholder,
            ];
            $request['transaction']['payer']['contactPhone'] = $formdata->phone ?? '';
            $request['transaction']['payer']['dniNumber'] = $formdata->documentnumber ?? '';
            $request['transaction']['extraParameters'] = [
                'INSTALLMENTS_NUMBER' => 1,
            ];
        } else if ($formdata->paymentmethod === 'pse') {
            global $CFG;
            $request['transaction']['paymentMethod'] = 'PSE';
            $request['transaction']['payer']['contactPhone'] = $formdata->phone;
            $request['transaction']['payer']['dniNumber'] = $formdata->documentnumber;
            $request['transaction']['extraParameters'] = [
                'RESPONSE_URL' => $CFG->wwwroot . '/payment/gateway/payu/return.php',
                'FINANCIAL_INSTITUTION_CODE' => $formdata->psebank,
                'USER_TYPE' => $formdata->usertype,
                'PSE_REFERENCE1' => $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1',
                'PSE_REFERENCE2' => $formdata->documenttype,
                'PSE_REFERENCE3' => $formdata->documentnumber,
            ];
        } else if ($formdata->paymentmethod === 'nequi') {
            $request['transaction']['paymentMethod'] = 'NEQUI';
            $request['transaction']['payer']['contactPhone'] = $formdata->phone;
        } else if ($formdata->paymentmethod === 'bancolombia') {
            $request['transaction']['paymentMethod'] = 'BANCOLOMBIA_BUTTON';
            $request['transaction']['payer']['contactPhone'] = $formdata->phone;
        } else if ($formdata->paymentmethod === 'googlepay') {
            $request['transaction']['paymentMethod'] = $formdata->gp_network;
            $request['transaction']['creditCard'] = [
                'name' => $formdata->cardholder,
            ];
            $request['transaction']['digitalWallet'] = [
                'type' => 'GOOGLE_PAY',
                'message' => $formdata->gp_token,
            ];
            $request['transaction']['extraParameters'] = [
                'INSTALLMENTS_NUMBER' => 1,
            ];
        } else if ($formdata->paymentmethod === 'cash') {
            $request['transaction']['paymentMethod'] = $formdata->cashmethod;
            $request['transaction']['payer']['contactPhone'] = $formdata->phone;
            $request['transaction']['expirationDate'] = date('Y-m-d\TH:i:s', strtotime('+7 days'));
        } else {
            $request['transaction']['paymentMethod'] = strtoupper($formdata->paymentmethod);
        }

        $endpoint = !empty($config->testmode) ?
            'https://sandbox.api.payulatam.com/payments-api/4.0/service.cgi' :
            'https://api.payulatam.com/payments-api/4.0/service.cgi';

        $ch = curl_init($endpoint);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($request));
        $response = curl_exec($ch);
        $info = curl_getinfo($ch);
        curl_close($ch);

        if ($info['http_code'] != 200 || empty($response)) {
            return null;
        }

        $result = json_decode($response);
        return $result->transactionResponse ?? null;
    }
}

