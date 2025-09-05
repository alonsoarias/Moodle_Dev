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
 * Settings for local_assign_no_submission_filter
 *
 * @package    local_assign_no_submission_filter
 * @copyright  2024 Your Organization
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) {
    $settings = new admin_settingpage(
        'local_assign_no_submission_filter',
        get_string('pluginname', 'local_assign_no_submission_filter')
    );

    // Enable/disable setting
    $settings->add(new admin_setting_configcheckbox(
        'local_assign_no_submission_filter/enabled',
        get_string('enabled', 'local_assign_no_submission_filter'),
        get_string('enabled_desc', 'local_assign_no_submission_filter'),
        1
    ));

    // Filter mode
    $options = [
        'hide' => get_string('mode_hide', 'local_assign_no_submission_filter'),
        'highlight' => get_string('mode_highlight', 'local_assign_no_submission_filter'),
    ];
    
    $settings->add(new admin_setting_configselect(
        'local_assign_no_submission_filter/mode',
        get_string('filtermode', 'local_assign_no_submission_filter'),
        get_string('filtermode_desc', 'local_assign_no_submission_filter'),
        'hide',
        $options
    ));

    // Apply to roles
    $roles = role_get_names(\context_system::instance());
    $roleoptions = [];
    foreach ($roles as $role) {
        $roleoptions[$role->id] = $role->localname;
    }

    $settings->add(new admin_setting_configmultiselect(
        'local_assign_no_submission_filter/roles',
        get_string('applytoroles', 'local_assign_no_submission_filter'),
        get_string('applytoroles_desc', 'local_assign_no_submission_filter'),
        [3, 4], // Teacher and Non-editing teacher
        $roleoptions
    ));

    // Auto-apply filter
    $settings->add(new admin_setting_configcheckbox(
        'local_assign_no_submission_filter/autoapply',
        get_string('autoapply', 'local_assign_no_submission_filter'),
        get_string('autoapply_desc', 'local_assign_no_submission_filter'),
        1
    ));

    $ADMIN->add('localplugins', $settings);
}