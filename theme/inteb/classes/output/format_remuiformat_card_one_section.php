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
 * Override de format_remuiformat card_one_section para theme_inteb
 * Permite mostrar TODOS los profesores (editing + non-editing)
 *
 * @package   theme_inteb
 * @copyright 2025 INTEB
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace theme_inteb\output;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/course/format/remuiformat/classes/output/card_one_section_renderable.php');

use format_remuiformat\output\format_remuiformat_card_one_section as parent_card_one_section;
use renderer_base;
use core_completion\progress;

/**
 * Override de format_remuiformat_card_one_section
 *
 * Extiende la clase original y sobrescribe export_for_template() para usar
 * nuestra función helper que muestra TODOS los profesores.
 */
class format_remuiformat_card_one_section extends parent_card_one_section {

    /**
     * Export this data so it can be used as the context for a mustache template.
     *
     * @param renderer_base $output
     * @return stdClass
     */
    public function export_for_template(renderer_base $output) {
        global $PAGE, $DB;

        // Llamar al método padre para obtener toda la data base
        $export = parent::export_for_template($output);

        // MODIFICACIÓN: Reemplazar la sección de teachers con nuestra versión
        // que muestra TODOS los profesores (editing + non-editing)

        if (isset($export->headerdata) && is_object($export->headerdata)) {
            // Obtener el curso usando reflexión (porque $course es privado en clase padre)
            $reflection = new \ReflectionClass(get_parent_class($this));
            $property = $reflection->getProperty('course');
            $property->setAccessible(true);
            $course = $property->getValue($this);

            // Obtener contexto de teachers usando nuestro helper
            $teacherscontext = \theme_inteb\format_remuiformat_helper::get_enrolled_teachers_context(
                $course,
                true // frontlineteacher = true para no limitar
            );

            // Reemplazar la sección de teachers
            if (!empty($teacherscontext)) {
                $export->headerdata->teachers = $teacherscontext;
            }
        }

        return $export;
    }
}
