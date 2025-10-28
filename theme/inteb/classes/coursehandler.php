<?php
/**
 * Course handler for theme_inteb - Extends RemUI coursehandler to add custom fields and instructor data.
 *
 * This class extends the RemUI coursehandler to customize course card data display:
 * 1. Shows ALL instructors (not just those with editing permissions)
 * 2. Displays RemUI custom fields (Course Duration, Skill Level, etc.)
 *
 * @package    theme_inteb
 * @category   classes
 * @author     Pedro Alonso Arias Balcucho
 * @copyright  2025 Soporte IngeWeb <soporte@ingeweb.co>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

// Require parent coursehandler
require_once($CFG->dirroot . '/theme/remui/classes/coursehandler.php');

/**
 * Extended coursehandler for theme_inteb
 *
 * Extends theme_remui_coursehandler to provide:
 * - Complete instructor list (all teachers, not just editing teachers)
 * - RemUI custom fields display in course cards
 */
class theme_inteb_coursehandler extends theme_remui_coursehandler {

    /**
     * Get all enrolled teachers for a course (not just editing teachers).
     *
     * This method obtains ALL teachers enrolled in a course, including both:
     * - editingteacher role (with editing permissions)
     * - teacher role (without editing permissions)
     *
     * RemUI's default behavior only shows teachers with 'mod/folder:managefiles' capability,
     * which typically means only editingteachers. This method removes that restriction.
     *
     * @param int $courseid The course ID
     * @param int $groupids Group IDs filter (0 for all groups)
     * @return array Array of user objects representing all teachers
     */
    protected function get_all_course_teachers($courseid, $groupids = 0) {
        global $DB;

        $coursecontext = \context_course::instance($courseid);

        // Get all roles that are considered "teacher" roles
        $teacherroles = $DB->get_records_sql(
            "SELECT DISTINCT r.id, r.shortname
               FROM {role} r
              WHERE r.shortname IN ('editingteacher', 'teacher')
           ORDER BY r.sortorder"
        );

        if (empty($teacherroles)) {
            return [];
        }

        $allteachers = [];

        // Get enrolled users for each teacher role
        foreach ($teacherroles as $role) {
            $teachers = get_role_users(
                $role->id,
                $coursecontext,
                true,  // Parent contexts
                'u.id, u.firstname, u.lastname, u.email, u.picture, u.imagealt, u.firstnamephonetic, u.lastnamephonetic, u.middlename, u.alternatename',
                'u.firstname ASC',
                true,  // Include only enrolled
                $groupids
            );

            foreach ($teachers as $teacher) {
                // Avoid duplicates (a user could have both teacher and editingteacher roles)
                if (!isset($allteachers[$teacher->id])) {
                    $allteachers[$teacher->id] = $teacher;
                }
            }
        }

        return $allteachers;
    }

