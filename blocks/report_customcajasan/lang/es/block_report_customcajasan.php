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
 * Spanish language strings for report_customcajasan.
 *
 * @package    block_report_customcajasan
 * @copyright  2025 Cajasan
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

// Nombre del plugin.
$string['pluginname'] = 'Informe de seguimiento y estado de cursos';
$string['report_customcajasan'] = 'Informe de seguimiento y estado de cursos';
$string['enrollment_report'] = 'Informe de seguimiento y estado de cursos';
$string['enrollment_report_course'] = 'Ver informe del curso';

// Capacidades.
$string['report_customcajasan:addinstance'] = 'Añadir un nuevo bloque de Informe de seguimiento y estado de cursos';
$string['report_customcajasan:myaddinstance'] = 'Añadir un nuevo bloque de Informe de seguimiento y estado de cursos a Mi Moodle';
$string['report_customcajasan:viewreport'] = 'Ver informe de seguimiento y estado de cursos';
$string['report_customcajasan:viewblock'] = 'Ver el bloque de informe de seguimiento y estado de cursos';
$string['report_customcajasan:manageblock'] = 'Gestionar el bloque de informe de seguimiento y estado de cursos a nivel del sitio';
$string['report_customcajasan:configurecourse'] = 'Configurar el bloque de informe de seguimiento y estado de cursos dentro de un curso';

// Alcance del bloque.
$string['block_no_access'] = 'No tienes permiso para utilizar este reporte.';
$string['block_link_label'] = 'Abrir informe consolidado';
$string['block_link_label_course'] = 'Abrir informe del curso';
$string['block_scope_system'] = 'Alcance: Resumen del sitio';
$string['block_scope_my'] = 'Alcance: Tablero personal';
$string['block_scope_current_course'] = 'Alcance: Curso actual – {$a}';
$string['block_scope_categories'] = 'Categorías de curso incluidas:';
$string['block_scope_course_only'] = 'El informe mostrará únicamente los datos de este curso.';
$string['block_scope_none'] = 'No se han seleccionado categorías. Configura el bloque para elegir una o más categorías.';
$string['block_scope_coursecount'] = 'Total de cursos actualmente en alcance: {$a}';
$string['block_scope_configure_notice'] = 'Edita este bloque para seleccionar las categorías que deben alimentar el informe.';
$string['block_warning_missing_viewblock'] = 'Tu rol no tiene la capacidad block/report_customcajasan:viewblock. El informe permanece disponible porque puedes editar este curso.';
$string['block_warning_missing_viewreport'] = 'Tu rol no tiene la capacidad block/report_customcajasan:viewreport. El informe permanece disponible porque puedes editar este curso.';

// Formulario de configuración.
$string['config_header'] = 'Configuración del alcance del informe';
$string['config_context_label'] = 'Ubicación del bloque';
$string['config_context_system'] = 'Sitio principal (página de inicio). Solo los gestores del sitio pueden configurar o ver el informe aquí.';
$string['config_context_my'] = 'Tablero personal. Solo los gestores del sitio pueden configurar o ver el informe aquí.';
$string['config_context_course'] = 'Página del curso: {$a}';
$string['config_context_system_note'] = 'El informe consolidado mostrará únicamente cursos pertenecientes a las categorías seleccionadas. Sin categorías no se visualizarán datos.';
$string['config_context_my_note'] = 'Utiliza esta configuración para construir un resumen personalizado a partir de las categorías de curso seleccionadas.';
$string['config_context_course_note'] = 'Por defecto el informe incluye el curso actual "{$a}". Las categorías que selecciones añadirán sus cursos sin duplicar información.';
$string['config_categoryids'] = 'Categorías incluidas en el informe';
$string['config_categoryids_help'] = 'Selecciona las categorías cuyos cursos deben incluirse al generar el informe. '
    . 'Cuando el bloque se ubica dentro de un curso, dicho curso siempre se incluye automáticamente.';
$string['config_categoryids_hint'] = 'Déjalo vacío para incluir solo el curso actual cuando el bloque esté dentro de un curso. '
    . 'En la página principal o en el tablero es necesario seleccionar al menos una categoría para mostrar datos.';
$string['config_categoryids_placeholder'] = 'Selecciona una o más categorías';
$string['config_categoryids_invalid'] = 'Una o más de las categorías seleccionadas ya no están disponibles. Revisa la selección.';

