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
 * Grade Items Report - Course detail view.
 *
 * This page displays all gradeable activities for a specific course.
 *
 * @package    report_gradeitems
 * @copyright  2026 Alonso Arias <soporte@orioncloud.com.co>
 * @author     Alonso Arias <soporte@orioncloud.com.co>
 * @link       https://orioncloud.com.co
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/adminlib.php');
require_once($CFG->libdir . '/excellib.class.php');

// Parameters.
$courseid = required_param('id', PARAM_INT);
$download = optional_param('download', '', PARAM_ALPHA);

// Get course.
$course = $DB->get_record('course', ['id' => $courseid], '*', MUST_EXIST);
$category = $DB->get_record('course_categories', ['id' => $course->category]);

// Setup page.
admin_externalpage_setup('reportgradeitems', '', null, '', ['pagelayout' => 'report']);

$context = context_system::instance();
require_capability('report/gradeitems:view', $context);

$PAGE->set_url(new moodle_url('/report/gradeitems/course.php', ['id' => $courseid]));
$PAGE->navbar->add(get_string('pluginname', 'report_gradeitems'),
    new moodle_url('/report/gradeitems/index.php'));
$PAGE->navbar->add(format_string($course->shortname));

$baseurl = new moodle_url('/report/gradeitems/course.php', ['id' => $courseid]);

// Get course info.
$params = ['courseid' => $courseid, 'timenow' => time()];

// Get enrolled students count.
$enrolledsql = "SELECT COUNT(DISTINCT ue.userid)
                  FROM {user_enrolments} ue
                  JOIN {enrol} e ON e.id = ue.enrolid
                 WHERE e.courseid = :courseid
                   AND e.status = 0
                   AND ue.status = 0
                   AND (ue.timeend = 0 OR ue.timeend > :timenow)";
$enrolledcount = $DB->count_records_sql($enrolledsql, $params);

// Get teachers.
$teacherssql = "SELECT DISTINCT u.id, u.firstname, u.lastname
                  FROM {role_assignments} ra
                  JOIN {context} ctx ON ctx.id = ra.contextid
                  JOIN {user} u ON u.id = ra.userid
                 WHERE ctx.contextlevel = :contextlevel
                   AND ctx.instanceid = :courseid
                   AND ra.roleid IN (3, 4)
              ORDER BY u.lastname, u.firstname";
$teachers = $DB->get_records_sql($teacherssql, ['contextlevel' => CONTEXT_COURSE, 'courseid' => $courseid]);
$teachernames = [];
foreach ($teachers as $teacher) {
    $teachernames[] = fullname($teacher);
}

// Main SQL query - Gradeable activities for this course.
$sql = "SELECT
            gi.id AS gradeitemid,
            gi.itemname AS activityname,
            gi.itemmodule AS moduletype,
            gi.iteminstance,
            cm.id AS cmid,
            cm.visible AS activityvisible,
            cm.section AS sectionid,
            cs.name AS sectionname,
            cs.section AS sectionnumber,
            gi.gradetype,
            gi.grademax,
            gi.gradepass,
            gi.aggregationcoef AS gradeweight,
            gi.aggregationcoef2 AS gradeweight2,
            (SELECT COUNT(gg.id)
             FROM {grade_grades} gg
             WHERE gg.itemid = gi.id
               AND gg.finalgrade IS NOT NULL
            ) AS gradecount,
            (SELECT AVG(gg.finalgrade)
             FROM {grade_grades} gg
             WHERE gg.itemid = gi.id
               AND gg.finalgrade IS NOT NULL
            ) AS gradeaverage
        FROM {grade_items} gi
        JOIN {modules} m ON m.name = gi.itemmodule
        JOIN {course_modules} cm ON cm.course = gi.courseid
                                AND cm.module = m.id
                                AND cm.instance = gi.iteminstance
        LEFT JOIN {course_sections} cs ON cs.id = cm.section
       WHERE gi.courseid = :courseid
         AND gi.itemtype = 'mod'
         AND gi.gradetype > 0
    ORDER BY cs.section, gi.sortorder";

$records = $DB->get_records_sql($sql, ['courseid' => $courseid]);

// Handle Excel download.
if ($download === 'excel') {
    download_activities_excel($course, $category, $records, $teachernames, $enrolledcount);
    exit;
}

// Handle CSV download.
if ($download === 'csv') {
    download_activities_csv($course, $category, $records, $teachernames, $enrolledcount);
    exit;
}

// Output page.
echo $OUTPUT->header();

