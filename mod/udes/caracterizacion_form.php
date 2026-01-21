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
 * Caracterización form for creating/editing characterization matrices.
 *
 * @package     mod_udes
 * @copyright   2026 Universidad de Santander - UDES (udes.edu.co)
 * @author      Alonso Arias <soporte@orioncloud.com.co>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir.'/formslib.php');

/**
 * Form for creating and editing characterization matrices.
 *
 * @package    mod_udes
 * @copyright  2026 Universidad de Santander - UDES
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_udes_caracterizacion_form extends moodleform {

    /**
     * Defines forms elements
     */
    public function definition() {
        $mform = $this->_form;

        // Hidden fields.
        $mform->addElement('hidden', 'id'); // Course module ID.
        $mform->setType('id', PARAM_INT);

        $mform->addElement('hidden', 'caracterizacionid'); // Caracterizacion ID (0 for new).
        $mform->setType('caracterizacionid', PARAM_INT);

        // General information.
        $mform->addElement('header', 'general', get_string('general', 'form'));

        // Characterization name/title (REQUIRED).
        $mform->addElement('text', 'nombre', get_string('caracterizacion_nombre', 'mod_udes'),
            array('size' => '64'));
        $mform->setType('nombre', PARAM_TEXT);
        $mform->addRule('nombre', null, 'required', null, 'client');
        $mform->addRule('nombre', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');
        $mform->addHelpButton('nombre', 'caracterizacion_nombre', 'mod_udes');

        // Course information section.
        $mform->addElement('header', 'courseinfo', get_string('course_information', 'mod_udes'));

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

        // Team members section - Excel H3-I9.
        $mform->addElement('header', 'teammembers', get_string('team_members', 'mod_udes'));

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

        // General resources section - Excel J11-J15.
        $mform->addElement('header', 'generalresources', get_string('general_resources', 'mod_udes'));

        // Excel J11: CVP.
        $mform->addElement('advcheckbox', 'cvp', get_string('cvp', 'mod_udes'));
        $mform->addHelpButton('cvp', 'cvp', 'mod_udes');

        // Excel J12: Sala para clases virtuales.
        $mform->addElement('advcheckbox', 'sala_clases', get_string('sala_clases', 'mod_udes'));

        // Excel J13: Video de bienvenida.
        $mform->addElement('advcheckbox', 'video_bienvenida', get_string('video_bienvenida', 'mod_udes'));

        // Excel J14: Foro del curso.
        $mform->addElement('advcheckbox', 'foro_curso', get_string('foro_curso', 'mod_udes'));

        // Excel J15: Mapa del curso.
        $mform->addElement('advcheckbox', 'mapa_curso', get_string('mapa_curso', 'mod_udes'));

        // Action buttons.
        $this->add_action_buttons(true, get_string('guardar', 'mod_udes'));
    }

    /**
     * Form validation.
     *
     * @param array $data array of ("fieldname"=>value) of submitted data
     * @param array $files array of uploaded files "element_name"=>tmp_file_path
     * @return array of "element_name"=>"error_description" if there are errors,
     *         or an empty array if everything is OK (true allowed for backwards compatibility too).
     */
    public function validation($data, $files) {
        $errors = parent::validation($data, $files);

        // Validate that nombre is not empty.
        if (empty(trim($data['nombre']))) {
            $errors['nombre'] = get_string('error_caracterizacion_nombre_empty', 'mod_udes');
        }

        return $errors;
    }
}
