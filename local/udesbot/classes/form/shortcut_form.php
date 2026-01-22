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
 * Shortcut form for udesbot.
 *
 * @package     local_udesbot
 * @author      Alonso Arias <soporte@orioncloud.com.co>
 * @copyright   2025 OrionCloud<https://orioncloud.com.co>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_udesbot\form;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');

use local_udesbot\bot\shortcut_handler;

/**
 * Form for creating/editing shortcuts.
 */
class shortcut_form extends \moodleform {

    /**
     * Form definition.
     */
    protected function definition() {
        $mform = $this->_form;

        // Hidden id field for editing.
        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);

        // Name.
        $mform->addElement('text', 'name', get_string('shortcutname', 'local_udesbot'), ['size' => 50]);
        $mform->setType('name', PARAM_TEXT);
        $mform->addRule('name', get_string('required'), 'required', null, 'client');
        $mform->addHelpButton('name', 'shortcutname', 'local_udesbot');

        // Keywords.
        $mform->addElement('textarea', 'keywords', get_string('shortcutkeywords', 'local_udesbot'),
            ['rows' => 5, 'cols' => 50]);
        $mform->setType('keywords', PARAM_TEXT);
        $mform->addRule('keywords', get_string('required'), 'required', null, 'client');
        $mform->addHelpButton('keywords', 'shortcutkeywords', 'local_udesbot');

        // Action type.
        $actiontypes = shortcut_handler::get_action_types();
        $mform->addElement('select', 'actiontype', get_string('actiontype', 'local_udesbot'), $actiontypes);
        $mform->addRule('actiontype', get_string('required'), 'required', null, 'client');
        $mform->addHelpButton('actiontype', 'actiontype', 'local_udesbot');

        // Description.
        $mform->addElement('textarea', 'description', get_string('description'), ['rows' => 3, 'cols' => 50]);
        $mform->setType('description', PARAM_TEXT);

        // Icon.
        $mform->addElement('text', 'icon', get_string('icon', 'local_udesbot'), ['size' => 10]);
        $mform->setType('icon', PARAM_TEXT);
        $mform->setDefault('icon', '⚡');
        $mform->addHelpButton('icon', 'icon', 'local_udesbot');

        // Sort order.
        $mform->addElement('text', 'sortorder', get_string('sortorder', 'local_udesbot'), ['size' => 5]);
        $mform->setType('sortorder', PARAM_INT);
        $mform->setDefault('sortorder', 0);

        // Enabled.
        $mform->addElement('advcheckbox', 'enabled', get_string('enabled', 'local_udesbot'));
        $mform->setDefault('enabled', 1);

        // Buttons.
        $this->add_action_buttons();
    }

    /**
     * Validate form data.
     *
     * @param array $data Form data
     * @param array $files Files
     * @return array Errors
     */
    public function validation($data, $files) {
        $errors = parent::validation($data, $files);

        // Check name length.
        if (strlen($data['name']) > 100) {
            $errors['name'] = get_string('nametoolong', 'local_udesbot');
        }

        // Check keywords not empty.
        if (empty(trim($data['keywords']))) {
            $errors['keywords'] = get_string('keywordsrequired', 'local_udesbot');
        }

        return $errors;
    }
}
