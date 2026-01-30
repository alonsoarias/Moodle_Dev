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
 * English language strings for mod_caracterizacion.
 *
 * @package     mod_caracterizacion
 * @copyright   2024 Alonso Arias <soporte@orioncloud.com.co>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

// General.
$string['modulename'] = 'RED Characterization';
$string['modulenameplural'] = 'RED Characterizations';
$string['modulename_help'] = 'The RED Characterization module allows managing the Digital Educational Resources (RED) production process at UDES, following the 6 workflow phases: Characterization, Curricular Review, Peer/Style Corrector, Production, Moodle Setup, and Final Approval.';
$string['pluginadministration'] = 'RED Characterization administration';
$string['pluginname'] = 'RED Characterization';
$string['caracterizacion:addinstance'] = 'Add a new RED Characterization activity';
$string['caracterizacion:view'] = 'View RED Characterization activity';
$string['caracterizacion:crearmatriz'] = 'Create new characterization matrix';
$string['caracterizacion:editarmatriz'] = 'Edit characterization matrix';
$string['caracterizacion:eliminarmatriz'] = 'Delete characterization matrix';
$string['caracterizacion:expertodisciplinar'] = 'Act as disciplinary expert';
$string['caracterizacion:asesormetodologico'] = 'Act as methodological advisor';
$string['caracterizacion:revisorcurricular'] = 'Act as curricular reviewer';
$string['caracterizacion:paracademico'] = 'Act as academic peer';
$string['caracterizacion:correctorestilo'] = 'Act as style corrector';
$string['caracterizacion:coordproduccion'] = 'Act as production coordinator';
$string['caracterizacion:jefemedios'] = 'Act as head of educational media';
$string['caracterizacion:produccion'] = 'Act as production professional';
$string['caracterizacion:alistamiento'] = 'Act as setup professional';
$string['caracterizacion:aprobarfase'] = 'Approve or reject phases';
$string['caracterizacion:vertodasmatrices'] = 'View all characterization matrices';

// Activity form.
$string['activityname'] = 'Activity name';
$string['activitydescription'] = 'Activity description';

// Matrices.
$string['matrices'] = 'Characterization Matrices';
$string['nuevamatriz'] = 'New Characterization Matrix';
$string['editarmatriz'] = 'Edit Matrix';
$string['eliminarmatriz'] = 'Delete Matrix';
$string['confirmareliminar'] = 'Are you sure you want to delete this characterization matrix? This action cannot be undone.';
$string['matrizcreada'] = 'Characterization matrix created successfully.';
$string['matrizactualizada'] = 'Characterization matrix updated successfully.';
$string['matrizeliminada'] = 'Characterization matrix deleted successfully.';
$string['nomatrices'] = 'No characterization matrices created. Click "New Characterization Matrix" to start.';
$string['vermatriz'] = 'View Matrix';

// Course info.
$string['infodelcurso'] = 'Course Information';
$string['programaacademico'] = 'Academic Program';
$string['programaacademico_help'] = 'Enter the academic program name';
$string['nombredelcurso'] = 'Course Name';
$string['nombredelcurso_help'] = 'Enter the course name';

// Roles.
$string['asignacionroles'] = 'Role Assignment';
$string['asesormetodologico'] = 'Methodological Advisor';
$string['asesormetodologico_help'] = 'Enter the instructional designer name';
$string['expertodisciplinar'] = 'Disciplinary Expert';
$string['expertodisciplinar_help'] = 'Enter the disciplinary expert name';
$string['revisorcurricular'] = 'Curricular Reviewer';
$string['revisorcurricular_help'] = 'Enter the curricular reviewer name';
$string['paracademico'] = 'Academic Peer';
$string['paracademico_help'] = 'Enter the academic peer name';
$string['correctorestilo'] = 'Style Corrector';
$string['correctorestilo_help'] = 'Enter the style corrector name';
$string['coordproduccion'] = 'Production Coordinator';
$string['coordproduccion_help'] = 'Enter the production coordinator name';
$string['jefemedios'] = 'Head of Media';
$string['jefemedios_help'] = 'Enter the head of educational media name';
$string['produccion'] = 'Production';
$string['produccion_help'] = 'Enter the design professional name';
$string['alistamiento'] = 'Setup';
$string['alistamiento_help'] = 'Enter the setup professional name';
$string['seleccioneusuario'] = 'Select a user';

