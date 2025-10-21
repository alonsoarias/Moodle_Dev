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

    $settingspage->add(new admin_setting_heading(
        'local_educambot_branding',
        get_string('brandingsettings', 'local_educambot'),
        ''
    ));

    $settingspage->add(new admin_setting_configtext(
        'local_educambot/botname',
        get_string('botname', 'local_educambot'),
        get_string('botname_desc', 'local_educambot'),
        get_string('defaultbotname', 'local_educambot'),
        PARAM_TEXT
    ));

    $settingspage->add(new admin_setting_configtext(
        'local_educambot/widgetlabel',
        get_string('widgetlabel', 'local_educambot'),
        get_string('widgetlabel_desc', 'local_educambot'),
        get_string('widgettitle', 'local_educambot'),
        PARAM_TEXT
    ));

    $settingspage->add(new admin_setting_configtextarea(
        'local_educambot/introtemplate',
        get_string('introtemplate', 'local_educambot'),
        get_string('introtemplate_desc', 'local_educambot'),
        get_string('widgetintro', 'local_educambot'),
        PARAM_RAW_TRIMMED
    ));

    $settingspage->add(new admin_setting_configtextarea(
        'local_educambot/greetingtemplate',
        get_string('greetingtemplate', 'local_educambot'),
        get_string('greetingtemplate_desc', 'local_educambot'),
        get_string('defaultgreeting', 'local_educambot'),
        PARAM_RAW_TRIMMED
    ));

    $settingspage->add(new admin_setting_configtext(
        'local_educambot/personalitytagline',
        get_string('personalitytagline', 'local_educambot'),
        get_string('personalitytagline_desc', 'local_educambot'),
        '',
        PARAM_TEXT
    ));

    $settingspage->add(new admin_setting_configcolourpicker(
        'local_educambot/primarycolor',
        get_string('primarycolor', 'local_educambot'),
        get_string('primarycolor_desc', 'local_educambot'),
        '#0f6fc5'
    ));

    $settingspage->add(new admin_setting_configcolourpicker(
        'local_educambot/accentcolor',
        get_string('accentcolor', 'local_educambot'),
        get_string('accentcolor_desc', 'local_educambot'),
        '#e7f0fb'
    ));

    $settingspage->add(new admin_setting_configcolourpicker(
        'local_educambot/backgroundcolor',
        get_string('backgroundcolor', 'local_educambot'),
        get_string('backgroundcolor_desc', 'local_educambot'),
        '#f7f9fc'
    ));

    $settingspage->add(new admin_setting_configcolourpicker(
        'local_educambot/textcolor',
        get_string('textcolor', 'local_educambot'),
        get_string('textcolor_desc', 'local_educambot'),
        '#1f2937'
    ));

    $ADMIN->add('local_educambot', $settingspage);

    $ADMIN->add('local_educambot', new admin_externalpage(
        'local_educambot_manage',
        get_string('manageentries', 'local_educambot'),
        new moodle_url('/local/educambot/manage.php'),
        'local/educambot:manage'
    ));
}
