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
 * Grade Items Report - Main page (Course summary view).
 *
 * This report displays a summary of courses with their total and gradeable
 * activities count. Click on a course to see the detailed activities.
 *
 * @package    report_gradeitems
 * @copyright  2025 Your Institution
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/adminlib.php');
require_once($CFG->libdir . '/excellib.class.php');

// Parameters.
$download = optional_param('download', '', PARAM_ALPHA);
$page = optional_param('page', 0, PARAM_INT);
$perpage = optional_param('perpage', 50, PARAM_INT);

// Filter parameters.
$categoryid = optional_param('category', '', PARAM_INT);
$courseid = optional_param('course', '', PARAM_INT);
$visibility = optional_param('visibility', '', PARAM_RAW);
$resetfilters = optional_param('resetbutton', '', PARAM_RAW);

// Reset filters if requested.
if (!empty($resetfilters)) {
    redirect(new moodle_url('/report/gradeitems/index.php'));
}

// Setup page.
admin_externalpage_setup('reportgradeitems', '', null, '', ['pagelayout' => 'report']);

$context = context_system::instance();
require_capability('report/gradeitems:view', $context);

$baseurl = new moodle_url('/report/gradeitems/index.php', [
    'category' => $categoryid,
    'course' => $courseid,
    'visibility' => $visibility,
    'perpage' => $perpage,
]);

// Build the SQL query with filters.
$params = [];
$where = ['c.id > 1'];

if (!empty($categoryid)) {
    // Include subcategories.
    $categoryids = get_category_and_children($categoryid);
    list($insql, $inparams) = $DB->get_in_or_equal($categoryids, SQL_PARAMS_NAMED, 'cat');
    $where[] = "c.category $insql";
    $params = array_merge($params, $inparams);
}

if (!empty($courseid)) {
    $where[] = 'c.id = :courseid';
    $params['courseid'] = $courseid;
}

if ($visibility !== '') {
    $where[] = 'c.visible = :visibility';
    $params['visibility'] = (int)$visibility;
}

$wheresql = implode(' AND ', $where);

// Add current time parameter for enrollment check.
$params['timenow'] = time();

// Main SQL query - Course summary with activity counts.
$sql = "SELECT
            c.id AS courseid,
            c.shortname AS courseshortname,
            c.fullname AS coursefullname,
            c.visible AS coursevisible,
            c.startdate AS coursestartdate,
            c.enddate AS courseenddate,
            cc.id AS categoryid,
            cc.name AS categoryname,
            (SELECT COUNT(DISTINCT ue.userid)
             FROM {user_enrolments} ue
             JOIN {enrol} e ON e.id = ue.enrolid
             WHERE e.courseid = c.id
               AND e.status = 0
               AND ue.status = 0
               AND (ue.timeend = 0 OR ue.timeend > :timenow)
            ) AS enrolledstudents,
            (SELECT COUNT(cm.id)
             FROM {course_modules} cm
             WHERE cm.course = c.id
               AND cm.deletioninprogress = 0
            ) AS totalactivities,
            (SELECT COUNT(gi.id)
             FROM {grade_items} gi
             WHERE gi.courseid = c.id
               AND gi.itemtype = 'mod'
               AND gi.gradetype > 0
            ) AS gradeableactivities
        FROM {course} c
        JOIN {course_categories} cc ON cc.id = c.category
       WHERE $wheresql
    ORDER BY cc.name, c.fullname";

// Count total records.
$countsql = "SELECT COUNT(c.id)
               FROM {course} c
               JOIN {course_categories} cc ON cc.id = c.category
              WHERE $wheresql";

// Remove timenow from count params (not used in count query).
$countparams = array_diff_key($params, ['timenow' => '']);
$totalcount = $DB->count_records_sql($countsql, $countparams);

// Handle Excel download.
if ($download === 'excel') {
    download_courses_excel($sql, $params);
    exit;
}

// Handle CSV download.
if ($download === 'csv') {
    download_courses_csv($sql, $params);
    exit;
}

// Get records for current page.
$records = $DB->get_records_sql($sql, $params, $page * $perpage, $perpage);

