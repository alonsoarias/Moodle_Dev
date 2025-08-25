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
 * Download center plugin
 *
 * @package       local_downloadcenter
 * @author        Simeon Naydenov (moniNaydenov@gmail.com)
 * @copyright     2020 Academic Moodle Cooperation {@link http://www.academic-moodle-cooperation.org}
 * @license       http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');
require_once(__DIR__ . '/locallib.php');

/**
 * Class local_downloadcenter_download_form
 */
class local_downloadcenter_download_form extends moodleform {
    /**
     * @throws coding_exception
     */
    public function definition() {
        global $COURSE, $PAGE;
        $mform = $this->_form;

        $resources = $this->_customdata['res'] ?? [];
        $selection = $this->_customdata['selection'] ?? [];

        // Get courseid from URL if not in global COURSE
        $courseid = optional_param('courseid', $COURSE->id, PARAM_INT);
        
        $mform->addElement('hidden', 'courseid', $courseid);
        $mform->setType('courseid', PARAM_INT);

        $mform->addElement('html',
            html_writer::tag('div',
                get_string('warningmessage', 'local_downloadcenter'),
                array('class' => 'alert alert-info alert-block')
            )
        );
        $mform->addElement('static', 'warning', '', ''); // Hack to work around fieldsets!

        $empty = true;
        $excludeempty = get_config('local_downloadcenter', 'exclude_empty_topics');
        $totalItemsAvailable = 0;
        $totalItemsSelected = 0;
        
        foreach ($resources as $sectionid => $sectioninfo) {
            if ($excludeempty && empty($sectioninfo->res)) { // Only display the sections that are not empty.
                continue;
            }

            $empty = false;
            $sectionname = 'item_topic_' . $sectionid;
            $mform->addElement('html', html_writer::start_tag('div', array('class' => 'card block mb-3')));
            $sectiontitle = html_writer::span($sectioninfo->title, 'sectiontitle');
            
            // Count items in section
            $totalitems = count($sectioninfo->res);
            $selecteditems = 0;
            
            // Check which items are selected
            foreach ($sectioninfo->res as $res) {
                $totalItemsAvailable++;
                $name = 'item_' . $res->modname . '_' . $res->instanceid;
                if (isset($selection[$name]) && $selection[$name]) {
                    $selecteditems++;
                    $totalItemsSelected++;
                }
            }
            
            // Section checkbox attributes
            $sectionattrs = array('class' => 'section-checkbox', 'data-section' => $sectionid);
            
            // Set section state based on item selection
            if ($selecteditems === 0) {
                // No items selected - section unchecked
                $mform->setDefault($sectionname, 0);
            } else if ($selecteditems === $totalitems) {
                // All items selected - section checked
                $mform->setDefault($sectionname, 1);
            } else {
                // Some items selected - section indeterminate
                $mform->setDefault($sectionname, 1);
                $sectionattrs['data-indeterminate'] = 1;
            }
            
            // Also check if section itself was selected
            if (isset($selection[$sectionname]) && $selection[$sectionname]) {
                $mform->setDefault($sectionname, 1);
                // If section was fully selected, select all items
                if ($selecteditems === 0) {
                    $selecteditems = $totalitems;
                }
            }
            
            // Add section checkbox
            $mform->addElement('checkbox', $sectionname, $sectiontitle, '', $sectionattrs);
            
            // Add item checkboxes
            foreach ($sectioninfo->res as $res) {
                $name = 'item_' . $res->modname . '_' . $res->instanceid;
                $title = html_writer::span($res->name) . ' ' . $res->icon;
                $title = html_writer::tag('span', $title, array('class' => 'itemtitle'));
                $itemattrs = array('class' => 'item-checkbox', 'data-section' => $sectionid);
                $mform->addElement('checkbox', $name, $title, '', $itemattrs);
                
                // Set default value based on selection
                if (isset($selection[$name]) && $selection[$name]) {
                    $mform->setDefault($name, 1);
                } else if (isset($selection[$sectionname]) && $selection[$sectionname] && 
                          (!isset($sectionattrs['data-indeterminate']) || !$sectionattrs['data-indeterminate'])) {
                    // If section is fully selected, select this item too
                    $mform->setDefault($name, 1);
                }
            }
            $mform->addElement('html', html_writer::end_tag('div'));
        }

        if ($empty) {
            $mform->addElement('html', html_writer::tag('h2', get_string('no_downloadable_content', 'local_downloadcenter')));
        } else {
            // Show selection summary
            if ($totalItemsSelected > 0) {
                $summaryText = "$totalItemsSelected of $totalItemsAvailable items currently selected";
                $mform->addElement('html', 
                    html_writer::tag('div', $summaryText, 
                                   array('class' => 'alert alert-success mb-3', 'id' => 'selection-summary'))
                );
            }
        }
        
        // Submit button - change label based on context
        $buttonLabel = get_string('saveselection', 'local_downloadcenter');
        if ($totalItemsSelected > 0) {
            $buttonLabel = get_string('saveselection', 'local_downloadcenter') . " ($totalItemsSelected items)";
        }
        $this->add_action_buttons(true, $buttonLabel);
    }
    
    /**
     * Custom validation
     * @param array $data
     * @param array $files
     * @return array
     */
    public function validation($data, $files) {
        $errors = parent::validation($data, $files);
        
        // No specific validation needed
        
        return $errors;
    }
}