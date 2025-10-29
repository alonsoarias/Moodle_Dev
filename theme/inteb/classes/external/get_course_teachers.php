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
 * External service to get all course teachers (both editing and non-editing)
 *
 * @package    theme_inteb
 * @copyright  2025 INTEB
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace theme_inteb\external;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/externallib.php');

use external_api;
use external_function_parameters;
use external_value;
use external_single_structure;
use external_multiple_structure;
use context_course;

/**
 * External service to get all course teachers
 */
class get_course_teachers extends external_api {

    /**
     * Returns description of method parameters
     *
     * @return external_function_parameters
     */
    public static function execute_parameters() {
        return new external_function_parameters([
            'courseid' => new external_value(PARAM_INT, 'Course ID'),
        ]);
    }

    /**
     * Get all teachers (both editing and non-editing) for a course
     *
     * @param int $courseid Course ID
     * @return array Array of teachers with their information
     */
    public static function execute($courseid) {
        global $DB, $OUTPUT, $CFG, $USER;

        $params = self::validate_parameters(self::execute_parameters(), [
            'courseid' => $courseid,
        ]);

        $courseid = $params['courseid'];

        // Validate course exists
        $course = $DB->get_record('course', ['id' => $courseid], '*', MUST_EXIST);
        $coursecontext = context_course::instance($courseid);

        // Check if user can view this course
        self::validate_context($coursecontext);
        require_capability('moodle/course:view', $coursecontext);

        // Get user groups for separate groups mode
        $usergroups = groups_get_user_groups($courseid, $USER->id);
        $groupids = 0;
        if ($course->groupmode == 1) { // Separate groups
            $groupids = $usergroups[0];
        }

        $teachers = [];

        // Get editing teacher role
        $editingteacherrole = $DB->get_record('role', ['shortname' => 'editingteacher']);
        if ($editingteacherrole) {
            $editingteachers = get_role_users(
                $editingteacherrole->id,
                $coursecontext,
                true,
                'u.*',
                'u.firstname',
                true,
                $groupids
            );
            $teachers = array_merge($teachers, $editingteachers);
        }

        // Get non-editing teacher role
        $teacherrole = $DB->get_record('role', ['shortname' => 'teacher']);
        if ($teacherrole) {
            $nonediting = get_role_users(
                $teacherrole->id,
                $coursecontext,
                true,
                'u.*',
                'u.firstname',
                true,
                $groupids
            );
            $teachers = array_merge($teachers, $nonediting);
        }

        // Remove duplicates (in case user has both roles)
        $uniqueteachers = [];
        foreach ($teachers as $teacher) {
            if (!isset($uniqueteachers[$teacher->id])) {
                $uniqueteachers[$teacher->id] = $teacher;
            }
        }
        $teachers = array_values($uniqueteachers);

        // Sort by firstname
        usort($teachers, function($a, $b) {
            return strcmp($a->firstname, $b->firstname);
        });

        // Build result array
        $result = [];
        $limit = 4; // Show maximum 4 teachers in header
        $count = 0;

        foreach ($teachers as $teacher) {
            if ($count < $limit) {
                $result[] = [
                    'id' => $teacher->id,
                    'name' => fullname($teacher, true),
                    'avatar' => $OUTPUT->user_picture($teacher, ['size' => 50]),
                    'profileurl' => $CFG->wwwroot . '/user/profile.php?id=' . $teacher->id,
                ];
                $count++;
            }
        }

        $totalcount = count($teachers);
        $hasmore = $totalcount > $limit;
        $morecount = $hasmore ? $totalcount - $limit : 0;

        return [
            'teachers' => $result,
            'totalcount' => $totalcount,
            'hasmore' => $hasmore,
            'morecount' => $morecount,
            'participantsurl' => $CFG->wwwroot . '/user/index.php?id=' . $courseid,
        ];
    }

    /**
     * Returns description of method result value
     *
     * @return external_single_structure
     */
    public static function execute_returns() {
        return new external_single_structure([
            'teachers' => new external_multiple_structure(
                new external_single_structure([
                    'id' => new external_value(PARAM_INT, 'User ID'),
                    'name' => new external_value(PARAM_TEXT, 'Full name'),
                    'avatar' => new external_value(PARAM_RAW, 'Avatar HTML'),
                    'profileurl' => new external_value(PARAM_URL, 'Profile URL'),
                ])
            ),
            'totalcount' => new external_value(PARAM_INT, 'Total number of teachers'),
            'hasmore' => new external_value(PARAM_BOOL, 'Whether there are more teachers'),
            'morecount' => new external_value(PARAM_INT, 'Number of additional teachers'),
            'participantsurl' => new external_value(PARAM_URL, 'Participants page URL'),
        ]);
    }
}