// Get teachers for all courses in results.
$courseids = array_keys($records);
$teachersbycourse = [];
if (!empty($courseids)) {
    $teachersbycourse = get_teachers_by_course($courseids);
}

// Output page.
echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('pageheading', 'report_gradeitems'));

echo html_writer::tag('p', get_string('reportdescription', 'report_gradeitems'), ['class' => 'lead']);

// Display filter form.
$filterform = new \report_gradeitems\form\filter_form($baseurl);
$filterform->set_data([
    'category' => $categoryid,
    'course' => $courseid,
    'visibility' => $visibility,
]);
$filterform->display();

// Export buttons.
echo html_writer::start_div('mb-3 mt-3');
echo html_writer::tag('h5', get_string('exportoptions', 'report_gradeitems'));
$excelurl = new moodle_url($baseurl, ['download' => 'excel']);
$csvurl = new moodle_url($baseurl, ['download' => 'csv']);
echo html_writer::link($excelurl, get_string('downloadexcel', 'report_gradeitems'), [
    'class' => 'btn btn-primary mr-2',
]);
echo html_writer::link($csvurl, get_string('downloadcsv', 'report_gradeitems'), [
    'class' => 'btn btn-secondary',
]);
echo html_writer::end_div();

// Display total count.
echo html_writer::tag('p', get_string('totalrecords', 'report_gradeitems', $totalcount), ['class' => 'font-weight-bold']);

if ($totalcount == 0) {
    echo $OUTPUT->notification(get_string('norecordsfound', 'report_gradeitems'), 'info');
} else {
    // Display pagination info.
    $from = ($page * $perpage) + 1;
    $to = min(($page + 1) * $perpage, $totalcount);
    echo html_writer::tag('p', get_string('showing', 'report_gradeitems', (object)[
        'from' => $from,
        'to' => $to,
        'total' => $totalcount,
    ]));

    // Display table.
    $table = new html_table();
    $table->head = [
        get_string('col_category', 'report_gradeitems'),
        get_string('col_courseshortname', 'report_gradeitems'),
        get_string('col_coursefullname', 'report_gradeitems'),
        get_string('col_coursevisible', 'report_gradeitems'),
        get_string('col_coursestartdate', 'report_gradeitems'),
        get_string('col_enrolledstudents', 'report_gradeitems'),
        get_string('col_teachers', 'report_gradeitems'),
        get_string('col_totalactivities', 'report_gradeitems'),
        get_string('col_gradeableactivities', 'report_gradeitems'),
        get_string('col_actions', 'report_gradeitems'),
    ];
    $table->attributes['class'] = 'table table-striped table-hover table-sm';
    $table->data = [];

    foreach ($records as $record) {
        $teachers = isset($teachersbycourse[$record->courseid]) ? $teachersbycourse[$record->courseid] : '-';

        // Link to course detail page.
        $detailurl = new moodle_url('/report/gradeitems/course.php', ['id' => $record->courseid]);

        $table->data[] = [
            format_string($record->categoryname),
            format_string($record->courseshortname),
            html_writer::link($detailurl, format_string($record->coursefullname), [
                'title' => get_string('viewactivities', 'report_gradeitems'),
            ]),
            $record->coursevisible ? get_string('yes', 'report_gradeitems') : get_string('no', 'report_gradeitems'),
            $record->coursestartdate ? userdate($record->coursestartdate, get_string('strftimedateshort', 'langconfig')) : '-',
            $record->enrolledstudents,
            html_writer::tag('small', $teachers),
            $record->totalactivities,
            html_writer::tag('strong', $record->gradeableactivities),
            html_writer::link($detailurl, get_string('viewactivities', 'report_gradeitems'), [
                'class' => 'btn btn-sm btn-outline-primary',
            ]),
        ];
    }

    echo html_writer::table($table);

    // Pagination.
    echo $OUTPUT->paging_bar($totalcount, $page, $perpage, $baseurl);
}

echo $OUTPUT->footer();

/**
 * Get category and all its children IDs.
 *
 * @param int $categoryid
 * @return array
 */
