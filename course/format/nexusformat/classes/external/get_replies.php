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
use context_module;
use context_course;

/**
 * External function for getting replies to a comment.
 *
 * @package    format_nexusformat
 * @copyright  2024 Nexus Learning
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class get_replies extends external_api {

    /**
     * Returns description of method parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'commentid' => new external_value(PARAM_INT, 'Parent comment ID'),
        ]);
    }

    /**
     * Get replies to a comment.
     *
     * @param int $commentid Parent comment ID
     * @return array Replies data
     */
    public static function execute(int $commentid): array {
        global $DB, $USER, $PAGE;

        // Validate parameters.
        $params = self::validate_parameters(self::execute_parameters(), [
            'commentid' => $commentid,
        ]);

        $commentid = $params['commentid'];

        // Get the parent comment.
        $parent = $DB->get_record('format_nexusformat_comments', ['id' => $commentid]);
        if (!$parent) {
            throw new \moodle_exception('commentnotfound', 'format_nexusformat');
        }

        // Check context.
        $context = context_module::instance($parent->cmid);
        self::validate_context($context);

        $courseid = $parent->courseid;
        $coursecontext = context_course::instance($courseid);

        // Get replies.
        $sql = "SELECT c.*,
                       (SELECT COUNT(*) FROM {format_nexusformat_likes} l WHERE l.commentid = c.id) as likecount,
                       (SELECT COUNT(*) FROM {format_nexusformat_likes} l WHERE l.commentid = c.id AND l.userid = :currentuser) as userliked
                FROM {format_nexusformat_comments} c
                WHERE c.parentid = :parentid
                ORDER BY c.timecreated ASC";

        $replies = $DB->get_records_sql($sql, [
            'parentid' => $commentid,
            'currentuser' => $USER->id,
        ]);

        // Format replies.
        $result = [];
        foreach ($replies as $reply) {
            $user = $DB->get_record('user', ['id' => $reply->userid], 'id, firstname, lastname, email, picture, imagealt');

            $userpicture = new \user_picture($user);
            $userpicture->size = 40;
            $avatarurl = $userpicture->get_url($PAGE)->out(false);

            $isteacher = has_capability('moodle/course:manageactivities', $coursecontext, $reply->userid);
            $role = $isteacher ? get_string('teacher', 'format_nexusformat') : get_string('student', 'format_nexusformat');

            $result[] = [
                'id' => (int)$reply->id,
                'userid' => (int)$reply->userid,
                'username' => fullname($user),
                'avatarurl' => $avatarurl,
                'role' => $role,
                'isteacher' => $isteacher ? 1 : 0,
                'content' => format_text($reply->content, $reply->contentformat),
                'rawcontent' => $reply->content,
                'timecreated' => (int)$reply->timecreated,
                'timeago' => self::time_ago($reply->timecreated),
                'likecount' => (int)($reply->likecount ?? 0),
                'userliked' => !empty($reply->userliked) ? 1 : 0,
                'candelete' => ($reply->userid == $USER->id || has_capability('moodle/course:manageactivities', $coursecontext)) ? 1 : 0,
                'canedit' => ($reply->userid == $USER->id) ? 1 : 0,
                'isown' => ($reply->userid == $USER->id) ? 1 : 0,
            ];
        }

        return [
            'replies' => $result,
            'count' => count($result),
        ];
    }

    /**
     * Get human-readable time ago string.
     *
     * @param int $timestamp Unix timestamp
     * @return string Time ago string
     */
    protected static function time_ago(int $timestamp): string {
        $diff = time() - $timestamp;

        if ($diff < 60) {
            return get_string('time_ago', 'format_nexusformat', get_string('now', 'moodle'));
        } else if ($diff < 3600) {
            $mins = floor($diff / 60);
            return get_string('time_ago', 'format_nexusformat', $mins . ' min');
        } else if ($diff < 86400) {
            $hours = floor($diff / 3600);
            return get_string('time_ago', 'format_nexusformat', $hours . 'h');
        } else if ($diff < 604800) {
            $days = floor($diff / 86400);
            return get_string('time_ago', 'format_nexusformat', $days . 'd');
        } else {
            return userdate($timestamp, get_string('strftimedatefullshort', 'langconfig'));
        }
    }

    /**
     * Returns description of method result value.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'replies' => new external_multiple_structure(
                new external_single_structure([
                    'id' => new external_value(PARAM_INT, 'Reply ID'),
                    'userid' => new external_value(PARAM_INT, 'User ID'),
                    'username' => new external_value(PARAM_TEXT, 'User full name'),
                    'avatarurl' => new external_value(PARAM_URL, 'User avatar URL'),
                    'role' => new external_value(PARAM_TEXT, 'User role'),
                    'isteacher' => new external_value(PARAM_INT, 'Is teacher'),
                    'content' => new external_value(PARAM_RAW, 'Formatted reply content'),
                    'rawcontent' => new external_value(PARAM_RAW, 'Raw reply content'),
                    'timecreated' => new external_value(PARAM_INT, 'Time created'),
                    'timeago' => new external_value(PARAM_TEXT, 'Time ago string'),
                    'likecount' => new external_value(PARAM_INT, 'Number of likes'),
                    'userliked' => new external_value(PARAM_INT, 'Current user liked'),
                    'candelete' => new external_value(PARAM_INT, 'Can delete'),
                    'canedit' => new external_value(PARAM_INT, 'Can edit'),
                    'isown' => new external_value(PARAM_INT, 'Is own reply'),
                ])
            ),
            'count' => new external_value(PARAM_INT, 'Number of replies'),
        ]);
    }
}
