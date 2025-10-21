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
 * Library of functions for local_educambot.
 *
 * @package     local_educambot
 * @copyright   2024 Educam
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Prints the chatbot widget before the footer is rendered.
 *
 * @param moodle_page|null $page
 * @param core_renderer|null $output
 * @return string
 */
function local_educambot_before_footer(?moodle_page $page = null, ?core_renderer $output = null): string {
    global $USER, $PAGE, $OUTPUT;

    if (CLI_SCRIPT || AJAX_SCRIPT) {
        return '';
    }

    $page = $page ?? $PAGE;
    $output = $output ?? $OUTPUT;

    if (!$page instanceof moodle_page || !$output instanceof core_renderer) {
        return '';
    }

    $pageidentifier = null;
    if ($page->url instanceof moodle_url) {
        $pageidentifier = $page->url->out_as_local_url(false);
    }

    $userid = isloggedin() && !isguestuser() ? (int)$USER->id : null;

    $engine = new \local_educambot\bot\engine($userid, $pageidentifier);
    $suggestions = $engine->get_suggestions();

    $renderable = new \local_educambot\output\widget($suggestions, $pageidentifier);

    $page->requires->css('/local/educambot/styles.css');
    $page->requires->js_call_amd('local_educambot/widget', 'init');

    return $output->render($renderable);
}
