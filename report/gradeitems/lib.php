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
 * Library functions for the Grade Items Report plugin.
 *
 * @package    report_gradeitems
 * @copyright  2026 Alonso Arias <soporte@orioncloud.com.co>
 * @author     Alonso Arias <soporte@orioncloud.com.co>
 * @link       https://orioncloud.com.co
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

/**
 * Add nodes to the admin navigation tree.
 *
 * @param navigation_node $navigation The navigation node to extend
 * @param stdClass $course The course to object for the report
 * @param context $context The context of the course
 * @return void
 */
function report_gradeitems_extend_navigation_course($navigation, $course, $context) {
    // This report is site-wide, not course-specific.
}

/**
 * Add link to the reports in the admin tree.
 *
 * @param navigation_node $navigation The navigation node
 * @param stdClass $user The user object
 * @param context $context The context
 * @return void
 */
function report_gradeitems_extend_navigation_user($navigation, $user, $context) {
    // This report is site-wide, not user-specific.
}

/**
 * This function extends the navigation with the report items.
 *
 * @param navigation_node $navigation The navigation node to extend
 * @param stdClass $course The course to object for the report
 * @param stdClass $context The context of the course
 * @return void
 */
function report_gradeitems_extend_navigation($navigation, $course, $context) {
    // This report is site-wide, navigation is handled via settings.
}

/**
 * Add the report to the admin navigation menu.
 *
 * @param settings_navigation $nav
 * @param context $context
 * @return void
 */
function report_gradeitems_extend_settings_navigation(settings_navigation $nav, context $context) {
    // This function is kept for compatibility but main access is via Site administration > Reports.
}
