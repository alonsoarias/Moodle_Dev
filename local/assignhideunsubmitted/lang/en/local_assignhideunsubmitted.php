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
 * Language strings for local_assignhideunsubmitted
 *
 * @package   local_assignhideunsubmitted
 * @copyright 2024 Your Organization
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['pluginname'] = 'Assignment Hide Unsubmitted';
$string['privacy:metadata'] = 'The Assignment Hide Unsubmitted plugin does not store any personal data.';
$string['enabled'] = 'Enable filtering';
$string['enabled_desc'] = 'When enabled, users with the selected role will only see participants who have submitted assignments.';
$string['hiderole'] = 'Apply filter for role';
$string['hiderole_desc'] = 'Users with this role will only see participants who have submitted. Typically set to Teacher or Non-editing teacher.';
$string['cachedef_participants'] = 'Filtered participants cache';
$string['patchstatus'] = 'Core patch status';
$string['patchstatus_desc'] = 'Shows whether the core modification patch is applied.';
$string['patchapplied'] = 'Core patch is applied';
$string['patchnotapplied'] = 'Core patch is NOT applied - filtering may not work';