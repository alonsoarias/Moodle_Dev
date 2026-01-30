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
 * Create or edit a characterization matrix.
 *
 * @package     mod_caracterizacion
 * @copyright   2024 Alonso Arias <soporte@orioncloud.com.co>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');
require_once($CFG->dirroot . '/mod/caracterizacion/lib.php');

$cmid = required_param('cmid', PARAM_INT);
$matrizid = optional_param('matrizid', 0, PARAM_INT);

$cm = get_coursemodule_from_id('caracterizacion', $cmid, 0, false, MUST_EXIST);
$course = $DB->get_record('course', ['id' => $cm->course], '*', MUST_EXIST);
$moduleinstance = $DB->get_record('caracterizacion', ['id' => $cm->instance], '*', MUST_EXIST);

require_login($course, true, $cm);
$context = context_module::instance($cm->id);

$isnew = ($matrizid == 0);
if ($isnew) {
    require_capability('mod/caracterizacion:crearmatriz', $context);
    $pagetitle = get_string('nuevamatriz', 'mod_caracterizacion');
} else {
    require_capability('mod/caracterizacion:editarmatriz', $context);
    $pagetitle = get_string('editarmatriz', 'mod_caracterizacion');
    $matriz = $DB->get_record('caracterizacion_matriz', ['id' => $matrizid], '*', MUST_EXIST);
}

$PAGE->set_url('/mod/caracterizacion/matriz_edit.php', ['cmid' => $cmid, 'matrizid' => $matrizid]);
$PAGE->set_title($pagetitle);
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_context($context);

// Load the AMD module for dynamic units/topics/resources.
$PAGE->requires->js_call_amd('mod_caracterizacion/matriz_form', 'init', [[
    'cmid' => $cmid,
    'matrizid' => $matrizid,
]]);

$form = new \mod_caracterizacion\form\matriz_form(null, [
    'cmid' => $cmid,
    'matrizid' => $matrizid,
    'courseid' => $course->id,
]);

// Set existing data if editing.
if (!$isnew) {
    $formdata = [
        'cmid' => $cmid,
        'matrizid' => $matrizid,
        'programa_academico' => $matriz->programa_academico,
        'nombre_curso' => $matriz->nombre_curso,
    ];

    // Load roles.
    $roles = $DB->get_records('caracterizacion_roles', ['matrizid' => $matrizid]);
    foreach ($roles as $role) {
        $formdata['rol_' . $role->rol] = $role->userid;
    }

    // Load general resources.
    $genresources = $DB->get_records('caracterizacion_recursos_gen', ['matrizid' => $matrizid]);
    foreach ($genresources as $gr) {
        $formdata['recgen_' . $gr->recurso . '_incluido'] = $gr->incluido;
        $formdata['recgen_' . $gr->recurso . '_obs'] = $gr->observaciones;
    }

    // Load units/topics/resources data as JSON.
    $unidadesdata = [];
    $unidades = $DB->get_records('caracterizacion_unidades', ['matrizid' => $matrizid], 'numero ASC');
    foreach ($unidades as $unidad) {
        $udata = [
            'id' => $unidad->id,
            'numero' => $unidad->numero,
            'nombre' => $unidad->nombre,
            'temas' => [],
        ];
        $temas = $DB->get_records('caracterizacion_temas', ['unidadid' => $unidad->id], 'numero ASC');
        foreach ($temas as $tema) {
            $tdata = [
                'id' => $tema->id,
                'numero' => $tema->numero,
                'nombre' => $tema->nombre,
                'recursos' => [],
            ];
            $recursos = $DB->get_records('caracterizacion_recursos', ['temaid' => $tema->id]);
            foreach ($recursos as $rec) {
                $tdata['recursos'][] = [
                    'id' => $rec->id,
                    'tipo_recurso' => $rec->tipo_recurso,
                    'recurso' => $rec->recurso,
                    'item' => $rec->item,
                    'observaciones' => $rec->observaciones,
                ];
            }
            $udata['temas'][] = $tdata;
        }
        $unidadesdata[] = $udata;
    }
    $formdata['unidades_data'] = json_encode($unidadesdata);

    $form->set_data($formdata);
}

if ($form->is_cancelled()) {
    redirect(new moodle_url('/mod/caracterizacion/view.php', ['id' => $cmid]));
}

