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
 * English language strings for report_educam1.
 *
 * @package    block_report_educam1
 * @copyright  2025 IngeWeb - Soluciones para triunfar en Internet
 * @author     Alonso Arias <soporte@ingeweb.co>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

// Plugin name.
$string['pluginname'] = 'Custom Notification Report';
$string['report_educam1'] = 'Custom Notification Report';

// Capabilities.
$string['report_educam1:addinstance'] = 'Add a new Custom Notification Report block';
$string['report_educam1:myaddinstance'] = 'Add a new Custom Notification Report block to My Moodle';
$string['report_educam1:viewreport'] = 'View custom report';

// Block scope.
$string['block_no_access'] = 'You do not have permission to use this report.';
$string['block_only_course'] = 'This block only works in course contexts.';
$string['block_link_label'] = 'View activity report';
$string['block_scope_current_course'] = 'Course: {$a}';
$string['block_activity_type'] = 'Activity type: {$a}';
$string['block_no_activity_configured'] = 'No activity type has been selected. Configure the block to select one.';
$string['block_configure_notice'] = 'Edit this block to select the activity type to report.';

// Configuration form.
$string['config_header'] = 'Report configuration';
$string['config_context_label'] = 'Block location';
$string['config_context_course'] = 'Course: {$a}';
$string['config_only_course'] = 'This block can only be configured within a course.';
$string['config_activitytype'] = 'Activity type to report';
$string['config_activitytype_help'] = 'Select the activity type (Assignment, Quiz, SCORM, Forum, etc.) you want to report on. The report will show the completion status of all students for activities of this type in the course.';
$string['config_activitytype_hint'] = 'You can create multiple instances of this block to report on different activity types in the same course.';
$string['config_select_activity'] = 'Select an activity type...';
$string['config_no_activities'] = 'There are no activities available in this course.';
$string['config_activitytype_required'] = 'You must select an activity type.';

// Report titles.
$string['report_title'] = 'Custom Notification Report';
$string['report_title_course'] = 'Report: {$a}';
$string['report_viewing_course'] = 'Course';
$string['report_activity_type'] = 'Activity type';

// Report views.
$string['view_individual'] = 'Individual view';
$string['view_matrix'] = 'Matrix view';
$string['switch_to_individual'] = 'Switch to individual view';
$string['switch_to_matrix'] = 'Switch to matrix view';

// Columns - Individual View.
$string['column_idnumber'] = 'ID Number';
$string['column_firstname'] = 'First name';
$string['column_lastname'] = 'Last name';
$string['column_email'] = 'Email';
$string['column_activity'] = 'Activity';
$string['column_completion_date'] = 'Completion date';
$string['column_status'] = 'Status';

// Columns - Matrix View.
$string['column_student'] = 'Student';

// Completion statuses.
$string['status_completed'] = 'Completed';
$string['status_not_completed'] = 'Not completed';
$string['completed_symbol'] = '✓';
$string['not_completed_symbol'] = '✗';

// Filters.
$string['filter_header'] = 'Filters';
$string['filter_activity'] = 'Specific activity';
$string['filter_all_activities'] = 'All activities';
$string['filter_status'] = 'Status';
$string['filter_search'] = 'Search student';
$string['filter_apply'] = 'Apply filters';
$string['filter_clear'] = 'Clear filters';
$string['filter_idnumber'] = 'ID Number';
$string['filter_idnumber_placeholder'] = 'Search by ID...';
$string['filter_firstname'] = 'First name';
$string['filter_firstname_placeholder'] = 'Search by first name...';
$string['filter_lastname'] = 'Last name';
$string['filter_lastname_placeholder'] = 'Search by last name...';
$string['filter_all'] = 'All';
$string['filter_by_letter'] = 'Filter by letter';
$string['filter_startdate'] = 'Completion start date';
$string['filter_enddate'] = 'Completion end date';

// Export.
$string['export_header'] = 'Export data';
$string['export_format'] = 'Format';
$string['export_excel'] = 'Excel (.xlsx)';
$string['export_ods'] = 'OpenDocument (.ods)';
$string['export_csv'] = 'CSV';
$string['export_button'] = 'Export';
$string['export_filename_individual'] = 'activity_report_individual';
$string['export_filename_matrix'] = 'activity_report_matrix';

// Messages.
$string['no_students'] = 'There are no students enrolled in this course.';
$string['no_activities'] = 'There are no activities of the selected type in this course.';
$string['no_data'] = 'No data to display.';
$string['loading'] = 'Loading data...';
$string['error_loading'] = 'Error loading data. Please try again.';

// Statistics.
$string['stats_total_students'] = 'Total students';
$string['stats_total_activities'] = 'Total activities';
$string['stats_completion_rate'] = 'Completion rate';
$string['stats_completed'] = 'Completed';
$string['stats_not_completed'] = 'Not completed';
$string['stats_total_completions'] = 'Total completions';

// Help.
$string['help_view_individual'] = 'The individual view shows a detailed list of each student with their completion status for the selected activities.';
$string['help_view_matrix'] = 'The matrix view shows a table with all students and all activities of the selected type, allowing you to see at a glance the overall progress of the course.';

// Pagination.
$string['showing_entries'] = 'Showing {$a->start} to {$a->end} of {$a->total} entries';
$string['per_page'] = 'Per page';

// Privacy.
$string['privacy:metadata'] = 'The Custom Notification Report block only displays existing data from other locations.';
