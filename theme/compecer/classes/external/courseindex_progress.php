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

namespace theme_compecer\external;

use context_course;
use external_api;
use external_function_parameters;
use external_multiple_structure;
use external_single_structure;
use external_value;
use theme_compecer\local\courseprogress\provider;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/externallib.php');

/**
 * External function to expose course index progress information for AJAX clients.
 *
 * @package     theme_compecer
 * @copyright   2024 IngeWeb
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class courseindex_progress extends external_api {
    /**
     * Describe the parameters for the execute method.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'courseid' => new external_value(PARAM_INT, 'Course identifier'),
        ]);
    }

    /**
     * Retrieve course index progress for the current user.
     *
     * @param int $courseid Course identifier.
     * @return array<string, mixed>
     */
    public static function execute(int $courseid): array {
        global $USER;

        $params = self::validate_parameters(self::execute_parameters(), ['courseid' => $courseid]);
        $course = get_course($params['courseid']);

        $context = context_course::instance($course->id);
        self::validate_context($context);
        require_login($course, false, null, false, true);

        $progress = provider::for_course($course->id, $USER->id);

        return $progress;
    }

    /**
     * Describe the structure of the execute return values.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'completionenabled' => new external_value(PARAM_BOOL, 'Whether completion is enabled for this user'),
            'course' => new external_single_structure([
                'percent' => new external_value(PARAM_INT, 'Completion percentage'),
                'completed' => new external_value(PARAM_INT, 'Completed activities count'),
                'failed' => new external_value(PARAM_INT, 'Failed activities count'),
                'inprogress' => new external_value(PARAM_INT, 'Activities in progress'),
                'notstarted' => new external_value(PARAM_INT, 'Activities not started'),
                'total' => new external_value(PARAM_INT, 'Total trackable activities'),
                'summary' => new external_value(PARAM_RAW, 'Summary label'),
                'a11y' => new external_value(PARAM_RAW, 'Accessibility description'),
            ]),
            'sections' => new external_multiple_structure(new external_single_structure([
                'id' => new external_value(PARAM_INT, 'Section id'),
                'percent' => new external_value(PARAM_INT, 'Section completion percentage'),
                'completed' => new external_value(PARAM_INT, 'Completed activities in section'),
                'inprogress' => new external_value(PARAM_INT, 'In progress activities in section'),
                'notstarted' => new external_value(PARAM_INT, 'Not started activities in section'),
                'failed' => new external_value(PARAM_INT, 'Failed activities in section'),
                'total' => new external_value(PARAM_INT, 'Total trackable activities in section'),
                'summary' => new external_value(PARAM_RAW, 'Summary label'),
                'a11y' => new external_value(PARAM_RAW, 'Accessibility description'),
            ])),
            'cms' => new external_multiple_structure(new external_single_structure([
                'id' => new external_value(PARAM_INT, 'Course module id'),
                'status' => new external_value(PARAM_ALPHANUMEXT, 'Status identifier'),
                'label' => new external_value(PARAM_RAW, 'Human readable label'),
            ])),
        ]);
    }
}
