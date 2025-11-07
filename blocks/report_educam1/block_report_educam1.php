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
 * Block definition class for report_educam1.
 *
 * @package    block_report_educam1
 * @copyright  2025 IngeWeb - Soluciones para triunfar en Internet
 * @author     Alonso Arias <soporte@ingeweb.co>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/course/lib.php');
require_once(__DIR__ . '/lib.php');

/**
 * Class block_report_educam1.
 */
class block_report_educam1 extends block_base {

    /**
     * Initialize the block.
     */
    public function init() {
        $this->title = get_string('pluginname', 'block_report_educam1');
    }

    /**
     * Ensure instance level configuration is enabled.
     *
     * @return bool
     */
    public function instance_allow_config() {
        return true;
    }

    /**
     * Determine formats where the block is allowed.
     * Only allow in course contexts.
     *
     * @return array
     */
    public function applicable_formats() {
        return [
            'admin' => false,
            'site-index' => false,
            'my' => false,
            'course-view' => true,
            'course-index' => false,
            'mod' => false
        ];
    }

    /**
     * Allow multiple instances per page.
     *
     * @return bool
     */
    public function instance_allow_multiple() {
        return true;
    }

    /**
     * No global configuration page.
     *
     * @return bool
     */
    public function has_config() {
        return false;
    }

    /**
     * Persist the detected course id when the block lives inside a course.
     */
    public function specialization() {
        if (empty($this->config)) {
            $this->config = new stdClass();
        }

        $contextinfo = $this->resolve_context_info();
        if ($contextinfo['incourse'] && empty($this->config->courseid)) {
            $this->config->courseid = $contextinfo['courseid'];
            $this->instance_config_save($this->config);
        }
    }

    /**
     * Get the block content.
     *
     * @return stdClass
     */
    public function get_content() {
        global $DB;

        if ($this->content !== null) {
            return $this->content;
        }

        $this->content = new stdClass();
        $this->content->text = '';
        $this->content->footer = '';

        if (!isloggedin() || isguestuser()) {
            return $this->content;
        }

        $contextinfo = $this->resolve_context_info();

        // Only show in course context
        if (!$contextinfo['incourse']) {
            $this->content->text = html_writer::div(
                get_string('block_only_course', 'block_report_educam1'),
                'alert alert-warning'
            );
            return $this->content;
        }

        $coursecontext = context_course::instance($contextinfo['courseid']);

        // Check permissions
        $caneditcourse = has_capability('moodle/course:update', $coursecontext);
        $hasviewreport = has_capability('block/report_educam1:viewreport', $coursecontext);
        $hasrequiredpermissions = ($caneditcourse || $hasviewreport);

        if (!$hasrequiredpermissions) {
            $this->content->text = html_writer::div(
                get_string('block_no_access', 'block_report_educam1'),
                'alert alert-info'
            );
            return $this->content;
        }

        // Get activity type configuration
        $activitytype = !empty($this->config->activitytype) ? $this->config->activitytype : '';

        $summarylines = [];
        $summarylines[] = get_string('block_scope_current_course', 'block_report_educam1',
            format_string($contextinfo['coursename'], true, ['context' => $coursecontext]));

        if (!empty($activitytype)) {
            $activityname = report_educam1_get_activity_type_name($activitytype);
            $summarylines[] = html_writer::div(
                get_string('block_activity_type', 'block_report_educam1', $activityname),
                'small text-muted mt-1'
            );
        } else {
            $summarylines[] = html_writer::div(
                get_string('block_no_activity_configured', 'block_report_educam1'),
                'alert alert-warning mt-2'
            );
        }

        $this->content->text = html_writer::div(implode('', $summarylines), 'block_report_educam1-summary');

        if (!empty($activitytype)) {
            $linkparams = [
                'instanceid' => $this->instance->id,
                'courseid' => $contextinfo['courseid']
            ];

            $reporturl = new moodle_url('/blocks/report_educam1/report.php', $linkparams);
            $linktext = get_string('block_link_label', 'block_report_educam1');

            $this->content->text .= html_writer::div(
                html_writer::link($reporturl, $linktext, ['class' => 'btn btn-primary w-100 mt-3']),
                'block_report_educam1-actions'
            );
        } else {
            $this->content->footer = html_writer::div(
                get_string('block_configure_notice', 'block_report_educam1'),
                'small text-muted mt-2'
            );
        }

        return $this->content;
    }

    /**
     * Resolve contextual information for the current block instance.
     *
     * @return array
     */
    protected function resolve_context_info(): array {
        global $COURSE;

        $parentcontext = context::instance_by_id($this->instance->parentcontextid, IGNORE_MISSING);
        if (!$parentcontext) {
            $parentcontext = $this->page->context;
        }

        $info = [
            'parentcontext' => $parentcontext,
            'contextlevel' => $parentcontext->contextlevel,
            'courseid' => 0,
            'coursename' => '',
            'incourse' => false,
        ];

        if ($parentcontext->contextlevel === CONTEXT_COURSE) {
            $info['courseid'] = (int)$parentcontext->instanceid;
            $info['incourse'] = ($info['courseid'] !== SITEID);
            if ($info['courseid'] && $info['courseid'] !== SITEID) {
                if (!empty($COURSE) && $COURSE->id == $info['courseid']) {
                    $info['coursename'] = $COURSE->fullname;
                } else {
                    $course = get_course($info['courseid']);
                    $info['coursename'] = $course->fullname;
                }
            }
        } else if (!empty($this->config->courseid)) {
            $info['courseid'] = (int)$this->config->courseid;
            $info['coursename'] = $info['courseid'] ? get_course($info['courseid'])->fullname : '';
        }

        return $info;
    }
}
