<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * Role Styles Plugin - Admin settings
 *
 * @package    local_rolestyles
 * @copyright  2024 Alonso Arias <soporte@ingeweb.co> - aulatecnos.es - tecnoszubia.es
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/local/rolestyles/lib.php');

if ($hassiteconfig) {
    $settings = new admin_settingpage('local_rolestyles', get_string('pluginname', 'local_rolestyles'));

    // Plugin title and description
    $settings->add(new admin_setting_heading(
        'local_rolestyles/header',
        get_string('pluginname', 'local_rolestyles'),
        get_string('plugin_description', 'local_rolestyles')
    ));

    // Enable/disable plugin
    $settings->add(new admin_setting_configcheckbox(
        'local_rolestyles/enabled',
        get_string('enabled', 'local_rolestyles'),
        get_string('enabled_desc', 'local_rolestyles'),
        1
    ));

    // Role selection section
    $settings->add(new admin_setting_heading(
        'local_rolestyles/roles_section',
        get_string('roles_section', 'local_rolestyles'),
        get_string('roles_section_desc', 'local_rolestyles')
    ));

    // Get all system roles
    $roles_array = local_rolestyles_get_all_roles();

    // Multiselect for role selection
    $settings->add(new admin_setting_configmultiselect(
        'local_rolestyles/selected_roles',
        get_string('selected_roles', 'local_rolestyles'),
        get_string('selected_roles_desc', 'local_rolestyles'),
        array(),
        $roles_array
    ));

    // CSS editor section
    $settings->add(new admin_setting_heading(
        'local_rolestyles/css_section',
        get_string('css_section', 'local_rolestyles'),
        get_string('css_section_desc', 'local_rolestyles')
    ));

    // CSS textarea - Enhanced version
    $css_setting = new admin_setting_configtextarea(
        'local_rolestyles/custom_css',
        get_string('custom_css', 'local_rolestyles'),
        get_string('custom_css_desc', 'local_rolestyles'),
        '',
        PARAM_RAW,
        '80',
        '20'
    );
    $settings->add($css_setting);

    // CSS examples
    $settings->add(new admin_setting_heading(
        'local_rolestyles/css_examples',
        get_string('css_examples_title', 'local_rolestyles'),
        get_string('css_examples', 'local_rolestyles')
    ));

    // Advanced settings
    $settings->add(new admin_setting_heading(
        'local_rolestyles/advanced_section',
        get_string('advanced_section', 'local_rolestyles'),
        get_string('advanced_section_desc', 'local_rolestyles')
    ));

    // Course categories filter (optional)
    $settings->add(new admin_setting_configtext(
        'local_rolestyles/course_categories',
        get_string('course_categories', 'local_rolestyles'),
        get_string('course_categories_desc', 'local_rolestyles'),
        '',
        PARAM_TEXT
    ));

    // Developer information
    $settings->add(new admin_setting_heading(
        'local_rolestyles/developer_info',
        get_string('developer_info', 'local_rolestyles'),
        get_string('developer_info_desc', 'local_rolestyles')
    ));

    // Include CSS and JS for enhanced editor if we're on the settings page
    global $PAGE;
    if (isset($PAGE) && $PAGE instanceof moodle_page) {
        $current_url = $PAGE->url->out(false);
        if (strpos($current_url, 'section=local_rolestyles') !== false) {
            // We're on the Role Styles settings page
            $PAGE->requires->css('/local/rolestyles/assets/css-editor.css');
            $PAGE->requires->js('/local/rolestyles/assets/css-editor.js');
        }
    }

    // Add to admin tree
    $ADMIN->add('localplugins', $settings);
}