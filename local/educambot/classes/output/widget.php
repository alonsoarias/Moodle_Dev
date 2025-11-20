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
        global $CFG, $USER;

        // Get bot configuration.
        $botname = get_config('local_educambot', 'botname') ?: get_string('botname_default', 'local_educambot');
        $widgetlabel = get_config('local_educambot', 'widgetlabel') ?: get_string('widgetlabel_default', 'local_educambot');
        $primarycolor = get_config('local_educambot', 'primarycolor') ?: '#0f6fc5';
        $greetingtemplate = get_config('local_educambot', 'greetingtemplate') ?: get_string('greeting_default', 'local_educambot');

        // Interpolate greeting message.
        $greetingmessage = $this->interpolate_message($greetingtemplate, $USER, $botname);

        return [
            'botname' => $botname,
            'widgetlabel' => $widgetlabel,
            'primarycolor' => $primarycolor,
            'greetingmessage' => $greetingmessage,
            'serviceurl' => $CFG->wwwroot . '/local/educambot/service.php',
            'sesskey' => sesskey(),
        ];
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
