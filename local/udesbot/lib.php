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
 * Plugin library functions.
 *
 * @package     local_udesbot
 * @author      Alonso Arias <soporte@orioncloud.com.co>
 * @copyright   2025 OrionCloud<https://orioncloud.com.co>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Check if the bot is available.
 *
 * @return bool True if available, false otherwise.
 */
function local_udesbot_is_available() {
    // Bot is always available (schedule feature removed in v3.5.0).
    return true;
}

/**
 * Inject the chat widget before the footer.
 * This is the standard Moodle callback function.
 */
function local_udesbot_before_footer() {
    global $PAGE, $OUTPUT, $USER;

    // Check if widget is enabled.
    if (!get_config('local_udesbot', 'widgetenabled')) {
        return;
    }

    // Only inject for logged in users.
    if (!isloggedin() || isguestuser()) {
        return;
    }

    // Check if user has capability.
    $context = context_system::instance();
    if (!has_capability('local/udesbot:use', $context)) {
        return;
    }

    // Check schedule availability (v1.8.0).
    if (!local_udesbot_is_available()) {
        return;
    }

    // Don't inject on certain pages.
    if (isset($_SERVER['REQUEST_URI'])) {
        $excludedpaths = ['/login/', '/admin/cli/'];
        foreach ($excludedpaths as $excluded) {
            if (strpos($_SERVER['REQUEST_URI'], $excluded) !== false) {
                return;
            }
        }
    }

    // Don't inject in embedded layout.
    if (strpos($PAGE->pagetype, 'embedded') !== false) {
        return;
    }

    // Don't inject if notifications are not allowed.
    try {
        if (!$PAGE->get_popup_notification_allowed()) {
            return;
        }
    } catch (Exception $e) {
        // Ignore if method not available.
    }

    // Output Bootstrap Icons CSS directly (v2.2.11).
    // Cannot use $PAGE->requires->css() because head is already printed.
    echo '<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">';

    // Prepare data for template.
    $widget = new \local_udesbot\output\widget();
    $data = $widget->export_for_template($OUTPUT);

    // Render and output the widget directly.
    echo $OUTPUT->render_from_template('local_udesbot/widget', $data);

    // Load JavaScript strings for the widget (v2.0.2).
    $PAGE->requires->strings_for_js([
        // Error messages.
        'error_noresponse',
        'error_connection',
        // Export strings.
        'export_header',
        'export_datetime',
        'export_you',
        'export_footer',
        'export_filename',
        // Feedback strings.
        'feedback_helpful',
        'feedback_nothelpful',
        'feedback_thanks',
        // Inactivity strings.
        'inactivity_warning',
        'keepchatopen',
        // Mascot strings.
        'mascot_greeting',
        'mascot_greeting_student',
        'mascot_greeting_teacher',
        'mascot_greeting_editingteacher',
        'mascot_greeting_coursecreator',
        'mascot_greeting_manager',
        'mascot_greeting_guest',
        'mascot_greeting_user',
        'mascot_needmore',
        'mascot_tryagain',
        'mascot_nopopular',
        'mascot_error',
        'mascot_popularheader',
        'mascot_similarheader',
        // Mascot suggestions.
        'mascot_suggest_activities',
        'mascot_suggest_admin',
        'mascot_suggest_attendance',
        'mascot_suggest_browse',
        'mascot_suggest_calendar',
        'mascot_suggest_categories',
        'mascot_suggest_course',
        'mascot_suggest_courses',
        'mascot_suggest_deadlines',
        'mascot_suggest_grades',
        'mascot_suggest_grading',
        'mascot_suggest_help',
        'mascot_suggest_login',
        'mascot_suggest_newcourse',
        'mascot_suggest_profile',
        'mascot_suggest_reports',
        'mascot_suggest_settings',
        'mascot_suggest_students',
        'mascot_suggest_tasks',
        'mascot_suggest_templates',
        'mascot_suggest_users',
        // History.
        'previousconversation',
        // Shortcuts (v2.2.2).
        'shortcuts_title',
    ], 'local_udesbot');

    // Include JavaScript module.
    $PAGE->requires->js_call_amd('local_udesbot/widget', 'init');
}

/**
 * Serves files for the local_udesbot plugin.
 *
 * @param stdClass $course The course object.
 * @param stdClass $cm The course module object (not used).
 * @param context $context The context.
 * @param string $filearea The file area.
 * @param array $args Extra arguments.
 * @param bool $forcedownload Whether to force download.
 * @param array $options Additional options.
 * @return bool|void False if file not found.
 */
function local_udesbot_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload, array $options = []) {
    // Check context is system level (where theme files are stored).
    if ($context->contextlevel != CONTEXT_SYSTEM) {
        return false;
    }

    // Check the file area is valid.
    $validareas = ['widgeticon', 'mascot'];
    if (!in_array($filearea, $validareas)) {
        return false;
    }

    // Get the item ID (theme ID) and file path.
    $itemid = array_shift($args);
    $filename = array_pop($args);
    $filepath = $args ? '/' . implode('/', $args) . '/' : '/';

    // Get file storage.
    $fs = get_file_storage();
    $file = $fs->get_file($context->id, 'local_udesbot', $filearea, $itemid, $filepath, $filename);

    if (!$file || $file->is_directory()) {
        return false;
    }

    // Send the file.
    send_stored_file($file, 0, 0, $forcedownload, $options);
}
