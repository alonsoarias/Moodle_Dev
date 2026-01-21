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
 * Prints an instance of mod_udes.
 *
 * @package     mod_udes
 * @copyright   2026 Universidad de Santander - UDES
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__.'/../../config.php');
require_once(__DIR__.'/lib.php');

// Course module id.
$id = optional_param('id', 0, PARAM_INT);

// Activity instance id.
$u = optional_param('u', 0, PARAM_INT);

// Action parameter.
$action = optional_param('action', 'view', PARAM_ALPHA);

// Phase parameter.
$phase = optional_param('phase', 0, PARAM_INT);

if ($id) {
    $cm = get_coursemodule_from_id('udes', $id, 0, false, MUST_EXIST);
    $course = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
    $moduleinstance = $DB->get_record('udes', array('id' => $cm->instance), '*', MUST_EXIST);
} else if ($u) {
    $moduleinstance = $DB->get_record('udes', array('id' => $u), '*', MUST_EXIST);
    $course = $DB->get_record('course', array('id' => $moduleinstance->course), '*', MUST_EXIST);
    $cm = get_coursemodule_from_instance('udes', $moduleinstance->id, $course->id, false, MUST_EXIST);
} else {
    throw new moodle_exception('missingidandcmid', 'mod_udes');
}

require_login($course, true, $cm);

$modulecontext = context_module::instance($cm->id);

$event = \mod_udes\event\course_module_viewed::create(array(
    'objectid' => $moduleinstance->id,
    'context' => $modulecontext
));
$event->add_record_snapshot('course', $course);
$event->add_record_snapshot('udes', $moduleinstance);
$event->trigger();

$PAGE->set_url('/mod/udes/view.php', array('id' => $cm->id));
$PAGE->set_title(format_string($moduleinstance->name));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_context($modulecontext);

// Output starts here.
echo $OUTPUT->header();

// Display module name and description.
echo $OUTPUT->heading($moduleinstance->name);

if ($moduleinstance->intro) {
    echo $OUTPUT->box(format_module_intro('udes', $moduleinstance, $cm->id), 'generalbox', 'intro');
}

// v2.0: Display list of caracterizaciones.
echo html_writer::start_div('udes-caracterizaciones-container');

// Check if user can create caracterizaciones.
$cancreate = has_capability('mod/udes:expertodisciplinar', $modulecontext) ||
             has_capability('mod/udes:asesormetodologico', $modulecontext) ||
             has_capability('mod/udes:manageall', $modulecontext);

// Button to create new caracterization.
if ($cancreate) {
    echo html_writer::start_div('udes-actions mb-3');
    echo html_writer::link(
        new moodle_url('/mod/udes/caracterizacion.php', array('id' => $cm->id)),
        get_string('nueva_caracterizacion', 'mod_udes'),
        array('class' => 'btn btn-primary')
    );
    echo html_writer::end_div();
}

// Get all caracterizaciones for this activity.
$caracterizaciones = \mod_udes\caracterizacion_manager::get_caracterizaciones_by_udes($moduleinstance->id);

