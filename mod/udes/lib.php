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
 * @package     mod_udes
 * @copyright   2026 Universidad de Santander - UDES
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Supported features.
 *
 * @param string $feature FEATURE_xx constant for requested feature
 * @return mixed True if module supports feature, false if not, null if doesn't know
 */
function udes_supports($feature) {
    switch($feature) {
        case FEATURE_MOD_INTRO:
            return true;
        case FEATURE_BACKUP_MOODLE2:
            return true;
        case FEATURE_SHOW_DESCRIPTION:
            return true;
        case FEATURE_GRADE_HAS_GRADE:
            return false;
        case FEATURE_GROUPS:
            return true;
        case FEATURE_GROUPINGS:
            return true;
        case FEATURE_COMPLETION_TRACKS_VIEWS:
            return true;
        default:
            return null;
    }
}

/**
 * Saves a new instance of the mod_udes into the database.
 *
 * @param stdClass $data An object from the form
 * @param mod_udes_mod_form $mform The form
 * @return int The id of the newly inserted record
 */
function udes_add_instance($data, $mform = null) {
    global $DB;

    $data->timecreated = time();
    $data->timemodified = time();

    $id = $DB->insert_record('udes', $data);

    return $id;
}

/**
 * Updates an instance of the mod_udes in the database.
 *
 * @param stdClass $data An object from the form
 * @param mod_udes_mod_form $mform The form
 * @return bool True if successful, false otherwise
 */
function udes_update_instance($data, $mform = null) {
    global $DB;

    $data->timemodified = time();
    $data->id = $data->instance;

    return $DB->update_record('udes', $data);
}

/**
 * Removes an instance of the mod_udes from the database.
 *
 * v2.0: Deletes all caracterizaciones and their related data using cascade delete.
 *
 * @param int $id Id of the module instance
 * @return bool True if successful, false on failure
 */
function udes_delete_instance($id) {
    global $DB;

    $exists = $DB->get_record('udes', array('id' => $id));
    if (!$exists) {
        return false;
    }

    // v2.0: Get all caracterizaciones for this activity.
    $caracterizaciones = $DB->get_records('udes_caracterizacion', array('udesid' => $id), '', 'id');

    // Delete each caracterizacion with cascade (recursos, workflow, aprobaciones, comentarios).
    foreach ($caracterizaciones as $caract) {
        \mod_udes\caracterizacion_manager::delete_caracterizacion($caract->id);
    }

    // Delete role assignments.
    $DB->delete_records('udes_role_assignments', array('udesid' => $id));

    // Delete main record.
    $DB->delete_records('udes', array('id' => $id));

    return true;
}

/**
 * Returns the lists of all browsable file areas within the given module context.
 *
 * @param stdClass $course
 * @param stdClass $cm
 * @param stdClass $context
 * @return array
 */
function udes_get_file_areas($course, $cm, $context) {
    return array(
        'recursos' => get_string('recursos', 'mod_udes'),
        'caracterizacion' => get_string('caracterizacion', 'mod_udes'),
    );
}

/**
 * File browsing support for mod_udes file areas.
 *
 * @param file_browser $browser
 * @param array $areas
 * @param stdClass $course
 * @param stdClass $cm
 * @param stdClass $context
 * @param string $filearea
 * @param int $itemid
 * @param string $filepath
 * @param string $filename
 * @return file_info instance or null if not found
 */
function udes_get_file_info($browser, $areas, $course, $cm, $context, $filearea, $itemid, $filepath, $filename) {
    return null;
}

/**
 * Serves the files from the mod_udes file areas.
 *
 * @param stdClass $course
 * @param stdClass $cm
 * @param stdClass $context
 * @param string $filearea
 * @param array $args
 * @param bool $forcedownload
 * @param array $options
 * @return bool False if we don't find a file
 */
