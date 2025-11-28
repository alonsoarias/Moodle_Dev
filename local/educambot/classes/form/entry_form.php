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
 * Rule entry form.
 *
 * @package     local_educambot
 * @copyright   2025 EducamBot Team
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_educambot\form;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');

/**
 * Rule entry form class.
 */
class entry_form extends \moodleform {

    /**
     * Define form elements.
     */
    protected function definition() {
        global $DB;

        $mform = $this->_form;

        // Hidden field for rule ID (when editing).
        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);

        // Category selector.
        $categories = $DB->get_records_menu('local_educambot_category', ['enabled' => 1], 'sortorder ASC', 'id, name');
        $categoryoptions = ['' => get_string('uncategorized', 'local_educambot')] + $categories;
        $mform->addElement('select', 'categoryid', get_string('category', 'local_educambot'), $categoryoptions);
        $mform->setType('categoryid', PARAM_INT);

        // Pattern field.
        $mform->addElement('text', 'pattern', get_string('pattern', 'local_educambot'), ['size' => 60]);
        $mform->setType('pattern', PARAM_TEXT);
        $mform->addRule('pattern', get_string('required'), 'required', null, 'client');
        $mform->addHelpButton('pattern', 'pattern', 'local_educambot');

        // Keywords field.
        $mform->addElement('textarea', 'keywords', get_string('keywords', 'local_educambot'),
            ['rows' => 4, 'cols' => 60]);
        $mform->setType('keywords', PARAM_TEXT);
        $mform->addHelpButton('keywords', 'keywords', 'local_educambot');

        // Response field.
        $mform->addElement('textarea', 'response', get_string('response', 'local_educambot'),
            ['rows' => 6, 'cols' => 60]);
        $mform->setType('response', PARAM_TEXT);
        $mform->addRule('response', get_string('required'), 'required', null, 'client');
        $mform->addHelpButton('response', 'response', 'local_educambot');

        // Tags field.
        $mform->addElement('text', 'tags', get_string('tags', 'local_educambot'), ['size' => 60]);
        $mform->setType('tags', PARAM_TEXT);
        $mform->addHelpButton('tags', 'tags', 'local_educambot');

        // Enabled checkbox.
        $mform->addElement('advcheckbox', 'enabled', get_string('enabled', 'local_educambot'));
        $mform->setDefault('enabled', 1);
        $mform->addHelpButton('enabled', 'enabled', 'local_educambot');

        // Show options checkbox.
        $mform->addElement('advcheckbox', 'showoptions', get_string('showoptions', 'local_educambot'));
        $mform->setDefault('showoptions', 1);
        $mform->addHelpButton('showoptions', 'showoptions', 'local_educambot');

        // Action buttons.
        $this->add_action_buttons();
    }

    /**
     * Validate form data.
     *
     * @param array $data Form data
     * @param array $files Uploaded files
     * @return array Errors
     */
    public function validation($data, $files) {
        $errors = parent::validation($data, $files);

        // Validate pattern is not empty after trimming.
        if (empty(trim($data['pattern']))) {
            $errors['pattern'] = get_string('required');
        }

        // Validate response is not empty after trimming.
        if (empty(trim($data['response']))) {
            $errors['response'] = get_string('required');
        }

        return $errors;
    }
}
