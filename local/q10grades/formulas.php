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
 * Formula configuration page for Q10 Grades Sync plugin.
 *
 * @package    local_q10grades
 * @copyright  2025 Your Institution
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/gradelib.php');

$courseid = required_param('courseid', PARAM_INT);
$action = optional_param('action', 'list', PARAM_ALPHA);
$id = optional_param('id', 0, PARAM_INT);
$delete = optional_param('delete', 0, PARAM_INT);
$confirm = optional_param('confirm', 0, PARAM_BOOL);

$course = $DB->get_record('course', ['id' => $courseid], '*', MUST_EXIST);
$context = context_course::instance($courseid);

require_login($course);
require_capability('local/q10grades:sync', $context);

$PAGE->set_url(new moodle_url('/local/q10grades/formulas.php', ['courseid' => $courseid]));
$PAGE->set_context($context);
$PAGE->set_course($course);
$PAGE->set_pagelayout('incourse');
$PAGE->set_title(get_string('formulas', 'local_q10grades'));
$PAGE->set_heading($course->fullname);

$PAGE->navbar->add(get_string('syncgrades', 'local_q10grades'),
    new moodle_url('/local/q10grades/sync.php', ['courseid' => $courseid]));
$PAGE->navbar->add(get_string('formulas', 'local_q10grades'));

// Get grade items for the course.
$gradeitems = grade_item::fetch_all(['courseid' => $courseid]);

// Handle delete.
if ($delete && confirm_sesskey()) {
    $formula = $DB->get_record('local_q10grades_formula', ['id' => $delete, 'courseid' => $courseid], '*', MUST_EXIST);

    if ($confirm) {
        $DB->delete_records('local_q10grades_formula', ['id' => $delete]);
        redirect(
            new moodle_url('/local/q10grades/formulas.php', ['courseid' => $courseid]),
            get_string('formuladeleted', 'local_q10grades'),
            null,
            \core\output\notification::NOTIFY_SUCCESS
        );
    } else {
        echo $OUTPUT->header();
        echo $OUTPUT->heading(get_string('deleteformula', 'local_q10grades'));
        echo $OUTPUT->confirm(
            get_string('deleteformulaconfirm', 'local_q10grades', $formula->name),
            new moodle_url('/local/q10grades/formulas.php', [
                'courseid' => $courseid,
                'delete' => $delete,
                'confirm' => 1,
                'sesskey' => sesskey(),
            ]),
            new moodle_url('/local/q10grades/formulas.php', ['courseid' => $courseid])
        );
        echo $OUTPUT->footer();
        exit;
    }
}

// Handle add/edit.
if ($action === 'add' || $action === 'edit') {
    $formula = null;
    if ($action === 'edit' && $id) {
        $formula = $DB->get_record('local_q10grades_formula', ['id' => $id, 'courseid' => $courseid], '*', MUST_EXIST);
    }

    $form = new \local_q10grades\form\formula_config_form(null, [
        'courseid' => $courseid,
        'gradeitems' => $gradeitems,
        'formula' => $formula,
    ]);

    if ($form->is_cancelled()) {
        redirect(new moodle_url('/local/q10grades/formulas.php', ['courseid' => $courseid]));
    } else if ($data = $form->get_data()) {
        global $USER;

        $now = time();

        // Collect weights.
        $weights = [];
        foreach ($data as $key => $value) {
            if (strpos($key, 'weight_') === 0 && is_numeric($value)) {
                $itemid = (int) str_replace('weight_', '', $key);
                $weights[$itemid] = (float) $value;
            }
        }

        $record = new stdClass();
        $record->courseid = $courseid;
        $record->name = $data->name;
        $record->q10_component_id = $data->q10_component_id;
        $record->formula_type = $data->formula_type;
        $record->selected_items = json_encode($data->selected_items);
        $record->weights = json_encode($weights);
        $record->custom_formula = $data->custom_formula ?? '';
        $record->enabled = $data->enabled;
        $record->timemodified = $now;
        $record->usermodified = $USER->id;

        if ($formula) {
            $record->id = $formula->id;
            $DB->update_record('local_q10grades_formula', $record);
            $message = get_string('formulaupdated', 'local_q10grades');
        } else {
            $record->timecreated = $now;
            $record->sortorder = $DB->count_records('local_q10grades_formula', ['courseid' => $courseid]);
            $DB->insert_record('local_q10grades_formula', $record);
            $message = get_string('formulacreated', 'local_q10grades');
        }

        redirect(
            new moodle_url('/local/q10grades/formulas.php', ['courseid' => $courseid]),
            $message,
            null,
            \core\output\notification::NOTIFY_SUCCESS
        );
    }

    echo $OUTPUT->header();
    echo $OUTPUT->heading($action === 'add' ? get_string('addformula', 'local_q10grades') : get_string('editformula', 'local_q10grades'));

    $form->display();

    echo $OUTPUT->footer();
    exit;
}

