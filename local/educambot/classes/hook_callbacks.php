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

namespace local_educambot;

use core\hook\output\before_footer_html_generation;

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/../lib.php');

/**
 * Hook callbacks for local_educambot.
 *
 * @package     local_educambot
 * @copyright   2024 Educam
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class hook_callbacks {
    /**
     * Inject the chatbot widget before the footer is printed.
     *
     * @param before_footer_html_generation $hook
     * @return void
     */
    public static function before_footer_html_generation(before_footer_html_generation $hook): void {
        global $PAGE;

        if (CLI_SCRIPT || AJAX_SCRIPT) {
            return;
        }

        $renderer = $hook->renderer;
        $page = null;
        if ($renderer instanceof \core_renderer) {
            $page = $renderer->page;
        } else if (isset($GLOBALS['PAGE']) && $GLOBALS['PAGE'] instanceof \moodle_page) {
            $page = $GLOBALS['PAGE'];
        }

        if (!$page instanceof \moodle_page) {
            return;
        }

        // Load CSS and JavaScript assets (similar to local_geniai).
        $page->requires->css('/local/educambot/styles.css');
        $page->requires->js_call_amd('local_educambot/widget', 'init');

        $content = local_educambot_render_widget($page, $renderer instanceof \core_renderer ? $renderer : null);
        if ($content !== '') {
            $hook->add_html($content);
        }
    }
}