function udes_pluginfile($course, $cm, $context, $filearea, array $args, $forcedownload, array $options = array()) {
    global $DB, $USER;

    if ($context->contextlevel != CONTEXT_MODULE) {
        return false;
    }

    require_login($course, true, $cm);

    // Prevent download - only view in browser.
    $forcedownload = false;

    $fs = get_file_storage();
    $relativepath = implode('/', $args);
    $fullpath = rtrim("/$context->id/mod_udes/$filearea/$relativepath", '/');

    $file = $fs->get_file_by_hash(sha1($fullpath));
    if (!$file || $file->is_directory()) {
        return false;
    }

    send_stored_file($file, 0, 0, $forcedownload, $options);
}

/**
 * Get workflow phases for UDES production process.
 *
 * @return array
 */
function udes_get_workflow_phases() {
    return array(
        1 => get_string('fase1_caracterizacion', 'mod_udes'),
        2 => get_string('fase2_revision_curricular', 'mod_udes'),
        3 => get_string('fase3_par_corrector', 'mod_udes'),
        4 => get_string('fase4_produccion', 'mod_udes'),
        5 => get_string('fase5_alistamiento', 'mod_udes'),
        6 => get_string('fase6_aprobacion_final', 'mod_udes'),
    );
}

/**
 * Get UDES roles.
 *
 * @return array
 */
function udes_get_roles() {
    return array(
        'experto_disciplinar' => get_string('role_experto_disciplinar', 'mod_udes'),
        'asesor_metodologico' => get_string('role_asesor_metodologico', 'mod_udes'),
        'revisor_curricular' => get_string('role_revisor_curricular', 'mod_udes'),
        'par_disciplinar' => get_string('role_par_disciplinar', 'mod_udes'),
        'corrector_estilo' => get_string('role_corrector_estilo', 'mod_udes'),
        'coordinacion_produccion' => get_string('role_coordinacion_produccion', 'mod_udes'),
        'produccion' => get_string('role_produccion', 'mod_udes'),
        'alistamiento' => get_string('role_alistamiento', 'mod_udes'),
    );
}

/**
 * Get resource types and categories.
 *
 * @return array
 */
function udes_get_resource_types() {
    return array(
        'RECURSOS_EDUCATIVOS_DIGITALES' => array(
            'E-book',
            'Video Clase',
            'Podcast',
            'Comic Virtual',
            'Paso a Paso',
            'Línea de Tiempo',
            'Infografía',
            'Mapa Conceptual',
            'Mapa Mental',
            'Video Interactivo',
            'Video con diapositivas',
            'Video explicativo',
        ),
        'RECURSOS_INTERACTIVOS_DIGITALES' => array(
            'Opción única',
            'Opción múltiple',
            'Verdadero o falso',
            'Marca las palabras',
            'Espacios en blanco',
            'Dictado',
            'Tarjeta didáctica',
            'Tarjetas de diálogo',
            'Hotspots',
            'Emparejamiento',
            'Arrastra las palabras',
            'Crucigrama',
            'Ordena los párrafos',
            'Sopa de letras',
            'Glosario interactivo',
        ),
        'RECURSOS_EVALUATIVOS' => array(
            'Tarea',
            'Lección',
        ),
        'RECURSOS_COLABORATIVOS' => array(
            'Wiki',
            'Foro temático',
            'Foro social',
        ),
        'RECURSOS_EXTERNOS' => array(
            'Paquetes',
            'Plataformas externas',
            'Video conferencias',
        ),
    );
}

/**
 * Get resource category name.
 *
 * @param string $category
 * @return string
 */
function udes_get_category_name($category) {
    $categories = array(
        'RECURSOS_EDUCATIVOS_DIGITALES' => get_string('recursos_educativos_digitales', 'mod_udes'),
        'RECURSOS_INTERACTIVOS_DIGITALES' => get_string('recursos_interactivos_digitales', 'mod_udes'),
        'RECURSOS_EVALUATIVOS' => get_string('recursos_evaluativos', 'mod_udes'),
        'RECURSOS_COLABORATIVOS' => get_string('recursos_colaborativos', 'mod_udes'),
        'RECURSOS_EXTERNOS' => get_string('recursos_externos', 'mod_udes'),
    );

    return isset($categories[$category]) ? $categories[$category] : $category;
}
