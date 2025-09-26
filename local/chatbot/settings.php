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
 * Settings for the local_chatbot plugin.
 *
 * @package    local_chatbot
 * @copyright  2024 Moodle Community
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) {
    $settings = new admin_settingpage('local_chatbot', get_string('pluginname', 'local_chatbot'));

    $settings->add(new admin_setting_configcheckbox(
        'local_chatbot/enabled',
        get_string('setting_enabled', 'local_chatbot'),
        get_string('setting_enabled_desc', 'local_chatbot'),
        1
    ));

    $settings->add(new admin_setting_configtext(
        'local_chatbot/assistantname',
        get_string('setting_assistantname', 'local_chatbot'),
        get_string('setting_assistantname_desc', 'local_chatbot'),
        get_string('chatbot_title', 'local_chatbot'),
        PARAM_TEXT
    ));

    $settings->add(new admin_setting_configselect(
        'local_chatbot/position',
        get_string('setting_position', 'local_chatbot'),
        get_string('setting_position_desc', 'local_chatbot'),
        'bottom_right',
        [
            'bottom_right' => get_string('position_bottom_right', 'local_chatbot'),
            'bottom_left' => get_string('position_bottom_left', 'local_chatbot'),
        ]
    ));

    $settings->add(new admin_setting_configselect(
        'local_chatbot/theme',
        get_string('setting_theme', 'local_chatbot'),
        get_string('setting_theme_desc', 'local_chatbot'),
        'modern',
        [
            'modern' => get_string('theme_modern', 'local_chatbot'),
            'minimal' => get_string('theme_minimal', 'local_chatbot'),
            'dark' => get_string('theme_dark', 'local_chatbot'),
        ]
    ));

    $settings->add(new admin_setting_configtextarea(
        'local_chatbot/welcome_message',
        get_string('setting_welcome', 'local_chatbot'),
        get_string('setting_welcome_desc', 'local_chatbot'),
        get_string('chatbot_welcome_template', 'local_chatbot'),
        PARAM_RAW
    ));

    $settings->add(new admin_setting_configtextarea(
        'local_chatbot/default_nomatch',
        get_string('setting_nomatch', 'local_chatbot'),
        get_string('setting_nomatch_desc', 'local_chatbot'),
        get_string('default_nomatch', 'local_chatbot'),
        PARAM_RAW
    ));

    $settings->add(new admin_setting_configtext(
        'local_chatbot/maxlength',
        get_string('setting_maxlength', 'local_chatbot'),
        get_string('setting_maxlength_desc', 'local_chatbot'),
        500,
        PARAM_INT
    ));

    $settings->add(new admin_setting_configcheckbox(
        'local_chatbot/allow_export',
        get_string('setting_allow_export', 'local_chatbot'),
        get_string('setting_allow_export_desc', 'local_chatbot'),
        1
    ));

    $ADMIN->add('localplugins', $settings);
}
