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
 * Settings for local_assign_no_submission_filter
 *
 * @package    local_assign_no_submission_filter
 * @copyright  2024 Your Organization
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) {
    require_once($CFG->dirroot . '/local/assign_no_submission_filter/lib.php');
    
    $settings = new admin_settingpage(
        'local_assign_no_submission_filter',
        get_string('pluginname', 'local_assign_no_submission_filter')
    );
    
    // General settings section
    $settings->add(new admin_setting_heading(
        'local_assign_no_submission_filter/general_section',
        get_string('general_section', 'local_assign_no_submission_filter'),
        get_string('general_section_desc', 'local_assign_no_submission_filter')
    ));
    
    // Enable/disable plugin
    $settings->add(new admin_setting_configcheckbox(
        'local_assign_no_submission_filter/enabled',
        get_string('enabled', 'local_assign_no_submission_filter'),
        get_string('enabled_desc', 'local_assign_no_submission_filter'),
        1
    ));
    
    // Filter downloads
    $settings->add(new admin_setting_configcheckbox(
        'local_assign_no_submission_filter/filter_downloads',
        get_string('filter_downloads', 'local_assign_no_submission_filter'),
        get_string('filter_downloads_desc', 'local_assign_no_submission_filter'),
        0
    ));
    
    // Hide participant count
    $settings->add(new admin_setting_configcheckbox(
        'local_assign_no_submission_filter/hide_participant_count',
        get_string('hide_participant_count', 'local_assign_no_submission_filter'),
        get_string('hide_participant_count_desc', 'local_assign_no_submission_filter'),
        1
    ));
    
    // Role selection section
    $settings->add(new admin_setting_heading(
        'local_assign_no_submission_filter/roles_section',
        get_string('roles_section', 'local_assign_no_submission_filter'),
        get_string('roles_section_desc', 'local_assign_no_submission_filter')
    ));
    
    // Get all system roles
    $roles_array = local_assign_no_submission_filter_get_all_roles();
    
    // Multiselect for role selection
    $settings->add(new admin_setting_configmultiselect(
        'local_assign_no_submission_filter/selected_roles',
        get_string('selected_roles', 'local_assign_no_submission_filter'),
        get_string('selected_roles_desc', 'local_assign_no_submission_filter'),
        array(3, 4), // Default: editingteacher (3) and teacher (4)
        $roles_array
    ));
    
    $ADMIN->add('localplugins', $settings);
}