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
 * Library of interface functions and constants.
 *
 * @package     mod_caracterizacion
 * @copyright   2024 Alonso Arias <soporte@orioncloud.com.co>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Returns the information on whether the module supports a feature.
 *
 * @param string $feature FEATURE_xx constant for requested feature.
 * @return mixed True if module supports feature, false if not, null if doesn't know or string for the module purpose.
 */
function caracterizacion_supports($feature) {
    switch ($feature) {
        case FEATURE_MOD_INTRO:
            return true;
        case FEATURE_SHOW_DESCRIPTION:
            return true;
        case FEATURE_BACKUP_MOODLE2:
            return true;
        case FEATURE_MOD_PURPOSE:
            return MOD_PURPOSE_COLLABORATION;
        default:
            return null;
    }
}

/**
 * Saves a new instance of the mod_caracterizacion into the database.
 *
 * @param stdClass $data An object from the form.
 * @param mod_caracterizacion_mod_form $mform The form.
 * @return int The id of the newly inserted record.
 */
function caracterizacion_add_instance($data, $mform = null) {
    global $DB;

    $data->timecreated = time();
    $data->timemodified = time();

    $id = $DB->insert_record('caracterizacion', $data);

    return $id;
}

/**
 * Updates an instance of the mod_caracterizacion in the database.
 *
 * @param stdClass $data An object from the form.
 * @param mod_caracterizacion_mod_form $mform The form.
 * @return bool True if successful.
 */
function caracterizacion_update_instance($data, $mform = null) {
    global $DB;

    $data->timemodified = time();
    $data->id = $data->instance;

    return $DB->update_record('caracterizacion', $data);
}

/**
 * Removes an instance of the mod_caracterizacion from the database.
 *
 * @param int $id Id of the module instance.
 * @return bool True if successful.
 */
function caracterizacion_delete_instance($id) {
    global $DB;

    $exists = $DB->get_record('caracterizacion', ['id' => $id]);
    if (!$exists) {
        return false;
    }

    // Delete all related matrices and their data.
    $matrices = $DB->get_records('caracterizacion_matriz', ['caracterizacionid' => $id]);
    foreach ($matrices as $matriz) {
        caracterizacion_delete_matriz_data($matriz->id);
    }

    $DB->delete_records('caracterizacion', ['id' => $id]);

    return true;
}

/**
 * Delete all data related to a specific matrix.
 *
 * @param int $matrizid The matrix ID.
 */
function caracterizacion_delete_matriz_data($matrizid) {
    global $DB;

    // Delete notifications.
    $DB->delete_records('caracterizacion_notif', ['matrizid' => $matrizid]);

    // Delete comments for all phases.
    $fases = $DB->get_records('caracterizacion_fases', ['matrizid' => $matrizid]);
    foreach ($fases as $fase) {
        $DB->delete_records('caracterizacion_comentarios', ['faseid' => $fase->id]);
    }

    // Delete phases.
    $DB->delete_records('caracterizacion_fases', ['matrizid' => $matrizid]);

    // Delete resources for all topics in all units.
    $unidades = $DB->get_records('caracterizacion_unidades', ['matrizid' => $matrizid]);
    foreach ($unidades as $unidad) {
        $temas = $DB->get_records('caracterizacion_temas', ['unidadid' => $unidad->id]);
        foreach ($temas as $tema) {
            $DB->delete_records('caracterizacion_recursos', ['temaid' => $tema->id]);
        }
        $DB->delete_records('caracterizacion_temas', ['unidadid' => $unidad->id]);
    }

    // Delete units.
    $DB->delete_records('caracterizacion_unidades', ['matrizid' => $matrizid]);

    // Delete general resources.
    $DB->delete_records('caracterizacion_recursos_gen', ['matrizid' => $matrizid]);

    // Delete roles.
    $DB->delete_records('caracterizacion_roles', ['matrizid' => $matrizid]);

    // Delete the matrix itself.
    $DB->delete_records('caracterizacion_matriz', ['id' => $matrizid]);
}

/**
 * Get resource types and their specific resources.
 *
 * @return array Associative array of resource types and their resources.
 */
function caracterizacion_get_resource_types() {
    return [
        'educativo_digital' => [
            'ebook' => 'rec_ebook',
            'videoclase' => 'rec_videoclase',
            'podcast' => 'rec_podcast',
            'comicvirtual' => 'rec_comicvirtual',
            'pasoapaso' => 'rec_pasoapaso',
            'lineadetiempo' => 'rec_lineadetiempo',
            'infografia' => 'rec_infografia',
            'mapaconceptual' => 'rec_mapaconceptual',
            'mapamental' => 'rec_mapamental',
            'videointeractivo' => 'rec_videointeractivo',
            'videodiapositivas' => 'rec_videodiapositivas',
            'videoexplicativo' => 'rec_videoexplicativo',
        ],
        'interactivo_digital' => [
            'hotspots' => 'rec_hotspots',
            'emparejamiento' => 'rec_emparejamiento',
            'arrastrapalabras' => 'rec_arrastrapalabras',
            'crucigrama' => 'rec_crucigrama',
            'ordenaparrafos' => 'rec_ordenaparrafos',
            'sopadeletras' => 'rec_sopadeletras',
            'glosariointeractivo' => 'rec_glosariointeractivo',
        ],
        'evaluativo' => [
            'opcionunica' => 'rec_opcionunica',
            'opcionmultiple' => 'rec_opcionmultiple',
            'verdaderofalso' => 'rec_verdaderofalso',
            'marcapalabras' => 'rec_marcapalabras',
            'espaciosenblanco' => 'rec_espaciosenblanco',
            'dictado' => 'rec_dictado',
            'tarjetadidactica' => 'rec_tarjetadidactica',
            'tarjetasdedialogo' => 'rec_tarjetasdedialogo',
        ],
        'colaborativo' => [
            'wiki' => 'rec_wiki',
            'tarea' => 'rec_tarea',
            'leccion' => 'rec_leccion',
            'forotematico' => 'rec_forotematico',
            'forosocial' => 'rec_forosocial',
        ],
        'externo' => [
            'videoconferencias' => 'rec_videoconferencias',
            'paquetes' => 'rec_paquetes',
            'plataformasexternas' => 'rec_plataformasexternas',
        ],
    ];
}

