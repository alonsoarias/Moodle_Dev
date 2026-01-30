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
 * @package     local_udesbot
 * @author      Alonso Arias <soporte@orioncloud.com.co>
 * @copyright   2025 OrionCloud<https://orioncloud.com.co>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_udesbot\form;

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
        $categories = $DB->get_records_menu('local_udesbot_category', ['enabled' => 1], 'sortorder ASC', 'id, name');
        $categoryoptions = ['' => get_string('uncategorized', 'local_udesbot')] + $categories;
        $mform->addElement('select', 'categoryid', get_string('category', 'local_udesbot'), $categoryoptions);
        $mform->setType('categoryid', PARAM_INT);

        // Pattern field.
        $mform->addElement('text', 'pattern', get_string('pattern', 'local_udesbot'), ['size' => 60]);
        $mform->setType('pattern', PARAM_TEXT);
        $mform->addRule('pattern', get_string('required'), 'required', null, 'client');
        $mform->addHelpButton('pattern', 'pattern', 'local_udesbot');

        // Keywords field.
        $mform->addElement('textarea', 'keywords', get_string('keywords', 'local_udesbot'),
            ['rows' => 4, 'cols' => 60]);
        $mform->setType('keywords', PARAM_TEXT);
        $mform->addHelpButton('keywords', 'keywords', 'local_udesbot');

        // Response field.
        $mform->addElement('textarea', 'response', get_string('response', 'local_udesbot'),
            ['rows' => 6, 'cols' => 60]);
        $mform->setType('response', PARAM_TEXT);
        $mform->addRule('response', get_string('required'), 'required', null, 'client');
        $mform->addHelpButton('response', 'response', 'local_udesbot');

        // Tags field.
        $mform->addElement('text', 'tags', get_string('tags', 'local_udesbot'), ['size' => 60]);
        $mform->setType('tags', PARAM_TEXT);
        $mform->addHelpButton('tags', 'tags', 'local_udesbot');

        // Enabled checkbox.
        $mform->addElement('advcheckbox', 'enabled', get_string('enabled', 'local_udesbot'));
        $mform->setDefault('enabled', 1);
        $mform->addHelpButton('enabled', 'enabled', 'local_udesbot');

        // Show options checkbox.
        $mform->addElement('advcheckbox', 'showoptions', get_string('showoptions', 'local_udesbot'));
        $mform->setDefault('showoptions', 1);
        $mform->addHelpButton('showoptions', 'showoptions', 'local_udesbot');

        // Advanced section header.
        $mform->addElement('header', 'advancedsection', get_string('advanced', 'local_udesbot'));
        $mform->setExpanded('advancedsection', false);

        // Context-aware checkbox (v1.7.0).
        $mform->addElement('advcheckbox', 'contextaware', get_string('contextaware', 'local_udesbot'));
        $mform->setDefault('contextaware', 0);
        $mform->addHelpButton('contextaware', 'contextaware', 'local_udesbot');

        // Dynamic response checkbox (v1.7.0).
        $mform->addElement('advcheckbox', 'dynamicresponse', get_string('dynamicresponse', 'local_udesbot'));
        $mform->setDefault('dynamicresponse', 0);
        $mform->addHelpButton('dynamicresponse', 'dynamicresponse', 'local_udesbot');

        // Required context (v1.7.0).
        $contextoptions = [
            '' => get_string('anycontext', 'local_udesbot'),
            'site' => get_string('sitecontext', 'local_udesbot'),
            'course' => get_string('coursecontext', 'local_udesbot'),
            'activity' => get_string('activitycontext', 'local_udesbot'),
        ];
        $mform->addElement('select', 'requiredcontext', get_string('requiredcontext', 'local_udesbot'), $contextoptions);
        $mform->setType('requiredcontext', PARAM_ALPHA);
        $mform->addHelpButton('requiredcontext', 'requiredcontext', 'local_udesbot');

        // Restrictions section header (v1.8.0).
        $mform->addElement('header', 'restrictionssection', get_string('restrictions', 'local_udesbot'));
        $mform->setExpanded('restrictionssection', false);

        // Archetypes multi-select (v1.9.2) - replaces free-text roles field.
        // These are Moodle role archetypes, not role shortnames.
        $archetypeoptions = [
            'student' => get_string('archetype_student', 'local_udesbot'),
            'teacher' => get_string('archetype_teacher', 'local_udesbot'),
            'editingteacher' => get_string('archetype_editingteacher', 'local_udesbot'),
            'coursecreator' => get_string('archetype_coursecreator', 'local_udesbot'),
            'manager' => get_string('archetype_manager', 'local_udesbot'),
            'guest' => get_string('archetype_guest', 'local_udesbot'),
            'user' => get_string('archetype_user', 'local_udesbot'),
        ];
        $archetypeselect = $mform->addElement('select', 'roles_array', get_string('archetypes', 'local_udesbot'), $archetypeoptions);
        $archetypeselect->setMultiple(true);
        $mform->addHelpButton('roles_array', 'archetypes', 'local_udesbot');

        // Hidden field to store comma-separated roles (for DB compatibility).
        $mform->addElement('hidden', 'roles');
        $mform->setType('roles', PARAM_TEXT);

        // Courses field (v1.8.0).
        $mform->addElement('text', 'courses', get_string('courses', 'local_udesbot'), ['size' => 60]);
        $mform->setType('courses', PARAM_TEXT);
        $mform->addHelpButton('courses', 'courses', 'local_udesbot');

        // Language section header (v1.8.0).
        $mform->addElement('header', 'langsection', get_string('multilanguage', 'local_udesbot'));
        $mform->setExpanded('langsection', false);

        // Language field (v1.8.0).
        $langstrings = get_string_manager()->get_list_of_translations();
        $langoptions = [];
        foreach ($langstrings as $code => $name) {
            // Extract base language code (e.g., 'es' from 'es_mx').
            $basecode = substr($code, 0, 2);
            if (!isset($langoptions[$basecode])) {
                $langoptions[$basecode] = $name;
            }
        }
        // Add common ones if missing.
        if (!isset($langoptions['es'])) {
            $langoptions['es'] = 'Español';
        }
        if (!isset($langoptions['en'])) {
            $langoptions['en'] = 'English';
        }
        ksort($langoptions);

        $mform->addElement('select', 'lang', get_string('language', 'local_udesbot'), $langoptions);
        $mform->setDefault('lang', 'es');
        $mform->setType('lang', PARAM_ALPHANUMEXT);
        $mform->addHelpButton('lang', 'language', 'local_udesbot');

        // Parent rule (for translations) (v1.8.0).
        $parentrules = $DB->get_records_sql(
            "SELECT id, pattern FROM {local_udesbot_rule} WHERE langparent IS NULL ORDER BY pattern",
            []
        );
        $parentoptions = ['' => get_string('none')];
        foreach ($parentrules as $rule) {
            $parentoptions[$rule->id] = shorten_text($rule->pattern, 60);
        }
        $mform->addElement('select', 'langparent', get_string('parentrule', 'local_udesbot'), $parentoptions);
        $mform->setType('langparent', PARAM_INT);
        $mform->addHelpButton('langparent', 'parentrule', 'local_udesbot');

        // Action buttons.
        $this->add_action_buttons();
    }

    /**
     * Set form data - converts comma-separated roles to array for multi-select.
     *
     * @param stdClass|array $data Form data
     */
    public function set_data($data) {
        if (is_object($data)) {
            $data = (array)$data;
        }

        // Convert comma-separated roles string to array for multi-select.
        if (!empty($data['roles'])) {
            $data['roles_array'] = array_map('trim', explode(',', $data['roles']));
        } else {
            $data['roles_array'] = [];
        }

        parent::set_data($data);
    }

    /**
     * Get submitted form data - converts array to comma-separated roles.
     *
     * @return stdClass|false Form data or false
     */
    public function get_data() {
        $data = parent::get_data();

        if ($data) {
            // Convert multi-select array to comma-separated string for DB.
            if (!empty($data->roles_array) && is_array($data->roles_array)) {
                $data->roles = implode(',', $data->roles_array);
            } else {
                $data->roles = '';
            }
            // Remove the temporary field.
            unset($data->roles_array);
        }

        return $data;
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
