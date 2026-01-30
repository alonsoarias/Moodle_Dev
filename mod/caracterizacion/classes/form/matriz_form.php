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
 * Matrix characterization form.
 *
 * @package     mod_caracterizacion
 * @copyright   2024 Alonso Arias <soporte@orioncloud.com.co>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_caracterizacion\form;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');

/**
 * Form for creating/editing a characterization matrix.
 */
class matriz_form extends \moodleform {

    /**
     * Form definition.
     */
    public function definition() {
        global $DB;

        $mform = $this->_form;
        $cmid = $this->_customdata['cmid'];
        $matrizid = $this->_customdata['matrizid'] ?? 0;
        $courseid = $this->_customdata['courseid'];

        // Hidden fields.
        $mform->addElement('hidden', 'cmid', $cmid);
        $mform->setType('cmid', PARAM_INT);
        $mform->addElement('hidden', 'matrizid', $matrizid);
        $mform->setType('matrizid', PARAM_INT);

        // =====================================================================
        // SECTION 1: Course Information.
        // =====================================================================
        $mform->addElement('header', 'infocurso', get_string('infodelcurso', 'mod_caracterizacion'));

        $mform->addElement('text', 'programa_academico',
            get_string('programaacademico', 'mod_caracterizacion'), ['size' => '64']);
        $mform->setType('programa_academico', PARAM_TEXT);
        $mform->addRule('programa_academico', null, 'required', null, 'client');
        $mform->addHelpButton('programa_academico', 'programaacademico', 'mod_caracterizacion');

        $mform->addElement('text', 'nombre_curso',
            get_string('nombredelcurso', 'mod_caracterizacion'), ['size' => '64']);
        $mform->setType('nombre_curso', PARAM_TEXT);
        $mform->addRule('nombre_curso', null, 'required', null, 'client');
        $mform->addHelpButton('nombre_curso', 'nombredelcurso', 'mod_caracterizacion');

        // =====================================================================
        // SECTION 2: Role Assignment.
        // =====================================================================
        $mform->addElement('header', 'rolesheader', get_string('asignacionroles', 'mod_caracterizacion'));

        // Get enrolled users for the course.
        $context = \context_course::instance($courseid);
        // Include all name fields required by fullname() function.
        $namefields = \core_user\fields::for_name()->get_sql('u', false, '', '', false)->selects;
        $userfields = 'u.id, u.email, ' . $namefields;
        $enrolledusers = get_enrolled_users($context, '', 0, $userfields);
        $useroptions = [0 => get_string('seleccioneusuario', 'mod_caracterizacion')];
        foreach ($enrolledusers as $user) {
            $useroptions[$user->id] = fullname($user) . ' (' . $user->email . ')';
        }

        $roles = [
            'asesor_metodologico' => 'asesormetodologico',
            'experto_disciplinar' => 'expertodisciplinar',
            'revisor_curricular' => 'revisorcurricular',
            'par_academico' => 'paracademico',
            'corrector_estilo' => 'correctorestilo',
            'coord_produccion' => 'coordproduccion',
            'produccion' => 'produccion',
            'alistamiento' => 'alistamiento',
        ];

        foreach ($roles as $rolekey => $rolename) {
            $mform->addElement('select', 'rol_' . $rolekey,
                get_string($rolename, 'mod_caracterizacion'), $useroptions);
            $mform->addHelpButton('rol_' . $rolekey, $rolename, 'mod_caracterizacion');
        }

        // =====================================================================
        // SECTION 3: General Resources.
        // =====================================================================
        $mform->addElement('header', 'recursosgenheader', get_string('recursosgenerales', 'mod_caracterizacion'));

        $generalresources = \caracterizacion_get_general_resources();
        foreach ($generalresources as $reskey => $resstr) {
            $group = [];
            $group[] = $mform->createElement('advcheckbox', 'recgen_' . $reskey . '_incluido',
                '', get_string('incluir', 'mod_caracterizacion'));
            $group[] = $mform->createElement('text', 'recgen_' . $reskey . '_obs',
                get_string('observaciones', 'mod_caracterizacion'), ['size' => '40']);
            $mform->addGroup($group, 'recgen_' . $reskey . '_group',
                get_string($resstr, 'mod_caracterizacion'), ' ', false);
            $mform->setType('recgen_' . $reskey . '_obs', PARAM_TEXT);
        }

        // =====================================================================
        // SECTION 4: Units and Topics (dynamic via JS).
        // =====================================================================
        $mform->addElement('header', 'unidadesheader', get_string('unidades', 'mod_caracterizacion'));

        // We use a hidden field to store the JSON data for units/topics/resources.
        // The AMD module will manage the dynamic UI.
        $mform->addElement('hidden', 'unidades_data', '');
        $mform->setType('unidades_data', PARAM_RAW);

        // Container for dynamic units/topics/resources UI.
        $mform->addElement('html', '<div id="mod-caracterizacion-unidades-container" data-cmid="' .
            $cmid . '" data-matrizid="' . $matrizid . '"></div>');

        // =====================================================================
        // Action buttons.
        // =====================================================================
        $this->add_action_buttons(true, get_string('guardar', 'mod_caracterizacion'));
    }

    /**
     * Custom validation.
     *
     * @param array $data Form data.
     * @param array $files Form files.
     * @return array Validation errors.
     */
    public function validation($data, $files) {
        $errors = parent::validation($data, $files);

        if (empty($data['programa_academico'])) {
            $errors['programa_academico'] = get_string('required');
        }
        if (empty($data['nombre_curso'])) {
            $errors['nombre_curso'] = get_string('required');
        }

        return $errors;
    }
}