/**
 * Get general resource types.
 *
 * @return array Associative array of general resources.
 */
function caracterizacion_get_general_resources() {
    return [
        'cvp' => 'cvp',
        'sala_virtual' => 'salavirtual',
        'video_bienvenida' => 'videobienvenida',
        'foro_curso' => 'forocurso',
        'mapa_curso' => 'mapacurso',
    ];
}

/**
 * Get phase definitions.
 *
 * @return array Associative array of phase number => string identifier.
 */
function caracterizacion_get_phases() {
    return [
        1 => 'fase1',
        2 => 'fase2',
        3 => 'fase3',
        4 => 'fase4',
        5 => 'fase5',
        6 => 'fase6',
    ];
}

/**
 * Get all capabilities for users who can participate (act) in a given phase.
 *
 * According to the UDES workflow:
 * - Phase 1: Experto fills in, Asesor accompanies.
 * - Phase 2: Revisor reviews, Asesor coordinates adjustments.
 * - Phase 3: Par reviews, Corrector adjusts, Asesor coordinates.
 * - Phase 4: Coordinacion assigns, Produccion develops.
 * - Phase 5: Alistamiento sets up in Moodle.
 * - Phase 6: Asesor gives final approval.
 *
 * @param int $fase Phase number.
 * @return array Array of capability strings for actors in the phase.
 */
function caracterizacion_get_phase_actor_capabilities($fase) {
    $map = [
        1 => ['mod/caracterizacion:expertodisciplinar', 'mod/caracterizacion:asesormetodologico'],
        2 => ['mod/caracterizacion:revisorcurricular', 'mod/caracterizacion:asesormetodologico'],
        3 => ['mod/caracterizacion:paracademico', 'mod/caracterizacion:correctorestilo',
               'mod/caracterizacion:asesormetodologico'],
        4 => ['mod/caracterizacion:coordproduccion', 'mod/caracterizacion:produccion'],
        5 => ['mod/caracterizacion:alistamiento'],
        6 => ['mod/caracterizacion:asesormetodologico'],
    ];
    return $map[$fase] ?? [];
}

/**
 * Get the capability required to APPROVE a given phase.
 *
 * According to the UDES workflow:
 * - Phases 1, 2, 3, 6: Asesor metodologico approves.
 * - Phase 4: Coordinacion de produccion approves.
 * - Phase 5: Alistamiento completes the setup.
 *
 * @param int $fase Phase number.
 * @return array Array of capability strings that can approve.
 */
function caracterizacion_get_phase_approval_capabilities($fase) {
    $map = [
        1 => ['mod/caracterizacion:asesormetodologico'],
        2 => ['mod/caracterizacion:asesormetodologico'],
        3 => ['mod/caracterizacion:asesormetodologico'],
        4 => ['mod/caracterizacion:coordproduccion'],
        5 => ['mod/caracterizacion:alistamiento'],
        6 => ['mod/caracterizacion:asesormetodologico'],
    ];
    return $map[$fase] ?? [];
}

/**
 * Get the roles to notify when a phase transitions.
 *
 * Following the UDES notification flow:
 * - Phase 1 approved -> Notify Revisor Curricular.
 * - Phase 2 approved -> Notify Par / Corrector.
 * - Phase 3 approved -> Notify Coordinacion de Produccion.
 * - Phase 4 approved -> Notify Alistamiento.
 * - Phase 5 approved -> Notify Asesor Metodologico.
 * - Phase 6 approved -> All (completed).
 *
 * @param int $fase The phase that was just approved.
 * @return array Array of role keys to notify.
 */
function caracterizacion_get_phase_notification_targets($fase) {
    $map = [
        1 => ['revisor_curricular'],
        2 => ['par_academico', 'corrector_estilo'],
        3 => ['coord_produccion'],
        4 => ['alistamiento'],
        5 => ['asesor_metodologico'],
        6 => [],
    ];
    return $map[$fase] ?? [];
}

/**
 * Check if a user can act (participate) on a specific phase.
 *
 * @param int $fase Phase number.
 * @param object $context The module context.
 * @param int|null $userid The user ID (null for current user).
 * @return bool True if the user can act on the phase.
 */
function caracterizacion_user_can_act_on_phase($fase, $context, $userid = null) {
    $capabilities = caracterizacion_get_phase_actor_capabilities($fase);
    foreach ($capabilities as $cap) {
        if (has_capability($cap, $context, $userid)) {
            return true;
        }
    }
    return false;
}

/**
 * Check if a user can APPROVE or REJECT a specific phase.
 *
 * @param int $fase Phase number.
 * @param object $context The module context.
 * @param int|null $userid The user ID (null for current user).
 * @return bool True if the user can approve the phase.
 */
function caracterizacion_user_can_approve_phase($fase, $context, $userid = null) {
    $capabilities = caracterizacion_get_phase_approval_capabilities($fase);
    foreach ($capabilities as $cap) {
        if (has_capability($cap, $context, $userid)) {
            return true;
        }
    }
    return false;
}
