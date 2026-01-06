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
use context_module;

/**
 * External function for getting comments on an activity.
 *
 * @package    format_nexusformat
 * @copyright  2024 Nexus Learning
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class get_comments extends external_api {

    /**
     * Returns description of method parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'cmid' => new external_value(PARAM_INT, 'Course module ID'),
            'sortby' => new external_value(PARAM_ALPHA, 'Sort order: newest, oldest, likes', VALUE_DEFAULT, 'newest'),
            'page' => new external_value(PARAM_INT, 'Page number', VALUE_DEFAULT, 0),
            'perpage' => new external_value(PARAM_INT, 'Comments per page', VALUE_DEFAULT, 20),
        ]);
    }

    /**
     * Get comments for an activity.
     *
     * @param int $cmid Course module ID
     * @param string $sortby Sort order
     * @param int $page Page number
     * @param int $perpage Comments per page
     * @return array Comments data
     */
    public static function execute(int $cmid, string $sortby = 'newest', int $page = 0, int $perpage = 20): array {
        global $DB, $USER, $OUTPUT;

        // Validate parameters.
        $params = self::validate_parameters(self::execute_parameters(), [
            'cmid' => $cmid,
            'sortby' => $sortby,
            'page' => $page,
            'perpage' => $perpage,
        ]);

        $cmid = $params['cmid'];
        $sortby = $params['sortby'];
        $page = $params['page'];
        $perpage = $params['perpage'];

        // Get course module and context.
        $cm = get_coursemodule_from_id('', $cmid, 0, false, MUST_EXIST);
        $context = context_module::instance($cmid);
        self::validate_context($context);

        $courseid = $cm->course;

        // Build sort clause.
        switch ($sortby) {
            case 'oldest':
                $sort = 'c.timecreated ASC';
                break;
            case 'likes':
                $sort = 'likecount DESC, c.timecreated DESC';
                break;
            case 'newest':
            default:
                $sort = 'c.timecreated DESC';
        }

        // Get top-level comments (parentid is null).
        $sql = "SELECT c.*,
                       (SELECT COUNT(*) FROM {format_nexusformat_likes} l WHERE l.commentid = c.id) as likecount,
                       (SELECT COUNT(*) FROM {format_nexusformat_likes} l WHERE l.commentid = c.id AND l.userid = :currentuser) as userliked,
                       (SELECT COUNT(*) FROM {format_nexusformat_comments} r WHERE r.parentid = c.id) as replycount
                FROM {format_nexusformat_comments} c
                WHERE c.cmid = :cmid AND c.parentid IS NULL
                ORDER BY {$sort}";

        $offset = $page * $perpage;
        $comments = $DB->get_records_sql($sql, [
            'cmid' => $cmid,
            'currentuser' => $USER->id,
        ], $offset, $perpage);

        // Get total count.
        $totalcount = $DB->count_records('format_nexusformat_comments', [
            'cmid' => $cmid,
            'parentid' => null,
        ]);

        // Format comments.
        $result = [];
        foreach ($comments as $comment) {
            $result[] = self::format_comment($comment, $courseid);
        }

        return [
            'comments' => $result,
            'totalcount' => $totalcount,
            'page' => $page,
            'perpage' => $perpage,
            'hasprevious' => $page > 0,
            'hasnext' => ($page + 1) * $perpage < $totalcount,
        ];
    }

    /**
     * Format a comment for output.
     *
     * @param object $comment Comment record
     * @param int $courseid Course ID
     * @return array Formatted comment
     */
    protected static function format_comment(object $comment, int $courseid): array {
        global $DB, $USER, $OUTPUT, $PAGE;

        // Get user info.
        $user = $DB->get_record('user', ['id' => $comment->userid], 'id, firstname, lastname, email, picture, imagealt');

        // Get user picture URL.
        $userpicture = new \user_picture($user);
        $userpicture->size = 50;
        $avatarurl = $userpicture->get_url($PAGE)->out(false);

        // Determine role.
        $context = context_course::instance($courseid);
        $isteacher = has_capability('moodle/course:manageactivities', $context, $comment->userid);
        $role = $isteacher ? get_string('teacher', 'format_nexusformat') : get_string('student', 'format_nexusformat');

        // Format time.
        $timeago = self::time_ago($comment->timecreated);

        return [
            'id' => (int)$comment->id,
            'userid' => (int)$comment->userid,
            'username' => fullname($user),
            'avatarurl' => $avatarurl,
            'role' => $role,
            'isteacher' => $isteacher ? 1 : 0,
            'content' => format_text($comment->content, $comment->contentformat),
            'rawcontent' => $comment->content,
            'timecreated' => (int)$comment->timecreated,
            'timeago' => $timeago,
            'likecount' => (int)($comment->likecount ?? 0),
            'userliked' => !empty($comment->userliked) ? 1 : 0,
            'replycount' => (int)($comment->replycount ?? 0),
            'candelete' => ($comment->userid == $USER->id || has_capability('moodle/course:manageactivities', $context)) ? 1 : 0,
            'canedit' => ($comment->userid == $USER->id) ? 1 : 0,
            'isown' => ($comment->userid == $USER->id) ? 1 : 0,
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
            'comments' => new external_multiple_structure(
                new external_single_structure([
                    'id' => new external_value(PARAM_INT, 'Comment ID'),
                    'userid' => new external_value(PARAM_INT, 'User ID'),
                    'username' => new external_value(PARAM_TEXT, 'User full name'),
                    'avatarurl' => new external_value(PARAM_URL, 'User avatar URL'),
                    'role' => new external_value(PARAM_TEXT, 'User role'),
                    'isteacher' => new external_value(PARAM_INT, 'Is teacher'),
                    'content' => new external_value(PARAM_RAW, 'Formatted comment content'),
                    'rawcontent' => new external_value(PARAM_RAW, 'Raw comment content'),
                    'timecreated' => new external_value(PARAM_INT, 'Time created'),
                    'timeago' => new external_value(PARAM_TEXT, 'Time ago string'),
                    'likecount' => new external_value(PARAM_INT, 'Number of likes'),
                    'userliked' => new external_value(PARAM_INT, 'Current user liked'),
                    'replycount' => new external_value(PARAM_INT, 'Number of replies'),
                    'candelete' => new external_value(PARAM_INT, 'Can delete'),
                    'canedit' => new external_value(PARAM_INT, 'Can edit'),
                    'isown' => new external_value(PARAM_INT, 'Is own comment'),
                ])
            ),
            'totalcount' => new external_value(PARAM_INT, 'Total number of comments'),
            'page' => new external_value(PARAM_INT, 'Current page'),
            'perpage' => new external_value(PARAM_INT, 'Comments per page'),
            'hasprevious' => new external_value(PARAM_BOOL, 'Has previous page'),
            'hasnext' => new external_value(PARAM_BOOL, 'Has next page'),
        ]);
    }
}