function get_category_and_children(int $categoryid): array {
    global $DB;

    $ids = [$categoryid];

    $children = $DB->get_records('course_categories', ['parent' => $categoryid], '', 'id');
    foreach ($children as $child) {
        $ids = array_merge($ids, get_category_and_children($child->id));
    }

    return $ids;
}

/**
 * Get teachers for multiple courses.
 *
 * @param array $courseids
 * @return array
 */
function get_teachers_by_course(array $courseids): array {
    global $DB;

    if (empty($courseids)) {
        return [];
    }

    list($insql, $params) = $DB->get_in_or_equal($courseids, SQL_PARAMS_NAMED, 'course');

    $sql = "SELECT DISTINCT ctx.instanceid AS courseid,
                   u.id AS userid,
                   u.firstname,
                   u.lastname
              FROM {role_assignments} ra
              JOIN {context} ctx ON ctx.id = ra.contextid
              JOIN {user} u ON u.id = ra.userid
             WHERE ctx.contextlevel = :contextlevel
               AND ctx.instanceid $insql
               AND ra.roleid IN (3, 4)
          ORDER BY ctx.instanceid, u.lastname, u.firstname";

    $params['contextlevel'] = CONTEXT_COURSE;

    $teachers = $DB->get_records_sql($sql, $params);

    $result = [];
    foreach ($teachers as $teacher) {
        if (!isset($result[$teacher->courseid])) {
            $result[$teacher->courseid] = [];
        }
        $result[$teacher->courseid][] = fullname($teacher);
    }

    // Convert arrays to strings.
    foreach ($result as $courseid => $names) {
        $result[$courseid] = implode(', ', $names);
    }

    return $result;
}

/**
 * Get category path for a category.
 *
 * @param int $categoryid
 * @return string
 */
function get_category_path(int $categoryid): string {
    global $DB;

    $category = $DB->get_record('course_categories', ['id' => $categoryid]);
    if (!$category) {
        return '';
    }

    $path = $category->name;
    if ($category->parent > 0) {
        $parentpath = get_category_path($category->parent);
        if (!empty($parentpath)) {
            $path = $parentpath . ' / ' . $path;
        }
    }

    return $path;
}

/**
 * Download courses report as Excel file.
 *
 * @param string $sql
 * @param array $params
 */
