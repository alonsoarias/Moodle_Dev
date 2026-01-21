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

// Get user's role in this activity.
$userroles = array();
if (has_capability('mod/udes:expertodisciplinar', $modulecontext)) {
    $userroles[] = 'experto_disciplinar';
}
if (has_capability('mod/udes:asesormetodologico', $modulecontext)) {
    $userroles[] = 'asesor_metodologico';
}
if (has_capability('mod/udes:revisorcurricular', $modulecontext)) {
    $userroles[] = 'revisor_curricular';
}
if (has_capability('mod/udes:pardisciplinar', $modulecontext)) {
    $userroles[] = 'par_disciplinar';
}
if (has_capability('mod/udes:correctorestilo', $modulecontext)) {
    $userroles[] = 'corrector_estilo';
}
if (has_capability('mod/udes:coordinacionproduccion', $modulecontext)) {
    $userroles[] = 'coordinacion_produccion';
}
if (has_capability('mod/udes:produccion', $modulecontext)) {
    $userroles[] = 'produccion';
}
if (has_capability('mod/udes:alistamiento', $modulecontext)) {
    $userroles[] = 'alistamiento';
}

// Display current phase.
$phases = udes_get_workflow_phases();
echo html_writer::start_div('udes-workflow');
echo html_writer::tag('h3', get_string('currentphase', 'mod_udes') . ': ' . $phases[$moduleinstance->currentphase]);

// Display phase navigation.
echo html_writer::start_div('udes-phases');
foreach ($phases as $phasenum => $phasename) {
    $phaseclass = 'udes-phase';
    if ($phasenum == $moduleinstance->currentphase) {
        $phaseclass .= ' active';
    } else if ($phasenum < $moduleinstance->currentphase) {
        $phaseclass .= ' completed';
    }

    echo html_writer::start_div($phaseclass);
    echo html_writer::tag('strong', 'Fase ' . $phasenum);
    echo html_writer::tag('p', $phasename);
    echo html_writer::end_div();
}
echo html_writer::end_div();
echo html_writer::end_div();

// Display phase-specific content based on current phase.
echo html_writer::start_div('udes-content');