// Back button.
echo html_writer::start_div('mb-3');
echo html_writer::link(
    new moodle_url('/report/gradeitems/index.php'),
    get_string('backtocourselist', 'report_gradeitems'),
    ['class' => 'btn btn-secondary']
);
echo html_writer::end_div();

// Course heading.
echo $OUTPUT->heading(get_string('coursedetail', 'report_gradeitems') . ': ' . format_string($course->fullname));

// Course info box.
echo html_writer::start_div('card mb-4');
echo html_writer::start_div('card-body');

echo html_writer::tag('h5', get_string('courseinfo', 'report_gradeitems'), ['class' => 'card-title']);

$infohtml = html_writer::start_tag('dl', ['class' => 'row mb-0']);

// Category.
$infohtml .= html_writer::tag('dt', get_string('col_category', 'report_gradeitems'), ['class' => 'col-sm-3']);
$infohtml .= html_writer::tag('dd', format_string($category->name), ['class' => 'col-sm-9']);

// Short name.
$infohtml .= html_writer::tag('dt', get_string('col_courseshortname', 'report_gradeitems'), ['class' => 'col-sm-3']);
$infohtml .= html_writer::tag('dd', format_string($course->shortname), ['class' => 'col-sm-9']);

// Visibility.
$infohtml .= html_writer::tag('dt', get_string('col_coursevisible', 'report_gradeitems'), ['class' => 'col-sm-3']);
$visiblestr = $course->visible ? get_string('yes', 'report_gradeitems') : get_string('no', 'report_gradeitems');
$infohtml .= html_writer::tag('dd', $visiblestr, ['class' => 'col-sm-9']);

// Start date.
$infohtml .= html_writer::tag('dt', get_string('col_coursestartdate', 'report_gradeitems'), ['class' => 'col-sm-3']);
$startdate = $course->startdate ? userdate($course->startdate, get_string('strftimedateshort', 'langconfig')) : '-';
$infohtml .= html_writer::tag('dd', $startdate, ['class' => 'col-sm-9']);

// End date.
$infohtml .= html_writer::tag('dt', get_string('col_courseenddate', 'report_gradeitems'), ['class' => 'col-sm-3']);
$enddate = $course->enddate ? userdate($course->enddate, get_string('strftimedateshort', 'langconfig')) : '-';
$infohtml .= html_writer::tag('dd', $enddate, ['class' => 'col-sm-9']);

// Enrolled students.
$infohtml .= html_writer::tag('dt', get_string('col_enrolledstudents', 'report_gradeitems'), ['class' => 'col-sm-3']);
$infohtml .= html_writer::tag('dd', $enrolledcount, ['class' => 'col-sm-9']);

// Teachers.
$infohtml .= html_writer::tag('dt', get_string('col_teachers', 'report_gradeitems'), ['class' => 'col-sm-3']);
$teacherstr = !empty($teachernames) ? implode(', ', $teachernames) : '-';
$infohtml .= html_writer::tag('dd', $teacherstr, ['class' => 'col-sm-9']);

$infohtml .= html_writer::end_tag('dl');

echo $infohtml;
echo html_writer::end_div();
echo html_writer::end_div();

// Export buttons.
echo html_writer::start_div('mb-3');
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

// Activities heading.
echo html_writer::tag('h4', get_string('gradeableactivities', 'report_gradeitems'));

$activitycount = count($records);
echo html_writer::tag('p', get_string('totalactivities_count', 'report_gradeitems', $activitycount),
    ['class' => 'font-weight-bold']);

if ($activitycount == 0) {
    echo $OUTPUT->notification(get_string('noactivitiesfound', 'report_gradeitems'), 'info');
} else {
    // Display table.
    $table = new html_table();
    $table->head = [
        get_string('col_section', 'report_gradeitems'),
        get_string('col_activityname', 'report_gradeitems'),
        get_string('col_moduletype', 'report_gradeitems'),
        get_string('col_activityvisible', 'report_gradeitems'),
        get_string('col_gradetype', 'report_gradeitems'),
        get_string('col_grademax', 'report_gradeitems'),
        get_string('col_gradepass', 'report_gradeitems'),
        get_string('col_gradeweight', 'report_gradeitems'),
        get_string('col_gradecount', 'report_gradeitems'),
        get_string('col_gradeaverage', 'report_gradeitems'),
    ];
    $table->attributes['class'] = 'table table-striped table-hover table-sm';
    $table->data = [];

    foreach ($records as $record) {
        // Get module display name.
        $modname = get_string('pluginname', 'mod_' . $record->moduletype);

        // Format grade type.
        $gradetypestr = format_grade_type($record->gradetype);

        // Format section.
        $section = $record->sectionname ?: get_string('section') . ' ' . $record->sectionnumber;

        // Calculate weight percentage.
        $weight = ($record->gradeweight2 > 0) ? round($record->gradeweight2 * 100, 2) : round($record->gradeweight, 2);

        $table->data[] = [
            $section,
            html_writer::link(
                new moodle_url('/mod/' . $record->moduletype . '/view.php', ['id' => $record->cmid]),
                format_string($record->activityname),
                ['target' => '_blank']
            ),
            $modname,
            $record->activityvisible ? get_string('yes', 'report_gradeitems') : get_string('no', 'report_gradeitems'),
            $gradetypestr,
            format_float($record->grademax, 2),
            format_float($record->gradepass, 2),
            $weight . '%',
            $record->gradecount,
            $record->gradeaverage !== null ? format_float($record->gradeaverage, 2) : '-',
        ];
    }

    echo html_writer::table($table);
}

