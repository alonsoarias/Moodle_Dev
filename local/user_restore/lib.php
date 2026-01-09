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
 * Library functions for local_user_restore plugin.
 *
 * @package    local_user_restore
 * @copyright  2024 Your Institution
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Pre user delete callback.
 *
 * This function is called BEFORE a user is deleted, allowing us to capture
 * their data for later restoration. This is the primary method for Moodle 3.9-4.x.
 *
 * @param stdClass $user The user object being deleted.
 */
function local_user_restore_pre_user_delete($user) {
    // Check if snapshot is enabled.
    if (!get_config('local_user_restore', 'enablesnapshot')) {
        return;
    }

    try {
        $snapshot = new \local_user_restore\snapshot_manager($user->id);
        $snapshot->capture_all();
    } catch (\Exception $e) {
        debugging('local_user_restore: Failed to capture user data snapshot: ' . $e->getMessage(), DEBUG_DEVELOPER);
    }
}

/**
 * Add navigation nodes to the admin tree.
 *
 * This function is called by Moodle to extend the navigation.
 *
 * @param navigation_node $navigation The navigation node to extend.
 * @param stdClass $course The course object (not used here).
 * @param context $context The context.
 */
function local_user_restore_extend_navigation(global_navigation $navigation) {
    // This function can be used to add navigation items if needed.
}

/**
 * Extend the settings navigation with the user restore link.
 *
 * @param settings_navigation $settingsnav The settings navigation object.
 * @param context $context The context.
 */
function local_user_restore_extend_settings_navigation(settings_navigation $settingsnav, context $context) {
    global $PAGE;

    // Only add to site admin navigation.
    if ($context->contextlevel !== CONTEXT_SYSTEM) {
        return;
    }

    // Check capability.
    if (!has_capability('local/user_restore:view', $context)) {
        return;
    }

    // Find the users node in admin navigation.
    $usersnode = $settingsnav->find('users', navigation_node::TYPE_SETTING);
    if ($usersnode) {
        $url = new moodle_url('/local/user_restore/index.php');
        $usersnode->add(
            get_string('managedeletedusers', 'local_user_restore'),
            $url,
            navigation_node::TYPE_SETTING,
            null,
            'user_restore',
            new pix_icon('i/user', '')
        );
    }
}

/**
 * Fragment API callback to get user details.
 *
 * @param array $args Arguments passed to the fragment.
 * @return string HTML fragment.
 */
function local_user_restore_output_fragment_userdetails($args) {
    global $OUTPUT;

    $userid = $args['userid'] ?? 0;
    if (!$userid) {
        return '';
    }

    $user = \local_user_restore\restore_manager::get_deleted_user($userid);
    if (!$user) {
        return get_string('usernotfound', 'local_user_restore');
    }

    $userinfo = \local_user_restore\restore_manager::get_user_display_info($user);

    $html = '<dl class="row">';
    $html .= '<dt class="col-sm-4">' . get_string('userid', 'local_user_restore') . '</dt>';
    $html .= '<dd class="col-sm-8">' . $user->id . '</dd>';
    $html .= '<dt class="col-sm-4">' . get_string('currentusername', 'local_user_restore') . '</dt>';
    $html .= '<dd class="col-sm-8">' . s($user->username) . '</dd>';
    $html .= '<dt class="col-sm-4">' . get_string('firstname', 'local_user_restore') . '</dt>';
    $html .= '<dd class="col-sm-8">' . s($user->firstname) . '</dd>';
    $html .= '<dt class="col-sm-4">' . get_string('lastname', 'local_user_restore') . '</dt>';
    $html .= '<dd class="col-sm-8">' . s($user->lastname) . '</dd>';
    $html .= '<dt class="col-sm-4">' . get_string('timedeleted', 'local_user_restore') . '</dt>';
    $html .= '<dd class="col-sm-8">' . $userinfo->deletiontime_formatted . '</dd>';
    $html .= '</dl>';

    return $html;
}
