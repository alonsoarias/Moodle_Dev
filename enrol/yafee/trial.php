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
 * Fee enrolment plugin.
 *
 * This plugin allows you to set up paid courses.
 *
 * @package    enrol_yafee
 * @copyright 2024 Alex Orlov <snickser@gmail.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use core_payment\helper;

require_once(__DIR__ . '/../../config.php');
global $USER, $DB;

defined('MOODLE_INTERNAL') || die();

require_login();
require_sesskey();

// Set the context of the page.
$PAGE->set_url($SCRIPT);
$PAGE->set_context(context_system::instance());

$instanceid = required_param('instanceid', PARAM_INT);

$url = \enrol_yafee\payment\service_provider::get_success_url('yafee', $instanceid);

$userid = $USER->id;
$component   = 'enrol_yafee';
$paymentarea = 'fee';

// Check.
$instance = $DB->get_record('enrol', ['enrol' => 'yafee', 'id' => $instanceid], '*', MUST_EXIST);
if ($DB->record_exists('enrol_yafee', ['courseid' => $instance->courseid, 'userid' => $userid])) {
    redirect($url);
}

$payable = helper::get_payable($component, $paymentarea, $instanceid);

$currency = $payable->get_currency();
if ($currency == 'BYR') {
    $currency = 'BYN';
}

$paymentid = helper::save_payment(
    $payable->get_account_id(),
    $component,
    $paymentarea,
    $instanceid,
    $userid,
    0,
    $currency,
    'yafee'
);

helper::deliver_order($component, $paymentarea, $instanceid, $paymentid, $userid);

redirect($url);
