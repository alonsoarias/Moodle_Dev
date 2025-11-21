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
 * Hook callbacks for injecting the widget.
 *
 * @package     local_educambot
 * @copyright   2025 EducamBot Team
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_educambot;

defined('MOODLE_INTERNAL') || die();

/**
 * Hook callbacks class.
 */
class hook_callbacks {

    /**
     * Inject widget before footer HTML generation.
     *
     * @param \core\hook\output\before_footer_html_generation $hook
     */
    public static function before_footer_html_generation(\core\hook\output\before_footer_html_generation $hook): void {
        self::inject_chat_widget();
    }

    /**
     * Inject the chat widget into the page.
     */
    private static function inject_chat_widget(): void {
        global $PAGE, $OUTPUT, $USER;

        // Only inject for logged in users.
        if (!isloggedin() || isguestuser()) {
            return;
        }

        // Check if user has capability.
        $context = \context_system::instance();
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
        if (!$PAGE->get_popup_notification_allowed()) {
            return;
        }

        // Prepare data for template.
        $widget = new \local_educambot\output\widget();
        $data = $widget->export_for_template($OUTPUT);

        // Render and output the widget directly.
        echo $OUTPUT->render_from_template('local_educambot/widget', $data);

        // Include JavaScript module.
        $PAGE->requires->js_call_amd('local_educambot/widget', 'init');
    }
}
