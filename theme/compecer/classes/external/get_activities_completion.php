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
 * External API for getting activities completion states
 *
 * @package    theme_compecer
 * @copyright  2024 IngeWeb https://www.ingeweb.co
 * @author     Alonso Arias <soporte@ingeweb.co>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace theme_compecer\external;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/externallib.php');
require_once($CFG->libdir . '/completionlib.php');

use external_api;
use external_function_parameters;
use external_value;
use external_multiple_structure;
use external_single_structure;
use context_course;
use completion_info;

/**
 * External API for getting activities completion states
 *
 * @package    theme_compecer
 * @copyright  2024 IngeWeb
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class get_activities_completion extends external_api {

    /**
     * Returns description of method parameters
     *
     * @return external_function_parameters
     */
    public static function execute_parameters() {
        return new external_function_parameters([
            'courseid' => new external_value(PARAM_INT, 'Course ID', VALUE_REQUIRED)
        ]);
    }

    /**
     * Get completion states for all activities in a course
     *
     * @param int $courseid The course ID
     * @return array Array of activity completion states
     */
    public static function execute($courseid) {
        global $USER, $DB;

        // Validate parameters.
        $params = self::validate_parameters(self::execute_parameters(), [
            'courseid' => $courseid
        ]);

        // Validate context.
        $context = context_course::instance($params['courseid']);
        self::validate_context($context);

        // Check if user is enrolled or has capability to view.
        if (!is_enrolled($context, $USER, '', true) &&
            !has_capability('moodle/course:viewhiddencourses', $context)) {
            return ['activities' => []];
        }

        // Get course.
        $course = $DB->get_record('course', ['id' => $params['courseid']], '*', MUST_EXIST);

        // Check if completion is enabled for this course.
        $completion = new completion_info($course);
        if (!$completion->is_enabled()) {
            return ['activities' => []];
        }

        // Get all course modules.
        $modinfo = get_fast_modinfo($course);
        $activities = [];

        foreach ($modinfo->get_cms() as $cm) {
            // Skip if not visible to user or completion not tracked.
            if (!$cm->uservisible || !$completion->is_enabled($cm)) {
                continue;
            }

            // Get completion data.
            $completiondata = $completion->get_data($cm, true, $USER->id);

            // Determine completion state.
            // 0 = not started, 1 = in progress, 2 = completed
            $state = 0; // not started

            if ($completiondata->completionstate == COMPLETION_COMPLETE ||
                $completiondata->completionstate == COMPLETION_COMPLETE_PASS) {
                $state = 2; // completed
            } else if ($completiondata->viewed == COMPLETION_VIEWED ||
                      $completiondata->completionstate == COMPLETION_INCOMPLETE) {
                $state = 1; // in progress
            }

            $activities[] = [
                'cmid' => $cm->id,
                'state' => $state
            ];
        }

        return ['activities' => $activities];
    }

    /**
     * Returns description of method result value
     *
     * @return external_single_structure
     */
    public static function execute_returns() {
        return new external_single_structure([
            'activities' => new external_multiple_structure(
                new external_single_structure([
                    'cmid' => new external_value(PARAM_INT, 'Course module ID'),
                    'state' => new external_value(PARAM_INT, 'Completion state (0=not started, 1=in progress, 2=completed)')
                ])
            )
        ]);
    }
}
