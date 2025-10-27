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
 * Block definition class for report_customcajasan
 *
 * @package    block_report_customcajasan
 * @copyright  2025 Cajasan
 * @author     Pedro Arias <soporte@ingeweb.co>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Class block_report_customcajasan
 *
 * @package    block_report_customcajasan
 * @copyright  2025 Cajasan
 * @author     Pedro Arias <soporte@ingeweb.co>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class block_report_customcajasan extends block_base {

    /**
     * Initialize the block.
     */
    public function init() {
        $this->title = get_string('pluginname', 'block_report_customcajasan');
    }

    /**
     * Get the block content.
     *
     * @return stdClass The block content.
     */
    public function get_content() {
        global $USER, $DB, $COURSE;

        if ($this->content !== null) {
            return $this->content;
        }

        $this->content = new stdClass;
        $this->content->text = '';
        $this->content->footer = '';

        // No mostrar nada si el usuario no está autenticado
        if (!isloggedin()) {
            return $this->content;
        }

        // Detectar si estamos en un contexto de curso específico
        $coursecontext = $this->page->context->get_course_context(false);
        $incourse = false;
        $courseid = null;
        
        if ($coursecontext && $coursecontext->contextlevel == CONTEXT_COURSE) {
            $courseid = $coursecontext->instanceid;
            // Verificar que no sea el sitio principal
            if ($courseid != SITEID) {
                $incourse = true;
            }
        }

        $systemcontext = context_system::instance();

        // Verificar permisos según el contexto
        if ($incourse) {
            // Estamos en un curso - verificar permisos en el contexto del curso
            $can_view = has_capability('block/report_customcajasan:viewreport', $coursecontext);
            $is_manager = has_any_capability(['moodle/course:update', 'moodle/course:manageactivities'], $coursecontext);
        } else {
            // Estamos en contexto del sistema
            $can_view = has_capability('block/report_customcajasan:viewreport', $systemcontext);
            $is_manager = has_any_capability(['moodle/site:config', 'moodle/course:update'], $systemcontext);
        }

        if ($can_view || $is_manager) {
            // El usuario tiene permiso - mostrar enlace al informe
            $reportparams = array();
            
            // Si estamos en un curso, pasar el ID del curso como parámetro
            if ($incourse && $courseid) {
                $reportparams['courseid'] = $courseid;
                $reportparams['coursecontext'] = 1; // Indicador de que venimos de un curso
            }
            
            $reporturl = new moodle_url('/blocks/report_customcajasan/report.php', $reportparams);
            
            // Ajustar el texto del botón según el contexto
            $buttontext = $incourse 
                ? get_string('enrollment_report_course', 'block_report_customcajasan')
                : get_string('enrollment_report', 'block_report_customcajasan');
            
            $this->content->text = html_writer::tag('div', 
                html_writer::link($reporturl, 
                    $buttontext, 
                    ['class' => 'btn btn-primary btn-block mb-2']),
                ['class' => 'report-links']);
                
            // Si estamos en un curso, mostrar información adicional
            if ($incourse && $courseid) {
                $course = $DB->get_record('course', array('id' => $courseid), 'fullname', MUST_EXIST);
                $this->content->footer = html_writer::tag('div',
                    html_writer::tag('small', 
                        get_string('viewing_course_report', 'block_report_customcajasan', $course->fullname),
                        ['class' => 'text-muted']
                    ),
                    ['class' => 'mt-2']
                );
            }
            
            // Si el usuario no tiene la capacidad explícita pero es gestor, mostrar mensaje explicativo
            if ($is_manager && !$can_view && !$incourse) {
                $this->content->footer .= html_writer::tag('div',
                    'Acceso proporcionado por rol de gestión del sitio',
                    ['class' => 'small text-muted mt-1']);
            }
        } else {
            // El usuario no tiene permiso - mostrar mensaje informativo
            $this->content->text = html_writer::tag('div',
                get_string('access_denied', 'block_report_customcajasan'),
                ['class' => 'alert alert-info']);
        }

        return $this->content;
    }

    /**
     * Specify which page formats this block can be displayed in.
     *
     * @return array Array of page formats.
     */
    public function applicable_formats() {
        return [
            'admin' => true,
            'site-index' => true,
            'my' => true,
            'course-view' => true,  // Permitir en vista de curso
            'course-index' => true,
            'mod' => false
        ];
    }

    /**
     * Can multiple instances of this block be used on a page?
     *
     * @return bool False means only one instance allowed.
     */
    public function instance_allow_multiple() {
        return false;
    }

    /**
     * Does this block have global configuration?
     *
     * @return bool False as this block doesn't have config.
     */
    public function has_config() {
        return false;
    }

    /**
     * Does this block have instance-specific configuration?
     *
     * @return bool True if the block can be configured.
     */
    public function instance_allow_config() {
        return false;
    }
}