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
 * External Web Service - Get Course Progress Data
 *
 * @package    theme_compecer
 * @copyright  2025 IngeWeb https://www.ingeweb.co
 * @author     Pedro Arias <soporte@ingeweb.co>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace theme_compecer\external;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/externallib.php');
require_once($CFG->libdir . '/completionlib.php');

use external_api;
use external_function_parameters;
use external_value;
use external_single_structure;
use external_multiple_structure;
use theme_compecer\courseindex_helper;

/**
 * External function to get course progress data for courseindex
 *
 * This webservice returns the global course progress and progress for each section
 * to be displayed in the courseindex via JavaScript.
 */
class get_course_progress extends external_api {

    /**
     * Returns description of method parameters
     *
     * @return external_function_parameters
     */
    public static function execute_parameters() {
        return new external_function_parameters([
            'courseid' => new external_value(PARAM_INT, 'Course ID'),
        ]);
    }

    /**
     * Get course progress data
     *
     * @param int $courseid Course ID
     * @return array Progress data
     */
    public static function execute($courseid) {
        global $DB, $USER;

        // Validate parameters.
        $params = self::validate_parameters(self::execute_parameters(), [
            'courseid' => $courseid,
        ]);

        // Get course.
        $course = $DB->get_record('course', ['id' => $params['courseid']], '*', MUST_EXIST);

        // Check course context and capability.
        $context = \context_course::instance($course->id);
        self::validate_context($context);
        require_capability('moodle/course:view', $context);

        // Get global course progress.
        $globalProgress = courseindex_helper::get_course_progress($course, $USER->id);

        // Get course module information.
        $modinfo = get_fast_modinfo($course);
        $sections = $modinfo->get_section_info_all();

        // Calculate progress for each section.
        $sectionsProgress = [];
        foreach ($sections as $section) {
            $sectionProgress = courseindex_helper::get_section_progress($section, $course, $USER->id);

            $sectionsProgress[] = [
                'sectionnumber' => $section->section,
                'sectionid' => $section->id,
                'percentage' => $sectionProgress['percentage'],
                'total' => $sectionProgress['total'],
                'completed' => $sectionProgress['completed'],
                'enabled' => $sectionProgress['enabled'],
                'summary' => courseindex_helper::get_progress_text(
                    $sectionProgress['percentage'],
                    $sectionProgress['completed'],
                    $sectionProgress['total']
                ),
            ];
        }

        return [
            'courseid' => $course->id,
            'global' => [
                'percentage' => $globalProgress['percentage'],
                'enabled' => $globalProgress['enabled'],
                'total' => $globalProgress['total'],
                'completed' => $globalProgress['completed'],
                'summary' => courseindex_helper::get_progress_text(
                    $globalProgress['percentage'],
                    $globalProgress['completed'],
                    $globalProgress['total']
                ),
            ],
            'sections' => $sectionsProgress,
        ];
    }

    /**
     * Returns description of method result value
     *
     * @return external_single_structure
     */
    public static function execute_returns() {
        return new external_single_structure([
            'courseid' => new external_value(PARAM_INT, 'Course ID'),
            'global' => new external_single_structure([
                'percentage' => new external_value(PARAM_INT, 'Global course progress percentage'),
                'enabled' => new external_value(PARAM_BOOL, 'Whether completion is enabled'),
                'total' => new external_value(PARAM_INT, 'Total completable activities in the course'),
                'completed' => new external_value(PARAM_INT, 'Completed activities in the course'),
                'summary' => new external_value(PARAM_RAW, 'Human readable global progress summary'),
            ]),
            'sections' => new external_multiple_structure(
                new external_single_structure([
                    'sectionnumber' => new external_value(PARAM_INT, 'Section number'),
                    'sectionid' => new external_value(PARAM_INT, 'Section ID'),
                    'percentage' => new external_value(PARAM_INT, 'Section progress percentage'),
                    'total' => new external_value(PARAM_INT, 'Total activities in section'),
                    'completed' => new external_value(PARAM_INT, 'Completed activities in section'),
                    'enabled' => new external_value(PARAM_BOOL, 'Whether section has completable activities'),
                    'summary' => new external_value(PARAM_RAW, 'Human readable section progress summary'),
                ])
            ),
        ]);
    }
}
