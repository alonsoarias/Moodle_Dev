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
 * External function to get course progress data.
 *
 * @package    theme_compecer
 * @copyright  2024 IngeWeb https://www.ingeweb.co
 * @author     Pedro Arias <soporte@ingeweb.co>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace theme_compecer\external;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/externallib.php');

use external_api;
use external_function_parameters;
use external_single_structure;
use external_multiple_structure;
use external_value;
use core_completion\progress;
use context_course;

/**
 * External function to get course progress data.
 */
class get_course_progress extends external_api {

    /**
     * Returns description of method parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters() {
        return new external_function_parameters([
            'courseid' => new external_value(PARAM_INT, 'Course ID')
        ]);
    }

    /**
     * Get course progress data.
     *
     * @param int $courseid Course ID
     * @return array Course progress data
     */
    public static function execute($courseid) {
        global $USER;

        // Validate parameters.
        $params = self::validate_parameters(self::execute_parameters(), [
            'courseid' => $courseid
        ]);

        // Validate context.
        $context = context_course::instance($params['courseid']);
        self::validate_context($context);

        // Check if user is enrolled in the course.
        require_capability('moodle/course:view', $context);

        // Get course object.
        $course = get_course($params['courseid']);

        // Initialize output.
        $output = [
            'courseid' => $course->id,
            'percentage' => 0,
            'hascompletion' => false,
            'activitycount' => 0,
            'completedcount' => 0,
            'activitylist' => []
        ];

        // Check if completion is enabled for the course.
        $completion = new \completion_info($course);
        if (!$completion->is_enabled()) {
            return $output;
        }

        $output['hascompletion'] = true;

        // Get course progress percentage using core API.
        $percentage = progress::get_course_progress_percentage($course, $USER->id);
        if (!is_null($percentage)) {
            $output['percentage'] = floor($percentage);
        }

        // Get activity statistics.
        $modinfo = get_fast_modinfo($course);
        $sections = $modinfo->get_section_info_all();

        $activitytypes = [];
        $totalactivities = 0;
        $completedactivities = 0;

        // Count activities by type and completion status.
        foreach ($sections as $section) {
            if (isset($modinfo->sections[$section->section])) {
                foreach ($modinfo->sections[$section->section] as $cmid) {
                    $cm = $modinfo->cms[$cmid];

                    // Skip if not visible on course page.
                    if (!$cm->is_visible_on_course_page()) {
                        continue;
                    }

                    $totalactivities++;

                    // Count by module type.
                    if (!isset($activitytypes[$cm->modname])) {
                        $activitytypes[$cm->modname] = [
                            'name' => $cm->modfullname,
                            'count' => 0
                        ];
                    }
                    $activitytypes[$cm->modname]['count']++;

                    // Check completion status.
                    $completiondata = $completion->get_data($cm, false, $USER->id);
                    if ($completiondata->completionstate == COMPLETION_COMPLETE ||
                        $completiondata->completionstate == COMPLETION_COMPLETE_PASS) {
                        $completedactivities++;
                    }
                }
            }
        }

        // Build activity list.
        foreach ($activitytypes as $type) {
            $output['activitylist'][] = $type['count'] . ' ' . $type['name'];
        }

        $output['activitycount'] = $totalactivities;
        $output['completedcount'] = $completedactivities;

        return $output;
    }

    /**
     * Returns description of method result value.
     *
     * @return external_single_structure
     */
    public static function execute_returns() {
        return new external_single_structure([
            'courseid' => new external_value(PARAM_INT, 'Course ID'),
            'percentage' => new external_value(PARAM_FLOAT, 'Course completion percentage'),
            'hascompletion' => new external_value(PARAM_BOOL, 'Whether completion is enabled'),
            'activitycount' => new external_value(PARAM_INT, 'Total activity count'),
            'completedcount' => new external_value(PARAM_INT, 'Completed activity count'),
            'activitylist' => new external_multiple_structure(
                new external_value(PARAM_TEXT, 'Activity count details'),
                'Activity count list'
            )
        ]);
    }
}
