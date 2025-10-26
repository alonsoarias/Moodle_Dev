<?php
// This file is part of Moodle - http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
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
 * Course Index Activity Module Output - Enhanced with Traffic Light Indicator
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
if (class_exists('\core_courseformat\output\local\content\cm\courseindex')) {
    /**
     * Course Index Activity Module with Traffic Light Indicator
     *
     * Extends core courseindex cm to add activity state indicator
     */
    class courseindex_cm extends \core_courseformat\output\local\content\cm\courseindex {

        /**
         * Export this data so it can be used as the context for a mustache template.
         *
         * @param renderer_base $output typically, the renderer that's calling this function
         * @return array data context for a mustache template
         */
        public function export_for_template(renderer_base $output): array {
            $data = parent::export_for_template($output);

            // Add activity state for traffic light indicator
            $course = $this->format->get_course();
            $cm = $this->mod;
            $state = courseindex_helper::get_activity_state($cm, $course);

            $data['activitystate'] = $state;
            $data['hasactivitystate'] = !empty($state) && $state !== 'notavailable';
            $data['isstatecompleted'] = $state === 'completed';
            $data['isstateinprogress'] = $state === 'inprogress';
            $data['isstatepending'] = $state === 'pending';
            $data['isstatenotavailable'] = $state === 'notavailable';

            return $data;
        }
    }
} else {
    // Fallback for older Moodle versions - empty class
    class courseindex_cm {
    }
}
