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
 * Course handler personalizado para theme_inteb
 * Extiende el coursehandler de theme_remui para mostrar TODOS los profesores
 * (con y sin permisos de edición)
 *
 * @package   theme_inteb
 * @copyright 2025 INTEB
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace theme_inteb;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/theme/remui/classes/coursehandler.php');

/**
 * Coursehandler extendido para theme_inteb
 *
 * Sobrescribe métodos del coursehandler de remui para incluir
 * TODOS los profesores (editing teachers y non-editing teachers)
 */
class coursehandler extends \theme_remui_coursehandler {

    /**
     * Obtiene contexto de profesores inscritos en un curso
     * MODIFICADO: Ahora obtiene TODOS los profesores (editing y non-editing)
     *
     * @param object $course Objeto del curso
     * @param bool $frontlineteacher Si true, limita a 4 profesores en el display
     * @return array Contexto con información de profesores
     */
    public function get_enrolled_teachers_context($course, $frontlineteacher = false) {
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
        // Capability: mod/folder:managefiles (como hace remui originalmente)
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
        // Alternativa: moodle/course:activityvisibility
        $nonEditingTeachers = get_enrolled_users(
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
        // Algunos usuarios pueden tener ambas capabilities
        $allteachers = array();

        // Agregar editing teachers
        foreach ($editingteachers as $teacher) {
            $allteachers[$teacher->id] = $teacher;
            // Marcar como editing teacher
            $allteachers[$teacher->id]->is_editing_teacher = true;
        }

        // Agregar non-editing teachers (evitando duplicados)
        foreach ($nonEditingTeachers as $teacher) {
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
            // Límite de profesores a mostrar en el header
            $namescount = 4;
            $profilecount = 0;

            foreach ($allteachers as $key => $teacher) {
                if ($frontlineteacher && $profilecount < $namescount) {
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
                }
                $profilecount++;
            }

            // Calcular contador de profesores adicionales
            if ($profilecount > $namescount) {
                $context['teachercount'] = $profilecount - $namescount;
            }

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
     * MÉTODO OPCIONAL: Si se requiere personalizar otros aspectos
     * Se puede sobrescribir get_courses() para modificar las course cards
     *
     * Por ahora lo dejamos comentado y usamos solo el método anterior
     */

    /*
    public function get_courses($filters = array()) {
        // Llamar al método padre
        $result = parent::get_courses($filters);

        // Aquí se pueden hacer modificaciones adicionales si se requieren
        // Por ejemplo, agregar más información de profesores a cada curso

        return $result;
    }
    */
}
