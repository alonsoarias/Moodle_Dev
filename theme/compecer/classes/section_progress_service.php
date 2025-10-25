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
 * Section progress service for calculating completion progress per section.
 *
 * @package   theme_compecer
 * @copyright 2025 IngeWeb
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace theme_compecer;

defined('MOODLE_INTERNAL') || die();

require_once($GLOBALS['CFG']->libdir . '/completionlib.php');

use cache;
use completion_info;
use stdClass;

/**
 * Service for calculating and caching section-level progress.
 *
 * Provides methods to calculate completion progress for individual course sections,
 * with caching support for improved performance.
 */
class section_progress_service {

    /** Cache definition name for section progress data */
    const CACHE_AREA = 'courseprogress';

    /** Cache TTL in seconds (5 minutes) */
    const CACHE_TTL = 300;

    /**
     * Get progress summary for a specific section.
     *
     * Calculates the completion progress for activities within a single section,
     * including percentage, total activities, completed count, and incomplete count.
     *
     * @param stdClass $course Moodle course record.
     * @param int $sectionid The section identifier (section info ID).
     * @param int $userid The user identifier.
     * @param bool $usecache Whether to use cached data (default: false).
     * @return array{
     *     hascompletion: bool,
     *     percentage: int,
     *     total: int,
     *     completed: int,
     *     incomplete: int,
     *     progresstext: string,
     *     percentageformatted: string,
     *     completedformatted: string,
     *     incompleteformatted: string,
     *     timecalculated: int
     * }
     */
    public static function get_section_progress(
        stdClass $course,
        int $sectionid,
        int $userid,
        bool $usecache = false
    ): array {
        // Try cache first if requested.
        if ($usecache) {
            $cached = self::get_from_cache($course->id, $sectionid, $userid);
            if ($cached !== null) {
                return $cached;
            }
        }

        $completion = new completion_info($course);

        // Return empty progress if completion is not enabled.
        if (!$completion->is_enabled()) {
            return self::empty_progress_result();
        }

        // Get course structure information.
        $modinfo = get_fast_modinfo($course);
        $sections = $modinfo->get_section_info_all();

        // Find the target section.
        $targetsection = null;
        foreach ($sections as $section) {
            if ($section->id == $sectionid) {
                $targetsection = $section;
                break;
            }
        }

        if (!$targetsection || empty($targetsection->sequence)) {
            return self::empty_progress_result();
        }

        // Get all activities with completion tracking.
        $allactivities = $completion->get_activities();

        // Filter activities that belong to this section.
        $sectionactivities = [];
        $cmids = explode(',', $targetsection->sequence);

        foreach ($cmids as $cmid) {
            if (isset($allactivities[$cmid])) {
                $sectionactivities[$cmid] = $allactivities[$cmid];
            }
        }

        $total = count($sectionactivities);
        $completed = 0;

        // Count completed activities in this section.
        foreach ($sectionactivities as $cm) {
            $data = $completion->get_data($cm, true, $userid);
            if ($data->completionstate != COMPLETION_INCOMPLETE) {
                $completed++;
            }
        }

        // Calculate percentage.
        $percentage = $total > 0 ? (int)round(($completed / $total) * 100) : 0;
        $incomplete = max($total - $completed, 0);

        // Build human-readable progress text.
        $progresstext = self::format_progress_text($completed, $total);

        $result = [
            'hascompletion' => true,
            'percentage' => $percentage,
            'total' => $total,
            'completed' => $completed,
            'incomplete' => $incomplete,
            'progresstext' => $progresstext,
            'percentageformatted' => $percentage,
            'completedformatted' => get_string('courseprogresscompletedshort', 'theme_compecer', $completed),
            'incompleteformatted' => get_string('courseprogressremainingshort', 'theme_compecer', $incomplete),
            'timecalculated' => time(),
        ];

        // Store in cache.
        if ($usecache) {
            self::set_in_cache($course->id, $sectionid, $userid, $result);
        }

        return $result;
    }

    /**
     * Get progress for all sections in a course.
     *
     * Returns an associative array with section IDs as keys and progress data as values.
     *
     * @param stdClass $course Moodle course record.
     * @param int $userid The user identifier.
     * @param bool $usecache Whether to use cached data.
     * @return array Associative array of section progress data.
     */
    public static function get_all_sections_progress(
        stdClass $course,
        int $userid,
        bool $usecache = false
    ): array {
        $completion = new completion_info($course);

        if (!$completion->is_enabled()) {
            return [];
        }

        $modinfo = get_fast_modinfo($course);
        $sections = $modinfo->get_section_info_all();
        $result = [];

        foreach ($sections as $section) {
            if ($section->id > 0) { // Skip section 0 (general section) if needed.
                $result[$section->id] = self::get_section_progress(
                    $course,
                    $section->id,
                    $userid,
                    $usecache
                );
            }
        }

        return $result;
    }

