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
 * English language strings for report_customcajasan
 *
 * @package    block_report_customcajasan
 * @copyright  2025 Cajasan
 * @author     Pedro Arias <soporte@ingeweb.co>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// Plugin name
$string['pluginname'] = 'Course Status and Tracking Report';
$string['report_customcajasan'] = 'Course Status and Tracking Report';
$string['enrollment_report'] = 'Course Status and Tracking Report';

// Capabilities
$string['report_customcajasan:addinstance'] = 'Add a new Course Status and Tracking Report block';
$string['report_customcajasan:myaddinstance'] = 'Add a new Course Status and Tracking Report block to the My Moodle page';
$string['report_customcajasan:viewblock'] = 'View the Course Status and Tracking Report block';
$string['report_customcajasan:viewreport'] = 'View Course Status and Tracking Report';
$string['report_customcajasan:viewsystemreport'] = 'View Course Status and Tracking Report on system pages';

// Table Headers
$string['column_identificacion'] = 'IDENTIFICATION';
$string['column_nombres'] = 'FIRST NAME';
$string['column_apellidos'] = 'LAST NAME';
$string['column_correo'] = 'EMAIL';
$string['column_unidad'] = 'UNIT';
$string['column_curso'] = 'COURSE';
$string['column_fecha_matricula'] = 'ENROLLMENT DATE';
$string['column_fecha_certificado'] = 'CERTIFICATE ISSUANCE DATE';
$string['column_estado'] = 'STATUS';
$string['column_categoria'] = 'CATEGORY';
$string['column_ultimo_acceso'] = 'LAST ACCESS DATE';

// Status values - updated for new status values
$string['state_aprobado'] = 'APPROVED';
$string['state_encurso'] = 'IN PROGRESS';
$string['state_noiniciado'] = 'NOT STARTED';
$string['state_soloconsulta'] = 'REFERENCE ONLY';
$string['status_explanation'] = 'Status Codes';

// Special values
$string['never'] = 'Never';

// Form Options
$string['option_all'] = 'All';
$string['option_category'] = 'Category';
$string['option_course'] = 'Course';
$string['option_firstname'] = 'First Name';
$string['option_lastname'] = 'Last Name';
$string['option_estado'] = 'Status';
$string['option_filter_by_letter'] = 'Filter by letter';
$string['option_download_format'] = 'Download Format';
$string['option_download_excel'] = 'Excel';
$string['option_download_ods'] = 'ODS';
$string['option_download_csv'] = 'CSV';

// Pagination options
$string['records_per_page'] = 'Records per page';
$string['all_records'] = 'All records';

// Other
$string['report_title'] = 'Course Status and Tracking Report';
$string['btn_download'] = 'Download';
$string['total_records'] = 'Total Records';
$string['idnumber'] = 'ID Number';
$string['start_date'] = 'Start Date';
$string['end_date'] = 'End Date';
$string['no_data'] = 'No enrollment data found';
$string['access_denied'] = 'You do not have permission to view this report';
$string['filters_required'] = 'You must select at least one filter to view data';
$string['select_filter_first'] = 'Please select at least one filter to view the report data';
$string['note_label'] = 'Note';
$string['status_note'] = 'Courses that do not issue certificates have the "REFERENCE ONLY" status.';
$string['search'] = 'Search';
$string['block_instructionstext'] = 'Use the report filters to narrow the search and download only the information you need.';

// Block configuration strings.
$string['config_displayoptions'] = 'Information to display';
$string['config_displayoptions_help'] = 'Choose which elements should be visible within this block. Each instance can present different information depending on the needs of the course or page.';
$string['config_option_reportlink'] = 'Show link to the detailed report';
$string['config_option_instructions'] = 'Show usage instructions';
$string['config_option_statuslegend'] = 'Show status legend';
$string['config_custommessage'] = 'Additional message';
$string['config_custommessage_help'] = 'Optional text displayed beneath the selected information. This can be used to highlight deadlines, contact information or any other relevant guidance.';
$string['config_restriction_heading'] = 'Course and category filters';
$string['config_restriction_heading_help'] = 'Choose which courses or categories should be available when accessing the report from this block. The selection applies only to this block instance.';
$string['config_coursefilters'] = 'Limit to courses';
$string['config_coursefilters_help'] = 'Select one or more courses that should be available within the report. Users will only be able to see enrolments for the selected courses.';
$string['config_coursefilters_empty'] = 'No courses are available with the required permissions.';
$string['config_categoryfilters'] = 'Limit to categories';
$string['config_categoryfilters_help'] = 'Select whole categories to include in the report. All courses inside the chosen categories will be available to users.';
$string['config_categoryfilters_empty'] = 'No categories are available with the required permissions.';
$string['config_summary_courses'] = 'Courses: {$a}';
$string['config_summary_categories'] = 'Categories: {$a}';

// AJAX error messages
$string['ajax_error'] = 'Error loading data. Please try again.';
$string['ajax_error_detail'] = 'Failed to load data';

// DataTables strings
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