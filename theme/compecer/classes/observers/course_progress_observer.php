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
 * Event observer for course progress cache invalidation.
 *
 * @package   theme_compecer
 * @copyright 2024 IngeWeb
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace theme_compecer\observers;

use theme_compecer\course_progress_service;

defined('MOODLE_INTERNAL') || die();

/**
 * Observer class for course progress events.
 *
 * Handles cache invalidation when completion events occur.
 */
class course_progress_observer {

    /**
     * Invalidate cached progress for a specific user when their completion changes.
     *
     * This is triggered when:
     * - A user completes an activity
     * - Course completion status changes
     *
     * @param \core\event\base $event The event that triggered this observer.
     * @return void
     */
    public static function invalidate_user_progress(\core\event\base $event): void {
        // Get course and user from event.
        $courseid = $event->courseid;
        $userid = $event->userid ?? $event->relateduserid ?? null;

        if (!$courseid || !$userid) {
            return;
        }

        try {
            course_progress_service::invalidate_cache($courseid, $userid);
        } catch (\Exception $e) {
            // Log but don't fail - cache invalidation is not critical.
            debugging(
                'Failed to invalidate course progress cache: ' . $e->getMessage(),
                DEBUG_DEVELOPER
            );
        }
    }

    /**
     * Purge all cached progress for a course when structure changes.
     *
     * This is triggered when:
     * - Completion defaults are updated
     * - Course modules are created or deleted
     * - Course structure changes
     *
     * @param \core\event\base $event The event that triggered this observer.
     * @return void
     */
    public static function purge_course_progress(\core\event\base $event): void {
        $courseid = $event->courseid;

        if (!$courseid) {
            return;
        }

        try {
            course_progress_service::purge_course_cache($courseid);
        } catch (\Exception $e) {
            // Log but don't fail - cache invalidation is not critical.
            debugging(
                'Failed to purge course progress cache: ' . $e->getMessage(),
                DEBUG_DEVELOPER
            );
        }
    }
}
