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
 * English language strings for Q10 Grades Sync plugin.
 *
 * @package    local_q10grades
 * @copyright  2025 Your Institution
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

// General.
$string['pluginname'] = 'Q10 Grades Sync';
$string['syncgrades'] = 'Q10 Grade Sync';
$string['privacy:metadata'] = 'The Q10 Grades Sync plugin sends grade data to the external Q10 system.';

// Capabilities.
$string['q10grades:sync'] = 'Sync grades to Q10';
$string['q10grades:viewlogs'] = 'View Q10 sync logs';
$string['q10grades:configure'] = 'Configure Q10 integration';
$string['q10grades:managemapping'] = 'Manage course mapping to Q10';

// Settings.
$string['apisettings'] = 'API Settings';
$string['apisettings_desc'] = 'Configure the connection to the Q10 API. You need to obtain these credentials from your Q10 administrator.';
$string['apiurl'] = 'API URL';
$string['apiurl_desc'] = 'The base URL for the Q10 API (e.g., https://api.q10.com/v1)';
$string['apikey'] = 'API Key';
$string['apikey_desc'] = 'Your Q10 API key (Client ID)';
$string['apisecret'] = 'API Secret';
$string['apisecret_desc'] = 'Your Q10 API secret (Client Secret)';
$string['institutionid'] = 'Institution ID';
$string['institutionid_desc'] = 'Your institution identifier in Q10';

$string['syncsettings'] = 'Sync Settings';
$string['syncsettings_desc'] = 'Configure how grades are synchronized with Q10.';
$string['enableautosync'] = 'Enable automatic sync';
$string['enableautosync_desc'] = 'Automatically sync grades to Q10 on a schedule';
$string['syncfrequency'] = 'Sync frequency';
$string['syncfrequency_desc'] = 'How often to automatically sync grades';
$string['hourly'] = 'Every hour';
$string['every6hours'] = 'Every 6 hours';
$string['every12hours'] = 'Every 12 hours';
$string['daily'] = 'Daily';
$string['weekly'] = 'Weekly';

$string['usermappingfield'] = 'User mapping field';
$string['usermappingfield_desc'] = 'Which Moodle user field to use for matching students in Q10';
$string['coursemappingfield'] = 'Course mapping field';
$string['coursemappingfield_desc'] = 'Which Moodle course field to use for matching subjects in Q10';

$string['gradescale'] = 'Grade format';
$string['gradescale_desc'] = 'How to format grades when sending to Q10';
$string['percentage'] = 'Percentage (0-100)';
$string['rawgrade'] = 'Raw grade value';
$string['lettergrade'] = 'Letter grade';

$string['debugmode'] = 'Debug mode';
$string['debugmode_desc'] = 'Enable detailed logging for troubleshooting API issues';

// Course mapping.
$string['coursemapping'] = 'Course Mapping';
$string['coursemapping_desc'] = 'Map this Moodle course to a subject in Q10. Enter the Q10 subject ID and optionally the period and group.';
$string['q10subjectid'] = 'Q10 Subject ID';
$string['q10subjectid_help'] = 'The unique identifier of the subject/materia in Q10';
$string['q10periodid'] = 'Q10 Period ID';
$string['q10periodid_help'] = 'The academic period ID in Q10 (optional)';
$string['q10groupid'] = 'Q10 Group ID';
$string['q10groupid_help'] = 'The group/section ID in Q10 (optional)';
$string['enablesync'] = 'Enable sync for this course';
$string['savemapping'] = 'Save mapping';
$string['mappingsaved'] = 'Course mapping saved successfully';
$string['currentmapping'] = 'Current Mapping';
$string['editmapping'] = 'Edit mapping';
$string['configuremapping'] = 'Configure mapping';

// Formulas.
$string['formulas'] = 'Grade Formulas';
$string['formulas_desc'] = 'Configure formulas to combine multiple Moodle activities into a single grade for Q10. Each formula maps to a Q10 evaluation component.';
$string['addformula'] = 'Add formula';
$string['editformula'] = 'Edit formula';
$string['deleteformula'] = 'Delete formula';
$string['deleteformulaconfirm'] = 'Are you sure you want to delete the formula "{$a}"?';
$string['formuladeleted'] = 'Formula deleted successfully';
$string['formulacreated'] = 'Formula created successfully';
$string['formulaupdated'] = 'Formula updated successfully';
$string['noformulas'] = 'No formulas configured. Create a formula to map Moodle activities to Q10 grade components.';
$string['configuredformulas'] = 'Configured Formulas';

$string['formulaname'] = 'Formula name';
$string['formulatype'] = 'Calculation type';
$string['formulaaverage'] = 'Simple average';
$string['formulaweighted'] = 'Weighted average';
$string['formulasum'] = 'Sum';
$string['formulahighest'] = 'Highest grade';
$string['formulalowest'] = 'Lowest grade';
$string['formulacustom'] = 'Custom formula';
$string['enableformula'] = 'Enable this formula';
$string['saveformula'] = 'Save formula';

$string['selectactivities'] = 'Select Activities';
$string['selectedactivities'] = 'Selected activities';
$string['gradeitems'] = 'Grade items';
$string['noactivities'] = 'No activities found in this course';
$string['selectatleastone'] = 'Please select at least one activity';

$string['itemweights'] = 'Item Weights';
$string['itemweights_desc'] = 'Assign weights to each selected item for weighted average calculation.';

