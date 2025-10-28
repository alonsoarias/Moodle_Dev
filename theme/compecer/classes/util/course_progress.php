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
 * Course progress utility class
 *
 * @package    theme_compecer
 * @copyright  2024 IngeWeb https://www.ingeweb.co
 * @author     Alonso Arias <soporte@ingeweb.co>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace theme_compecer\util;

defined('MOODLE_INTERNAL') || die();

use completion_info;
use core_completion\progress;

/**
 * Course progress utility class
 */
class course_progress {

    /**
     * Get course overall progress percentage
     *
     * @param object $course Course object
     * @param int $userid User ID (default: current user)
     * @return array Array with 'percentage' and 'hasprogress' keys
     */
    public static function get_course_progress($course, $userid = null) {
        global $USER;

        if ($userid === null) {
            $userid = $USER->id;
        }

        $completion = new completion_info($course);

        // Check if completion is enabled at course level
        if (!$completion->is_enabled()) {
            return [
                'hasprogress' => false,
                'percentage' => 0
            ];
        }

        // Count activities with completion tracking
        $modinfo = get_fast_modinfo($course);
        $totalactivities = 0;
        $completedactivities = 0;

        foreach ($modinfo->get_cms() as $cm) {
            // Skip labels and non-visible activities
            if ($cm->modname === 'label' || !$cm->uservisible) {
                continue;
            }

            // Check if this activity has completion tracking enabled
            if ($completion->is_enabled($cm) != COMPLETION_TRACKING_NONE) {
                $totalactivities++;

                // Get completion data for this activity
                $completiondata = $completion->get_data($cm, true, $userid);

                if ($completiondata->completionstate == COMPLETION_COMPLETE ||
                    $completiondata->completionstate == COMPLETION_COMPLETE_PASS) {
                    $completedactivities++;
                }
            }
        }

        // If there are no activities with completion tracking, return no progress
        if ($totalactivities === 0) {
            return [
                'hasprogress' => false,
                'percentage' => 0
            ];
        }

        // Calculate percentage
        $percentage = floor(($completedactivities / $totalactivities) * 100);

        return [
            'hasprogress' => true,
            'percentage' => $percentage
        ];
    }

    /**
     * Get section progress information
     *
     * @param object $course Course object
     * @param object $section Section info object
     * @param int $userid User ID (default: current user)
     * @return array Array with progress information
     */
    public static function get_section_progress($course, $section, $userid = null) {
        global $USER;

        if ($userid === null) {
            $userid = $USER->id;
        }

        // Check if user is logged in and not a guest
        $cancomplete = isloggedin() && !isguestuser();
        if (!$cancomplete) {
            return [
                'hasprogress' => false,
                'percentage' => 0,
                'complete' => 0,
                'total' => 0
            ];
        }

        $completion = new completion_info($course);

        // Check if completion is enabled
        if (!$completion->is_enabled()) {
            return [
                'hasprogress' => false,
                'percentage' => 0,
                'complete' => 0,
                'total' => 0
            ];
        }

        // Get course modinfo
        $modinfo = get_fast_modinfo($course);

        // Check if section exists and has activities
        if (empty($modinfo->sections[$section->section])) {
            return [
                'hasprogress' => false,
                'percentage' => 0,
                'complete' => 0,
                'total' => 0
            ];
        }

        $total = 0;
        $complete = 0;

        // Count completed activities in this section
        foreach ($modinfo->sections[$section->section] as $cmid) {
            $cm = $modinfo->cms[$cmid];

            // Skip labels and non-visible activities
            if ($cm->modname === 'label' || !$cm->uservisible) {
                continue;
            }

            // Check if completion tracking is enabled for this activity
            if ($completion->is_enabled($cm) != COMPLETION_TRACKING_NONE) {
                $total++;
                $completiondata = $completion->get_data($cm, true, $userid);

                if ($completiondata->completionstate == COMPLETION_COMPLETE ||
                    $completiondata->completionstate == COMPLETION_COMPLETE_PASS) {
                    $complete++;
                }
            }
        }

        // Calculate percentage
        $percentage = 0;
        if ($total > 0) {
            $percentage = round(($complete / $total) * 100, 0);
        }

        return [
            'hasprogress' => ($total > 0),
            'percentage' => $percentage,
            'complete' => $complete,
            'total' => $total
        ];
    }

    /**
     * Get section progress by section ID
     *
     * @param int $courseid Course ID
     * @param int $sectionid Section ID
     * @param int $userid User ID (default: current user)
     * @return array Array with progress information
     */
    public static function get_section_progress_by_id($courseid, $sectionid, $userid = null) {
        global $DB;

        $course = $DB->get_record('course', ['id' => $courseid], '*', MUST_EXIST);
        $section = $DB->get_record('course_sections', ['id' => $sectionid], '*', MUST_EXIST);

        return self::get_section_progress($course, $section, $userid);
    }
}
