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
 * Approve or reject a caracterizacion phase.
 *
 * @package     mod_udes
 * @copyright   2026 Universidad de Santander - UDES
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__.'/../../config.php');
require_once(__DIR__.'/lib.php');

// Course module id.
$id = required_param('id', PARAM_INT);

// v2.0: Caracterizacion id.
$caracterizacionid = required_param('caracterizacionid', PARAM_INT);

// Action: approve or reject.
$action = required_param('action', PARAM_ALPHA);

$cm = get_coursemodule_from_id('udes', $id, 0, false, MUST_EXIST);
$course = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
$moduleinstance = $DB->get_record('udes', array('id' => $cm->instance), '*', MUST_EXIST);

require_login($course, true, $cm);

$modulecontext = context_module::instance($cm->id);

// v2.0: Validate caracterizacion belongs to this module.
$caracterizacion = \mod_udes\caracterizacion_manager::get_caracterizacion($caracterizacionid);
if (!$caracterizacion || $caracterizacion->udesid != $moduleinstance->id) {
    throw new moodle_exception('error_caracterizacion_not_found', 'mod_udes');
}

// Check permissions.
if (!has_capability('mod/udes:approve', $modulecontext) && !has_capability('mod/udes:manageall', $modulecontext)) {
    throw new moodle_exception('error_no_permission', 'mod_udes');
}

$PAGE->set_url('/mod/udes/approve.php', array(
    'id' => $cm->id,
    'caracterizacionid' => $caracterizacionid,
    'action' => $action
));
$PAGE->set_title(format_string($moduleinstance->name));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_context($modulecontext);

// Process form submission.
if ($_SERVER['REQUEST_METHOD'] === 'POST' && confirm_sesskey()) {
    $comentario = optional_param('comentario', '', PARAM_TEXT);

    if ($action === 'approve') {
        // Approve the current phase and advance to next.
        \mod_udes\caracterizacion_manager::advance_to_next_phase($caracterizacionid, $USER->id);

        // Save approval record.
        $aprobacion = new stdClass();
        $aprobacion->caracterizacionid = $caracterizacionid;
        $aprobacion->phase = $caracterizacion->currentphase;
        $aprobacion->userid = $USER->id;
        $aprobacion->aprobado = 1;
        $aprobacion->comentario = $comentario;
        $aprobacion->timecreated = time();
        $DB->insert_record('udes_aprobaciones', $aprobacion);

        // Update caracterizacion status.
        if ($caracterizacion->currentphase < 6) {
            $DB->set_field('udes_caracterizacion', 'estado', 'en_proceso', array('id' => $caracterizacionid));
        } else if ($caracterizacion->currentphase == 6) {
            $DB->set_field('udes_caracterizacion', 'estado', 'aprobado', array('id' => $caracterizacionid));
        }

        $message = get_string('success_approved', 'mod_udes');
        $notifytype = \core\output\notification::NOTIFY_SUCCESS;

    } else if ($action === 'reject') {
        // Reject the current phase.
        $aprobacion = new stdClass();
        $aprobacion->caracterizacionid = $caracterizacionid;
        $aprobacion->phase = $caracterizacion->currentphase;
        $aprobacion->userid = $USER->id;
        $aprobacion->aprobado = 0;
        $aprobacion->comentario = $comentario;
        $aprobacion->timecreated = time();
        $DB->insert_record('udes_aprobaciones', $aprobacion);

        // Update caracterizacion status to rejected.
        $DB->set_field('udes_caracterizacion', 'estado', 'rechazado', array('id' => $caracterizacionid));

        $message = get_string('success_rejected', 'mod_udes');
        $notifytype = \core\output\notification::NOTIFY_WARNING;
    } else {
        throw new moodle_exception('error_invalid_action', 'mod_udes');
    }

    // Redirect back to caracterizacion view.
    redirect(
        new moodle_url('/mod/udes/caracterizacion_view.php', array('id' => $cm->id, 'caracterizacionid' => $caracterizacionid)),
        $message,
        null,
        $notifytype
    );
}

// Display confirmation form.
echo $OUTPUT->header();

$phases = udes_get_workflow_phases();
echo $OUTPUT->heading(format_string($caracterizacion->nombre));
echo html_writer::tag('h3',
    ($action === 'approve' ? get_string('aprobar', 'mod_udes') : get_string('rechazar', 'mod_udes')) .
    ' - ' . $phases[$caracterizacion->currentphase]
);

// Confirmation form.
echo html_writer::start_tag('form', array('method' => 'post', 'class' => 'mform'));
echo html_writer::empty_tag('input', array('type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()));

echo html_writer::start_div('form-group');
echo html_writer::tag('label', get_string('comentario', 'mod_udes'), array('for' => 'comentario'));
echo html_writer::tag('textarea', '', array(
    'name' => 'comentario',
    'id' => 'comentario',
    'class' => 'form-control',
    'rows' => '5'
));
echo html_writer::tag('small', get_string('comentario_opcional', 'mod_udes'), array('class' => 'form-text text-muted'));
echo html_writer::end_div();

echo html_writer::start_div('form-group');
if ($action === 'approve') {
    echo html_writer::empty_tag('input', array(
        'type' => 'submit',
        'class' => 'btn btn-success',
        'value' => get_string('aprobar', 'mod_udes')
    ));
} else if ($action === 'reject') {
    echo html_writer::empty_tag('input', array(
        'type' => 'submit',
        'class' => 'btn btn-danger',
        'value' => get_string('rechazar', 'mod_udes')
    ));
}
echo ' ';
echo html_writer::link(
    new moodle_url('/mod/udes/caracterizacion_view.php', array('id' => $cm->id, 'caracterizacionid' => $caracterizacionid)),
    get_string('cancelar', 'mod_udes'),
    array('class' => 'btn btn-secondary')
);
echo html_writer::end_div();

echo html_writer::end_tag('form');

echo $OUTPUT->footer();