    /**
     * Enhanced get_courses method that adds custom fields and all instructors.
     *
     * This method extends the parent get_courses() to:
     * 1. Add RemUI custom field data to each course
     * 2. Replace instructor list with ALL teachers (not just editing teachers)
     * 3. Show complete instructor count
     *
     * @param bool   $totalcount if true then count of total course is returned instead of course data
     * @param string $search if given then course returned should match the search
     * @param int    $category if given then course returned should be in given category
     * @param int    $limitfrom return subset starting from course limitfrom
     * @param int    $limitto return subset starting from course limitfrom to limitto
     * @param array  $mycourses list of user's courses
     * @param string $categorysort sort to apply on courses
     * @param array  $courses courses array
     * @param bool   $filtermodified if true then fresh course count will be loaded else cached will be used
     * @param array  $filteredcourseids array of filtered course ids
     * @param bool   $isfilterapplied if true then filter is applied
     * @return array of courses with enhanced data
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
        global $CFG, $OUTPUT, $DB;

        // Get base course data from parent (RemUI)
        $coursesarray = parent::get_courses(
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

        // If only requesting count, return as-is
        if ($totalcount === true || is_int($coursesarray)) {
            return $coursesarray;
        }

        // Enhance each course with additional data
        foreach ($coursesarray as $index => &$coursedata) {

            if (!isset($coursedata['courseid'])) {
                continue;
            }

            $courseid = $coursedata['courseid'];

            // ========================================
            // 1. ADD REMUI CUSTOM FIELDS
            // ========================================

            // Get RemUI custom fields for this course
            // Fields: Course Duration, Course Intro Video URL, Skill Level, Focus Mode
            $customfields = get_all_remui_course_metadata($courseid);

            if (!empty($customfields)) {
                // Add custom fields array to course data
                $coursedata['remuicustomfields'] = [];

                foreach ($customfields as $shortname => $fielddata) {
                    $coursedata['remuicustomfields'][] = [
                        'shortname' => $shortname,
                        'name' => $fielddata['name'],
                        'value' => $fielddata['text'],
                        'hasvalue' => !empty($fielddata['text'])
                    ];
                }

                // Add individual fields for easy template access
                if (isset($customfields['edwcourseduration'])) {
                    $coursedata['courseduration'] = $customfields['edwcourseduration']['text'];
                    $coursedata['hascourseduration'] = true;
                }

                if (isset($customfields['edwcourseintrovideourlembedded'])) {
                    $coursedata['courseintrovideo'] = $customfields['edwcourseintrovideourlembedded']['text'];
                    $coursedata['hascourseintrovideo'] = true;
                }

                if (isset($customfields['edwskilllevel'])) {
                    $coursedata['courseskilllevel'] = $customfields['edwskilllevel']['text'];
                    $coursedata['hascourseskilllevel'] = true;
                }
            }

            // ========================================
            // 2. REPLACE INSTRUCTORS WITH ALL TEACHERS
            // ========================================

            // Get ALL teachers (both editingteacher and teacher roles)
            $allteachers = $this->get_all_course_teachers($courseid);

            if (!empty($allteachers)) {
                // Clear existing instructors (from RemUI - only editing teachers)
                $coursedata['instructors'] = [];

                $teachercount = 0;

                foreach ($allteachers as $teacher) {
                    $coursedata['instructors'][] = [
                        'id' => $teacher->id,
                        'name' => fullname($teacher, true),
                        'url' => $CFG->wwwroot . '/user/profile.php?id=' . $teacher->id,
                        'picture' => $OUTPUT->user_picture($teacher, ['size' => 35, 'link' => false])
                    ];
                    $teachercount++;

                    // Only show first instructor in some views, but count all
                    if ($teachercount == 1) {
                        // Keep first instructor for single display
                    }
                }

                // Update instructor count (show +N for additional teachers)
                // -1 because we show the first one separately
                $coursedata['instructorcount'] = ($teachercount > 1) ? ($teachercount - 1) : '';
                $coursedata['hasmultipleinstructors'] = ($teachercount > 1);
                $coursedata['totalinstructors'] = $teachercount;
            }
        }

        return $coursesarray;
    }

    /**
     * Enhanced get_enrolled_teachers_context for course pages.
     *
     * This method overrides RemUI's version to show ALL teachers on course pages,
     * not just those with editing permissions.
     *
     * @param object $course Course object
     * @param bool $frontlineteacher Whether to format for frontline display
     * @return array Context array with all instructors
     */
    public function get_enrolled_teachers_context($course, $frontlineteacher = false) {
        global $OUTPUT, $CFG, $USER;

        $courseid = $course->id;
        $usergroups = groups_get_user_groups($courseid, $USER->id);
        $groupids = 0;

        if ($course->groupmode == 1) {
            $groupids = $usergroups[0];
        }

        // Get ALL teachers (not just editing teachers)
        $teachers = $this->get_all_course_teachers($courseid, $groupids);

        // Get editingteacher role for participants page link
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

        $context = [];

        if ($teachers) {
            $namescount = 4;
            $profilecount = 0;

            foreach ($teachers as $key => $teacher) {
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

            $context['participantspageurl'] = $CFG->wwwroot . '/user/index.php?id=' . $courseid . '&roleid=' . $roles->id;
            $context['hasteachers'] = true;
        }

        return $context;
    }
}
