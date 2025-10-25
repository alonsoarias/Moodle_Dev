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
 * Custom section renderer for the Compecer theme.
 *
 * @package   theme_compecer
 * @copyright 2024 IngeWeb
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace theme_compecer\output\core\courseformat;

use core_courseformat\base as course_format;
use theme_compecer\course_progress_service;

/**
 * Extends the default section renderer to enrich the course index context.
 */
class section_renderer extends \core_courseformat\output\section_renderer {

    /**
     * Render the course index drawer including initial progress metadata.
     *
     * @param course_format $format The active course format.
     * @return string|null
     */
    public function course_index_drawer(course_format $format): ?string {
        global $USER;

        if (!$format->uses_course_index()) {
            return '';
        }

        include_course_editor($format);

        $course = $format->get_course();
        $context = [
            'courseid' => (int)$course->id,
            'progressenabled' => false,
            'progress' => null,
        ];

        if (isloggedin() && !isguestuser() && !empty($course->id)) {
            // Use cache for initial render to improve performance
            $summary = course_progress_service::get_course_progress_summary($course, (int)$USER->id, true);

            if (!empty($summary['hascompletion'])) {
                $context['progressenabled'] = true;
                $context['progress'] = [
                    'percentage' => (int)$summary['percentage'],
                    'percentageformatted' => (string)((int)$summary['percentage']),
                    'completed' => (int)$summary['completed'],
                    'completedformatted' => get_string(
                        'courseprogresscompletedshort',
                        'theme_compecer',
                        $summary['completed']
                    ),
                    'incomplete' => (int)$summary['incomplete'],
                    'incompleteformatted' => get_string(
                        'courseprogressremainingshort',
                        'theme_compecer',
                        $summary['incomplete']
                    ),
                    'total' => (int)$summary['total'],
                    'progresstext' => $summary['progresstext'] ?? '',
                ];
            }
        }

        return $this->render_from_template('core_courseformat/local/courseindex/drawer', $context);
    }
}
