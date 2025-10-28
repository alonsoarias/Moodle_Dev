<?php
/**
 * EnrolmentPageHandler personalizado para theme_inteb.
 *
 * Extiende el EnrolmentPageHandler de RemUI para utilizar el coursehandler
 * personalizado de inteb que muestra TODOS los instructores (no solo los
 * que tienen permisos de edición) y agrega campos personalizados.
 *
 * @package    theme_inteb
 * @category   classes
 * @author     Pedro Alonso Arias Balcucho
 * @copyright  2025 Soporte IngeWeb <soporte@ingeweb.co>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace theme_inteb;

defined('MOODLE_INTERNAL') || die();

// Require parent class
require_once($CFG->dirroot . '/theme/remui/classes/EnrolmentPageHandler.php');

/**
 * Extended EnrolmentPageHandler for theme_inteb
 *
 * Sobrescribe el comportamiento del EnrolmentPageHandler de RemUI para:
 * - Utilizar theme_inteb_coursehandler que obtiene TODOS los profesores
 * - Mostrar campos personalizados de RemUI en el page-header
 */
class EnrolmentPageHandler extends \theme_remui\EnrolmentPageHandler {

    /**
     * Generate data for enrollment page using inteb's coursehandler.
     *
     * Este método sobrescribe COMPLETAMENTE el del padre para usar theme_inteb_coursehandler
     * en todas las instancias, permitiendo:
     * - Mostrar TODOS los instructores (editingteacher y teacher)
     * - Agregar campos personalizados de RemUI
     * - Asegurar consistencia en toda la página de enrolment
     *
     * NOTA: No podemos llamar al método del padre porque internamente usa
     * theme_remui_coursehandler, lo que sobrescribiría nuestros datos.
     *
     * @param mixed $templatecontext Contexto del template
     * @return array Contexto completo para el template
     */
    public function generate_enrolment_page_context($templatecontext) {
        global $COURSE, $DB, $USER, $PAGE, $CFG, $OUTPUT;

        $cid = (int)$COURSE->id;
        $context = array();
        $temp = array();

        // Datos básicos del curso
        $temp['id'] = $COURSE->id;
        $temp['coursename'] = format_text($COURSE->fullname);
        $temp['category'] = format_text($COURSE->category);

        // ========================================
        // USAR COURSEHANDLER DE INTEB (NO REMUI)
        // ========================================
        // CRÍTICO: usar theme_inteb_coursehandler para obtener
        // TODOS los profesores y los campos personalizados RemUI
        $coursehandler = new \theme_inteb_coursehandler();
        $coursedataarray = $coursehandler->get_courses(
            false,  // totalcount
            null,   // search
            null,   // category
            0,      // limitfrom
            0,      // limitto
            null,   // mycourses
            null,   // categorysort
            array(0 => $COURSE),  // courses
            false   // filtermodified
        );

        $coursedata = $coursedataarray[0];

        // Información de categoría
        try {
            $coursecategory = \core_course_category::get($COURSE->category);
            $temp['categoryname'] = $coursecategory->get_formatted_name();
            $temp['categoryurl'] = $CFG->wwwroot . '/course/index.php?categoryid=' . $COURSE->category;
        } catch (\Exception $e) {
            $coursecategory = "";
            $categoryname = "";
            $categoryurl = "";
        }

        $coursecontext = \context_course::instance($cid);
        $roles = array_flip(get_default_enrol_roles($coursecontext));
        $enrolledstudents = $coursedata['enrolleduserscount'] ?? 0;
        $temp['enrolledstudents'] = $enrolledstudents;

        // ========================================
        // INSTRUCTORES - TODOS (NO SOLO EDITORES)
        // ========================================
        // El coursedata de inteb ya tiene TODOS los instructores
        // (tanto editingteacher como teacher)
        $temp['instructorcount'] = false;
        if (isset($coursedata['instructors']) && !empty($coursedata['instructors'])) {
            $instructors = $coursedata['instructors'];

            // Preparar datos para el template
            foreach ($instructors as $index => $instructor) {
                if ($index == 0) {
                    // Primer instructor: mostrar nombre
                    $temp['instructor']['name'] = $instructor['name'];
                }

                // Mostrar hasta 4 avatares
                if ($index < 4) {
                    $temp['instructor']['avatars'][] = [
                        'avatars' => $instructor['picture'],
                        'teacherprofileurl' => $instructor['url']
                    ];
                }
            }

            // Indicar si hay más instructores (+N)
            if (count($instructors) > 4) {
                $temp['instructorcount'] = count($instructors) - 4;
            }
        }

        // Ratings and Review (si el plugin está disponible)
        $context['noratingfound'] = false;
        if (is_plugin_available('block_edwiserratingreview')) {
            $rnr = new \block_edwiserratingreview\ReviewManager();
            $PAGE->requires->strings_for_js([
                'noreviewsfound',
            ], 'block_edwiserratingreview');
            $rnrreviewfull = $rnr->generate_enrolpage_block($cid);
            $rnrshortdesignarray = \theme_remui\utility::get_ernr_coursecard_design($COURSE);
            $temp['rnrreviewdesign'] = $rnrshortdesignarray['rnrshortdesign'];

            if ($rnrshortdesignarray['rnrshortratingvalue'] == 0 && (!$PAGE->user_is_editing())) {
                $temp['rnrreviewdesign'] = false;
                $rnrreviewfull = false;
            }
            if ($rnrshortdesignarray['rnrshortratingvalue'] == 0 && $PAGE->user_is_editing()) {
                $context['noratingfound'] = true;
                $rnrreviewfull = true;
            }
        }

        $temp['totallessons'] = $coursedata['lessoncount'];

        // Obtener custom fields de RemUI (incluye los campos personalizados)
        $customfielddata = get_course_metadata($cid);

        if (isset($customfielddata['edwcourseintrovideourlembedded'])) {
            $temp['introvideourl'] = $customfielddata['edwcourseintrovideourlembedded'];
        }

        // Header section Context
        $context['headersection'] = $temp;

        // Course Overview Section
        $temp = array();
        $temp['coursesummary'] = format_text(
            file_rewrite_pluginfile_urls($COURSE->summary, 'pluginfile.php', $coursecontext->id, 'course', 'summary', NULL),
            FORMAT_HTML,
            array("noclean" => true)
        );

        if (isset($rnrreviewfull)) {
            $temp['rnrreviewfull'] = $rnrreviewfull;
        }

        $context['courseoverview'] = $temp;

        // ========================================
        // PRICING SECTION - Con campos personalizados
        // ========================================
        $temp = array();
        $temp = $this->get_course_purchase_details($COURSE->id);
        $temp['enrolledstudents'] = $enrolledstudents;
        $temp['enrolledusertitletext'] = $coursedata['enrolledusertitletext'];

        // Campos personalizados RemUI para pricing section
        if (isset($customfielddata['edwcourseduration'])) {
            $temp['courselength'] = format_text($customfielddata['edwcourseduration'], FORMAT_HTML);
        }

        if (isset($customfielddata['edwskilllevel']) && \theme_remui\Utility::get_skilllevel_by_courseid($COURSE->id)) {
            $temp['skilllevel'] = get_string('skill' . $customfielddata['edwskilllevel'], 'theme_remui');
        }

        $temp['totallessons'] = $coursedata['lessoncount'];
        $temp['lessonstitletext'] = $coursedata['lessonstitletext'];

        $temp['additionalcustomfields'] = $this->get_additional_custom_metadata_html($cid);
        $temp['showselecteddatesetting'] = $coursedata['showselecteddatesetting'];
        $temp['showselecteddatesettingname'] = $coursedata['showselecteddatesettingname'];
        $temp['showselecteddatesettingdate'] = $coursedata['showselecteddatesettingdate'];

        // Language
        $langarray = \get_string_manager()->get_list_of_translations();
        $language = $langarray["en"];
        if ($COURSE->lang != "" && isset($langarray[$COURSE->lang])) {
            $language = $langarray[$COURSE->lang];
        }
        $temp['language'] = $language;

        $context['pricingsection'] = $temp;

        // Custom fields metadata
        $customfieldsarray = array_values(get_all_remui_course_metadata($COURSE->id));
        $customfieldcatgory_id = "";
        if (!empty($customfieldsarray)) {
            $customfieldcatgory_id = $customfieldsarray[0]['categoryid'];
        }

        // Additional context data
        $context['ismanager'] = \theme_remui\utility::check_user_admin_cap($USER);
        $context['courseimage'] = $coursedata['courseimage'];
        $context['relatedcourses'] = $this->get_related_courses();
        $context['coursearcivecaturl'] = $CFG->wwwroot . "/course/index.php?categoryid=" . $COURSE->category;
        $context['latestcourses'] = $this->get_latest_courses();
        $context['courseurl'] = $CFG->wwwroot . "/course/view.php?id=" . $COURSE->id;
        $context['hasrelatedcourses'] = get_config("theme_remui", 'showrelatedcourse');
        $context['haslatestcourses'] = get_config("theme_remui", 'showlatestcourse');
        $context['showrelatedcoursesblock'] = true;
        $context['showlatestcoursesblock'] = true;

        if (!$context['relatedcourses']) {
            $context['showrelatedcoursesblock'] = false;
        }
        if (!$context['latestcourses']) {
            $context['showlatestcoursesblock'] = false;
        }

        // Check if has instructors
        $hasintstructors = isset($coursedata['instructors']) && !empty($coursedata['instructors']);
        $context['hasintstructors'] = $hasintstructors;

        $context['hasnarrowidth'] = (get_config("theme_remui", "pagewidth") == 'fullwidth') ? false : true;
        $context['editing'] = $PAGE->user_is_editing();
        $context['editcoursetitle'] = $CFG->wwwroot . '/course/edit.php?id=' . $COURSE->id . '#id_fullname';
        $context['editcategorylink'] = $CFG->wwwroot . '/course/edit.php?id=' . $COURSE->id . '#id_category';
        $context['editinstructorspageurl'] = $CFG->wwwroot . '/user/index.php?id=' . $COURSE->id;
        $context['editapprovalpageurl'] = $CFG->wwwroot . '/blocks/edwiserratingreview/admin.php';
        $context['editcourseintorvideourllink'] = $CFG->wwwroot . '/course/edit.php?id=' . $COURSE->id . '#id_category_' . $customfieldcatgory_id;
        $context['enrolloptionshidden'] = get_config('theme_remui', "enrolloptionshidden" . $COURSE->id);
        $context['editcourseimglink'] = $CFG->wwwroot . '/course/edit.php?id=' . $COURSE->id . '#fitem_id_overviewfiles_filemanager';
        $context['editenrolmethodspagelink'] = $CFG->wwwroot . '/enrol/instances.php?id=' . $COURSE->id;
        $context['editaddremuicustomfieldlink'] = $CFG->wwwroot . '/course/customfield.php' . '#category-' . $customfieldcatgory_id;
        $context['editcoursecustomfields'] = $CFG->wwwroot . '/course/edit.php?id=' . $COURSE->id . '#id_category_' . $customfieldcatgory_id;
        $context['editcoursedesclink'] = $CFG->wwwroot . '/course/edit.php?id=' . $COURSE->id . '#id_descriptionhdrcontainer';
        $context['editcourseinforsettinglink'] = $CFG->wwwroot . '/admin/settings.php?section=themesettingremui#theme_remui_course';
        $context['editfreelabelsettinglink'] = $CFG->wwwroot . '/admin/settings.php?section=themesettingremui&settingsectionname=admin-enrolment_payment#theme_remui_course';
        $context['editreletedlatestsettinglink'] = $CFG->wwwroot . '/admin/settings.php?section=themesettingremui&settingsectionname=admin-showrelatedcourse#theme_remui_course';
        $context['playiconurl'] = $OUTPUT->image_url("play", "theme_remui");
        $context['csstohidemainarearemuifields'] = $this->get_css_to_hide_custom_metadata_inmainwarpper($COURSE->id);

        return $context;
    }

