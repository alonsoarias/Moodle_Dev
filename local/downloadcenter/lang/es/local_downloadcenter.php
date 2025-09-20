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
 * Spanish language strings for local_downloadcenter
 *
 * @package    local_downloadcenter
 * @copyright  2025 Original: Academic Moodle Cooperation, Extended: Alonso Arias <soporte@ingeweb.co>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['pluginname'] = 'Centro de descargas';

// Capacidades.
$string['downloadcenter:view'] = 'Ver centro de descargas';
$string['downloadcenter:downloadmultiple'] = 'Descargar múltiples cursos';
$string['downloadcenter:excludestudentcontent'] = 'Excluir contenido de estudiantes de las descargas';

// Navegación.
$string['navigationlink'] = 'Centro de descargas';
$string['admindownloadcenter'] = 'Centro de descargas administrativo';

// Formularios y botones.
$string['createzip'] = 'Crear archivo ZIP';
$string['download'] = 'Descargar';
$string['downloadoptions'] = 'Opciones de descarga';
$string['selectcategory'] = 'Seleccionar categoría';
$string['selectcourses'] = 'Seleccionar cursos';
$string['adminmultiselectinstructions'] = 'Seleccione una o varias categorías para ver sus cursos y elija los cursos y recursos que desea incluir en la descarga administrativa.';
$string['loadcourses'] = 'Cargar cursos';
$string['saveandcontinue'] = 'Guardar y continuar';
$string['clearselection'] = 'Limpiar selección';
$string['downloadselection'] = 'Descargar selección';
$string['addtoselection'] = 'Añadir a la selección';
$string['optionssaved'] = 'Opciones guardadas exitosamente';

// Opciones.
$string['downloadoptions:addnumbering'] = 'Añadir numeración a archivos y carpetas';
$string['downloadoptions:addnumbering_help'] = 'Si está habilitado, las secciones del curso, archivos y carpetas serán numerados en el orden en que aparecen en el curso.';
$string['downloadoptions:filesrealnames'] = 'Descargar archivos con nombre original';
$string['downloadoptions:filesrealnames_help'] = 'Si está habilitado, los recursos de archivo se descargarán con su nombre de archivo original en lugar del nombre visible en el curso.';
$string['excludestudentcontent'] = 'Excluir contenido generado por estudiantes';
$string['excludestudentcontent_help'] = 'Si está habilitado, las entregas y contenido de estudiantes (tareas, mensajes en foros, etc.) serán excluidos de la descarga. Solo se incluirán los materiales del curso proporcionados por los profesores.';
$string['includefiles'] = 'Incluir todos los tipos de archivo';
$string['includefiles_help'] = 'Si está habilitado, todos los tipos de archivo serán incluidos en la descarga.';

// Configuración.
$string['generalsettings'] = 'Configuración general';
$string['generalsettings_desc'] = 'Configure las opciones generales del centro de descargas.';
$string['performancesettings'] = 'Configuración de rendimiento';
$string['performancesettings_desc'] = 'Configure los ajustes relacionados con el rendimiento para la creación de ZIP.';
$string['enableadmindownload'] = 'Habilitar descarga multi-curso para administradores';
$string['enableadmindownload_desc'] = 'Permitir a los administradores descargar múltiples cursos a la vez.';
$string['maxcoursesperdownload'] = 'Máximo de cursos por descarga';
$string['maxcoursesperdownload_desc'] = 'Número máximo de cursos que pueden descargarse en un solo archivo ZIP.';
$string['excludestudentdefault'] = 'Excluir contenido de estudiantes por defecto';
$string['excludestudentdefault_desc'] = 'Cuando está habilitado, el contenido generado por estudiantes será excluido por defecto en nuevas descargas.';
$string['compressionlevel'] = 'Nivel de compresión ZIP';
$string['compressionlevel_desc'] = 'Nivel de compresión para archivos ZIP. Mayor compresión toma más tiempo pero produce archivos más pequeños.';
$string['compressionstore'] = 'Solo almacenar (sin compresión)';
$string['compressionfast'] = 'Compresión rápida';
$string['compressionbest'] = 'Mejor compresión';
$string['memorylimit'] = 'Límite de memoria';
$string['memorylimit_desc'] = 'Límite de memoria PHP para creación de ZIP (ej. 512M).';
$string['timelimit'] = 'Límite de tiempo';
$string['timelimit_desc'] = 'Tiempo máximo de ejecución en segundos para la creación de ZIP.';

// Mensajes.
$string['infomessage_students'] = 'Aquí puede descargar uno o todos los contenidos disponibles de este curso en un archivo ZIP.';
$string['infomessage_teachers'] = 'Aquí puede descargar uno o todos los contenidos disponibles de este curso en un archivo ZIP.<br>(Los estudiantes solo podrán descargar actividades y recursos visibles/no ocultos.)';
$string['nocoursesselected'] = 'No se han seleccionado cursos para descargar.';
$string['nocourseaccess'] = 'No tiene acceso para descargar estos cursos.';
$string['nocoursesfound'] = 'No se encontraron cursos en esta categoría.';
$string['nocontentavailable'] = 'Este curso aún no tiene recursos descargables.';
$string['adminfullcourselabel'] = 'Seleccionar todos los recursos disponibles del curso';
$string['adminfullcoursehint'] = 'Intentará incluir todos los recursos descargables del curso aunque no aparezcan listados abajo.';
$string['toomanycoursesselected'] = 'Demasiados cursos seleccionados. Máximo permitido: {$a}';
$string['zipfailed'] = 'Error al crear el archivo ZIP.';
$string['noselectederror'] = 'Por favor seleccione al menos un recurso para descargar.';
$string['zipcreating'] = 'Se está creando el archivo ZIP...';
$string['zipready'] = 'El archivo ZIP se ha creado exitosamente.';
$string['selectioncleared'] = 'Selección eliminada';
$string['loading'] = 'Cargando…';

// Búsqueda.
$string['search:hint'] = 'Escriba para filtrar actividades y recursos...';
$string['search:results'] = 'Resultados de búsqueda';

// Eventos.
$string['eventviewed'] = 'Centro de descargas visualizado';
$string['eventdownloadedzip'] = 'ZIP descargado';

// Otros.
$string['untitled'] = 'Sin título';
$string['privacy:metadata'] = 'El plugin Centro de descargas no almacena ningún dato personal.';

$string['currentselection'] = 'Selección actual';
$string['saveoptions'] = 'Guardar opciones';
$string['courses'] = 'cursos';
$string['hidden'] = 'Oculto';