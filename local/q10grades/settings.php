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
 * Settings for the Q10 Grades Sync plugin.
 *
 * @package    local_q10grades
 * @copyright  2025 Your Institution
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) {
    $settings = new admin_settingpage('local_q10grades', get_string('pluginname', 'local_q10grades'));
    $ADMIN->add('localplugins', $settings);

    // API Configuration section.
    $settings->add(new admin_setting_heading(
        'local_q10grades/apiheading',
        get_string('apisettings', 'local_q10grades'),
        get_string('apisettings_desc', 'local_q10grades')
    ));

    // API URL.
    $settings->add(new admin_setting_configtext(
        'local_q10grades/apiurl',
        get_string('apiurl', 'local_q10grades'),
        get_string('apiurl_desc', 'local_q10grades'),
        'https://api.q10.com/v1',
        PARAM_URL
    ));

    // API Key.
    $settings->add(new admin_setting_configpasswordunmask(
        'local_q10grades/apikey',
        get_string('apikey', 'local_q10grades'),
        get_string('apikey_desc', 'local_q10grades'),
        ''
    ));

    // API Secret.
    $settings->add(new admin_setting_configpasswordunmask(
        'local_q10grades/apisecret',
        get_string('apisecret', 'local_q10grades'),
        get_string('apisecret_desc', 'local_q10grades'),
        ''
    ));

    // Institution ID (Q10 specific).
    $settings->add(new admin_setting_configtext(
        'local_q10grades/institutionid',
        get_string('institutionid', 'local_q10grades'),
        get_string('institutionid_desc', 'local_q10grades'),
        '',
        PARAM_ALPHANUMEXT
    ));

    // Sync Configuration section.
    $settings->add(new admin_setting_heading(
        'local_q10grades/syncheading',
        get_string('syncsettings', 'local_q10grades'),
        get_string('syncsettings_desc', 'local_q10grades')
    ));

    // Enable automatic sync.
    $settings->add(new admin_setting_configcheckbox(
        'local_q10grades/enableautosync',
        get_string('enableautosync', 'local_q10grades'),
        get_string('enableautosync_desc', 'local_q10grades'),
        0
    ));

    // Sync frequency (in hours).
    $frequencies = [
        1 => get_string('hourly', 'local_q10grades'),
        6 => get_string('every6hours', 'local_q10grades'),
        12 => get_string('every12hours', 'local_q10grades'),
        24 => get_string('daily', 'local_q10grades'),
        168 => get_string('weekly', 'local_q10grades'),
    ];
    $settings->add(new admin_setting_configselect(
        'local_q10grades/syncfrequency',
        get_string('syncfrequency', 'local_q10grades'),
        get_string('syncfrequency_desc', 'local_q10grades'),
        24,
        $frequencies
    ));

    // User mapping field.
    $mappingfields = [
        'idnumber' => get_string('idnumber'),
        'username' => get_string('username'),
        'email' => get_string('email'),
    ];
    $settings->add(new admin_setting_configselect(
        'local_q10grades/usermappingfield',
        get_string('usermappingfield', 'local_q10grades'),
        get_string('usermappingfield_desc', 'local_q10grades'),
        'idnumber',
        $mappingfields
    ));

    // Course mapping field.
    $coursemappingfields = [
        'idnumber' => get_string('idnumber'),
        'shortname' => get_string('shortname'),
    ];
    $settings->add(new admin_setting_configselect(
        'local_q10grades/coursemappingfield',
        get_string('coursemappingfield', 'local_q10grades'),
        get_string('coursemappingfield_desc', 'local_q10grades'),
        'idnumber',
        $coursemappingfields
    ));

    // Grade scale.
    $gradescales = [
        'percentage' => get_string('percentage', 'local_q10grades'),
        'raw' => get_string('rawgrade', 'local_q10grades'),
        'letter' => get_string('lettergrade', 'local_q10grades'),
    ];
    $settings->add(new admin_setting_configselect(
        'local_q10grades/gradescale',
        get_string('gradescale', 'local_q10grades'),
        get_string('gradescale_desc', 'local_q10grades'),
        'percentage',
        $gradescales
    ));

    // Debug mode.
    $settings->add(new admin_setting_configcheckbox(
        'local_q10grades/debug',
        get_string('debugmode', 'local_q10grades'),
        get_string('debugmode_desc', 'local_q10grades'),
        0
    ));
}
