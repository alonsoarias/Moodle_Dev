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
 * Library functions for the Assignment No Submission Filter plugin.
 *
 * This file contains the core library functions used throughout the plugin
 * to handle role checking, submission filtering, and configuration validation.
 *
 * @package    local_assign_no_submission_filter
 * @author     IngeWeb
 * @copyright  2026 IngeWeb para TecnosZubia
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/*
 * Define submission status constants if not already defined by mod_assign.
 * These constants match the values used in the assign_submission table.
 */
if (!defined('ASSIGN_SUBMISSION_STATUS_NEW')) {
    /** @var string Status for new/empty submissions */
    define('ASSIGN_SUBMISSION_STATUS_NEW', 'new');
}
if (!defined('ASSIGN_SUBMISSION_STATUS_SUBMITTED')) {
    /** @var string Status for submitted assignments */
    define('ASSIGN_SUBMISSION_STATUS_SUBMITTED', 'submitted');
}
if (!defined('ASSIGN_SUBMISSION_STATUS_DRAFT')) {
    /** @var string Status for draft submissions */
    define('ASSIGN_SUBMISSION_STATUS_DRAFT', 'draft');
}

/**
 * Get all system roles for the plugin configuration page.
 *
 * Retrieves all roles defined in the system, sorted by their display order,
 * for use in the role selection multiselect in plugin settings.
 *
 * @return array Associative array of role id => localized role name.
 */
function local_assign_no_submission_filter_get_all_roles(): array {
    global $DB;

    $roles = $DB->get_records('role', null, 'sortorder ASC');
    $rolesarray = [];

    foreach ($roles as $role) {
        $rolesarray[$role->id] = role_get_name($role);
    }

    return $rolesarray;
}

/**
 * Check if the current user has one of the selected roles for filtering.
 *
 * This function checks both the given context and its parent contexts
 * to determine if the user has any of the roles configured in the plugin
 * settings for seeing filtered assignment views.
 *
 * @param context|null $context The context to check roles in. If null, returns false.
 * @return bool True if the user has one of the selected roles, false otherwise.
 */
function local_assign_no_submission_filter_user_has_selected_role(?context $context = null): bool {
    global $USER;

    // Get selected roles from configuration.
    $selectedroles = get_config('local_assign_no_submission_filter', 'selected_roles');
    if (empty($selectedroles)) {
        return false;
    }

    // Context is required for role checking.
    if ($context === null) {
        return false;
    }

    // Convert comma-separated string to array.
    $selectedrolesarray = explode(',', $selectedroles);
    if (empty($selectedrolesarray)) {
        return false;
    }

    // Check user roles in the current context.
    $userroles = get_user_roles($context, $USER->id, true);
    foreach ($userroles as $role) {
        if (in_array($role->roleid, $selectedrolesarray)) {
            return true;
        }
    }

    // Also check parent contexts for inherited roles.
    $parentcontexts = $context->get_parent_context_ids();
    foreach ($parentcontexts as $parentcontextid) {
        $parentcontext = context::instance_by_id($parentcontextid);
        $userroles = get_user_roles($parentcontext, $USER->id, true);

        foreach ($userroles as $role) {
            if (in_array($role->roleid, $selectedrolesarray)) {
                return true;
            }
        }
    }

    return false;
}

/**
 * Get user IDs who have made submissions for a specific assignment.
 *
 * Retrieves a list of unique user IDs who have submitted work to the
 * specified assignment, excluding empty/new submissions.
 *
 * @param int $assignid The assignment ID to check submissions for.
 * @return array Array of user IDs with valid submissions.
 */
function local_assign_no_submission_filter_get_submitted_users(int $assignid): array {
    global $DB;

    $sql = "SELECT DISTINCT s.userid
            FROM {assign_submission} s
            WHERE s.assignment = :assignid
              AND s.status <> :statusnew
              AND s.latest = 1
              AND s.userid > 0";

    $params = [
        'assignid' => $assignid,
        'statusnew' => ASSIGN_SUBMISSION_STATUS_NEW,
    ];

    return $DB->get_fieldset_sql($sql, $params);
}

/**
 * Determine if the submission filter should be applied.
 *
 * Checks both the plugin enabled status and whether the current user
 * has a role that should see filtered views.
 *
 * @param context|null $context The context to check. Required for role verification.
 * @return bool True if filtering should be applied, false otherwise.
 */
function local_assign_no_submission_filter_should_filter(?context $context = null): bool {
    // Check if plugin is enabled globally.
    if (!get_config('local_assign_no_submission_filter', 'enabled')) {
        return false;
    }

    // Verify user has an appropriate role.
    return local_assign_no_submission_filter_user_has_selected_role($context);
}

/**
 * Determine if the participant count should be hidden.
 *
 * Checks if the hide_participant_count setting is enabled and
 * if the current user has a role that should see hidden counts.
 *
 * @param context|null $context The context to check roles in.
 * @return bool True if participant count should be hidden, false otherwise.
 */
function local_assign_no_submission_filter_should_hide_count(?context $context = null): bool {
    // Check if feature is enabled.
    if (!get_config('local_assign_no_submission_filter', 'hide_participant_count')) {
        return false;
    }

    // Verify user has an appropriate role.
    return local_assign_no_submission_filter_user_has_selected_role($context);
}

/**
 * Determine if the submitted count should be hidden.
 *
 * Checks if the hide_submitted_count setting is enabled and
 * if the current user has a role that should see hidden counts.
 *
 * @param context|null $context The context to check roles in.
 * @return bool True if submitted count should be hidden, false otherwise.
 */
function local_assign_no_submission_filter_should_hide_submitted_count(?context $context = null): bool {
    // Check if feature is enabled.
    if (!get_config('local_assign_no_submission_filter', 'hide_submitted_count')) {
        return false;
    }

    // Verify user has an appropriate role.
    return local_assign_no_submission_filter_user_has_selected_role($context);
}
