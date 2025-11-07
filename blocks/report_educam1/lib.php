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
 * Library functions for report_educam1
 *
 * @package    block_report_educam1
 * @copyright  2025 IngeWeb - Soluciones para triunfar en Internet
 * @author     Alonso Arias <soporte@ingeweb.co>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/excellib.class.php');
require_once($CFG->libdir . '/odslib.class.php');
require_once($CFG->libdir . '/csvlib.class.php');
require_once($CFG->libdir . '/completionlib.php');

/**
 * Get the proper name for an activity type.
 *
 * @param string $modname Module name
 * @return string Localized activity type name
 */
function report_educam1_get_activity_type_name($modname) {
    if (empty($modname)) {
        return '';
    }
    return get_string('modulename', 'mod_' . $modname);
}

/**
 * Get all students enrolled in a course.
 *
 * @param int $courseid Course ID
 * @return array Array of user objects
 */
function report_educam1_get_course_students($courseid) {
    global $DB;

    $context = context_course::instance($courseid);

    // Get all users enrolled with student role
    $sql = "SELECT DISTINCT u.id, u.idnumber, u.firstname, u.lastname, u.email
            FROM {user} u
            JOIN {user_enrolments} ue ON ue.userid = u.id
            JOIN {enrol} e ON e.id = ue.enrolid
            JOIN {role_assignments} ra ON ra.userid = u.id
            JOIN {context} ctx ON ctx.id = ra.contextid
            JOIN {role} r ON r.id = ra.roleid
            WHERE e.courseid = :courseid
              AND ctx.contextlevel = :contextlevel
              AND ctx.instanceid = :instanceid
              AND r.archetype = 'student'
              AND u.deleted = 0
              AND u.suspended = 0
              AND ue.status = 0
            ORDER BY u.lastname, u.firstname";

    $params = [
        'courseid' => $courseid,
        'contextlevel' => CONTEXT_COURSE,
        'instanceid' => $courseid
    ];

    return $DB->get_records_sql($sql, $params);
}

/**
 * Get all activities of a specific type in a course.
 *
 * @param int $courseid Course ID
 * @param string $modname Module name (e.g., 'assign', 'quiz', 'scorm')
 * @return array Array of activity objects
 */
function report_educam1_get_course_activities($courseid, $modname) {
    global $DB;

    if (empty($modname)) {
        return [];
    }

    $sql = "SELECT cm.id AS cmid, cm.instance, m.name AS modname,
                   COALESCE(a.name, '') AS activityname,
                   cm.completion
            FROM {course_modules} cm
            JOIN {modules} m ON m.id = cm.module
            LEFT JOIN {" . $modname . "} a ON a.id = cm.instance
            WHERE cm.course = :courseid
              AND m.name = :modname
              AND cm.deletioninprogress = 0
            ORDER BY cm.id";

    $params = [
        'courseid' => $courseid,
        'modname' => $modname
    ];

    return $DB->get_records_sql($sql, $params);
}

/**
 * Get completion data for a specific student and activity.
 *
 * @param int $userid User ID
 * @param int $cmid Course module ID
 * @return object|null Completion data object or null
 */
function report_educam1_get_completion_data($userid, $cmid) {
    global $DB;

    $completion = $DB->get_record('course_modules_completion', [
        'coursemoduleid' => $cmid,
        'userid' => $userid
    ]);

    if (!$completion) {
        return null;
    }

    return $completion;
}

/**
 * Check if a student has completed an activity.
 *
 * @param int $userid User ID
 * @param int $cmid Course module ID
 * @return bool True if completed, false otherwise
 */
function report_educam1_is_activity_completed($userid, $cmid) {
    $completion = report_educam1_get_completion_data($userid, $cmid);

    if (!$completion) {
        return false;
    }

    return ($completion->completionstate == COMPLETION_COMPLETE ||
            $completion->completionstate == COMPLETION_COMPLETE_PASS);
}

/**
 * Get completion date for a student's activity.
 *
 * @param int $userid User ID
 * @param int $cmid Course module ID
 * @return int|null Completion timestamp or null
 */
