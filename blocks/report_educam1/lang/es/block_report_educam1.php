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
 * Spanish language strings for report_educam1.
 *
 * @package    block_report_educam1
 * @copyright  2025 IngeWeb - Soluciones para triunfar en Internet
 * @author     Alonso Arias <soporte@ingeweb.co>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

// Nombre del plugin.
$string['pluginname'] = 'Reporte personalizado de notificaciones';
$string['report_educam1'] = 'Reporte personalizado de notificaciones';

// Capacidades.
$string['report_educam1:addinstance'] = 'Añadir un nuevo bloque de Reporte personalizado de notificaciones';
$string['report_educam1:myaddinstance'] = 'Añadir un nuevo bloque de Reporte personalizado de notificaciones a Mi Moodle';
$string['report_educam1:viewreport'] = 'Ver reporte personalizado';

// Alcance del bloque.
$string['block_no_access'] = 'No tienes permiso para utilizar este reporte.';
$string['block_only_course'] = 'Este bloque solo funciona en contextos de curso.';
$string['block_link_label'] = 'Ver reporte de actividades';
$string['block_scope_current_course'] = 'Curso: {$a}';
$string['block_activity_type'] = 'Tipo de actividad: {$a}';
$string['block_no_activity_configured'] = 'No se ha seleccionado ningún tipo de actividad. Configura el bloque para seleccionar uno.';
$string['block_configure_notice'] = 'Edita este bloque para seleccionar el tipo de actividad a reportar.';

// Formulario de configuración.
$string['config_header'] = 'Configuración del reporte';
$string['config_context_label'] = 'Ubicación del bloque';
$string['config_context_course'] = 'Curso: {$a}';
$string['config_only_course'] = 'Este bloque solo puede ser configurado dentro de un curso.';
$string['config_activitytype'] = 'Tipo de actividad a reportar';
$string['config_activitytype_help'] = 'Selecciona el tipo de actividad (Tarea, Cuestionario, SCORM, Foro, etc.) que deseas reportar. El reporte mostrará el estado de completitud de todos los estudiantes para las actividades de este tipo en el curso.';
$string['config_activitytype_hint'] = 'Puedes crear múltiples instancias de este bloque para reportar diferentes tipos de actividades en el mismo curso.';
$string['config_select_activity'] = 'Selecciona un tipo de actividad...';
$string['config_no_activities'] = 'No hay actividades disponibles en este curso.';
$string['config_activitytype_required'] = 'Debes seleccionar un tipo de actividad.';

// Títulos del reporte.
$string['report_title'] = 'Reporte de completitud de actividades';
$string['report_title_course'] = 'Reporte: {$a}';
$string['report_viewing_course'] = 'Curso: {$a}';
$string['report_activity_type'] = 'Tipo de actividad: {$a}';

// Vistas del reporte.
$string['view_individual'] = 'Vista individual';
$string['view_matrix'] = 'Vista matricial';
$string['switch_to_individual'] = 'Cambiar a vista individual';
$string['switch_to_matrix'] = 'Cambiar a vista matricial';

// Columnas - Vista Individual.
$string['column_idnumber'] = 'Cédula';
$string['column_firstname'] = 'Nombres';
$string['column_lastname'] = 'Apellidos';
$string['column_email'] = 'Correo electrónico';
$string['column_activity'] = 'Actividad';
$string['column_completion_date'] = 'Fecha de finalización';
$string['column_status'] = 'Estado';

// Columnas - Vista Matricial.
$string['column_student'] = 'Estudiante';

// Estados de completitud.
$string['status_completed'] = 'Completado';
$string['status_not_completed'] = 'No completado';
$string['completed_symbol'] = '✓';
$string['not_completed_symbol'] = '✗';

// Filtros.
$string['filter_header'] = 'Filtros';
$string['filter_activity'] = 'Actividad específica';
$string['filter_all_activities'] = 'Todas las actividades';
$string['filter_status'] = 'Estado';
$string['filter_search'] = 'Buscar estudiante';
$string['filter_apply'] = 'Aplicar filtros';
$string['filter_clear'] = 'Limpiar filtros';
$string['filter_idnumber'] = 'Cédula / ID Number';
$string['filter_idnumber_placeholder'] = 'Buscar por cédula...';
$string['filter_firstname'] = 'Nombre';
$string['filter_firstname_placeholder'] = 'Buscar por nombre...';
$string['filter_lastname'] = 'Apellido';
$string['filter_lastname_placeholder'] = 'Buscar por apellido...';
$string['filter_all'] = 'Todos';
$string['filter_by_letter'] = 'Filtrar por letra';
$string['filter_startdate'] = 'Fecha inicial de finalización';
$string['filter_enddate'] = 'Fecha final de finalización';

// Exportación.
$string['export_header'] = 'Exportar datos';
$string['export_format'] = 'Formato';
$string['export_excel'] = 'Excel (.xlsx)';
$string['export_ods'] = 'OpenDocument (.ods)';
$string['export_csv'] = 'CSV';
$string['export_button'] = 'Exportar';
$string['export_filename_individual'] = 'reporte_individual_actividades';
$string['export_filename_matrix'] = 'reporte_matricial_actividades';

// Mensajes.
$string['no_students'] = 'No hay estudiantes matriculados en este curso.';
$string['no_activities'] = 'No hay actividades del tipo seleccionado en este curso.';
$string['no_data'] = 'No hay datos para mostrar.';
$string['loading'] = 'Cargando datos...';
$string['error_loading'] = 'Error al cargar los datos. Por favor, inténtalo de nuevo.';

// Estadísticas.
$string['stats_total_students'] = 'Total de estudiantes';
$string['stats_total_activities'] = 'Total de actividades';
$string['stats_completion_rate'] = 'Tasa de completitud';
$string['stats_completed'] = 'Completados';
$string['stats_not_completed'] = 'No completados';
$string['stats_total_completions'] = 'Total de completaciones';

// Ayuda.
$string['help_view_individual'] = 'La vista individual muestra una lista detallada de cada estudiante con su estado de completitud para las actividades seleccionadas.';
$string['help_view_matrix'] = 'La vista matricial muestra una tabla con todos los estudiantes y todas las actividades del tipo seleccionado, permitiendo ver de un vistazo el progreso general del curso.';

// Paginación.
$string['showing_entries'] = 'Mostrando {$a->start} a {$a->end} de {$a->total} registros';
$string['per_page'] = 'Por página';

// Privacidad.
$string['privacy:metadata'] = 'El bloque de Reporte personalizado de notificaciones solo muestra datos existentes de otros lugares.';
