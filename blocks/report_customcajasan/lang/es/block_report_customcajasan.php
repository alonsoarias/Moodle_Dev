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
 * Spanish language strings for report_customcajasan
 *
 * @package    block_report_customcajasan
 * @copyright  2025 Cajasan
 * @author     Pedro Arias <soporte@ingeweb.co>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// Nombre del plugin
$string['pluginname'] = 'Informe de Seguimiento y Estado de Cursos';
$string['report_customcajasan'] = 'Informe de Seguimiento y Estado de Cursos';
$string['enrollment_report'] = 'Informe de Seguimiento y Estado de Cursos';

// Capacidades
$string['report_customcajasan:addinstance'] = 'Añadir un nuevo bloque de Informe de Seguimiento y Estado de Cursos';
$string['report_customcajasan:myaddinstance'] = 'Añadir un nuevo bloque de Informe de Seguimiento y Estado de Cursos a Mi Moodle';
$string['report_customcajasan:viewblock'] = 'Ver el bloque de Informe de Seguimiento y Estado de Cursos';
$string['report_customcajasan:viewreport'] = 'Ver Informe de Seguimiento y Estado de Cursos';
$string['report_customcajasan:viewsystemreport'] = 'Ver el informe en páginas del sistema';

// Cabeceras de tabla
$string['column_identificacion'] = 'IDENTIFICACIÓN';
$string['column_nombres'] = 'NOMBRES';
$string['column_apellidos'] = 'APELLIDOS';
$string['column_correo'] = 'CORREO';
$string['column_unidad'] = 'UNIDAD';
$string['column_curso'] = 'CURSO';
$string['column_fecha_matricula'] = 'FECHA DE MATRICULA';
$string['column_fecha_certificado'] = 'FECHA DE EMISIÓN CERTIFICADO';
$string['column_estado'] = 'ESTADO';
$string['column_categoria'] = 'CATEGORÍA';
$string['column_ultimo_acceso'] = 'ÚLTIMO ACCESO AL CURSO';

// Valores de estado - actualizados
$string['state_aprobado'] = 'APROBADO';
$string['state_encurso'] = 'EN CURSO';
$string['state_noiniciado'] = 'NO INICIADO';
$string['state_soloconsulta'] = 'SOLO CONSULTA';
$string['status_explanation'] = 'Estados';

// Valores especiales
$string['never'] = 'Nunca';

// Opciones de formulario
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

// Opciones de paginación
$string['records_per_page'] = 'Registros por página';
$string['all_records'] = 'Todos los registros';

// Otros
$string['report_title'] = 'Informe de Seguimiento y Estado de Cursos';
$string['btn_download'] = 'Descargar';
$string['total_records'] = 'Total de Registros';
$string['idnumber'] = 'Número de Identificación';
$string['start_date'] = 'Fecha de Inicio';
$string['end_date'] = 'Fecha de Fin';
$string['no_data'] = 'No se encontraron datos de matriculación';
$string['access_denied'] = 'No tienes permiso para ver este informe';
$string['filters_required'] = 'Debe seleccionar al menos un filtro para ver los datos';
$string['select_filter_first'] = 'Por favor, seleccione al menos un filtro para ver los datos del informe';
$string['note_label'] = 'Nota';
$string['status_note'] = 'Los cursos que no emiten certificados tienen el estado "SOLO CONSULTA".';
$string['search'] = 'Buscar';
$string['block_instructionstext'] = 'Utilice los filtros del informe para acotar la búsqueda y descargar solo la información necesaria.';

// Configuración del bloque.
$string['config_displayoptions'] = 'Información a mostrar';
$string['config_displayoptions_help'] = 'Seleccione los elementos que deben ser visibles dentro de este bloque. Cada instancia puede presentar información distinta según las necesidades del curso o de la página.';
$string['config_option_reportlink'] = 'Mostrar enlace al informe detallado';
$string['config_option_instructions'] = 'Mostrar instrucciones de uso';
$string['config_option_statuslegend'] = 'Mostrar leyenda de estados';
$string['config_custommessage'] = 'Mensaje adicional';
$string['config_custommessage_help'] = 'Texto opcional que se mostrará debajo de la información seleccionada. Puede utilizarlo para destacar fechas límite, datos de contacto u otra orientación relevante.';
$string['config_restriction_heading'] = 'Filtros de cursos y categorías';
$string['config_restriction_heading_help'] = 'Seleccione los cursos o categorías que estarán disponibles al consultar el reporte desde este bloque. Estas restricciones aplican únicamente para esta instancia.';
$string['config_coursefilters'] = 'Limitar a cursos';
$string['config_coursefilters_help'] = 'Elija uno o varios cursos que estarán disponibles en el reporte. Los usuarios solo podrán consultar la información de los cursos seleccionados.';
$string['config_coursefilters_empty'] = 'No hay cursos disponibles con los permisos requeridos.';
$string['config_categoryfilters'] = 'Limitar a categorías';
$string['config_categoryfilters_help'] = 'Seleccione categorías completas para incluir en el reporte. Todos los cursos dentro de las categorías elegidas estarán disponibles.';
$string['config_categoryfilters_empty'] = 'No hay categorías disponibles con los permisos requeridos.';
$string['config_summary_courses'] = 'Cursos: {$a}';
$string['config_summary_categories'] = 'Categorías: {$a}';

// Mensajes de error AJAX
$string['ajax_error'] = 'Error al cargar datos. Por favor, inténtelo de nuevo.';
$string['ajax_error_detail'] = 'Error al cargar datos';

// Cadenas para DataTables
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