// General resources.
$string['recursosgenerales'] = 'Course General Resources';
$string['cvp'] = 'Portable Virtual Course - CVP';
$string['salavirtual'] = 'Virtual Classroom';
$string['videobienvenida'] = 'Welcome Video';
$string['forocurso'] = 'Course Forum';
$string['mapacurso'] = 'Course Map';
$string['incluir'] = 'Include';
$string['observaciones'] = 'Observations';

// Units and topics.
$string['unidades'] = 'Units';
$string['unidad'] = 'Unit';
$string['unidadnum'] = 'Unit {$a}';
$string['nombreunidad'] = 'Unit Name';
$string['nombreunidad_help'] = 'Enter the unit name';
$string['temas'] = 'Topics';
$string['tema'] = 'Topic';
$string['temanum'] = 'Topic {$a}';
$string['nombretema'] = 'Topic Name';
$string['nombretema_help'] = 'Enter the topic name';
$string['agregarunidad'] = 'Add Unit';
$string['eliminaunidad'] = 'Delete Unit';
$string['agregartema'] = 'Add Topic';
$string['eliminartema'] = 'Delete Topic';

// Resource types.
$string['recursosdelaunidad'] = 'Unit Resources';
$string['recursosdeltema'] = 'Topic Resources';
$string['tiporecurso'] = 'Resource Type';
$string['seleccionetiporecurso'] = 'Select resource type';
$string['recurso'] = 'Resource';
$string['seleccionerecurso'] = 'Select resource';
$string['item'] = 'Item';
$string['agregarrecurso'] = 'Add Resource';
$string['eliminarrecurso'] = 'Delete Resource';

// Resource categories.
$string['educativo_digital'] = 'Digital Educational Resources';
$string['interactivo_digital'] = 'Digital Interactive Resources';
$string['evaluativo'] = 'Evaluative Resources';
$string['colaborativo'] = 'Collaborative Resources';
$string['externo'] = 'External Resources';

// Specific resources - Educational Digital.
$string['rec_ebook'] = 'E-book';
$string['rec_videoclase'] = 'Video Class';
$string['rec_podcast'] = 'Podcast';
$string['rec_comicvirtual'] = 'Virtual Comic';
$string['rec_pasoapaso'] = 'Step by Step';
$string['rec_lineadetiempo'] = 'Timeline';
$string['rec_infografia'] = 'Infographic';
$string['rec_mapaconceptual'] = 'Concept Map';
$string['rec_mapamental'] = 'Mind Map';
$string['rec_videointeractivo'] = 'Interactive Video';
$string['rec_videodiapositivas'] = 'Video with Slides';
$string['rec_videoexplicativo'] = 'Explanatory Video';

// Specific resources - Interactive Digital.
$string['rec_hotspots'] = 'Hotspots';
$string['rec_emparejamiento'] = 'Matching';
$string['rec_arrastrapalabras'] = 'Drag Words';
$string['rec_crucigrama'] = 'Crossword';
$string['rec_ordenaparrafos'] = 'Order Paragraphs';
$string['rec_sopadeletras'] = 'Word Search';
$string['rec_glosariointeractivo'] = 'Interactive Glossary';

// Specific resources - Evaluative.
$string['rec_opcionunica'] = 'Single Choice';
$string['rec_opcionmultiple'] = 'Multiple Choice';
$string['rec_verdaderofalso'] = 'True or False';
$string['rec_marcapalabras'] = 'Mark the Words';
$string['rec_espaciosenblanco'] = 'Fill in the Blanks';
$string['rec_dictado'] = 'Dictation';
$string['rec_tarjetadidactica'] = 'Flashcard';
$string['rec_tarjetasdedialogo'] = 'Dialogue Cards';

// Specific resources - Collaborative.
$string['rec_wiki'] = 'Wiki';
$string['rec_tarea'] = 'Assignment';
$string['rec_leccion'] = 'Lesson';
$string['rec_forotematico'] = 'Thematic Forum';
$string['rec_forosocial'] = 'Social Forum';

// Specific resources - External.
$string['rec_videoconferencias'] = 'Video Conferences';
$string['rec_paquetes'] = 'Packages';
$string['rec_plataformasexternas'] = 'External Platforms';

