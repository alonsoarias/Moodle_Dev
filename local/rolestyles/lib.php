<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * Library functions for the Role Styles plugin.
 *
 * @package    local_rolestyles
 * @copyright  2024 Alonso Arias
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Hook executed before HTTP headers are sent.
 * Injects CSS and body classes for users holding configured roles.
 */
function local_rolestyles_hook_before_http_headers($hook = null): void {
    if (!local_rolestyles_user_has_role()) {
        return;
    }

    local_rolestyles_inject_css();
}

/**
 * Early hook to register the custom renderer factory.
 */
function local_rolestyles_after_config(): void {
    global $PAGE, $CFG;

    if (!get_config('local_rolestyles', 'enabled')) {
        return;
    }

    require_once($CFG->dirroot . '/local/rolestyles/classes/assign_renderer_factory.php');
    $PAGE->theme->rendererfactory = \local_rolestyles\assign_renderer_factory::class;
}

/**
 * Determine if the current user has any of the configured roles.
 *
 * @return bool
 */
function local_rolestyles_user_has_role(): bool {
    global $USER;

    if (!get_config('local_rolestyles', 'enabled') || !isloggedin() || isguestuser()) {
        return false;
    }

    $configured = explode(',', (string) get_config('local_rolestyles', 'selected_roles'));
    $configured = array_filter(array_map('intval', $configured));
    if (empty($configured)) {
        return false;
    }

    $context = local_rolestyles_get_context();
    $roles = get_user_roles($context, $USER->id, true);
    foreach ($roles as $role) {
        if (in_array((int) $role->roleid, $configured, true)) {
            return true;
        }
    }
    return false;
}

/**
 * Get context for the current request.
 *
 * @return context
 */
function local_rolestyles_get_context(): context {
    global $PAGE, $COURSE;

    if (!empty($PAGE->context)) {
        return $PAGE->context;
    }
    if (!empty($COURSE->id)) {
        return context_course::instance($COURSE->id);
    }
    return context_system::instance();
}

/**
 * Inject CSS and body classes for active roles.
 */
function local_rolestyles_inject_css(): void {
    global $PAGE;

    $roleids = \local_rolestyles\assign_filter::active_roleids();
    foreach ($roleids as $roleid) {
        $PAGE->add_body_class('roleid-' . $roleid);
    }

    $css = trim((string) get_config('local_rolestyles', 'custom_css'));
    if ($css === '') {
        return;
    }

    $escaped = addslashes($css);
    $js = "(function(){var s=document.getElementById('local-rolestyles-css');" .
        "if(s){s.remove();}s=document.createElement('style');s.id='local-rolestyles-css';" .
        "s.innerHTML='{$escaped}';document.head.appendChild(s);})();";
    $PAGE->requires->js_init_code($js);
}

/**
 * Retrieve system roles for the settings page.
 *
 * @return array
 */
function local_rolestyles_get_all_roles(): array {
    $roles = role_get_names(null, ROLENAME_ORIGINAL);
    $result = [];
    foreach ($roles as $role) {
        $result[$role->id] = $role->localname . ' (' . $role->shortname . ')';
    }
    return $result;
}
