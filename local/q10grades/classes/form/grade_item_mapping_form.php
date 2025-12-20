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
 * Grade item mapping form for Q10 Grades Sync.
 *
 * @package    local_q10grades
 * @copyright  2025 Your Institution
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_q10grades\form;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');

/**
 * Form for mapping Moodle grade items to Q10 evaluation components.
 */
class grade_item_mapping_form extends \moodleform {

    /**
     * Form definition.
     */
    protected function definition() {
        global $DB;

        $mform = $this->_form;
        $customdata = $this->_customdata;

        $courseid = $customdata['courseid'];
        $gradeitems = $customdata['gradeitems'] ?? [];
        $existingmappings = $customdata['mappings'] ?? [];

        $mform->addElement('hidden', 'courseid', $courseid);
        $mform->setType('courseid', PARAM_INT);

        $mform->addElement('header', 'mappingheader', get_string('gradeitemsmapping', 'local_q10grades'));
        $mform->addElement('html', '<p>' . get_string('gradeitemsmapping_desc', 'local_q10grades') . '</p>');

        if (empty($gradeitems)) {
            $mform->addElement('static', 'nogradeitems', '', get_string('nogradeitems', 'local_q10grades'));
        } else {
            foreach ($gradeitems as $item) {
                $itemname = $item->itemname ?: get_string('coursetotal', 'grades');
                if ($item->itemtype === 'course') {
                    $itemname = get_string('coursetotal', 'grades');
                } elseif ($item->itemtype === 'category') {
                    $itemname = get_string('category', 'grades') . ': ' . $item->itemname;
                }

                $group = [];

                // Checkbox to include this item.
                $group[] = $mform->createElement('advcheckbox', 'enabled_' . $item->id, '', '');

                // Q10 component ID.
                $group[] = $mform->createElement('text', 'component_' . $item->id,
                    '', ['placeholder' => get_string('q10componentid', 'local_q10grades'), 'size' => 30]);

                // Weight.
                $group[] = $mform->createElement('text', 'weight_' . $item->id,
                    '', ['placeholder' => '%', 'size' => 5]);

                $mform->addGroup($group, 'gradeitem_' . $item->id, $itemname, ' ', false);

                $mform->setType('component_' . $item->id, PARAM_TEXT);
                $mform->setType('weight_' . $item->id, PARAM_FLOAT);

                // Set defaults from existing mappings.
                if (isset($existingmappings[$item->id])) {
                    $mapping = $existingmappings[$item->id];
                    $mform->setDefault('enabled_' . $item->id, $mapping->enabled);
                    $mform->setDefault('component_' . $item->id, $mapping->q10_component_id);
                    $mform->setDefault('weight_' . $item->id, $mapping->weight);
                }
            }
        }

        $this->add_action_buttons(true, get_string('savemappings', 'local_q10grades'));
    }
}
