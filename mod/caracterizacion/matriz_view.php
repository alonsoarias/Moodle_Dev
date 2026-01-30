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
 * View a characterization matrix with phase management.
 *
 * @package     mod_caracterizacion
 * @copyright   2024 Alonso Arias <soporte@orioncloud.com.co>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');
require_once($CFG->dirroot . '/mod/caracterizacion/lib.php');

$cmid = required_param('cmid', PARAM_INT);
$matrizid = required_param('matrizid', PARAM_INT);

$cm = get_coursemodule_from_id('caracterizacion', $cmid, 0, false, MUST_EXIST);
$course = $DB->get_record('course', ['id' => $cm->course], '*', MUST_EXIST);
$moduleinstance = $DB->get_record('caracterizacion', ['id' => $cm->instance], '*', MUST_EXIST);

require_login($course, true, $cm);
$context = context_module::instance($cm->id);
require_capability('mod/caracterizacion:view', $context);

$matriz = $DB->get_record('caracterizacion_matriz', ['id' => $matrizid], '*', MUST_EXIST);

$PAGE->set_url('/mod/caracterizacion/matriz_view.php', ['cmid' => $cmid, 'matrizid' => $matrizid]);
$PAGE->set_title(get_string('vermatriz', 'mod_caracterizacion'));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_context($context);

// Load AMD module for phase interactions.
$PAGE->requires->js_call_amd('mod_caracterizacion/fase_manager', 'init', [[
    'cmid' => $cmid,
    'matrizid' => $matrizid,
    'currentphase' => (int) $matriz->fase_actual,
]]);

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('vermatriz', 'mod_caracterizacion'));

// Back button.
$backurl = new moodle_url('/mod/caracterizacion/view.php', ['id' => $cmid]);
echo html_writer::div(
    html_writer::link($backurl, get_string('volver', 'mod_caracterizacion'), ['class' => 'btn btn-secondary mb-3']),
    'mb-3'
);

// Build template data.
$templatedata = [
    'matrizid' => $matrizid,
    'cmid' => $cmid,
    'programa_academico' => format_string($matriz->programa_academico),
    'nombre_curso' => format_string($matriz->nombre_curso),
    'sesskey' => sesskey(),
];

// Creator info.
$creator = $DB->get_record('user', ['id' => $matriz->creado_por]);
$templatedata['creador'] = $creator ? fullname($creator) : '-';
$templatedata['fechacreacion'] = userdate($matriz->timecreated);

// Roles.
$roles = $DB->get_records('caracterizacion_roles', ['matrizid' => $matrizid]);
$rolelabels = [
    'asesor_metodologico' => 'asesormetodologico',
    'experto_disciplinar' => 'expertodisciplinar',
    'revisor_curricular' => 'revisorcurricular',
    'par_academico' => 'paracademico',
    'corrector_estilo' => 'correctorestilo',
    'coord_produccion' => 'coordproduccion',
    'produccion' => 'produccion',
    'alistamiento' => 'alistamiento',
];
$templatedata['roles'] = [];
foreach ($roles as $role) {
    $label = isset($rolelabels[$role->rol]) ?
        get_string($rolelabels[$role->rol], 'mod_caracterizacion') : $role->rol;
    $templatedata['roles'][] = [
        'label' => $label,
        'nombre' => format_string($role->nombre_completo),
    ];
}

// General resources.
$genresources = $DB->get_records('caracterizacion_recursos_gen', ['matrizid' => $matrizid]);
$genlabels = caracterizacion_get_general_resources();
$templatedata['recursos_generales'] = [];
foreach ($genresources as $gr) {
    $label = isset($genlabels[$gr->recurso]) ?
        get_string($genlabels[$gr->recurso], 'mod_caracterizacion') : $gr->recurso;
    $templatedata['recursos_generales'][] = [
        'label' => $label,
        'incluido' => (bool) $gr->incluido,
        'observaciones' => format_string($gr->observaciones),
    ];
}

// Units, topics, resources.
$unidades = $DB->get_records('caracterizacion_unidades', ['matrizid' => $matrizid], 'numero ASC');
$templatedata['unidades'] = [];
$resourcecounts = [
    'educativo_digital' => 0,
    'interactivo_digital' => 0,
    'evaluativo' => 0,
    'colaborativo' => 0,
    'externo' => 0,
];
$totalgeneral = 0;

