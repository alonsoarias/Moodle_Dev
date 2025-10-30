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
 * Renderer overrides for theme_inteb
 *
 * Este archivo contiene los renderers personalizados del theme inteb que extienden
 * funcionalidad de plugins para adaptarla a las necesidades específicas del tema.
 *
 * @package   theme_inteb
 * @copyright (c) 2025 IngeWeb <soporte@ingeweb.co>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Pedro Arias <soporte@ingeweb.co>
 */

defined('MOODLE_INTERNAL') || die();

// Cargar el renderer original del plugin format_remuiformat
require_once($CFG->dirroot . '/course/format/remuiformat/renderer.php');

/**
 * Renderer personalizado para format_remuiformat en theme inteb
 *
 * Extiende el renderer del plugin format_remuiformat para modificar
 * el comportamiento de visualización de profesores en el header del curso.
 *
 * Cambios principales:
 * - Muestra AMBOS roles: editingteacher Y teacher (en vez de solo editingteacher)
 * - Utiliza funciones auxiliares del theme (theme_inteb_get_extra_header_context)
 *
 * @package   theme_inteb
 * @copyright (c) 2025 IngeWeb
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class theme_inteb_format_remuiformat_renderer extends format_remuiformat_renderer {

    /**
     * Renderiza la página de una sección en formato card.
     *
     * Sobreescribe el método original para usar nuestra función personalizada
     * theme_inteb_get_extra_header_context() que incluye ambos roles de profesor.
     *
     * @param \format_remuiformat\output\format_remuiformat_card_one_section $section Objeto renderable de la sección
     */
    public function render_card_one_section(
        \format_remuiformat\output\format_remuiformat_card_one_section $section
    ) {
        global $PAGE, $COURSE;

        // Exportar contexto del template usando el método original
        $templatecontext = $section->export_for_template($this);

        // MODIFICACIÓN: Reemplazar el headerdata con nuestra versión que incluye ambos roles
        if (isset($templatecontext->headerdata)) {
            // Obtener el curso desde el contexto de la página o desde COURSE global
            $course = $PAGE->course;
            if (empty($course) || $course->id == SITEID) {
                $course = $COURSE;
            }

            // Obtener datos desde el contexto existente
            $percentage = $templatecontext->headerdata['percentage'] ?? null;
            $imgurl = $templatecontext->headerdata['headercourseimage'] ?? '';

            if ($course && $course->id != SITEID) {
                // Recrear el objeto export con la estructura esperada
                $export = new stdClass();
                $export->generalsection = [];
                $export->turneditingonswitch = '';

                // Usar nuestra función que incluye ambos roles
                $templatecontext->headerdata = theme_inteb_get_extra_header_context(
                    $export,
                    $course,
                    $percentage,
                    $imgurl
                );

                // Preservar subsectionjs y sectionreturn si existen
                if (isset($export->generalsection['subsectionjs'])) {
                    $templatecontext->subsectionjs = $export->generalsection['subsectionjs'];
                }
                if (isset($export->generalsection['sectionreturn'])) {
                    $templatecontext->sectionreturn = $export->generalsection['sectionreturn'];
                }
                if (isset($export->turneditingonswitch)) {
                    $templatecontext->turneditingonswitch = $export->turneditingonswitch;
                }
            }
        }

        // Renderizar con el contexto modificado
        echo $this->render_from_template('format_remuiformat/card_one_section', $templatecontext);
    }

    /**
     * Renderiza la página de una sección en formato lista.
     *
     * Sobreescribe el método original para usar nuestra función personalizada
     * theme_inteb_get_extra_header_context() que incluye ambos roles de profesor.
     *
     * @param \format_remuiformat\output\format_remuiformat_list_one_section $activity Objeto renderable de la actividad
     */
    public function render_list_one_section(
        \format_remuiformat\output\format_remuiformat_list_one_section $activity
    ) {
        global $PAGE, $COURSE;

        // Exportar contexto del template usando el método original
        $templatecontext = $activity->export_for_template($this);

        // MODIFICACIÓN: Reemplazar el headerdata con nuestra versión que incluye ambos roles
        if (isset($templatecontext->headerdata)) {
            // Obtener el curso desde el contexto de la página o desde COURSE global
            $course = $PAGE->course;
            if (empty($course) || $course->id == SITEID) {
                $course = $COURSE;
            }

            // Obtener datos desde el contexto existente
            $percentage = $templatecontext->headerdata['percentage'] ?? null;
            $imgurl = $templatecontext->headerdata['headercourseimage'] ?? '';

            if ($course && $course->id != SITEID) {
                // Recrear el objeto export con la estructura esperada
                $export = new stdClass();
                $export->generalsection = [];
                $export->turneditingonswitch = '';

                // Usar nuestra función que incluye ambos roles
                $templatecontext->headerdata = theme_inteb_get_extra_header_context(
                    $export,
                    $course,
                    $percentage,
                    $imgurl
                );

                // Preservar subsectionjs y sectionreturn si existen
                if (isset($export->generalsection['subsectionjs'])) {
                    $templatecontext->subsectionjs = $export->generalsection['subsectionjs'];
                }
                if (isset($export->generalsection['sectionreturn'])) {
                    $templatecontext->sectionreturn = $export->generalsection['sectionreturn'];
                }
                if (isset($export->turneditingonswitch)) {
                    $templatecontext->turneditingonswitch = $export->turneditingonswitch;
                }
            }
        }

        // Renderizar con el contexto modificado
        echo $this->render_from_template('format_remuiformat/list_one_section', $templatecontext);
    }
}
