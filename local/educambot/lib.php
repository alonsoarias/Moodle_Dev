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
    return local_educambot_render_widget($page, $output);
}

/**
 * Builds the chatbot widget markup.
 *
 * @param moodle_page|null $page
 * @param core_renderer|null $output
 * @return string
 */
function local_educambot_render_widget(?moodle_page $page = null, ?core_renderer $output = null): string {
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

    $renderable = new \local_educambot\output\widget(
        $suggestions,
        $pageidentifier,
        $userid,
        $engine->get_courseid()
    );

    return $output->render($renderable);
}

/**
 * Ensures the chatbot assets are queued before the page header is output.
 *
 * @param moodle_page|null $page
 * @param core_renderer|null $output
 * @return void
 */
function local_educambot_before_standard_html_head(?moodle_page $page = null, ?core_renderer $output = null): void {
    global $PAGE;

    if (CLI_SCRIPT || AJAX_SCRIPT) {
        return;
    }

    $page = $page ?? $PAGE;

    if (!$page instanceof moodle_page) {
        return;
    }

    $page->requires->css('/local/educambot/styles.css');
    $page->requires->js_call_amd('local_educambot/widget', 'init');
}

/**
 * Serves plugin files for local_educambot.
 *
 * @param stdClass $course
 * @param stdClass $cm
 * @param context $context
 * @param string $filearea
 * @param array $args
 * @param bool $forcedownload
 * @param array $options
 * @return bool
 */
function local_educambot_pluginfile($course, $cm, $context, string $filearea, array $args, bool $forcedownload, array $options = []) {
    if ($context->contextlevel !== CONTEXT_SYSTEM || $filearea !== 'response') {
        return false;
    }

    require_login(null, false);
    require_capability('local/educambot:manage', context_system::instance());

    if (empty($args)) {
        return false;
    }

    $itemid = (int)array_shift($args);
    if ($itemid <= 0) {
        return false;
    }

    $filename = array_pop($args);
    $filepath = empty($args) ? '/' : '/' . implode('/', $args) . '/';

    $fs = get_file_storage();
    $file = $fs->get_file($context->id, 'local_educambot', 'response', $itemid, $filepath, $filename);
    if (!$file || $file->is_directory()) {
        return false;
    }

    send_stored_file($file, 0, 0, $forcedownload, $options);
}