foreach ($unidades as $unidad) {
    $udata = [
        'numero' => $unidad->numero,
        'nombre' => format_string($unidad->nombre),
        'temas' => [],
    ];
    $temas = $DB->get_records('caracterizacion_temas', ['unidadid' => $unidad->id], 'numero ASC');
    foreach ($temas as $tema) {
        $tdata = [
            'numero' => $unidad->numero . '.' . $tema->numero,
            'nombre' => format_string($tema->nombre),
            'recursos' => [],
        ];
        $recursos = $DB->get_records('caracterizacion_recursos', ['temaid' => $tema->id]);
        foreach ($recursos as $rec) {
            $tipolabel = get_string($rec->tipo_recurso, 'mod_caracterizacion');
            $reclabel = get_string('rec_' . $rec->recurso, 'mod_caracterizacion');
            $tdata['recursos'][] = [
                'tipo' => $tipolabel,
                'recurso' => $reclabel,
                'item' => format_string($rec->item),
                'observaciones' => format_text($rec->observaciones, FORMAT_PLAIN),
                'hasobservaciones' => !empty($rec->observaciones),
            ];
            if (isset($resourcecounts[$rec->tipo_recurso])) {
                $resourcecounts[$rec->tipo_recurso]++;
            }
            $totalgeneral++;
        }
        $tdata['hasrecursos'] = !empty($tdata['recursos']);
        $udata['temas'][] = $tdata;
    }
    $udata['hastemas'] = !empty($udata['temas']);
    $templatedata['unidades'][] = $udata;
}
$templatedata['hasunidades'] = !empty($templatedata['unidades']);

// Resource summary.
$generalcount = $DB->count_records_select('caracterizacion_recursos_gen',
    'matrizid = ? AND incluido = 1', [$matrizid]);
$templatedata['resumen'] = [
    ['label' => get_string('cantidadtotalgeneral', 'mod_caracterizacion'), 'count' => $generalcount],
    ['label' => get_string('cantidadtotaleducativos', 'mod_caracterizacion'), 'count' => $resourcecounts['educativo_digital']],
    ['label' => get_string('cantidadtotalinteractivos', 'mod_caracterizacion'), 'count' => $resourcecounts['interactivo_digital']],
    ['label' => get_string('cantidadtotalevaluativos', 'mod_caracterizacion'), 'count' => $resourcecounts['evaluativo']],
    ['label' => get_string('cantidadtotalcolaborativos', 'mod_caracterizacion'), 'count' => $resourcecounts['colaborativo']],
    ['label' => get_string('cantidadtotalexternos', 'mod_caracterizacion'), 'count' => $resourcecounts['externo']],
    ['label' => get_string('cantidadtotalrecursos', 'mod_caracterizacion'),
     'count' => $totalgeneral + $generalcount, 'istotal' => true],
];

// Phase progress.
$phases = caracterizacion_get_phases();
$faserecords = $DB->get_records('caracterizacion_fases', ['matrizid' => $matrizid], 'fase ASC');
$templatedata['fases'] = [];
foreach ($faserecords as $fr) {
    $fasedata = [
        'faseid' => $fr->id,
        'fase' => $fr->fase,
        'label' => get_string($phases[$fr->fase], 'mod_caracterizacion'),
        'estado' => get_string($fr->estado, 'mod_caracterizacion'),
        'is_pendiente' => ($fr->estado === 'pendiente'),
        'is_en_revision' => ($fr->estado === 'en_revision'),
        'is_aprobada' => ($fr->estado === 'aprobada'),
        'is_rechazada' => ($fr->estado === 'rechazada'),
        'is_current' => ($fr->fase == $matriz->fase_actual),
        'canact' => caracterizacion_user_can_act_on_phase($fr->fase, $context),
        'canapprove' => caracterizacion_user_can_approve_phase($fr->fase, $context),
        'comentarios' => [],
    ];

    // Load comments for this phase.
    $comentarios = $DB->get_records('caracterizacion_comentarios', ['faseid' => $fr->id], 'timecreated DESC');
    foreach ($comentarios as $com) {
        $comuser = $DB->get_record('user', ['id' => $com->userid]);
        $accionstr = get_string($com->accion, 'mod_caracterizacion');
        $fasedata['comentarios'][] = [
            'usuario' => $comuser ? fullname($comuser) : '-',
            'comentario' => format_text($com->comentario, FORMAT_PLAIN),
            'accion' => $accionstr,
            'fecha' => userdate($com->timecreated),
        ];
    }
    $fasedata['hascomentarios'] = !empty($fasedata['comentarios']);

    $templatedata['fases'][] = $fasedata;
}

echo $OUTPUT->render_from_template('mod_caracterizacion/matriz_view', $templatedata);

echo $OUTPUT->footer();
