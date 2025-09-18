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
 * Admin selection form for multiple courses
 *
 * @package    local_downloadcenter
 * @copyright  2025 Alonso Arias <soporte@ingeweb.co>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_downloadcenter\forms;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');

/**
 * Form for admin course selection and options.
 *
 * @package    local_downloadcenter
 * @copyright  2025 Alonso Arias <soporte@ingeweb.co>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class admin_selection_form extends \moodleform {
    
    /**
     * Define the form.
     *
     * @return void
     */
    public function definition() {
        global $DB, $OUTPUT;
        
        $mform = $this->_form;
        
        $categories = $this->_customdata['categories'] ?? [];
        $selected = $this->_customdata['selected'] ?? [];
        $options = $this->_customdata['options'] ?? [];
        
        // Category selector header.
        $mform->addElement('header', 'categoryselector', 
                          get_string('selectcategory', 'local_downloadcenter'));
        
        // Category dropdown.
        $mform->addElement('select', 'categoryid', 
                          get_string('category'), $categories);
        $mform->setType('categoryid', PARAM_INT);
        
        // Load courses button.
        $mform->addElement('submit', 'loadcourses', 
                          get_string('loadcourses', 'local_downloadcenter'));
        
        // Course selection.
        $categoryid = optional_param('categoryid', 0, PARAM_INT);
        if ($categoryid) {
            $mform->addElement('header', 'courseselection', 
                              get_string('selectcourses', 'local_downloadcenter'));
            
            // Get courses in category.
            $category = \core_course_category::get($categoryid);
            $courses = $category->get_courses(['recursive' => true, 'sort' => ['fullname' => 1]]);
            
            if (!empty($courses)) {
                // Add select all/none buttons using correct Moodle strings.
                $mform->addElement('html', '<div class="form-group">');
                $mform->addElement('html', 
                    '<button type="button" id="selectall" class="btn btn-secondary btn-sm mr-2">' . 
                    get_string('selectall') . '</button>');
                $mform->addElement('html', 
                    '<button type="button" id="selectnone" class="btn btn-secondary btn-sm">' . 
                    get_string('deselectall') . '</button>'); // Cambiado de 'selectnone' a 'deselectall'
                $mform->addElement('html', '</div>');
                
                // Course checkboxes.
                foreach ($courses as $course) {
                    // Fix: Convert core_course_list_element to stdClass for can_access_course()
                    $courserecord = $DB->get_record('course', ['id' => $course->id]);
                    if (!can_access_course($courserecord)) {
                        continue;
                    }
                    
                    $coursename = format_string($course->fullname) . ' (' . 
                                 format_string($course->shortname) . ')';
                    
                    if (!$course->visible) {
                        $coursename .= ' ' . \html_writer::tag('span', 
                            get_string('hidden'),
                            ['class' => 'badge badge-warning']
                        );
                    }
                    
                    $elementname = 'courses[' . $course->id . ']';
                    
                    $mform->addElement('advcheckbox', $elementname, '', $coursename);
                    $mform->setType($elementname, PARAM_BOOL);
                    
                    if (in_array($course->id, $selected)) {
                        $mform->setDefault($elementname, 1);
                    }
                }
                
                // JavaScript for select all/none.
                $mform->addElement('html', '
                <script>
                document.getElementById("selectall").addEventListener("click", function() {
                    var checkboxes = document.querySelectorAll(\'input[name^="courses["]\');
                    checkboxes.forEach(function(cb) { cb.checked = true; });
                });
                document.getElementById("selectnone").addEventListener("click", function() {
                    var checkboxes = document.querySelectorAll(\'input[name^="courses["]\');
                    checkboxes.forEach(function(cb) { cb.checked = false; });
                });
                </script>');
            } else {
                $mform->addElement('html', 
                    $OUTPUT->notification(get_string('nocoursesfound', 'local_downloadcenter'), 
                                         \core\output\notification::NOTIFY_WARNING)
                );
            }
        }
        
        // Download options header.
        $mform->addElement('header', 'downloadoptionsheader', 
                          get_string('downloadoptions', 'local_downloadcenter'));
        
        // Option: Exclude student content.
        $mform->addElement('checkbox', 'excludestudent', 
                          get_string('excludestudentcontent', 'local_downloadcenter'));
        $mform->setDefault('excludestudent', 
                          $options['excludestudent'] ?? get_config('local_downloadcenter', 'excludestudentdefault'));
        $mform->addHelpButton('excludestudent', 'excludestudentcontent', 'local_downloadcenter');
        
        // Option: Include files.
        $mform->addElement('checkbox', 'includefiles', 
                          get_string('includefiles', 'local_downloadcenter'));
        $mform->setDefault('includefiles', $options['includefiles'] ?? 1);
        $mform->addHelpButton('includefiles', 'includefiles', 'local_downloadcenter');
        
        // Option: Use real filenames.
        $mform->addElement('checkbox', 'filesrealnames', 
                          get_string('downloadoptions:filesrealnames', 'local_downloadcenter'));
        $mform->setDefault('filesrealnames', $options['filesrealnames'] ?? 0);
        $mform->addHelpButton('filesrealnames', 'downloadoptions:filesrealnames', 'local_downloadcenter');
        
        // Option: Add numbering.
        $mform->addElement('checkbox', 'addnumbering', 
                          get_string('downloadoptions:addnumbering', 'local_downloadcenter'));
        $mform->setDefault('addnumbering', $options['addnumbering'] ?? 0);
        $mform->addHelpButton('addnumbering', 'downloadoptions:addnumbering', 'local_downloadcenter');
        
        // Action buttons.
        $this->add_action_buttons(true, get_string('saveandcontinue', 'local_downloadcenter'));
    }
    
    /**
     * Validate the form data.
     *
     * @param array $data Form data
     * @param array $files Files
     * @return array Validation errors
     */
    public function validation($data, $files) {
        $errors = parent::validation($data, $files);
        
        // Check maximum courses limit.
        if (!empty($data['courses'])) {
            $selectedcount = count(array_filter($data['courses']));
            $maxcourses = get_config('local_downloadcenter', 'maxcoursesperdownload');
            
            if ($selectedcount > $maxcourses) {
                $errors['courses[0]'] = get_string('toomanycoursesselected', 
                                                   'local_downloadcenter', $maxcourses);
            }
        }
        
        return $errors;
    }
}