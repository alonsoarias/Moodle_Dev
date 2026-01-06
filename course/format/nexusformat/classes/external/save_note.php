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
 * External function for saving a user note.
 *
 * @package    format_nexusformat
 * @copyright  2024 Nexus Learning
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class save_note extends external_api {

    /**
     * Returns description of method parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'courseid' => new external_value(PARAM_INT, 'Course ID'),
            'content' => new external_value(PARAM_RAW, 'Note content'),
            'title' => new external_value(PARAM_TEXT, 'Note title', VALUE_DEFAULT, ''),
            'noteid' => new external_value(PARAM_INT, 'Note ID (0 for new note)', VALUE_DEFAULT, 0),
            'cmid' => new external_value(PARAM_INT, 'Course module ID (0 for general note)', VALUE_DEFAULT, 0),
        ]);
    }

    /**
     * Save a user note.
     *
     * @param int $courseid Course ID
     * @param string $content Note content
     * @param string $title Note title
     * @param int $noteid Note ID (0 for new)
     * @param int $cmid Course module ID
     * @return array Result
     */
    public static function execute(int $courseid, string $content, string $title = '', int $noteid = 0, int $cmid = 0): array {
        global $DB, $USER;

        // Validate parameters.
        $params = self::validate_parameters(self::execute_parameters(), [
            'courseid' => $courseid,
            'content' => $content,
            'title' => $title,
            'noteid' => $noteid,
            'cmid' => $cmid,
        ]);

        $courseid = $params['courseid'];
        $content = $params['content'];
        $title = $params['title'];
        $noteid = $params['noteid'];
        $cmid = $params['cmid'];

        // Check context and capabilities.
        $context = context_course::instance($courseid);
        self::validate_context($context);

        $now = time();

        if ($noteid > 0) {
            // Update existing note.
            $note = $DB->get_record('format_nexusformat_notes', [
                'id' => $noteid,
                'userid' => $USER->id,
                'courseid' => $courseid,
            ]);

            if (!$note) {
                throw new \moodle_exception('notenotfound', 'format_nexusformat');
            }

            $note->title = $title;
            $note->content = $content;
            $note->timemodified = $now;

            $DB->update_record('format_nexusformat_notes', $note);
        } else {
            // Create new note.
            $note = new \stdClass();
            $note->userid = $USER->id;
            $note->courseid = $courseid;
            $note->cmid = $cmid > 0 ? $cmid : null;
            $note->title = $title;
            $note->content = $content;
            $note->contentformat = FORMAT_HTML;
            $note->timecreated = $now;
            $note->timemodified = $now;

            $note->id = $DB->insert_record('format_nexusformat_notes', $note);
        }

        return [
            'success' => true,
            'noteid' => (int)$note->id,
            'message' => get_string('note_saved', 'format_nexusformat'),
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
            'noteid' => new external_value(PARAM_INT, 'The note ID'),
            'message' => new external_value(PARAM_TEXT, 'Status message'),
        ]);
    }
}
