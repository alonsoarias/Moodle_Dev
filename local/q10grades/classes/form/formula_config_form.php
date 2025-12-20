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
 * Formula configuration form for calculating grades from multiple activities.
 *
 * @package    local_q10grades
 * @copyright  2025 Your Institution
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_q10grades\form;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');

/**
 * Form for configuring grade calculation formulas.
 */
class formula_config_form extends \moodleform {

    /**
     * Form definition.
     */
    protected function definition() {
        $mform = $this->_form;
        $customdata = $this->_customdata;

        $courseid = $customdata['courseid'];
        $gradeitems = $customdata['gradeitems'] ?? [];
        $formula = $customdata['formula'] ?? null;

        $mform->addElement('hidden', 'courseid', $courseid);
        $mform->setType('courseid', PARAM_INT);

        $mform->addElement('hidden', 'formulaid', $formula->id ?? 0);
        $mform->setType('formulaid', PARAM_INT);

        // Formula name.
        $mform->addElement('text', 'name', get_string('formulaname', 'local_q10grades'), ['size' => 50]);
        $mform->setType('name', PARAM_TEXT);
        $mform->addRule('name', get_string('required'), 'required', null, 'client');

        // Q10 Component ID this formula maps to.
        $mform->addElement('text', 'q10_component_id', get_string('q10componentid', 'local_q10grades'), ['size' => 50]);
        $mform->setType('q10_component_id', PARAM_TEXT);
        $mform->addRule('q10_component_id', get_string('required'), 'required', null, 'client');
        $mform->addHelpButton('q10_component_id', 'q10componentid', 'local_q10grades');

        // Formula type.
        $formulatypes = [
            'average' => get_string('formulaaverage', 'local_q10grades'),
            'weighted' => get_string('formulaweighted', 'local_q10grades'),
            'sum' => get_string('formulasum', 'local_q10grades'),
            'highest' => get_string('formulahighest', 'local_q10grades'),
            'lowest' => get_string('formulalowest', 'local_q10grades'),
            'custom' => get_string('formulacustom', 'local_q10grades'),
        ];
        $mform->addElement('select', 'formula_type', get_string('formulatype', 'local_q10grades'), $formulatypes);
        $mform->setDefault('formula_type', 'average');

        // Select activities/grade items.
        $mform->addElement('header', 'activitiesheader', get_string('selectactivities', 'local_q10grades'));

        if (empty($gradeitems)) {
            $mform->addElement('static', 'noactivities', '', get_string('noactivities', 'local_q10grades'));
        } else {
            $options = [];
            foreach ($gradeitems as $item) {
                if ($item->itemtype === 'course') {
                    continue; // Skip course total.
                }
                $itemname = $item->itemname ?: get_string('unnamed', 'local_q10grades');
                if ($item->itemmodule) {
                    $itemname = get_string('modulename', $item->itemmodule) . ': ' . $itemname;
                }
                $options[$item->id] = $itemname;
            }

            $select = $mform->addElement('select', 'selected_items', get_string('gradeitems', 'local_q10grades'), $options);
            $select->setMultiple(true);
            $mform->addRule('selected_items', get_string('required'), 'required', null, 'client');

            // Weights for weighted average.
            $mform->addElement('header', 'weightsheader', get_string('itemweights', 'local_q10grades'));
            $mform->addElement('html', '<p>' . get_string('itemweights_desc', 'local_q10grades') . '</p>');

            foreach ($gradeitems as $item) {
                if ($item->itemtype === 'course') {
                    continue;
                }
                $itemname = $item->itemname ?: get_string('unnamed', 'local_q10grades');
                $mform->addElement('text', 'weight_' . $item->id, $itemname, ['size' => 5]);
                $mform->setType('weight_' . $item->id, PARAM_FLOAT);
                $mform->setDefault('weight_' . $item->id, 1);
                $mform->hideIf('weight_' . $item->id, 'formula_type', 'neq', 'weighted');
            }

            // Custom formula.
            $mform->addElement('header', 'customheader', get_string('customformula', 'local_q10grades'));
            $mform->addElement('textarea', 'custom_formula', get_string('customformula', 'local_q10grades'),
                ['rows' => 3, 'cols' => 60]);
            $mform->setType('custom_formula', PARAM_TEXT);
            $mform->addHelpButton('custom_formula', 'customformula', 'local_q10grades');
            $mform->hideIf('custom_formula', 'formula_type', 'neq', 'custom');
        }

        // Enable this formula.
        $mform->addElement('advcheckbox', 'enabled', get_string('enableformula', 'local_q10grades'));
        $mform->setDefault('enabled', 1);

        // Set defaults from existing formula.
        if ($formula) {
            $mform->setDefault('name', $formula->name);
            $mform->setDefault('q10_component_id', $formula->q10_component_id);
            $mform->setDefault('formula_type', $formula->formula_type);
            $mform->setDefault('enabled', $formula->enabled);

            if (!empty($formula->selected_items)) {
                $mform->setDefault('selected_items', json_decode($formula->selected_items, true));
            }
            if (!empty($formula->weights)) {
                $weights = json_decode($formula->weights, true);
                foreach ($weights as $itemid => $weight) {
                    $mform->setDefault('weight_' . $itemid, $weight);
                }
            }
            if (!empty($formula->custom_formula)) {
                $mform->setDefault('custom_formula', $formula->custom_formula);
            }
        }

        $this->add_action_buttons(true, get_string('saveformula', 'local_q10grades'));
    }

    /**
     * Validation.
     *
     * @param array $data Form data.
     * @param array $files Uploaded files.
     * @return array Errors.
     */
    public function validation($data, $files) {
        $errors = parent::validation($data, $files);

        if (empty($data['selected_items'])) {
            $errors['selected_items'] = get_string('selectatleastone', 'local_q10grades');
        }

        if ($data['formula_type'] === 'weighted') {
            $totalweight = 0;
            foreach ($data as $key => $value) {
                if (strpos($key, 'weight_') === 0 && is_numeric($value)) {
                    $totalweight += $value;
                }
            }
            // Weight validation is optional.
        }

        return $errors;
    }
}
