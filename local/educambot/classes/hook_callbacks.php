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
        global $PAGE, $USER;

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
        $excludedpages = ['login', 'admin-cli'];
        foreach ($excludedpages as $excluded) {
            if (strpos($PAGE->pagetype, $excluded) !== false) {
                return;
            }
        }

        // Render the widget.
        $output = $PAGE->get_renderer('core');
        $widget = new \local_educambot\output\widget();
        $widgethtml = $output->render_from_template('local_educambot/widget', $widget->export_for_template($output));

        // Add to footer.
        $hook->add_html($widgethtml);

        // Include JavaScript module.
        $PAGE->requires->js_call_amd('local_educambot/widget', 'init');
    }
}
