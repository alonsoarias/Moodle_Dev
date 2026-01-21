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
 * Caracterización CRUD operations.
 *
 * @package     mod_udes
 * @copyright   2026 Universidad de Santander - UDES (udes.edu.co)
 * @author      Alonso Arias <soporte@orioncloud.com.co>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__.'/../../config.php');
require_once(__DIR__.'/lib.php');
require_once(__DIR__.'/caracterizacion_form.php');

// Course module id.
$id = required_param('id', PARAM_INT);
$caracterizacionid = optional_param('caracterizacionid', 0, PARAM_INT);
$action = optional_param('action', 'edit', PARAM_ALPHA);

$cm = get_coursemodule_from_id('udes', $id, 0, false, MUST_EXIST);
$course = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
$moduleinstance = $DB->get_record('udes', array('id' => $cm->instance), '*', MUST_EXIST);

require_login($course, true, $cm);

$modulecontext = context_module::instance($cm->id);

// Check permissions.
$canmanage = has_capability('mod/udes:expertodisciplinar', $modulecontext) ||
             has_capability('mod/udes:asesormetodologico', $modulecontext) ||
             has_capability('mod/udes:manageall', $modulecontext);

if (!$canmanage) {
    throw new moodle_exception('error_no_permission', 'mod_udes');
}

// Set up the page.
$PAGE->set_url('/mod/udes/caracterizacion.php', array('id' => $cm->id, 'caracterizacionid' => $caracterizacionid));
$PAGE->set_title(format_string($moduleinstance->name));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_context($modulecontext);

// Handle delete action.
if ($action === 'delete' && $caracterizacionid > 0 && confirm_sesskey()) {
    $caracterizacion = $DB->get_record('udes_caracterizacion', array('id' => $caracterizacionid), '*', MUST_EXIST);

    // Verify it belongs to this udes instance.
    if ($caracterizacion->udesid != $moduleinstance->id) {
        throw new moodle_exception('error_no_permission', 'mod_udes');
    }

    \mod_udes\caracterizacion_manager::delete_caracterizacion($caracterizacionid);

    redirect(
        new moodle_url('/mod/udes/view.php', array('id' => $cm->id)),
        get_string('success_saved', 'mod_udes'),
        null,
        \core\output\notification::NOTIFY_SUCCESS
    );
}

// Prepare form data.
$caracterizacion = null;
if ($caracterizacionid > 0) {
    $caracterizacion = \mod_udes\caracterizacion_manager::get_caracterizacion($caracterizacionid);
    if (!$caracterizacion || $caracterizacion->udesid != $moduleinstance->id) {
        throw new moodle_exception('error_no_permission', 'mod_udes');
    }
}

// Initialize form.
$mform = new mod_udes_caracterizacion_form(null, array(
    'id' => $cm->id,
    'caracterizacionid' => $caracterizacionid
));

// Set existing data for editing.
if ($caracterizacion) {
    $mform->set_data($caracterizacion);
}

// Handle form submission.
if ($mform->is_cancelled()) {
    redirect(new moodle_url('/mod/udes/view.php', array('id' => $cm->id)));
} else if ($data = $mform->get_data()) {
    if ($caracterizacionid > 0) {
        // Update existing caracterization.
        \mod_udes\caracterizacion_manager::update_caracterizacion($caracterizacionid, $data);
        $message = get_string('success_saved', 'mod_udes');
    } else {
        // Create new caracterization.
        $caracterizacionid = \mod_udes\caracterizacion_manager::create_caracterizacion($moduleinstance->id, $data);
        $message = get_string('success_saved', 'mod_udes');
    }

    redirect(
        new moodle_url('/mod/udes/view.php', array('id' => $cm->id)),
        $message,
        null,
        \core\output\notification::NOTIFY_SUCCESS
    );
}

// Output starts here.
echo $OUTPUT->header();

// Display heading.
if ($caracterizacionid > 0) {
    echo $OUTPUT->heading(get_string('editar_caracterizacion', 'mod_udes'));
} else {
    echo $OUTPUT->heading(get_string('nueva_caracterizacion', 'mod_udes'));
}

// Display the form.
$mform->display();

echo $OUTPUT->footer();
