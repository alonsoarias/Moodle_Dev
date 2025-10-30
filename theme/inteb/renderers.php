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
 * Provides custom rendering for format_remuiformat to show ALL teachers
 *
 * @package   theme_inteb
 * @copyright 2025 INTEB
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/course/format/remuiformat/renderer.php');

/**
 * Override de format_remuiformat_renderer para theme_inteb
 *
 * Este renderer intercepta la renderización de cursos con formato remuiformat
 * y modifica los datos de profesores para mostrar TODOS (editing + non-editing)
 * sin necesidad de modificar el plugin format_remuiformat.
 */
class theme_inteb_format_remuiformat_renderer extends format_remuiformat_renderer {

    /**
     * Renders the card one section page.
     *
     * OVERRIDE: Intercepta la renderización para inyectar datos completos de profesores
     *
     * @param \format_remuiformat\output\format_remuiformat_card_one_section $activity Object of Activity renderable
     */
    public function render_card_one_section(
        \format_remuiformat\output\format_remuiformat_card_one_section $activity) {

        // Obtener contexto del template desde el objeto activity
        $templatecontext = $activity->export_for_template($this);

        // MODIFICACIÓN: Reemplazar datos de teachers con versión completa
        $templatecontext = $this->inject_all_teachers_data($templatecontext, $activity);

        // Renderizar con el contexto modificado
        echo $this->render_from_template('format_remuiformat/card_one_section', $templatecontext);
    }

    /**
     * Renders the list one section page.
     *
     * OVERRIDE: Intercepta la renderización para inyectar datos completos de profesores
     *
     * @param \format_remuiformat\output\format_remuiformat_list_one_section $activity Object of Activity renderable
     */
    public function render_list_one_section(
        \format_remuiformat\output\format_remuiformat_list_one_section $activity) {

        // Obtener contexto del template desde el objeto activity
        $templatecontext = $activity->export_for_template($this);

        // MODIFICACIÓN: Reemplazar datos de teachers con versión completa
        $templatecontext = $this->inject_all_teachers_data($templatecontext, $activity);

        // Renderizar con el contexto modificado
        echo $this->render_from_template('format_remuiformat/list_one_section', $templatecontext);
    }

    /**
     * Inyecta datos completos de profesores (editing + non-editing) en el contexto del template
     *
     * @param stdClass $templatecontext Contexto del template generado por export_for_template()
     * @param object $activity Objeto renderable (card o list)
     * @return stdClass Contexto modificado con datos completos de profesores
     */
    private function inject_all_teachers_data($templatecontext, $activity) {
        // Verificar que existe la sección headerdata con teachers
        if (!isset($templatecontext->headerdata) || !is_object($templatecontext->headerdata)) {
            return $templatecontext;
        }

        // Usar reflexión para acceder a la propiedad privada $course del renderable
        try {
            $reflection = new ReflectionClass($activity);
            $property = $reflection->getProperty('course');
            $property->setAccessible(true);
            $course = $property->getValue($activity);
        } catch (ReflectionException $e) {
            // Si falla la reflexión, devolver el contexto sin modificar
            debugging('theme_inteb: No se pudo acceder a la propiedad course: ' . $e->getMessage(), DEBUG_DEVELOPER);
            return $templatecontext;
        }

        // Obtener contexto de profesores usando nuestro helper extendido
        $teacherscontext = \theme_inteb\format_remuiformat_helper::get_enrolled_teachers_context(
            $course,
            true // frontlineteacher = true para mostrar TODOS sin límite
        );

        // Reemplazar la sección de teachers en headerdata
        if (!empty($teacherscontext)) {
            // Convertir array a objeto si es necesario (para consistencia con template)
            $templatecontext->headerdata->teachers = (object)$teacherscontext;
        }

        return $templatecontext;
    }
}
