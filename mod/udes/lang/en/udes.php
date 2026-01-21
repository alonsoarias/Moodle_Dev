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
 * Plugin strings are defined here.
 *
 * @package     mod_udes
 * @copyright   2026 Universidad de Santander - UDES (udes.edu.co)
 * @author      Alonso Arias <soporte@orioncloud.com.co>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['pluginname'] = 'UDES Educational Resources Production System';
$string['modulename'] = 'UDES Resources Production';
$string['modulenameplural'] = 'UDES Resources Production';
$string['pluginadministration'] = 'UDES Production Administration';

// Capabilities.
$string['udes:addinstance'] = 'Add new UDES Production activity';
$string['udes:view'] = 'View UDES Production activity';
$string['udes:expertodisciplinar'] = 'Act as Subject Matter Expert';
$string['udes:asesormetodologico'] = 'Act as Methodological Advisor';
$string['udes:revisorcurricular'] = 'Act as Curricular Reviewer';
$string['udes:pardisciplinar'] = 'Act as Academic Peer';
$string['udes:correctorestilo'] = 'Act as Style Corrector';
$string['udes:coordinacionproduccion'] = 'Act as Production Coordinator';
$string['udes:produccion'] = 'Act as Production';
$string['udes:alistamiento'] = 'Act as Readiness';
$string['udes:approve'] = 'Approve phases';
$string['udes:reject'] = 'Reject phases';
$string['udes:viewreports'] = 'View reports';
$string['udes:manageall'] = 'Manage all phases';

// General.
$string['name'] = 'Name';
$string['description'] = 'Description';
$string['recursos'] = 'Resources';
$string['caracterizacion'] = 'Characterization';

// v2.0: Multiple characterizations.
$string['caracterizaciones'] = 'Characterizations';
$string['caracterizaciones_info'] = 'This activity allows creating multiple characterization matrices. Each with its own team, resources and independent 6-phase workflow.';
$string['caracterizacion_nombre'] = 'Characterization Name';
$string['caracterizacion_nombre_help'] = 'Enter an identifier name for this characterization matrix (eg: "Mathematics I - 2026-1")';
$string['nueva_caracterizacion'] = 'New Characterization';
$string['editar_caracterizacion'] = 'Edit Characterization';
$string['eliminar_caracterizacion'] = 'Delete Characterization';
$string['ver_caracterizacion'] = 'View Characterization';
$string['lista_caracterizaciones'] = 'Characterizations List';
$string['sin_caracterizaciones'] = 'No characterizations created yet. Create the first characterization to begin.';
$string['confirm_delete_caracterizacion'] = 'Are you sure you want to delete this characterization? This action will delete all associated resources, comments and approvals.';
$string['error_caracterizacion_nombre_empty'] = 'The characterization name cannot be empty';
$string['course_information'] = 'Course Information';
$string['team_members'] = 'Team Members';
$string['general_resources'] = 'General Course Resources';

// Workflow phases.
$string['fase1_caracterizacion'] = 'Phase 1: Fill out the Characterization';
$string['fase2_revision_curricular'] = 'Phase 2: Curricular Review';
$string['fase3_par_corrector'] = 'Phase 3: Peer / Style Corrector';
$string['fase4_produccion'] = 'Phase 4: Production';
$string['fase5_alistamiento'] = 'Phase 5: Moodle Readiness';
$string['fase6_aprobacion_final'] = 'Phase 6: Final Course Approval';

// Roles.
$string['role_experto_disciplinar'] = 'Subject Matter Expert';
$string['role_asesor_metodologico'] = 'Methodological Advisor';
$string['role_revisor_curricular'] = 'Curricular Reviewer';
$string['role_par_disciplinar'] = 'Academic Peer';
$string['role_corrector_estilo'] = 'Style Corrector';
$string['role_coordinacion_produccion'] = 'Production Coordinator';
$string['role_produccion'] = 'Production';
$string['role_alistamiento'] = 'Readiness';

// Resource categories.
$string['recursos_educativos_digitales'] = 'Digital Educational Resources';
$string['recursos_interactivos_digitales'] = 'Digital Interactive Resources';
$string['recursos_evaluativos'] = 'Evaluative Resources';
$string['recursos_colaborativos'] = 'Collaborative Resources';
$string['recursos_externos'] = 'External Resources';

// Caracterización - Excel rows 1-9: Team Members.
$string['programa_academico'] = 'Academic Program';
$string['programa_academico_help'] = 'Enter the academic program name (Excel H1-I1)';
$string['nombre_curso'] = 'Course Name';
$string['nombre_curso_help'] = 'Enter the course name (Excel H2-I2)';
$string['asesor_metodologico'] = 'Methodological Advisor (Instructional Designer)';
$string['experto_disciplinar'] = 'Subject Matter Expert';
$string['par_academico'] = 'Academic Peer';
$string['corrector_estilo'] = 'Style Corrector';
$string['coordinacion_produccion'] = 'Production Coordinator';
$string['produccion_nombre'] = 'Production (Design Professional)';
$string['alistamiento_nombre'] = 'Readiness';

// Caracterización - Excel rows 11-15: General Resources.
$string['recursos_generales'] = 'General Course Resources';
$string['cvp'] = 'Portable Virtual Course (CVP)';
$string['cvp_help'] = 'Select if this course requires a Portable Virtual Course (Excel J11)';
$string['sala_clases'] = 'Virtual Classroom';
$string['video_bienvenida'] = 'Welcome Video';
$string['foro_curso'] = 'Course Forum';
$string['mapa_curso'] = 'Course Map';