function report_educam1_get_completion_date($userid, $cmid) {
    $completion = report_educam1_get_completion_data($userid, $cmid);

    if (!$completion || empty($completion->timemodified)) {
        return null;
    }

    if ($completion->completionstate == COMPLETION_COMPLETE ||
        $completion->completionstate == COMPLETION_COMPLETE_PASS) {
        return $completion->timemodified;
    }

    return null;
}

/**
 * Get individual view data for all students and activities.
 *
 * @param int $courseid Course ID
 * @param string $activitytype Activity type (module name)
 * @param int|null $specificactivity Specific activity CM ID (optional)
 * @param array $filters Filters to apply (idnumber, firstname, lastname, status)
 * @return array Array of student completion data
 */
function report_educam1_get_individual_view_data($courseid, $activitytype, $specificactivity = null, $filters = []) {
    $students = report_educam1_get_course_students($courseid);
    $activities = report_educam1_get_course_activities($courseid, $activitytype);

    if (empty($students) || empty($activities)) {
        return [];
    }

    // Apply student filters
    if (!empty($filters['idnumber'])) {
        $students = array_filter($students, function($student) use ($filters) {
            return stripos($student->idnumber, $filters['idnumber']) !== false;
        });
    }
    if (!empty($filters['firstname'])) {
        $students = array_filter($students, function($student) use ($filters) {
            return stripos($student->firstname, $filters['firstname']) !== false;
        });
    }
    if (!empty($filters['lastname'])) {
        $students = array_filter($students, function($student) use ($filters) {
            return stripos($student->lastname, $filters['lastname']) !== false;
        });
    }

    // Filter to specific activity if provided
    if (!empty($specificactivity)) {
        $activities = array_filter($activities, function($activity) use ($specificactivity) {
            return $activity->cmid == $specificactivity;
        });
    }

    $data = [];

    foreach ($students as $student) {
        foreach ($activities as $activity) {
            $completed = report_educam1_is_activity_completed($student->id, $activity->cmid);
            $completiondate = report_educam1_get_completion_date($student->id, $activity->cmid);

            // Apply status filter
            if (!empty($filters['status'])) {
                if ($filters['status'] === 'completed' && !$completed) {
                    continue;
                }
                if ($filters['status'] === 'not_completed' && $completed) {
                    continue;
                }
            }

            $row = new stdClass();
            $row->userid = $student->id;
            $row->idnumber = $student->idnumber;
            $row->firstname = $student->firstname;
            $row->lastname = $student->lastname;
            $row->email = $student->email;
            $row->activityid = $activity->cmid;
            $row->activityname = $activity->activityname;
            $row->completed = $completed;
            $row->completiondate = $completiondate;

            $data[] = $row;
        }
    }

    return $data;
}

/**
 * Get matrix view data for all students and activities.
 *
 * @param int $courseid Course ID
 * @param string $activitytype Activity type (module name)
 * @param array $filters Filters to apply (idnumber, firstname, lastname)
 * @return array Associative array with 'students' and 'activities' keys
 */
function report_educam1_get_matrix_view_data($courseid, $activitytype, $filters = []) {
    $students = report_educam1_get_course_students($courseid);
    $activities = report_educam1_get_course_activities($courseid, $activitytype);

    if (empty($students) || empty($activities)) {
        return ['students' => [], 'activities' => [], 'completions' => []];
    }

    // Apply student filters
    if (!empty($filters['idnumber'])) {
        $students = array_filter($students, function($student) use ($filters) {
            return stripos($student->idnumber, $filters['idnumber']) !== false;
        });
    }
    if (!empty($filters['firstname'])) {
        $students = array_filter($students, function($student) use ($filters) {
            return stripos($student->firstname, $filters['firstname']) !== false;
        });
    }
    if (!empty($filters['lastname'])) {
        $students = array_filter($students, function($student) use ($filters) {
            return stripos($student->lastname, $filters['lastname']) !== false;
        });
    }

    $completions = [];

    foreach ($students as $student) {
        $completions[$student->id] = [];
        foreach ($activities as $activity) {
            $completed = report_educam1_is_activity_completed($student->id, $activity->cmid);
            $completions[$student->id][$activity->cmid] = $completed;
        }
    }

    return [
        'students' => array_values($students),
        'activities' => array_values($activities),
        'completions' => $completions
    ];
}

/**
 * Export individual view data to spreadsheet format.
 *
 * @param array $data Individual view data
 * @param string $filename Base filename
 * @param string $format Format ('excel' or 'ods')
 * @param string $sheetname Sheet name
 */
