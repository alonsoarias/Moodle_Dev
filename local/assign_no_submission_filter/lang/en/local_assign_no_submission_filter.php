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
 * English language strings
 *
 * @package    local_assign_no_submission_filter
 * @copyright  2024 Your Organization
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['pluginname'] = 'Assignment No Submission Filter';
$string['privacy:metadata'] = 'The Assignment No Submission Filter plugin does not store any personal data.';

// General settings
$string['general_section'] = 'General Settings';
$string['general_section_desc'] = 'Configure the basic functionality of the submission filter.';
$string['enabled'] = 'Enable automatic filtering';
$string['enabled_desc'] = 'Automatically filter out students without submissions in assignment grading tables.';
$string['filter_downloads'] = 'Apply filter to downloads';
$string['filter_downloads_desc'] = 'Also filter students without submissions when downloading grading worksheets.';
$string['autoapply'] = 'Auto-apply submission filter';
$string['autoapply_desc'] = 'Automatically set the assignment filter to show only submissions when viewing the grading table.';
$string['hide_participant_count'] = 'Hide participant count';
$string['hide_participant_count_desc'] = 'Hide the participant count row in assignment summary tables.';
$string['hide_submitted_count'] = 'Hide submitted count';
$string['hide_submitted_count_desc'] = 'Hide the submitted count row in assignment summary tables.';

// Role settings
$string['roles_section'] = 'Role Configuration';
$string['roles_section_desc'] = 'Select which roles will see the filtered view. Users with these roles will have students without submissions hidden from their view.';
$string['selected_roles'] = 'Selected roles';
$string['selected_roles_desc'] = 'Choose the roles that should see the filtered assignment views. Only users with these roles will have the filter applied.';