    /**
     * Get courses for enrolment page using inteb's coursehandler.
     *
     * Sobrescribe el método del padre para usar theme_inteb_coursehandler,
     * asegurando que las "related courses" y "latest courses" también
     * muestren TODOS los instructores y campos personalizados.
     *
     * @param bool   $totalcount
     * @param string $search
     * @param int    $category
     * @param int    $limitfrom
     * @param int    $limitto
     * @param mixed  $mycourses
     * @param string $categorysort
     * @param array  $courses
     * @param bool   $filtermodified
     * @return array Courses data
     */
    public function get_enrolpage_courses($totalcount, $search, $category, $limitfrom, $limitto, $mycourses, $categorysort, $courses, $filtermodified) {
        global $COURSE;

        // USAR COURSEHANDLER DE INTEB (NO REMUI)
        $coursehandler = new \theme_inteb_coursehandler();
        $coursedata = $coursehandler->get_courses(
            $totalcount,
            $search,
            $category,
            $limitfrom,
            $limitto,
            $mycourses,
            $categorysort,
            $courses,
            $filtermodified
        );

        $allcourses = $coursedata;
        unset($allcourses);

        foreach ($coursedata as $course) {
            $allcourses[$course['courseid']] = $course;
        }

        // Excluir el curso actual
        unset($allcourses[$COURSE->id]);

        return $allcourses;
    }
}
