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
 * Grade upload page for Q10 Grades Sync plugin.
 *
 * @package    local_q10grades
 * @copyright  2025 Your Institution
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/gradelib.php');

$courseid = required_param('courseid', PARAM_INT);
$action = optional_param('action', 'preview', PARAM_ALPHA);
$formulaid = optional_param('formulaid', 0, PARAM_INT);
$confirm = optional_param('confirm', 0, PARAM_BOOL);

$course = $DB->get_record('course', ['id' => $courseid], '*', MUST_EXIST);
$context = context_course::instance($courseid);

require_login($course);
require_capability('local/q10grades:sync', $context);

$PAGE->set_url(new moodle_url('/local/q10grades/upload.php', ['courseid' => $courseid]));
$PAGE->set_context($context);
$PAGE->set_course($course);
$PAGE->set_pagelayout('incourse');
$PAGE->set_title(get_string('uploadgrades', 'local_q10grades'));
$PAGE->set_heading($course->fullname);

$PAGE->navbar->add(get_string('syncgrades', 'local_q10grades'),
    new moodle_url('/local/q10grades/sync.php', ['courseid' => $courseid]));
$PAGE->navbar->add(get_string('uploadgrades', 'local_q10grades'));

// Check configuration.
$apiclient = new \local_q10grades\q10_api_client();
$syncmanager = new \local_q10grades\grade_sync_manager($courseid);
$calculator = new \local_q10grades\formula_calculator($courseid);

$formulas = $calculator->get_formulas(true);

// Handle upload action.
if ($action === 'upload' && confirm_sesskey()) {
    if (!$apiclient->is_configured()) {
        redirect(
            new moodle_url('/local/q10grades/upload.php', ['courseid' => $courseid]),
            get_string('apinotconfigured', 'local_q10grades'),
            null,
            \core\output\notification::NOTIFY_ERROR
        );
    }

    if (!$syncmanager->has_mapping()) {
        redirect(
            new moodle_url('/local/q10grades/upload.php', ['courseid' => $courseid]),
            get_string('nomapping', 'local_q10grades'),
            null,
            \core\output\notification::NOTIFY_ERROR
        );
    }

    $mapping = $syncmanager->get_mapping();
    $usermappingfield = get_config('local_q10grades', 'usermappingfield') ?: 'idnumber';

    $totaluploaded = 0;
    $totalerrors = 0;
    $errors = [];

    // Upload grades for each formula.
    foreach ($formulas as $formula) {
        $calculated = $calculator->calculate_formula_grades($formula);

        if (empty($calculated)) {
            continue;
        }

        $gradestosend = [];

        foreach ($calculated as $userid => $data) {
            $student = $data['user'];
            $q10studentid = $student->$usermappingfield ?? '';

            if (empty($q10studentid)) {
                $errors[] = get_string('nostudentid', 'local_q10grades', fullname($student));
                $totalerrors++;
                continue;
            }

            $gradestosend[] = [
                'student_id' => $q10studentid,
                'grade' => $data['calculated_grade'],
            ];
        }

        if (!empty($gradestosend)) {
            try {
                $response = $apiclient->upload_grades(
                    $mapping->q10_subject_id,
                    $formula->q10_component_id,
                    $gradestosend,
                    $mapping->q10_period_id
                );

                $totaluploaded += count($gradestosend);

                // Log success.
                foreach ($calculated as $userid => $data) {
                    $syncmanager->log_sync_public(
                        $userid,
                        null,
                        $data['calculated_grade'],
                        'success',
                        null,
                        'manual',
                        $USER->id,
                        json_encode($response)
                    );
                }
            } catch (\Exception $e) {
                $errors[] = $formula->name . ': ' . $e->getMessage();
                $totalerrors += count($gradestosend);

                // Log error.
                foreach ($calculated as $userid => $data) {
                    $syncmanager->log_sync_public(
                        $userid,
                        null,
                        $data['calculated_grade'],
                        'error',
                        $e->getMessage(),
                        'manual',
                        $USER->id
                    );
                }
            }
        }
    }

    $message = get_string('uploadcomplete', 'local_q10grades', [
        'uploaded' => $totaluploaded,
        'errors' => $totalerrors,
    ]);

    if (!empty($errors)) {
        $message .= '<br>' . implode('<br>', $errors);
    }

    redirect(
        new moodle_url('/local/q10grades/upload.php', ['courseid' => $courseid]),
        $message,
        null,
        $totalerrors > 0 ? \core\output\notification::NOTIFY_WARNING : \core\output\notification::NOTIFY_SUCCESS
    );
}

echo $OUTPUT->header();

echo $OUTPUT->heading(get_string('uploadgrades', 'local_q10grades'));

// Navigation tabs.
$tabs = [];
$tabs[] = new tabobject('sync',
    new moodle_url('/local/q10grades/sync.php', ['courseid' => $courseid]),
    get_string('syncgrades', 'local_q10grades'));
$tabs[] = new tabobject('mapping',
    new moodle_url('/local/q10grades/mapping.php', ['courseid' => $courseid]),
    get_string('coursemapping', 'local_q10grades'));
$tabs[] = new tabobject('formulas',
    new moodle_url('/local/q10grades/formulas.php', ['courseid' => $courseid]),
    get_string('formulas', 'local_q10grades'));
