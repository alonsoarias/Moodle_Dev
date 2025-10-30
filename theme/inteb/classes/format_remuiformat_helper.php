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
 * Helper class for format_remuiformat integration in theme_inteb
 * Provides custom teacher context that shows ALL teachers (editing + non-editing)
 *
 * @package   theme_inteb
 * @copyright 2025 INTEB
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace theme_inteb;

defined('MOODLE_INTERNAL') || die();

/**
 * Helper class for format_remuiformat customizations
 */
class format_remuiformat_helper {

    /**
     * Obtiene contexto de profesores inscritos en un curso
     * MODIFICADO: Ahora obtiene TODOS los profesores (editing y non-editing)
     *
     * Esta es la versión extendida que reemplaza get_enrolled_teachers_context_formate()
     * cuando se usa theme_inteb.
     *
     * @param object $course Objeto del curso
     * @param bool $frontlineteacher Si true, NO limita cantidad de profesores mostrados
     * @return array Contexto con información completa de profesores
     */
    public static function get_enrolled_teachers_context($course, $frontlineteacher = false) {
        global $OUTPUT, $CFG, $USER, $DB;

        $courseid = $course->id;

        // Obtener grupos del usuario para respetar modo de grupos
        $usergroups = groups_get_user_groups($courseid, $USER->id);
        $groupids = 0;

        if ($course->groupmode == 1) {
            $groupids = $usergroups[0];
        }

        $coursecontext = \context_course::instance($courseid);

        // ====================================================================
        // MODIFICACIÓN PRINCIPAL: Obtener TODOS los profesores
        // ====================================================================

        // PASO 1: Obtener profesores con permisos de edición (editingteacher)
        // Capability: mod/folder:managefiles (como hace remuiformat originalmente)
        $editingteachers = get_enrolled_users(
            $coursecontext,
            'mod/folder:managefiles',
            $groupids,
            '*',
            'firstname',
            $limitfrom = 0,
            $limitnum = 0,
            $onlyactive = true
        );

        // PASO 2: Obtener profesores SIN permisos de edición (non-editing teacher)
        // Capability: moodle/course:viewhiddenactivities (capability típica de teacher role)
        $noneditingteachers = get_enrolled_users(
            $coursecontext,
            'moodle/course:viewhiddenactivities',
            $groupids,
            '*',
            'firstname',
            $limitfrom = 0,
            $limitnum = 0,
            $onlyactive = true
        );

        // PASO 3: Combinar ambos arrays y eliminar duplicados
        $allteachers = array();

        // Agregar editing teachers
        foreach ($editingteachers as $teacher) {
            $allteachers[$teacher->id] = $teacher;
            // Marcar como editing teacher
            $allteachers[$teacher->id]->is_editing_teacher = true;
        }

        // Agregar non-editing teachers (evitando duplicados)
        foreach ($noneditingteachers as $teacher) {
            if (!isset($allteachers[$teacher->id])) {
                $allteachers[$teacher->id] = $teacher;
                // Marcar como non-editing teacher
                $allteachers[$teacher->id]->is_editing_teacher = false;
            }
        }

        // PASO 4: Ordenar por firstname
        uasort($allteachers, function($a, $b) {
            return strcasecmp($a->firstname, $b->firstname);
        });

        // ====================================================================
        // FIN DE MODIFICACIÓN PRINCIPAL
        // ====================================================================

        // Obtener rol de editingteacher para el enlace de participantes
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

        $context = array();

        if ($allteachers) {
            // MODIFICADO: Ya no limitamos a 4 profesores - mostramos TODOS
            $profilecount = 0;

            foreach ($allteachers as $key => $teacher) {
                // Crear entrada para cada profesor
                $instructor = array();
                $instructor['id'] = $teacher->id;
                $instructor['name'] = fullname($teacher, true);
                $instructor['avatars'] = $OUTPUT->user_picture($teacher);
                $instructor['teacherprofileurl'] = $CFG->wwwroot.'/user/profile.php?id='.$teacher->id;

                // NUEVO: Agregar indicador de tipo de profesor
                $instructor['is_editing_teacher'] = $teacher->is_editing_teacher;
                $instructor['teacher_type_class'] = $teacher->is_editing_teacher
                    ? 'editing-teacher'
                    : 'non-editing-teacher';

                // NUEVO: Agregar tooltip descriptivo
                if ($teacher->is_editing_teacher) {
                    $instructor['teacher_type_title'] = get_string('editingteacher', 'theme_inteb');
                } else {
                    $instructor['teacher_type_title'] = get_string('teacher', 'theme_inteb');
                }

                if ($profilecount != 0) {
                    $instructor['hasanother'] = true;
                }

                $context['instructors'][] = $instructor;
                $profilecount++;
            }

            // Ya no hay teachercount porque mostramos todos
            $context['teachercount'] = 0;

            $context['participantspageurl'] = $CFG->wwwroot.'/user/index.php?id='.$courseid.'&roleid='.$roles->id;
            $context['hasteachers'] = true;

            // NUEVO: Agregar contadores separados para estadísticas
            $editingcount = 0;
            $noneditingcount = 0;
            foreach ($allteachers as $teacher) {
                if ($teacher->is_editing_teacher) {
                    $editingcount++;
                } else {
                    $noneditingcount++;
                }
            }
            $context['editing_teachers_count'] = $editingcount;
            $context['non_editing_teachers_count'] = $noneditingcount;
            $context['total_teachers_count'] = $profilecount;
        }

        return $context;
    }

