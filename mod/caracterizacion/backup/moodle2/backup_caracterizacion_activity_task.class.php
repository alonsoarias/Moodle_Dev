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
 * Backup task for mod_caracterizacion.
 *
 * @package     mod_caracterizacion
 * @copyright   2024 Alonso Arias <soporte@orioncloud.com.co>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/caracterizacion/backup/moodle2/backup_caracterizacion_stepslib.php');

/**
 * Backup task class for mod_caracterizacion.
 */
class backup_caracterizacion_activity_task extends backup_activity_task {

    /**
     * No specific settings for this activity.
     */
    protected function define_my_settings() {
        // No particular settings.
    }

    /**
     * Define the backup steps.
     */
    protected function define_my_steps() {
        $this->add_step(new backup_caracterizacion_activity_structure_step('caracterizacion_structure', 'caracterizacion.xml'));
    }

    /**
     * Encode content links.
     *
     * @param string $content The content to encode.
     * @return string The encoded content.
     */
    public static function encode_content_links($content) {
        global $CFG;

        $base = preg_quote($CFG->wwwroot, '/');

        $search = '/(' . $base . '\/mod\/caracterizacion\/index\.php\?id\=)([0-9]+)/';
        $content = preg_replace($search, '$@CARACTERIZACIONINDEX*$2@$', $content);

        $search = '/(' . $base . '\/mod\/caracterizacion\/view\.php\?id\=)([0-9]+)/';
        $content = preg_replace($search, '$@CARACTERIZACIONVIEWBYID*$2@$', $content);

        return $content;
    }
}
