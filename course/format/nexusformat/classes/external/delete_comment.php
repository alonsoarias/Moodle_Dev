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
use context_module;
use context_course;

/**
 * External function for deleting a comment.
 *
 * @package    format_nexusformat
 * @copyright  2024 Nexus Learning
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class delete_comment extends external_api {

    /**
     * Returns description of method parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'commentid' => new external_value(PARAM_INT, 'Comment ID'),
        ]);
    }

    /**
     * Delete a comment.
     *
     * @param int $commentid Comment ID
     * @return array Result
     */
    public static function execute(int $commentid): array {
        global $DB, $USER;

        // Validate parameters.
        $params = self::validate_parameters(self::execute_parameters(), [
            'commentid' => $commentid,
        ]);

        $commentid = $params['commentid'];

        // Get the comment.
        $comment = $DB->get_record('format_nexusformat_comments', ['id' => $commentid]);
        if (!$comment) {
            throw new \moodle_exception('commentnotfound', 'format_nexusformat');
        }

        // Check context and permissions.
        $context = context_module::instance($comment->cmid);
        self::validate_context($context);

        $coursecontext = context_course::instance($comment->courseid);
        $canmanage = has_capability('moodle/course:manageactivities', $coursecontext);

        // Only owner or manager can delete.
        if ($comment->userid != $USER->id && !$canmanage) {
            throw new \moodle_exception('nopermission', 'format_nexusformat');
        }

        // Delete all likes for this comment.
        $DB->delete_records('format_nexusformat_likes', ['commentid' => $commentid]);

        // Delete all replies to this comment.
        $replies = $DB->get_records('format_nexusformat_comments', ['parentid' => $commentid]);
        foreach ($replies as $reply) {
            $DB->delete_records('format_nexusformat_likes', ['commentid' => $reply->id]);
        }
        $DB->delete_records('format_nexusformat_comments', ['parentid' => $commentid]);

        // Delete the comment itself.
        $DB->delete_records('format_nexusformat_comments', ['id' => $commentid]);

        return [
            'success' => true,
            'message' => get_string('comment_deleted', 'format_nexusformat'),
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
