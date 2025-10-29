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
 * Course handler for theme_inteb
 *
 * Extends the parent theme_remui coursehandler to customize teacher display behavior.
 * This override allows showing ALL teachers (both editingteacher and teacher roles)
 * instead of only teachers with editing permissions.
 *
 * @package   theme_inteb
 * @copyright 2025 Soporte IngeWeb <soporte@ingeweb.co>
 * @author    Pedro Alonso Arias Balcucho
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace theme_inteb;

defined('MOODLE_INTERNAL') || die();

// Load parent coursehandler class.
require_once($CFG->dirroot . '/theme/remui/classes/coursehandler.php');

/**
 * Extended coursehandler class for theme_inteb
 *
 * This class extends the theme_remui_coursehandler to modify the behavior
 * of teacher retrieval in courses, specifically to include both editing
 * and non-editing teachers.
 */
class coursehandler extends \theme_remui_coursehandler {

    /**
     * Get Enrolled Teachers Context
     *
     * Override of parent method to retrieve ALL teachers (editingteacher AND teacher roles)
     * instead of filtering by 'mod/folder:managefiles' capability which only editing teachers have.
     *
     * This method respects the course groupmode and only shows teachers from the user's groups
     * when separate groups mode is enabled and the user doesn't have access to all groups.
     *
     * @param object $course Course object
     * @param boolean $frontlineteacher Whether to show only frontline teacher details
     * @return Array Context array with teacher information
     */
    public function get_enrolled_teachers_context($course, $frontlineteacher = false) {
        global $OUTPUT, $CFG, $USER, $DB;

        $courseid = $course->id;
        $coursecontext = \context_course::instance($courseid);

        // Get user's groups for this course.
        $usergroups = groups_get_user_groups($courseid, $USER->id);
        $groupids = 0;

        // If course is in separate groups mode, filter by user's groups.
        if ($course->groupmode == 1) {
            $groupids = $usergroups[0];
        }

        // Get both editingteacher and teacher roles.
        $editingteacherrole = $DB->get_record('role', array('shortname' => 'editingteacher'));
        $teacherrole = $DB->get_record('role', array('shortname' => 'teacher'));

        $teachers = array();

        // Get editing teachers.
        if ($editingteacherrole) {
            $editingteachers = get_role_users(
                $editingteacherrole->id,
                $coursecontext,
                true,               // Check parent contexts
                'u.*',              // Fields to return
                'u.firstname',      // Sort order
                true,               // Active users only
                $groupids,          // Group IDs (0 = all groups)
                '',                 // Name filter
                '',                 // Additional query
                '',                 // Additional parameters
                ''                  // Additional context
            );
            if ($editingteachers) {
                $teachers = array_merge($teachers, $editingteachers);
            }
        }

        // Get non-editing teachers.
        if ($teacherrole) {
            $nonediting = get_role_users(
                $teacherrole->id,
                $coursecontext,
                true,
                'u.*',
                'u.firstname',
                true,
                $groupids,
                '',
                '',
                '',
                ''
            );
            if ($nonediting) {
                $teachers = array_merge($teachers, $nonediting);
            }
        }

        // Remove duplicates (in case a user has both roles) and maintain firstname order.
        $uniqueteachers = array();
        foreach ($teachers as $teacher) {
            if (!isset($uniqueteachers[$teacher->id])) {
                $uniqueteachers[$teacher->id] = $teacher;
            }
        }

        // Reindex array and sort by firstname.
        $teachers = array_values($uniqueteachers);
        usort($teachers, function($a, $b) {
            return strcmp($a->firstname, $b->firstname);
        });

        // Get editing teacher role info for the participants page URL.
        $roles = new \stdClass();
        $allroles = get_all_roles();
        foreach ($allroles as $singlerole) {
            if ($singlerole->shortname == 'editingteacher') {
                $roles = $singlerole;
                break;
            }
        }
        if (!isset($roles->id)) {
            $roles->id = "";
        }

        // Build context array with teacher information.
        $context = array();

        if ($teachers) {
            $namescount = 4;
            $profilecount = 0;

            foreach ($teachers as $key => $teacher) {
                if ($frontlineteacher && $profilecount < $namescount) {
                    $instructor = array();
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

            // Add count of remaining teachers if more than namescount.
            if ($profilecount > $namescount) {
                $context['teachercount'] = $profilecount - $namescount;
            }

            $context['participantspageurl'] = $CFG->wwwroot . '/user/index.php?id=' . $courseid . '&roleid=' . $roles->id;
            $context['hasteachers'] = true;
        }

        return $context;
    }
}
