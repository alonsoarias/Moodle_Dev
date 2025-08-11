<?php
// This file is part of local_downloadcenter for Moodle - http://moodle.org/
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
 * Course selection form for download center.
 *
 * @package       local_downloadcenter
 * @author        ChatGPT
 * @license       http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');
require_once($CFG->dirroot . '/course/lib.php');

class local_downloadcenter_course_select_form extends moodleform {
    public function definition() {
        $mform = $this->_form;

        $options = [];
        $courses = \core_course_category::top()->get_courses([
            'recursive' => true,
            'sort' => ['fullname' => 1],
        ]);
        foreach ($courses as $course) {
            if (!can_access_course($course)) {
                continue;
            }
            $options[$course->id] = $course->get_formatted_name();
        }

        $attributes = ['multiple' => 'multiple', 'size' => 10];
        $mform->addElement('select', 'courseids', get_string('course'), $options, $attributes);
        $mform->setType('courseids', PARAM_INT);

        $buttonarray = [];
        $buttonarray[] = $mform->createElement('submit', 'downloadall',
            get_string('downloadall', 'local_downloadcenter'));
        $buttonarray[] = $mform->createElement('submit', 'submitbutton',
            get_string('selectfiles', 'local_downloadcenter'));
        $mform->addGroup($buttonarray, 'buttons', '', [' '], false);
    }
}