echo $OUTPUT->footer();

/**
 * Format grade type to string.
 *
 * @param int $gradetype
 * @return string
 */
function format_grade_type(int $gradetype): string {
    switch ($gradetype) {
        case 1:
            return get_string('gradetype_value', 'report_gradeitems');
        case 2:
            return get_string('gradetype_scale', 'report_gradeitems');
        case 3:
            return get_string('gradetype_text', 'report_gradeitems');
        default:
            return get_string('gradetype_none', 'report_gradeitems');
    }
}

/**
 * Download activities report as Excel file.
 *
 * @param stdClass $course
 * @param stdClass $category
 * @param array $records
 * @param array $teachernames
 * @param int $enrolledcount
 */
function download_activities_excel($course, $category, array $records, array $teachernames, int $enrolledcount): void {
    // Create workbook.
    $filename = 'course_activities_' . $course->shortname . '_' . date('Y-m-d_His');
    $workbook = new MoodleExcelWorkbook($filename);

    // Add worksheet.
    $worksheet = $workbook->add_worksheet(core_text::substr($course->shortname, 0, 31));

    // Header format.
    $headerformat = $workbook->add_format([
        'bold' => 1,
        'bg_color' => '#4472C4',
        'color' => 'white',
        'align' => 'center',
        'border' => 1,
    ]);

    // Info format.
    $infoformat = $workbook->add_format(['bold' => 1]);

    // Data format.
    $dataformat = $workbook->add_format(['border' => 1]);
    $numformat = $workbook->add_format(['border' => 1, 'align' => 'right']);

    // Course info section.
    $row = 0;
    $worksheet->write_string($row, 0, get_string('col_coursefullname', 'report_gradeitems') . ':', $infoformat);
    $worksheet->write_string($row++, 1, format_string($course->fullname));
    $worksheet->write_string($row, 0, get_string('col_courseshortname', 'report_gradeitems') . ':', $infoformat);
    $worksheet->write_string($row++, 1, format_string($course->shortname));
    $worksheet->write_string($row, 0, get_string('col_category', 'report_gradeitems') . ':', $infoformat);
    $worksheet->write_string($row++, 1, format_string($category->name));
    $worksheet->write_string($row, 0, get_string('col_enrolledstudents', 'report_gradeitems') . ':', $infoformat);
    $worksheet->write_number($row++, 1, $enrolledcount);
    $worksheet->write_string($row, 0, get_string('col_teachers', 'report_gradeitems') . ':', $infoformat);
    $worksheet->write_string($row++, 1, implode(', ', $teachernames));

    $row++; // Empty row.

    // Headers.
    $headers = [
        get_string('col_section', 'report_gradeitems'),
        get_string('col_activityname', 'report_gradeitems'),
        get_string('col_moduletype', 'report_gradeitems'),
        get_string('col_activityvisible', 'report_gradeitems'),
        get_string('col_gradetype', 'report_gradeitems'),
        get_string('col_grademax', 'report_gradeitems'),
        get_string('col_gradepass', 'report_gradeitems'),
        get_string('col_gradeweight', 'report_gradeitems'),
        get_string('col_gradecount', 'report_gradeitems'),
        get_string('col_gradeaverage', 'report_gradeitems'),
        get_string('col_cmid', 'report_gradeitems'),
    ];

    // Write headers.
    $col = 0;
    foreach ($headers as $header) {
        $worksheet->write_string($row, $col, $header, $headerformat);
        $col++;
    }
    $row++;

    // Set column widths.
    $worksheet->set_column(0, 0, 20);
    $worksheet->set_column(1, 1, 40);
    $worksheet->set_column(2, 2, 15);
    $worksheet->set_column(3, 3, 12);
    $worksheet->set_column(4, 4, 12);
    $worksheet->set_column(5, 9, 12);
    $worksheet->set_column(10, 10, 10);

    // Write data.
    foreach ($records as $record) {
        $modname = get_string('pluginname', 'mod_' . $record->moduletype);
        $gradetypestr = format_grade_type($record->gradetype);
        $section = $record->sectionname ?: get_string('section') . ' ' . $record->sectionnumber;
        $weight = ($record->gradeweight2 > 0) ? round($record->gradeweight2 * 100, 2) : round($record->gradeweight, 2);

        $col = 0;
        $worksheet->write_string($row, $col++, $section, $dataformat);
        $worksheet->write_string($row, $col++, format_string($record->activityname), $dataformat);
        $worksheet->write_string($row, $col++, $modname, $dataformat);
        $worksheet->write_string($row, $col++, $record->activityvisible ? get_string('yes') : get_string('no'), $dataformat);
        $worksheet->write_string($row, $col++, $gradetypestr, $dataformat);
        $worksheet->write_number($row, $col++, $record->grademax, $numformat);
        $worksheet->write_number($row, $col++, $record->gradepass, $numformat);
        $worksheet->write_number($row, $col++, $weight, $numformat);
        $worksheet->write_number($row, $col++, $record->gradecount, $numformat);
        if ($record->gradeaverage !== null) {
            $worksheet->write_number($row, $col++, round($record->gradeaverage, 2), $numformat);
        } else {
            $worksheet->write_string($row, $col++, '', $dataformat);
        }
        $worksheet->write_number($row, $col++, $record->cmid, $numformat);

        $row++;
    }

    $workbook->close();
}

