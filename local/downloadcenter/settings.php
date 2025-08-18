<?php
// This file is part of local_downloadcenter for Moodle - http://moodle.org/
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
 * Download center plugin
 *
 * @package       local_downloadcenter
 * @author        Tim Schroeder (t.schroeder@itc.rwth-aachen.de)
 * @copyright     2020 Academic Moodle Cooperation {@link http://www.academic-moodle-cooperation.org}
 * @license       http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) {
    // Agregar en la sección de cursos (donde está management.php)
    // Primero verificamos si existe la categoría courses
    $courseadmin = $ADMIN->locate('courses');
    
    if ($courseadmin) {
        // Agregar el enlace después de "Manage courses and categories"
        $ADMIN->add('courses', new admin_externalpage(
            'local_downloadcenter_courses',
            get_string('navigationlink', 'local_downloadcenter'),
            new moodle_url('/local/downloadcenter/index.php'),
            'local/downloadcenter:view'
        ));
    }
    
    // Configuraciones del plugin
    $settings = new admin_settingpage('local_downloadcenter', get_string('settings_title', 'local_downloadcenter'));
    $ADMIN->add('localplugins', $settings);

    $settings->add(new admin_setting_configcheckbox(
        'local_downloadcenter/exclude_empty_topics',
        get_string('exclude_empty_topics', 'local_downloadcenter'),
        get_string('exclude_empty_topics_help', 'local_downloadcenter'),
        0
    ));
    
    // Agregar configuraciones adicionales para mejorar la funcionalidad
    $settings->add(new admin_setting_configtext(
        'local_downloadcenter/maxzipsize',
        get_string('maxzipsize', 'local_downloadcenter'),
        get_string('maxzipsize_desc', 'local_downloadcenter'),
        '512', // 512 MB por defecto
        PARAM_INT
    ));
    
    $settings->add(new admin_setting_configcheckbox(
        'local_downloadcenter/includeassignments',
        get_string('includeassignments', 'local_downloadcenter'),
        get_string('includeassignments_desc', 'local_downloadcenter'),
        1
    ));
}