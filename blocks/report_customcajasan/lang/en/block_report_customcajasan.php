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
 * English language strings for report_customcajasan.
 *
 * @package    block_report_customcajasan
 * @copyright  2025 Cajasan
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

// Plugin name.
$string['pluginname'] = 'Course status and tracking report';
$string['report_customcajasan'] = 'Course status and tracking report';
$string['enrollment_report'] = 'Course status and tracking report';
$string['enrollment_report_course'] = 'View course report';

// Capabilities.
$string['report_customcajasan:addinstance'] = 'Add a new Course status and tracking report block';
$string['report_customcajasan:myaddinstance'] = 'Add a new Course status and tracking report block to the My Moodle page';
$string['report_customcajasan:viewreport'] = 'View Course status and tracking report';
$string['report_customcajasan:viewblock'] = 'View the Course status and tracking report block';
$string['report_customcajasan:manageblock'] = 'Manage the Course status and tracking report block at system level';
$string['report_customcajasan:configurecourse'] = 'Configure the Course status and tracking report block within a course';

// Block scope strings.
$string['block_no_access'] = 'You do not have permission to use this report.';
$string['block_link_label'] = 'Open consolidated report';
$string['block_link_label_course'] = 'Open course report';
$string['block_scope_system'] = 'Scope: Site-wide summary';
$string['block_scope_my'] = 'Scope: Personal dashboard';
$string['block_scope_current_course'] = 'Scope: Current course – {$a}';
$string['block_scope_categories'] = 'Included course categories:';
$string['block_scope_course_only'] = 'The report will display data from this course only.';
$string['block_scope_none'] = 'No categories selected. Configure the block to select one or more categories.';
$string['block_scope_coursecount'] = 'Total courses currently in scope: {$a}';
$string['block_scope_configure_notice'] = 'Edit this block to choose the categories that should feed the report.';

// Configuration form.
$string['config_header'] = 'Report scope configuration';
$string['config_context_label'] = 'Block location';
$string['config_context_system'] = 'Main site (front page). Only site managers can configure or view the report here.';
$string['config_context_my'] = 'Personal dashboard. Only site managers can configure or view the report here.';
$string['config_context_course'] = 'Course page: {$a}';
$string['config_context_system_note'] = 'The consolidated report will only show courses that belong to the selected categories. Without categories no data will be displayed.';
$string['config_context_my_note'] = 'Use this configuration to build a personalised overview sourced from the selected course categories.';
$string['config_context_course_note'] = 'By default, the report includes the current course "{$a}". Any categories you select will add their courses on top of it without duplicates.';
$string['config_categoryids'] = 'Categories included in the report';
$string['config_categoryids_help'] = 'Select the course categories whose courses should be included when the report is generated. '
    . 'When the block is placed inside a course, the current course is always included automatically.';
$string['config_categoryids_hint'] = 'Leave empty to include only the current course when the block is inside a course. '
    . 'On the front page or dashboard, at least one category is required for data to be displayed.';
$string['config_categoryids_placeholder'] = 'Select one or more categories';
$string['config_categoryids_invalid'] = 'One or more of the selected categories is no longer available. Please review the selection.';

// Table headers.
$string['column_identificacion'] = 'IDENTIFICATION';
$string['column_nombres'] = 'FIRST NAME';
$string['column_apellidos'] = 'LAST NAME';
$string['column_correo'] = 'EMAIL';
$string['column_unidad'] = 'UNIT';
$string['column_curso'] = 'COURSE';
$string['column_fecha_matricula'] = 'ENROLMENT DATE';
$string['column_fecha_certificado'] = 'CERTIFICATE ISSUANCE DATE';
$string['column_estado'] = 'STATUS';
$string['column_categoria'] = 'CATEGORY';
$string['column_ultimo_acceso'] = 'LAST ACCESS DATE';

// Status values.
$string['state_aprobado'] = 'APPROVED';
$string['state_encurso'] = 'IN PROGRESS';
$string['state_noiniciado'] = 'NOT STARTED';
$string['state_soloconsulta'] = 'REFERENCE ONLY';
$string['status_explanation'] = 'Status codes';
$string['status_note'] = 'Courses that do not issue certificates are labelled as "REFERENCE ONLY".';

// Special values.
$string['never'] = 'Never';

// Form options.
$string['option_all'] = 'All';
$string['option_category'] = 'Category';
$string['option_course'] = 'Course';
$string['option_firstname'] = 'First name';
$string['option_lastname'] = 'Last name';
$string['option_estado'] = 'Status';
$string['option_filter_by_letter'] = 'Filter by letter';
$string['option_download_format'] = 'Download format';
$string['option_download_excel'] = 'Excel';
$string['option_download_ods'] = 'ODS';
$string['option_download_csv'] = 'CSV';

// Pagination options.
$string['records_per_page'] = 'Records per page';
$string['all_records'] = 'All records';

// Report headings and labels.
$string['report_title'] = 'Course status and tracking report';
$string['report_title_course'] = 'Course report: {$a}';
$string['report_scope_heading'] = 'Effective report scope';
$string['report_scope_current_course'] = 'Current course: {$a}';
$string['report_scope_categories'] = 'Configured categories:';
$string['report_scope_additional_courses'] = 'Additional courses derived from categories:';
$string['report_scope_none'] = 'No additional categories configured. Only explicitly selected courses are included.';
$string['report_scope_not_ready'] = 'Configure this block to select at least one category before viewing data.';
$string['report_scope_effective_courses'] = 'Total courses in scope: {$a}';
$string['report_scope_restricted'] = 'The available filters are limited by the block configuration.';
$string['report_scope_notice_frontpage'] = 'No categories have been configured yet. Select one or more categories in the block settings to display data.';
$string['btn_download'] = 'Download';
$string['total_records'] = 'Total records';
$string['idnumber'] = 'ID number';
$string['start_date'] = 'Start date';
$string['end_date'] = 'End date';
$string['no_data'] = 'No enrolment data found';
$string['access_denied'] = 'You do not have permission to view this report';
$string['filters_required'] = 'You must select at least one filter to view data';
$string['select_filter_first'] = 'Please select at least one filter to view the report data';
$string['search'] = 'Search';
$string['viewing_course_report'] = 'Viewing report for: {$a}';
$string['viewing_course_report_full'] = 'Course report';
$string['report_no_scope_configured'] = 'This report block has not been configured yet. Please edit the block and select one or more categories.';
$string['download_scope_missing'] = 'A download cannot be generated because no valid courses are currently in scope.';
$string['invalidblockinstance'] = 'The requested block instance could not be found.';

// AJAX error messages.
$string['ajax_error'] = 'Error loading data. Please try again.';
$string['ajax_error_detail'] = 'Failed to load data';

// DataTables strings.
$string['showing'] = 'Showing';
$string['to'] = 'to';
$string['of'] = 'of';
$string['entries'] = 'entries';
$string['filtered_from'] = 'filtered from';
$string['total'] = 'total';
$string['first'] = 'First';
$string['last'] = 'Last';
$string['next'] = 'Next';
$string['previous'] = 'Previous';
