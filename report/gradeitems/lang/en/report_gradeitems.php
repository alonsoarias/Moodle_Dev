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
 * Language strings for the Grade Items Report plugin.
 *
 * @package    report_gradeitems
 * @copyright  2025 Your Institution
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

$string['pluginname'] = 'Grade Items Report';
$string['gradeitems:view'] = 'View grade items report';

// Page titles and headers.
$string['pagetitle'] = 'Grade Items Report';
$string['pageheading'] = 'Grade Items Report - Exportable to Excel';
$string['reportdescription'] = 'This report shows all gradeable activities from courses with their configuration and statistics. Use the filters to narrow down results and export to Excel.';

// Filter labels.
$string['filters'] = 'Filters';
$string['filter_category'] = 'Category';
$string['filter_course'] = 'Course';
$string['filter_moduletype'] = 'Module type';
$string['filter_gradetype'] = 'Grade type';
$string['filter_visibility'] = 'Visibility';
$string['allcategories'] = 'All categories';
$string['allcourses'] = 'All courses';
$string['allmoduletypes'] = 'All module types';
$string['allgradetypes'] = 'All grade types';
$string['allvisibility'] = 'All';
$string['visible'] = 'Visible';
$string['hidden'] = 'Hidden';
$string['applyfilters'] = 'Apply filters';
$string['clearfilters'] = 'Clear filters';

// Table headers.
$string['col_category'] = 'Category';
$string['col_categorypath'] = 'Category path';
$string['col_courseshortname'] = 'Short name';
$string['col_coursefullname'] = 'Course name';
$string['col_coursevisible'] = 'Course visible';
$string['col_coursestartdate'] = 'Start date';
$string['col_courseenddate'] = 'End date';
$string['col_enrolledstudents'] = 'Enrolled students';
$string['col_teachers'] = 'Teachers';
$string['col_activityname'] = 'Activity name';
$string['col_moduletype'] = 'Module type';
$string['col_activityvisible'] = 'Activity visible';
$string['col_section'] = 'Section';
$string['col_gradetype'] = 'Grade type';
$string['col_grademax'] = 'Max grade';
$string['col_gradepass'] = 'Pass grade';
$string['col_gradeweight'] = 'Weight (%)';
$string['col_gradecount'] = 'Grades count';
$string['col_gradeaverage'] = 'Average grade';
$string['col_cmid'] = 'CM ID';

// Grade types.
$string['gradetype_value'] = 'Value';
$string['gradetype_scale'] = 'Scale';
$string['gradetype_text'] = 'Text';
$string['gradetype_none'] = 'None';

// Visibility.
$string['yes'] = 'Yes';
$string['no'] = 'No';

// Export.
$string['downloadexcel'] = 'Download Excel (.xlsx)';
$string['downloadcsv'] = 'Download CSV';
$string['exportoptions'] = 'Export options';

// Results.
$string['totalrecords'] = 'Total records: {$a}';
$string['norecordsfound'] = 'No records found with the current filters.';
$string['showing'] = 'Showing {$a->from} to {$a->to} of {$a->total} records';

// Errors.
$string['nopermission'] = 'You do not have permission to view this report.';
