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
 * Event observer class for local_user_restore plugin.
 *
 * Captures user data before deletion for later restoration.
 *
 * @package    local_user_restore
 * @copyright  2024 Your Institution
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_user_restore;

defined('MOODLE_INTERNAL') || die();

/**
 * Observer class to capture user data when a user is being deleted.
 */
class observer {

    /**
     * Handle user_deleted event.
     *
     * This captures user enrollments, group memberships, cohort memberships,
     * role assignments, and grades before they are permanently deleted.
     *
     * @param \core\event\user_deleted $event The event.
     */
    public static function user_deleted(\core\event\user_deleted $event) {
        global $DB;

        $userid = $event->objectid;

        // Check if user data snapshot is enabled.
        if (!get_config('local_user_restore', 'enablesnapshot')) {
            return;
        }

        try {
            $snapshot = new snapshot_manager($userid);
            $snapshot->capture_all();
        } catch (\Exception $e) {
            // Log error but don't prevent deletion.
            debugging('local_user_restore: Failed to capture user data snapshot: ' . $e->getMessage(), DEBUG_DEVELOPER);
        }
    }
}
