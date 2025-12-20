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
 * Course mapping page for Q10 Grades Sync plugin.
 *
 * @package    local_q10grades
 * @copyright  2025 Your Institution
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');

$courseid = required_param('courseid', PARAM_INT);

$course = $DB->get_record('course', ['id' => $courseid], '*', MUST_EXIST);
$context = context_course::instance($courseid);

require_login($course);
require_capability('local/q10grades:sync', $context);

$PAGE->set_url(new moodle_url('/local/q10grades/mapping.php', ['courseid' => $courseid]));
$PAGE->set_context($context);
$PAGE->set_course($course);
$PAGE->set_pagelayout('incourse');
$PAGE->set_title(get_string('coursemapping', 'local_q10grades'));
$PAGE->set_heading($course->fullname);

$PAGE->navbar->add(get_string('syncgrades', 'local_q10grades'),
    new moodle_url('/local/q10grades/sync.php', ['courseid' => $courseid]));
$PAGE->navbar->add(get_string('coursemapping', 'local_q10grades'));

$syncmanager = new \local_q10grades\grade_sync_manager($courseid);
$mapping = $syncmanager->get_mapping();

$form = new \local_q10grades\form\course_mapping_form(null, [
    'courseid' => $courseid,
    'mapping' => $mapping,
]);

if ($form->is_cancelled()) {
    redirect(new moodle_url('/local/q10grades/sync.php', ['courseid' => $courseid]));
} else if ($data = $form->get_data()) {
    $syncmanager->save_mapping(
        $data->q10_subject_id,
        $data->q10_period_id ?? null,
        $data->q10_group_id ?? null,
        (bool)$data->enabled
    );

    redirect(
        new moodle_url('/local/q10grades/sync.php', ['courseid' => $courseid]),
        get_string('mappingsaved', 'local_q10grades'),
        null,
        \core\output\notification::NOTIFY_SUCCESS
    );
}

echo $OUTPUT->header();

echo $OUTPUT->heading(get_string('coursemapping', 'local_q10grades'));

// Navigation tabs.
$tabs = [];
$tabs[] = new tabobject('sync',
    new moodle_url('/local/q10grades/sync.php', ['courseid' => $courseid]),
    get_string('syncgrades', 'local_q10grades'));
$tabs[] = new tabobject('mapping',
    new moodle_url('/local/q10grades/mapping.php', ['courseid' => $courseid]),
    get_string('coursemapping', 'local_q10grades'));
$tabs[] = new tabobject('items',
    new moodle_url('/local/q10grades/items.php', ['courseid' => $courseid]),
    get_string('q10items', 'local_q10grades'));
$tabs[] = new tabobject('upload',
    new moodle_url('/local/q10grades/upload.php', ['courseid' => $courseid]),
    get_string('uploadgrades', 'local_q10grades'));
$tabs[] = new tabobject('history',
    new moodle_url('/local/q10grades/history.php', ['courseid' => $courseid]),
    get_string('synchistory', 'local_q10grades'));

echo $OUTPUT->tabtree($tabs, 'mapping');

echo html_writer::tag('p', get_string('coursemapping_desc', 'local_q10grades'), ['class' => 'lead']);

$form->display();

echo $OUTPUT->footer();
