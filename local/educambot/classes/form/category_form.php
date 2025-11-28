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
 * Category entry form.
 *
 * @package     local_educambot
 * @copyright   2025 EducamBot Team
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_educambot\form;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');

/**
 * Category entry form class.
 */
class category_form extends \moodleform {

    /**
     * Define form elements.
     */
    protected function definition() {
        global $DB;

        $mform = $this->_form;
        $categoryid = $this->_customdata['categoryid'] ?? 0;

        // Hidden field for category ID (when editing).
        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);

        // Category name.
        $mform->addElement('text', 'name', get_string('categoryname', 'local_educambot'), ['size' => 50, 'maxlength' => 100]);
        $mform->setType('name', PARAM_TEXT);
        $mform->addRule('name', get_string('required'), 'required', null, 'client');
        $mform->addRule('name', get_string('maximumchars', '', 100), 'maxlength', 100, 'client');

        // Description.
        $mform->addElement('textarea', 'description', get_string('categorydescription', 'local_educambot'),
            ['rows' => 3, 'cols' => 50]);
        $mform->setType('description', PARAM_TEXT);

        // Parent category selector.
        $categories = $DB->get_records_menu('local_educambot_category', null, 'sortorder ASC', 'id, name');

        // Remove self from parent options to avoid circular reference.
        if ($categoryid > 0) {
            unset($categories[$categoryid]);
        }

        $parentoptions = [0 => get_string('none')] + $categories;
        $mform->addElement('select', 'parent', get_string('parentcategory', 'local_educambot'), $parentoptions);
        $mform->setType('parent', PARAM_INT);

        // Sort order.
        $mform->addElement('text', 'sortorder', get_string('optionorder', 'local_educambot'), ['size' => 5]);
        $mform->setType('sortorder', PARAM_INT);
        $mform->setDefault('sortorder', 0);

        // Enabled checkbox.
        $mform->addElement('advcheckbox', 'enabled', get_string('enabled', 'local_educambot'));
        $mform->setDefault('enabled', 1);

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

        // Validate name is not empty after trimming.
        if (empty(trim($data['name']))) {
            $errors['name'] = get_string('required');
        }

        // Validate name length.
        if (strlen($data['name']) > 100) {
            $errors['name'] = get_string('maximumchars', '', 100);
        }

        return $errors;
    }
}
