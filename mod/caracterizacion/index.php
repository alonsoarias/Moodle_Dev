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
 * Display information about all mod_caracterizacion modules in the requested course.
 *
 * @package     mod_caracterizacion
 * @copyright   2024 Alonso Arias <soporte@orioncloud.com.co>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');

$id = required_param('id', PARAM_INT); // Course ID.

$course = $DB->get_record('course', ['id' => $id], '*', MUST_EXIST);
require_course_login($course);

$PAGE->set_url('/mod/caracterizacion/index.php', ['id' => $id]);
$PAGE->set_title(format_string($course->fullname));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_pagelayout('incourse');

echo $OUTPUT->header();

$modulenameplural = get_string('modulenameplural', 'mod_caracterizacion');
echo $OUTPUT->heading($modulenameplural);

if (!$caracterizaciones = get_all_instances_in_course('caracterizacion', $course)) {
    notice(
        get_string('thereareno', 'moodle', get_string('modulenameplural', 'mod_caracterizacion')),
        new moodle_url('/course/view.php', ['id' => $course->id])
    );
}

$table = new html_table();
$table->attributes['class'] = 'generaltable mod_index';
$table->head = [
    get_string('name'),
    get_string('description'),
];
$table->align = ['left', 'left'];

foreach ($caracterizaciones as $caracterizacion) {
    $link = html_writer::link(
        new moodle_url('/mod/caracterizacion/view.php', ['id' => $caracterizacion->coursemodule]),
        format_string($caracterizacion->name)
    );
    $table->data[] = [$link, format_module_intro('caracterizacion', $caracterizacion, $caracterizacion->coursemodule)];
}

echo html_writer::table($table);
echo $OUTPUT->footer();
