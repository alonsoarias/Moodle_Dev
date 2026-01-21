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

$string['pluginname'] = 'Sistema de Producción de Recursos Educativos UDES';
$string['modulename'] = 'Producción de Recursos UDES';
$string['modulenameplural'] = 'Producción de Recursos UDES';
$string['pluginadministration'] = 'Administración de Producción UDES';

// Capabilities.
$string['udes:addinstance'] = 'Agregar nueva actividad de Producción UDES';
$string['udes:view'] = 'Ver actividad de Producción UDES';
$string['udes:expertodisciplinar'] = 'Actuar como Experto Disciplinar';
$string['udes:asesormetodologico'] = 'Actuar como Asesor Metodológico';
$string['udes:revisorcurricular'] = 'Actuar como Revisor Curricular';
$string['udes:pardisciplinar'] = 'Actuar como Par Disciplinar';
$string['udes:correctorestilo'] = 'Actuar como Corrector de Estilo';
$string['udes:coordinacionproduccion'] = 'Actuar como Coordinación de Producción';
$string['udes:produccion'] = 'Actuar como Producción';
$string['udes:alistamiento'] = 'Actuar como Alistamiento';
$string['udes:approve'] = 'Aprobar fases';
$string['udes:reject'] = 'Rechazar fases';
$string['udes:viewreports'] = 'Ver reportes';
$string['udes:manageall'] = 'Gestionar todas las fases';

// General.
$string['name'] = 'Nombre';
$string['description'] = 'Descripción';
$string['recursos'] = 'Recursos';
$string['caracterizacion'] = 'Caracterización';

// Workflow phases.
$string['fase1_caracterizacion'] = 'Fase 1: Diligencia la Caracterización';
$string['fase2_revision_curricular'] = 'Fase 2: Revisión Curricular';
$string['fase3_par_corrector'] = 'Fase 3: Par / Corrector de Estilo';
$string['fase4_produccion'] = 'Fase 4: Producción';
$string['fase5_alistamiento'] = 'Fase 5: Alistamiento en Moodle';
$string['fase6_aprobacion_final'] = 'Fase 6: Aprobación Final del Curso';

// Roles.
$string['role_experto_disciplinar'] = 'Experto Disciplinar';
$string['role_asesor_metodologico'] = 'Asesor Metodológico';
$string['role_revisor_curricular'] = 'Revisor Curricular';
$string['role_par_disciplinar'] = 'Par Disciplinar';
$string['role_corrector_estilo'] = 'Corrector de Estilo';
$string['role_coordinacion_produccion'] = 'Coordinación de Producción';
$string['role_produccion'] = 'Producción';
$string['role_alistamiento'] = 'Alistamiento';

// Resource categories.
$string['recursos_educativos_digitales'] = 'Recursos Educativos Digitales';
$string['recursos_interactivos_digitales'] = 'Recursos Interactivos Digitales';
$string['recursos_evaluativos'] = 'Recursos Evaluativos';
$string['recursos_colaborativos'] = 'Recursos Colaborativos';
$string['recursos_externos'] = 'Recursos Externos';

// Caracterización - Excel rows 1-9: Team Members.
$string['programa_academico'] = 'Programa Académico';
$string['programa_academico_help'] = 'Escriba el nombre del programa académico (Excel H1-I1)';
$string['nombre_curso'] = 'Nombre del Curso';
$string['nombre_curso_help'] = 'Escriba el nombre del curso (Excel H2-I2)';
$string['asesor_metodologico'] = 'Asesor Metodológico (Diseñador Instruccional)';
$string['experto_disciplinar'] = 'Experto Disciplinar';
$string['par_academico'] = 'Par Académico';
$string['corrector_estilo'] = 'Corrector de Estilo';
$string['coordinacion_produccion'] = 'Coordinación de Producción';
$string['produccion_nombre'] = 'Producción (Profesional de Diseño)';
$string['alistamiento_nombre'] = 'Alistamiento';

// Caracterización - Excel rows 11-15: General Resources.
$string['recursos_generales'] = 'Recursos Generales del Curso';
$string['cvp'] = 'Curso Virtual Portable (CVP)';
$string['sala_clases'] = 'Sala para Clases Virtuales';
$string['video_bienvenida'] = 'Video de Bienvenida';
$string['foro_curso'] = 'Foro del Curso';
$string['mapa_curso'] = 'Mapa del Curso';

// Resources.
$string['unidad'] = 'Unidad';
$string['tema'] = 'Tema';
$string['tipo_recurso'] = 'Tipo de Recurso';
$string['recurso'] = 'Recurso';
$string['nombre_unidad'] = 'Nombre de la Unidad';
$string['nombre_tema'] = 'Nombre del Tema';
$string['agregar_recurso'] = 'Agregar Recurso';
$string['editar_recurso'] = 'Editar Recurso';
$string['eliminar_recurso'] = 'Eliminar Recurso';
$string['ver_recurso'] = 'Ver Recurso';

