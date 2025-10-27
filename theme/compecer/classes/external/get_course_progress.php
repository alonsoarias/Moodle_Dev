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
 * External service for getting course progress with section details.
 *
 * @package    theme_compecer
 * @copyright  2024 IngeWeb https://www.ingeweb.co
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace theme_compecer\external;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/externallib.php');

use external_api;
use external_function_parameters;
use external_value;
use external_single_structure;
use external_multiple_structure;
use context_course;
use theme_compecer\local\courseindex\progress_helper;

/**
 * External service for getting course progress.
 *
 * @package    theme_compecer
 * @copyright  2024 IngeWeb
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class get_course_progress extends external_api {

    /**
     * Returns description of method parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters() {
        return new external_function_parameters([
            'courseid' => new external_value(PARAM_INT, 'Course ID'),
        ]);
    }

    /**
     * Get course progress with section details.
     *
     * @param int $courseid The course ID
     * @return array Course progress data
     */
    public static function execute($courseid) {
        global $USER, $DB;

        // Validate parameters.
        $params = self::validate_parameters(self::execute_parameters(), [
            'courseid' => $courseid,
        ]);

        $courseid = $params['courseid'];

        // Validate course exists.
        $course = $DB->get_record('course', ['id' => $courseid], '*', MUST_EXIST);

        // Check capability.
        $context = context_course::instance($courseid);
        self::validate_context($context);
        require_capability('moodle/course:view', $context);

        // Initialize completion.
        $payload = progress_helper::build_course_payload($course, $USER->id);
        return $payload;
    }

    /**
     * Returns description of method result value.
     *
     * @return external_single_structure
     */
    public static function execute_returns() {
        return new external_single_structure([
            'courseid' => new external_value(PARAM_INT, 'Course ID'),
            'percentage' => new external_value(PARAM_FLOAT, 'Overall completion percentage'),
            'hascompletion' => new external_value(PARAM_BOOL, 'Whether completion is enabled'),
            'completedcount' => new external_value(PARAM_INT, 'Number of completed activities'),
            'activitycount' => new external_value(PARAM_INT, 'Total number of activities'),
            'activitysummary' => new external_value(PARAM_TEXT, 'Summary of completed vs total activities', VALUE_OPTIONAL),
            'activitylist' => new external_multiple_structure(
                new external_value(PARAM_TEXT, 'Activity type and count'),
                'List of activity types',
                VALUE_OPTIONAL
            ),
            'progresscolor' => new external_value(PARAM_TEXT, 'Progress bar color class'),
            'statelabels' => new external_single_structure([
                'notstarted' => new external_value(PARAM_TEXT, 'Label for not started'),
                'inprogress' => new external_value(PARAM_TEXT, 'Label for in progress'),
                'completed' => new external_value(PARAM_TEXT, 'Label for completed'),
                'notracking' => new external_value(PARAM_TEXT, 'Label for activities without tracking'),
            ], 'Localized labels for activity states'),
            'sections' => new external_multiple_structure(
                new external_single_structure([
                    'id' => new external_value(PARAM_INT, 'Section ID'),
                    'number' => new external_value(PARAM_INT, 'Section number'),
                    'name' => new external_value(PARAM_TEXT, 'Section name'),
                    'visible' => new external_value(PARAM_BOOL, 'Section visibility'),
                    'sectionurl' => new external_value(PARAM_URL, 'Section URL'),
                    'progressinfo' => new external_single_structure([
                        'percentage' => new external_value(PARAM_FLOAT, 'Section completion percentage'),
                        'completed' => new external_value(PARAM_INT, 'Completed activities in section'),
                        'total' => new external_value(PARAM_INT, 'Total activities in section'),
                        'summary' => new external_value(PARAM_TEXT, 'Summary string (e.g., "3 of 5 activities"), optional', VALUE_OPTIONAL),
                        'progresscolor' => new external_value(PARAM_TEXT, 'Progress color class'),
                    ], 'Section progress information'),
                    'activities' => new external_multiple_structure(
                        new external_single_structure([
                            'id' => new external_value(PARAM_INT, 'Activity ID'),
                            'name' => new external_value(PARAM_TEXT, 'Activity name'),
                            'modname' => new external_value(PARAM_TEXT, 'Module name'),
                            'url' => new external_value(PARAM_URL, 'Activity URL'),
                            'state' => new external_value(PARAM_TEXT, 'Activity state'),
                        ]),
                        'Activities in section',
                        VALUE_OPTIONAL
                    ),
                ]),
                'Sections data',
                VALUE_OPTIONAL
            ),
        ]);
    }
}
