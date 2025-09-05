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
 * English language strings for local_assign_no_submission_filter
 *
 * @package    local_assign_no_submission_filter
 * @copyright  2024 Your Organization
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['pluginname'] = 'Assignment No Submission Filter';
$string['privacy:metadata'] = 'The Assignment No Submission Filter plugin does not store any personal data.';

// Settings
$string['enabled'] = 'Enable filtering';
$string['enabled_desc'] = 'When enabled, automatically hides students without submissions in grading view.';

$string['filtermode'] = 'Filter mode';
$string['filtermode_desc'] = 'Select how to handle students without submissions.';
$string['mode_hide'] = 'Hide completely';
$string['mode_highlight'] = 'Highlight but show';

$string['applytoroles'] = 'Apply to roles';
$string['applytoroles_desc'] = 'Select which roles will see the filtered view. Default: Teacher and Non-editing teacher.';

$string['autoapply'] = 'Auto-apply filter';
$string['autoapply_desc'] = 'Automatically apply the filter without user intervention.';

$string['hidenosubmission'] = 'Hide students without submissions';

// Capabilities
$string['assign_no_submission_filter:bypassfilter'] = 'Bypass submission filter';
$string['assign_no_submission_filter:configure'] = 'Configure submission filter';