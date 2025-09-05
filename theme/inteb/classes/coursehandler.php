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
 * Course handler overrides for theme_inteb.
 *
 * Extends the RemUI course handler so that instructor details on
 * course cards include both editing teachers and non‑editing teachers
 * and exposes an instructorcount matching RemUI's implementation.
 *
 * @package   theme_inteb
 * @copyright 2024
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use theme_remui\utility;

require_once($CFG->dirroot . '/theme/remui/classes/coursehandler.php');

class theme_inteb_coursehandler extends theme_remui_coursehandler {
    /**
     * Retrieve courses and inject instructor information for both
     * editing teachers and teachers.
     *
     * @param int|bool $totalcount Return count only.
     * @param string|null $search Search term.
     * @param int|array|null $category Category filter.
     * @param int $limitfrom Offset for paging.
     * @param int $limitto Limit for paging.
     * @param array|null $mycourses My courses filter.
     * @param string|null $categorysort Sort order.
     * @param array $courses Pre-fetched courses.
     * @param bool $filtermodified Filter modified courses.
     * @param array $filteredcourseids Filtered course IDs.
     * @param bool $isfilterapplied Filter flag.
     * @return array|int Courses array or total count.
     */
    public function get_courses(
        $totalcount = false,
        $search = null,
        $category = null,
        $limitfrom = 0,
        $limitto = 0,
        $mycourses = null,
        $categorysort = null,
        $courses = [],
        $filtermodified = false,
        $filteredcourseids = [],
        $isfilterapplied = false
    ) {
        global $CFG, $DB;

        $result = parent::get_courses(
            $totalcount,
            $search,
            $category,
            $limitfrom,
            $limitto,
            $mycourses,
            $categorysort,
            $courses,
            $filtermodified,
            $filteredcourseids,
            $isfilterapplied
        );

        if ($totalcount !== false) {
            // Parent returned array($coursecount, $coursesarray).
            list($coursecount, $coursesarray) = $result;
            $coursesarray = $this->append_instructors($coursesarray);
            return [$coursecount, $coursesarray];
        }

        $coursesarray = $this->append_instructors($result);
        return $coursesarray;
    }

    /**
     * Append instructor and instructorcount fields to courses array.
     *
     * @param array $coursesarray Courses array from parent handler.
     * @return array Modified courses array.
     */
    protected function append_instructors(array $coursesarray): array {
        global $CFG, $DB;

        foreach ($coursesarray as &$course) {
            $context = \context_course::instance($course['courseid']);
            $roles = $DB->get_records_list('role', 'shortname', ['editingteacher', 'teacher'], '', 'id');
            $instructors = [];
            foreach ($roles as $role) {
                $users = get_role_users($role->id, $context, false, 'u.*');
                foreach ($users as $user) {
                    $instructors[$user->id] = $user; // Ensure uniqueness.
                }
              }
            if ($instructors) {
                $users = array_values($instructors);
                $maxshown = 4;
                $shown = [];
                foreach (array_slice($users, 0, $maxshown) as $user) {
                    $shown[] = [
                        'name' => fullname($user, true),
                        'url' => $CFG->wwwroot . '/user/profile.php?id=' . $user->id,
                        'picture' => utility::get_user_picture($user),
                        'imgStyle' => ''
                    ];
                }
                $course['instructors'] = $shown;
                $remaining = count($users) - count($shown);
                $course['instructorcount'] = $remaining > 0 ? $remaining : '';
            } else {
                $course['instructors'] = [];
                $course['instructorcount'] = '';
            }

            // Inject remui custom field course image if available.
            $handler = \core_customfield\handler::get_handler('core_course', 'course');
            $fields = $handler->get_instance_fields($course['courseid']);
            foreach ($fields as $data) {
                if ($data->get_field()->get('shortname') === 'remuicourseimage') {
                    $course['remuicourseimage'] = $data->export_value();
                    break;
                }
            }
        }
        unset($course);

        return $coursesarray;
    }

    /**
     * Get enrolled teachers (editing and non-editing) for templates.
     *
     * @param \stdClass $course Course object.
     * @param bool $frontlineteacher Limit to frontline teachers.
     * @return array Context containing instructor info.
     */
    public function get_enrolled_teachers_context($course, $frontlineteacher = false): array {
        global $OUTPUT, $CFG, $USER, $DB;

        $courseid = $course->id;

        $usergroups = groups_get_user_groups($courseid, $USER->id);
        $groupids = 0;
        if ($course->groupmode == 1) {
            $groupids = $usergroups[0];
        }

        $coursecontext = \context_course::instance($courseid);
        $roles = $DB->get_records_list('role', 'shortname', ['editingteacher', 'teacher'], '', 'id');

        $teachers = [];
        foreach ($roles as $role) {
            $users = get_role_users($role->id, $coursecontext, false, 'u.*', 'firstname', true, $groupids);
            foreach ($users as $user) {
                $teachers[$user->id] = $user;
            }
        }

        $context = [];
        if ($teachers) {
            $namescount = 4;
            $profilecount = 0;
            foreach ($teachers as $teacher) {
                if ($frontlineteacher && $profilecount < $namescount) {
                    $instructor = [];
                    $instructor['id'] = $teacher->id;
                    $instructor['name'] = fullname($teacher, true);
                    $instructor['avatars'] = $OUTPUT->user_picture($teacher);
                    $instructor['teacherprofileurl'] = $CFG->wwwroot . '/user/profile.php?id=' . $teacher->id;
                    if ($profilecount != 0) {
                        $instructor['hasanother'] = true;
                    }
                    $context['instructors'][] = $instructor;
                }
                $profilecount++;
            }
            if ($profilecount > $namescount) {
                $context['teachercount'] = $profilecount - $namescount;
            }
            $role = reset($roles);
            $context['participantspageurl'] = $CFG->wwwroot . '/user/index.php?id=' . $courseid . '&roleid=' . $role->id;
            $context['hasteachers'] = true;
        }
        return $context;
    }
}
