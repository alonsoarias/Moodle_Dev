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
 * Course mapping form for Q10 Grades Sync.
 *
 * @package    local_q10grades
 * @copyright  2025 Your Institution
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_q10grades\form;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');

/**
 * Form for mapping a Moodle course to a Q10 subject.
 */
class course_mapping_form extends \moodleform {

    /**
     * Form definition.
     */
    protected function definition() {
        $mform = $this->_form;
        $customdata = $this->_customdata;

        $courseid = $customdata['courseid'];
        $mapping = $customdata['mapping'] ?? null;

        $mform->addElement('hidden', 'courseid', $courseid);
        $mform->setType('courseid', PARAM_INT);

        // Q10 Subject ID.
        $mform->addElement('text', 'q10_subject_id', get_string('q10subjectid', 'local_q10grades'), ['size' => 50]);
        $mform->setType('q10_subject_id', PARAM_TEXT);
        $mform->addRule('q10_subject_id', get_string('required'), 'required', null, 'client');
        $mform->addHelpButton('q10_subject_id', 'q10subjectid', 'local_q10grades');

        // Q10 Period ID.
        $mform->addElement('text', 'q10_period_id', get_string('q10periodid', 'local_q10grades'), ['size' => 50]);
        $mform->setType('q10_period_id', PARAM_TEXT);
        $mform->addHelpButton('q10_period_id', 'q10periodid', 'local_q10grades');

        // Q10 Group ID.
        $mform->addElement('text', 'q10_group_id', get_string('q10groupid', 'local_q10grades'), ['size' => 50]);
        $mform->setType('q10_group_id', PARAM_TEXT);
        $mform->addHelpButton('q10_group_id', 'q10groupid', 'local_q10grades');

        // Enable sync.
        $mform->addElement('advcheckbox', 'enabled', get_string('enablesync', 'local_q10grades'));
        $mform->setDefault('enabled', 1);

        // Set defaults from existing mapping.
        if ($mapping) {
            $mform->setDefault('q10_subject_id', $mapping->q10_subject_id);
            $mform->setDefault('q10_period_id', $mapping->q10_period_id);
            $mform->setDefault('q10_group_id', $mapping->q10_group_id);
            $mform->setDefault('enabled', $mapping->enabled);
        }

        $this->add_action_buttons(true, get_string('savemapping', 'local_q10grades'));
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

        if (empty(trim($data['q10_subject_id']))) {
            $errors['q10_subject_id'] = get_string('required');
        }

        return $errors;
    }
}
