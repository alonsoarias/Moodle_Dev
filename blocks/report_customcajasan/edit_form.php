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
 * Form for editing report_customcajasan block instances.
 *
 * @package    block_report_customcajasan
 * @copyright  2025 Cajasan
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/lib.php');

/**
 * Class block_report_customcajasan_edit_form
 *
 * Provides instance configuration options for the block.
 */
class block_report_customcajasan_edit_form extends block_edit_form {

    /**
     * Add block-specific configuration fields to the form.
     *
     * @param MoodleQuickForm $mform Form object to add elements to.
     */
    protected function specific_definition($mform) {
        global $USER, $PAGE;

        $mform->addElement('header', 'configheader', get_string('blocksettings', 'block'));

        // Opciones de visualización
        $options = $this->block->get_available_info_options();
        $mform->addElement(
            'select',
            'config_displayoptions',
            get_string('config_displayoptions', 'block_report_customcajasan'),
            $options,
            ['multiple' => true, 'size' => count($options)]
        );
        $mform->addHelpButton('config_displayoptions', 'config_displayoptions', 'block_report_customcajasan');
        $mform->setDefault('config_displayoptions', [
            block_report_customcajasan::INFO_REPORT_LINK,
            block_report_customcajasan::INFO_INSTRUCTIONS,
        ]);

        // Mensaje personalizado
        $mform->addElement(
            'textarea',
            'config_custommessage',
            get_string('config_custommessage', 'block_report_customcajasan'),
            ['rows' => 4]
        );
        $mform->setType('config_custommessage', PARAM_TEXT);
        $mform->addHelpButton('config_custommessage', 'config_custommessage', 'block_report_customcajasan');

        // Determinar el contexto padre
        $parentcontext = null;
        if (!empty($this->block->instance->parentcontextid)) {
            $parentcontext = context::instance_by_id($this->block->instance->parentcontextid, IGNORE_MISSING);
        } else if (!empty($this->block->page->context)) {
            $parentcontext = $this->block->page->context;
        }

        $iscoursecontext = $parentcontext && $parentcontext->contextlevel === CONTEXT_COURSE;

        // ✅ SECCIÓN DE RESTRICCIONES - Solo si estamos en un curso
        if ($iscoursecontext) {
            $mform->addElement('header', 'configrestrictions', get_string('config_restriction_heading', 'block_report_customcajasan'));
            $mform->addHelpButton('configrestrictions', 'config_restriction_heading', 'block_report_customcajasan');

            // ✅ Filtro de cursos
            $courseoptions = [];
            $courses = get_user_capability_course('block/report_customcajasan:viewreport', $USER->id, true, 'fullname, shortname', 'fullname');
            
            foreach ($courses as $course) {
                $coursecontext = context_course::instance($course->id, IGNORE_MISSING);
                $courseoptions[$course->id] = format_string($course->fullname, true, ['context' => $coursecontext ?: null]);
            }

            if (!empty($courseoptions)) {
                $coursename = 'config_' . block_report_customcajasan::CONFIG_COURSES;
                $select = $mform->addElement(
                    'select',
                    $coursename,
                    get_string('config_coursefilters', 'block_report_customcajasan'),
                    $courseoptions,
                    ['multiple' => true, 'size' => min(10, max(3, count($courseoptions)))]
                );
                $mform->addHelpButton($coursename, 'config_coursefilters', 'block_report_customcajasan');
            } else {
                $mform->addElement(
                    'static', 
                    'config_' . block_report_customcajasan::CONFIG_COURSES . '_notice', 
                    '', 
                    get_string('config_coursefilters_empty', 'block_report_customcajasan')
                );
            }

            // ✅ Filtro de categorías
            $categoryoptions = core_course_category::make_categories_list('block/report_customcajasan:viewreport');
            
            if (!empty($categoryoptions)) {
                $categoryname = 'config_' . block_report_customcajasan::CONFIG_CATEGORIES;
                $select = $mform->addElement(
                    'select',
                    $categoryname,
                    get_string('config_categoryfilters', 'block_report_customcajasan'),
                    $categoryoptions,
                    ['multiple' => true, 'size' => min(10, max(3, count($categoryoptions)))]
                );
                $mform->addHelpButton($categoryname, 'config_categoryfilters', 'block_report_customcajasan');
            } else {
                $mform->addElement(
                    'static', 
                    'config_' . block_report_customcajasan::CONFIG_CATEGORIES . '_notice', 
                    '', 
                    get_string('config_categoryfilters_empty', 'block_report_customcajasan')
                );
            }
        }
    }

    /**
     * Populate the form with default configuration data.
     * 
     * CRÍTICO: Carga las restricciones guardadas para que se muestren en el formulario.
     *
     * @param stdClass $defaults Defaults passed from the block instance.
     */
    public function set_data($defaults) {
        if (!empty($this->block->config)) {
            // Opciones de visualización
            if (!empty($this->block->config->displayoptions)) {
                $defaults->config_displayoptions = $this->block->config->displayoptions;
            }
            
            // Mensaje personalizado
            if (isset($this->block->config->custommessage)) {
                $defaults->config_custommessage = $this->block->config->custommessage;
            }

            // ✅ CRÍTICO: Cargar las restricciones guardadas
            
            // Cargar cursos filtrados
            if (isset($this->block->config->{block_report_customcajasan::CONFIG_COURSES})) {
                $savedcourses = $this->block->config->{block_report_customcajasan::CONFIG_COURSES};
                
                // Convertir a array si es necesario
                if (is_string($savedcourses)) {
                    $savedcourses = explode(',', $savedcourses);
                }
                if (!is_array($savedcourses)) {
                    $savedcourses = (array)$savedcourses;
                }
                
                $savedcourses = array_map('intval', $savedcourses);
                $savedcourses = array_filter($savedcourses);
                
                if (!empty($savedcourses)) {
                    $defaults->{'config_' . block_report_customcajasan::CONFIG_COURSES} = $savedcourses;
                }
            }
            
            // Cargar categorías filtradas
            if (isset($this->block->config->{block_report_customcajasan::CONFIG_CATEGORIES})) {
                $savedcategories = $this->block->config->{block_report_customcajasan::CONFIG_CATEGORIES};
                
                // Convertir a array si es necesario
                if (is_string($savedcategories)) {
                    $savedcategories = explode(',', $savedcategories);
                }
                if (!is_array($savedcategories)) {
                    $savedcategories = (array)$savedcategories;
                }
                
                $savedcategories = array_map('intval', $savedcategories);
                $savedcategories = array_filter($savedcategories);
                
                if (!empty($savedcategories)) {
                    $defaults->{'config_' . block_report_customcajasan::CONFIG_CATEGORIES} = $savedcategories;
                }
            }
        }

        parent::set_data($defaults);
    }

    /**
     * Display the configuration form when the block is being added.
     *
     * @return bool
     */
    public static function display_form_when_adding(): bool {
        return true;
    }
}