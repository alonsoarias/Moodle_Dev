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
 * Settings for Nexus Format.
 *
 * @package    format_nexusformat
 * @copyright  2024 Nexus Learning
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if ($ADMIN->fulltree) {

    // Header - Layout Settings.
    $settings->add(new admin_setting_heading(
        'format_nexusformat/layoutsettings',
        get_string('settings_layout', 'format_nexusformat'),
        get_string('settings_layout_desc', 'format_nexusformat')
    ));

    // Content area width (percentage).
    $settings->add(new admin_setting_configselect(
        'format_nexusformat/contentwidth',
        get_string('settings_contentwidth', 'format_nexusformat'),
        get_string('settings_contentwidth_desc', 'format_nexusformat'),
        '70',
        [
            '60' => '60%',
            '65' => '65%',
            '70' => '70%',
            '75' => '75%',
            '80' => '80%',
        ]
    ));

    // Header - Color Settings.
    $settings->add(new admin_setting_heading(
        'format_nexusformat/colorsettings',
        get_string('settings_colors', 'format_nexusformat'),
        get_string('settings_colors_desc', 'format_nexusformat')
    ));

    // Primary accent color.
    $settings->add(new admin_setting_configcolourpicker(
        'format_nexusformat/accentcolor',
        get_string('settings_accentcolor', 'format_nexusformat'),
        get_string('settings_accentcolor_desc', 'format_nexusformat'),
        '#0d6efd'
    ));

    // Secondary accent color (gradient).
    $settings->add(new admin_setting_configcolourpicker(
        'format_nexusformat/secondarycolor',
        get_string('settings_secondarycolor', 'format_nexusformat'),
        get_string('settings_secondarycolor_desc', 'format_nexusformat'),
        '#764ba2'
    ));

    // Progress bar color.
    $settings->add(new admin_setting_configcolourpicker(
        'format_nexusformat/progresscolor',
        get_string('settings_progresscolor', 'format_nexusformat'),
        get_string('settings_progresscolor_desc', 'format_nexusformat'),
        '#0dcaf0'
    ));

    // Header - Feature Settings.
    $settings->add(new admin_setting_heading(
        'format_nexusformat/featuresettings',
        get_string('settings_features', 'format_nexusformat'),
        get_string('settings_features_desc', 'format_nexusformat')
    ));

    // Enable Activities tab.
    $settings->add(new admin_setting_configcheckbox(
        'format_nexusformat/enableactivitiestab',
        get_string('settings_enableactivitiestab', 'format_nexusformat'),
        get_string('settings_enableactivitiestab_desc', 'format_nexusformat'),
        1
    ));

    // Enable Notes system.
    $settings->add(new admin_setting_configcheckbox(
        'format_nexusformat/enablenotes',
        get_string('settings_enablenotes', 'format_nexusformat'),
        get_string('settings_enablenotes_desc', 'format_nexusformat'),
        1
    ));

    // Enable Comments system.
    $settings->add(new admin_setting_configcheckbox(
        'format_nexusformat/enablecomments',
        get_string('settings_enablecomments', 'format_nexusformat'),
        get_string('settings_enablecomments_desc', 'format_nexusformat'),
        1
    ));

    // Enable Participation banner.
    $settings->add(new admin_setting_configcheckbox(
        'format_nexusformat/enableparticipationbanner',
        get_string('settings_enableparticipationbanner', 'format_nexusformat'),
        get_string('settings_enableparticipationbanner_desc', 'format_nexusformat'),
        1
    ));

    // Header - Visual Settings.
    $settings->add(new admin_setting_heading(
        'format_nexusformat/visualsettings',
        get_string('settings_visual', 'format_nexusformat'),
        get_string('settings_visual_desc', 'format_nexusformat')
    ));

    // Card border radius.
    $settings->add(new admin_setting_configselect(
        'format_nexusformat/cardborderradius',
        get_string('settings_cardborderradius', 'format_nexusformat'),
        get_string('settings_cardborderradius_desc', 'format_nexusformat'),
        '8',
        [
            '0' => get_string('settings_borderradius_none', 'format_nexusformat'),
            '4' => get_string('settings_borderradius_small', 'format_nexusformat'),
            '8' => get_string('settings_borderradius_medium', 'format_nexusformat'),
            '12' => get_string('settings_borderradius_large', 'format_nexusformat'),
            '16' => get_string('settings_borderradius_xlarge', 'format_nexusformat'),
        ]
    ));

    // Enable card shadows.
    $settings->add(new admin_setting_configcheckbox(
        'format_nexusformat/enablecardshadows',
        get_string('settings_enablecardshadows', 'format_nexusformat'),
        get_string('settings_enablecardshadows_desc', 'format_nexusformat'),
        1
    ));

    // Sidebar position.
    $settings->add(new admin_setting_configselect(
        'format_nexusformat/sidebarposition',
        get_string('settings_sidebarposition', 'format_nexusformat'),
        get_string('settings_sidebarposition_desc', 'format_nexusformat'),
        'right',
        [
            'right' => get_string('settings_sidebar_right', 'format_nexusformat'),
            'left' => get_string('settings_sidebar_left', 'format_nexusformat'),
        ]
    ));
}
