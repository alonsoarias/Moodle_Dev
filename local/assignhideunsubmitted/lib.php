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
 * Library functions for local_assignhideunsubmitted
 *
 * @package   local_assignhideunsubmitted
 * @copyright 2024 Your Organization
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Extend navigation - used for debugging
 */
function local_assignhideunsubmitted_extend_navigation(global_navigation $navigation) {
    global $CFG, $PAGE;
    
    // Only in debug mode
    if ($CFG->debug >= DEBUG_DEVELOPER && $PAGE->cm && $PAGE->cm->modname === 'assign') {
        // Check if our override is active
        if (class_exists('\assign')) {
            $reflection = new ReflectionClass('\assign');
            $filename = $reflection->getFileName();
            if (strpos($filename, 'local_assignhideunsubmitted') !== false) {
                debugging('Assignment Hide Unsubmitted: Class override is ACTIVE', DEBUG_DEVELOPER);
            } else {
                debugging('Assignment Hide Unsubmitted: Class override is NOT active - using patch method', DEBUG_DEVELOPER);
            }
        }
    }
}