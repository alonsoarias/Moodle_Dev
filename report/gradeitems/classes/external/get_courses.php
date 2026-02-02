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
 * External function to get courses with gradeable activities.
 *
 * @package    report_gradeitems
 * @copyright  2026 Alonso Arias <soporte@orioncloud.com.co>
 * @author     Alonso Arias <soporte@orioncloud.com.co>
 * @link       https://orioncloud.com.co
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace report_gradeitems\external;

defined('MOODLE_INTERNAL') || die;

require_once($CFG->libdir . '/externallib.php');

use external_api;
use external_function_parameters;
use external_value;
use external_single_structure;
use external_multiple_structure;
use context_system;
use moodle_url;
use html_writer;

/**
 * External function to get courses with gradeable activities.
 */
class get_courses extends external_api {

    /**
     * Define parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'categoryid' => new external_value(PARAM_INT, 'Category ID filter', VALUE_DEFAULT, 0),
            'courseid' => new external_value(PARAM_INT, 'Course ID filter', VALUE_DEFAULT, 0),
            'visibility' => new external_value(PARAM_RAW, 'Visibility filter', VALUE_DEFAULT, ''),
            'page' => new external_value(PARAM_INT, 'Page number', VALUE_DEFAULT, 0),
            'perpage' => new external_value(PARAM_INT, 'Results per page', VALUE_DEFAULT, 25),
        ]);
    }

    /**
     * Execute the function.
     *
     * @param int $categoryid
     * @param int $courseid
     * @param string $visibility
     * @param int $page
     * @param int $perpage
     * @return array
     */
    public static function execute(int $categoryid, int $courseid, string $visibility, int $page, int $perpage): array {
        global $DB;

        // Validate parameters.
        $params = self::validate_parameters(self::execute_parameters(), [
            'categoryid' => $categoryid,
            'courseid' => $courseid,
            'visibility' => $visibility,
            'page' => $page,
            'perpage' => $perpage,
        ]);

        // Check capability.
        $context = context_system::instance();
        self::validate_context($context);
        require_capability('report/gradeitems:view', $context);

        // Build the SQL query with filters.
        $sqlparams = [];
        $where = ['c.id > 1'];

        if (!empty($params['categoryid'])) {
            $categoryids = self::get_category_and_children($params['categoryid']);
            list($insql, $inparams) = $DB->get_in_or_equal($categoryids, SQL_PARAMS_NAMED, 'cat');
            $where[] = "c.category $insql";
            $sqlparams = array_merge($sqlparams, $inparams);
        }

        if (!empty($params['courseid'])) {
            $where[] = 'c.id = :courseid';
            $sqlparams['courseid'] = $params['courseid'];
        }

        if ($params['visibility'] !== '') {
            $where[] = 'c.visible = :visibility';
            $sqlparams['visibility'] = (int)$params['visibility'];
        }

        $wheresql = implode(' AND ', $where);
        $sqlparams['timenow'] = time();

        // Main SQL query.
        $sql = "SELECT
                    c.id AS courseid,
                    c.shortname AS courseshortname,
                    c.fullname AS coursefullname,
                    c.visible AS coursevisible,
                    cc.id AS categoryid,
                    cc.name AS categoryname,
                    (SELECT COUNT(DISTINCT ue.userid)
                     FROM {user_enrolments} ue
                     JOIN {enrol} e ON e.id = ue.enrolid
                     WHERE e.courseid = c.id
                       AND e.status = 0
                       AND ue.status = 0
                       AND (ue.timeend = 0 OR ue.timeend > :timenow)
                    ) AS enrolledstudents,
                    (SELECT COUNT(cm.id)
                     FROM {course_modules} cm
                     WHERE cm.course = c.id
                       AND cm.deletioninprogress = 0
                       AND cm.visible = 1
                    ) AS totalactivities,
                    (SELECT COUNT(gi.id)
                     FROM {grade_items} gi
                     JOIN {modules} m ON m.name = gi.itemmodule
                     JOIN {course_modules} cm ON cm.course = gi.courseid
                                             AND cm.module = m.id
                                             AND cm.instance = gi.iteminstance
                     WHERE gi.courseid = c.id
                       AND gi.itemtype = 'mod'
                       AND gi.gradetype > 0
                       AND cm.visible = 1
                       AND cm.deletioninprogress = 0
                    ) AS gradeableactivities,
                    (SELECT COUNT(gi.id)
                     FROM {grade_items} gi
                     JOIN {modules} m ON m.name = gi.itemmodule
                     JOIN {course_modules} cm ON cm.course = gi.courseid
                                             AND cm.module = m.id
                                             AND cm.instance = gi.iteminstance
                     WHERE gi.courseid = c.id
                       AND gi.itemtype = 'mod'
                       AND gi.gradetype > 0
                       AND cm.visible = 0
                       AND cm.deletioninprogress = 0
                    ) AS hiddengradeableactivities
                FROM {course} c
                JOIN {course_categories} cc ON cc.id = c.category
               WHERE $wheresql
            ORDER BY cc.name, c.fullname";

        // Count total records.
        $countsql = "SELECT COUNT(c.id)
                       FROM {course} c
                       JOIN {course_categories} cc ON cc.id = c.category
                      WHERE $wheresql";
        $countparams = array_diff_key($sqlparams, ['timenow' => '']);
        $totalcount = $DB->count_records_sql($countsql, $countparams);

        // Get records.
        if ($params['perpage'] > 0) {
            $records = $DB->get_records_sql($sql, $sqlparams, $params['page'] * $params['perpage'], $params['perpage']);
        } else {
            $records = $DB->get_records_sql($sql, $sqlparams);
        }

        // Get teachers.
        $courseids = array_keys($records);
        $teachersbycourse = self::get_teachers_by_course($courseids);

        // Format results.
        $courses = [];
        foreach ($records as $record) {
            $teachers = isset($teachersbycourse[$record->courseid]) ? $teachersbycourse[$record->courseid] : '-';

            $courseurl = new moodle_url('/course/view.php', ['id' => $record->courseid]);
            $detailurl = new moodle_url('/report/gradeitems/course.php', ['id' => $record->courseid]);

            $gradeabletext = (string)$record->gradeableactivities;
            if ($record->hiddengradeableactivities > 0) {
                $gradeabletext .= ' (' . $record->hiddengradeableactivities . ' ' .
                    get_string('hidden', 'report_gradeitems') . ')';
            }

            $courses[] = [
                'courseid' => $record->courseid,
                'categoryname' => format_string($record->categoryname),
                'courseshortname' => format_string($record->courseshortname),
                'coursefullname' => format_string($record->coursefullname),
                'courseurl' => $courseurl->out(false),
                'coursevisible' => $record->coursevisible ? get_string('yes', 'report_gradeitems') : get_string('no', 'report_gradeitems'),
                'enrolledstudents' => (int)$record->enrolledstudents,
                'teachers' => $teachers,
                'totalactivities' => (int)$record->totalactivities,
                'gradeableactivities' => $gradeabletext,
                'detailurl' => $detailurl->out(false),
            ];
        }

        // Calculate pagination info.
        if ($params['perpage'] > 0) {
            $from = ($params['page'] * $params['perpage']) + 1;
            $to = min(($params['page'] + 1) * $params['perpage'], $totalcount);
        } else {
            $from = 1;
            $to = $totalcount;
        }

        return [
            'courses' => $courses,
            'totalcount' => $totalcount,
            'page' => $params['page'],
            'perpage' => $params['perpage'],
            'from' => $totalcount > 0 ? $from : 0,
            'to' => $to,
            'haspages' => $params['perpage'] > 0 && $totalcount > $params['perpage'],
            'totalpages' => $params['perpage'] > 0 ? ceil($totalcount / $params['perpage']) : 1,
        ];
    }

