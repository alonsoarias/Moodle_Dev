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
 * Course Index Helper - Calculation of course and section progress
 *
 * @package    theme_compecer
 * @copyright  2025 IngeWeb https://www.ingeweb.co
 * @author     Pedro Arias <soporte@ingeweb.co>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace theme_compecer;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/completionlib.php');

/**
 * Helper class for CourseIndex progress calculations
 *
 * This class provides methods to calculate real completion percentages
 * for both global course progress and individual section progress.
 *
 * @package    theme_compecer
 * @copyright  2025 IngeWeb
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class courseindex_helper {

    /**
     * Get global course progress percentage for current user
     *
     * Uses Moodle's Completion API to calculate the real completion percentage
     * of all completable activities in the course for the current user.
     *
     * @param object $course The course object
     * @param int|null $userid User ID (null = current user)
     * @return array Array with 'percentage' (int 0-100) and 'enabled' (bool)
     */
    public static function get_course_progress($course, $userid = null) {
        global $USER;

        if ($userid === null) {
            $userid = $USER->id;
        }

        $result = [
            'percentage' => 0,
            'completed' => 0,
            'total' => 0,
            'enabled' => false,
        ];

        // Check if user can participate in completion tracking.
        $cancomplete = isloggedin() && !isguestuser();
        if (!$cancomplete) {
            return $result;
        }

        $completioninfo = new \completion_info($course);
        if (!$completioninfo->is_enabled()) {
            return $result;
        }

        $result['enabled'] = true;

        $modinfo = get_fast_modinfo($course);

        foreach ($modinfo->get_cms() as $cm) {
            // Skip labels (not completable) modules.
            if ($cm->modname === 'label') {
                continue;
            }

            if (!$cm->uservisible) {
                continue;
            }

            if ($completioninfo->is_enabled($cm) == COMPLETION_TRACKING_NONE) {
                continue;
            }

            $result['total']++;

            $completiondata = $completioninfo->get_data($cm, true, $userid);
            if ($completiondata->completionstate == COMPLETION_COMPLETE ||
                $completiondata->completionstate == COMPLETION_COMPLETE_PASS) {
                $result['completed']++;
            }
        }

        if ($result['total'] > 0) {
            // Use the official API to keep consistency with course overview.
            $coursepercentage = new \core_completion\progress();
            $percentvalue = $coursepercentage->get_course_progress_percentage($course, $userid);

            if ($percentvalue !== null) {
                $result['percentage'] = (int) round($percentvalue);
            } else {
                $result['percentage'] = (int) round(($result['completed'] / $result['total']) * 100);
            }
        }

        return $result;
    }

    /**
     * Get section progress information
     *
     * Calculates the completion percentage for all completable activities
     * within a specific section.
     *
     * @param object $section The section object
     * @param object $course The course object
     * @param int|null $userid User ID (null = current user)
     * @return array Array with 'percentage', 'total', 'completed', 'enabled'
     */
    public static function get_section_progress($section, $course, $userid = null) {
        global $USER;

        if ($userid === null) {
            $userid = $USER->id;
        }

        $result = [
            'percentage' => 0,
            'total' => 0,
            'completed' => 0,
            'enabled' => false,
        ];

        // Check if user can complete activities.
        $cancomplete = isloggedin() && !isguestuser();
        if (!$cancomplete) {
            return $result;
        }

        // Get course module information.
        $modinfo = get_fast_modinfo($course);

        // Check if section has modules.
        if (empty($modinfo->sections[$section->section])) {
            return $result;
        }

        // Get completion information.
        $completioninfo = new \completion_info($course);
        if (!$completioninfo->is_enabled()) {
            return $result;
        }

        $result['enabled'] = true;

        $total = 0;
        $completed = 0;

        // Iterate through all course modules in the section.
        foreach ($modinfo->sections[$section->section] as $cmid) {
            $cm = $modinfo->cms[$cmid];

            // Skip labels (not completable activities).
            if ($cm->modname === 'label') {
                continue;
            }

            // Check if module is visible to user.
            if (!$cm->uservisible) {
                continue;
            }

            // Check if completion tracking is enabled for this module.
            if ($completioninfo->is_enabled($cm) == COMPLETION_TRACKING_NONE) {
                continue;
            }

            $total++;

            // Get completion data for this module.
            $completiondata = $completioninfo->get_data($cm, true, $userid);

            // Check if module is completed.
            if ($completiondata->completionstate == COMPLETION_COMPLETE ||
                $completiondata->completionstate == COMPLETION_COMPLETE_PASS) {
                $completed++;
            }
        }

        $result['total'] = $total;
        $result['completed'] = $completed;

        // Calculate percentage.
        if ($total > 0) {
            $result['percentage'] = round(($completed / $total) * 100, 0);
        }

        return $result;
    }

    /**
     * Get activity completion state (for traffic light indicator)
     *
     * Returns a state that can be used to display a traffic light indicator:
     * - 'completed': Activity is completed (green)
     * - 'inprogress': Activity is partially completed (yellow)
     * - 'pending': Activity is not started (red)
     * - 'notavailable': Activity is not available (gray)
     *
     * @param object $cm Course module object
     * @param object $course The course object
     * @param int|null $userid User ID (null = current user)
     * @return string State: 'completed', 'inprogress', 'pending', 'notavailable'
     */
    public static function get_activity_state($cm, $course, $userid = null) {
        global $USER;

        if ($userid === null) {
            $userid = $USER->id;
        }

        // Check if module is available to user.
        if (!$cm->uservisible || !$cm->available) {
            return 'notavailable';
        }

        // Check if user can complete activities.
        if (!isloggedin() || isguestuser()) {
            return 'notavailable';
        }

        // Get completion information.
        $completioninfo = new \completion_info($course);
        if (!$completioninfo->is_enabled()) {
            return 'notavailable';
        }

        // Check if completion tracking is enabled for this module.
        if ($completioninfo->is_enabled($cm) == COMPLETION_TRACKING_NONE) {
            return 'notavailable';
        }

        // Get completion data.
        $completiondata = $completioninfo->get_data($cm, true, $userid);

        // Determine state based on completion state.
        switch ($completiondata->completionstate) {
            case COMPLETION_COMPLETE:
            case COMPLETION_COMPLETE_PASS:
                return 'completed';

            case COMPLETION_COMPLETE_FAIL:
                // Failed but attempted - consider as in progress.
                return 'inprogress';

            case COMPLETION_INCOMPLETE:
            default:
                // Check if activity has been viewed/attempted.
                if (!empty($completiondata->viewed) || !empty($completiondata->timemodified)) {
                    return 'inprogress';
                }
                return 'pending';
        }
    }

    /**
     * Get formatted progress text for display
     *
     * Returns user-friendly text based on progress percentage.
     *
     * @param int $percentage Progress percentage (0-100)
     * @param int $completed Number of completed activities
     * @param int $total Total number of activities
     * @return string Formatted text
     */
    public static function get_progress_text($percentage, $completed, $total) {
        if ($total === 0) {
            return '';
        }

        if ($percentage === 100) {
            return get_string('allactivitiescompleted', 'theme_compecer');
        } else if ($percentage === 0) {
            return get_string('noactivitiescompleted', 'theme_compecer');
        } else {
            return get_string('activitiescompletedcount', 'theme_compecer', [
                'completed' => $completed,
                'total' => $total,
            ]);
        }
    }

    /**
     * Determine if a section should be marked as current
     *
     * @param object $section The section object
     * @param int|null $currentsection Current section number
     * @return bool True if this is the current section
     */
    public static function is_current_section($section, $currentsection = null) {
        if ($currentsection === null) {
            return false;
        }
        return $section->section == $currentsection;
    }
}
