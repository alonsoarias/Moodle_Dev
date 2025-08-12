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

class local_downloadcenter_course_select_form extends moodleform {
    public function definition() {
        $mform = $this->_form;
        $courses = $this->_customdata['courses'] ?? [];
        $selection = $this->_customdata['selection'] ?? [];
        $catid = $this->_customdata['catid'] ?? 0;

        foreach ($courses as $course) {
            if (!$course->can_access()) {
                continue;
            }
            $url = new moodle_url('/local/downloadcenter/index.php', ['catid' => $catid, 'courseid' => $course->id]);
            $label = html_writer::link($url, $course->get_formatted_name());
            if (isset($selection[$course->id])) {
                $label .= ' (' . get_string('selected', 'local_downloadcenter') . ')';
            }
            $mform->addElement('advcheckbox', 'courses[' . $course->id . ']', '', $label, ['group' => 1]);
        }

        $mform->addElement('hidden', 'catid', $catid);
        $mform->setType('catid', PARAM_INT);

        $this->add_action_buttons(false, get_string('addcoursestoselection', 'local_downloadcenter'));
    }
}
