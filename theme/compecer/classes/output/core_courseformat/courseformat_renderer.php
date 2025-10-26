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
 * Course format renderer for theme_compecer
 *
 * @package    theme_compecer
 * @copyright  2025 IngeWeb https://www.ingeweb.co
 * @author     Pedro Arias <soporte@ingeweb.co>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace theme_compecer\output\core_courseformat;

use theme_compecer\courseindex_helper;
use core_courseformat\output\local\courseindex\drawer as base_drawer;
use core_courseformat\output\local\courseindex\section as base_section;
use core_courseformat\output\local\courseindex\cm as base_cm;
use renderer_base;

defined('MOODLE_INTERNAL') || die();

/**
 * Course format renderer with enhanced courseindex progress features
 *
 * This renderer extends the core courseindex to add:
 * - Global course progress display
 * - Per-section progress display
 * - Traffic light indicators for activities
 *
 * @package    theme_compecer
 * @copyright  2025 IngeWeb
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class courseformat_renderer extends \core_courseformat\output\renderer {

    /**
     * Render the courseindex drawer with global progress
     *
     * @param base_drawer $drawer The drawer renderable
     * @return string HTML output
     */
    public function render_drawer(base_drawer $drawer) {
        $data = $drawer->export_for_template($this);

        // Add global course progress.
        $course = $drawer->get_course();
        $progress = courseindex_helper::get_course_progress($course);

        $data['courseprogress'] = $progress;
        $data['hasprogress'] = $progress['enabled'] && $progress['percentage'] > 0;
        $data['progresspercentage'] = $progress['percentage'];
        $data['progresswidth'] = $progress['percentage'];

        return $this->render_from_template('core_courseformat/local/courseindex/drawer', $data);
    }

    /**
     * Render a section with progress information
     *
     * @param base_section $section The section renderable
     * @return string HTML output
     */
    public function render_section(base_section $section) {
        $data = $section->export_for_template($this);

        // Add section progress.
        $course = $section->get_course();
        $sectionobject = $section->get_section();
        $progress = courseindex_helper::get_section_progress($sectionobject, $course);

        $data['sectionprogress'] = $progress;
        $data['hassectionprogress'] = $progress['enabled'] && $progress['total'] > 0;
        $data['sectionprogresspercentage'] = $progress['percentage'];
        $data['sectionprogresswidth'] = $progress['percentage'];
        $data['sectionprogresstext'] = courseindex_helper::get_progress_text(
            $progress['percentage'],
            $progress['completed'],
            $progress['total']
        );

        return $this->render_from_template('core_courseformat/local/courseindex/section', $data);
    }

    /**
     * Render a course module with activity state indicator
     *
     * @param base_cm $cm The course module renderable
     * @return string HTML output
     */
    public function render_cm(base_cm $cm) {
        $data = $cm->export_for_template($this);

        // Add activity state for traffic light indicator.
        $course = $cm->get_course();
        $cmobject = $cm->get_cm();
        $state = courseindex_helper::get_activity_state($cmobject, $course);

        $data['activitystate'] = $state;
        $data['hasactivitystate'] = !empty($state) && $state !== 'notavailable';
        $data['isstatecompleted'] = $state === 'completed';
        $data['isstateinprogress'] = $state === 'inprogress';
        $data['isstatepending'] = $state === 'pending';
        $data['isstatenotavailable'] = $state === 'notavailable';

        return $this->render_from_template('core_courseformat/local/courseindex/cm', $data);
    }
}