/**
 * Download activities report as CSV file.
 *
 * @param stdClass $course
 * @param stdClass $category
 * @param array $records
 * @param array $teachernames
 * @param int $enrolledcount
 */
function download_activities_csv($course, $category, array $records, array $teachernames, int $enrolledcount): void {
    global $CFG;

    require_once($CFG->libdir . '/csvlib.class.php');

    $filename = 'course_activities_' . $course->shortname . '_' . date('Y-m-d_His');
    $csvexport = new csv_export_writer('comma');
    $csvexport->set_filename($filename);

    // Course info.
    $csvexport->add_data([get_string('col_coursefullname', 'report_gradeitems'), format_string($course->fullname)]);
    $csvexport->add_data([get_string('col_courseshortname', 'report_gradeitems'), format_string($course->shortname)]);
    $csvexport->add_data([get_string('col_category', 'report_gradeitems'), format_string($category->name)]);
    $csvexport->add_data([get_string('col_enrolledstudents', 'report_gradeitems'), $enrolledcount]);
    $csvexport->add_data([get_string('col_teachers', 'report_gradeitems'), implode(', ', $teachernames)]);
    $csvexport->add_data([]); // Empty row.

    // Headers.
    $headers = [
        get_string('col_section', 'report_gradeitems'),
        get_string('col_activityname', 'report_gradeitems'),
        get_string('col_moduletype', 'report_gradeitems'),
        get_string('col_activityvisible', 'report_gradeitems'),
        get_string('col_gradetype', 'report_gradeitems'),
        get_string('col_grademax', 'report_gradeitems'),
        get_string('col_gradepass', 'report_gradeitems'),
        get_string('col_gradeweight', 'report_gradeitems'),
        get_string('col_gradecount', 'report_gradeitems'),
        get_string('col_gradeaverage', 'report_gradeitems'),
        get_string('col_cmid', 'report_gradeitems'),
    ];

    $csvexport->add_data($headers);

    // Data.
    foreach ($records as $record) {
        $modname = get_string('pluginname', 'mod_' . $record->moduletype);
        $gradetypestr = format_grade_type($record->gradetype);
        $section = $record->sectionname ?: get_string('section') . ' ' . $record->sectionnumber;
        $weight = ($record->gradeweight2 > 0) ? round($record->gradeweight2 * 100, 2) : round($record->gradeweight, 2);

        $row = [
            $section,
            format_string($record->activityname),
            $modname,
            $record->activityvisible ? get_string('yes') : get_string('no'),
            $gradetypestr,
            $record->grademax,
            $record->gradepass,
            $weight,
            $record->gradecount,
            $record->gradeaverage !== null ? round($record->gradeaverage, 2) : '',
            $record->cmid,
        ];

        $csvexport->add_data($row);
    }

    $csvexport->download_file();
}