$string['customformula'] = 'Custom formula';
$string['customformula_help'] = 'Enter a custom mathematical formula. Use [[item_ID]] to reference grade items or [[grade1]], [[grade2]], etc. for positional reference. Supported operators: + - * / ()';

$string['q10componentid'] = 'Q10 Component ID';
$string['q10componentid_help'] = 'The ID of the evaluation component in Q10 where this grade will be uploaded';

// Upload/Sync.
$string['uploadgrades'] = 'Upload Grades';
$string['uploadtoq10'] = 'Upload Grades to Q10';
$string['uploadconfirm'] = 'Are you sure you want to upload grades to Q10? This action cannot be undone.';
$string['uploadcomplete'] = 'Upload complete: {$a->uploaded} grades uploaded, {$a->errors} errors.';
$string['gradepreview'] = 'Grade Preview';
$string['gradepreview_desc'] = 'Review the calculated grades before uploading to Q10.';
$string['previewgrades'] = 'Preview grades';
$string['nostudentgrades'] = 'No student grades available to upload.';
$string['formuladetails'] = 'Formula Details';
$string['student'] = 'Student';
$string['notset'] = 'Not set';
$string['nostudentid'] = 'Student {$a} does not have a Q10 ID mapped.';

// History.
$string['synchistory'] = 'Sync History';
$string['nohistory'] = 'No sync history available.';
$string['syncstatistics'] = 'Sync Statistics';
$string['totalsyncs'] = 'Total syncs';
$string['successful'] = 'Successful';
$string['failed'] = 'Failed';
$string['lastsync'] = 'Last sync';
$string['never'] = 'Never';
$string['syncedby'] = 'Synced by';
$string['pending'] = 'Pending';
$string['type'] = 'Type';
$string['synctype_manual'] = 'Manual';
$string['synctype_scheduled'] = 'Scheduled';
$string['synctype_realtime'] = 'Real-time';

// Grade item mapping.
$string['gradeitemsmapping'] = 'Grade Items Mapping';
$string['gradeitemsmapping_desc'] = 'Map individual Moodle grade items to Q10 evaluation components.';
$string['nogradeitems'] = 'No grade items found in this course.';
$string['savemappings'] = 'Save mappings';
$string['selecteditems'] = 'Selected items';
$string['nitems'] = '{$a} items';

// Quick actions.
$string['quickactions'] = 'Quick Actions';

// Errors and warnings.
$string['apinotconfigured'] = 'Q10 API is not configured.';
$string['configureapi'] = 'Configure API';
$string['nomapping'] = 'This course is not mapped to a Q10 subject.';
$string['nostudentmapping'] = 'Student does not have a Q10 ID mapping.';
$string['connectionsuccessful'] = 'Connection to Q10 API successful!';
$string['authfailed'] = 'Authentication failed: {$a}';
$string['apierror'] = 'API error: {$a}';
$string['invalidjsonresponse'] = 'Invalid JSON response from Q10 API';
$string['invalidhttpmethod'] = 'Invalid HTTP method: {$a}';

// Misc.
$string['enabled'] = 'Enabled';
$string['disabled'] = 'Disabled';
$string['unnamed'] = 'Unnamed';

// Task.
$string['task_sync_grades'] = 'Sync grades to Q10';

// Q10 Items mapping.
$string['q10items'] = 'Q10 Grade Items';
$string['q10items_desc'] = 'Map Q10 grade items (e.g., Assignments, Exams, Participation) to Moodle activities. Select which activities contribute to each Q10 grade item and choose a calculation method.';
$string['q10iteminfo'] = 'Q10 Item Information';
$string['q10itemid'] = 'Q10 Item ID';
$string['q10itemid_help'] = 'The unique identifier for this grade item in Q10';
$string['q10itemname'] = 'Q10 Item Name';
$string['q10itemname_placeholder'] = 'e.g., Assignments, Final Exam, Participation';
$string['q10itemweight'] = 'Weight in Q10 (%)';
$string['q10itemweight_help'] = 'The percentage weight of this item in the final Q10 grade (optional, for reference)';
$string['additem'] = 'Add Q10 Item';
$string['edititem'] = 'Edit Q10 Item';
$string['deleteitem'] = 'Delete Q10 Item';
$string['deleteitemconfirm'] = 'Are you sure you want to delete the Q10 item "{$a}"? This will also remove all activity mappings.';
$string['itemdeleted'] = 'Q10 item deleted successfully';
$string['itemcreated'] = 'Q10 item created successfully';
$string['itemupdated'] = 'Q10 item updated successfully';
$string['noitems'] = 'No Q10 items configured. Add items manually or fetch them from Q10.';
$string['fetchfromq10'] = 'Fetch items from Q10';
$string['itemsimported'] = '{$a} items imported from Q10';
$string['noitemsq10'] = 'No items found in Q10 for this subject';
$string['fetcherror'] = 'Error fetching items from Q10: {$a}';
$string['selectactivities_desc'] = 'Select the Moodle activities that will be used to calculate the grade for this Q10 item.';
$string['calculationmethod'] = 'Calculation Method';
$string['calculationtype'] = 'Calculation type';
$string['calculationtype_help'] = 'How to combine the selected activities into a single grade: Average (mean of all), Weighted (using specified weights), Sum (total), Highest (best grade), or Lowest (worst grade).';
$string['enableitem'] = 'Enable this item for synchronization';
$string['weight'] = 'Weight';
$string['activities'] = 'activities';
$string['none'] = 'None';
$string['configuredq10items'] = 'Configured Q10 Items';