function report_educam1_export_individual_spreadsheet($data, $filename, $format, $sheetname = 'Report') {
    global $CFG;

    // Clear output buffers
    while (ob_get_level()) {
        ob_end_clean();
    }

    if ($format === 'excel') {
        $workbook = new MoodleExcelWorkbook("-");
        $filename .= '.xlsx';
    } else {
        $workbook = new MoodleODSWorkbook("-");
        $filename .= '.ods';
    }

    $workbook->send($filename);
    $worksheet = $workbook->add_worksheet($sheetname);

    // Set column widths
    $worksheet->set_column(0, 0, 15);  // ID Number
    $worksheet->set_column(1, 1, 20);  // First name
    $worksheet->set_column(2, 2, 20);  // Last name
    $worksheet->set_column(3, 3, 30);  // Email
    $worksheet->set_column(4, 4, 40);  // Activity name
    $worksheet->set_column(5, 5, 20);  // Completion date
    $worksheet->set_column(6, 6, 15);  // Status

    // Header format
    $headerformat = $workbook->add_format();
    $headerformat->set_bold();
    $headerformat->set_align('center');
    $headerformat->set_align('vcenter');
    $headerformat->set_bg_color('#CCCCCC');

    // Prepare headers
    $headers = [
        get_string('column_idnumber', 'block_report_educam1'),
        get_string('column_firstname', 'block_report_educam1'),
        get_string('column_lastname', 'block_report_educam1'),
        get_string('column_email', 'block_report_educam1'),
        'Actividad',
        get_string('column_completion_date', 'block_report_educam1'),
        get_string('column_status', 'block_report_educam1')
    ];

    // Write headers
    for ($i = 0; $i < count($headers); $i++) {
        $worksheet->write(0, $i, $headers[$i], $headerformat);
    }

    // Write data
    $row = 1;
    foreach ($data as $item) {
        $worksheet->write($row, 0, $item->idnumber);
        $worksheet->write($row, 1, $item->firstname);
        $worksheet->write($row, 2, $item->lastname);
        $worksheet->write($row, 3, $item->email);
        $worksheet->write($row, 4, $item->activityname);

        $completiondate = $item->completiondate ? userdate($item->completiondate, '%Y-%m-%d %H:%M') : '';
        $worksheet->write($row, 5, $completiondate);

        $status = $item->completed ?
            get_string('status_completed', 'block_report_educam1') :
            get_string('status_not_completed', 'block_report_educam1');
        $worksheet->write($row, 6, $status);

        $row++;
    }

    $workbook->close();
    exit;
}

/**
 * Export individual view data to CSV format.
 *
 * @param array $data Individual view data
 * @param string $filename Base filename
 */
function report_educam1_export_individual_csv($data, $filename) {
    // Clear output buffers
    while (ob_get_level()) {
        ob_end_clean();
    }

    $filename .= '.csv';

    header("Content-Type: text/csv; charset=utf-8");
    header("Content-Disposition: attachment; filename=\"$filename\"");
    header("Expires: 0");
    header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
    header("Pragma: public");

    $output = fopen('php://output', 'w');

    // Write BOM for UTF-8
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

    // Write headers
    $headers = [
        get_string('column_idnumber', 'block_report_educam1'),
        get_string('column_firstname', 'block_report_educam1'),
        get_string('column_lastname', 'block_report_educam1'),
        get_string('column_email', 'block_report_educam1'),
        'Actividad',
        get_string('column_completion_date', 'block_report_educam1'),
        get_string('column_status', 'block_report_educam1')
    ];
    fputcsv($output, $headers);

    // Write data
    foreach ($data as $item) {
        $completiondate = $item->completiondate ? userdate($item->completiondate, '%Y-%m-%d %H:%M') : '';
        $status = $item->completed ?
            get_string('status_completed', 'block_report_educam1') :
            get_string('status_not_completed', 'block_report_educam1');

        fputcsv($output, [
            $item->idnumber,
            $item->firstname,
            $item->lastname,
            $item->email,
            $item->activityname,
            $completiondate,
            $status
        ]);
    }

    fclose($output);
    exit;
}

