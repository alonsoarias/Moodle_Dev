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
 * Course Index Drawer Output - Enhanced with Global Progress
 *
 * @package    theme_compecer
 * @copyright  2025 IngeWeb https://www.ingeweb.co
 * @author     Pedro Arias <soporte@ingeweb.co>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace theme_compecer\output\courseformat;

use theme_compecer\courseindex_helper;
use renderer_base;

defined('MOODLE_INTERNAL') || die();

// Check if the parent class exists (Moodle 4.0+)
if (class_exists('\core_courseformat\output\local\content\courseindex')) {
    /**
     * Course Index Drawer with Global Progress
     *
     * Extends core courseindex to add global course progress bar
     */
    class courseindex_drawer extends \core_courseformat\output\local\content\courseindex {

        /**
         * Export this data so it can be used as the context for a mustache template.
         *
         * @param renderer_base $output typically, the renderer that's calling this function
         * @return array data context for a mustache template
         */
        public function export_for_template(renderer_base $output): array {
            $data = parent::export_for_template($output);

            // Add global course progress
            $course = $this->format->get_course();
            $progress = courseindex_helper::get_course_progress($course);

            $data['courseid'] = $course->id;
            $data['courseprogress'] = $progress;
            $data['hasprogress'] = $progress['enabled'] && $progress['percentage'] > 0;
            $data['progresspercentage'] = $progress['percentage'];
            $data['progresswidth'] = $progress['percentage'];

            return $data;
        }
    }
} else {
    // Fallback for older Moodle versions - empty class
    class courseindex_drawer {
    }
}
