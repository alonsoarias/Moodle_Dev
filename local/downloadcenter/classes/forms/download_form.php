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
 * Download form for course resources
 *
 * @package    local_downloadcenter
 * @copyright  2025 Original: Academic Moodle Cooperation, Extended: Alonso Arias <soporte@ingeweb.co>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_downloadcenter\forms;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');

/**
 * Form for selecting resources to download.
 *
 * @package    local_downloadcenter
 * @copyright  2025 Original: Academic Moodle Cooperation, Extended: Alonso Arias <soporte@ingeweb.co>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class download_form extends \moodleform {
    
    /**
     * Define the form.
     *
     * @return void
     */
    public function definition() {
        global $COURSE, $OUTPUT;
        
        $mform = $this->_form;
        
        $resources = $this->_customdata['res'] ?? [];
        $mode = $this->_customdata['mode'] ?? 'course';
        
        // Hidden fields.
        $mform->addElement('hidden', 'courseid', $COURSE->id);
        $mform->setType('courseid', PARAM_INT);
        
        $mform->addElement('hidden', 'mode', $mode);
        $mform->setType('mode', PARAM_ALPHA);
        
        // Information message.
        $coursecontext = \context_course::instance($COURSE->id);
        
        if (has_capability('moodle/course:update', $coursecontext)) {
            $infomessage = get_string('infomessage_teachers', 'local_downloadcenter');
        } else {
            $infomessage = get_string('infomessage_students', 'local_downloadcenter');
        }
        
        $mform->addElement('html', 
            \html_writer::tag('div', $infomessage, ['class' => 'alert alert-info alert-block'])
        );
        
        // Search box.
        $mform->addElement('html', 
            $OUTPUT->render_from_template('local_downloadcenter/searchbox', [])
        );
        
        // Hack for proper fieldset rendering.
        $mform->addElement('static', 'warning', '', '');
        
        // Add resources by section.
        $firstbox = true;
        foreach ($resources as $sectionid => $sectioninfo) {
            $sectionname = 'item_topic_' . $sectionid;
            
            $class = 'card block mb-3';
            if ($firstbox) {
                $class .= ' mt-3';
                $firstbox = false;
            }
            
            $mform->addElement('html', \html_writer::start_tag('div', ['class' => $class]));
            
            // Section title.
            $sectiontitle = \html_writer::span($sectioninfo->title, 'sectiontitle mt-1');
            
            // Add visibility badge if hidden.
            if (!$sectioninfo->visible) {
                $sectiontitle .= \html_writer::tag('span', 
                    get_string('hiddenfromstudents'),
                    ['class' => 'badge bg-info text-white ml-1 sectiontitlebadge']
                );
            }
            
            // Section checkbox.
            $mform->addElement('checkbox', $sectionname, $sectiontitle, '', ['class' => 'mt-2']);
            $mform->setDefault($sectionname, 1);
            
            // Add resources in section.
            foreach ($sectioninfo->res as $res) {
                $name = 'item_' . $res->modname . '_' . $res->instanceid;
                $title = \html_writer::span($res->name) . ' ' . $res->icon;
                
                // Add visibility badges.
                $badge = '';
                if (!$res->visible) {
                    $badge = \html_writer::tag('span', 
                        get_string('hiddenfromstudents'),
                        ['class' => 'badge bg-info text-white mb-1']
                    );
                }
                if ($res->isstealth) {
                    $badge = \html_writer::tag('span', 
                        get_string('hiddenoncoursepage'),
                        ['class' => 'badge bg-info text-white mb-1']
                    );
                }
                
                $title = \html_writer::tag('span', $title . $badge, ['class' => 'itemtitle']);
                
                $mform->addElement('checkbox', $name, $title);
                $mform->setDefault($name, 1);
            }
            
            $mform->addElement('html', \html_writer::end_tag('div'));
        }
        
        // Download options section.
        $mform->addElement('header', 'downloadoptions', 
                          get_string('downloadoptions', 'local_downloadcenter'));
        
        // Option: Use real filenames.
        $mform->addElement('checkbox', 'filesrealnames', 
                          get_string('downloadoptions:filesrealnames', 'local_downloadcenter'));
        $mform->setDefault('filesrealnames', 0);
        $mform->addHelpButton('filesrealnames', 'downloadoptions:filesrealnames', 'local_downloadcenter');
        
        // Option: Add numbering.
        $mform->addElement('checkbox', 'addnumbering', 
                          get_string('downloadoptions:addnumbering', 'local_downloadcenter'));
        $mform->setDefault('addnumbering', 0);
        $mform->addHelpButton('addnumbering', 'downloadoptions:addnumbering', 'local_downloadcenter');
        
        // Option: Exclude student content (for teachers).
        if (has_capability('local/downloadcenter:excludestudentcontent', $coursecontext)) {
            $mform->addElement('checkbox', 'excludestudent', 
                              get_string('excludestudentcontent', 'local_downloadcenter'));
            $mform->setDefault('excludestudent', 
                              get_config('local_downloadcenter', 'excludestudentdefault'));
            $mform->addHelpButton('excludestudent', 'excludestudentcontent', 'local_downloadcenter');
        }
        
        // Action buttons.
        $this->add_action_buttons(true, get_string('createzip', 'local_downloadcenter'));
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
        
        // Check if at least one resource is selected.
        $hasselection = false;
        foreach ($data as $key => $value) {
            if ((strpos($key, 'item_') === 0) && $value) {
                $hasselection = true;
                break;
            }
        }
        
        if (!$hasselection) {
            $errors['warning'] = get_string('noselectederror', 'local_downloadcenter');
        }
        
        return $errors;
    }
}