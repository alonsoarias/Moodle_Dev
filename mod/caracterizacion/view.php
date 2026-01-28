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
 * Prints an instance of mod_caracterizacion - lists all matrices.
 *
 * @package     mod_caracterizacion
 * @copyright   2024 Alonso Arias <soporte@orioncloud.com.co>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');
require_once($CFG->dirroot . '/mod/caracterizacion/lib.php');

$id = optional_param('id', 0, PARAM_INT); // Course module ID.
$c = optional_param('c', 0, PARAM_INT);   // Instance ID.

if ($id) {
    $cm = get_coursemodule_from_id('caracterizacion', $id, 0, false, MUST_EXIST);
    $course = $DB->get_record('course', ['id' => $cm->course], '*', MUST_EXIST);
    $moduleinstance = $DB->get_record('caracterizacion', ['id' => $cm->instance], '*', MUST_EXIST);
} else {
    $moduleinstance = $DB->get_record('caracterizacion', ['id' => $c], '*', MUST_EXIST);
    $course = $DB->get_record('course', ['id' => $moduleinstance->course], '*', MUST_EXIST);
    $cm = get_coursemodule_from_instance('caracterizacion', $moduleinstance->id, $course->id, false, MUST_EXIST);
}

require_login($course, true, $cm);

$context = context_module::instance($cm->id);
require_capability('mod/caracterizacion:view', $context);

$PAGE->set_url('/mod/caracterizacion/view.php', ['id' => $cm->id]);
$PAGE->set_title(format_string($moduleinstance->name));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_context($context);

// Trigger view event.
$event = \mod_caracterizacion\event\course_module_viewed::create([
    'objectid' => $moduleinstance->id,
    'context' => $context,
]);
$event->add_record_snapshot('course', $course);
$event->add_record_snapshot('caracterizacion', $moduleinstance);
$event->trigger();

echo $OUTPUT->header();
echo $OUTPUT->heading(format_string($moduleinstance->name));

if ($moduleinstance->intro) {
    echo $OUTPUT->box(format_module_intro('caracterizacion', $moduleinstance, $cm->id), 'generalbox mod_introbox', 'intro');
}

// Show "New Matrix" button if user has capability.
if (has_capability('mod/caracterizacion:crearmatriz', $context)) {
    $newurl = new moodle_url('/mod/caracterizacion/matriz_edit.php', ['cmid' => $cm->id]);
    echo html_writer::div(
        $OUTPUT->single_button($newurl, get_string('nuevamatriz', 'mod_caracterizacion'), 'get'),
        'mb-3'
    );
}

// Load matrices.
$canviewall = has_capability('mod/caracterizacion:vertodasmatrices', $context);
if ($canviewall) {
    $matrices = $DB->get_records('caracterizacion_matriz', ['caracterizacionid' => $moduleinstance->id], 'timecreated DESC');
} else {
    $matrices = $DB->get_records('caracterizacion_matriz', [
        'caracterizacionid' => $moduleinstance->id,
        'creado_por' => $USER->id,
    ], 'timecreated DESC');
}

$phases = caracterizacion_get_phases();

if (empty($matrices)) {
    echo $OUTPUT->notification(get_string('nomatrices', 'mod_caracterizacion'), 'info');
} else {
    // Render matrices list using the template.
    $templatedata = [
        'matrices' => [],
        'cmid' => $cm->id,
        'candelete' => has_capability('mod/caracterizacion:eliminarmatriz', $context),
        'canedit' => has_capability('mod/caracterizacion:editarmatriz', $context),
    ];

    foreach ($matrices as $matriz) {
        $creator = $DB->get_record('user', ['id' => $matriz->creado_por]);
        $creatorname = $creator ? fullname($creator) : '-';
        $estadostr = get_string($matriz->estado, 'mod_caracterizacion');
        $fasestr = isset($phases[$matriz->fase_actual]) ?
            get_string($phases[$matriz->fase_actual], 'mod_caracterizacion') :
            get_string('fase1', 'mod_caracterizacion');

        $estadoclass = 'badge-secondary';
        switch ($matriz->estado) {
            case 'en_proceso':
                $estadoclass = 'badge-primary';
                break;
            case 'completada':
                $estadoclass = 'badge-success';
                break;
        }

        $templatedata['matrices'][] = [
            'id' => $matriz->id,
            'programa_academico' => format_string($matriz->programa_academico),
            'nombre_curso' => format_string($matriz->nombre_curso),
            'fase_actual' => $fasestr,
            'fase_num' => $matriz->fase_actual,
            'estado' => $estadostr,
            'estadoclass' => $estadoclass,
            'creador' => $creatorname,
            'timecreated' => userdate($matriz->timecreated),
            'timemodified' => userdate($matriz->timemodified),
            'viewurl' => (new moodle_url('/mod/caracterizacion/matriz_view.php', [
                'cmid' => $cm->id,
                'matrizid' => $matriz->id,
            ]))->out(false),
            'editurl' => (new moodle_url('/mod/caracterizacion/matriz_edit.php', [
                'cmid' => $cm->id,
                'matrizid' => $matriz->id,
            ]))->out(false),
            'deleteurl' => (new moodle_url('/mod/caracterizacion/matriz_delete.php', [
                'cmid' => $cm->id,
                'matrizid' => $matriz->id,
                'sesskey' => sesskey(),
            ]))->out(false),
        ];
    }

    echo $OUTPUT->render_from_template('mod_caracterizacion/matrices_list', $templatedata);
}

echo $OUTPUT->footer();