// Workflow.
$string['currentphase'] = 'Fase Actual';
$string['phase'] = 'Fase';
$string['estado'] = 'Estado';
$string['estado_pendiente'] = 'Pendiente';
$string['estado_en_proceso'] = 'En Proceso';
$string['estado_aprobado'] = 'Aprobado';
$string['estado_rechazado'] = 'Rechazado';
$string['estado_borrador'] = 'Borrador';

// Approvals.
$string['aprobar'] = 'Aprobar';
$string['rechazar'] = 'Rechazar';
$string['comentario'] = 'Comentario';
$string['comentarios'] = 'Comentarios';
$string['agregar_comentario'] = 'Agregar Comentario';
$string['aprobaciones'] = 'Aprobaciones';
$string['aprobado_por'] = 'Aprobado por';
$string['rechazado_por'] = 'Rechazado por';
$string['fecha'] = 'Fecha';

// Notifications.
$string['notification_phase_approved'] = 'La fase {$a->phase} ha sido aprobada';
$string['notification_phase_rejected'] = 'La fase {$a->phase} ha sido rechazada';
$string['notification_assigned'] = 'Se le ha asignado una nueva tarea en {$a->name}';
$string['notification_next_phase'] = 'Puede comenzar la fase {$a->phase}';

// Forms.
$string['guardar'] = 'Guardar';
$string['cancelar'] = 'Cancelar';
$string['continuar'] = 'Continuar';
$string['volver'] = 'Volver';
$string['siguiente'] = 'Siguiente';
$string['anterior'] = 'Anterior';

// Errors.
$string['error_no_permission'] = 'No tiene permiso para realizar esta acción';
$string['error_phase_not_ready'] = 'La fase anterior no ha sido aprobada';
$string['error_invalid_role'] = 'Rol no válido para esta fase';
$string['error_already_approved'] = 'Esta fase ya ha sido aprobada';
$string['error_no_recursos'] = 'No hay recursos disponibles';

// Messages.
$string['success_saved'] = 'Guardado correctamente';
$string['success_approved'] = 'Aprobado correctamente';
$string['success_rejected'] = 'Rechazado correctamente';
$string['confirm_approve'] = '¿Está seguro que desea aprobar esta fase?';
$string['confirm_reject'] = '¿Está seguro que desea rechazar esta fase?';
$string['confirm_delete'] = '¿Está seguro que desea eliminar este recurso?';

// Reports.
$string['reportes'] = 'Reportes';
$string['resumen'] = 'Resumen';
$string['progreso'] = 'Progreso';
$string['total_recursos'] = 'Total de Recursos';
$string['recursos_por_categoria'] = 'Recursos por Categoría';
$string['fases_completadas'] = 'Fases Completadas';
$string['tiempo_estimado'] = 'Tiempo Estimado';

// Help.
$string['help_caracterizacion'] = 'Seleccione los tipos de recursos educativos a diseñar y usar para este curso';
$string['help_workflow'] = 'El proceso sigue 6 fases secuenciales que deben ser aprobadas';
$string['help_roles'] = 'Cada rol tiene permisos específicos en cada fase del proceso';
$string['help_notificaciones'] = 'Se envían notificaciones automáticas al finalizar cada fase';

// Privacy.
$string['privacy:metadata:udes_recursos'] = 'Información sobre los recursos educativos creados';
$string['privacy:metadata:udes_recursos:userid'] = 'ID del usuario que creó el recurso';
$string['privacy:metadata:udes_recursos:contenido_unidad'] = 'Contenido del recurso de unidad';
$string['privacy:metadata:udes_recursos:contenido_tema'] = 'Contenido del recurso de tema';
$string['privacy:metadata:udes_recursos:timecreated'] = 'Fecha de creación del recurso';
$string['privacy:metadata:udes_recursos:timemodified'] = 'Fecha de última modificación del recurso';

$string['privacy:metadata:udes_comentarios'] = 'Comentarios realizados en el proceso';
$string['privacy:metadata:udes_comentarios:userid'] = 'ID del usuario que comentó';
$string['privacy:metadata:udes_comentarios:comentario'] = 'Texto del comentario';
$string['privacy:metadata:udes_comentarios:timecreated'] = 'Fecha del comentario';

$string['privacy:metadata:udes_aprobaciones'] = 'Aprobaciones y rechazos en el proceso';
$string['privacy:metadata:udes_aprobaciones:userid'] = 'ID del usuario que aprobó/rechazó';
$string['privacy:metadata:udes_aprobaciones:comentario'] = 'Comentario de aprobación/rechazo';
$string['privacy:metadata:udes_aprobaciones:aprobado'] = 'Si fue aprobado (1) o rechazado (0)';
$string['privacy:metadata:udes_aprobaciones:timecreated'] = 'Fecha de aprobación/rechazo';

$string['privacy:metadata:udes_workflow'] = 'Información de seguimiento del workflow';
$string['privacy:metadata:udes_workflow:userid'] = 'ID del usuario responsable de la fase';
$string['privacy:metadata:udes_workflow:timecreated'] = 'Fecha de inicio de la fase';
$string['privacy:metadata:udes_workflow:timemodified'] = 'Fecha de última modificación de la fase';
