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
 * Admin settings for local_downloadcenter
 *
 * @package    local_downloadcenter
 * @copyright  2025 Alonso Arias <soporte@ingeweb.co>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) {
    
    // Create settings page.
    $settings = new admin_settingpage('local_downloadcenter', 
        get_string('pluginname', 'local_downloadcenter'));
    
    // General settings heading.
    $settings->add(new admin_setting_heading(
        'local_downloadcenter/generalsettings',
        get_string('generalsettings', 'local_downloadcenter'),
        get_string('generalsettings_desc', 'local_downloadcenter')
    ));
    
    // Enable admin multi-course download.
    $settings->add(new admin_setting_configcheckbox(
        'local_downloadcenter/enableadmindownload',
        get_string('enableadmindownload', 'local_downloadcenter'),
        get_string('enableadmindownload_desc', 'local_downloadcenter'),
        1
    ));
    
    // Maximum courses per download.
    $settings->add(new admin_setting_configtext(
        'local_downloadcenter/maxcoursesperdownload',
        get_string('maxcoursesperdownload', 'local_downloadcenter'),
        get_string('maxcoursesperdownload_desc', 'local_downloadcenter'),
        20,
        PARAM_INT
    ));
    
    // Default exclude student content.
    $settings->add(new admin_setting_configcheckbox(
        'local_downloadcenter/excludestudentdefault',
        get_string('excludestudentdefault', 'local_downloadcenter'),
        get_string('excludestudentdefault_desc', 'local_downloadcenter'),
        1
    ));
    
    // Performance settings heading.
    $settings->add(new admin_setting_heading(
        'local_downloadcenter/performancesettings',
        get_string('performancesettings', 'local_downloadcenter'),
        get_string('performancesettings_desc', 'local_downloadcenter')
    ));
    
    // ZIP compression level.
    $options = [
        0 => get_string('compressionstore', 'local_downloadcenter'),
        1 => get_string('compressionfast', 'local_downloadcenter'),
        9 => get_string('compressionbest', 'local_downloadcenter')
    ];
    $settings->add(new admin_setting_configselect(
        'local_downloadcenter/compressionlevel',
        get_string('compressionlevel', 'local_downloadcenter'),
        get_string('compressionlevel_desc', 'local_downloadcenter'),
        1,
        $options
    ));
    
    // Memory limit for ZIP creation.
    $settings->add(new admin_setting_configtext(
        'local_downloadcenter/memorylimit',
        get_string('memorylimit', 'local_downloadcenter'),
        get_string('memorylimit_desc', 'local_downloadcenter'),
        '512M',
        PARAM_TEXT
    ));
    
    // Time limit for ZIP creation.
    $settings->add(new admin_setting_configtext(
        'local_downloadcenter/timelimit',
        get_string('timelimit', 'local_downloadcenter'),
        get_string('timelimit_desc', 'local_downloadcenter'),
        300,
        PARAM_INT
    ));
    
    // Add settings page to admin tree.
    $ADMIN->add('localplugins', $settings);
    
    // Add link to admin download center if enabled.
    if (get_config('local_downloadcenter', 'enableadmindownload')) {
        $ADMIN->add('courses', 
            new admin_externalpage('local_downloadcenter_admin',
                get_string('admindownloadcenter', 'local_downloadcenter'),
                new moodle_url('/local/downloadcenter/index.php', ['mode' => 'admin']),
                'moodle/site:config'
            )
        );
    }
}