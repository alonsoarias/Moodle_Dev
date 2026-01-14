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
 * Library functions for local_forum_delete_replies
 *
 * @package    local_forum_delete_replies
 * @copyright  2025 Your Organization
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Extends forum module settings navigation with delete replies link.
 *
 * @param settings_navigation $settingsnav The settings navigation object
 * @param context $context The context of the page
 * @return void
 */
function local_forum_delete_replies_extend_settings_navigation(settings_navigation $settingsnav, context $context): void {
    // Only add to forum module pages.
    if ($context->contextlevel != CONTEXT_MODULE) {
        return;
    }

    $cm = get_coursemodule_from_id('forum', $context->instanceid);
    if (!$cm) {
        return;
    }

    // Check capability.
    if (!has_capability('local/forum_delete_replies:delete', $context)) {
        return;
    }

    // Find the module settings node.
    $modulesettings = $settingsnav->find('modulesettings', navigation_node::TYPE_SETTING);
    if (!$modulesettings) {
        return;
    }

    // Add delete replies link.
    $url = new moodle_url('/local/forum_delete_replies/cleanup.php', [
        'forumid' => $cm->instance,
        'courseid' => $cm->course,
    ]);

    $modulesettings->add(
        get_string('deleteallreplies', 'local_forum_delete_replies'),
        $url,
        navigation_node::TYPE_SETTING,
        null,
        'local_forum_delete_replies',
        new pix_icon('t/delete', '')
    );
}

/**
 * Extends course navigation with delete replies link.
 *
 * @param navigation_node $navigation The navigation node
 * @param stdClass $course The course object
 * @param context_course $context The course context
 * @return void
 */
function local_forum_delete_replies_extend_navigation_course(
    navigation_node $navigation,
    stdClass $course,
    context_course $context
): void {
    // Check if user can manage activities.
    if (!has_capability('moodle/course:manageactivities', $context)) {
        return;
    }

    // Check if there are forums in the course.
    $forums = get_all_instances_in_course('forum', $course);
    if (empty($forums)) {
        return;
    }

    // Add link to course navigation.
    $url = new moodle_url('/local/forum_delete_replies/index.php', ['courseid' => $course->id]);

    $navigation->add(
        get_string('deleteallreplies', 'local_forum_delete_replies'),
        $url,
        navigation_node::TYPE_CUSTOM,
        null,
        'local_forum_delete_replies',
        new pix_icon('t/delete', '')
    );
}