// Phases.
$string['fases'] = 'Process Phases';
$string['fase'] = 'Phase';
$string['fasenum'] = 'Phase {$a}';
$string['fase1'] = 'Phase 1: Fill in Characterization';
$string['fase2'] = 'Phase 2: Curricular Review';
$string['fase3'] = 'Phase 3: Peer / Style Corrector';
$string['fase4'] = 'Phase 4: Production';
$string['fase5'] = 'Phase 5: Moodle Setup';
$string['fase6'] = 'Phase 6: Final Course Approval';
$string['faseactual'] = 'Current Phase';
$string['faseanterior'] = 'Previous Phase';
$string['fasesiguiente'] = 'Next Phase';
$string['progresofases'] = 'Phase Progress';

// Phase states.
$string['pendiente'] = 'Pending';
$string['en_revision'] = 'In Review';
$string['aprobada'] = 'Approved';
$string['rechazada'] = 'Rejected';
$string['borrador'] = 'Draft';
$string['en_proceso'] = 'In Progress';
$string['completada'] = 'Completed';

// Phase actions.
$string['aprobar'] = 'Approve';
$string['rechazar'] = 'Reject';
$string['comentar'] = 'Comment';
$string['reenviar'] = 'Resubmit for Review';
$string['enviarrevision'] = 'Submit for Review';
$string['comentarioobligatorio'] = 'You must enter a comment for this action.';
$string['accionrealizada'] = 'Action performed successfully.';
$string['sinpermisosfase'] = 'You do not have permissions to act on this phase.';
$string['faseanterionnoaprobada'] = 'The previous phase has not been approved yet.';

// Comments.
$string['comentarios'] = 'Comments';
$string['agregarcomentario'] = 'Add Comment';
$string['sincomentarios'] = 'No comments for this phase.';
$string['comentariode'] = 'Comment by {$a}';

// Notifications.
$string['notificaciones'] = 'Notifications';
$string['notif_fase_aprobada'] = '{$a->fase} of the matrix "{$a->matriz}" has been approved by {$a->usuario}.';
$string['notif_fase_rechazada'] = '{$a->fase} of the matrix "{$a->matriz}" has been rejected by {$a->usuario}.';
$string['notif_fase_envio'] = '{$a->fase} of the matrix "{$a->matriz}" has been submitted for review by {$a->usuario}.';
$string['notif_subject_aprobada'] = 'Phase approved - {$a}';
$string['notif_subject_rechazada'] = 'Phase rejected - {$a}';
$string['notif_subject_envio'] = 'New phase for review - {$a}';
$string['messageprovider:faseaprobada'] = 'Phase approved notification';
$string['messageprovider:faserechazada'] = 'Phase rejected notification';
$string['messageprovider:faseenvio'] = 'Phase submission notification';

// Summary / Counters.
$string['resumen'] = 'Resource Summary';
$string['cantidadtotalgeneral'] = 'Total General Resources';
$string['cantidadtotaleducativos'] = 'Total Digital Educational Resources';
$string['cantidadtotalinteractivos'] = 'Total Digital Interactive Resources';
$string['cantidadtotalevaluativos'] = 'Total Evaluative Resources';
$string['cantidadtotalcolaborativos'] = 'Total Collaborative Resources';
$string['cantidadtotalexternos'] = 'Total External Resources';
$string['cantidadtotalrecursos'] = 'Total Resources';

// Actions.
$string['guardar'] = 'Save';
$string['cancelar'] = 'Cancel';
$string['volver'] = 'Back';
$string['eliminar'] = 'Delete';
$string['editar'] = 'Edit';
$string['ver'] = 'View';
$string['acciones'] = 'Actions';
$string['estado'] = 'Status';
$string['creador'] = 'Creator';
$string['fechacreacion'] = 'Creation Date';
$string['ultimamodificacion'] = 'Last Modified';
$string['guardarcontinuar'] = 'Save and Continue';

// Errors.
$string['errorgenerico'] = 'An error occurred while processing the request.';
$string['errormatriznoexiste'] = 'The characterization matrix does not exist.';
$string['errorsinpermisos'] = 'You do not have permissions to perform this action.';
