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
 * Strings for component 'format_nexusformat', language 'en'.
 *
 * @package    format_nexusformat
 * @copyright  2024 Nexus Learning
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['pluginname'] = 'Nexus Format';
$string['plugin_description'] = 'A modern course format with a two-column layout inspired by Edutin Academy.';
$string['privacy:metadata'] = 'The Nexus Format plugin does not store any personal data.';

// Section names.
$string['sectionname'] = 'Unit';
$string['section0name'] = 'General';
$string['newsection'] = 'New unit';

// Course display.
$string['currentsection'] = 'Current unit';
$string['hidefromothers'] = 'Hide unit';
$string['showfromothers'] = 'Show unit';

// Page titles.
$string['page-course-view-nexusformat'] = 'Any course main page in Nexus format';
$string['page-course-view-nexusformat-x'] = 'Any course page in Nexus format';

// Sidebar tabs.
$string['tab_content'] = 'Content';
$string['tab_activities'] = 'Activities';
$string['tab_notes'] = 'Notes';

// Progress.
$string['progress'] = 'Progress';
$string['progress_completed'] = '{$a}% completed';
$string['activities_completed'] = '{$a->completed} of {$a->total} activities completed';

// Content sidebar.
$string['unit'] = 'Unit {$a}';
$string['lesson'] = 'Lesson';
$string['expand_unit'] = 'Expand unit';
$string['collapse_unit'] = 'Collapse unit';

// Activities tab.
$string['gradable_activities'] = 'Gradable activities';
$string['no_gradable_activities'] = 'There are no gradable activities in this course.';
$string['status_pending'] = 'Pending';
$string['status_completed'] = 'Completed';
$string['score'] = 'Score';
$string['not_graded'] = 'Not graded';

// Notes tab.
$string['my_notes'] = 'My notes';
$string['add_note'] = 'Add note';
$string['edit_note'] = 'Edit note';
$string['delete_note'] = 'Delete note';
$string['save_note'] = 'Save note';
$string['cancel'] = 'Cancel';
$string['note_title'] = 'Note title (optional)';
$string['note_placeholder'] = 'Write your note here...';
$string['no_notes'] = 'You have not created any notes yet.';
$string['note_saved'] = 'Note saved successfully.';
$string['note_deleted'] = 'Note deleted successfully.';
$string['notenotfound'] = 'Note not found.';
$string['confirm_delete_note'] = 'Are you sure you want to delete this note?';

// Activity content.
$string['select_activity'] = 'Select an activity from the sidebar to view its content.';
$string['activity_not_found'] = 'Activity not found.';
$string['loading'] = 'Loading...';

// Comments section.
$string['comments'] = 'Comments';
$string['add_comment'] = 'Add comment';
$string['reply'] = 'Reply';
$string['replies'] = '{$a} replies';
$string['view_replies'] = 'View {$a} replies';
$string['hide_replies'] = 'Hide replies';
$string['like'] = 'Like';
$string['likes'] = '{$a} likes';
$string['comment_placeholder'] = 'Write your comment...';
$string['post_comment'] = 'Post';
$string['sort_by'] = 'Sort by';
$string['sort_newest'] = 'Newest first';
$string['sort_oldest'] = 'Oldest first';
$string['sort_likes'] = 'Most liked';
$string['participation_banner'] = 'There are classmates who need help with this topic. We invite you to read their questions and provide answers.';
$string['close_banner'] = 'Close';
$string['student'] = 'Student';
$string['teacher'] = 'Teacher';
$string['time_ago'] = '{$a} ago';

// Errors.
$string['error_loading'] = 'Error loading content. Please try again.';
$string['error_saving'] = 'Error saving. Please try again.';
$string['emptycomment'] = 'Comment cannot be empty.';
$string['commentnotfound'] = 'Comment not found.';
$string['comment_saved'] = 'Comment saved successfully.';
$string['comment_deleted'] = 'Comment deleted successfully.';
$string['nopermission'] = 'You do not have permission to perform this action.';
$string['no_comments'] = 'Be the first to comment!';
$string['edit_comment'] = 'Edit';
$string['delete_comment'] = 'Delete';
$string['confirm_delete_comment'] = 'Are you sure you want to delete this comment?';
$string['show_replies'] = 'Show {$a} replies';
$string['write_reply'] = 'Write a reply...';
$string['cancel_reply'] = 'Cancel';
$string['loading_comments'] = 'Loading comments...';
$string['load_more_comments'] = 'Load more comments';

