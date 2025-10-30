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
 * Local plugin to extend format_remuiformat for theme_inteb
 *
 * This plugin provides function wrappers and hooks to extend format_remuiformat
 * functionality to show ALL teachers (editing + non-editing) when theme_inteb is active.
 *
 * IMPORTANT: This requires a minimal patch to format_remuiformat/lib.php
 * See README.md for installation instructions.
 *
 * @package   local_inteb_remuiformat_ext
 * @copyright 2025 INTEB
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Wrapper function for getting enrolled teachers context
 * This is called by the patched format_remuiformat if the theme is inteb
 *
 * @param object $course Course object
 * @param bool $frontlineteacher Whether to limit display
 * @return array Teacher context array
 */
function local_inteb_get_enrolled_teachers_context_formate($course, $frontlineteacher = false) {
    // Use the helper class from theme_inteb
    return \theme_inteb\format_remuiformat_helper::get_enrolled_teachers_context($course, $frontlineteacher);
}
