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
 * The main mod_udes configuration form.
 *
 * @package     mod_udes
 * @copyright   2026 Universidad de Santander - UDES
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/course/moodleform_mod.php');

/**
 * Module instance settings form.
 *
 * @package    mod_udes
 * @copyright  2026 Universidad de Santander - UDES
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_udes_mod_form extends moodleform_mod {

    /**
     * Defines forms elements
     */
    public function definition() {
        global $CFG;

        $mform = $this->_form;

        // Adding the "general" fieldset, where all the common settings are shown.
        $mform->addElement('header', 'general', get_string('general', 'form'));

        // Adding the standard "name" field.
        $mform->addElement('text', 'name', get_string('name', 'mod_udes'), array('size' => '64'));

        if (!empty($CFG->formatstringstriptags)) {
            $mform->setType('name', PARAM_TEXT);
        } else {
            $mform->setType('name', PARAM_CLEANHTML);
        }

        $mform->addRule('name', null, 'required', null, 'client');
        $mform->addRule('name', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');

        $this->standard_intro_elements();

        // UDES specific fields - Based on Excel structure (rows 1-9).
        $mform->addElement('header', 'udesconfig', get_string('caracterizacion', 'mod_udes'));

        // Excel H1-I1: Programa académico.
        $mform->addElement('text', 'programa_academico', get_string('programa_academico', 'mod_udes'),
            array('size' => '64'));
        $mform->setType('programa_academico', PARAM_TEXT);
        $mform->addHelpButton('programa_academico', 'programa_academico', 'mod_udes');

        // Excel H2-I2: Nombre del curso.
        $mform->addElement('text', 'nombre_curso', get_string('nombre_curso', 'mod_udes'),
            array('size' => '64'));
        $mform->setType('nombre_curso', PARAM_TEXT);
        $mform->addHelpButton('nombre_curso', 'nombre_curso', 'mod_udes');

        // Excel H3-I3: Asesor Metodológico.
        $mform->addElement('text', 'asesor_metodologico', get_string('asesor_metodologico', 'mod_udes'),
            array('size' => '64'));
        $mform->setType('asesor_metodologico', PARAM_TEXT);

        // Excel H4-I4: Experto Disciplinar.
        $mform->addElement('text', 'experto_disciplinar', get_string('experto_disciplinar', 'mod_udes'),
            array('size' => '64'));
        $mform->setType('experto_disciplinar', PARAM_TEXT);

        // Excel H5-I5: Par Académico.
        $mform->addElement('text', 'par_academico', get_string('par_academico', 'mod_udes'),
            array('size' => '64'));
        $mform->setType('par_academico', PARAM_TEXT);

        // Excel H6-I6: Corrector de Estilo.
        $mform->addElement('text', 'corrector_estilo', get_string('corrector_estilo', 'mod_udes'),
            array('size' => '64'));
        $mform->setType('corrector_estilo', PARAM_TEXT);

        // Excel H7-I7: Coordinación de Producción.
        $mform->addElement('text', 'coordinacion_produccion', get_string('coordinacion_produccion', 'mod_udes'),
            array('size' => '64'));
        $mform->setType('coordinacion_produccion', PARAM_TEXT);

        // Excel H8-I8: Producción.
        $mform->addElement('text', 'produccion', get_string('produccion_nombre', 'mod_udes'),
            array('size' => '64'));
        $mform->setType('produccion', PARAM_TEXT);

        // Excel H9-I9: Alistamiento.
        $mform->addElement('text', 'alistamiento', get_string('alistamiento_nombre', 'mod_udes'),
            array('size' => '64'));
        $mform->setType('alistamiento', PARAM_TEXT);

        // Add standard elements.
        $this->standard_coursemodule_elements();

        // Add standard buttons.
        $this->add_action_buttons();
    }

    /**
     * Add any custom completion rules to the form.
     *
     * @return array Contains the names of the added form elements
     */
    public function add_completion_rules() {
        return array();
    }

    /**
     * Determines if completion is enabled for this module.
     *
     * @param array $data
     * @return bool
     */
    public function completion_rule_enabled($data) {
        return false;
    }
}