if ($data = $form->get_data()) {
    $now = time();

    if ($isnew) {
        // Create new matrix.
        $matrizrecord = new stdClass();
        $matrizrecord->caracterizacionid = $moduleinstance->id;
        $matrizrecord->programa_academico = $data->programa_academico;
        $matrizrecord->nombre_curso = $data->nombre_curso;
        $matrizrecord->fase_actual = 1;
        $matrizrecord->estado = 'borrador';
        $matrizrecord->creado_por = $USER->id;
        $matrizrecord->timecreated = $now;
        $matrizrecord->timemodified = $now;
        $matrizid = $DB->insert_record('caracterizacion_matriz', $matrizrecord);

        // Initialize all 6 phases.
        for ($i = 1; $i <= 6; $i++) {
            $faserecord = new stdClass();
            $faserecord->matrizid = $matrizid;
            $faserecord->fase = $i;
            $faserecord->estado = ($i === 1) ? 'en_revision' : 'pendiente';
            $faserecord->timecreated = $now;
            $faserecord->timemodified = $now;
            if ($i === 1) {
                $faserecord->fecha_inicio = $now;
            }
            $DB->insert_record('caracterizacion_fases', $faserecord);
        }
    } else {
        // Update existing matrix.
        $matrizrecord = new stdClass();
        $matrizrecord->id = $matrizid;
        $matrizrecord->programa_academico = $data->programa_academico;
        $matrizrecord->nombre_curso = $data->nombre_curso;
        $matrizrecord->timemodified = $now;
        $DB->update_record('caracterizacion_matriz', $matrizrecord);
    }

    // Save roles (including Jefe de Medios - 9 roles total).
    $DB->delete_records('caracterizacion_roles', ['matrizid' => $matrizid]);
    $roledefs = [
        'asesor_metodologico', 'experto_disciplinar', 'revisor_curricular',
        'par_academico', 'corrector_estilo', 'coord_produccion', 'jefe_medios',
        'produccion', 'alistamiento',
    ];
    foreach ($roledefs as $rolekey) {
        $fieldname = 'rol_' . $rolekey;
        if (!empty($data->$fieldname)) {
            $rolerecord = new stdClass();
            $rolerecord->matrizid = $matrizid;
            $rolerecord->rol = $rolekey;
            $rolerecord->userid = $data->$fieldname;
            $user = $DB->get_record('user', ['id' => $data->$fieldname]);
            $rolerecord->nombre_completo = $user ? fullname($user) : '';
            $rolerecord->timecreated = $now;
            $DB->insert_record('caracterizacion_roles', $rolerecord);
        }
    }

    // Save general resources.
    $DB->delete_records('caracterizacion_recursos_gen', ['matrizid' => $matrizid]);
    $generalresources = caracterizacion_get_general_resources();
    foreach ($generalresources as $reskey => $resstr) {
        $incluidofield = 'recgen_' . $reskey . '_incluido';
        $obsfield = 'recgen_' . $reskey . '_obs';
        $grrecord = new stdClass();
        $grrecord->matrizid = $matrizid;
        $grrecord->recurso = $reskey;
        $grrecord->incluido = !empty($data->$incluidofield) ? 1 : 0;
        $grrecord->observaciones = $data->$obsfield ?? '';
        $DB->insert_record('caracterizacion_recursos_gen', $grrecord);
    }

    // Save units/topics/resources from JSON data.
    // First delete existing data.
    $existingunidades = $DB->get_records('caracterizacion_unidades', ['matrizid' => $matrizid]);
    foreach ($existingunidades as $eu) {
        $existingtemas = $DB->get_records('caracterizacion_temas', ['unidadid' => $eu->id]);
        foreach ($existingtemas as $et) {
            $DB->delete_records('caracterizacion_recursos', ['temaid' => $et->id]);
        }
        $DB->delete_records('caracterizacion_temas', ['unidadid' => $eu->id]);
    }
    $DB->delete_records('caracterizacion_unidades', ['matrizid' => $matrizid]);

    // Insert new data from JSON.
    if (!empty($data->unidades_data)) {
        $unidadesdata = json_decode($data->unidades_data, true);
        if (is_array($unidadesdata)) {
            foreach ($unidadesdata as $udata) {
                $unidadrecord = new stdClass();
                $unidadrecord->matrizid = $matrizid;
                $unidadrecord->numero = $udata['numero'];
                $unidadrecord->nombre = $udata['nombre'] ?? '';
                $unidadrecord->timecreated = $now;
                $unidadrecord->timemodified = $now;
                $unidadid = $DB->insert_record('caracterizacion_unidades', $unidadrecord);

                if (!empty($udata['temas']) && is_array($udata['temas'])) {
                    foreach ($udata['temas'] as $tdata) {
                        $temarecord = new stdClass();
                        $temarecord->unidadid = $unidadid;
                        $temarecord->numero = $tdata['numero'];
                        $temarecord->nombre = $tdata['nombre'] ?? '';
                        $temaid = $DB->insert_record('caracterizacion_temas', $temarecord);

                        if (!empty($tdata['recursos']) && is_array($tdata['recursos'])) {
                            foreach ($tdata['recursos'] as $rdata) {
                                $recrecord = new stdClass();
                                $recrecord->temaid = $temaid;
                                $recrecord->tipo_recurso = $rdata['tipo_recurso'];
                                $recrecord->recurso = $rdata['recurso'];
                                $recrecord->item = $rdata['item'] ?? '';
                                $recrecord->observaciones = $rdata['observaciones'] ?? '';
                                $DB->insert_record('caracterizacion_recursos', $recrecord);
                            }
                        }
                    }
                }
            }
        }
    }

    $msgkey = $isnew ? 'matrizcreada' : 'matrizactualizada';
    redirect(
        new moodle_url('/mod/caracterizacion/view.php', ['id' => $cmid]),
        get_string($msgkey, 'mod_caracterizacion'),
        null,
        \core\output\notification::NOTIFY_SUCCESS
    );
}

echo $OUTPUT->header();
echo $OUTPUT->heading($pagetitle);
$form->display();
echo $OUTPUT->footer();
