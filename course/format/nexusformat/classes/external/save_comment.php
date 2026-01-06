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

/**
 * External function for saving a comment.
 *
 * @package    format_nexusformat
 * @copyright  2024 Nexus Learning
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class save_comment extends external_api {

    /**
     * Returns description of method parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'cmid' => new external_value(PARAM_INT, 'Course module ID'),
            'content' => new external_value(PARAM_RAW, 'Comment content'),
            'parentid' => new external_value(PARAM_INT, 'Parent comment ID (0 for top-level)', VALUE_DEFAULT, 0),
            'commentid' => new external_value(PARAM_INT, 'Comment ID for editing (0 for new)', VALUE_DEFAULT, 0),
        ]);
    }

    /**
     * Save a comment.
     *
     * @param int $cmid Course module ID
     * @param string $content Comment content
     * @param int $parentid Parent comment ID
     * @param int $commentid Comment ID for editing
     * @return array Result
     */
    public static function execute(int $cmid, string $content, int $parentid = 0, int $commentid = 0): array {
        global $DB, $USER;

        // Validate parameters.
        $params = self::validate_parameters(self::execute_parameters(), [
            'cmid' => $cmid,
            'content' => $content,
            'parentid' => $parentid,
            'commentid' => $commentid,
        ]);

        $cmid = $params['cmid'];
        $content = trim($params['content']);
        $parentid = $params['parentid'];
        $commentid = $params['commentid'];

        // Validate content is not empty.
        if (empty($content)) {
            throw new \moodle_exception('emptycomment', 'format_nexusformat');
        }

        // Get course module and context.
        $cm = get_coursemodule_from_id('', $cmid, 0, false, MUST_EXIST);
        $context = context_module::instance($cmid);
        self::validate_context($context);

        $courseid = $cm->course;
        $now = time();

        if ($commentid > 0) {
            // Edit existing comment.
            $comment = $DB->get_record('format_nexusformat_comments', [
                'id' => $commentid,
                'userid' => $USER->id,
            ]);

            if (!$comment) {
                throw new \moodle_exception('commentnotfound', 'format_nexusformat');
            }

            $comment->content = $content;
            $comment->timemodified = $now;

            $DB->update_record('format_nexusformat_comments', $comment);

            return [
                'success' => true,
                'commentid' => (int)$comment->id,
                'message' => get_string('comment_saved', 'format_nexusformat'),
                'isreply' => !empty($comment->parentid) ? 1 : 0,
            ];
        } else {
            // Create new comment.
            $comment = new \stdClass();
            $comment->userid = $USER->id;
            $comment->courseid = $courseid;
            $comment->cmid = $cmid;
            $comment->parentid = $parentid > 0 ? $parentid : null;
            $comment->content = $content;
            $comment->contentformat = FORMAT_HTML;
            $comment->timecreated = $now;
            $comment->timemodified = $now;

            // Validate parent exists if replying.
            if ($parentid > 0) {
                $parent = $DB->get_record('format_nexusformat_comments', ['id' => $parentid, 'cmid' => $cmid]);
                if (!$parent) {
                    throw new \moodle_exception('commentnotfound', 'format_nexusformat');
                }
            }

            $comment->id = $DB->insert_record('format_nexusformat_comments', $comment);

            return [
                'success' => true,
                'commentid' => (int)$comment->id,
                'message' => get_string('comment_saved', 'format_nexusformat'),
                'isreply' => $parentid > 0 ? 1 : 0,
            ];
        }
    }

    /**
     * Returns description of method result value.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'success' => new external_value(PARAM_BOOL, 'Whether the operation was successful'),
            'commentid' => new external_value(PARAM_INT, 'The comment ID'),
            'message' => new external_value(PARAM_TEXT, 'Status message'),
            'isreply' => new external_value(PARAM_INT, 'Is this a reply'),
        ]);
    }
}
