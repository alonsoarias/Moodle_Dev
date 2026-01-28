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
use context_course;

/**
 * External function for deleting a user note.
 *
 * @package    format_nexusformat
 * @copyright  2024 Nexus Learning
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class delete_note extends external_api {

    /**
     * Returns description of method parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'noteid' => new external_value(PARAM_INT, 'Note ID'),
            'courseid' => new external_value(PARAM_INT, 'Course ID'),
        ]);
    }

    /**
     * Delete a user note.
     *
     * @param int $noteid Note ID
     * @param int $courseid Course ID
     * @return array Result
     */
    public static function execute(int $noteid, int $courseid): array {
        global $DB, $USER;

        // Validate parameters.
        $params = self::validate_parameters(self::execute_parameters(), [
            'noteid' => $noteid,
            'courseid' => $courseid,
        ]);

        $noteid = $params['noteid'];
        $courseid = $params['courseid'];

        // Check context and capabilities.
        $context = context_course::instance($courseid);
        self::validate_context($context);

        // Get the note and verify ownership.
        $note = $DB->get_record('format_nexusformat_notes', [
            'id' => $noteid,
            'userid' => $USER->id,
            'courseid' => $courseid,
        ]);

        if (!$note) {
            throw new \moodle_exception('notenotfound', 'format_nexusformat');
        }

        // Delete the note.
        $DB->delete_records('format_nexusformat_notes', ['id' => $noteid]);

        return [
            'success' => true,
            'message' => get_string('note_deleted', 'format_nexusformat'),
        ];
    }

    /**
     * Returns description of method result value.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'success' => new external_value(PARAM_BOOL, 'Whether the operation was successful'),
            'message' => new external_value(PARAM_TEXT, 'Status message'),
        ]);
    }
}
