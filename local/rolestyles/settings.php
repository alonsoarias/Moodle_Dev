<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * Settings for the Role Styles local plugin.
 *
 * @package    local_rolestyles
 * @copyright  2024 Alonso Arias
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/lib.php');

if ($hassiteconfig) {
    $settings = new admin_settingpage('local_rolestyles', get_string('pluginname', 'local_rolestyles'));

    $settings->add(new admin_setting_configcheckbox(
        'local_rolestyles/enabled',
        get_string('enabled', 'local_rolestyles'),
        get_string('enabled_desc', 'local_rolestyles'),
        0
    ));

    $roles = local_rolestyles_get_all_roles();
    $settings->add(new admin_setting_configmultiselect(
        'local_rolestyles/selected_roles',
        get_string('selected_roles', 'local_rolestyles'),
        get_string('selected_roles_desc', 'local_rolestyles'),
        [],
        $roles
    ));

    $settings->add(new admin_setting_configtextarea(
        'local_rolestyles/custom_css',
        get_string('custom_css', 'local_rolestyles'),
        get_string('custom_css_desc', 'local_rolestyles'),
        '',
        PARAM_RAW
    ));

    $ADMIN->add('localplugins', $settings);
}
