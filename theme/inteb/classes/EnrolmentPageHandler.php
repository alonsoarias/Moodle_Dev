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
     * Este método sobrescribe el del padre para usar theme_inteb_coursehandler
     * en lugar de theme_remui_coursehandler, lo que permite:
     * - Mostrar TODOS los instructores (editingteacher y teacher)
     * - Agregar campos personalizados de RemUI
     *
     * @param mixed $templatecontext Contexto del template
     * @return array Contexto mejorado para el template
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
        // Esto es crítico: usar theme_inteb_coursehandler para obtener
        // TODOS los profesores y los campos personalizados
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

        // Obtener información de instructores desde el coursedata (ya procesados por inteb)
        $coursecontext = \context_course::instance($cid);
        $roles = array_flip(get_default_enrol_roles($coursecontext));
        $enrolledstudents = $coursedata['enrolleduserscount'] ?? 0;
        $temp['enrolledstudents'] = $enrolledstudents;

        // ========================================
        // INSTRUCTORES - TODOS (NO SOLO EDITORES)
        // ========================================
        // El coursedata de inteb ya tiene TODOS los instructores
        if (isset($coursedata['instructors']) && !empty($coursedata['instructors'])) {
            $instructors = $coursedata['instructors'];

            // Preparar datos para el template
            $temp['instructor'] = [];
            $temp['instructor']['avatars'] = [];

            // Mostrar el primer instructor con nombre
            if (count($instructors) > 0) {
                $firstinstructor = $instructors[0];
                $temp['instructor']['name'] = $firstinstructor['name'];

                // Agregar avatares de todos los instructores (mostrar primeros 4)
                $maxdisplay = min(4, count($instructors));
                for ($i = 0; $i < $maxdisplay; $i++) {
                    $temp['instructor']['avatars'][] = [
                        'avatars' => $instructors[$i]['picture'],
                        'teacherprofileurl' => $instructors[$i]['url']
                    ];
                }
            }

            // Indicar si hay más instructores
            if (count($instructors) > 4) {
                $temp['instructorcount'] = count($instructors) - 4;
            } else {
                $temp['instructorcount'] = false;
            }
        } else {
            // Fallback: usar el método del padre si no hay datos de inteb
            // (aunque esto no debería pasar)
            $temp['instructorcount'] = false;
        }

        // Llamar al método del padre para el resto de la lógica
        // y sobrescribir solo las partes relacionadas con instructores
        $parentcontext = parent::generate_enrolment_page_context($templatecontext);

        // Sobrescribir la sección de instructores con nuestros datos
        if (isset($parentcontext['headersection'])) {
            $parentcontext['headersection']['instructor'] = $temp['instructor'];
            $parentcontext['headersection']['instructorcount'] = $temp['instructorcount'];
        }

        return $parentcontext;
    }
}