    /**
     * Obtiene contexto extra para el header del curso
     * Versión extendida que usa get_enrolled_teachers_context() para mostrar todos los profesores
     *
     * @param object $export Objeto de exportación (pasado por referencia)
     * @param object $course Objeto del curso
     * @param float|null $percentage Porcentaje de progreso del curso
     * @param string $imgurl URL de la imagen del curso
     * @return array Contexto completo para el header
     */
    public static function get_extra_header_context(&$export, $course, $percentage, $imgurl) {
        global $DB, $CFG, $OUTPUT, $PAGE;

        $coursedetails = get_course($course->id);
        if (!is_null($percentage)) {
            $percentage = floor($percentage);
            $export->generalsection['percentage'] = $percentage;
        } else {
            $export->generalsection['percentage'] = 0;
        }

        $categorydetails = $DB->get_record('course_categories', array('id' => $coursedetails->category));
        $rnrshortdesign = '';

        if (format_remuiformat_check_plugin_available("block_edwiserratingreview")) {
            $rnr = new \block_edwiserratingreview\ReviewManager();
            $rnrshortdesign = $rnr->get_short_design_enrolmentpage($course->id);
        }

        $coursesettings = course_get_format($course)->get_settings();

        // ====================================================================
        // MODIFICACIÓN PRINCIPAL: Usar nuestra función personalizada
        // ====================================================================
        $export->generalsection['teachers'] = self::get_enrolled_teachers_context($course, true);
        // ====================================================================

        $export->generalsection['coursefullname'] = format_text($coursedetails->fullname);
        $export->generalsection['coursecategoryname'] = format_text($categorydetails->name);
        $export->generalsection['rnrdesign'] = $rnrshortdesign;

        if (gettype($imgurl) != "object") {
            $imgurl = formate_get_course_image($course);
        }

        $export->generalsection['headercourseimage'] = $imgurl;
        $export->generalsection['remuiheaderimagebgposition'] = $coursesettings['edw_format_hd_bgpos'];
        $export->generalsection['remuiheaderimagebgsize'] = $coursesettings['edw_format_hd_bgsize'];
        $export->generalsection['courseheaderdesign'] = true;
        $export->turneditingonswitch = $OUTPUT->page_heading_button();

        if ($CFG->theme == 'remui') {
            $export->generalsection['courseheaderdesign'] = get_config('theme_remui', 'courseheaderdesign') == 0 ? false : true;
            $export->turneditingonswitch = "";
        }

        $headeroverlayopacity = $coursesettings['headeroverlayopacity'];
        if (is_numeric($headeroverlayopacity) && ($headeroverlayopacity <= 100)) {
            $headeroverlayopacity = $headeroverlayopacity / 100;
            $export->generalsection['overlayopacity'] = $headeroverlayopacity;
        } else {
            $export->generalsection['overlayopacity'] = 1;
        }

        $export->generalsection['coursecompletionstatus'] = $course->enablecompletion;
        $export->generalsection['subsectionjs'] = false;
        $export->generalsection['sectionreturn'] = null;

        if ($CFG->branch >= '405') {
            $export->generalsection['subsectionjs'] = true;
        }

        return $export->generalsection;
    }
}
