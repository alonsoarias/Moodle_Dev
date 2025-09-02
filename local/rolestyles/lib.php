<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * Role Styles Plugin - Main library functions
 *
 * @package    local_rolestyles
 * @copyright  2024 Alonso Arias <soporte@ingeweb.co> - aulatecnos.es - tecnoszubia.es
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Hook implementation for Moodle 4.0+
 */
function local_rolestyles_before_http_headers($hook = null): void {
    global $PAGE, $CFG;
    local_rolestyles_inject_css();
    if (local_rolestyles_has_selected_role()) {
        require_once($CFG->dirroot . '/local/rolestyles/classes/assign_renderer_factory.php');
        // Register custom renderer factory for this request when a role is active.
        $PAGE->theme->rendererfactory = \local_rolestyles\assign_renderer_factory::class;
    }
}

/**
 * Legacy callback for earlier versions
 */
function local_rolestyles_before_http_headers_callback() {
    local_rolestyles_before_http_headers();
}

/**
 * Main function to inject CSS based on user roles
 */
function local_rolestyles_inject_css() {
    global $USER, $PAGE, $CFG, $COURSE;
    
    // Check if plugin is enabled
    $enabled = get_config('local_rolestyles', 'enabled');
    if (!$enabled) {
        return;
    }
    
    // Check if user is logged in
    if (!isloggedin() || isguestuser()) {
        return;
    }
    
    try {
        // Determine appropriate context
        $context = local_rolestyles_get_context();
        if (!$context) {
            return;
        }
        
        // Get user roles in context
        $userroles = get_user_roles($context, $USER->id, true);
        if (empty($userroles)) {
            return;
        }
        
        // Get selected roles configuration
        $selected_roles = get_config('local_rolestyles', 'selected_roles');
        if (empty($selected_roles)) {
            return;
        }
        
        // Check if user has any selected roles
        $selected_roles_array = explode(',', $selected_roles);
        $role_classes = array();
        $user_role_names = array();
        
        foreach ($userroles as $role) {
            if (in_array($role->roleid, $selected_roles_array)) {
                $role_classes[] = 'role-' . $role->shortname;
                $role_classes[] = 'roleid-' . $role->roleid;
                $user_role_names[] = $role->shortname;
            }
        }
        
        if (empty($role_classes)) {
            return;
        }
        
        // Add CSS classes to body
        foreach ($role_classes as $class) {
            $PAGE->add_body_class($class);
        }

        // Get custom CSS
        $custom_css = get_config('local_rolestyles', 'custom_css');
        if (!empty($custom_css)) {
            // Try renderer factory method
            try {
                require_once($CFG->dirroot . '/local/rolestyles/classes/renderer_factory.php');
                \local_rolestyles\renderer_factory::create_theme_renderer($PAGE->theme->name);
            } catch (Exception $e) {
                // Continue with fallback method
            }
            
            // Fallback: Direct CSS injection
            local_rolestyles_inject_css_direct($custom_css, $user_role_names);
        }
        
    } catch (Exception $e) {
        // Fail silently to avoid breaking the page
        return;
    }
}

/**
 * Get appropriate context for role checking
 * @return context|null
 */
function local_rolestyles_get_context() {
    global $COURSE, $PAGE;
    
    // Priority 1: Course context if in a course
    if (!empty($COURSE->id) && $COURSE->id > 1) {
        return context_course::instance($COURSE->id);
    }
    
    // Priority 2: Page context if available
    if (!empty($PAGE->context) && $PAGE->context->contextlevel >= CONTEXT_COURSE) {
        return $PAGE->context;
    }
    
    // Fallback: System context
    return context_system::instance();
}

/**
 * Determine if the current user has one of the selected roles.
 *
 * @return bool True if a selected role is active
 */