function download_courses_excel(string $sql, array $params): void {
    global $DB;

    $records = $DB->get_records_sql($sql, $params);

    // Get teachers for all courses.
    $courseids = array_keys($records);
    $teachersbycourse = get_teachers_by_course($courseids);

    // Create workbook.
    $filename = 'courses_gradeable_activities_' . date('Y-m-d_His');
    $workbook = new MoodleExcelWorkbook($filename);

    // Add worksheet.
    $worksheet = $workbook->add_worksheet(get_string('pluginname', 'report_gradeitems'));

    // Header format.
    $headerformat = $workbook->add_format([
        'bold' => 1,
        'bg_color' => '#4472C4',
        'color' => 'white',
        'align' => 'center',
        'border' => 1,
    ]);

    // Data format.
    $dataformat = $workbook->add_format(['border' => 1]);
    $numformat = $workbook->add_format(['border' => 1, 'align' => 'right']);

    // Headers.
    $headers = [
        get_string('col_category', 'report_gradeitems'),
        get_string('col_categorypath', 'report_gradeitems'),
        get_string('col_courseshortname', 'report_gradeitems'),
        get_string('col_coursefullname', 'report_gradeitems'),
        get_string('col_coursevisible', 'report_gradeitems'),
        get_string('col_coursestartdate', 'report_gradeitems'),
        get_string('col_courseenddate', 'report_gradeitems'),
        get_string('col_enrolledstudents', 'report_gradeitems'),
        get_string('col_teachers', 'report_gradeitems'),
        get_string('col_totalactivities', 'report_gradeitems'),
        get_string('col_gradeableactivities', 'report_gradeitems'),
    ];

    // Write headers.
    $col = 0;
    foreach ($headers as $header) {
        $worksheet->write_string(0, $col, $header, $headerformat);
        $col++;
    }

    // Set column widths.
    $worksheet->set_column(0, 0, 20);
    $worksheet->set_column(1, 1, 35);
    $worksheet->set_column(2, 2, 15);
    $worksheet->set_column(3, 3, 40);
    $worksheet->set_column(4, 4, 10);
    $worksheet->set_column(5, 6, 12);
    $worksheet->set_column(7, 7, 12);
    $worksheet->set_column(8, 8, 40);
    $worksheet->set_column(9, 10, 15);

    // Write data.
    $row = 1;
    foreach ($records as $record) {
        $teachers = isset($teachersbycourse[$record->courseid]) ? $teachersbycourse[$record->courseid] : '';
        $categorypath = get_category_path($record->categoryid);

        $col = 0;
        $worksheet->write_string($row, $col++, format_string($record->categoryname), $dataformat);
        $worksheet->write_string($row, $col++, $categorypath, $dataformat);
        $worksheet->write_string($row, $col++, format_string($record->courseshortname), $dataformat);
        $worksheet->write_string($row, $col++, format_string($record->coursefullname), $dataformat);
        $worksheet->write_string($row, $col++, $record->coursevisible ? get_string('yes') : get_string('no'), $dataformat);
        $worksheet->write_string($row, $col++,
            $record->coursestartdate ? userdate($record->coursestartdate, '%Y-%m-%d') : '', $dataformat);
        $worksheet->write_string($row, $col++,
            $record->courseenddate ? userdate($record->courseenddate, '%Y-%m-%d') : '', $dataformat);
        $worksheet->write_number($row, $col++, $record->enrolledstudents, $numformat);
        $worksheet->write_string($row, $col++, $teachers, $dataformat);
        $worksheet->write_number($row, $col++, $record->totalactivities, $numformat);
        $worksheet->write_number($row, $col++, $record->gradeableactivities, $numformat);

        $row++;
    }

    $workbook->close();
}

/**
 * Download courses report as CSV file.
 *
 * @param string $sql
 * @param array $params
 */
function download_courses_csv(string $sql, array $params): void {
    global $DB, $CFG;

    require_once($CFG->libdir . '/csvlib.class.php');

    $records = $DB->get_records_sql($sql, $params);

    // Get teachers for all courses.
    $courseids = array_keys($records);
    $teachersbycourse = get_teachers_by_course($courseids);

    $filename = 'courses_gradeable_activities_' . date('Y-m-d_His');
    $csvexport = new csv_export_writer('comma');
    $csvexport->set_filename($filename);

    // Headers.
    $headers = [
        get_string('col_category', 'report_gradeitems'),
        get_string('col_categorypath', 'report_gradeitems'),
        get_string('col_courseshortname', 'report_gradeitems'),
        get_string('col_coursefullname', 'report_gradeitems'),
        get_string('col_coursevisible', 'report_gradeitems'),
        get_string('col_coursestartdate', 'report_gradeitems'),
        get_string('col_courseenddate', 'report_gradeitems'),
        get_string('col_enrolledstudents', 'report_gradeitems'),
        get_string('col_teachers', 'report_gradeitems'),
        get_string('col_totalactivities', 'report_gradeitems'),
        get_string('col_gradeableactivities', 'report_gradeitems'),
    ];

    $csvexport->add_data($headers);

    // Data.
    foreach ($records as $record) {
        $teachers = isset($teachersbycourse[$record->courseid]) ? $teachersbycourse[$record->courseid] : '';
        $categorypath = get_category_path($record->categoryid);

        $row = [
            format_string($record->categoryname),
            $categorypath,
            format_string($record->courseshortname),
            format_string($record->coursefullname),
            $record->coursevisible ? get_string('yes') : get_string('no'),
            $record->coursestartdate ? userdate($record->coursestartdate, '%Y-%m-%d') : '',
            $record->courseenddate ? userdate($record->courseenddate, '%Y-%m-%d') : '',
            $record->enrolledstudents,
            $teachers,
            $record->totalactivities,
            $record->gradeableactivities,
        ];

        $csvexport->add_data($row);
    }

    $csvexport->download_file();
}
