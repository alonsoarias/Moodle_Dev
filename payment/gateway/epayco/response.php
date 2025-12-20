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
 * Handles ePayco response (user return from payment page).
 *
 * This file is where the user is redirected after completing payment on ePayco.
 * It shows the payment status and redirects to the appropriate page.
 *
 * @package    paygw_epayco
 * @copyright  2025 ingeweb.co <soporte@ingeweb.co>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use core_payment\helper;
use paygw_epayco\gateway;
use paygw_epayco\epayco_helper;

require_once(__DIR__ . '/../../../config.php');

require_login();

// Get parameters from ePayco response (GET parameters).
$refpayco = optional_param('ref_payco', '', PARAM_ALPHANUMEXT);

// Alternative: ePayco sometimes sends parameters differently.
if (empty($refpayco)) {
    $refpayco = optional_param('x_ref_payco', '', PARAM_ALPHANUMEXT);
}

$PAGE->set_context(context_system::instance());
$PAGE->set_url('/payment/gateway/epayco/response.php');
$PAGE->set_pagelayout('standard');
$PAGE->set_title(get_string('paymentresult', 'paygw_epayco'));

echo $OUTPUT->header();

if (empty($refpayco)) {
    // No reference provided, show error.
    echo $OUTPUT->notification(get_string('error:noreference', 'paygw_epayco'), 'error');
    echo $OUTPUT->continue_button(new moodle_url('/'));
    echo $OUTPUT->footer();
    die();
}

try {
    // Query ePayco API to get transaction details.
    $apiurl = 'https://secure.epayco.co/validation/v1/reference/' . urlencode($refpayco);

    $curl = curl_init();
    curl_setopt_array($curl, [
        CURLOPT_URL => $apiurl,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
    ]);

    $response = curl_exec($curl);
    $httpcode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    curl_close($curl);

    if ($httpcode !== 200 || empty($response)) {
        throw new \Exception('Failed to query ePayco API');
    }

    $data = json_decode($response, true);
    if (json_last_error() !== JSON_ERROR_NONE || !isset($data['success']) || !$data['success']) {
        throw new \Exception('Invalid response from ePayco API');
    }

    $txdata = $data['data'] ?? [];
    $codresponse = $txdata['x_cod_response'] ?? 0;
    $responsemsg = $txdata['x_response'] ?? '';
    $responsereason = $txdata['x_response_reason_text'] ?? '';
    $reference = $txdata['x_id_invoice'] ?? '';
    $amount = $txdata['x_amount'] ?? 0;
    $currency = $txdata['x_currency_code'] ?? 'COP';

    // Get component info from extra fields.
    $component = $txdata['x_extra1'] ?? '';
    $paymentarea = $txdata['x_extra2'] ?? '';
    $itemid = (int)($txdata['x_extra3'] ?? 0);

    // Display result based on status.
    echo html_writer::start_div('payment-result text-center py-5');

    if ($codresponse == epayco_helper::STATUS_ACCEPTED) {
        // Payment approved.
        echo html_writer::tag('div',
            html_writer::tag('i', '', ['class' => 'fa fa-check-circle fa-5x text-success']),
            ['class' => 'mb-4']
        );
        echo html_writer::tag('h2', get_string('paymentapproved', 'paygw_epayco'), ['class' => 'text-success']);
        echo html_writer::tag('p', get_string('paymentapproved_desc', 'paygw_epayco'), ['class' => 'lead']);

    } else if ($codresponse == epayco_helper::STATUS_PENDING) {
        // Payment pending.
        echo html_writer::tag('div',
            html_writer::tag('i', '', ['class' => 'fa fa-clock-o fa-5x text-warning']),
            ['class' => 'mb-4']
        );
        echo html_writer::tag('h2', get_string('paymentpending', 'paygw_epayco'), ['class' => 'text-warning']);
        echo html_writer::tag('p', get_string('paymentpending_desc', 'paygw_epayco'), ['class' => 'lead']);

    } else if ($codresponse == epayco_helper::STATUS_REJECTED) {
        // Payment rejected.
        echo html_writer::tag('div',
            html_writer::tag('i', '', ['class' => 'fa fa-times-circle fa-5x text-danger']),
            ['class' => 'mb-4']
        );
        echo html_writer::tag('h2', get_string('paymentrejected', 'paygw_epayco'), ['class' => 'text-danger']);
        echo html_writer::tag('p', $responsereason ?: get_string('paymentrejected_desc', 'paygw_epayco'), ['class' => 'lead']);

    } else {
        // Other status (failed, cancelled, etc.).
        echo html_writer::tag('div',
            html_writer::tag('i', '', ['class' => 'fa fa-exclamation-circle fa-5x text-secondary']),
            ['class' => 'mb-4']
        );
        echo html_writer::tag('h2', get_string('paymentfailed', 'paygw_epayco'), ['class' => 'text-secondary']);
        echo html_writer::tag('p', $responsereason ?: get_string('paymentfailed_desc', 'paygw_epayco'), ['class' => 'lead']);
    }

    // Transaction details.
    echo html_writer::start_tag('div', ['class' => 'card mt-4 mx-auto', 'style' => 'max-width: 400px;']);
    echo html_writer::start_tag('div', ['class' => 'card-body']);
    echo html_writer::tag('h5', get_string('transactiondetails', 'paygw_epayco'), ['class' => 'card-title']);

    echo html_writer::tag('p',
        html_writer::tag('strong', get_string('reference', 'paygw_epayco') . ': ') . s($reference),
        ['class' => 'card-text mb-1']
    );
    echo html_writer::tag('p',
        html_writer::tag('strong', get_string('refpayco', 'paygw_epayco') . ': ') . s($refpayco),
        ['class' => 'card-text mb-1']
    );
    echo html_writer::tag('p',
        html_writer::tag('strong', get_string('amount', 'paygw_epayco') . ': ') .
        format_float($amount, 2) . ' ' . s($currency),
        ['class' => 'card-text mb-1']
    );

    echo html_writer::end_tag('div');
    echo html_writer::end_tag('div');

    echo html_writer::end_div();

    // Determine redirect URL.
    $redirecturl = new moodle_url('/');

    if (!empty($component) && !empty($paymentarea) && $itemid > 0) {
        try {
            $successurl = helper::get_success_url($component, $paymentarea, $itemid);
            if ($codresponse == epayco_helper::STATUS_ACCEPTED) {
                $redirecturl = $successurl;
            }
        } catch (\Exception $e) {
            // Use default redirect.
        }
    }

    echo html_writer::start_div('text-center mt-4');
    echo $OUTPUT->single_button($redirecturl, get_string('continue'), 'get');
    echo html_writer::end_div();

} catch (\Exception $e) {
    debugging('ePayco response error: ' . $e->getMessage(), DEBUG_DEVELOPER);
    echo $OUTPUT->notification(get_string('error:queryingepayco', 'paygw_epayco'), 'error');
    echo $OUTPUT->continue_button(new moodle_url('/'));
}

echo $OUTPUT->footer();