function local_rolestyles_has_selected_role(): bool {
    global $USER;

    // Basic caching to avoid repeated role lookups during a single request.
    static $cached = null;
    if ($cached !== null) {
        return $cached;
    }

    $enabled = get_config('local_rolestyles', 'enabled');
    if (!$enabled || !isloggedin() || isguestuser()) {
        $cached = false;
        return $cached;
    }

    $context = local_rolestyles_get_context();
    if (!$context) {
        $cached = false;
        return $cached;
    }

    $selected = get_config('local_rolestyles', 'selected_roles');
    if (empty($selected)) {
        $cached = false;
        return $cached;
    }

    $selected = explode(',', $selected);
    $userroles = get_user_roles($context, $USER->id, true);
    foreach ($userroles as $role) {
        if (in_array($role->roleid, $selected)) {
            $cached = true;
            return $cached;
        }
    }
    $cached = false;
    return $cached;
}

/**
 * Build a summary string for the current filter state.
 *
 * @param int $total Total number of participants.
 * @param int $visible Number of participants visible after filtering.
 * @return string Localised summary message.
 */
function local_rolestyles_get_filter_summary(int $total, int $visible): string {
    $hidden = max($total - $visible, 0);
    $data = (object) [
        'visible' => $visible,
        'total' => $total,
        'hidden' => $hidden,
    ];
    return get_string('filtersummary', 'local_rolestyles', $data);
}

/**
 * Filter assignment grading table rows to include only submitted and ungraded participants.
 *
 * This helper centralises the logic used by the custom assign renderer and provides
 * a basic in-memory cache to avoid repeating the same database query within a request.
 *
 * @param assign_grading_table $table The grading table instance.
 * @param int $pagesize Number of rows per page.
 * @return array Array containing the filtered rows and the total rows count.
 */
function local_rolestyles_filter_assign_grading(assign_grading_table $table, int $pagesize): array {
    $assignid = $table->assignment->get_instance()->id ?? 0;
    $page = property_exists($table, 'currpage') ? $table->currpage : 0;
    static $cache = [];
    $cachekey = $assignid . ':' . $page;

    if (!isset($cache[$cachekey])) {
        $table->query_db($pagesize, false);
        $rows = $table->rawdata ?? [];
        if (!empty($rows)) {
            $filtered = array_filter($rows, static function($row) {
                return $row->status !== ASSIGN_SUBMISSION_STATUS_NEW && $row->grade === null;
            });
        } else {
            $filtered = [];
        }
        $cache[$cachekey] = ['rows' => $filtered, 'total' => count($rows)];
    } else {
        // Ensure pagination setup when using cached results.
        $table->query_db($pagesize, false);
    }

    return [$cache[$cachekey]['rows'], $cache[$cachekey]['total']];
}

/**
 * Direct CSS injection method
 */
function local_rolestyles_inject_css_direct($css, $role_names) {
    global $PAGE;
    
    $clean_css = preg_replace('/\s+/', ' ', trim($css));
    $escaped_css = addslashes($clean_css);
    $roles_info = implode(', ', $role_names);
    
    $js_code = "
    (function() {
        function injectRoleStyles() {
            var existingStyle = document.getElementById('local-rolestyles-css');
            if (existingStyle) {
                existingStyle.remove();
            }
            
            var style = document.createElement('style');
            style.id = 'local-rolestyles-css';
            style.type = 'text/css';
            style.innerHTML = '/* Role Styles Plugin - Roles: {$roles_info} */\\n{$escaped_css}';
            
            document.head.appendChild(style);
        }
        
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', injectRoleStyles);
        } else {
            injectRoleStyles();
        }
    })();
    ";
    
    $PAGE->requires->js_init_code($js_code);
}

/**
 * Get all system roles for settings
 * @return array
 */
function local_rolestyles_get_all_roles() {
    global $CFG;
    require_once($CFG->libdir . '/accesslib.php');
    
    $roles = role_get_names(null, ROLENAME_ORIGINAL);
    $roles_array = array();
    
    foreach ($roles as $role) {
        $roles_array[$role->id] = $role->localname . ' (' . $role->shortname . ')';
    }
    
    return $roles_array;
}

/**
 * Basic CSS validation
 * @param string $css
 * @return bool
 */
function local_rolestyles_validate_css($css) {
    if (empty($css)) {
        return true;
    }
    
    $dangerous_patterns = array(
        '@import', 'expression(', 'javascript:', 'vbscript:', 'data:', '<script', '</script'
    );
    
    foreach ($dangerous_patterns as $pattern) {
        if (stripos($css, $pattern) !== false) {
            return false;
        }
    }
    
    return true;
}