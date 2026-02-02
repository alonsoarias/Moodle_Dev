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
 * @copyright  2026 Alonso Arias <soporte@orioncloud.com.co>
 * @author     Alonso Arias <soporte@orioncloud.com.co>
 * @link       https://orioncloud.com.co
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

$string['pluginname'] = 'Grade Items Report';
$string['gradeitems:view'] = 'View grade items report';

// Page titles and headers.
$string['pagetitle'] = 'Grade Items Report';
$string['pageheading'] = 'Grade Items Report - Courses Summary';
$string['reportdescription'] = 'This report shows courses with the count of total and gradeable activities. Click on a course name to see the detail of its gradeable activities.';
$string['coursedetail'] = 'Course Detail';
$string['courseinfo'] = 'Course Information';
$string['gradeableactivities'] = 'Gradeable Activities';

// Filter labels.
$string['filters'] = 'Filters';
$string['filter_category'] = 'Category';
$string['filter_course'] = 'Course';
$string['filter_visibility'] = 'Course visibility';
$string['filter_activityvisibility'] = 'Activity visibility';
$string['allcategories'] = 'All categories';
$string['allcourses'] = 'All courses';
$string['allvisibility'] = 'All';
$string['allactivities'] = 'All activities';
$string['visibleactivities'] = 'Visible activities only';
$string['hiddenactivities'] = 'Hidden activities only';
$string['visible'] = 'Visible';
$string['hidden'] = 'Hidden';
$string['applyfilters'] = 'Apply filters';
$string['clearfilters'] = 'Clear filters';

// Table headers - Course list.
$string['col_category'] = 'Category';
$string['col_categorypath'] = 'Category path';
$string['col_courseshortname'] = 'Short name';
$string['col_coursefullname'] = 'Course name';
$string['col_coursevisible'] = 'Visible';
$string['col_coursestartdate'] = 'Start date';
$string['col_courseenddate'] = 'End date';
$string['col_enrolledstudents'] = 'Enrolled students';
$string['col_teachers'] = 'Teachers';
$string['col_totalactivities'] = 'Total activities';
$string['col_gradeableactivities'] = 'Gradeable activities';
$string['col_actions'] = 'Actions';

// Table headers - Activity details.
$string['col_activityname'] = 'Activity name';
$string['col_moduletype'] = 'Module type';
$string['col_activityvisible'] = 'Visible';
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

// Results and navigation.
$string['totalrecords'] = 'Total courses: {$a}';
$string['totalactivities_count'] = 'Total gradeable activities: {$a}';
$string['norecordsfound'] = 'No courses found with the current filters.';
$string['noactivitiesfound'] = 'No gradeable activities found in this course.';
$string['showing'] = 'Showing {$a->from} to {$a->to} of {$a->total} courses';
$string['viewactivities'] = 'View activities';
$string['backtocourselist'] = 'Back to course list';
$string['gotocourse'] = 'Go to course';
$string['col_hiddengradeableactivities'] = 'Hidden gradeable';
$string['resultsperpage'] = 'Results per page';
$string['showall'] = 'Show all';

// Errors.
$string['nopermission'] = 'You do not have permission to view this report.';

// Credits.
$string['developedby'] = 'Developed by';

// Tooltips.
$string['tooltip_category'] = 'Category where the course is located';
$string['tooltip_courseshortname'] = 'Short identifier for the course';
$string['tooltip_coursefullname'] = 'Click to open the course in a new tab';
$string['tooltip_coursevisible'] = 'Indicates if the course is visible to students';
$string['tooltip_enrolledstudents'] = 'Number of students with active enrollment';
$string['tooltip_teachers'] = 'Teachers and non-editing teachers assigned to the course';
$string['tooltip_totalactivities'] = 'Total visible activities in the course (resources and activities)';
$string['tooltip_gradeableactivities'] = 'Visible activities that have grading configured';
$string['tooltip_hiddengradeableactivities'] = 'Hidden activities that have grading configured';
$string['tooltip_viewactivities'] = 'View the list of gradeable activities for this course';
$string['tooltip_section'] = 'Course section where the activity is located';
$string['tooltip_activityname'] = 'Click to open the activity in a new tab';
$string['tooltip_moduletype'] = 'Type of activity module (quiz, assignment, forum, etc.)';
$string['tooltip_activityvisible'] = 'Indicates if the activity is visible to students';
$string['tooltip_gradetype'] = 'Type of grading: Value (numeric), Scale, or Text';
$string['tooltip_grademax'] = 'Maximum grade that can be achieved';
$string['tooltip_gradepass'] = 'Minimum grade required to pass';
$string['tooltip_gradeweight'] = 'Weight of this activity in the gradebook calculation';
$string['tooltip_gradecount'] = 'Number of students who have received a grade';
$string['tooltip_gradeaverage'] = 'Average grade of all graded students';
