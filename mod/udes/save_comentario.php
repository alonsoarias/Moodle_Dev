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
 * Save comment for a caracterizacion.
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

// Comment text.
$comentario = required_param('comentario', PARAM_TEXT);

$cm = get_coursemodule_from_id('udes', $id, 0, false, MUST_EXIST);
$course = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
$moduleinstance = $DB->get_record('udes', array('id' => $cm->instance), '*', MUST_EXIST);

require_login($course, true, $cm);
require_sesskey();

$modulecontext = context_module::instance($cm->id);

// v2.0: Validate caracterizacion belongs to this module.
$caracterizacion = \mod_udes\caracterizacion_manager::get_caracterizacion($caracterizacionid);
if (!$caracterizacion || $caracterizacion->udesid != $moduleinstance->id) {
    throw new moodle_exception('error_caracterizacion_not_found', 'mod_udes');
}

// Check permissions.
$cancomment = has_capability('mod/udes:expertodisciplinar', $modulecontext) ||
              has_capability('mod/udes:asesormetodologico', $modulecontext) ||
              has_capability('mod/udes:revisorcurricular', $modulecontext) ||
              has_capability('mod/udes:pardisciplinar', $modulecontext) ||
              has_capability('mod/udes:correctorestilo', $modulecontext) ||
              has_capability('mod/udes:coordinacionproduccion', $modulecontext) ||
              has_capability('mod/udes:produccion', $modulecontext) ||
              has_capability('mod/udes:alistamiento', $modulecontext) ||
              has_capability('mod/udes:manageall', $modulecontext);

if (!$cancomment) {
    throw new moodle_exception('error_no_permission', 'mod_udes');
}

// Validate comment is not empty.
if (empty(trim($comentario))) {
    redirect(
        new moodle_url('/mod/udes/caracterizacion_view.php', array('id' => $cm->id, 'caracterizacionid' => $caracterizacionid)),
        get_string('error_comentario_empty', 'mod_udes'),
        null,
        \core\output\notification::NOTIFY_ERROR
    );
}

// Save the comment.
$comment = new stdClass();
$comment->caracterizacionid = $caracterizacionid;
$comment->phase = $caracterizacion->currentphase;
$comment->userid = $USER->id;
$comment->comentario = $comentario;
$comment->recursoid = 0; // Not related to a specific resource.
$comment->timecreated = time();

$DB->insert_record('udes_comentarios', $comment);

// Redirect back to caracterizacion view.
redirect(
    new moodle_url('/mod/udes/caracterizacion_view.php', array('id' => $cm->id, 'caracterizacionid' => $caracterizacionid)),
    get_string('success_saved', 'mod_udes'),
    null,
    \core\output\notification::NOTIFY_SUCCESS
);
