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
 * Cleanup manager for deleting forum replies from a single forum.
 *
 * @package    local_forum_delete_replies
 * @copyright  2025 Your Organization
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_forum_delete_replies;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/forum/lib.php');

/**
 * Manager class for handling forum reply deletion for a single forum.
 *
 * @package    local_forum_delete_replies
 * @copyright  2025 Your Organization
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class cleanup_manager {

    /** @var int The forum ID */
    protected $forumid;

    /** @var \stdClass The forum record */
    protected $forum;

    /** @var \stdClass The course record */
    protected $course;

    /** @var \stdClass The course module record */
    protected $cm;

    /** @var \context_module The module context */
    protected $context;

    /**
     * Constructor.
     *
     * @param int $forumid The forum ID
     * @throws \moodle_exception If forum is invalid
     */
    public function __construct(int $forumid) {
        global $DB;

        $this->forumid = $forumid;
        $this->forum = $DB->get_record('forum', ['id' => $forumid], '*', MUST_EXIST);
        $this->course = get_course($this->forum->course);
        $this->cm = get_coursemodule_from_instance('forum', $forumid, $this->course->id, false, MUST_EXIST);
        $this->context = \context_module::instance($this->cm->id);
    }

    /**
     * Get the forum record.
     *
     * @return \stdClass The forum record
     */
    public function get_forum(): \stdClass {
        return $this->forum;
    }

    /**
     * Get the course record.
     *
     * @return \stdClass The course record
     */
    public function get_course(): \stdClass {
        return $this->course;
    }

    /**
     * Get the course module record.
     *
     * @return \stdClass The course module record
     */
    public function get_cm(): \stdClass {
        return $this->cm;
    }

    /**
     * Get the module context.
     *
     * @return \context_module The module context
     */
    public function get_context(): \context_module {
        return $this->context;
    }

    /**
     * Check if user has permission to delete replies.
     *
     * @param int|null $userid User ID (null for current user)
     * @return bool True if user can delete
     */
    public function can_delete(?int $userid = null): bool {
        return has_capability('local/forum_delete_replies:delete', $this->context, $userid);
    }

    /**
     * Get count of replies (posts with parent != 0).
     *
     * @return int Number of replies
     */
    public function get_reply_count(): int {
        global $DB;

        return $DB->count_records_select(
            'forum_posts',
            'discussion IN (SELECT id FROM {forum_discussions} WHERE forum = :forumid) AND parent != 0',
            ['forumid' => $this->forumid]
        );
    }

    /**
     * Get discussions with their reply counts.
     *
     * @return array Array of discussion objects with reply_count property
     */
    public function get_discussions_with_reply_counts(): array {
        global $DB;

        $sql = "SELECT d.id, d.name, d.firstpost, d.userid, d.timemodified,
                       (SELECT COUNT(*) FROM {forum_posts} p WHERE p.discussion = d.id AND p.parent != 0) as reply_count
                  FROM {forum_discussions} d
                 WHERE d.forum = :forumid
                 ORDER BY d.timemodified DESC";

        return $DB->get_records_sql($sql, ['forumid' => $this->forumid]);
    }

    /**
     * Get detailed information about replies to be deleted.
     *
     * @param int $limit Maximum number of records to return (0 for all)
     * @return array Array of post objects with user and discussion info
     */
    public function get_replies_preview(int $limit = 100): array {
        global $DB;

        $sql = "SELECT p.id, p.discussion, p.parent, p.userid, p.created, p.subject,
                       d.name as discussion_name,
                       u.firstname, u.lastname, u.email
                  FROM {forum_posts} p
                  JOIN {forum_discussions} d ON d.id = p.discussion
                  JOIN {user} u ON u.id = p.userid
                 WHERE d.forum = :forumid AND p.parent != 0
                 ORDER BY p.created DESC";

        if ($limit > 0) {
            return $DB->get_records_sql($sql, ['forumid' => $this->forumid], 0, $limit);
        }

        return $DB->get_records_sql($sql, ['forumid' => $this->forumid]);
    }

    /**
     * Get all reply post IDs for this forum.
     *
     * @return array Array of post IDs
     */
    public function get_reply_ids(): array {
        global $DB;

        $sql = "SELECT p.id
                  FROM {forum_posts} p
                  JOIN {forum_discussions} d ON d.id = p.discussion
                 WHERE d.forum = :forumid AND p.parent != 0
                 ORDER BY p.id DESC";

        return $DB->get_fieldset_sql($sql, ['forumid' => $this->forumid]);
    }

    /**
     * Delete all replies using Moodle's API (safe method).
     *
     * This method properly handles attachments, ratings, events, etc.
     *
     * @param callable|null $progresscallback Callback function for progress updates
     * @return array Result with 'deleted', 'errors', 'total', and 'error_messages'
     */
    public function delete_replies_safe(?callable $progresscallback = null): array {
        global $DB;

        $result = [
            'deleted' => 0,
            'errors' => 0,
            'total' => 0,
            'error_messages' => [],
        ];

        // Get all discussions.
        $discussions = $DB->get_records('forum_discussions', ['forum' => $this->forumid]);

        $discussioncount = count($discussions);
        $current = 0;

        foreach ($discussions as $discussion) {
            $current++;

            if ($progresscallback) {
                $progresscallback($current, $discussioncount, $discussion->name);
            }

            // Get all reply posts (parent != 0) for this discussion, ordered by ID descending.
            // Deleting in reverse order (newest first) helps avoid orphan issues.
            $posts = $DB->get_records_select(
                'forum_posts',
                'discussion = :discussionid AND parent != 0',
                ['discussionid' => $discussion->id],
                'id DESC'
            );

            foreach ($posts as $post) {
                $result['total']++;

                try {
                    // Add required properties.
                    $post->course = $this->course->id;
                    $post->forum = $this->forumid;

                    // Delete post with children = 'ignore' because we're deleting all anyway.
                    $deleted = forum_delete_post($post, 'ignore', $this->course, $this->cm, $this->forum, true);

                    if ($deleted) {
                        $result['deleted']++;
                    } else {
                        $result['errors']++;
                        $result['error_messages'][] = "Failed to delete post ID: {$post->id}";
                    }
                } catch (\Exception $e) {
                    $result['errors']++;
                    $result['error_messages'][] = "Error deleting post ID {$post->id}: " . $e->getMessage();
                }
            }
        }

        // Clear caches.
        \cache_helper::purge_by_event('changesinforumdiscussions');

        return $result;
    }

    /**
     * Delete all replies using direct SQL (fast method).
     *
     * Use this for large forums where API method would be too slow.
     * WARNING: This bypasses Moodle events.
     *
     * @return array Result with 'deleted', 'errors', 'total', and 'error_messages'
     */
    public function delete_replies_fast(): array {
        global $DB, $CFG;

        $result = [
            'deleted' => 0,
            'errors' => 0,
            'total' => 0,
            'error_messages' => [],
        ];

        // Start transaction.
        $transaction = $DB->start_delegated_transaction();

        try {
            // Get post IDs to delete.
            $postids = $this->get_reply_ids();
            $result['total'] = count($postids);

            if (empty($postids)) {
                $transaction->allow_commit();
                return $result;
            }

            // Delete from forum_read.
            list($insql, $params) = $DB->get_in_or_equal($postids, SQL_PARAMS_NAMED);
            $DB->delete_records_select('forum_read', "postid $insql", $params);

            // Delete from forum_queue.
            $DB->delete_records_select('forum_queue', "postid $insql", $params);

            // Delete ratings for these posts.
            $DB->delete_records_select(
                'rating',
                "component = 'mod_forum' AND ratingarea = 'post' AND itemid $insql",
                $params
            );

            // Delete files associated with these posts.
            $fs = get_file_storage();
            foreach ($postids as $postid) {
                $fs->delete_area_files($this->context->id, 'mod_forum', 'attachment', $postid);
                $fs->delete_area_files($this->context->id, 'mod_forum', 'post', $postid);
            }

            // Delete the posts.
            $DB->delete_records_select('forum_posts', "id $insql", $params);

            $result['deleted'] = $result['total'];

            // Update last post for all discussions.
            $discussions = $DB->get_records('forum_discussions', ['forum' => $this->forumid]);
            foreach ($discussions as $discussion) {
                forum_discussion_update_last_post($discussion->id);
            }

            // Commit transaction.
            $transaction->allow_commit();

            // Clear caches.
            \cache_helper::purge_by_event('changesinforumdiscussions');

            // Invalidate RSS feeds.
            if (!empty($CFG->enablerssfeeds)) {
                require_once($CFG->dirroot . '/mod/forum/rsslib.php');
                forum_rss_delete_file($this->forum);
            }

        } catch (\Exception $e) {
            $transaction->rollback($e);
            $result['errors'] = $result['total'];
            $result['deleted'] = 0;
            $result['error_messages'][] = $e->getMessage();
        }

        return $result;
    }

    /**
     * Get summary statistics for the forum.
     *
     * @return array Summary data with forum, course, discussions, and replies info
     */
    public function get_summary(): array {
        $discussions = $this->get_discussions_with_reply_counts();
        $totalreplies = 0;
        $discussionswithreplies = 0;

        foreach ($discussions as $discussion) {
            $totalreplies += $discussion->reply_count;
            if ($discussion->reply_count > 0) {
                $discussionswithreplies++;
            }
        }

        return [
            'forum' => $this->forum,
            'course' => $this->course,
            'total_discussions' => count($discussions),
            'discussions_with_replies' => $discussionswithreplies,
            'total_replies' => $totalreplies,
            'discussions' => $discussions,
        ];
    }
}
