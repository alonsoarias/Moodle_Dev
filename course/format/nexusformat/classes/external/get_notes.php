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

namespace format_nexusformat\external;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/externallib.php');

use external_api;
use external_function_parameters;
use external_value;
use external_single_structure;
use external_multiple_structure;
use context_course;

/**
 * External function for getting user notes.
 *
 * @package    format_nexusformat
 * @copyright  2024 Nexus Learning
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class get_notes extends external_api {

    /**
     * Returns description of method parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'courseid' => new external_value(PARAM_INT, 'Course ID'),
        ]);
    }

    /**
     * Get user notes for a course.
     *
     * @param int $courseid Course ID
     * @return array Notes data
     */
    public static function execute(int $courseid): array {
        global $DB, $USER;

        // Validate parameters.
        $params = self::validate_parameters(self::execute_parameters(), ['courseid' => $courseid]);
        $courseid = $params['courseid'];

        // Check context and capabilities.
        $context = context_course::instance($courseid);
        self::validate_context($context);

        // Get user notes for this course.
        $notes = $DB->get_records('format_nexusformat_notes', [
            'userid' => $USER->id,
            'courseid' => $courseid,
        ], 'timemodified DESC');

        $result = [];
        foreach ($notes as $note) {
            $result[] = [
                'id' => (int)$note->id,
                'title' => $note->title ?? '',
                'content' => $note->content,
                'contentformat' => (int)$note->contentformat,
                'cmid' => $note->cmid ? (int)$note->cmid : 0,
                'timecreated' => (int)$note->timecreated,
                'timemodified' => (int)$note->timemodified,
                'timecreatedformatted' => userdate($note->timecreated),
                'timemodifiedformatted' => userdate($note->timemodified),
            ];
        }

        return [
            'notes' => $result,
            'count' => count($result),
        ];
    }

    /**
     * Returns description of method result value.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'notes' => new external_multiple_structure(
                new external_single_structure([
                    'id' => new external_value(PARAM_INT, 'Note ID'),
                    'title' => new external_value(PARAM_TEXT, 'Note title'),
                    'content' => new external_value(PARAM_RAW, 'Note content'),
                    'contentformat' => new external_value(PARAM_INT, 'Content format'),
                    'cmid' => new external_value(PARAM_INT, 'Course module ID (0 if general)'),
                    'timecreated' => new external_value(PARAM_INT, 'Time created'),
                    'timemodified' => new external_value(PARAM_INT, 'Time modified'),
                    'timecreatedformatted' => new external_value(PARAM_TEXT, 'Formatted creation time'),
                    'timemodifiedformatted' => new external_value(PARAM_TEXT, 'Formatted modification time'),
                ])
            ),
            'count' => new external_value(PARAM_INT, 'Total number of notes'),
        ]);
    }
}
