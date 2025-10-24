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
 * Course progress service helpers.
 *
 * @package   theme_compecer
 * @copyright 2024 IngeWeb
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace theme_compecer;

defined('MOODLE_INTERNAL') || die();

require_once($GLOBALS['CFG']->libdir . '/completionlib.php');

use completion_info;
use core_completion\progress;
use stdClass;

/**
 * Small helper around Moodle completion API.
 */
class course_progress_service {
    /**
     * Build progress summary for a given course and user.
     *
     * @param stdClass $course Moodle course record.
     * @param int $userid The user identifier.
     * @return array{
     *     hascompletion: bool,
     *     percentage: int,
     *     total: int,
     *     completed: int,
     *     incomplete: int
     * }
     */
    public static function get_course_progress_summary(stdClass $course, int $userid): array {
        $completion = new completion_info($course);

        if (!$completion->is_enabled()) {
            return [
                'hascompletion' => false,
                'percentage' => 0,
                'total' => 0,
                'completed' => 0,
                'incomplete' => 0,
            ];
        }

        $activities = $completion->get_activities();
        $total = count($activities);
        $completed = 0;

        foreach ($activities as $cm) {
            $data = $completion->get_data($cm, true, $userid);
            if ($data->completionstate != COMPLETION_INCOMPLETE) {
                $completed++;
            }
        }

        $percentage = progress::get_course_progress_percentage($course, $userid);
        $percentage = $percentage === null ? 0 : (int)round($percentage);

        return [
            'hascompletion' => true,
            'percentage' => $percentage,
            'total' => $total,
            'completed' => $completed,
            'incomplete' => max($total - $completed, 0),
        ];
    }
}