// List formulas.
$formulas = $DB->get_records('local_q10grades_formula', ['courseid' => $courseid], 'sortorder');

echo $OUTPUT->header();

echo $OUTPUT->heading(get_string('formulas', 'local_q10grades'));

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

echo $OUTPUT->tabtree($tabs, 'formulas');

echo html_writer::tag('p', get_string('formulas_desc', 'local_q10grades'), ['class' => 'lead']);

if (empty($formulas)) {
    echo $OUTPUT->notification(get_string('noformulas', 'local_q10grades'), 'info');
} else {
    $formulatypes = [
        'average' => get_string('formulaaverage', 'local_q10grades'),
        'weighted' => get_string('formulaweighted', 'local_q10grades'),
        'sum' => get_string('formulasum', 'local_q10grades'),
        'highest' => get_string('formulahighest', 'local_q10grades'),
        'lowest' => get_string('formulalowest', 'local_q10grades'),
        'custom' => get_string('formulacustom', 'local_q10grades'),
    ];

    $table = new html_table();
    $table->head = [
        get_string('formulaname', 'local_q10grades'),
        get_string('q10componentid', 'local_q10grades'),
        get_string('formulatype', 'local_q10grades'),
        get_string('selecteditems', 'local_q10grades'),
        get_string('status'),
        get_string('actions'),
    ];
    $table->attributes['class'] = 'table table-striped';

    foreach ($formulas as $formula) {
        $selecteditems = json_decode($formula->selected_items, true) ?? [];
        $itemcount = count($selecteditems);

        $status = $formula->enabled ?
            html_writer::tag('span', get_string('enabled', 'local_q10grades'), ['class' => 'badge badge-success']) :
            html_writer::tag('span', get_string('disabled', 'local_q10grades'), ['class' => 'badge badge-secondary']);

        $actions = html_writer::link(
            new moodle_url('/local/q10grades/formulas.php', ['courseid' => $courseid, 'action' => 'edit', 'id' => $formula->id]),
            $OUTPUT->pix_icon('t/edit', get_string('edit')),
            ['class' => 'mr-2']
        );
        $actions .= html_writer::link(
            new moodle_url('/local/q10grades/formulas.php', ['courseid' => $courseid, 'delete' => $formula->id, 'sesskey' => sesskey()]),
            $OUTPUT->pix_icon('t/delete', get_string('delete')),
            ['class' => 'text-danger']
        );

        $table->data[] = [
            $formula->name,
            $formula->q10_component_id,
            $formulatypes[$formula->formula_type] ?? $formula->formula_type,
            get_string('nitems', 'local_q10grades', $itemcount),
            $status,
            $actions,
        ];
    }

    echo html_writer::table($table);
}

echo html_writer::tag('div',
    html_writer::link(
        new moodle_url('/local/q10grades/formulas.php', ['courseid' => $courseid, 'action' => 'add']),
        get_string('addformula', 'local_q10grades'),
        ['class' => 'btn btn-primary']
    ),
    ['class' => 'mt-3']
);

echo $OUTPUT->footer();
