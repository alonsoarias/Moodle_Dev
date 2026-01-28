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
 * Restore task for mod_caracterizacion.
 *
 * @package     mod_caracterizacion
 * @copyright   2024 Alonso Arias <soporte@orioncloud.com.co>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/caracterizacion/backup/moodle2/restore_caracterizacion_stepslib.php');

/**
 * Restore task class for mod_caracterizacion.
 */
class restore_caracterizacion_activity_task extends restore_activity_task {

    /**
     * No specific settings.
     */
    protected function define_my_settings() {
        // No particular settings.
    }

    /**
     * Define the restore steps.
     */
    protected function define_my_steps() {
        $this->add_step(new restore_caracterizacion_activity_structure_step(
            'caracterizacion_structure', 'caracterizacion.xml'));
    }

    /**
     * Define decode contents.
     *
     * @return array
     */
    public static function define_decode_contents() {
        $contents = [];
        $contents[] = new restore_decode_content('caracterizacion', ['intro'], 'caracterizacion');
        return $contents;
    }

    /**
     * Define decode rules.
     *
     * @return array
     */
    public static function define_decode_rules() {
        $rules = [];
        $rules[] = new restore_decode_rule('CARACTERIZACIONVIEWBYID', '/mod/caracterizacion/view.php?id=$1', 'course_module');
        $rules[] = new restore_decode_rule('CARACTERIZACIONINDEX', '/mod/caracterizacion/index.php?id=$1', 'course');
        return $rules;
    }

    /**
     * Define restore log rules.
     *
     * @return array
     */
    public static function define_restore_log_rules() {
        $rules = [];
        $rules[] = new restore_log_rule('caracterizacion', 'add', 'view.php?id={course_module}', '{caracterizacion}');
        $rules[] = new restore_log_rule('caracterizacion', 'update', 'view.php?id={course_module}', '{caracterizacion}');
        $rules[] = new restore_log_rule('caracterizacion', 'view', 'view.php?id={course_module}', '{caracterizacion}');
        return $rules;
    }

    /**
     * Define restore log rules for course.
     *
     * @return array
     */
    public static function define_restore_log_rules_for_course() {
        $rules = [];
        $rules[] = new restore_log_rule('caracterizacion', 'view all', 'index.php?id={course}', null);
        return $rules;
    }
}
