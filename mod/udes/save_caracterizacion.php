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
 * Save caracterización form data.
 *
 * @package     mod_udes
 * @copyright   2026 Universidad de Santander - UDES (udes.edu.co)
 * @author      Alonso Arias <soporte@orioncloud.com.co>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__.'/../../config.php');
require_once(__DIR__.'/lib.php');

// Course module id.
$id = required_param('id', PARAM_INT);

$cm = get_coursemodule_from_id('udes', $id, 0, false, MUST_EXIST);
$course = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
$moduleinstance = $DB->get_record('udes', array('id' => $cm->instance), '*', MUST_EXIST);

require_login($course, true, $cm);
require_sesskey();

$modulecontext = context_module::instance($cm->id);

// Check permissions.
$canmanage = has_capability('mod/udes:expertodisciplinar', $modulecontext) ||
             has_capability('mod/udes:asesormetodologico', $modulecontext);

if (!$canmanage) {
    throw new moodle_exception('error_no_permission', 'mod_udes');
}

// Get caracterizacion record or create new one.
$caracterizacion = $DB->get_record('udes_caracterizacion', array('udesid' => $moduleinstance->id));

if (!$caracterizacion) {
    $caracterizacion = new stdClass();
    $caracterizacion->udesid = $moduleinstance->id;
    $caracterizacion->timecreated = time();
    $isnew = true;
} else {
    $isnew = false;
}

// Update fields from form - Excel J11-J15.
$caracterizacion->cvp = optional_param('cvp', 0, PARAM_INT);
$caracterizacion->sala_clases = optional_param('sala_clases', 0, PARAM_INT);
$caracterizacion->video_bienvenida = optional_param('video_bienvenida', 0, PARAM_INT);
$caracterizacion->foro_curso = optional_param('foro_curso', 0, PARAM_INT);
$caracterizacion->mapa_curso = optional_param('mapa_curso', 0, PARAM_INT);
$caracterizacion->timemodified = time();

if ($isnew) {
    $DB->insert_record('udes_caracterizacion', $caracterizacion);
} else {
    $DB->update_record('udes_caracterizacion', $caracterizacion);
}

// Redirect back to view page.
redirect(
    new moodle_url('/mod/udes/view.php', array('id' => $cm->id)),
    get_string('success_saved', 'mod_udes'),
    null,
    \core\output\notification::NOTIFY_SUCCESS
);
