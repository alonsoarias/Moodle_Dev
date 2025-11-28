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
 * Widget renderable class.
 *
 * @package     local_educambot
 * @copyright   2025 EducamBot Team
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_educambot\output;

defined('MOODLE_INTERNAL') || die();

use renderable;
use templatable;
use renderer_base;

/**
 * Widget class.
 */
class widget implements renderable, templatable {

    /**
     * Export data for template.
     *
     * @param renderer_base $output
     * @return array
     */
    public function export_for_template(renderer_base $output) {
        global $CFG, $USER, $COURSE, $DB;

        // Get bot configuration.
        $botname = get_config('local_educambot', 'botname') ?: get_string('botname_default', 'local_educambot');
        $widgetlabel = get_config('local_educambot', 'widgetlabel') ?: get_string('widgetlabel_default', 'local_educambot');
        $greetingtemplate = get_config('local_educambot', 'greetingtemplate') ?: get_string('greeting_default', 'local_educambot');

        // Get selected theme (v1.8.0).
        $theme = $this->get_current_theme();

        // Interpolate greeting message.
        $greetingmessage = $this->interpolate_message($greetingtemplate, $USER, $botname);

        // Get current course ID for context.
        $courseid = isset($COURSE->id) ? $COURSE->id : SITEID;

        return [
            'botname' => $botname,
            'widgetlabel' => $widgetlabel,
            'primarycolor' => $theme->primarycolor,
            'secondarycolor' => $theme->secondarycolor,
            'textcolor' => $theme->textcolor,
            'backgroundcolor' => $theme->backgroundcolor,
            'usercolor' => $theme->usercolor,
            'botcolor' => $theme->botcolor,
            'greetingmessage' => $greetingmessage,
            'serviceurl' => $CFG->wwwroot . '/local/educambot/service.php',
            'startupurl' => $CFG->wwwroot . '/local/educambot/startup.php',
            'sesskey' => sesskey(),
            'courseid' => $courseid,
        ];
    }

    /**
     * Get the current active theme.
     *
     * @return object Theme object with color properties.
     */
    private function get_current_theme() {
        global $DB;

        // Check if theme table exists (for upgrades).
        $dbman = $DB->get_manager();
        $table = new \xmldb_table('local_educambot_theme');

        if ($dbman->table_exists($table)) {
            // Try to get default theme.
            $theme = $DB->get_record('local_educambot_theme', ['isdefault' => 1]);
            if ($theme) {
                return $theme;
            }

            // Fallback to first theme.
            $theme = $DB->get_record('local_educambot_theme', [], '*', IGNORE_MULTIPLE);
            if ($theme) {
                return $theme;
            }
        }

        // Return default colors if no theme found (pre-v1.8.0 or fresh install).
        $default = new \stdClass();
        $default->primarycolor = get_config('local_educambot', 'primarycolor') ?: '#0f6fc5';
        $default->secondarycolor = '#084a8a';
        $default->textcolor = '#1f2937';
        $default->backgroundcolor = '#f9fafb';
        $default->usercolor = $default->primarycolor;
        $default->botcolor = '#ffffff';

        return $default;
    }

    /**
     * Interpolate message with user variables.
     *
     * @param string $message Message template
     * @param object $user User object
     * @param string $botname Bot name
     * @return string Interpolated message
     */
    private function interpolate_message($message, $user, $botname) {
        $replacements = [
            '{{userfirstname}}' => $user->firstname ?? '',
            '{{userlastname}}' => $user->lastname ?? '',
            '{{botname}}' => $botname,
        ];

        return str_replace(array_keys($replacements), array_values($replacements), $message);
    }
}
