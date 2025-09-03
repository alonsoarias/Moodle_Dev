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
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * Strings for component 'enrol_nexuspay', language 'en'.
 *
 * @package    enrol_nexuspay
 * @copyright 2025 Alonso Arias <soporte@nexuslabs.com.co>
 * @author    Alonso Arias
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['assignrole'] = 'Assign role';
$string['cost'] = 'Enrolment fee';
$string['costerror'] = 'The enrolment fee must be a number and greater than zero.';
$string['currency'] = 'Currency';
$string['defaultgroup'] = 'Default group';
$string['defaultrole'] = 'Default role assignment';
$string['defaultrole_desc'] = 'Select the role to assign to users after making a payment.';
$string['donate'] = '<div>Plugin version: {$a->release} ({$a->versiondisk})<br>
You can find new versions of the plugin at <a href=https://github.com/NexusLabs/moodle-enrol_nexuspay>GitHub.com</a>
<img src="https://img.shields.io/github/v/release/NexusLabs/moodle-enrol_nexuspay.svg"><br>
For support, contact <a href="mailto:soporte@nexuslabs.com.co">NexusLabs</a></div>';
$string['enrolenddate'] = 'End date';
$string['enrolenddate_help'] = 'If enabled, users can be enrolled until this date only.';
$string['enrolenddaterror'] = 'The enrolment end date cannot be earlier than the start date.';
$string['enrolperiod'] = 'Enrolment duration ({$a->desc}): {$a->count}';
$string['enrolperiod_desc'] = 'Default length of time that the enrolment is valid. If set to zero, the enrolment duration
 will be unlimited by default.';
$string['enrolperiod_help'] = 'Length of time that the enrolment is valid, starting with the moment the user is enrolled. If disabled, the enrolment duration will be unlimited.';
$string['enrolperiodend'] = 'Renewing until {$a->date} {$a->time}';
$string['enrolstartdate'] = 'Start date';
$string['enrolstartdate_help'] = 'If enabled, users can only be enrolled from this date onwards.';
$string['expiredaction'] = 'Enrolment expiry action';
$string['expiredaction_help'] = 'Select action to carry out when user enrolment expires. Please note that some user data and settings are purged from course during course unenrolment.';
$string['expirynotify'] = 'Notify before enrolment expires';
$string['expirynotify_help'] = 'This setting determines whether enrolment expiry notification messages are sent.';
$string['expirythreshold'] = 'Notification threshold';
$string['expirythreshold_help'] = 'How long before enrolment expiry should users be notified?';
$string['defaultenrolperiod'] = 'Default enrolment duration';
$string['defaultenrolperiod_desc'] = 'Default length of time that the enrolment is valid. If set to zero, the enrolment duration will be unlimited by default.';
$string['forcepayment'] = 'Force payment';
$string['forcepayment_desc'] = 'Force payment page opening for the course when the course is accessed';
$string['forcepayment_help'] = 'Force payment page opening for the course when the course is accessed';
$string['freetrial'] = 'Free trial';
$string['freetrial_desc'] = 'Free first month in courses with a set duration of study for new enrolments.';
$string['groupkey'] = 'Use group enrolment keys';
$string['groupkey_desc'] = 'Use group enrolment keys by default.';
$string['groupkeytext'] = 'If you have a group key, please enter it here';
$string['groupkeytextforce'] = 'If you have a group key, please enter it here. Otherwise payment will be required';
$string['groupsuccess'] = 'Group key accepted';
$string['managemanualenrolements'] = 'Manage NexusPay enrolments';
$string['menuname'] = 'NexusPay payment';
$string['menunameshort'] = 'NexusPay';
$string['newenrols'] = 'Allow new enrolments without payment';
$string['newenrols_desc'] = 'Allow users to enrol into course without payment by default if they have never been enrolled before.';
$string['newenrolswithoutpayment'] = 'Enrolment into the course is available without payment for those enrolling for the first time';
$string['nocost'] = 'There is no cost associated with enrolling in this course!';
$string['notenrollable'] = 'You cannot enrol yourself in this course.';
$string['notrequired'] = 'Not required';
$string['paymentrequired'] = 'A fee payment is required to participate in this course.';
$string['pluginname'] = 'NexusPay Enrolment';
$string['pluginname_desc'] = 'The NexusPay enrolment method allows you to set up courses that require a payment. There is a site-wide fee that you set as a default for the whole site and then a course setting that you can set for each course individually. The course fee overrides the site fee.';
$string['privacy:metadata'] = 'The NexusPay enrolment plugin does not store any personal data.';
$string['renewenrolment'] = 'Renew paid subscription';
$string['renewenrolment_text'] = 'Renewal cost';
$string['role'] = 'Default assigned role';
$string['sendexpirynotificationstask'] = 'NexusPay enrolment send expiry notifications task';
$string['sendpaymentbutton'] = 'Select payment method';
$string['showduration'] = 'Show duration of training on page';
$string['status'] = 'Allow NexusPay enrolments';
$string['status_desc'] = 'Allow users to use NexusPay to enrol into a course by default.';
$string['syncenrolmentstask'] = 'Synchronise NexusPay enrolments task';
$string['thisyear'] = 'This year';
$string['uninterrupted'] = 'Pay for missed time';
$string['uninterrupted_desc'] = 'The price for the course is formed taking into account the missed time of the period you have not paid for.';
$string['uninterrupted_help'] = 'The cost of the break from the last payment is added to the course fee. Only works in courses with a set duration of study.';
$string['uninterrupted_warn'] = '<font color=red>Works only in payment gateways PayU, bePaid, Robokassa, YooKassa, PayAnyWay!</font>';
$string['validationerror'] = 'Enrolments can not be enabled without specifying the payment account';
$string['nexuspay:config'] = 'Configure enrolment on payment enrol instances';
$string['nexuspay:enrol'] = 'Enrol users to course';
$string['nexuspay:manage'] = 'Manage enrolled users';
$string['nexuspay:unenrol'] = 'Unenrol users from course';
$string['nexuspay:unenrolself'] = 'Unenrol self from course';