$tabs[] = new tabobject('upload',
    new moodle_url('/local/q10grades/upload.php', ['courseid' => $courseid]),
    get_string('uploadgrades', 'local_q10grades'));
$tabs[] = new tabobject('history',
    new moodle_url('/local/q10grades/history.php', ['courseid' => $courseid]),
    get_string('synchistory', 'local_q10grades'));

echo $OUTPUT->tabtree($tabs, 'upload');

// Warnings.
if (!$apiclient->is_configured()) {
    echo $OUTPUT->notification(
        get_string('apinotconfigured', 'local_q10grades') . ' ' .
        html_writer::link(
            new moodle_url('/admin/settings.php', ['section' => 'local_q10grades']),
            get_string('configureapi', 'local_q10grades')
        ),
        'warning'
    );
}

if (!$syncmanager->has_mapping()) {
    echo $OUTPUT->notification(
        get_string('nomapping', 'local_q10grades') . ' ' .
        html_writer::link(
            new moodle_url('/local/q10grades/mapping.php', ['courseid' => $courseid]),
            get_string('configuremapping', 'local_q10grades')
        ),
        'warning'
    );
}

if (empty($formulas)) {
    echo $OUTPUT->notification(
        get_string('noformulas', 'local_q10grades') . ' ' .
        html_writer::link(
            new moodle_url('/local/q10grades/formulas.php', ['courseid' => $courseid, 'action' => 'add']),
            get_string('addformula', 'local_q10grades')
        ),
        'warning'
    );
    echo $OUTPUT->footer();
    exit;
}

// Preview table.
echo html_writer::tag('h4', get_string('gradepreview', 'local_q10grades'));
echo html_writer::tag('p', get_string('gradepreview_desc', 'local_q10grades'));

$preview = $calculator->get_preview_data();

// Build the preview table.
$table = new html_table();
$table->attributes['class'] = 'table table-striped table-bordered';

// Headers.
$headers = [
    get_string('student', 'local_q10grades'),
    get_string('idnumber'),
];

foreach ($preview['formulas'] as $formula) {
    $headers[] = $formula['name'] . '<br><small class="text-muted">' . $formula['q10_component_id'] . '</small>';
}

$table->head = $headers;

// Data rows.
foreach ($preview['students'] as $studentid => $student) {
    $row = [
        $student['fullname'],
        $student['q10_id'] ?: html_writer::tag('span', get_string('notset', 'local_q10grades'), ['class' => 'text-warning']),
    ];

    foreach ($preview['formulas'] as $formulaid => $formula) {
        if (isset($preview['grades'][$studentid][$formulaid])) {
            $grade = $preview['grades'][$studentid][$formulaid];
            $row[] = html_writer::tag('span', number_format($grade, 2), ['class' => 'badge badge-info']);
        } else {
            $row[] = html_writer::tag('span', '-', ['class' => 'text-muted']);
        }
    }

    $table->data[] = $row;
}

if (empty($table->data)) {
    echo $OUTPUT->notification(get_string('nostudentgrades', 'local_q10grades'), 'info');
} else {
    echo html_writer::table($table);
}

// Formula details accordion.
echo html_writer::tag('h4', get_string('formuladetails', 'local_q10grades'), ['class' => 'mt-4']);

foreach ($preview['formulas'] as $formula) {
    $formulatypes = [
        'average' => get_string('formulaaverage', 'local_q10grades'),
        'weighted' => get_string('formulaweighted', 'local_q10grades'),
        'sum' => get_string('formulasum', 'local_q10grades'),
        'highest' => get_string('formulahighest', 'local_q10grades'),
        'lowest' => get_string('formulalowest', 'local_q10grades'),
        'custom' => get_string('formulacustom', 'local_q10grades'),
    ];

    echo html_writer::start_div('card mb-2');
    echo html_writer::start_div('card-body');
    echo html_writer::tag('h5', $formula['name'], ['class' => 'card-title']);

    echo html_writer::start_tag('ul', ['class' => 'list-unstyled mb-0']);
    echo html_writer::tag('li', '<strong>' . get_string('q10componentid', 'local_q10grades') . ':</strong> ' . $formula['q10_component_id']);
    echo html_writer::tag('li', '<strong>' . get_string('formulatype', 'local_q10grades') . ':</strong> ' . ($formulatypes[$formula['formula_type']] ?? $formula['formula_type']));
    echo html_writer::tag('li', '<strong>' . get_string('selectedactivities', 'local_q10grades') . ':</strong> ' . implode(', ', $formula['items']));
    echo html_writer::end_tag('ul');

    echo html_writer::end_div();
    echo html_writer::end_div();
}

// Upload button.
if ($apiclient->is_configured() && $syncmanager->has_mapping() && !empty($table->data)) {
    echo html_writer::start_div('mt-4');

    $uploadurl = new moodle_url('/local/q10grades/upload.php', [
        'courseid' => $courseid,
        'action' => 'upload',
        'sesskey' => sesskey(),
    ]);

    echo html_writer::tag('div',
        html_writer::link(
            $uploadurl,
            get_string('uploadtoq10', 'local_q10grades'),
            [
                'class' => 'btn btn-primary btn-lg',
                'onclick' => "return confirm('" . get_string('uploadconfirm', 'local_q10grades') . "')",
            ]
        ),
        ['class' => 'text-center']
    );

    echo html_writer::end_div();
}

echo $OUTPUT->footer();
