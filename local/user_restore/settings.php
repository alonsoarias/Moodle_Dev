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
 * Settings for local_user_restore plugin.
 *
 * @package    local_user_restore
 * @copyright  2024 Your Institution
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) {
    // Create settings page.
    $settings = new admin_settingpage(
        'local_user_restore',
        get_string('pluginname', 'local_user_restore')
    );

    // Add link to manage deleted users page in the Users menu.
    $ADMIN->add('users', new admin_externalpage(
        'local_user_restore_manage',
        get_string('managedeletedusers', 'local_user_restore'),
        new moodle_url('/local/user_restore/index.php'),
        'local/user_restore:view'
    ));

    // Enable logging setting.
    $settings->add(new admin_setting_configcheckbox(
        'local_user_restore/enablelogging',
        get_string('enablelogging', 'local_user_restore'),
        get_string('enablelogging_desc', 'local_user_restore'),
        1
    ));

    // Add settings page to local plugins.
    $ADMIN->add('localplugins', $settings);
}
