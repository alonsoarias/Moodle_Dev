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
 * Bulk cleanup manager for deleting forum replies across multiple forums.
 *
 * @package    local_forum_delete_replies
 * @copyright  2025 Your Organization
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_forum_delete_replies;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/forum/lib.php');

/**
 * Bulk manager class for mass forum reply deletion across courses or site-wide.
 *
 * @package    local_forum_delete_replies
 * @copyright  2025 Your Organization
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class bulk_manager {

    /**
     * Get total reply count for a course (all forums).
     *
     * @param int $courseid Course ID
     * @return int Number of replies
     */
    public static function get_course_reply_count(int $courseid): int {
        global $DB;

        $sql = "SELECT COUNT(p.id)
                  FROM {forum_posts} p
                  JOIN {forum_discussions} d ON d.id = p.discussion
                  JOIN {forum} f ON f.id = d.forum
                 WHERE f.course = :courseid AND p.parent != 0";

        return $DB->count_records_sql($sql, ['courseid' => $courseid]);
    }

    /**
     * Get total reply count for entire site.
     *
     * @return int Number of replies
     */
    public static function get_site_reply_count(): int {
        global $DB;

        return $DB->count_records_select('forum_posts', 'parent != 0');
    }

    /**
     * Get forums summary for a course.
     *
     * @param int $courseid Course ID
     * @return array Array with forum stats including forums, counts, and totals
     */
    public static function get_course_forums_summary(int $courseid): array {
        global $DB;

        $sql = "SELECT f.id, f.name,
                       (SELECT COUNT(*) FROM {forum_discussions} d WHERE d.forum = f.id) as discussion_count,
                       (SELECT COUNT(*) FROM {forum_posts} p
                        JOIN {forum_discussions} d2 ON d2.id = p.discussion
                        WHERE d2.forum = f.id AND p.parent != 0) as reply_count
                  FROM {forum} f
                 WHERE f.course = :courseid
                 ORDER BY f.name";

        $forums = $DB->get_records_sql($sql, ['courseid' => $courseid]);

        $totalreplies = 0;
        $forumswithreplies = 0;

        foreach ($forums as $forum) {
            $totalreplies += $forum->reply_count;
            if ($forum->reply_count > 0) {
                $forumswithreplies++;
            }
        }

        return [
            'forums' => $forums,
            'total_forums' => count($forums),
            'forums_with_replies' => $forumswithreplies,
            'total_replies' => $totalreplies,
        ];
    }

    /**
     * Delete all replies from all forums in a course using SQL (fast method).
     *
     * This is the equivalent of running SQL commands directly.
     *
     * @param int $courseid Course ID
     * @param callable|null $progresscallback Progress callback function
     * @return array Result with 'deleted', 'errors', 'total', 'forums_cleaned', and 'error_messages'
     */
    public static function delete_course_replies_fast(int $courseid, ?callable $progresscallback = null): array {
        global $DB, $CFG;

        $result = [
            'deleted' => 0,
            'errors' => 0,
            'total' => 0,
            'forums_cleaned' => 0,
            'error_messages' => [],
        ];

        // Get all post IDs to delete for this course.
        $sql = "SELECT p.id
                  FROM {forum_posts} p
                  JOIN {forum_discussions} d ON d.id = p.discussion
                  JOIN {forum} f ON f.id = d.forum
                 WHERE f.course = :courseid AND p.parent != 0";

        $postids = $DB->get_fieldset_sql($sql, ['courseid' => $courseid]);
        $result['total'] = count($postids);

        if (empty($postids)) {
            return $result;
        }

        // Start transaction.
        $transaction = $DB->start_delegated_transaction();

        try {
            if ($progresscallback) {
                $progresscallback(1, 5, get_string('progress_deleting_read', 'local_forum_delete_replies'));
            }

            // 1. Delete from forum_read.
            list($insql, $params) = $DB->get_in_or_equal($postids, SQL_PARAMS_NAMED);
            $DB->delete_records_select('forum_read', "postid $insql", $params);

            if ($progresscallback) {
                $progresscallback(2, 5, get_string('progress_deleting_queue', 'local_forum_delete_replies'));
            }

            // 2. Delete from forum_queue.
            $DB->delete_records_select('forum_queue', "postid $insql", $params);

            if ($progresscallback) {
                $progresscallback(3, 5, get_string('progress_deleting_ratings', 'local_forum_delete_replies'));
            }

            // 3. Delete ratings.
            $DB->delete_records_select(
                'rating',
                "component = 'mod_forum' AND ratingarea = 'post' AND itemid $insql",
                $params
            );

            if ($progresscallback) {
                $progresscallback(4, 5, get_string('progress_deleting_files', 'local_forum_delete_replies'));
            }

            // 4. Delete files - get all forum contexts in the course.
            $forums = $DB->get_records('forum', ['course' => $courseid]);
            $fs = get_file_storage();

            foreach ($forums as $forum) {
                $cm = get_coursemodule_from_instance('forum', $forum->id, $courseid, false, IGNORE_MISSING);
                if ($cm) {
                    $context = \context_module::instance($cm->id);
                    // Get post IDs for this specific forum.
                    $forumpostids = $DB->get_fieldset_sql(
                        "SELECT p.id FROM {forum_posts} p
                         JOIN {forum_discussions} d ON d.id = p.discussion
                         WHERE d.forum = :forumid AND p.parent != 0",
                        ['forumid' => $forum->id]
                    );
                    foreach ($forumpostids as $postid) {
                        $fs->delete_area_files($context->id, 'mod_forum', 'attachment', $postid);
                        $fs->delete_area_files($context->id, 'mod_forum', 'post', $postid);
                    }
                    $result['forums_cleaned']++;
                }
            }

            if ($progresscallback) {
                $progresscallback(5, 5, get_string('progress_deleting_posts', 'local_forum_delete_replies'));
            }

            // 5. Delete the posts.
            $DB->delete_records_select('forum_posts', "id $insql", $params);

            $result['deleted'] = $result['total'];

            // 6. Update last post for all discussions in the course.
            $sql = "SELECT d.id FROM {forum_discussions} d
                    JOIN {forum} f ON f.id = d.forum
                    WHERE f.course = :courseid";
            $discussions = $DB->get_fieldset_sql($sql, ['courseid' => $courseid]);

            foreach ($discussions as $discussionid) {
                forum_discussion_update_last_post($discussionid);
            }

            // Commit transaction.
            $transaction->allow_commit();

            // Clear caches.
            \cache_helper::purge_by_event('changesinforumdiscussions');

        } catch (\Exception $e) {
            $transaction->rollback($e);
            $result['errors'] = $result['total'];
            $result['deleted'] = 0;
            $result['error_messages'][] = $e->getMessage();
        }

        return $result;
    }

    /**
     * Delete all replies from entire site using SQL (fast method).
     *
     * WARNING: This affects ALL forums in ALL courses!
     *
     * @param callable|null $progresscallback Progress callback function
     * @return array Result with 'deleted', 'errors', 'total', and 'error_messages'
     */
    public static function delete_site_replies_fast(?callable $progresscallback = null): array {
        global $DB, $CFG;

        $result = [
            'deleted' => 0,
            'errors' => 0,
            'total' => 0,
            'error_messages' => [],
        ];

        // Count total posts to delete.
        $result['total'] = $DB->count_records_select('forum_posts', 'parent != 0');

        if ($result['total'] == 0) {
            return $result;
        }

        // Start transaction.
        $transaction = $DB->start_delegated_transaction();

        try {
            if ($progresscallback) {
                $progresscallback(1, 6, get_string('progress_preparing', 'local_forum_delete_replies'));
            }

            // Get IDs of posts to delete (for dependent tables).
            $postids = $DB->get_fieldset_select('forum_posts', 'id', 'parent != 0');

            if ($progresscallback) {
                $progresscallback(2, 6, get_string('progress_deleting_read', 'local_forum_delete_replies'));
            }

            // 1. Delete from forum_read where post is a reply.
            $DB->execute("DELETE FROM {forum_read} WHERE postid IN (SELECT id FROM {forum_posts} WHERE parent != 0)");

            if ($progresscallback) {
                $progresscallback(3, 6, get_string('progress_deleting_queue', 'local_forum_delete_replies'));
            }

            // 2. Delete from forum_queue.
            $DB->execute("DELETE FROM {forum_queue} WHERE postid IN (SELECT id FROM {forum_posts} WHERE parent != 0)");

            if ($progresscallback) {
                $progresscallback(4, 6, get_string('progress_deleting_ratings', 'local_forum_delete_replies'));
            }

            // 3. Delete ratings.
            list($insql, $params) = $DB->get_in_or_equal($postids, SQL_PARAMS_NAMED);
            $DB->delete_records_select(
                'rating',
                "component = 'mod_forum' AND ratingarea = 'post' AND itemid $insql",
                $params
            );

            if ($progresscallback) {
                $progresscallback(5, 6, get_string('progress_deleting_posts_long', 'local_forum_delete_replies'));
            }

            // 4. Delete all reply posts.
            $DB->delete_records_select('forum_posts', 'parent != 0');

            $result['deleted'] = $result['total'];

            if ($progresscallback) {
                $progresscallback(6, 6, get_string('progress_updating_discussions', 'local_forum_delete_replies'));
            }

            // 5. Update last post for all discussions.
            $discussions = $DB->get_fieldset_select('forum_discussions', 'id', '1=1');
            foreach ($discussions as $discussionid) {
                forum_discussion_update_last_post($discussionid);
            }

            // Commit transaction.
            $transaction->allow_commit();

            // Clear all caches.
            \cache_helper::purge_by_event('changesinforumdiscussions');
            purge_all_caches();

        } catch (\Exception $e) {
            $transaction->rollback($e);
            $result['errors'] = $result['total'];
            $result['deleted'] = 0;
            $result['error_messages'][] = $e->getMessage();
        }

        return $result;
    }
}
