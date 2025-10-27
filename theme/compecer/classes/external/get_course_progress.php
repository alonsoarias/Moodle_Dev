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
 * Web service to get course overall progress
 *
 * @package    theme_compecer
 * @copyright  2024 IngeWeb https://www.ingeweb.co
 * @author     Alonso Arias <soporte@ingeweb.co>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace theme_compecer\external;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/externallib.php');

use external_api;
use external_function_parameters;
use external_value;
use external_single_structure;
use context_course;
use theme_compecer\util\course_progress;

/**
 * External function to get course overall progress
 */
class get_course_progress extends external_api {

    /**
     * Returns description of method parameters
     *
     * @return external_function_parameters
     */
    public static function execute_parameters() {
        return new external_function_parameters([
            'courseid' => new external_value(PARAM_INT, 'Course ID', VALUE_REQUIRED),
        ]);
    }

    /**
     * Get course overall progress
     *
     * @param int $courseid Course ID
     * @return array Progress data
     */
    public static function execute($courseid) {
        global $USER, $DB;

        // Validate parameters
        $params = self::validate_parameters(self::execute_parameters(), [
            'courseid' => $courseid,
        ]);

        // Get course record
        $course = $DB->get_record('course', ['id' => $params['courseid']], '*', MUST_EXIST);

        // Validate context
        $context = context_course::instance($params['courseid']);
        self::validate_context($context);

        // Check if user can access the course (enrolled or has viewhiddencourses capability)
        if (!is_enrolled($context, $USER, '', true) && !has_capability('moodle/course:viewhiddencourses', $context)) {
            // Return empty progress for users without access
            return [
                'hasprogress' => false,
                'percentage' => 0,
                'completed' => 0,
                'total' => 0
            ];
        }

        // Get progress data
        $progress = course_progress::get_course_progress($course, $USER->id);

        // Get detailed activity counts
        $completion = new \completion_info($course);
        $completed = 0;
        $total = 0;

        if ($completion->is_enabled()) {
            $modinfo = get_fast_modinfo($course);
            foreach ($modinfo->get_cms() as $cm) {
                if ($cm->modname === 'label' || !$cm->uservisible) {
                    continue;
                }
                if ($completion->is_enabled($cm) != COMPLETION_TRACKING_NONE) {
                    $total++;
                    $completiondata = $completion->get_data($cm, true, $USER->id);
                    if ($completiondata->completionstate == COMPLETION_COMPLETE ||
                        $completiondata->completionstate == COMPLETION_COMPLETE_PASS) {
                        $completed++;
                    }
                }
            }
        }

        return [
            'hasprogress' => $progress['hasprogress'],
            'percentage' => $progress['percentage'],
            'completed' => $completed,
            'total' => $total
        ];
    }

    /**
     * Returns description of method result value
     *
     * @return external_single_structure
     */
    public static function execute_returns() {
        return new external_single_structure([
            'hasprogress' => new external_value(PARAM_BOOL, 'Whether the course has progress tracking'),
            'percentage' => new external_value(PARAM_INT, 'Completion percentage'),
            'completed' => new external_value(PARAM_INT, 'Number of completed activities'),
            'total' => new external_value(PARAM_INT, 'Total number of activities with completion'),
        ]);
    }
}
