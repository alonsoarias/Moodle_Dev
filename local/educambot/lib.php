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
 * @package     local_educambot
 * @copyright   2025 EducamBot Team
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Check if the bot is available based on schedule.
 *
 * @return bool True if available, false otherwise.
 */
function local_educambot_is_available() {
    // Check if schedule enforcement is enabled.
    if (!get_config('local_educambot', 'scheduleenabled')) {
        return true; // Schedule not enforced.
    }

    // Use schedule checker if available.
    if (class_exists('\local_educambot\bot\schedule_checker')) {
        return \local_educambot\bot\schedule_checker::is_available();
    }

    return true; // Fallback: always available.
}

/**
 * Inject the chat widget before the footer.
 * This is the standard Moodle callback function.
 */
function local_educambot_before_footer() {
    global $PAGE, $OUTPUT, $USER;

    // Check if widget is enabled.
    if (!get_config('local_educambot', 'widgetenabled')) {
        return;
    }

    // Only inject for logged in users.
    if (!isloggedin() || isguestuser()) {
        return;
    }

    // Check if user has capability.
    $context = context_system::instance();
    if (!has_capability('local/educambot:use', $context)) {
        return;
    }

    // Check schedule availability (v1.8.0).
    if (!local_educambot_is_available()) {
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

    // Prepare data for template.
    $widget = new \local_educambot\output\widget();
    $data = $widget->export_for_template($OUTPUT);

    // Render and output the widget directly.
    echo $OUTPUT->render_from_template('local_educambot/widget', $data);

    // Include JavaScript module.
    $PAGE->requires->js_call_amd('local_educambot/widget', 'init');
}

/**
 * Serves files for the local_educambot plugin.
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
function local_educambot_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload, array $options = []) {
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
    $file = $fs->get_file($context->id, 'local_educambot', $filearea, $itemid, $filepath, $filename);

    if (!$file || $file->is_directory()) {
        return false;
    }

    // Send the file.
    send_stored_file($file, 0, 0, $forcedownload, $options);
}
