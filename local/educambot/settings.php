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
 * Plugin administration pages are defined here.
 *
 * @package     local_educambot
 * @copyright   2025 EducamBot Team
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) {
    // Create settings category.
    $ADMIN->add('localplugins', new admin_category('local_educambot',
        get_string('pluginname', 'local_educambot')));

    // Settings page.
    $settings = new admin_settingpage('local_educambot_settings',
        get_string('settings_header', 'local_educambot'));

    if ($ADMIN->fulltree) {
        // Bot name setting.
        $settings->add(new admin_setting_configtext(
            'local_educambot/botname',
            get_string('botname', 'local_educambot'),
            get_string('botname_desc', 'local_educambot'),
            get_string('botname_default', 'local_educambot'),
            PARAM_TEXT
        ));
    }

    $ADMIN->add('local_educambot', $settings);

    // Manage rules page.
    $ADMIN->add('local_educambot', new admin_externalpage(
        'local_educambot_manage',
        get_string('managerules', 'local_educambot'),
        new moodle_url('/local/educambot/manage.php'),
        'local/educambot:manage'
    ));
}
