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
    // Create settings page.
    $settings = new admin_settingpage('local_chatbot', get_string('pluginname', 'local_chatbot'));

    // Enable/disable setting.
    $settings->add(new admin_setting_configcheckbox(
        'local_chatbot/enabled',
        get_string('setting_enabled', 'local_chatbot'),
        get_string('setting_enabled_desc', 'local_chatbot'),
        1
    ));

    // Assistant name.
    $settings->add(new admin_setting_configtext(
        'local_chatbot/assistantname',
        get_string('setting_assistantname', 'local_chatbot'),
        get_string('setting_assistantname_desc', 'local_chatbot'),
        get_string('chatbot_title', 'local_chatbot'),
        PARAM_TEXT
    ));

    // Position setting.
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

    // Theme setting.
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

    // Welcome message.
    $settings->add(new admin_setting_configtextarea(
        'local_chatbot/welcome_message',
        get_string('setting_welcome', 'local_chatbot'),
        get_string('setting_welcome_desc', 'local_chatbot'),
        get_string('chatbot_welcome_template', 'local_chatbot'),
        PARAM_RAW
    ));

    // Default no match response.
    $settings->add(new admin_setting_configtextarea(
        'local_chatbot/default_nomatch',
        get_string('setting_nomatch', 'local_chatbot'),
        get_string('setting_nomatch_desc', 'local_chatbot'),
        get_string('default_nomatch', 'local_chatbot'),
        PARAM_RAW
    ));

    // Max message length.
    $settings->add(new admin_setting_configtext(
        'local_chatbot/maxlength',
        get_string('setting_maxlength', 'local_chatbot'),
        get_string('setting_maxlength_desc', 'local_chatbot'),
        500,
        PARAM_INT
    ));

    // Allow export.
    $settings->add(new admin_setting_configcheckbox(
        'local_chatbot/allow_export',
        get_string('setting_allow_export', 'local_chatbot'),
        get_string('setting_allow_export_desc', 'local_chatbot'),
        1
    ));

    // Add settings page to admin tree.
    $ADMIN->add('localplugins', $settings);

    // Add admin pages.
    $category = new admin_category('local_chatbot_admin', get_string('pluginname', 'local_chatbot'));
    $ADMIN->add('localplugins', $category);

    // Add management pages.
    $ADMIN->add('local_chatbot_admin', new admin_externalpage(
        'local_chatbot_intents',
        get_string('manage_intents', 'local_chatbot'),
        new moodle_url('/local/chatbot/admin/intents.php'),
        'local/chatbot:manage'
    ));

    $ADMIN->add('local_chatbot_admin', new admin_externalpage(
        'local_chatbot_entities',
        get_string('manage_entities', 'local_chatbot'),
        new moodle_url('/local/chatbot/admin/entities.php'),
        'local/chatbot:manage'
    ));

    $ADMIN->add('local_chatbot_admin', new admin_externalpage(
        'local_chatbot_training',
        get_string('training', 'local_chatbot'),
        new moodle_url('/local/chatbot/admin/training.php'),
        'local/chatbot:manage'
    ));

    $ADMIN->add('local_chatbot_admin', new admin_externalpage(
        'local_chatbot_analytics',
        get_string('analytics', 'local_chatbot'),
        new moodle_url('/local/chatbot/admin/analytics.php'),
        'local/chatbot:manage'
    ));

    $ADMIN->add('local_chatbot_admin', new admin_externalpage(
        'local_chatbot_dialogues',
        get_string('dialogues', 'local_chatbot'),
        new moodle_url('/local/chatbot/admin/dialogues.php'),
        'local/chatbot:manage'
    ));

    $ADMIN->add('local_chatbot_admin', new admin_externalpage(
        'local_chatbot_test',
        get_string('test_chatbot', 'local_chatbot'),
        new moodle_url('/local/chatbot/admin/test.php'),
        'local/chatbot:manage'
    ));
}