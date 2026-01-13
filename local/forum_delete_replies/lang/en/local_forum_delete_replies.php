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
 * Language strings for local_forum_delete_replies (English)
 *
 * @package    local_forum_delete_replies
 * @copyright  2025 Your Organization
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['pluginname'] = 'Delete Forum Replies';
$string['deleteallreplies'] = 'Delete all replies';
$string['deletefromforum'] = 'Delete replies from forum';
$string['selectforum'] = 'Select a forum';
$string['selectforumhelp'] = 'Select the forum from which you want to delete all replies. Only the initial post of each discussion will be kept.';
$string['nodiscussions'] = 'This forum has no discussions.';
$string['noforums'] = 'There are no forums in this course.';
$string['noreplies'] = 'There are no replies to delete in this forum.';
$string['confirmdelete'] = 'Confirm deletion';
$string['confirmdeletemessage'] = 'Are you sure you want to delete all replies from the forum "{$a->forumname}"? This action will delete {$a->replycount} replies from {$a->discussioncount} discussions. Only the initial post of each discussion will be preserved. This action cannot be undone.';
$string['deletesuccess'] = 'Successfully deleted {$a->deleted} replies from {$a->discussions} discussions.';
$string['deleteerror'] = 'Error deleting some replies. {$a->deleted} of {$a->total} replies were deleted.';
$string['deletepartial'] = '{$a->deleted} replies deleted. {$a->errors} errors occurred.';
$string['cancel'] = 'Cancel';
$string['delete'] = 'Delete replies';
$string['deleting'] = 'Deleting replies...';
$string['forum'] = 'Forum';
$string['discussions'] = 'Discussions';
$string['replies'] = 'Replies to delete';
$string['firstpost'] = 'Initial post (preserved)';
$string['preview'] = 'Preview';
$string['previewmessage'] = 'The following replies will be deleted:';
$string['backtocourse'] = 'Back to course';
$string['backtoforum'] = 'Back to forum';
$string['nopermission'] = 'You do not have permission to delete replies from this forum.';
$string['invalidforum'] = 'Invalid forum specified.';
$string['invalidcourse'] = 'Invalid course specified.';
$string['discussionname'] = 'Discussion';
$string['replycount'] = 'Replies';
$string['author'] = 'Author';
$string['created'] = 'Created';
$string['willbedeleted'] = 'Will be deleted';
$string['preserved'] = 'Preserved';
$string['privacy:metadata'] = 'The Delete Forum Replies plugin does not store any personal data.';
$string['forum_delete_replies:delete'] = 'Delete all replies from forums';
$string['forum_delete_replies:view'] = 'View delete replies page';
$string['summary'] = 'Summary';
$string['totalreplies'] = 'Total replies to delete';
$string['totaldiscussions'] = 'Total discussions affected';
$string['warning'] = 'Warning';
$string['warningmessage'] = 'This operation is irreversible. All replies, including their attachments and ratings, will be permanently deleted.';
$string['processing'] = 'Processing discussion {$a->current} of {$a->total}...';
$string['completed'] = 'Operation completed';
$string['deletedrepliesfromdiscussion'] = 'Deleted {$a->count} replies from discussion "{$a->name}"';
$string['bulkdelete'] = 'Delete all replies from course';
$string['bulkwarning'] = 'This will delete ALL replies from ALL forums in this course with a single action.';
$string['bulkconfirmmessage'] = 'You are about to delete {$a->replycount} replies from {$a->forumcount} forums in course "{$a->coursename}". This action cannot be undone.';
$string['deleteallnow'] = 'Delete all replies now';
$string['totalforums'] = 'Total forums';
$string['forumswithReplies'] = 'Forums with replies';
$string['forumlist'] = 'Forums in course';
$string['sitewidedelete'] = 'Delete replies site-wide';
$string['sitewidedeletewarning'] = 'WARNING: This will delete ALL replies from ALL forums in the ENTIRE site!';
$string['sitewideconfirm'] = 'Are you absolutely sure? This will delete {$a} replies from all courses.';
$string['oneclickdelete'] = 'One-click delete';
