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
 * @package     local_udesbot
 * @author      Alonso Arias <soporte@orioncloud.com.co>
 * @copyright   2025 OrionCloud<https://orioncloud.com.co>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) {
    // Create settings category.
    $ADMIN->add('localplugins', new admin_category('local_udesbot',
        get_string('pluginname', 'local_udesbot')));

    // Settings page.
    $settings = new admin_settingpage('local_udesbot_settings',
        get_string('settings_header', 'local_udesbot'));

    if ($ADMIN->fulltree) {
        // General section.
        $settings->add(new admin_setting_heading(
            'local_udesbot/general_heading',
            get_string('general_heading', 'local_udesbot'),
            ''
        ));

        // Enable widget setting.
        $settings->add(new admin_setting_configcheckbox(
            'local_udesbot/widgetenabled',
            get_string('widgetenabled', 'local_udesbot'),
            get_string('widgetenabled_desc', 'local_udesbot'),
            1
        ));

        // Bot identity section.
        $settings->add(new admin_setting_heading(
            'local_udesbot/identity_heading',
            get_string('identity_heading', 'local_udesbot'),
            ''
        ));

        // Bot name setting.
        $settings->add(new admin_setting_configtext(
            'local_udesbot/botname',
            get_string('botname', 'local_udesbot'),
            get_string('botname_desc', 'local_udesbot'),
            get_string('botname_default', 'local_udesbot'),
            PARAM_TEXT
        ));

        // Widget label setting.
        $settings->add(new admin_setting_configtext(
            'local_udesbot/widgetlabel',
            get_string('widgetlabel', 'local_udesbot'),
            get_string('widgetlabel_desc', 'local_udesbot'),
            get_string('widgetlabel_default', 'local_udesbot'),
            PARAM_TEXT
        ));

        // Greeting message setting.
        $settings->add(new admin_setting_configtextarea(
            'local_udesbot/greetingtemplate',
            get_string('greetingtemplate', 'local_udesbot'),
            get_string('greetingtemplate_desc', 'local_udesbot'),
            get_string('greeting_default', 'local_udesbot'),
            PARAM_TEXT
        ));

        // Appearance section.
        $settings->add(new admin_setting_heading(
            'local_udesbot/appearance_heading',
            get_string('appearance_heading', 'local_udesbot'),
            ''
        ));

        // Primary color setting.
        $settings->add(new admin_setting_configcolourpicker(
            'local_udesbot/primarycolor',
            get_string('primarycolor', 'local_udesbot'),
            get_string('primarycolor_desc', 'local_udesbot'),
            '#0f6fc5'
        ));

        // Language section (v1.8.0).
        $settings->add(new admin_setting_heading(
            'local_udesbot/language_heading',
            get_string('language_heading', 'local_udesbot'),
            ''
        ));

        // Auto-detect language setting.
        $settings->add(new admin_setting_configcheckbox(
            'local_udesbot/autolang',
            get_string('autolang', 'local_udesbot'),
            get_string('autolang_desc', 'local_udesbot'),
            1
        ));

        // History section (v1.9.0).
        $settings->add(new admin_setting_heading(
            'local_udesbot/history_heading',
            get_string('history_heading', 'local_udesbot'),
            ''
        ));

        // Enable conversation history setting.
        $settings->add(new admin_setting_configcheckbox(
            'local_udesbot/enablehistory',
            get_string('enablehistory', 'local_udesbot'),
            get_string('enablehistory_desc', 'local_udesbot'),
            1
        ));

        // History retention period (v1.9.5).
        $retentionoptions = [
            0 => get_string('retention_forever', 'local_udesbot'),
            7 => get_string('retention_1week', 'local_udesbot'),
            30 => get_string('retention_1month', 'local_udesbot'),
            90 => get_string('retention_3months', 'local_udesbot'),
            180 => get_string('retention_6months', 'local_udesbot'),
            365 => get_string('retention_1year', 'local_udesbot'),
        ];
        $settings->add(new admin_setting_configselect(
            'local_udesbot/historyretention',
            get_string('historyretention', 'local_udesbot'),
            get_string('historyretention_desc', 'local_udesbot'),
            90, // Default: 3 months.
            $retentionoptions
        ));

        // Timeout section (v1.9.0).
        $settings->add(new admin_setting_heading(
            'local_udesbot/timeout_heading',
            get_string('timeout_heading', 'local_udesbot'),
            ''
        ));

        // Inactivity timeout setting.
        $settings->add(new admin_setting_configtext(
            'local_udesbot/inactivitytimeout',
            get_string('inactivitytimeout', 'local_udesbot'),
            get_string('inactivitytimeout_desc', 'local_udesbot'),
            600000,
            PARAM_INT
        ));

        // Feedback section (v3.5.0).
        $settings->add(new admin_setting_heading(
            'local_udesbot/feedback_heading',
            get_string('feedback_heading', 'local_udesbot'),
            ''
        ));

        // Enable feedback setting.
        $settings->add(new admin_setting_configcheckbox(
            'local_udesbot/enablefeedback',
            get_string('enablefeedback', 'local_udesbot'),
            get_string('enablefeedback_desc', 'local_udesbot'),
            1
        ));
    }

    $ADMIN->add('local_udesbot', $settings);

    // Manage rules page.
    $ADMIN->add('local_udesbot', new admin_externalpage(
        'local_udesbot_manage',
        get_string('managerules', 'local_udesbot'),
        new moodle_url('/local/udesbot/manage.php'),
        'local/udesbot:manage'
    ));

    // Reports page.
    $ADMIN->add('local_udesbot', new admin_externalpage(
        'local_udesbot_reports',
        get_string('reports', 'local_udesbot'),
        new moodle_url('/local/udesbot/reports.php'),
        'local/udesbot:manage'
    ));

    // Categories page.
    $ADMIN->add('local_udesbot', new admin_externalpage(
        'local_udesbot_categories',
        get_string('categories', 'local_udesbot'),
        new moodle_url('/local/udesbot/categories.php'),
        'local/udesbot:manage'
    ));

    // Import/Export page.
    $ADMIN->add('local_udesbot', new admin_externalpage(
        'local_udesbot_importexport',
        get_string('importexport', 'local_udesbot'),
        new moodle_url('/local/udesbot/import.php'),
        'local/udesbot:manage'
    ));

    // Shortcuts page (v1.7.0).
    $ADMIN->add('local_udesbot', new admin_externalpage(
        'local_udesbot_shortcuts',
        get_string('shortcuts', 'local_udesbot'),
        new moodle_url('/local/udesbot/shortcuts.php'),
        'local/udesbot:manage'
    ));

    // Themes page (v1.8.0).
    $ADMIN->add('local_udesbot', new admin_externalpage(
        'local_udesbot_themes',
        get_string('managethemes', 'local_udesbot'),
        new moodle_url('/local/udesbot/themes.php'),
        'local/udesbot:manage'
    ));
}
