<?php
// This file is part of Moodle - http://moodle.org/.
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

use core_courseformat\base as course_format;
use theme_compecer\courseindex\progress_data;
use function get_string;

/**
 * Override the default course index renderer to inject progress metadata.
 *
 * @package   theme_compecer
 * @copyright 2024 IngeWeb
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class section_renderer extends \core_courseformat\output\section_renderer {
    /**
     * Render the course index drawer with the enhanced progress header.
     *
     * @param course_format $format Course format instance.
     * @return string
     */
    public function course_index_drawer(course_format $format): ?string {
        if (!$format->uses_course_index()) {
            return '';
        }

        include_course_editor($format);

        $dataset = progress_data::build($format);
        $context = [
            'progress' => [
                'enabled' => $dataset['enabled'],
                'json' => json_encode($dataset, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
                'coursepercentage' => $dataset['course']['percentageformatted'],
                'coursesummary' => $dataset['enabled'] ? $dataset['course']['summarydisplay'] : '',
                'coursetitle' => get_string('courseindex_progress_heading', 'theme_compecer'),
                'notracking' => $dataset['message']['notracking'],
            ],
        ];

        return $this->render_from_template('core_courseformat/local/courseindex/drawer', $context);
    }
}
