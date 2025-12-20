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
 * Library functions for Q10 Grades Sync plugin.
 *
 * @package    local_q10grades
 * @copyright  2025 Your Institution
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Add navigation node to course settings.
 *
 * @param navigation_node $parentnode The parent navigation node.
 * @param stdClass $course The course object.
 * @param context_course $context The course context.
 * @return void
 */
function local_q10grades_extend_navigation_course($parentnode, $course, $context) {
    if (has_capability('local/q10grades:sync', $context)) {
        $url = new moodle_url('/local/q10grades/sync.php', ['courseid' => $course->id]);
        $parentnode->add(
            get_string('syncgrades', 'local_q10grades'),
            $url,
            navigation_node::TYPE_SETTING,
            null,
            'q10grades',
            new pix_icon('i/grades', '')
        );
    }
}

/**
 * Add link to course admin menu.
 *
 * @param settings_navigation $settingsnav The settings navigation object.
 * @param context $context The context.
 * @return void
 */
function local_q10grades_extend_settings_navigation(settings_navigation $settingsnav, context $context) {
    global $PAGE;

    if ($context->contextlevel != CONTEXT_COURSE) {
        return;
    }

    if (!has_capability('local/q10grades:sync', $context)) {
        return;
    }

    $courseadminnode = $settingsnav->find('courseadmin', navigation_node::TYPE_COURSE);
    if ($courseadminnode) {
        $url = new moodle_url('/local/q10grades/sync.php', ['courseid' => $context->instanceid]);
        $courseadminnode->add(
            get_string('syncgrades', 'local_q10grades'),
            $url,
            navigation_node::TYPE_SETTING,
            null,
            'local_q10grades_sync',
            new pix_icon('i/grades', '')
        );
    }
}

/**
 * Get the course grades for a specific course.
 *
 * @param int $courseid The course ID.
 * @param array $userids Optional array of user IDs to filter.
 * @return array Array of grades indexed by user ID.
 */
function local_q10grades_get_course_grades($courseid, $userids = []) {
    global $CFG;
    require_once($CFG->libdir . '/gradelib.php');

    $grades = [];
    $context = context_course::instance($courseid);

    // Get enrolled users.
    $enrolledusers = get_enrolled_users($context, '', 0, 'u.*', null, 0, 0, true);

    if (!empty($userids)) {
        $enrolledusers = array_filter($enrolledusers, function($user) use ($userids) {
            return in_array($user->id, $userids);
        });
    }

    // Get course grade item.
    $gradeitem = grade_item::fetch(['itemtype' => 'course', 'courseid' => $courseid]);

    if (!$gradeitem) {
        return $grades;
    }

    foreach ($enrolledusers as $user) {
        $grade = new grade_grade(['itemid' => $gradeitem->id, 'userid' => $user->id]);

        $grades[$user->id] = [
            'userid' => $user->id,
            'username' => $user->username,
            'email' => $user->email,
            'firstname' => $user->firstname,
            'lastname' => $user->lastname,
            'idnumber' => $user->idnumber,
            'finalgrade' => $grade->finalgrade,
            'rawgrade' => $grade->rawgrade,
            'grademax' => $gradeitem->grademax,
            'grademin' => $gradeitem->grademin,
            'gradepass' => $gradeitem->gradepass,
            'percentage' => $gradeitem->grademax > 0 ?
                round(($grade->finalgrade / $gradeitem->grademax) * 100, 2) : 0,
        ];
    }

    return $grades;
}

/**
 * Get all grade items for a course.
 *
 * @param int $courseid The course ID.
 * @return array Array of grade items.
 */
function local_q10grades_get_course_grade_items($courseid) {
    global $CFG;
    require_once($CFG->libdir . '/gradelib.php');

    $items = [];
    $gradeitems = grade_item::fetch_all(['courseid' => $courseid]);

    if ($gradeitems) {
        foreach ($gradeitems as $gradeitem) {
            $items[$gradeitem->id] = [
                'id' => $gradeitem->id,
                'itemname' => $gradeitem->itemname,
                'itemtype' => $gradeitem->itemtype,
                'itemmodule' => $gradeitem->itemmodule,
                'grademax' => $gradeitem->grademax,
                'grademin' => $gradeitem->grademin,
                'gradepass' => $gradeitem->gradepass,
                'idnumber' => $gradeitem->idnumber,
            ];
        }
    }

    return $items;
}
