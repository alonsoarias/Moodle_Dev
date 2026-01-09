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
 * Language strings for local_user_restore plugin.
 *
 * @package    local_user_restore
 * @copyright  2024 Your Institution
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['pluginname'] = 'User Restore';
$string['user_restore:view'] = 'View deleted users';
$string['user_restore:restore'] = 'Restore deleted users';

// Page titles.
$string['deletedusers'] = 'Deleted Users';
$string['restoreuser'] = 'Restore User';
$string['managedeletedusers'] = 'Manage Deleted Users';

// Table headers.
$string['userid'] = 'User ID';
$string['originalusername'] = 'Original Username';
$string['currentusername'] = 'Current Username';
$string['firstname'] = 'First Name';
$string['lastname'] = 'Last Name';
$string['email'] = 'Email';
$string['timedeleted'] = 'Time Deleted';
$string['actions'] = 'Actions';

// Form fields.
$string['newusername'] = 'New Username';
$string['newusername_help'] = 'Enter a new username for the restored user. The original username was anonymized upon deletion.';
$string['newemail'] = 'New Email';
$string['newemail_help'] = 'Enter a valid email address for the restored user. The original email was anonymized upon deletion.';
$string['newpassword'] = 'New Password';
$string['newpassword_help'] = 'Enter a new password for the user. This is required as the original password was cleared.';
$string['sendnotification'] = 'Send notification email';
$string['sendnotification_help'] = 'If enabled, an email will be sent to the user with their new credentials.';

// Buttons.
$string['restore'] = 'Restore';
$string['cancel'] = 'Cancel';
$string['back'] = 'Back to list';

// Messages.
$string['userrestored'] = 'User "{$a}" has been successfully restored.';
$string['userrestoreerror'] = 'Error restoring user: {$a}';
$string['nodeletedusers'] = 'No deleted users found.';
$string['confirmrestore'] = 'Are you sure you want to restore this user?';
$string['usernameexists'] = 'This username is already in use. Please choose a different one.';
$string['emailexists'] = 'This email is already in use. Please choose a different one.';
$string['invaliduser'] = 'Invalid user or user is not deleted.';
$string['restoredsuccessfully'] = 'User restored successfully';
$string['restoreinfo'] = 'Restoring a user will reactivate their account. You must provide a new username and email since the original values were anonymized during deletion.';

// Settings.
$string['settings'] = 'User Restore Settings';
$string['enablesnapshot'] = 'Enable data snapshot';
$string['enablesnapshot_desc'] = 'Automatically capture user data (enrollments, groups, roles, grades) before deletion for later restoration.';
$string['enablelogging'] = 'Enable logging';
$string['enablelogging_desc'] = 'Log all user restoration actions for auditing purposes.';
$string['allowbulkrestore'] = 'Allow bulk restore';
$string['allowbulkrestore_desc'] = 'Allow administrators to restore multiple users at once.';

// Privacy.
$string['privacy:metadata'] = 'The User Restore plugin stores snapshots of user data before deletion to enable full restoration. This includes enrollment data, group memberships, cohort memberships, role assignments, and grades.';

// Events.
$string['eventuserrestored'] = 'User restored';

// Capabilities.
$string['user_restore:viewdeletedusers'] = 'View list of deleted users';
$string['user_restore:restoreusers'] = 'Restore deleted users';

// Errors.
$string['nopermission'] = 'You do not have permission to perform this action.';
$string['usernotfound'] = 'User not found.';
$string['usernotdeleted'] = 'This user has not been deleted.';

// Data restoration strings.
$string['restoredata'] = 'Restore User Data';
$string['restoreuserdata'] = 'Restore enrollments, groups, and grades';
$string['restoreuserdata_help'] = 'If enabled, the user\'s course enrollments, group memberships, cohort memberships, role assignments, and grades will be restored from the saved snapshot.';
$string['snapshotavailable'] = 'Saved user data available for restoration:';
$string['nosnapshotavailable'] = 'No saved data available. The user will be restored but their enrollments, groups, and grades will need to be manually re-added.';
$string['enrolments'] = 'Course enrollments';
$string['groups'] = 'Group memberships';
$string['cohorts'] = 'Cohort memberships';
$string['roles'] = 'Role assignments';
$string['grades'] = 'Grades';

// Restoration result messages.
$string['enrolmentsrestored'] = '{$a} enrollment(s) restored';
$string['groupsrestored'] = '{$a} group membership(s) restored';
$string['cohortsrestored'] = '{$a} cohort membership(s) restored';
$string['rolesrestored'] = '{$a} role assignment(s) restored';
$string['gradesrestored'] = '{$a} grade(s) restored';

// Privacy metadata strings.
$string['privacy:metadata:userid'] = 'The ID of the user whose data snapshot was captured.';
$string['privacy:metadata:datatype'] = 'The type of data stored (enrolment, group, cohort, role, grade).';
$string['privacy:metadata:datajson'] = 'JSON encoded snapshot of user data before deletion.';
$string['privacy:metadata:timecreated'] = 'The time when the snapshot was created.';
