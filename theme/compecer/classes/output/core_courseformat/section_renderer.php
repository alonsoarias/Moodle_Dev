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

namespace theme_compecer\output\core_courseformat;

use completion_info;
use core_courseformat\base as course_format;
use stdClass;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/course/lib.php');
require_once($CFG->libdir . '/completionlib.php');

/**
 * Course format section renderer extensions for the Compecer theme.
 *
 * @package     theme_compecer
 * @copyright   2024 IngeWeb
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class section_renderer extends \core_courseformat\output\section_renderer {
    /**
     * Render the course index drawer with the Compecer layout and progress data.
     *
     * @param course_format $format The course format instance.
     * @return string
     */
    public function course_index_drawer(course_format $format): string {
        if (!$format->uses_course_index()) {
            return '';
        }

        include_course_editor($format);

        global $USER;

        $course = $format->get_course();
        $progress = $this->build_course_progress($course, $USER->id);

        $data = [
            'heading' => get_string('sidebarcoursemenuheading', 'theme_compecer'),
            'progress' => $progress,
        ];

        return $this->render_from_template('core_courseformat/local/courseindex/drawer', $data);
    }

    /**
     * Compile course completion progress information for the current user.
     *
     * @param stdClass $course The course record.
     * @param int $userid The current user id.
     * @return array
     */
    protected function build_course_progress(stdClass $course, int $userid): array {
        $completioninfo = new completion_info($course);

        if (!$completioninfo->is_enabled()) {
            $message = get_string('courseprogressnotracked', 'theme_compecer');
            return [
                'available' => false,
                'percentage' => 0,
                'completed' => 0,
                'total' => 0,
                'summary' => '',
                'message' => $message,
                'arialabel' => $message,
                'iscoursecomplete' => false,
            ];
        }

        $activities = array_filter(
            $completioninfo->get_activities(),
            static function($cm) use ($completioninfo): bool {
                return $completioninfo->is_enabled($cm);
            }
        );

        $total = count($activities);
        $completed = 0;
        foreach ($activities as $cm) {
            $data = $completioninfo->get_data($cm, true, $userid);
            if ($data->completionstate != COMPLETION_INCOMPLETE) {
                $completed++;
            }
        }

        if ($total === 0) {
            $message = get_string('courseprogressnoactivities', 'theme_compecer');
            return [
                'available' => false,
                'percentage' => 0,
                'completed' => 0,
                'total' => 0,
                'summary' => '',
                'message' => $message,
                'arialabel' => $message,
                'iscoursecomplete' => false,
            ];
        }

        $percentage = (int)round(($completed / $total) * 100);
        $summary = get_string('courseprogresssummary', 'theme_compecer', (object) [
            'completed' => $completed,
            'total' => $total,
        ]);
        $arialabel = get_string('courseprogressaria', 'theme_compecer', (object) [
            'percent' => $percentage,
            'summary' => $summary,
        ]);

        return [
            'available' => true,
            'percentage' => $percentage,
            'completed' => $completed,
            'total' => $total,
            'summary' => $summary,
            'message' => '',
            'arialabel' => $arialabel,
            'iscoursecomplete' => $completioninfo->is_course_complete($userid),
        ];
    }
}
