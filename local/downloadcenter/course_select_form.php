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
 * @license       http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');
require_once(__DIR__ . '/locallib.php');

class local_downloadcenter_course_select_form extends moodleform {
    public function definition() {
        $mform = $this->_form;
        $courses = $this->_customdata['courses'] ?? [];
        $selection = $this->_customdata['selection'] ?? [];
        $catids = $this->_customdata['catids'] ?? [];
        $categoryid = $this->_customdata['categoryid'] ?? 0;

        foreach ($courses as $course) {
            // Use the correct method for core_course_list_element objects
            if (!$course->can_access()) {
                continue;
            }
            
            $url = local_downloadcenter_build_url($catids, ['courseid' => $course->id]);
            $label = html_writer::link($url, $course->get_formatted_name());
            
            // Check selection state
            if (isset($selection[$course->id])) {
                $selectionData = $selection[$course->id];
                if (isset($selectionData['downloadall']) && $selectionData['downloadall']) {
                    $label .= ' <span class="badge badge-success">' . 
                             get_string('selected', 'local_downloadcenter') . '</span>';
                } else {
                    // Check if it's a partial selection
                    $hasSelection = false;
                    foreach ($selectionData as $key => $value) {
                        if (strpos($key, 'item_') === 0 && $value) {
                            $hasSelection = true;
                            break;
                        }
                    }
                    if ($hasSelection) {
                        $label .= ' <span class="badge badge-info">' . 
                                 get_string('selected', 'local_downloadcenter') . ' (partial)</span>';
                    }
                }
            }
            
            $checkboxname = 'courses[' . $course->id . ']';
            $attrs = [
                'group' => 1,
                'class' => 'course-checkbox',
                'data-courseid' => $course->id
            ];
            
            $mform->addElement('advcheckbox', $checkboxname, '', $label, $attrs);
            $mform->setType($checkboxname, PARAM_BOOL);
            
            if (isset($selection[$course->id])) {
                $mform->setDefault($checkboxname, 1);
                // Set indeterminate state for partial selections
                if (empty($selection[$course->id]['downloadall'])) {
                    $hasItems = false;
                    foreach ($selection[$course->id] as $key => $value) {
                        if (strpos($key, 'item_') === 0 && $value) {
                            $hasItems = true;
                            break;
                        }
                    }
                    if ($hasItems) {
                        $mform->updateElementAttr($checkboxname, ['data-indeterminate' => 1]);
                    }
                }
            }
        }
        
        // Only add checkbox controller if there are courses
        if (!empty($courses)) {
            $this->add_checkbox_controller(1);
        }
        
        // Hidden fields for navigation
        if (!empty($catids)) {
            $mform->addElement('hidden', 'catids', implode(',', $catids));
            $mform->setType('catids', PARAM_SEQUENCE);
        }
        
        if ($categoryid) {
            $mform->addElement('hidden', 'categoryid', $categoryid);
            $mform->setType('categoryid', PARAM_INT);
        }
        
        $this->add_action_buttons(false, get_string('addcoursestoselection', 'local_downloadcenter'));
    }
    
    /**
     * Custom validation for the form
     *
     * @param array $data Array of data submitted
     * @param array $files Array of files submitted
     * @return array Array of errors
     */
    public function validation($data, $files) {
        $errors = parent::validation($data, $files);
        
        // No additional validation needed for now
        
        return $errors;
    }
}