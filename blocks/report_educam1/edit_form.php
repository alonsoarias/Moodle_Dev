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
 * Block configuration form for report_educam1.
 *
 * @package    block_report_educam1
 * @copyright  2025 IngeWeb - Soluciones para triunfar en Internet
 * @author     Alonso Arias <soporte@ingeweb.co>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');
require_once($CFG->dirroot . '/course/lib.php');
require_once($CFG->libdir . '/outputcomponents.php');

/**
 * Configuration form definition.
 */
class block_report_educam1_edit_form extends block_edit_form {

    /**
     * Adds custom form fields.
     *
     * @param moodleform $mform
     */
    protected function specific_definition($mform) {
        global $PAGE, $COURSE;

        $parentcontext = context::instance_by_id($this->block->instance->parentcontextid, IGNORE_MISSING);
        if (!$parentcontext) {
            $parentcontext = $PAGE->context;
        }

        $iscoursecontext = ($parentcontext->contextlevel === CONTEXT_COURSE && $parentcontext->instanceid != SITEID);

        if (!$iscoursecontext) {
            $mform->addElement('static', 'error', '',
                html_writer::div(get_string('config_only_course', 'block_report_educam1'), 'alert alert-warning'));
            return;
        }

        require_capability('moodle/course:update', $parentcontext);

        $mform->addElement('header', 'configheader', get_string('config_header', 'block_report_educam1'));

        // Course context info
        $course = get_course($parentcontext->instanceid);
        $coursename = format_string($course->fullname);
        $contextinfo = get_string('config_context_course', 'block_report_educam1', $coursename);
        $mform->addElement('static', 'contextinfo', get_string('config_context_label', 'block_report_educam1'), $contextinfo);

        // Activity type selector
        $activitytypes = $this->get_available_activity_types($course->id);

        if (empty($activitytypes)) {
            $mform->addElement('static', 'noactivities', '',
                html_writer::div(get_string('config_no_activities', 'block_report_educam1'), 'alert alert-info'));
        } else {
            $mform->addElement('select', 'config_activitytype',
                get_string('config_activitytype', 'block_report_educam1'), $activitytypes);
            $mform->addHelpButton('config_activitytype', 'config_activitytype', 'block_report_educam1');
            $mform->setType('config_activitytype', PARAM_TEXT);

            $defaultactivitytype = '';
            if (!empty($this->block->config) && !empty($this->block->config->activitytype)) {
                $defaultactivitytype = $this->block->config->activitytype;
            }
            $mform->setDefault('config_activitytype', $defaultactivitytype);

            $mform->addElement('static', 'confighint', '',
                html_writer::div(get_string('config_activitytype_hint', 'block_report_educam1'), 'text-muted'));
        }

        // Hidden courseid field
        $courseid = $parentcontext->instanceid;
        $mform->addElement('hidden', 'config_courseid', $courseid);
        $mform->setType('config_courseid', PARAM_INT);
        $mform->setConstant('config_courseid', $courseid);
    }

    /**
     * Get available activity types in the course.
     *
     * @param int $courseid
     * @return array
     */
    protected function get_available_activity_types($courseid) {
        global $DB;

        $sql = "SELECT DISTINCT m.name
                FROM {course_modules} cm
                JOIN {modules} m ON m.id = cm.module
                WHERE cm.course = :courseid
                  AND cm.deletioninprogress = 0
                  AND m.visible = 1
                ORDER BY m.name";

        $modules = $DB->get_records_sql($sql, ['courseid' => $courseid]);

        $activitytypes = ['' => get_string('config_select_activity', 'block_report_educam1')];

        foreach ($modules as $module) {
            $modname = $module->name;
            // Get the proper module name from language strings
            $activityname = get_string('modulename', 'mod_' . $modname);
            $activitytypes[$modname] = $activityname;
        }

        return $activitytypes;
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

        if (empty($data['config_activitytype'])) {
            $errors['config_activitytype'] = get_string('config_activitytype_required', 'block_report_educam1');
        }

        return $errors;
    }
}
