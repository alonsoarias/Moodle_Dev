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
 * Block definition class for report_customcajasan.
 *
 * @package    block_report_customcajasan
 * @copyright  2025 Cajasan
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/course/lib.php');
require_once(__DIR__ . '/lib.php');

/**
 * Class block_report_customcajasan.
 */
class block_report_customcajasan extends block_base {

    /**
     * Initialize the block.
     */
    public function init() {
        $this->title = get_string('pluginname', 'block_report_customcajasan');
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
     *
     * @return array
     */
    public function applicable_formats() {
        return [
            'admin' => true,
            'site-index' => true,
            'my' => true,
            'course-view' => true,
            'course-index' => true,
            'mod' => false
        ];
    }

    /**
     * Only one instance per page.
     *
     * @return bool
     */
    public function instance_allow_multiple() {
        return false;
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
        $systemcontext = context_system::instance();
        $coursecontext = $contextinfo['incourse'] ? context_course::instance($contextinfo['courseid']) : null;

        if ($contextinfo['incourse']) {
            $blockcontext = context_block::instance($this->instance->id);
            $hasviewblock = has_capability('block/report_customcajasan:viewblock', $blockcontext);
            $hasviewreport = has_capability('block/report_customcajasan:viewreport', $coursecontext);
            $caneditcourse = has_capability('moodle/course:update', $coursecontext);
            $hasrequiredpermissions = ($hasviewblock && $hasviewreport && $caneditcourse);
        } else {
            $hasviewreport = has_capability('block/report_customcajasan:viewreport', $systemcontext);
            $canmanage = has_capability('block/report_customcajasan:manageblock', $systemcontext);
            $hasrequiredpermissions = ($hasviewreport && $canmanage);
        }

        if (!$hasrequiredpermissions) {
            $this->content->text = html_writer::div(
                get_string('block_no_access', 'block_report_customcajasan'),
                'alert alert-info'
            );
            return $this->content;
        }

        $categoryids = $this->get_configured_category_ids();
        $forcedcourseids = $this->get_forced_course_ids($contextinfo);
        $scope = report_customcajasan_effective_scope($categoryids, $forcedcourseids, null, null);
        $categorynames = [];
        if (!empty($categoryids)) {
            list($insql, $params) = $DB->get_in_or_equal($categoryids, SQL_PARAMS_NAMED);
            $records = $DB->get_records_sql("SELECT id, name FROM {course_categories} WHERE id {$insql}", $params);
            foreach ($categoryids as $cid) {
                if (!empty($records[$cid])) {
                    $categorynames[] = format_string($records[$cid]->name, true, ['context' => $systemcontext]);
                }
            }
        }

        $summarylines = [];
        if ($contextinfo['incourse']) {
            $summarylines[] = get_string('block_scope_current_course', 'block_report_customcajasan',
                format_string($contextinfo['coursename'], true, ['context' => $coursecontext]));
        } else if ($contextinfo['isuserscontext']) {
            $summarylines[] = get_string('block_scope_my', 'block_report_customcajasan');
        } else {
            $summarylines[] = get_string('block_scope_system', 'block_report_customcajasan');
        }

        if (!empty($categorynames)) {
            $summarylines[] = get_string('block_scope_categories', 'block_report_customcajasan');
            $summarylines[] = html_writer::alist($categorynames, [], 'ul');
        } else if ($contextinfo['incourse']) {
            $summarylines[] = html_writer::div(
                get_string('block_scope_course_only', 'block_report_customcajasan'),
                'small text-muted'
            );
        } else {
            $summarylines[] = html_writer::div(
                get_string('block_scope_none', 'block_report_customcajasan'),
                'alert alert-warning mb-2'
            );
        }

        if (!empty($scope['courseids'])) {
            $summarylines[] = html_writer::div(
                get_string('block_scope_coursecount', 'block_report_customcajasan', count($scope['courseids'])),
                'small text-muted'
            );
        }

        $this->content->text = html_writer::div(implode('', $summarylines), 'block_report_customcajasan-summary');

        $linkparams = ['instanceid' => $this->instance->id];
        if ($contextinfo['incourse']) {
            $linkparams['courseid'] = $contextinfo['courseid'];
            $linkparams['coursecontext'] = 1;
        }

        $reporturl = new moodle_url('/blocks/report_customcajasan/report.php', $linkparams);
        $linktext = $contextinfo['incourse']
            ? get_string('block_link_label_course', 'block_report_customcajasan')
            : get_string('block_link_label', 'block_report_customcajasan');

        $this->content->text .= html_writer::div(
            html_writer::link($reporturl, $linktext, ['class' => 'btn btn-primary w-100 mt-3']),
            'block_report_customcajasan-actions'
        );

        if (!$contextinfo['incourse'] && empty($categorynames)) {
            $this->content->footer = html_writer::div(
                get_string('block_scope_configure_notice', 'block_report_customcajasan'),
                'small text-muted mt-2'
            );
        }

        return $this->content;
    }

    /**
     * Determine the list of courses that must be enforced for the report scope.
     *
     * @param array $contextinfo
     * @return array
     */
    protected function get_forced_course_ids(array $contextinfo): array {
        $courseids = [];

        if (!empty($contextinfo['incourse']) && !empty($contextinfo['courseid'])) {
            $courseids[] = (int)$contextinfo['courseid'];
        }

        if (!empty($this->config->courseid)) {
            $courseids[] = (int)$this->config->courseid;
        }

        return report_customcajasan_normalize_id_list($courseids);
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
            'isuserscontext' => ($parentcontext->contextlevel === CONTEXT_USER)
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

    /**
     * Normalise configured category ids.
     *
     * @return array
     */
    protected function get_configured_category_ids(): array {
        if (empty($this->config) || empty($this->config->categoryids)) {
            return [];
        }

        $ids = (array)$this->config->categoryids;
        $ids = array_map('intval', $ids);
        $ids = array_filter($ids, static function($id) {
            return $id > 0;
        });

        return array_values(array_unique($ids));
    }
}
