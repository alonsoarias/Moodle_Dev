<?php
/**
 * Library functions for theme_inteb.
 *
 * This file contains helper functions and hooks for the theme_inteb.
 * Provides functions to enhance course cards with:
 * - RemUI custom fields display
 * - Complete instructor listing (all teachers, not just editing teachers)
 *
 * @package    theme_inteb
 * @category   lib
 * @author     Pedro Alonso Arias Balcucho
 * @copyright  2025 Soporte IngeWeb <soporte@ingeweb.co>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Get all teachers for a course (both editingteacher and teacher roles).
 *
 * Unlike RemUI's default behavior which filters by 'mod/folder:managefiles' capability,
 * this function returns ALL users with teacher or editingteacher roles, regardless of
 * editing permissions.
 *
 * @param int $courseid The course ID
 * @param int $groupids Optional group filter (0 for all groups)
 * @return array Array of teacher objects with formatted data for templates
 */
function theme_inteb_get_all_course_teachers($courseid, $groupids = 0) {
    global $DB, $CFG, $OUTPUT;

    $coursecontext = context_course::instance($courseid);

    // Get teacher and editingteacher roles
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
            'u.*',  // All user fields
            'u.firstname ASC',
            true,  // Include only enrolled
            $groupids
        );

        foreach ($teachers as $teacher) {
            // Avoid duplicates (user could have both roles)
            if (!isset($allteachers[$teacher->id])) {
                $allteachers[$teacher->id] = [
                    'id' => $teacher->id,
                    'name' => fullname($teacher, true),
                    'url' => $CFG->wwwroot . '/user/profile.php?id=' . $teacher->id,
                    'picture' => $OUTPUT->user_picture($teacher, ['size' => 35, 'link' => false]),
                    'user' => $teacher
                ];
            }
        }
    }

    return array_values($allteachers);
}

/**
 * Get RemUI custom fields for a course formatted for template display.
 *
 * Returns an array of custom fields from the "RemUI Custom Fields" category,
 * formatted and ready to be displayed in course cards.
 *
 * Available fields (with their shortnames):
 * - Course Duration (edwcourseduration)
 * - Course Intro Video URL (edwcourseintrovideourlembedded)
 * - Skill Level (edwskilllevel) - Beginner/Intermediate/Advanced
 * - Focus Mode (edwfocusmode)
 *
 * @param int $courseid The course ID
 * @return array Array of custom fields with 'shortname', 'name', 'value', 'hasvalue'
 */
function theme_inteb_get_remui_custom_fields($courseid) {
    // Use RemUI's function to get custom fields
    $customfields = get_all_remui_course_metadata($courseid);

    if (empty($customfields)) {
        return [];
    }

    $formattedfields = [];

    foreach ($customfields as $shortname => $fielddata) {
        $formattedfields[] = [
            'shortname' => $shortname,
            'name' => $fielddata['name'],
            'value' => $fielddata['text'],
            'hasvalue' => !empty($fielddata['text'])
        ];
    }

    return $formattedfields;
}

/**
 * Get specific RemUI custom field value for a course.
 *
 * Quick access function to get a single custom field value.
 *
 * @param int $courseid The course ID
 * @param string $fieldshortname The field shortname (e.g., 'edwcourseduration')
 * @return string|null The field value or null if not found
 */
function theme_inteb_get_remui_field_value($courseid, $fieldshortname) {
    $customfields = get_all_remui_course_metadata($courseid);

    if (isset($customfields[$fieldshortname])) {
        return $customfields[$fieldshortname]['text'];
    }

    return null;
}

/**
 * Inject inteb coursehandler into RemUI contexts.
 *
 * This function provides a way to get an instance of theme_inteb_coursehandler
 * for use in places where RemUI would normally use its own handler.
 *
 * @return theme_inteb_coursehandler Instance of inteb's enhanced coursehandler
 */
function theme_inteb_get_coursehandler() {
    require_once(__DIR__ . '/classes/coursehandler.php');
    return new theme_inteb_coursehandler();
}

