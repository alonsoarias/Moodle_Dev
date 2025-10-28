<?php
/**
 * Enhanced course data webservice for theme_inteb.
 *
 * This webservice extends RemUI's course data API to include:
 * - All instructors (not just editing teachers)
 * - RemUI custom fields data
 *
 * @package    theme_inteb
 * @category   external
 * @author     Pedro Alonso Arias Balcucho
 * @copyright  2025 Soporte IngeWeb <soporte@ingeweb.co>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace theme_inteb\external;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/externallib.php');
require_once($CFG->dirroot . '/theme/inteb/lib.php');

use external_api;
use external_function_parameters;
use external_value;
use external_single_structure;
use external_multiple_structure;

/**
 * Enhanced course data API for theme_inteb.
 *
 * Provides course data with complete instructor lists and custom fields.
 */
class get_enhanced_courses extends external_api {

    /**
     * Returns description of method parameters.
     *
     * @return external_function_parameters
     */
    public static function get_courses_parameters() {
        return new external_function_parameters([
            'courseids' => new external_multiple_structure(
                new external_value(PARAM_INT, 'Course ID'),
                'Array of course IDs to get data for',
                VALUE_REQUIRED
            ),
        ]);
    }

    /**
     * Get enhanced course data including all instructors and custom fields.
     *
     * @param array $courseids Array of course IDs
     * @return array Array of enhanced course data
     */
    public static function get_courses($courseids) {
        global $CFG;

        $params = self::validate_parameters(self::get_courses_parameters(), [
            'courseids' => $courseids
        ]);

        $courseids = $params['courseids'];

        if (empty($courseids)) {
            return ['courses' => []];
        }

        // Use inteb's coursehandler to get enhanced data
        require_once($CFG->dirroot . '/theme/inteb/classes/coursehandler.php');
        $coursehandler = new \theme_inteb_coursehandler();

        // Get course objects
        $courses = [];
        foreach ($courseids as $courseid) {
            try {
                $course = get_course($courseid);
                $courses[] = $course;
            } catch (\Exception $e) {
                // Skip invalid courses
                continue;
            }
        }

        if (empty($courses)) {
            return ['courses' => []];
        }

        // Get enhanced course data
        $enhancedcourses = $coursehandler->get_courses(
            false,  // totalcount
            null,   // search
            null,   // category
            0,      // limitfrom
            0,      // limitto
            null,   // mycourses
            null,   // categorysort
            $courses,  // courses array
            false   // filtermodified
        );

        return ['courses' => array_values($enhancedcourses)];
    }

    /**
     * Returns description of method result value.
     *
     * @return external_single_structure
     */
    public static function get_courses_returns() {
        return new external_single_structure([
            'courses' => new external_multiple_structure(
                new external_single_structure([
                    'courseid' => new external_value(PARAM_INT, 'Course ID'),
                    'coursename' => new external_value(PARAM_TEXT, 'Course name'),
                    'shortname' => new external_value(PARAM_TEXT, 'Course shortname'),
                    'categoryname' => new external_value(PARAM_TEXT, 'Category name'),
                    'courseurl' => new external_value(PARAM_URL, 'Course URL'),
                    'courseimage' => new external_value(PARAM_URL, 'Course image URL'),
                    'coursesummary' => new external_value(PARAM_RAW, 'Course summary', VALUE_OPTIONAL),
                    'instructors' => new external_multiple_structure(
                        new external_single_structure([
                            'id' => new external_value(PARAM_INT, 'User ID'),
                            'name' => new external_value(PARAM_TEXT, 'Full name'),
                            'url' => new external_value(PARAM_URL, 'Profile URL'),
                            'picture' => new external_value(PARAM_RAW, 'User picture HTML'),
                        ]),
                        'List of ALL instructors',
                        VALUE_OPTIONAL
                    ),
                    'instructorcount' => new external_value(PARAM_TEXT, 'Additional instructor count', VALUE_OPTIONAL),
                    'totalinstructors' => new external_value(PARAM_INT, 'Total number of instructors', VALUE_OPTIONAL),
                    'remuicustomfields' => new external_multiple_structure(
                        new external_single_structure([
                            'shortname' => new external_value(PARAM_TEXT, 'Field shortname'),
                            'name' => new external_value(PARAM_TEXT, 'Field name'),
                            'value' => new external_value(PARAM_RAW, 'Field value'),
                            'hasvalue' => new external_value(PARAM_BOOL, 'Has value'),
                        ]),
                        'RemUI custom fields',
                        VALUE_OPTIONAL
                    ),
                    'courseduration' => new external_value(PARAM_TEXT, 'Course duration', VALUE_OPTIONAL),
                    'hascourseduration' => new external_value(PARAM_BOOL, 'Has duration', VALUE_OPTIONAL),
                    'courseskilllevel' => new external_value(PARAM_TEXT, 'Skill level', VALUE_OPTIONAL),
                    'hascourseskilllevel' => new external_value(PARAM_BOOL, 'Has skill level', VALUE_OPTIONAL),
                ], 'Enhanced course data')
            )
        ]);
    }
}