// Resources.
$string['unidad'] = 'Unit';
$string['tema'] = 'Topic';
$string['tipo_recurso'] = 'Resource Type';
$string['recurso'] = 'Resource';
$string['nombre_unidad'] = 'Unit Name';
$string['nombre_tema'] = 'Topic Name';
$string['agregar_recurso'] = 'Add Resource';
$string['editar_recurso'] = 'Edit Resource';
$string['eliminar_recurso'] = 'Delete Resource';
$string['ver_recurso'] = 'View Resource';

// Workflow.
$string['currentphase'] = 'Current Phase';
$string['phase'] = 'Phase';
$string['estado'] = 'Status';
$string['estado_pendiente'] = 'Pending';
$string['estado_en_proceso'] = 'In Progress';
$string['estado_aprobado'] = 'Approved';
$string['estado_rechazado'] = 'Rejected';
$string['estado_borrador'] = 'Draft';

// Approvals.
$string['aprobar'] = 'Approve';
$string['rechazar'] = 'Reject';
$string['comentario'] = 'Comment';
$string['comentarios'] = 'Comments';
$string['agregar_comentario'] = 'Add Comment';
$string['aprobaciones'] = 'Approvals';
$string['aprobado_por'] = 'Approved by';
$string['rechazado_por'] = 'Rejected by';
$string['fecha'] = 'Date';

// Notifications.
$string['notification_phase_approved'] = 'Phase {$a->phase} has been approved';
$string['notification_phase_rejected'] = 'Phase {$a->phase} has been rejected';
$string['notification_assigned'] = 'You have been assigned a new task in {$a->name}';
$string['notification_next_phase'] = 'You can start phase {$a->phase}';

// Forms.
$string['guardar'] = 'Save';
$string['cancelar'] = 'Cancel';
$string['continuar'] = 'Continue';
$string['volver'] = 'Back';
$string['siguiente'] = 'Next';
$string['anterior'] = 'Previous';

// Errors.
$string['error_no_permission'] = 'You do not have permission to perform this action';
$string['error_phase_not_ready'] = 'The previous phase has not been approved';
$string['error_invalid_role'] = 'Invalid role for this phase';
$string['error_already_approved'] = 'This phase has already been approved';
$string['error_no_recursos'] = 'No resources available';

// Messages.
$string['success_saved'] = 'Saved successfully';
$string['success_approved'] = 'Approved successfully';
$string['success_rejected'] = 'Rejected successfully';
$string['confirm_approve'] = 'Are you sure you want to approve this phase?';
$string['confirm_reject'] = 'Are you sure you want to reject this phase?';
$string['confirm_delete'] = 'Are you sure you want to delete this resource?';

// Reports.
$string['reportes'] = 'Reports';
$string['resumen'] = 'Summary';
$string['progreso'] = 'Progress';
$string['total_recursos'] = 'Total Resources';
$string['recursos_por_categoria'] = 'Resources by Category';
$string['fases_completadas'] = 'Completed Phases';
$string['tiempo_estimado'] = 'Estimated Time';

// Help.
$string['help_caracterizacion'] = 'Select the types of educational resources to design and use for this course';
$string['help_workflow'] = 'The process follows 6 sequential phases that must be approved';
$string['help_roles'] = 'Each role has specific permissions in each phase of the process';
$string['help_notificaciones'] = 'Automatic notifications are sent at the end of each phase';

// Privacy.
$string['privacy:metadata:udes_recursos'] = 'Information about created educational resources';
$string['privacy:metadata:udes_recursos:userid'] = 'User ID who created the resource';
$string['privacy:metadata:udes_recursos:contenido_unidad'] = 'Unit resource content';
$string['privacy:metadata:udes_recursos:contenido_tema'] = 'Topic resource content';
$string['privacy:metadata:udes_recursos:timecreated'] = 'Resource creation date';
$string['privacy:metadata:udes_recursos:timemodified'] = 'Resource last modification date';

$string['privacy:metadata:udes_comentarios'] = 'Comments made in the process';
$string['privacy:metadata:udes_comentarios:userid'] = 'User ID who commented';
$string['privacy:metadata:udes_comentarios:comentario'] = 'Comment text';
$string['privacy:metadata:udes_comentarios:timecreated'] = 'Comment date';

$string['privacy:metadata:udes_aprobaciones'] = 'Approvals and rejections in the process';
$string['privacy:metadata:udes_aprobaciones:userid'] = 'User ID who approved/rejected';
$string['privacy:metadata:udes_aprobaciones:comentario'] = 'Approval/rejection comment';
$string['privacy:metadata:udes_aprobaciones:aprobado'] = 'Whether it was approved (1) or rejected (0)';
$string['privacy:metadata:udes_aprobaciones:timecreated'] = 'Approval/rejection date';

$string['privacy:metadata:udes_workflow'] = 'Workflow tracking information';
$string['privacy:metadata:udes_workflow:userid'] = 'User ID responsible for the phase';
$string['privacy:metadata:udes_workflow:timecreated'] = 'Phase start date';
$string['privacy:metadata:udes_workflow:timemodified'] = 'Phase last modification date';
