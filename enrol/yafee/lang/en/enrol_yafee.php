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
 * Strings for component 'enrol_yafee', language 'en'.
 *
 * @package    enrol_yafee
 * @copyright 2025 Alex Orlov <snickser@gmail.com>
 * @copyright based on work by 2019 Shamim Rezaie <shamim@moodle.com>
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
You can find new versions of the plugin at <a href=https://github.com/Snickser/moodle-enrol_yafee>GitHub.com</a>
<img src="https://img.shields.io/github/v/release/Snickser/moodle-enrol_yafee.svg"><br>
Please send me some <a href="https://yoomoney.ru/fundraise/143H2JO3LLE.240720">donate</a>😊</div>
BTC 1GFTTPCgRTC8yYL1gU7wBZRfhRNRBdLZsq<br>
TRX TRGMc3b63Lus6ehLasbbHxsb2rHky5LbPe<br>
ETH 0x1bce7aadef39d328d262569e6194febe597cb2c9<br>
<iframe src="https://yoomoney.ru/quickpay/fundraise/button?billNumber=143H2JO3LLE.240720"
width="330" height="50" frameborder="0" allowtransparency="true" scrolling="no"></iframe>';
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
$string['expiredaction_help'] = 'Select the action to be performed when a user\'s enrolment expires. Please note that some user data and settings are deleted when a user is suspended or unenrolled. For suspend or unenrol function you must enable \enrol_yafee\task\sync_enrolments task in Moodle scheduler.';
$string['expiredmessagebody'] = 'Dear {$a->fullname},

This is a notification that your enrolment in the course \'{$a->course}\' has been suspended.

To renew your enrolment, go to {$a->payurl}
';
$string['expiredmessagesubject'] = 'Expiry notice';
$string['expirymessageenrolledbody'] = 'Dear {$a->user},

This is a notification that your enrolment in the course \'{$a->course}\' is due to expire on {$a->timeend}.

If you need help, please contact {$a->enroller}.';
$string['expirymessageenrolledsubject'] = 'Fee enrolment expiry notification';
$string['expirymessageenrollerbody'] = 'Fee enrolment in the course \'{$a->course}\' will expire within the next {$a->threshold} for the following users:

{$a->users}

To extend their enrolment, go to {$a->extendurl}';
$string['expirymessageenrollersubject'] = 'Fee enrolment expiry notification';
$string['expirynotifyall'] = 'Teacher and enrolled user';
$string['expirynotifyenroller'] = 'Teacher only';
$string['expirynotifyperiod'] = 'Suspension notification interval';
$string['expirynotifyperiod_desc'] = 'Sending a notification about suspension of access to a course after the user\'s enrollment period has expired. This parameter must be equal to the execution period of the notification sending scheduler enrol_yafee\task\send_expiry_notifications, if less - messages will not be sent, if more - they will be sent several times.';
$string['extremovedsuspendnoroles'] = 'Suspend course enrolment and remove roles';
$string['forcepayment'] = 'Ignore enrollment deadlines';
$string['forcepayment_help'] = 'If set, the payment form available via the link during the notification threshold period will be available regardless of the course enrollment start or end dates set. For example, when the course enrollment is already closed, previously enrolled students will be able to continue paying for their tuition.';
$string['freetrial'] = 'Free trial of training';
$string['freetrial_desc'] = 'You have a trial period to enrol ({$a->count} {$a->desc})';
$string['freetrial_help'] = 'Allows you to open a course once for a certain period of time without payment.';
$string['freetrialbutton'] = 'Trial enrol';
$string['groupkeytext'] = 'Enter group password';
$string['groupkeytextforce'] = 'Enter group password';
$string['groupsuccess'] = 'Group password successfully accepted';
$string['managemanualenrolements'] = 'Manage YaFee enrolments';
$string['menuname'] = '<font color=red><b>Renew paid subscription</b></font>';
$string['menunameshort'] = '<font color=red><b>Renew paid subscription</b></font>';
$string['messageprovider:expiry_notification'] = 'Self enrolment expiry notifications';
$string['newenrols'] = 'Allow new self enrolments';
$string['newenrols_desc'] = 'Allow users to self enrol into new courses by default.';
$string['newenrols_help'] = 'This setting determines whether a user can self enrol into this course, or renew enrolment.';
$string['nocost'] = 'There is no cost to enrol in this course!';
$string['paymentaccount'] = 'Payment account';
$string['paymentaccount_help'] = 'Enrolment fees will be paid to this account.';
$string['paymentrequired'] = 'This course requires a payment for entry.';
$string['pluginname'] = 'Yet another Enrolment on payment';
$string['pluginname_desc'] = 'The payment registration method allows you to set up courses that require payment. There is a sitewide fee, which you set here as the default fee for the entire site, and then course settings, which you can set for each course individually. The course fee overrides the site fee.';
$string['privacy:metadata'] = 'The enrolment on payment enrolment plugin does not store any personal data.';
$string['purchasedescription'] = 'Enrolment in course {$a}';
$string['renewenrolment'] = 'Renewing a paid subscription';
$string['renewenrolment_text'] = 'Renewal cost';
$string['sendexpirynotificationstask'] = 'Fee enrolment send expiry notifications task';
$string['sendpaymentbutton'] = 'Select payment type';
$string['showduration'] = 'Show duration of training';
$string['status'] = 'Allow enrolment on payment enrolments';
$string['status_desc'] = 'Allow users to make a payment to enrol into a course by default.';
$string['syncenrolmentstask'] = 'Synchronise fee enrolments task';
$string['thisyear'] = 'This year';
$string['uninterrupted'] = 'Pay for missed time';
$string['uninterrupted_desc'] = 'The price for the course is formed taking into account the missed time of the period you have not paid for.';
$string['uninterrupted_help'] = 'The cost of the break from the last payment is added to the course fee. Only works in courses with a set duration of study.';
$string['uninterrupted_warn'] = '<font color=red>Works only in payment gateways bePaid, Robokassa, YooKassa, PayAnyWay!</font>';
$string['validationerror'] = 'Enrolments can not be enabled without specifying the payment account';
$string['yafee:config'] = 'Configure enrolment on payment enrol instances';
$string['yafee:enrol'] = 'Enrol users to course';
$string['yafee:manage'] = 'Manage enrolled users';
$string['yafee:unenrol'] = 'Unenrol users from course';
$string['yafee:unenrolself'] = 'Unenrol self from course';
