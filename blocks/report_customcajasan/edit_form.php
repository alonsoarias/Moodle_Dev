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
 * Block configuration form for report_customcajasan.
 *
 * @package    block_report_customcajasan
 * @copyright  2025 Cajasan
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');
require_once($CFG->dirroot . '/course/lib.php');

/**
 * Configuration form definition.
 */
class block_report_customcajasan_edit_form extends block_edit_form {

    /**
     * Adds custom form fields.
     *
     * @param moodleform $mform
     */
    protected function specific_definition($mform) {
        global $PAGE;

        $systemcontext = context_system::instance();
        $parentcontext = context::instance_by_id($this->block->instance->parentcontextid, IGNORE_MISSING);
        if (!$parentcontext) {
            $parentcontext = $PAGE->context;
        }

        $isuserscontext = ($parentcontext->contextlevel === CONTEXT_USER);
        $iscoursecontext = ($parentcontext->contextlevel === CONTEXT_COURSE && $parentcontext->instanceid != SITEID);

        if ($iscoursecontext) {
            require_capability('block/report_customcajasan:configurecourse', $parentcontext);
        } else {
            require_capability('block/report_customcajasan:manageblock', $systemcontext);
        }

        $mform->addElement('header', 'configheader', get_string('config_header', 'block_report_customcajasan'));

        if ($iscoursecontext) {
            $course = get_course($parentcontext->instanceid);
            $contextinfo = get_string('config_context_course', 'block_report_customcajasan', format_string($course->fullname));
        } else if ($isuserscontext) {
            $contextinfo = get_string('config_context_my', 'block_report_customcajasan');
        } else {
            $contextinfo = get_string('config_context_system', 'block_report_customcajasan');
        }
        $mform->addElement('static', 'contextinfo', get_string('config_context_label', 'block_report_customcajasan'), $contextinfo);

        $categoryoptions = core_course_category::make_categories_list(null, 0, true, '\\-');
        $multiselectoptions = [
            'multiple' => true,
            'tags' => false,
            'placeholder' => get_string('config_categoryids_placeholder', 'block_report_customcajasan')
        ];
        $mform->addElement('autocomplete', 'config_categoryids',
            get_string('config_categoryids', 'block_report_customcajasan'), $categoryoptions, $multiselectoptions);
        $mform->addHelpButton('config_categoryids', 'config_categoryids', 'block_report_customcajasan');
        $mform->setType('config_categoryids', PARAM_RAW);

        $defaultcategories = [];
        if (!empty($this->block->config) && !empty($this->block->config->categoryids)) {
            $defaultcategories = (array)$this->block->config->categoryids;
        }
        $mform->setDefault('config_categoryids', $defaultcategories);

        $mform->addElement('static', 'configcategoryhint', '',
            get_string('config_categoryids_hint', 'block_report_customcajasan'));

        $courseid = 0;
        if ($iscoursecontext) {
            $courseid = $parentcontext->instanceid;
        }
        if (!empty($this->block->config) && !empty($this->block->config->courseid)) {
            $courseid = (int)$this->block->config->courseid;
        }

        $mform->addElement('hidden', 'config_courseid', $courseid);
        $mform->setType('config_courseid', PARAM_INT);
        $mform->setConstant('config_courseid', $courseid);
    }

    /**
     * Custom validation for the configuration form.
     *
     * @param array $data
     * @param array $files
     * @return array
     */
    public function validation($data, $files) {
        $errors = parent::validation($data, $files);

        if (!empty($data['config_categoryids']) && is_array($data['config_categoryids'])) {
            foreach ($data['config_categoryids'] as $categoryid) {
                if (!core_course_category::get($categoryid, IGNORE_MISSING)) {
                    $errors['config_categoryids'] = get_string('config_categoryids_invalid', 'block_report_customcajasan');
                    break;
                }
            }
        }

        return $errors;
    }
}
