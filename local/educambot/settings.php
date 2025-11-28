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
        // General section.
        $settings->add(new admin_setting_heading(
            'local_educambot/general_heading',
            get_string('general_heading', 'local_educambot'),
            ''
        ));

        // Enable widget setting.
        $settings->add(new admin_setting_configcheckbox(
            'local_educambot/widgetenabled',
            get_string('widgetenabled', 'local_educambot'),
            get_string('widgetenabled_desc', 'local_educambot'),
            1
        ));

        // Bot identity section.
        $settings->add(new admin_setting_heading(
            'local_educambot/identity_heading',
            get_string('identity_heading', 'local_educambot'),
            ''
        ));

        // Bot name setting.
        $settings->add(new admin_setting_configtext(
            'local_educambot/botname',
            get_string('botname', 'local_educambot'),
            get_string('botname_desc', 'local_educambot'),
            get_string('botname_default', 'local_educambot'),
            PARAM_TEXT
        ));

        // Widget label setting.
        $settings->add(new admin_setting_configtext(
            'local_educambot/widgetlabel',
            get_string('widgetlabel', 'local_educambot'),
            get_string('widgetlabel_desc', 'local_educambot'),
            get_string('widgetlabel_default', 'local_educambot'),
            PARAM_TEXT
        ));

        // Greeting message setting.
        $settings->add(new admin_setting_configtextarea(
            'local_educambot/greetingtemplate',
            get_string('greetingtemplate', 'local_educambot'),
            get_string('greetingtemplate_desc', 'local_educambot'),
            get_string('greeting_default', 'local_educambot'),
            PARAM_TEXT
        ));

        // Appearance section.
        $settings->add(new admin_setting_heading(
            'local_educambot/appearance_heading',
            get_string('appearance_heading', 'local_educambot'),
            ''
        ));

        // Primary color setting.
        $settings->add(new admin_setting_configcolourpicker(
            'local_educambot/primarycolor',
            get_string('primarycolor', 'local_educambot'),
            get_string('primarycolor_desc', 'local_educambot'),
            '#0f6fc5'
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

    // Reports page.
    $ADMIN->add('local_educambot', new admin_externalpage(
        'local_educambot_reports',
        get_string('reports', 'local_educambot'),
        new moodle_url('/local/educambot/reports.php'),
        'local/educambot:manage'
    ));

    // Categories page.
    $ADMIN->add('local_educambot', new admin_externalpage(
        'local_educambot_categories',
        get_string('categories', 'local_educambot'),
        new moodle_url('/local/educambot/categories.php'),
        'local/educambot:manage'
    ));

    // Import/Export page.
    $ADMIN->add('local_educambot', new admin_externalpage(
        'local_educambot_importexport',
        get_string('importexport', 'local_educambot'),
        new moodle_url('/local/educambot/import.php'),
        'local/educambot:manage'
    ));
}
