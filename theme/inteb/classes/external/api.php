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
 * Theme external services - Extended for theme_inteb
 *
 * This class extends theme_remui's external API to override specific webservices
 * with enhanced versions that include:
 * - RemUI custom fields (edwcourseduration, edwskilllevel)
 * - All instructors (not just editing teachers)
 *
 * @package   theme_inteb
 * @copyright (c) 2025 IngeWeb <soporte@ingeweb.co>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace theme_inteb\external;

defined('MOODLE_INTERNAL') || die;

require_once($CFG->libdir . "/externallib.php");
require_once($CFG->libdir . "/filelib.php");
require_once($CFG->dirroot . "/theme/remui/lib.php");

use external_api;

// Import RemUI's external traits (we'll use most of them as-is)
use theme_remui\external\handle_bug_feedback_report;
use theme_remui\external\get_msg_contact_list_count;
use theme_remui\external\get_login_user_detail;
use theme_remui\external\get_course_stats;
// NOTE: NOT importing get_myoverviewcourses from remui - using our own below
use theme_remui\external\enrol_get_course_content;
use theme_remui\external\enrol_get_course_instructors;
use theme_remui\external\get_courses;
use theme_remui\external\get_tags;
use theme_remui\external\save_user_profile_settings;
use theme_remui\external\get_dashboard_stats;
use theme_remui\external\set_block_pos;
use theme_remui\external\enroll_page_action;
use theme_remui\external\change_frontpagechooser;
use theme_remui\external\do_setup_action;
use theme_remui\external\do_feedbackcollection_action;

/**
 * Uses Moodle webservices trait - Extended for theme_inteb
 *
 * This class uses most of RemUI's traits as-is, but overrides:
 * - get_myoverviewcourses: To include custom fields and all instructors
 *
 * @copyright (c) 2025 IngeWeb <soporte@ingeweb.co>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class api extends external_api {
    // Use RemUI's traits as-is
    use handle_bug_feedback_report;
    use get_msg_contact_list_count;
    use get_login_user_detail;
    use get_course_stats;
    use enrol_get_course_content;
    use enrol_get_course_instructors;
    use get_courses;
    use get_tags;
    use save_user_profile_settings;
    use get_dashboard_stats;
    use set_block_pos;
    use enroll_page_action;
    use change_frontpagechooser;
    use do_setup_action;
    use do_feedbackcollection_action;

    // OVERRIDE: Use inteb's enhanced version instead of remui's
    use \theme_inteb\external\get_myoverviewcourses;
}
