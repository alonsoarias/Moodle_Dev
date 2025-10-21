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
 * Settings for local_educambot.
 *
 * @package     local_educambot
 * @copyright   2024 Educam
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) {
    $category = new admin_category('local_educambot', get_string('pluginname', 'local_educambot'));
    $ADMIN->add('localplugins', $category);

    $settingspage = new admin_settingpage('local_educambot_settings', get_string('settingsheading', 'local_educambot'));

    $settingspage->add(new admin_setting_configcheckbox(
        'local_educambot/loggingenabled',
        get_string('loggingenabled', 'local_educambot'),
        get_string('loggingenabled_desc', 'local_educambot'),
        1
    ));

    $settingspage->add(new admin_setting_configtext(
        'local_educambot/retentionperiod',
        get_string('retentionperiod', 'local_educambot'),
        get_string('retentionperiod_desc', 'local_educambot'),
        90,
        PARAM_INT
    ));

    $ADMIN->add('local_educambot', $settingspage);

    $ADMIN->add('local_educambot', new admin_externalpage(
        'local_educambot_manage',
        get_string('manageentries', 'local_educambot'),
        new moodle_url('/local/educambot/manage.php'),
        'local/educambot:manage'
    ));
}