switch ($moduleinstance->currentphase) {
    case 1:
        // Fase 1: Caracterización.
        echo html_writer::tag('h4', get_string('fase1_caracterizacion', 'mod_udes'));

        // Check if user has permission for this phase.
        if (in_array('experto_disciplinar', $userroles) || in_array('asesor_metodologico', $userroles)) {
            // Display caracterización form.
            $caracterizacion = $DB->get_record('udes_caracterizacion', array('udesid' => $moduleinstance->id));

            echo html_writer::start_tag('form', array('method' => 'post', 'action' => 'save_caracterizacion.php'));
            echo html_writer::empty_tag('input', array('type' => 'hidden', 'name' => 'id', 'value' => $cm->id));
            echo html_writer::empty_tag('input', array('type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()));

            // CVP checkbox.
            $checked = ($caracterizacion && $caracterizacion->cvp) ? 'checked' : '';
            echo html_writer::start_div('form-group');
            echo html_writer::tag('label',
                html_writer::checkbox('cvp', '1', $checked) . ' ' . get_string('cvp', 'mod_udes')
            );
            echo html_writer::end_div();

            // Sala clases checkbox.
            $checked = ($caracterizacion && $caracterizacion->sala_clases) ? 'checked' : '';
            echo html_writer::start_div('form-group');
            echo html_writer::tag('label',
                html_writer::checkbox('sala_clases', '1', $checked) . ' ' . get_string('sala_clases', 'mod_udes')
            );
            echo html_writer::end_div();

            // Video bienvenida checkbox.
            $checked = ($caracterizacion && $caracterizacion->video_bienvenida) ? 'checked' : '';
            echo html_writer::start_div('form-group');
            echo html_writer::tag('label',
                html_writer::checkbox('video_bienvenida', '1', $checked) . ' ' . get_string('video_bienvenida', 'mod_udes')
            );
            echo html_writer::end_div();

            // Foro curso checkbox.
            $checked = ($caracterizacion && $caracterizacion->foro_curso) ? 'checked' : '';
            echo html_writer::start_div('form-group');
            echo html_writer::tag('label',
                html_writer::checkbox('foro_curso', '1', $checked) . ' ' . get_string('foro_curso', 'mod_udes')
            );
            echo html_writer::end_div();

            // Mapa curso checkbox.
            $checked = ($caracterizacion && $caracterizacion->mapa_curso) ? 'checked' : '';
            echo html_writer::start_div('form-group');
            echo html_writer::tag('label',
                html_writer::checkbox('mapa_curso', '1', $checked) . ' ' . get_string('mapa_curso', 'mod_udes')
            );
            echo html_writer::end_div();

            // Submit button.
            echo html_writer::empty_tag('input', array(
                'type' => 'submit',
                'class' => 'btn btn-primary',
                'value' => get_string('guardar', 'mod_udes')
            ));

            echo html_writer::end_tag('form');

            // Display resource selection.
            echo html_writer::tag('h5', get_string('recursos', 'mod_udes'), array('class' => 'mt-4'));
            echo html_writer::link(
                new moodle_url('/mod/udes/recursos.php', array('id' => $cm->id)),
                get_string('agregar_recurso', 'mod_udes'),
                array('class' => 'btn btn-secondary')
            );
        } else {
            echo html_writer::tag('p', get_string('error_no_permission', 'mod_udes'), array('class' => 'alert alert-warning'));
        }
        break;

    case 2:
    case 3:
    case 4:
    case 5:
    case 6:
        // Other phases - display review and approval interface.
        echo html_writer::tag('h4', $phases[$moduleinstance->currentphase]);
        echo html_writer::tag('p', 'Contenido de la fase ' . $moduleinstance->currentphase . ' en desarrollo.');

        // Display approval buttons for users with proper permissions.
        if (has_capability('mod/udes:approve', $modulecontext)) {
            echo html_writer::start_div('udes-approval-buttons mt-4');

            echo html_writer::link(
                new moodle_url('/mod/udes/approve.php', array('id' => $cm->id, 'phase' => $moduleinstance->currentphase)),
                get_string('aprobar', 'mod_udes'),
                array('class' => 'btn btn-success')
            );

            echo ' ';

            echo html_writer::link(
                new moodle_url('/mod/udes/reject.php', array('id' => $cm->id, 'phase' => $moduleinstance->currentphase)),
                get_string('rechazar', 'mod_udes'),
                array('class' => 'btn btn-danger')
            );

            echo html_writer::end_div();
        }
        break;
}

echo html_writer::end_div();

// Display comments section.
echo html_writer::start_div('udes-comments mt-4');
echo html_writer::tag('h4', get_string('comentarios', 'mod_udes'));

$comentarios = $DB->get_records('udes_comentarios',
    array('udesid' => $moduleinstance->id, 'phase' => $moduleinstance->currentphase),
    'timecreated DESC'
);

if ($comentarios) {
    foreach ($comentarios as $comentario) {
        $user = $DB->get_record('user', array('id' => $comentario->userid));
        echo html_writer::start_div('udes-comment card mb-2');
        echo html_writer::start_div('card-body');
        echo html_writer::tag('h6', fullname($user), array('class' => 'card-subtitle mb-2 text-muted'));
        echo html_writer::tag('p', $comentario->comentario, array('class' => 'card-text'));
        echo html_writer::tag('small', userdate($comentario->timecreated), array('class' => 'text-muted'));
        echo html_writer::end_div();
        echo html_writer::end_div();
    }
} else {
    echo html_writer::tag('p', 'No hay comentarios aún.');
}

echo html_writer::end_div();

// Finish the page.
echo $OUTPUT->footer();
