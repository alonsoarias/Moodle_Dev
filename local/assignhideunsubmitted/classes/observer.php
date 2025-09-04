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
 * Event observer
 *
 * @package   local_assignhideunsubmitted
 * @copyright 2024
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_assignhideunsubmitted;

defined('MOODLE_INTERNAL') || die();

class observer {
    
    /**
     * Handle grading table viewed event
     * 
     * @param \mod_assign\event\grading_table_viewed $event
     */
    public static function grading_table_viewed(\mod_assign\event\grading_table_viewed $event) {
        global $PAGE, $USER, $CFG;
        
        // Add JavaScript to filter the table client-side
        if (self::should_filter($USER->id, $event->contextid)) {
            $PAGE->requires->js_call_amd(
                'local_assignhideunsubmitted/filter', 
                'init',
                [$event->objectid]
            );
        }
    }
    
    /**
     * Check if filtering should be applied
     */
    protected static function should_filter($userid, $contextid) {
        if (!get_config('local_assignhideunsubmitted', 'enabled')) {
            return false;
        }
        
        $roleid = (int)get_config('local_assignhideunsubmitted', 'hiderole');
        if (!$roleid) {
            return false;
        }
        
        $context = \context::instance_by_id($contextid);
        $roles = get_user_roles($context, $userid, true);
        
        foreach ($roles as $role) {
            if ((int)$role->roleid === $roleid) {
                return true;
            }
        }
        
        return false;
    }
}