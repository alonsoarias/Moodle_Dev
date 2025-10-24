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
 * AJAX endpoint that exposes course progress details.
 *
 * @package   theme_compecer
 * @copyright 2024 IngeWeb
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace theme_compecer\external;

defined('MOODLE_INTERNAL') || die();

require_once($GLOBALS['CFG']->libdir . '/externallib.php');

use context_course;
use external_api;
use external_function_parameters;
use external_single_structure;
use external_value;
use theme_compecer\course_progress_service;

/**
 * External function that returns the progress summary for a course.
 */
class course_progress extends external_api {
    /**
     * Describe parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'courseid' => new external_value(PARAM_INT, 'Course identifier'),
        ]);
    }

    /**
     * Return course progress summary for the current user.
     *
     * @param int $courseid Course identifier.
     * @return array<string,int|bool>
     */
    public static function execute(int $courseid): array {
        global $USER;

        $params = self::validate_parameters(self::execute_parameters(), ['courseid' => $courseid]);
        $course = get_course($params['courseid']);

        require_login($course);

        $context = context_course::instance($course->id);
        self::validate_context($context);

        $summary = course_progress_service::get_course_progress_summary($course, (int)$USER->id);

        return [
            'hascompletion' => $summary['hascompletion'],
            'percentage' => $summary['percentage'],
            'total' => $summary['total'],
            'completed' => $summary['completed'],
            'incomplete' => $summary['incomplete'],
            'timeupdated' => time(),
        ];
    }

    /**
     * Describe return fields.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'hascompletion' => new external_value(PARAM_BOOL, 'Whether completion is enabled for the course'),
            'percentage' => new external_value(PARAM_INT, 'Rounded completion percentage'),
            'total' => new external_value(PARAM_INT, 'Total number of activities that count towards completion'),
            'completed' => new external_value(PARAM_INT, 'Activities completed by the current user'),
            'incomplete' => new external_value(PARAM_INT, 'Activities not completed yet'),
            'timeupdated' => new external_value(PARAM_INT, 'Server timestamp of the calculation'),
        ]);
    }
}
