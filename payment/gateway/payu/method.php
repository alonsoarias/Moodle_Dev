<?php
// This file is part of Moodle - https://moodle.org/
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
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * PayU payment method display page.
 *
 * @package     paygw_payu
 * @copyright   2025 Alonso Arias <soporte@nexuslabs.com.co>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use core_payment\helper;

require_once(__DIR__ . '/../../../config.php');
global $CFG, $USER, $DB, $OUTPUT, $PAGE;

defined('MOODLE_INTERNAL') || die();

require_login();
require_sesskey();

$component   = required_param('component', PARAM_COMPONENT);
$paymentarea = required_param('paymentarea', PARAM_AREA);
$itemid      = required_param('itemid', PARAM_INT);
$description = required_param('description', PARAM_TEXT);

$description = json_decode('"' . $description . '"');

$params = [
    'component'   => $component,
    'paymentarea' => $paymentarea,
    'itemid'      => $itemid,
    'description' => $description,
];

$config = (object) helper::get_gateway_configuration($component, $paymentarea, $itemid, 'payu');
$payable = helper::get_payable($component, $paymentarea, $itemid);

// Get currency and payment amount.
$currency = $payable->get_currency();
$surcharge = helper::get_gateway_surcharge('payu');
$fee = helper::get_rounded_cost($payable->get_amount(), $currency, $surcharge);

// Get course and enrollment period info.
$enrolperiod = 0;
$enrolperioddesc = null;
$uninterrupted = false;
$showenrolperiod = true;

// Check if this is for enrol_nexuspay
if ($component == "enrol_nexuspay") {
    $cs = $DB->get_record('enrol', ['id' => $itemid, 'enrol' => 'nexuspay']);
    if ($cs) {
        $enrolperiod = $cs->enrolperiod;
        
        // Check uninterrupted cost
        if (!empty($cs->customint5)) {
            $data = $DB->get_record('user_enrolments', ['userid' => $USER->id, 'enrolid' => $cs->id]);
            if ($data) {
                // Calculate periods
                $ctime = time();
                $timeend = isset($data->timeend) ? $data->timeend : $ctime;
                
                if ($timeend < $ctime && $enrolperiod > 0) {
                    $periods_missed = ceil(($ctime - $timeend) / $enrolperiod);
                    if ($periods_missed > 0) {
                        $uninterrupted = true;
                        $fee = $fee * (1 + $periods_missed);
                    }
                }
            }
        }
        
        // Format enrollment period
        if ($enrolperiod > 0) {
            $days = floor($enrolperiod / 86400);
            if ($days > 0) {
                $enrolperioddesc = ($days == 1) ? get_string('day') : $days . ' ' . get_string('days');
            } else {
                $hours = floor($enrolperiod / 3600);
                $enrolperioddesc = ($hours == 1) ? get_string('hour') : $hours . ' ' . get_string('hours');
            }
        }
        
        // Check if should show enrollment period
        $showenrolperiod = !empty($cs->customint8);
    }
}

// Check group names if applicable
$groupnames = '';
if ($paymentarea == 'enrolment' && !empty($cs->customint1)) {
    $groupassignment = $DB->get_record('enrol_nexuspay_groups', [
        'userid' => $USER->id,
        'courseid' => $cs->courseid,
        'instanceid' => $itemid
    ]);
    
    if ($groupassignment && $groupassignment->groupid) {
        $group = $DB->get_record('groups', ['id' => $groupassignment->groupid]);
        if ($group) {
            $groupnames = $group->name;
        }
    }
}

// Set up page
$PAGE->set_context(context_system::instance());
$PAGE->set_url('/payment/gateway/payu/method.php', $params);
$PAGE->set_title(get_string('pluginname', 'paygw_payu'));
$PAGE->set_heading(get_string('pluginname', 'paygw_payu'));

// Get country-specific PayU logo
$country = strtolower($config->country ?? 'co');
$image = $CFG->wwwroot . '/payment/gateway/payu/pix/payu_logo.png';

// Prepare template context
$templatecontext = [
    'sesskey' => sesskey(),
    'component' => $component,
    'paymentarea' => $paymentarea,
    'itemid' => $itemid,
    'description' => $description,
    'fee' => $fee,
    'currency' => $currency,
    'localizedcost' => \core_payment\helper::get_cost_as_string($fee, $currency),
    'image' => $image,
    'config' => $CFG,
    'fixcost' => !empty($config->fixcost),
    'suggest' => $config->suggest ?? $fee,
    'maxcost' => $config->maxcost ?? 0,
    'skipmode' => !empty($config->skipmode),
    'passwordmode' => !empty($config->passwordmode) && empty($config->skipmode),
    'uninterrupted' => $uninterrupted,
    'enrolperiod' => $enrolperiod,
    'enrolperiod_desc' => $enrolperioddesc,
    'showenrolperiod' => $showenrolperiod && $enrolperiod > 0,
    'groupnames' => $groupnames,
    'country_name' => \paygw_payu\gateway::SUPPORTED_COUNTRIES[$config->country] ?? 'Colombia'
];

echo $OUTPUT->header();
echo $OUTPUT->render_from_template('paygw_payu/method', $templatecontext);
echo $OUTPUT->footer();