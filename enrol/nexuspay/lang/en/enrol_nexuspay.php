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
 * Strings for component 'enrol_nexuspay', language 'en'.
 *
 * @package    enrol_nexuspay
 * @copyright  2025 NexusPay Development Team
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// Plugin name and description.
$string['pluginname'] = 'NexusPay Enrollment';
$string['pluginname_desc'] = 'The NexusPay enrollment method allows you to set up paid courses with flexible payment options. Supports multiple currencies (USD, COP) and various enrollment periods.';

// General settings.
$string['assignrole'] = 'Assign role';
$string['defaultrole'] = 'Default role assignment';
$string['defaultrole_desc'] = 'Select the role to assign to users after making a payment.';
$string['status'] = 'Allow NexusPay enrollments';
$string['status_desc'] = 'Allow users to make payments to enroll in courses by default.';

// Cost and currency settings.
$string['cost'] = 'Enrollment fee';
$string['costerror'] = 'The enrollment fee must be a number greater than zero with up to two decimal places.';
$string['currency'] = 'Currency';
$string['nocost'] = 'There is no cost to enroll in this course!';

// Group settings.
$string['defaultgroup'] = 'Default group';
$string['groupkey'] = 'Use group enrollment keys';
$string['groupkey_desc'] = 'Use group enrollment keys by default.';
$string['groupkeytext'] = 'Click here to enter group password if required.';
$string['groupkeytextforce'] = 'A group password is required to enroll in this course.';
$string['groupsuccess'] = 'Group password successfully accepted';

// Enrollment period settings.
$string['enrolperiod'] = 'Enrollment duration';
$string['enrolperiod_desc'] = 'Default length of time that the enrollment is valid. If set to zero, the enrollment duration will be unlimited by default.';
$string['enrolperiod_help'] = 'Length of time that the enrollment is valid, starting from the moment the user is enrolled. If disabled, the enrollment duration will be unlimited.';
$string['enrolstartdate'] = 'Start date';
$string['enrolstartdate_help'] = 'If enabled, users can only be enrolled from this date onwards.';
$string['enrolenddate'] = 'End date';
$string['enrolenddate_help'] = 'If enabled, users can be enrolled until this date only.';
$string['enrolenddaterror'] = 'The enrollment end date cannot be earlier than the start date.';
$string['enrolperiodend'] = 'Subscription will be extended until {$a->date} {$a->time}';

// Trial period settings.
$string['freetrial'] = 'Free trial period';
$string['freetrial_desc'] = 'Trial period available ({$a->count} {$a->desc})';
$string['freetrial_help'] = 'Allows users to access the course once for a specified period without payment.';
$string['freetrialbutton'] = 'Start free trial';

// Payment settings.
$string['paymentaccount'] = 'Payment account';
$string['paymentaccount_help'] = 'Enrollment fees will be paid to this account.';
$string['paymentrequired'] = 'This course requires a payment for entry.';
$string['purchasedescription'] = 'Enrollment in course {$a}';
$string['sendpaymentbutton'] = 'Make payment';

// New enrollment settings.
$string['newenrols'] = 'Allow new enrollments';
$string['newenrols_desc'] = 'Allow users to self-enroll in new courses by default.';
$string['newenrols_help'] = 'This setting determines whether new users can enroll in this course, or only enrolled users can renew their enrollment.';

// Expiry notifications.
$string['expirynotify'] = 'Notify before enrollment expires';
$string['expirynotify_help'] = 'This setting determines whether enrollment expiry notification messages are sent.';
$string['expirynotifyall'] = 'Enroller and enrolled user';
$string['expirynotifyenroller'] = 'Enroller only';
$string['expirythreshold'] = 'Notification threshold';
$string['expirythreshold_help'] = 'How long before enrollment expires should users be notified?';
$string['expirynotifyhour'] = 'Hour to send enrollment expiry notifications';

// Messages.
$string['expiredaction'] = 'Enrollment expiry action';
$string['expiredaction_help'] = 'Select action to carry out when user enrollment expires. Please note that some user data and settings are purged from course during course unenrollment.';
$string['expiredmessagebody'] = 'Dear {$a->fullname},

This is a notification that your enrollment in the course \'{$a->course}\' has been suspended.

To renew your enrollment, please go to: {$a->payurl}

If you need assistance, please contact your course administrator.';
$string['expiredmessagesubject'] = 'Enrollment expiry notification';

$string['expirymessageenrolledbody'] = 'Dear {$a->user},

This is a notification that your enrollment in the course \'{$a->course}\' is due to expire on {$a->timeend}.

If you need help, please contact {$a->enroller}.';
$string['expirymessageenrolledsubject'] = 'NexusPay enrollment expiry notification';

$string['expirymessageenrollerbody'] = 'NexusPay enrollment in the course \'{$a->course}\' will expire within the next {$a->threshold} for the following users:

{$a->users}

To extend their enrollment, go to {$a->extendurl}';
$string['expirymessageenrollersubject'] = 'NexusPay enrollment expiry notification';

// Uninterrupted payment.
$string['uninterrupted'] = 'Pay for missed time';
$string['uninterrupted_desc'] = 'The course price includes the cost of unpaid periods ({$a}).';
$string['uninterrupted_help'] = 'The course fee includes the cost of time missed since the last payment. Only works in courses with a set duration.';

// Force payment.
$string['forcepayment'] = 'Ignore enrollment dates for payment';
$string['forcepayment_help'] = 'If set, the payment form will be available regardless of enrollment start or end dates. For example, when enrollment is closed, previously enrolled students can continue paying.';

// Additional settings.
$string['showduration'] = 'Show enrollment duration on page';
$string['renewenrolment'] = 'Renew subscription';
$string['renewenrolment_text'] = 'Renewal cost';
$string['enrolperiod_duration'] = 'Duration ({$a->desc}): {$a->count}';
$string['thisyear'] = 'This year';
$string['extremovedsuspendnoroles'] = 'Suspend course enrollment and remove roles';

// Management interface.
$string['manageenrolements'] = 'Manage NexusPay enrollments';
$string['editselectedusers'] = 'Edit selected user enrollments';
$string['menuname'] = 'Payment Options';
$string['menunameshort'] = 'Pay';

// Tasks.
$string['sendexpirynotificationstask'] = 'NexusPay send expiry notifications task';
$string['syncenrolmentstask'] = 'Synchronize NexusPay enrollments task';
$string['expirynotifyperiod'] = 'Expiry notification interval';
$string['expirynotifyperiod_desc'] = 'How often to send notifications about enrollment expiry. This value should match the scheduled task frequency.';

// Privacy.
$string['privacy:metadata'] = 'The NexusPay enrollment plugin does not store any personal data.';

// Capabilities.
$string['nexuspay:config'] = 'Configure NexusPay enrollment instances';
$string['nexuspay:enrol'] = 'Enroll users';
$string['nexuspay:manage'] = 'Manage enrolled users';
$string['nexuspay:unenrol'] = 'Unenroll users from course';
$string['nexuspay:unenrolself'] = 'Unenroll self from course';

// Validation errors.
$string['validationerror'] = 'Enrollments cannot be enabled without specifying a payment account';