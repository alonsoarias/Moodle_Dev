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
 * Library functions for local_assign_no_submission_filter
 *
 * @package    local_assign_no_submission_filter
 * @copyright  2024 Your Organization
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

// Constants
if (!defined('ASSIGN_SUBMISSION_STATUS_NEW')) {
    define('ASSIGN_SUBMISSION_STATUS_NEW', 'new');
}
if (!defined('ASSIGN_SUBMISSION_STATUS_SUBMITTED')) {
    define('ASSIGN_SUBMISSION_STATUS_SUBMITTED', 'submitted');
}
if (!defined('ASSIGN_SUBMISSION_STATUS_DRAFT')) {
    define('ASSIGN_SUBMISSION_STATUS_DRAFT', 'draft');
}

/**
 * Get all system roles for configuration
 *
 * @return array Array of role id => role name
 */
function local_assign_no_submission_filter_get_all_roles() {
    global $DB;
    
    $roles = $DB->get_records('role', null, 'sortorder ASC');
    $roles_array = array();
    
    foreach ($roles as $role) {
        $roles_array[$role->id] = role_get_name($role);
    }
    
    return $roles_array;
}

/**
 * Check if current user has one of the selected roles
 *
 * @param context $context The context to check
 * @return bool True if user has one of the selected roles
 */
function local_assign_no_submission_filter_user_has_selected_role($context = null) {
    global $USER, $DB;
    
    // Get selected roles configuration
    $selected_roles = get_config('local_assign_no_submission_filter', 'selected_roles');
    if (empty($selected_roles)) {
        return false;
    }
    
    // If no context provided, we can't check
    if (!$context) {
        return false;
    }
    
    // Convert string to array
    $selected_roles_array = explode(',', $selected_roles);
    if (empty($selected_roles_array)) {
        return false;
    }
    
    // Get user roles in this context
    $userroles = get_user_roles($context, $USER->id, true);
    
    // Check if user has any of the selected roles
    foreach ($userroles as $role) {
        if (in_array($role->roleid, $selected_roles_array)) {
            return true;
        }
    }
    
    // Also check parent contexts
    $parentcontexts = $context->get_parent_context_ids();
    foreach ($parentcontexts as $parentcontextid) {
        $parentcontext = context::instance_by_id($parentcontextid);
        $userroles = get_user_roles($parentcontext, $USER->id, true);
        
        foreach ($userroles as $role) {
            if (in_array($role->roleid, $selected_roles_array)) {
                return true;
            }
        }
    }
    
    return false;
}

/**
 * Get users who have made submissions for an assignment
 *
 * @param int $assignid Assignment ID
 * @return array Array of user IDs
 */
function local_assign_no_submission_filter_get_submitted_users($assignid) {
    global $DB;
    
    // Get users with actual submissions (not 'new' status)
    $sql = "SELECT DISTINCT s.userid
            FROM {assign_submission} s
            WHERE s.assignment = :assignid
              AND s.status <> :statusnew
              AND s.latest = 1
              AND s.userid > 0";
    
    $params = [
        'assignid' => $assignid,
        'statusnew' => ASSIGN_SUBMISSION_STATUS_NEW
    ];
    
    return $DB->get_fieldset_sql($sql, $params);
}

/**
 * Check if filter should be applied
 *
 * @param context $context The context to check
 * @return bool
 */
function local_assign_no_submission_filter_should_filter($context = null) {
    // First check if plugin is enabled
    if (!get_config('local_assign_no_submission_filter', 'enabled')) {
        return false;
    }
    
    // Then check if user has selected role
    return local_assign_no_submission_filter_user_has_selected_role($context);
}

/**
 * Check if we should hide participant count
 *
 * @param context $context The context to check
 * @return bool
 */
function local_assign_no_submission_filter_should_hide_count($context = null) {
    // First check if feature is enabled
    if (!get_config('local_assign_no_submission_filter', 'hide_participant_count')) {
        return false;
    }
    
    // Then check if user has selected role
    return local_assign_no_submission_filter_user_has_selected_role($context);
}