<?php
/**
 * Extends the core course renderer for theme_inteb to customize the display of course elements.
 *
 * This class extends the default Moodle course renderer provided by the theme_remui. It allows
 * for customization specific to the needs of theme_inteb, focusing primarily on the visual
 * presentation of course elements to align with the theme's aesthetics and functional requirements.
 *
 * @package    theme_inteb
 * @category   output
 * @author     Pedro Alonso Arias Balcucho
 * @copyright  2025 Soporte IngeWeb <soporte@ingeweb.co>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace theme_inteb\output\core;

defined('MOODLE_INTERNAL') || die();

use moodle_url;
use html_writer;
use core_course_category;
use coursecat_helper;
use stdClass;
use core_course_list_element;
use theme_remui\util\extras;

// Including the parent theme's course renderer for extension
require_once(__DIR__ . '/../../../../remui/classes/output/core/course_renderer.php');

/**
 * Class course_renderer
 *
 * Custom course renderer for theme_inteb, extending the course renderer from theme_remui.
 * It can override methods from the parent class to alter the default rendering behavior or
 * add new methods to introduce new functionalities specific to theme_inteb.
 */
class course_renderer extends \theme_remui\output\core\course_renderer {

    /**
     * Returns HTML to print list of available courses for the frontpage
     *
     * Overrides parent method to use theme_inteb_coursehandler instead of theme_remui_coursehandler.
     * This ensures that custom fields (course duration, skill level) and all instructors are included.
     *
     * @return string HTML output for frontpage courses
     */
    public function frontpage_available_courses() {
        global $CFG, $DB;
        $contenthtml = '';
        $chelper = new coursecat_helper();
        $chelper->set_show_courses(self::COURSECAT_SHOW_COURSES_EXPANDED)->set_courses_display_options(array(
                    'recursive' => true,
                    'limit' => $CFG->frontpagecourselimit,
                    'viewmoreurl' => new moodle_url('/course/index.php'),
                    'viewmoretext' => new \lang_string('fulllistofcourses')));

        $chelper->set_attributes(array('class' => 'frontpage-course-list-all'));

        $courselength = $CFG->frontpagecourselimit;
        $totalcount = core_course_category::get(0)->get_courses_count($chelper->get_courses_display_options());

        if (!$totalcount &&
            !$this->page->user_is_editing() &&
            has_capability('moodle/course:create', \context_system::instance())
        ) {
            // Print link to create a new course, for the 1st available category.
            return $this->add_new_course_button();
        }

        // INTEB ENHANCEMENT: Use theme_inteb_coursehandler to include custom fields
        $coursehandler = new \theme_inteb_coursehandler();
        $courses = $coursehandler->get_courses(
            false,
            null,
            null,
            0,
            $courselength,
            null,
            null,
            [],
            false
        );

        if (!empty($courses)) {
            $contenthtml .= "<div class='slick-slide-container'>";
            foreach ($courses as $course) {
                $contenthtml .= $this->render_from_template("theme_remui/frontpage_available_course", $course);
            }
            $contenthtml .= "</div>";
            $contenthtml .= "<div class='available-courses button-container w-100 text-center mt-3'>
                            <button type='button' class='btn btn-floating btn-primary btn-prev btn-sm'>
                            <span class='edw-icon edw-icon-Left-Arrow' aria-hidden='true'></span>
                            </button>
                            <button type='button' class='btn btn-floating btn-primary btn-next btn-sm '>
                            <span class='edw-icon edw-icon-Right-Arrow' aria-hidden='true'></span>
                            </button>
                            </div>";

            $contenthtml .= "<div class='row'>
                            <div class='col-12 text-right'>
                             <a href='{$CFG->wwwroot}/course/index.php' class='btn btn-primary mt-2'>" . get_string('viewallcourses', 'core')."</a>
                            </div>
                            </div>";
        }

        return $contenthtml;
    }
}
