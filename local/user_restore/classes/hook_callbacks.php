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
 * Hook callbacks for local_user_restore plugin.
 *
 * @package    local_user_restore
 * @copyright  2024 Your Institution
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_user_restore;

defined('MOODLE_INTERNAL') || die();

/**
 * Hook callback class for capturing user data before deletion.
 */
class hook_callbacks {

    /**
     * Callback executed before a user is deleted.
     *
     * This captures all user data that will be deleted so it can be restored later.
     *
     * @param \core\hook\user\before_user_deleted $hook The hook instance.
     */
    public static function before_user_deleted(\core\hook\user\before_user_deleted $hook) {
        $user = $hook->user;

        // Check if snapshot is enabled.
        if (!get_config('local_user_restore', 'enablesnapshot')) {
            return;
        }

        try {
            $snapshot = new snapshot_manager($user->id);
            $snapshot->capture_all();
        } catch (\Exception $e) {
            debugging('local_user_restore: Failed to capture user data snapshot: ' . $e->getMessage(), DEBUG_DEVELOPER);
        }
    }
}
