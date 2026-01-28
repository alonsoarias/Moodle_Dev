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
 * Delete a characterization matrix.
 *
 * @package     mod_caracterizacion
 * @copyright   2024 Alonso Arias <soporte@orioncloud.com.co>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');
require_once($CFG->dirroot . '/mod/caracterizacion/lib.php');

$cmid = required_param('cmid', PARAM_INT);
$matrizid = required_param('matrizid', PARAM_INT);

$cm = get_coursemodule_from_id('caracterizacion', $cmid, 0, false, MUST_EXIST);
$course = $DB->get_record('course', ['id' => $cm->course], '*', MUST_EXIST);

require_login($course, true, $cm);
$context = context_module::instance($cm->id);
require_capability('mod/caracterizacion:eliminarmatriz', $context);
require_sesskey();

$matriz = $DB->get_record('caracterizacion_matriz', ['id' => $matrizid], '*', MUST_EXIST);

caracterizacion_delete_matriz_data($matrizid);

redirect(
    new moodle_url('/mod/caracterizacion/view.php', ['id' => $cmid]),
    get_string('matrizeliminada', 'mod_caracterizacion'),
    null,
    \core\output\notification::NOTIFY_SUCCESS
);
