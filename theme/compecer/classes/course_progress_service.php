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
 * Course progress service helpers.
 *
 * @package   theme_compecer
 * @copyright 2024 IngeWeb
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace theme_compecer;

defined('MOODLE_INTERNAL') || die();

require_once($GLOBALS['CFG']->libdir . '/completionlib.php');

use cache;
use completion_info;
use core_completion\progress;
use stdClass;

/**
 * Course progress service with caching and enhanced features.
 *
 * Provides methods to calculate and retrieve course completion progress
 * with optional caching for improved performance.
 */
class course_progress_service {

    /** Cache definition name for progress data */
    const CACHE_AREA = 'courseprogress';

    /** Cache TTL in seconds (5 minutes) */
    const CACHE_TTL = 300;

    /**
     * Build progress summary for a given course and user.
     *
     * This method calculates comprehensive progress information including:
     * - Completion status (enabled/disabled)
     * - Percentage of completion
     * - Total activities with completion tracking
     * - Number of completed activities
     * - Number of incomplete activities
     * - Formatted text string ("X of Y completed")
     *
     * @param stdClass $course Moodle course record.
     * @param int $userid The user identifier.
     * @param bool $usecache Whether to use cached data (default: false for real-time updates).
     * @return array{
     *     hascompletion: bool,
     *     percentage: int,
     *     total: int,
     *     completed: int,
     *     incomplete: int,
     *     progresstext: string,
     *     timecalculated: int
     * }
     */
    public static function get_course_progress_summary(
        stdClass $course,
        int $userid,
        bool $usecache = false
    ): array {
        // Try to get from cache if requested
        if ($usecache) {
            $cached = self::get_from_cache($course->id, $userid);
            if ($cached !== null) {
                return $cached;
            }
        }

        $completion = new completion_info($course);

        // Return empty progress if completion is not enabled
        if (!$completion->is_enabled()) {
            $result = [
                'hascompletion' => false,
                'percentage' => 0,
                'total' => 0,
                'completed' => 0,
                'incomplete' => 0,
                'progresstext' => get_string('completionnotenabled', 'completion'),
                'timecalculated' => time(),
            ];
            return $result;
        }

        // Get all activities with completion tracking
        $activities = $completion->get_activities();
        $total = count($activities);
        $completed = 0;

        // Count completed activities
        foreach ($activities as $cm) {
            $data = $completion->get_data($cm, true, $userid);
            // Consider any completion state other than INCOMPLETE as completed
            if ($data->completionstate != COMPLETION_INCOMPLETE) {
                $completed++;
            }
        }

        // Get overall percentage from core API
        $percentage = progress::get_course_progress_percentage($course, $userid);
        $percentage = $percentage === null ? 0 : (int)round($percentage);

        // Build human-readable progress text
        $progresstext = self::format_progress_text($completed, $total);

        $result = [
            'hascompletion' => true,
            'percentage' => $percentage,
            'total' => $total,
            'completed' => $completed,
            'incomplete' => max($total - $completed, 0),
            'progresstext' => $progresstext,
            'timecalculated' => time(),
        ];

        // Store in cache for future requests
        if ($usecache) {
            self::set_in_cache($course->id, $userid, $result);
        }

        return $result;
    }

    /**
     * Format progress text in a human-readable way.
     *
     * Returns strings like:
     * - "3 of 10 activities completed"
     * - "All activities completed!"
     * - "No activities yet"
     *
     * @param int $completed Number of completed activities.
     * @param int $total Total number of activities.
     * @return string Formatted progress text.
     */
    private static function format_progress_text(int $completed, int $total): string {
        if ($total === 0) {
            return get_string('noactivities', 'theme_compecer');
        }

        if ($completed >= $total) {
            return get_string('allactivitiescompleted', 'theme_compecer');
        }

        return get_string(
            'activitiescompletedcount',
            'theme_compecer',
            ['completed' => $completed, 'total' => $total]
        );
    }

    /**
     * Get progress data from cache.
     *
     * @param int $courseid Course identifier.
     * @param int $userid User identifier.
     * @return array|null Cached progress data or null if not found/expired.
     */
    private static function get_from_cache(int $courseid, int $userid): ?array {
        try {
            $cache = cache::make('theme_compecer', self::CACHE_AREA);
            $key = self::get_cache_key($courseid, $userid);
            $data = $cache->get($key);

            if ($data !== false) {
                // Check if cache is still valid (within TTL)
                if (isset($data['timecalculated']) &&
                    (time() - $data['timecalculated']) < self::CACHE_TTL) {
                    return $data;
                }
            }
        } catch (\Exception $e) {
            // If cache fails, continue without it
            debugging('Cache retrieval failed: ' . $e->getMessage(), DEBUG_DEVELOPER);
        }

        return null;
    }

    /**
     * Store progress data in cache.
     *
     * @param int $courseid Course identifier.
     * @param int $userid User identifier.
     * @param array $data Progress data to cache.
     * @return void
     */
    private static function set_in_cache(int $courseid, int $userid, array $data): void {
        try {
            $cache = cache::make('theme_compecer', self::CACHE_AREA);
            $key = self::get_cache_key($courseid, $userid);
            $cache->set($key, $data);
        } catch (\Exception $e) {
            // If cache fails, continue without it
            debugging('Cache storage failed: ' . $e->getMessage(), DEBUG_DEVELOPER);
        }
    }

    /**
     * Invalidate cached progress for a specific user and course.
     *
     * Call this when completion data changes.
     *
     * @param int $courseid Course identifier.
     * @param int $userid User identifier.
     * @return void
     */
    public static function invalidate_cache(int $courseid, int $userid): void {
        try {
            $cache = cache::make('theme_compecer', self::CACHE_AREA);
            $key = self::get_cache_key($courseid, $userid);
            $cache->delete($key);
        } catch (\Exception $e) {
            debugging('Cache invalidation failed: ' . $e->getMessage(), DEBUG_DEVELOPER);
        }
    }

    /**
     * Purge all cached progress data for a course.
     *
     * Useful when course structure changes or completion settings are updated.
     *
     * @param int $courseid Course identifier.
     * @return void
     */
    public static function purge_course_cache(int $courseid): void {
        try {
            $cache = cache::make('theme_compecer', self::CACHE_AREA);
            $cache->purge();
        } catch (\Exception $e) {
            debugging('Cache purge failed: ' . $e->getMessage(), DEBUG_DEVELOPER);
        }
    }

    /**
     * Generate cache key for a course and user.
     *
     * @param int $courseid Course identifier.
     * @param int $userid User identifier.
     * @return string Cache key.
     */
    private static function get_cache_key(int $courseid, int $userid): string {
        return "progress_{$courseid}_{$userid}";
    }
}