/**
 * Override theme_remui's extra SCSS callback to include inteb customizations.
 *
 * This allows theme_inteb to inject additional SCSS variables and styles
 * into the compiled CSS.
 *
 * @param theme_config $theme The theme config object
 * @return string Additional SCSS code
 */
function theme_inteb_get_extra_scss($theme) {
    $content = '';

    // Add any custom SCSS variables or styles here
    $content .= '
// Theme Inteb - Custom SCSS for course cards

// Custom field display styles
.course-custom-field {
    display: flex;
    align-items: center;
    margin: 0.25rem 0;
    font-size: 0.875rem;

    .field-icon {
        margin-right: 0.5rem;
        opacity: 0.7;
    }

    .field-label {
        font-weight: 600;
        margin-right: 0.5rem;
    }

    .field-value {
        color: $gray-700;
    }
}

// Enhanced instructor display
.course-instructors-list {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
    margin-top: 0.5rem;

    .instructor-item {
        display: flex;
        align-items: center;
        padding: 0.25rem 0.5rem;
        background: rgba(0, 0, 0, 0.05);
        border-radius: 1rem;

        img {
            margin-right: 0.5rem;
        }
    }
}

// Course duration badge
.course-duration-badge {
    display: inline-flex;
    align-items: center;
    padding: 0.25rem 0.75rem;
    background: var(--primary);
    color: white;
    border-radius: 1rem;
    font-size: 0.75rem;
    font-weight: 600;

    i {
        margin-right: 0.25rem;
    }
}

// Skill level badge variants
.skill-level-badge {
    display: inline-flex;
    align-items: center;
    padding: 0.25rem 0.75rem;
    border-radius: 1rem;
    font-size: 0.75rem;
    font-weight: 600;

    &.beginner {
        background: #28a745;
        color: white;
    }

    &.intermediate {
        background: #ffc107;
        color: #000;
    }

    &.advanced {
        background: #dc3545;
        color: white;
    }
}
';

    // Call parent theme's extra SCSS if it exists
    if (function_exists('theme_remui_get_extra_scss')) {
        $content .= theme_remui_get_extra_scss($theme);
    }

    return $content;
}

/**
 * Process course data to add inteb-specific enhancements.
 *
 * This function takes course data array and enhances it with:
 * - All teachers (not just editing teachers)
 * - RemUI custom fields
 *
 * Used by renderers and templates to get enhanced course data.
 *
 * @param array $coursedata Course data array from RemUI
 * @return array Enhanced course data array
 */
function theme_inteb_enhance_course_data($coursedata) {
    if (!isset($coursedata['courseid'])) {
        return $coursedata;
    }

    $courseid = $coursedata['courseid'];

    // Add RemUI custom fields
    $customfields = theme_inteb_get_remui_custom_fields($courseid);
    if (!empty($customfields)) {
        $coursedata['remuicustomfields'] = $customfields;
        $coursedata['hasremuicustomfields'] = true;

        // Add individual fields for easy access
        foreach ($customfields as $field) {
            if ($field['hasvalue']) {
                switch ($field['shortname']) {
                    case 'edwcourseduration':
                        $coursedata['courseduration'] = $field['value'];
                        $coursedata['hascourseduration'] = true;
                        break;
                    case 'edwcourseintrovideourlembedded':
                        $coursedata['courseintrovideo'] = $field['value'];
                        $coursedata['hascourseintrovideo'] = true;
                        break;
                    case 'edwskilllevel':
                        $coursedata['courseskilllevel'] = $field['value'];
                        $coursedata['hascourseskilllevel'] = true;
                        // Add CSS class for skill level badge
                        $coursedata['courseskillevelclass'] = strtolower($field['value']);
                        break;
                }
            }
        }
    }

    // Replace instructors with all teachers
    $allteachers = theme_inteb_get_all_course_teachers($courseid);
    if (!empty($allteachers)) {
        $coursedata['instructors'] = $allteachers;
        $teachercount = count($allteachers);
        $coursedata['instructorcount'] = ($teachercount > 1) ? ($teachercount - 1) : '';
        $coursedata['hasmultipleinstructors'] = ($teachercount > 1);
        $coursedata['totalinstructors'] = $teachercount;
    }

    return $coursedata;
}