    /**
     * Get completion state for a specific course module.
     *
     * Returns a semantic state string for UI display (semaphore system).
     *
     * @param stdClass $course Moodle course record.
     * @param int $cmid Course module ID.
     * @param int $userid The user identifier.
     * @return array{
     *     state: string,
     *     label: string,
     *     color: string,
     *     icon: string
     * }
     */
    public static function get_activity_completion_state(
        stdClass $course,
        int $cmid,
        int $userid
    ): array {
        $completion = new completion_info($course);

        // Default state: unavailable.
        $defaultstate = [
            'state' => 'unavailable',
            'label' => get_string('activitystateunavailable', 'theme_compecer'),
            'color' => 'gray',
            'icon' => 'circle',
        ];

        if (!$completion->is_enabled()) {
            return $defaultstate;
        }

        $activities = $completion->get_activities();

        if (!isset($activities[$cmid])) {
            return $defaultstate;
        }

        $cm = $activities[$cmid];
        $data = $completion->get_data($cm, true, $userid);

        // Determine state based on completion data.
        switch ($data->completionstate) {
            case COMPLETION_COMPLETE:
            case COMPLETION_COMPLETE_PASS:
                return [
                    'state' => 'completed',
                    'label' => get_string('activitystatecompleted', 'theme_compecer'),
                    'color' => 'success', // Green
                    'icon' => 'check-circle',
                ];

            case COMPLETION_COMPLETE_FAIL:
                return [
                    'state' => 'completed-fail',
                    'label' => get_string('activitystatefailed', 'theme_compecer'),
                    'color' => 'danger', // Red
                    'icon' => 'times-circle',
                ];

            case COMPLETION_INCOMPLETE:
                // Check if user has viewed or interacted with the activity.
                if (isset($data->viewed) && $data->viewed == COMPLETION_VIEWED) {
                    return [
                        'state' => 'in-progress',
                        'label' => get_string('activitystateinprogress', 'theme_compecer'),
                        'color' => 'warning', // Yellow/Amber
                        'icon' => 'clock',
                    ];
                } else {
                    return [
                        'state' => 'not-started',
                        'label' => get_string('activitystatenotstarted', 'theme_compecer'),
                        'color' => 'secondary', // Red/Gray
                        'icon' => 'circle',
                    ];
                }

            default:
                return $defaultstate;
        }
    }

    /**
     * Format progress text in a human-readable way.
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
            return get_string('sectioncompleted', 'theme_compecer');
        }

        return get_string(
            'sectionprogresscount',
            'theme_compecer',
            ['completed' => $completed, 'total' => $total]
        );
    }

    /**
     * Return an empty progress result structure.
     *
     * @return array Empty progress data.
     */
    private static function empty_progress_result(): array {
        return [
            'hascompletion' => false,
            'percentage' => 0,
            'total' => 0,
            'completed' => 0,
            'incomplete' => 0,
            'progresstext' => '',
            'percentageformatted' => 0,
            'completedformatted' => get_string('courseprogresscompletedshort', 'theme_compecer', 0),
            'incompleteformatted' => get_string('courseprogressremainingshort', 'theme_compecer', 0),
            'timecalculated' => time(),
        ];
    }

    /**
     * Get section progress data from cache.
     *
     * @param int $courseid Course identifier.
     * @param int $sectionid Section identifier.
     * @param int $userid User identifier.
     * @return array|null Cached progress data or null if not found/expired.
     */
    private static function get_from_cache(int $courseid, int $sectionid, int $userid): ?array {
        try {
            $cache = cache::make('theme_compecer', self::CACHE_AREA);
            $key = self::get_cache_key($courseid, $sectionid, $userid);
            $data = $cache->get($key);

            if ($data !== false) {
                // Check if cache is still valid (within TTL).
                if (isset($data['timecalculated']) &&
                    (time() - $data['timecalculated']) < self::CACHE_TTL) {
                    return $data;
                }
            }
        } catch (\Exception $e) {
            debugging('Section progress cache retrieval failed: ' . $e->getMessage(), DEBUG_DEVELOPER);
        }

        return null;
    }

    /**
     * Store section progress data in cache.
     *
     * @param int $courseid Course identifier.
     * @param int $sectionid Section identifier.
     * @param int $userid User identifier.
     * @param array $data Progress data to cache.
     * @return void
     */
    private static function set_in_cache(int $courseid, int $sectionid, int $userid, array $data): void {
        try {
            $cache = cache::make('theme_compecer', self::CACHE_AREA);
            $key = self::get_cache_key($courseid, $sectionid, $userid);
            $cache->set($key, $data);
        } catch (\Exception $e) {
            debugging('Section progress cache storage failed: ' . $e->getMessage(), DEBUG_DEVELOPER);
        }
    }

    /**
     * Generate cache key for section progress.
     *
     * @param int $courseid Course identifier.
     * @param int $sectionid Section identifier.
     * @param int $userid User identifier.
     * @return string Cache key.
     */
    private static function get_cache_key(int $courseid, int $sectionid, int $userid): string {
        return "section_progress_{$courseid}_{$sectionid}_{$userid}";
    }

    /**
     * Invalidate cached progress for a specific section and user.
     *
     * @param int $courseid Course identifier.
     * @param int $sectionid Section identifier.
     * @param int $userid User identifier.
     * @return void
     */
    public static function invalidate_cache(int $courseid, int $sectionid, int $userid): void {
        try {
            $cache = cache::make('theme_compecer', self::CACHE_AREA);
            $key = self::get_cache_key($courseid, $sectionid, $userid);
            $cache->delete($key);
        } catch (\Exception $e) {
            debugging('Section progress cache invalidation failed: ' . $e->getMessage(), DEBUG_DEVELOPER);
        }
    }
}
