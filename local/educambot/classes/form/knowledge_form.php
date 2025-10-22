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
 * Form used to manage knowledge base entries.
 *
 * @package     local_educambot
 * @copyright   2024 Educam
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_educambot\form;

use context_system;
use moodleform;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->libdir . '/formslib.php');
require_once($CFG->libdir . '/editorlib.php');

/**
 * Form to create or edit knowledge entries.
 */
class knowledge_form extends moodleform {
    /**
     * Form definition.
     */
    protected function definition(): void {
        $mform = $this->_form;

        $mform->addElement('text', 'title', get_string('title', 'local_educambot'), ['size' => 80]);
        $mform->setType('title', PARAM_TEXT);
        $mform->addRule('title', get_string('formerrortitlerequired', 'local_educambot'), 'required');

        $mform->addElement('textarea', 'summary', get_string('summary', 'local_educambot'), 'rows="4" cols="80"');
        $mform->setType('summary', PARAM_RAW_TRIMMED);

        $editoroptions = $this->_customdata['editoroptions'] ?? [
            'context' => context_system::instance(),
            'maxfiles' => EDITOR_UNLIMITED_FILES,
            'maxbytes' => 0,
            'trusttext' => false,
            'subdirs' => false,
        ];
        $mform->addElement('editor', 'content', get_string('content', 'local_educambot'), null, $editoroptions);
        $mform->setType('content', PARAM_RAW);
        $mform->addRule('content', get_string('formerrorcontentrequired', 'local_educambot'), 'required');

        $topics = $this->_customdata['topics'] ?? [];
        $mform->addElement('select', 'topics', get_string('topics', 'local_educambot'), $topics, ['multiple' => 'multiple', 'size' => 8]);
        $mform->setType('topics', PARAM_RAW);

        $mform->addElement('text', 'tags', get_string('keywords', 'local_educambot'), ['size' => 80]);
        $mform->setType('tags', PARAM_RAW_TRIMMED);
        $mform->addHelpButton('tags', 'knowledgekeywords', 'local_educambot');

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
     * @param array|object $defaultvalues
     */
    public function set_data($defaultvalues) {
        if (is_object($defaultvalues)) {
            $defaultvalues = (array)$defaultvalues;
        }
        if (isset($defaultvalues['topics']) && is_string($defaultvalues['topics'])) {
            $defaultvalues['topics'] = array_filter(array_map('intval', explode(',', $defaultvalues['topics'])));
        }
        if (isset($defaultvalues['content']) && is_string($defaultvalues['content'])) {
            $defaultvalues['content'] = [
                'text' => $defaultvalues['content'],
                'format' => FORMAT_HTML,
            ];
        }
        parent::set_data($defaultvalues);
    }

    /**
     * Form validation.
     *
     * @param array $data
     * @param array $files
     * @return array
     */
    public function validation($data, $files): array {
        $errors = parent::validation($data, $files);
        if (trim($data['title'] ?? '') === '') {
            $errors['title'] = get_string('formerrortitlerequired', 'local_educambot');
        }
        $content = trim($data['content']['text'] ?? '');
        if ($content === '') {
            $errors['content'] = get_string('formerrorcontentrequired', 'local_educambot');
        }
        return $errors;
    }
}
