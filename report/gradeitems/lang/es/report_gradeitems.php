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
 * Cadenas de idioma para el plugin de Reporte de Actividades Calificables.
 *
 * @package    report_gradeitems
 * @copyright  2026 Alonso Arias <soporte@orioncloud.com.co>
 * @author     Alonso Arias <soporte@orioncloud.com.co>
 * @link       https://orioncloud.com.co
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

$string['pluginname'] = 'Reporte de Actividades Calificables';
$string['gradeitems:view'] = 'Ver reporte de actividades calificables';

// Títulos y encabezados.
$string['pagetitle'] = 'Reporte de Actividades Calificables';
$string['pageheading'] = 'Reporte de Actividades Calificables - Resumen de Cursos';
$string['reportdescription'] = 'Este reporte muestra los cursos con la cantidad de actividades totales y calificables. Haga clic en el nombre del curso para ver el detalle de sus actividades calificables.';
$string['coursedetail'] = 'Detalle del Curso';
$string['courseinfo'] = 'Información del Curso';
$string['gradeableactivities'] = 'Actividades Calificables';

// Etiquetas de filtros.
$string['filters'] = 'Filtros';
$string['filter_category'] = 'Categoría';
$string['filter_course'] = 'Curso';
$string['filter_visibility'] = 'Visibilidad del curso';
$string['allcategories'] = 'Todas las categorías';
$string['allcourses'] = 'Todos los cursos';
$string['allvisibility'] = 'Todos';
$string['visible'] = 'Visible';
$string['hidden'] = 'Oculto';
$string['applyfilters'] = 'Aplicar filtros';
$string['clearfilters'] = 'Limpiar filtros';

// Encabezados de tabla - Lista de cursos.
$string['col_category'] = 'Categoría';
$string['col_categorypath'] = 'Ruta de categoría';
$string['col_courseshortname'] = 'Nombre corto';
$string['col_coursefullname'] = 'Nombre del curso';
$string['col_coursevisible'] = 'Visible';
$string['col_coursestartdate'] = 'Fecha de inicio';
$string['col_courseenddate'] = 'Fecha de fin';
$string['col_enrolledstudents'] = 'Estudiantes matriculados';
$string['col_teachers'] = 'Profesores';
$string['col_totalactivities'] = 'Actividades totales';
$string['col_gradeableactivities'] = 'Actividades calificables';
$string['col_actions'] = 'Acciones';

// Encabezados de tabla - Detalle de actividades.
$string['col_activityname'] = 'Nombre de actividad';
$string['col_moduletype'] = 'Tipo de módulo';
$string['col_activityvisible'] = 'Visible';
$string['col_section'] = 'Sección';
$string['col_gradetype'] = 'Tipo de calificación';
$string['col_grademax'] = 'Nota máxima';
$string['col_gradepass'] = 'Nota de aprobación';
$string['col_gradeweight'] = 'Peso (%)';
$string['col_gradecount'] = 'Cantidad de calificaciones';
$string['col_gradeaverage'] = 'Promedio de calificación';
$string['col_cmid'] = 'ID del CM';

// Tipos de calificación.
$string['gradetype_value'] = 'Valor';
$string['gradetype_scale'] = 'Escala';
$string['gradetype_text'] = 'Texto';
$string['gradetype_none'] = 'Ninguno';

// Visibilidad.
$string['yes'] = 'Sí';
$string['no'] = 'No';

// Exportación.
$string['downloadexcel'] = 'Descargar Excel (.xlsx)';
$string['downloadcsv'] = 'Descargar CSV';
$string['exportoptions'] = 'Opciones de exportación';

// Resultados y navegación.
$string['totalrecords'] = 'Total de cursos: {$a}';
$string['totalactivities_count'] = 'Total de actividades calificables: {$a}';
$string['norecordsfound'] = 'No se encontraron cursos con los filtros actuales.';
$string['noactivitiesfound'] = 'No se encontraron actividades calificables en este curso.';
$string['showing'] = 'Mostrando {$a->from} a {$a->to} de {$a->total} cursos';
$string['viewactivities'] = 'Ver actividades';
$string['backtocourselist'] = 'Volver a la lista de cursos';

// Errores.
$string['nopermission'] = 'No tiene permiso para ver este reporte.';

// Créditos.
$string['developedby'] = 'Desarrollado por';