// Activity display.
$string['activity_requires_interaction'] = 'This activity requires interaction. Click the button below to open it.';
$string['activity_requires_fullview'] = 'This activity requires a full view to work properly. Click the button below to open it in a new window.';
$string['activityinfo'] = 'Activity information';
$string['openactivity'] = 'Open activity';

// Settings - Layout.
$string['settings_layout'] = 'Layout settings';
$string['settings_layout_desc'] = 'Configure the layout of the Nexus format.';
$string['settings_contentwidth'] = 'Content area width';
$string['settings_contentwidth_desc'] = 'The width of the main content area as a percentage. The sidebar will take the remaining space.';
$string['settings_containerwidth'] = 'Container width';
$string['settings_containerwidth_desc'] = 'The total width of the Nexus format container as a percentage of the viewport. Use less than 100% to center the content with margins on both sides.';

// Settings - Colors.
$string['settings_colors'] = 'Color settings';
$string['settings_colors_desc'] = 'Customize the colors used in the Nexus format.';
$string['settings_accentcolor'] = 'Primary accent color';
$string['settings_accentcolor_desc'] = 'The main accent color used for active elements, links, and highlights.';
$string['settings_secondarycolor'] = 'Secondary accent color';
$string['settings_secondarycolor_desc'] = 'Secondary color used for gradients (e.g., progress bar, participation banner).';
$string['settings_progresscolor'] = 'Progress bar color';
$string['settings_progresscolor_desc'] = 'The end color of the progress bar gradient.';

// Settings - Features.
$string['settings_features'] = 'Feature settings';
$string['settings_features_desc'] = 'Enable or disable various features of the Nexus format.';
$string['settings_enableactivitiestab'] = 'Enable Activities tab';
$string['settings_enableactivitiestab_desc'] = 'Show the Activities tab in the sidebar with gradable activities list.';
$string['settings_enablenotes'] = 'Enable Notes system';
$string['settings_enablenotes_desc'] = 'Allow students to create personal notes for the course.';
$string['settings_enablecomments'] = 'Enable Comments system';
$string['settings_enablecomments_desc'] = 'Allow students and teachers to comment on activities.';
$string['settings_enableparticipationbanner'] = 'Enable Participation banner';
$string['settings_enableparticipationbanner_desc'] = 'Show a banner encouraging students to help their classmates when there are comments.';

// Settings - Visual.
$string['settings_visual'] = 'Visual settings';
$string['settings_visual_desc'] = 'Customize the visual appearance of the Nexus format.';
$string['settings_cardborderradius'] = 'Card border radius';
$string['settings_cardborderradius_desc'] = 'The roundness of card corners.';
$string['settings_borderradius_none'] = 'None (square)';
$string['settings_borderradius_small'] = 'Small (4px)';
$string['settings_borderradius_medium'] = 'Medium (8px)';
$string['settings_borderradius_large'] = 'Large (12px)';
$string['settings_borderradius_xlarge'] = 'Extra large (16px)';
$string['settings_enablecardshadows'] = 'Enable card shadows';
$string['settings_enablecardshadows_desc'] = 'Add subtle shadows to cards for a more elevated look.';
$string['settings_sidebarposition'] = 'Sidebar position';
$string['settings_sidebarposition_desc'] = 'Choose whether the sidebar appears on the left or right side.';
$string['settings_sidebar_right'] = 'Right';
$string['settings_sidebar_left'] = 'Left';

// Mobile/Responsive.
$string['toggle_sidebar'] = 'Toggle sidebar';
$string['close_sidebar'] = 'Close sidebar';
