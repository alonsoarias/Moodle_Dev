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
 * Form definition for knowledge base entries.
 *
 * @package     local_educambot
 * @copyright   2024 Educam
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_educambot\form;

defined('MOODLE_INTERNAL') || die();

use context_system;
use moodleform;

global $CFG;
require_once($CFG->libdir . '/formslib.php');
require_once($CFG->libdir . '/editorlib.php');

/**
 * Form to create or edit bot rules.
 */
class entry_form extends moodleform {
    /**
     * Defines form fields.
     */
    protected function definition(): void {
        $mform = $this->_form;

        $mform->addElement('textarea', 'pattern', get_string('pattern', 'local_educambot'), 'rows="3" cols="50"');
        $mform->setType('pattern', PARAM_TEXT);
        $mform->addRule('pattern', get_string('formerrorpatternrequired', 'local_educambot'), 'required');

        $mform->addElement('textarea', 'synonyms', get_string('synonyms', 'local_educambot'), 'rows="4" cols="50"');
        $mform->setType('synonyms', PARAM_TEXT);
        $mform->addHelpButton('synonyms', 'synonyms', 'local_educambot');

        $mform->addElement('text', 'keywords', get_string('keywords', 'local_educambot'));
        $mform->setType('keywords', PARAM_TEXT);
        $mform->addHelpButton('keywords', 'keywords', 'local_educambot');

        $editoroptions = $this->_customdata['editoroptions'] ?? [
            'context' => context_system::instance(),
            'maxfiles' => EDITOR_UNLIMITED_FILES,
            'maxbytes' => 0,
            'trusttext' => false,
        ];
        $mform->addElement('editor', 'response', get_string('response', 'local_educambot'), null, $editoroptions);
        $mform->setType('response', PARAM_RAW);
        $mform->addRule('response', get_string('formerrorresponcerequired', 'local_educambot'), 'required');

        $roles = get_all_roles(context_system::instance());
        $roleoptions = [];
        foreach ($roles as $role) {
            $roleoptions[$role->shortname] = role_get_name($role, context_system::instance());
        }
        $mform->addElement('select', 'roles', get_string('roles', 'local_educambot'), $roleoptions, ['multiple' => 'multiple']);
        $mform->setType('roles', PARAM_RAW);
        $mform->addHelpButton('roles', 'roles', 'local_educambot');

        $mform->addElement('textarea', 'contexts', get_string('contexts', 'local_educambot'), 'rows="3" cols="50"');
        $mform->setType('contexts', PARAM_TEXT);
        $mform->addHelpButton('contexts', 'contexts', 'local_educambot');

        $mform->addElement('advcheckbox', 'suggested', get_string('suggested', 'local_educambot'));
        $mform->setType('suggested', PARAM_INT);
        $mform->setDefault('suggested', 0);

        $mform->addElement('advcheckbox', 'enabled', get_string('enabled', 'local_educambot'));
        $mform->setType('enabled', PARAM_INT);
        $mform->setDefault('enabled', 1);

        $mform->addElement('hidden', 'id', 0);
        $mform->setType('id', PARAM_INT);

        $this->add_action_buttons();
    }

    /**
     * Custom data preprocessing.
     *
     * @param array $defaultvalues
     */
    public function set_data($defaultvalues) {
        if (is_object($defaultvalues)) {
            $defaultvalues = (array)$defaultvalues;
        }
        if (isset($defaultvalues['roles']) && is_string($defaultvalues['roles'])) {
            $defaultvalues['roles'] = preg_split('/[,;]/', $defaultvalues['roles'], -1, PREG_SPLIT_NO_EMPTY);
        }
        if (isset($defaultvalues['response']) && is_string($defaultvalues['response'])) {
            $defaultvalues['response'] = [
                'text' => $defaultvalues['response'],
                'format' => FORMAT_HTML,
            ];
        }
        parent::set_data($defaultvalues);
    }

    /**
     * Validates form data.
     *
     * @param array $data
     * @param array $files
     * @return array
     */
    public function validation($data, $files): array {
        $errors = parent::validation($data, $files);
        if (trim($data['pattern'] ?? '') === '') {
            $errors['pattern'] = get_string('formerrorpatternrequired', 'local_educambot');
        }
        $responsetext = trim($data['response']['text'] ?? '');
        if ($responsetext === '') {
            $errors['response'] = get_string('formerrorresponcerequired', 'local_educambot');
        }
        return $errors;
    }
}
