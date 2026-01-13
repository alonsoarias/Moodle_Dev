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
 * Extends course settings navigation with delete replies link
 *
 * @param settings_navigation $settingsnav
 * @param context $context
 */
function local_forum_delete_replies_extend_settings_navigation(settings_navigation $settingsnav, context $context) {
    global $PAGE;

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
 * Extends course navigation with delete replies link
 *
 * @param navigation_node $navigation
 * @param stdClass $course
 * @param context_course $context
 */
function local_forum_delete_replies_extend_navigation_course(navigation_node $navigation, stdClass $course, context_course $context) {
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

/**
 * Add link to the forum activity action menu
 *
 * @param cm_info $cm
 * @param array $actions
 * @return array
 */
function local_forum_delete_replies_cm_info_dynamic(cm_info $cm) {
    // This function is called for all course modules.
    // We don't need to do anything here.
}

/**
 * Fragment API for AJAX operations
 *
 * @param array $args
 * @return string
 */
function local_forum_delete_replies_output_fragment_preview($args) {
    global $DB, $OUTPUT;

    $forumid = $args['forumid'];
    $context = context::instance_by_id($args['contextid']);

    require_capability('local/forum_delete_replies:delete', $context);

    require_once(__DIR__ . '/classes/cleanup_manager.php');
    $manager = new \local_forum_delete_replies\cleanup_manager($forumid);

    $summary = $manager->get_summary();
    $preview = $manager->get_replies_preview(20);

    $html = html_writer::tag('p', get_string('totalreplies', 'local_forum_delete_replies') . ': ' .
        html_writer::tag('strong', $summary['total_replies']));

    if (!empty($preview)) {
        $table = new html_table();
        $table->head = ['Discussion', 'Post', 'Author', 'Date'];
        foreach ($preview as $post) {
            $table->data[] = [
                format_string($post->discussion_name),
                format_string($post->subject),
                fullname($post),
                userdate($post->created, get_string('strftimedatetimeshort', 'langconfig')),
            ];
        }
        $html .= html_writer::table($table);
    }

    return $html;
}
