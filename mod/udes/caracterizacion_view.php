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
 * View a single caracterizacion instance with workflow, resources, and comments.
 *
 * @package     mod_udes
 * @copyright   2026 Universidad de Santander - UDES
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__.'/../../config.php');
require_once(__DIR__.'/lib.php');

// Course module id.
$id = required_param('id', PARAM_INT);

// Caracterizacion id.
$caracterizacionid = required_param('caracterizacionid', PARAM_INT);

$cm = get_coursemodule_from_id('udes', $id, 0, false, MUST_EXIST);
$course = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
$moduleinstance = $DB->get_record('udes', array('id' => $cm->instance), '*', MUST_EXIST);

require_login($course, true, $cm);

$modulecontext = context_module::instance($cm->id);

// Get the caracterizacion.
$caracterizacion = \mod_udes\caracterizacion_manager::get_caracterizacion($caracterizacionid);

if (!$caracterizacion || $caracterizacion->udesid != $moduleinstance->id) {
    throw new moodle_exception('error_caracterizacion_not_found', 'mod_udes');
}

$PAGE->set_url('/mod/udes/caracterizacion_view.php', array('id' => $cm->id, 'caracterizacionid' => $caracterizacionid));
$PAGE->set_title(format_string($moduleinstance->name) . ' - ' . format_string($caracterizacion->nombre));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_context($modulecontext);

// Output starts here.
echo $OUTPUT->header();

// Breadcrumb.
echo html_writer::start_div('mb-3');
echo html_writer::link(
    new moodle_url('/mod/udes/view.php', array('id' => $cm->id)),
    get_string('volver', 'mod_udes'),
    array('class' => 'btn btn-secondary btn-sm')
);
echo html_writer::end_div();

// Display caracterizacion name.
echo $OUTPUT->heading($moduleinstance->name . ' - ' . format_string($caracterizacion->nombre));

// Display caracterizacion information.
echo html_writer::start_div('udes-caracterizacion-info card mb-4');
echo html_writer::start_div('card-body');

echo html_writer::tag('h4', get_string('course_information', 'mod_udes'));

if (!empty($caracterizacion->programa_academico)) {
    echo html_writer::tag('p',
        html_writer::tag('strong', get_string('programa_academico', 'mod_udes') . ': ') .
        format_string($caracterizacion->programa_academico)
    );
}

if (!empty($caracterizacion->nombre_curso)) {
    echo html_writer::tag('p',
        html_writer::tag('strong', get_string('nombre_curso', 'mod_udes') . ': ') .
        format_string($caracterizacion->nombre_curso)
    );
}

echo html_writer::end_div(); // card-body.
echo html_writer::end_div(); // card.

// Display team members.
echo html_writer::start_div('udes-team card mb-4');
echo html_writer::start_div('card-body');

echo html_writer::tag('h4', get_string('team_members', 'mod_udes'));

$teamfields = array(
    'asesor_metodologico',
    'experto_disciplinar',
    'par_academico',
    'corrector_estilo',
    'coordinacion_produccion',
    'produccion',
    'alistamiento'
);

echo html_writer::start_tag('ul', array('class' => 'list-unstyled'));
foreach ($teamfields as $field) {
    if (!empty($caracterizacion->$field)) {
        echo html_writer::start_tag('li');
        echo html_writer::tag('strong', get_string($field, 'mod_udes') . ': ');
        echo format_string($caracterizacion->$field);
        echo html_writer::end_tag('li');
    }
}
echo html_writer::end_tag('ul');

echo html_writer::end_div(); // card-body.
echo html_writer::end_div(); // card.

// Display general resources.
echo html_writer::start_div('udes-general-resources card mb-4');
echo html_writer::start_div('card-body');

echo html_writer::tag('h4', get_string('general_resources', 'mod_udes'));

$generalresources = array(
    'cvp' => get_string('cvp', 'mod_udes'),
    'sala_clases' => get_string('sala_clases', 'mod_udes'),
    'video_bienvenida' => get_string('video_bienvenida', 'mod_udes'),
    'foro_curso' => get_string('foro_curso', 'mod_udes'),
    'mapa_curso' => get_string('mapa_curso', 'mod_udes')
);

