<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * Role Styles Plugin - English language strings
 *
 * @package    local_rolestyles
 * @copyright  2024 Alonso Arias <soporte@ingeweb.co> - aulatecnos.es - tecnoszubia.es
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['pluginname'] = 'Role Styles';
$string['privacy:metadata'] = 'The Role Styles plugin does not store any personal user data.';

// Plugin information
$string['plugin_description'] = 'This plugin allows applying custom CSS styles based on user roles globally across the entire platform. Styles are automatically applied on all Moodle pages for selected roles.';

// General settings
$string['enabled'] = 'Enable plugin';
$string['enabled_desc'] = 'Enable or disable the application of user role-based styles';

// Roles section
$string['roles_section'] = 'Role Selection';
$string['roles_section_desc'] = 'Select the roles you want to apply custom CSS styles to globally across the platform';
$string['selected_roles'] = 'Selected roles';
$string['selected_roles_desc'] = 'Hold Ctrl (or Cmd on Mac) to select multiple roles. Users with these roles will see the custom styles throughout the entire Moodle platform.';

// CSS section
$string['css_section'] = 'Custom CSS Editor';
$string['css_section_desc'] = 'Write here the CSS styles you want to apply globally to users with selected roles';
$string['custom_css'] = 'Custom CSS';
$string['custom_css_desc'] = 'Enter your custom CSS code. Styles will be applied globally across the platform. You can use auto-generated classes like .role-teacher, .role-student, .roleid-5, etc.';

// CSS examples
$string['css_examples_title'] = 'CSS Examples';
$string['css_examples'] = '<strong>CSS examples you can use:</strong><br><br>
<code>
/* Change background color for teachers */<br>
.role-teacher .main-content {<br>
&nbsp;&nbsp;&nbsp;&nbsp;background-color: #e8f5e8;<br>
&nbsp;&nbsp;&nbsp;&nbsp;border-left: 4px solid #28a745;<br>
}<br><br>

/* Change navbar for students */<br>
.role-student .navbar {<br>
&nbsp;&nbsp;&nbsp;&nbsp;background-color: #007bff !important;<br>
}<br><br>

/* Styles for specific role by ID */<br>
.roleid-5 .course-content {<br>
&nbsp;&nbsp;&nbsp;&nbsp;box-shadow: 0 2px 10px rgba(0,0,0,0.1);<br>
}<br><br>

/* Apply to multiple roles */<br>
.role-teacher, .role-editingteacher {<br>
&nbsp;&nbsp;&nbsp;&nbsp;/* Styles for teachers */<br>
}<br>
</code>';

// Advanced settings
$string['advanced_section'] = 'Advanced Settings';
$string['advanced_section_desc'] = 'Additional options for advanced users';
$string['course_categories'] = 'Course categories (IDs)';
$string['course_categories_desc'] = 'Comma-separated category IDs. Leave empty to apply to all courses. Example: 1,5,10';

// Developer information
$string['developer_info'] = 'Credits';
$string['developer_info_desc'] = '<strong>This plugin has been created for another success case of <a href="https://ingeweb.co" target="_blank" style="color: #007bff; text-decoration: none; font-weight: bold;">IngeWeb</a>.</strong><br>';

// System messages
$string['role_styles_applied'] = 'Styles applied for roles: {$a}';
$string['no_roles_found'] = 'No matching roles found for this user';

// Errors
$string['error_no_roles_selected'] = 'No roles have been selected. Please select at least one role in settings.';
$string['error_invalid_css'] = 'CSS contains elements not allowed for security reasons.';
$string['error_saving_config'] = 'Error saving configuration: {$a}';