    /**
     * Define return structure.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'courses' => new external_multiple_structure(
                new external_single_structure([
                    'courseid' => new external_value(PARAM_INT, 'Course ID'),
                    'categoryname' => new external_value(PARAM_TEXT, 'Category name'),
                    'courseshortname' => new external_value(PARAM_TEXT, 'Course short name'),
                    'coursefullname' => new external_value(PARAM_TEXT, 'Course full name'),
                    'courseurl' => new external_value(PARAM_URL, 'Course URL'),
                    'coursevisible' => new external_value(PARAM_TEXT, 'Course visibility'),
                    'enrolledstudents' => new external_value(PARAM_INT, 'Enrolled students count'),
                    'teachers' => new external_value(PARAM_RAW, 'Teachers list'),
                    'totalactivities' => new external_value(PARAM_INT, 'Total activities count'),
                    'gradeableactivities' => new external_value(PARAM_RAW, 'Gradeable activities count'),
                    'detailurl' => new external_value(PARAM_URL, 'Detail page URL'),
                ])
            ),
            'totalcount' => new external_value(PARAM_INT, 'Total records count'),
            'page' => new external_value(PARAM_INT, 'Current page'),
            'perpage' => new external_value(PARAM_INT, 'Results per page'),
            'from' => new external_value(PARAM_INT, 'From record number'),
            'to' => new external_value(PARAM_INT, 'To record number'),
            'haspages' => new external_value(PARAM_BOOL, 'Has multiple pages'),
            'totalpages' => new external_value(PARAM_INT, 'Total pages'),
        ]);
    }

    /**
     * Get category and all its children IDs.
     *
     * @param int $categoryid
     * @return array
     */
    private static function get_category_and_children(int $categoryid): array {
        global $DB;

        $ids = [$categoryid];
        $children = $DB->get_records('course_categories', ['parent' => $categoryid], '', 'id');
        foreach ($children as $child) {
            $ids = array_merge($ids, self::get_category_and_children($child->id));
        }

        return $ids;
    }

    /**
     * Get teachers for multiple courses.
     *
     * @param array $courseids
     * @return array
     */
    private static function get_teachers_by_course(array $courseids): array {
        global $DB;

        if (empty($courseids)) {
            return [];
        }

        list($insql, $params) = $DB->get_in_or_equal($courseids, SQL_PARAMS_NAMED, 'course');

        $sql = "SELECT DISTINCT ctx.instanceid AS courseid,
                       u.id AS userid,
                       u.firstname,
                       u.lastname
                  FROM {role_assignments} ra
                  JOIN {context} ctx ON ctx.id = ra.contextid
                  JOIN {user} u ON u.id = ra.userid
                 WHERE ctx.contextlevel = :contextlevel
                   AND ctx.instanceid $insql
                   AND ra.roleid IN (3, 4)
              ORDER BY ctx.instanceid, u.lastname, u.firstname";

        $params['contextlevel'] = CONTEXT_COURSE;

        $teachers = $DB->get_records_sql($sql, $params);

        $result = [];
        foreach ($teachers as $teacher) {
            if (!isset($result[$teacher->courseid])) {
                $result[$teacher->courseid] = [];
            }
            $result[$teacher->courseid][] = fullname($teacher);
        }

        foreach ($result as $cid => $names) {
            $result[$cid] = implode(', ', $names);
        }

        return $result;
    }
}