// Cabeceras de tabla.
$string['column_identificacion'] = 'IDENTIFICACIÓN';
$string['column_nombres'] = 'NOMBRES';
$string['column_apellidos'] = 'APELLIDOS';
$string['column_correo'] = 'CORREO';
$string['column_unidad'] = 'UNIDAD';
$string['column_curso'] = 'CURSO';
$string['column_fecha_matricula'] = 'FECHA DE MATRÍCULA';
$string['column_fecha_certificado'] = 'FECHA DE EMISIÓN CERTIFICADO';
$string['column_estado'] = 'ESTADO';
$string['column_categoria'] = 'CATEGORÍA';
$string['column_ultimo_acceso'] = 'ÚLTIMO ACCESO AL CURSO';

// Valores de estado.
$string['state_aprobado'] = 'APROBADO';
$string['state_encurso'] = 'EN CURSO';
$string['state_noiniciado'] = 'NO INICIADO';
$string['state_soloconsulta'] = 'SOLO CONSULTA';
$string['status_explanation'] = 'Estados';
$string['status_note'] = 'Los cursos que no emiten certificados tienen el estado "SOLO CONSULTA".';

// Valores especiales.
$string['never'] = 'Nunca';

// Opciones de formulario.
$string['option_all'] = 'Todos';
$string['option_category'] = 'Categoría';
$string['option_course'] = 'Curso';
$string['option_firstname'] = 'Nombre';
$string['option_lastname'] = 'Apellido';
$string['option_estado'] = 'Estado';
$string['option_filter_by_letter'] = 'Filtrar por letra';
$string['option_download_format'] = 'Formato de descarga';
$string['option_download_excel'] = 'Excel';
$string['option_download_ods'] = 'ODS';
$string['option_download_csv'] = 'CSV';

// Opciones de paginación.
$string['records_per_page'] = 'Registros por página';
$string['all_records'] = 'Todos los registros';

// Encabezados y etiquetas del reporte.
$string['report_title'] = 'Informe de seguimiento y estado de cursos';
$string['report_title_course'] = 'Informe del curso: {$a}';
$string['report_scope_heading'] = 'Alcance efectivo del informe';
$string['report_scope_current_course'] = 'Curso actual: {$a}';
$string['report_scope_categories'] = 'Categorías configuradas:';
$string['report_scope_additional_courses'] = 'Cursos adicionales derivados de las categorías:';
$string['report_scope_none'] = 'No hay categorías adicionales configuradas. Solo se incluyen los cursos seleccionados explícitamente.';
$string['report_scope_not_ready'] = 'Configura este bloque y selecciona al menos una categoría antes de visualizar datos.';
$string['report_scope_effective_courses'] = 'Total de cursos en alcance: {$a}';
$string['report_scope_restricted'] = 'Los filtros disponibles están limitados por la configuración del bloque.';
$string['report_scope_notice_frontpage'] = 'Todavía no se han configurado categorías. Selecciona una o más categorías en la configuración del bloque para mostrar datos.';
$string['btn_download'] = 'Descargar';
$string['total_records'] = 'Total de registros';
$string['idnumber'] = 'Número de identificación';
$string['start_date'] = 'Fecha de inicio';
$string['end_date'] = 'Fecha de fin';
$string['no_data'] = 'No se encontraron datos de matriculación';
$string['access_denied'] = 'No tienes permiso para ver este informe';
$string['filters_required'] = 'Debe seleccionar al menos un filtro para ver los datos';
$string['select_filter_first'] = 'Por favor seleccione al menos un filtro para ver los datos del informe';
$string['search'] = 'Buscar';
$string['viewing_course_report'] = 'Viendo reporte de: {$a}';
$string['viewing_course_report_full'] = 'Reporte del curso';
$string['report_no_scope_configured'] = 'Este bloque de informe aún no ha sido configurado. Edita el bloque y selecciona una o más categorías.';
$string['download_scope_missing'] = 'No es posible generar la descarga porque no hay cursos válidos en el alcance actual.';
$string['invalidblockinstance'] = 'No se pudo encontrar la instancia solicitada del bloque.';

// Mensajes de error AJAX.
$string['ajax_error'] = 'Error al cargar datos. Por favor, inténtelo de nuevo.';
$string['ajax_error_detail'] = 'Error al cargar datos';

// Cadenas para DataTables.
$string['showing'] = 'Mostrando';
$string['to'] = 'a';
$string['of'] = 'de';
$string['entries'] = 'registros';
$string['filtered_from'] = 'filtrado de';
$string['total'] = 'total';
$string['first'] = 'Primero';
$string['last'] = 'Último';
$string['next'] = 'Siguiente';
$string['previous'] = 'Anterior';
