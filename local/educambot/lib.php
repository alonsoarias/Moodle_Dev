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