echo html_writer::start_tag('ul', array('class' => 'list-unstyled'));
foreach ($generalresources as $field => $label) {
    $checked = $caracterizacion->$field ? '✓' : '✗';
    $class = $caracterizacion->$field ? 'text-success' : 'text-muted';
    echo html_writer::tag('li',
        html_writer::tag('span', $checked, array('class' => $class)) . ' ' . $label
    );
}
echo html_writer::end_tag('ul');

echo html_writer::end_div(); // card-body.
echo html_writer::end_div(); // card.

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

// Display current phase and workflow.
$phases = udes_get_workflow_phases();
echo html_writer::start_div('udes-workflow card mb-4');
echo html_writer::start_div('card-body');

echo html_writer::tag('h4', get_string('currentphase', 'mod_udes'));
echo html_writer::tag('h5',
    get_string('fase', 'mod_udes') . ' ' . $caracterizacion->currentphase . ': ' . $phases[$caracterizacion->currentphase],
    array('class' => 'text-primary')
);

// Estado badge.
$estadobadgeclass = 'badge badge-secondary';
switch ($caracterizacion->estado) {
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

echo html_writer::tag('span',
    get_string('estado', 'mod_udes') . ': ' . get_string('estado_' . $caracterizacion->estado, 'mod_udes'),
    array('class' => $estadobadgeclass . ' mb-3')
);

// Display phase navigation.
echo html_writer::start_div('udes-phases mt-3');
foreach ($phases as $phasenum => $phasename) {
    $phaseclass = 'udes-phase badge mr-2 mb-2';
    if ($phasenum == $caracterizacion->currentphase) {
        $phaseclass .= ' badge-primary';
    } else if ($phasenum < $caracterizacion->currentphase) {
        $phaseclass .= ' badge-success';
    } else {
        $phaseclass .= ' badge-secondary';
    }

    echo html_writer::tag('span',
        'Fase ' . $phasenum . ': ' . $phasename,
        array('class' => $phaseclass)
    );
}
echo html_writer::end_div();

echo html_writer::end_div(); // card-body.
echo html_writer::end_div(); // card.

// Display phase-specific content.
echo html_writer::start_div('udes-content card mb-4');
echo html_writer::start_div('card-body');

echo html_writer::tag('h4', $phases[$caracterizacion->currentphase]);

// Check permissions for current phase.
$haspermission = false;
switch ($caracterizacion->currentphase) {
    case 1:
        $haspermission = in_array('experto_disciplinar', $userroles) || in_array('asesor_metodologico', $userroles);
        break;
    case 2:
        $haspermission = in_array('revisor_curricular', $userroles);
        break;
    case 3:
        $haspermission = in_array('par_disciplinar', $userroles) || in_array('corrector_estilo', $userroles);
        break;
    case 4:
        $haspermission = in_array('coordinacion_produccion', $userroles) || in_array('produccion', $userroles);
        break;
    case 5:
        $haspermission = in_array('alistamiento', $userroles);
        break;
    case 6:
        $haspermission = has_capability('mod/udes:approve', $modulecontext);
        break;
}

if ($haspermission || has_capability('mod/udes:manageall', $modulecontext)) {
    // Display resource management link for phase 1.
    if ($caracterizacion->currentphase == 1) {
        echo html_writer::tag('p', get_string('fase1_caracterizacion', 'mod_udes'));
        echo html_writer::link(
            new moodle_url('/mod/udes/recursos.php', array('id' => $cm->id, 'caracterizacionid' => $caracterizacionid)),
            get_string('agregar_recurso', 'mod_udes'),
            array('class' => 'btn btn-primary')
        );
    }

    // Display approval buttons for phases 2-6.
    if ($caracterizacion->currentphase > 1 && has_capability('mod/udes:approve', $modulecontext)) {
        echo html_writer::start_div('udes-approval-buttons mt-4');

        echo html_writer::link(
            new moodle_url('/mod/udes/approve.php',
                array('id' => $cm->id, 'caracterizacionid' => $caracterizacionid, 'action' => 'approve')
            ),
            get_string('aprobar', 'mod_udes'),
            array('class' => 'btn btn-success')
        );

        echo ' ';

        echo html_writer::link(
            new moodle_url('/mod/udes/approve.php',
                array('id' => $cm->id, 'caracterizacionid' => $caracterizacionid, 'action' => 'reject')
            ),
            get_string('rechazar', 'mod_udes'),
            array('class' => 'btn btn-danger')
        );

        echo html_writer::end_div();
    }
} else {
    echo html_writer::tag('p', get_string('error_no_permission', 'mod_udes'), array('class' => 'alert alert-warning'));
}

echo html_writer::end_div(); // card-body.
echo html_writer::end_div(); // card.

// Display recursos for this caracterizacion.
echo html_writer::start_div('udes-recursos card mb-4');
echo html_writer::start_div('card-body');

echo html_writer::tag('h4', get_string('recursos', 'mod_udes'));

$recursos = $DB->get_records('udes_recursos', array('caracterizacionid' => $caracterizacionid), 'unidad, tema');

if ($recursos) {
    echo html_writer::start_tag('table', array('class' => 'table table-striped'));
    echo html_writer::start_tag('thead');
    echo html_writer::start_tag('tr');
    echo html_writer::tag('th', get_string('unidad', 'mod_udes'));
    echo html_writer::tag('th', get_string('tema', 'mod_udes'));
    echo html_writer::tag('th', get_string('tipo_recurso', 'mod_udes'));
    echo html_writer::end_tag('tr');
    echo html_writer::end_tag('thead');
    echo html_writer::start_tag('tbody');

    foreach ($recursos as $recurso) {
        echo html_writer::start_tag('tr');
        echo html_writer::tag('td', $recurso->unidad);
        echo html_writer::tag('td', $recurso->tema);
        echo html_writer::tag('td', $recurso->tipo_recurso);
        echo html_writer::end_tag('tr');
    }

    echo html_writer::end_tag('tbody');
    echo html_writer::end_tag('table');
} else {
    echo html_writer::tag('p', get_string('error_no_recursos', 'mod_udes'), array('class' => 'text-muted'));
}

echo html_writer::end_div(); // card-body.
echo html_writer::end_div(); // card.

// Display comments section.
echo html_writer::start_div('udes-comments card mb-4');
echo html_writer::start_div('card-body');

echo html_writer::tag('h4', get_string('comentarios', 'mod_udes'));

$comentarios = $DB->get_records('udes_comentarios',
    array('caracterizacionid' => $caracterizacionid, 'phase' => $caracterizacion->currentphase),
    'timecreated DESC'
);

if ($comentarios) {
    foreach ($comentarios as $comentario) {
        $user = $DB->get_record('user', array('id' => $comentario->userid));
        echo html_writer::start_div('udes-comment card mb-2 bg-light');
        echo html_writer::start_div('card-body');
        echo html_writer::tag('h6', fullname($user), array('class' => 'card-subtitle mb-2 text-muted'));
        echo html_writer::tag('p', format_text($comentario->comentario), array('class' => 'card-text'));
        echo html_writer::tag('small', userdate($comentario->timecreated), array('class' => 'text-muted'));
        echo html_writer::end_div();
        echo html_writer::end_div();
    }
} else {
    echo html_writer::tag('p', 'No hay comentarios aún.', array('class' => 'text-muted'));
}

// Add comment form.
if ($haspermission || has_capability('mod/udes:manageall', $modulecontext)) {
    echo html_writer::start_div('mt-3');
    echo html_writer::tag('h5', get_string('agregar_comentario', 'mod_udes'));

    echo html_writer::start_tag('form', array('method' => 'post', 'action' => 'save_comentario.php'));
    echo html_writer::empty_tag('input', array('type' => 'hidden', 'name' => 'id', 'value' => $cm->id));
    echo html_writer::empty_tag('input', array('type' => 'hidden', 'name' => 'caracterizacionid', 'value' => $caracterizacionid));
    echo html_writer::empty_tag('input', array('type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()));

    echo html_writer::tag('textarea',
        '',
        array('name' => 'comentario', 'class' => 'form-control', 'rows' => '3', 'required' => 'required')
    );

    echo html_writer::empty_tag('input', array(
        'type' => 'submit',
        'class' => 'btn btn-primary mt-2',
        'value' => get_string('guardar', 'mod_udes')
    ));

    echo html_writer::end_tag('form');
    echo html_writer::end_div();
}

echo html_writer::end_div(); // card-body.
echo html_writer::end_div(); // card.

// Finish the page.
echo $OUTPUT->footer();