if (empty($caracterizaciones)) {
    // No caracterizaciones yet.
    echo html_writer::start_div('alert alert-info');
    echo html_writer::tag('p', get_string('sin_caracterizaciones', 'mod_udes'));
    echo html_writer::end_div();
} else {
    // Display caracterizaciones in a grid.
    echo html_writer::tag('h3', get_string('lista_caracterizaciones', 'mod_udes'));

    // Get workflow phases for display.
    $phases = udes_get_workflow_phases();

    echo html_writer::start_div('row');

    foreach ($caracterizaciones as $caract) {
        // Get progress information.
        $progress = \mod_udes\caracterizacion_manager::get_caracterizacion_with_progress($caract->id);

        // Phase badge class.
        $phasebadgeclass = 'badge badge-secondary';
        if ($caract->currentphase == 6 && $caract->estado == 'aprobado') {
            $phasebadgeclass = 'badge badge-success';
        } else if ($caract->estado == 'rechazado') {
            $phasebadgeclass = 'badge badge-danger';
        } else if ($caract->currentphase > 1) {
            $phasebadgeclass = 'badge badge-info';
        }

        // Estado badge.
        $estadobadgeclass = 'badge badge-secondary';
        switch ($caract->estado) {
            case 'aprobado':
                $estadobadgeclass = 'badge badge-success';
                break;
            case 'rechazado':
                $estadobadgeclass = 'badge badge-danger';
                break;
            case 'en_proceso':
                $estadobadgeclass = 'badge badge-warning';
                break;
        }

        echo html_writer::start_div('col-md-6 col-lg-4 mb-3');
        echo html_writer::start_div('card h-100');
        echo html_writer::start_div('card-body');

        // Caracterization name.
        echo html_writer::tag('h5', format_string($caract->nombre), array('class' => 'card-title'));

        // Phase and status badges.
        echo html_writer::start_div('mb-2');
        echo html_writer::tag('span',
            get_string('fase', 'mod_udes') . ' ' . $caract->currentphase . ': ' . $phases[$caract->currentphase],
            array('class' => $phasebadgeclass)
        );
        echo ' ';
        echo html_writer::tag('span',
            get_string('estado_' . $caract->estado, 'mod_udes'),
            array('class' => $estadobadgeclass)
        );
        echo html_writer::end_div();

        // Course information.
        if (!empty($caract->nombre_curso)) {
            echo html_writer::tag('p',
                html_writer::tag('strong', get_string('nombre_curso', 'mod_udes') . ': ') .
                format_string($caract->nombre_curso),
                array('class' => 'card-text small')
            );
        }

        if (!empty($caract->programa_academico)) {
            echo html_writer::tag('p',
                html_writer::tag('strong', get_string('programa_academico', 'mod_udes') . ': ') .
                format_string($caract->programa_academico),
                array('class' => 'card-text small')
            );
        }

        // Progress statistics.
        echo html_writer::start_div('mt-2 small text-muted');
        echo html_writer::tag('div',
            get_string('recursos', 'mod_udes') . ': ' . $progress->recursos_count
        );
        echo html_writer::tag('div',
            get_string('comentarios', 'mod_udes') . ': ' . $progress->comentarios_count
        );
        echo html_writer::end_div();

        echo html_writer::end_div(); // card-body.

        // Card footer with action buttons.
        echo html_writer::start_div('card-footer');

        // View/Edit button.
        echo html_writer::link(
            new moodle_url('/mod/udes/caracterizacion_view.php', array('id' => $cm->id, 'caracterizacionid' => $caract->id)),
            get_string('ver', 'mod_udes'),
            array('class' => 'btn btn-sm btn-info')
        );

        // Edit button (only for creators or managers).
        if ($cancreate) {
            echo ' ';
            echo html_writer::link(
                new moodle_url('/mod/udes/caracterizacion.php', array('id' => $cm->id, 'caracterizacionid' => $caract->id)),
                get_string('editar', 'mod_udes'),
                array('class' => 'btn btn-sm btn-secondary')
            );
        }

        // Delete button (only for managers).
        if (has_capability('mod/udes:manageall', $modulecontext)) {
            echo ' ';
            echo html_writer::link(
                new moodle_url('/mod/udes/caracterizacion.php',
                    array('id' => $cm->id, 'caracterizacionid' => $caract->id, 'action' => 'delete', 'sesskey' => sesskey())
                ),
                get_string('eliminar', 'mod_udes'),
                array(
                    'class' => 'btn btn-sm btn-danger',
                    'onclick' => 'return confirm("' . get_string('confirm_delete_caracterizacion', 'mod_udes') . '");'
                )
            );
        }

        echo html_writer::end_div(); // card-footer.
        echo html_writer::end_div(); // card.
        echo html_writer::end_div(); // col.
    }

    echo html_writer::end_div(); // row.
}

echo html_writer::end_div(); // container.

// Finish the page.
echo $OUTPUT->footer();