/**
 * Export matrix view data to spreadsheet format.
 *
 * @param array $matrixdata Matrix view data
 * @param string $filename Base filename
 * @param string $format Format ('excel' or 'ods')
 * @param string $sheetname Sheet name
 */
function report_educam1_export_matrix_spreadsheet($matrixdata, $filename, $format, $sheetname = 'Report') {
    global $CFG;

    // Clear output buffers
    while (ob_get_level()) {
        ob_end_clean();
    }

    if ($format === 'excel') {
        $workbook = new MoodleExcelWorkbook("-");
        $filename .= '.xlsx';
    } else {
        $workbook = new MoodleODSWorkbook("-");
        $filename .= '.ods';
    }

    $workbook->send($filename);
    $worksheet = $workbook->add_worksheet($sheetname);

    $students = $matrixdata['students'];
    $activities = $matrixdata['activities'];
    $completions = $matrixdata['completions'];

    // Set column widths
    $worksheet->set_column(0, 0, 15);  // ID Number
    $worksheet->set_column(1, 1, 20);  // First name
    $worksheet->set_column(2, 2, 20);  // Last name
    $worksheet->set_column(3, 3, 30);  // Email

    // Header format
    $headerformat = $workbook->add_format();
    $headerformat->set_bold();
    $headerformat->set_align('center');
    $headerformat->set_align('vcenter');
    $headerformat->set_bg_color('#CCCCCC');

    // Prepare headers
    $headers = [
        get_string('column_idnumber', 'block_report_educam1'),
        get_string('column_firstname', 'block_report_educam1'),
        get_string('column_lastname', 'block_report_educam1'),
        get_string('column_email', 'block_report_educam1')
    ];

    // Add activity headers
    foreach ($activities as $activity) {
        $headers[] = $activity->activityname;
    }

    // Write headers
    for ($i = 0; $i < count($headers); $i++) {
        $worksheet->write(0, $i, $headers[$i], $headerformat);
    }

    // Write data
    $row = 1;
    foreach ($students as $student) {
        $col = 0;
        $worksheet->write($row, $col++, $student->idnumber);
        $worksheet->write($row, $col++, $student->firstname);
        $worksheet->write($row, $col++, $student->lastname);
        $worksheet->write($row, $col++, $student->email);

        foreach ($activities as $activity) {
            $completed = isset($completions[$student->id][$activity->cmid]) ?
                $completions[$student->id][$activity->cmid] : false;
            $symbol = $completed ?
                get_string('completed_symbol', 'block_report_educam1') : '';
            $worksheet->write($row, $col++, $symbol);
        }

        $row++;
    }

    $workbook->close();
    exit;
}

/**
 * Export matrix view data to CSV format.
 *
 * @param array $matrixdata Matrix view data
 * @param string $filename Base filename
 */
function report_educam1_export_matrix_csv($matrixdata, $filename) {
    // Clear output buffers
    while (ob_get_level()) {
        ob_end_clean();
    }

    $filename .= '.csv';

    header("Content-Type: text/csv; charset=utf-8");
    header("Content-Disposition: attachment; filename=\"$filename\"");
    header("Expires: 0");
    header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
    header("Pragma: public");

    $output = fopen('php://output', 'w');

    // Write BOM for UTF-8
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

    $students = $matrixdata['students'];
    $activities = $matrixdata['activities'];
    $completions = $matrixdata['completions'];

    // Prepare headers
    $headers = [
        get_string('column_idnumber', 'block_report_educam1'),
        get_string('column_firstname', 'block_report_educam1'),
        get_string('column_lastname', 'block_report_educam1'),
        get_string('column_email', 'block_report_educam1')
    ];

    // Add activity headers
    foreach ($activities as $activity) {
        $headers[] = $activity->activityname;
    }

    fputcsv($output, $headers);

    // Write data
    foreach ($students as $student) {
        $row = [
            $student->idnumber,
            $student->firstname,
            $student->lastname,
            $student->email
        ];

        foreach ($activities as $activity) {
            $completed = isset($completions[$student->id][$activity->cmid]) ?
                $completions[$student->id][$activity->cmid] : false;
            $symbol = $completed ?
                get_string('completed_symbol', 'block_report_educam1') : '';
            $row[] = $symbol;
        }

        fputcsv($output, $row);
    }

    fclose($output);
    exit;
}
