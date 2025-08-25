<?php
// This file is part of local_downloadcenter for Moodle - http://moodle.org/
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
 * Download center plugin language strings
 *
 * @package       local_downloadcenter
 * @author        Simeon Naydenov (moniNaydenov@gmail.com)
 * @copyright     2020 Academic Moodle Cooperation {@link http://www.academic-moodle-cooperation.org}
 * @license       http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

$string['pluginname'] = 'Download center IEDigital';
$string['downloadcenter:view'] = 'Ver Centro de descargas';
$string['navigationlink'] = 'Centro de descargas';
$string['pagetitle'] = 'Centro de descargas de ';
$string['settings_title'] = 'Configuración del Centro de descargas';

// Settings
$string['exclude_empty_topics'] = 'Excluir temas vacíos';
$string['exclude_empty_topics_help'] = 'Excluye los temas vacíos del archivo ZIP descargado.';
$string['maxzipsize'] = 'Tamaño máximo del ZIP (MB)';
$string['maxzipsize_desc'] = 'Tamaño máximo de los archivos ZIP en megabytes. Establézcalo en 0 para ilimitado.';
$string['includeassignments'] = 'Incluir descripciones de tareas';
$string['includeassignments_desc'] = 'Incluye las descripciones de las tareas y los archivos adjuntos de la introducción en las descargas.';

// Interface strings
$string['warningmessage'] = 'Aquí puede descargar en un archivo ZIP uno o todos los contenidos disponibles de este curso.';
$string['saveselection'] = 'Guardar selección';
$string['zipready'] = 'El archivo ZIP se creó correctamente.';
$string['download'] = 'Descargar';
$string['zipcreating'] = 'Creando el archivo ZIP...';
$string['eventDOWNLOADEDZIP'] = 'Se descargó el ZIP';
$string['eventVIEWED'] = 'Centro de descargas visualizado';
$string['untitled'] = 'Sin título';
$string['privacy:null_reason'] = 'Este complemento no almacena ni procesa información personal. Presenta una interfaz para descargar todos los archivos del curso que se manipulan dentro del mismo.';
$string['no_downloadable_content'] = 'No hay contenido descargable';

// Selection interface
$string['downloadall'] = 'Descargar todo';
$string['selectfiles'] = 'Seleccionar archivos';
$string['selectonecourse'] = 'Por favor, seleccione exactamente un curso al elegir archivos específicos.';
$string['selectcourses'] = 'Seleccionar cursos';
$string['downloadselection'] = 'Descargar cursos seleccionados';
$string['clearselection'] = 'Borrar selección';
$string['selected'] = 'seleccionados';
$string['addcoursestoselection'] = 'Añadir cursos seleccionados';
$string['currentselection'] = 'Selección actual';
$string['filesadded'] = 'Archivos añadidos a la selección';

// Error messages
$string['nocoursesselected'] = 'No se seleccionaron cursos para la descarga';
$string['noaccesstocourse'] = 'No tiene acceso al curso: {$a}';
$string['errorcreatinzip'] = 'Error al crear el archivo ZIP. Por favor, inténtelo de nuevo.';
$string['nocoursesfound'] = 'No se encontraron cursos';

// Help and descriptions
$string['downloadcenter_help'] = 'El Centro de descargas le permite descargar contenido de los cursos en lote como archivos ZIP. Seleccione una categoría para ver los cursos disponibles y, después, elija los cursos que desea descargar.';
$string['downloadcenter_desc'] = 'Seleccione cursos de la lista siguiente para agregarlos a su selección de descarga. Puede descargar contenido de varios cursos a la vez.';

// Navigation
$string['back'] = 'Atrás';
$string['administrationsite'] = 'Administración del sitio';
$string['courses'] = 'Cursos';
$string['searchcourses'] = 'Buscar cursos...';
$string['search'] = 'Buscar';

// Capabilities
$string['downloadcenter:downloadcoursecontent'] = 'Descargar contenido del curso';
$string['downloadcenter:downloadallcourses'] = 'Descargar contenido de todos los cursos';

// Notifications
$string['downloadsuccess'] = 'Descarga completada correctamente';
$string['downloadfailed'] = 'La descarga falló: {$a}';
$string['courseadded'] = 'Curso "{$a}" añadido a la selección';
$string['courseremoved'] = 'Curso "{$a}" eliminado de la selección';
$string['selectioncleared'] = 'Selección borrada';

// Bulk operations
$string['bulkdownload'] = 'Descarga masiva';
$string['selectall'] = 'Seleccionar todo';
$string['selectnone'] = 'Deseleccionar todo';
$string['selectedcount'] = '{$a} cursos seleccionados';

// File types
$string['resource'] = 'Archivo';
$string['folder'] = 'Carpeta';
$string['page'] = 'Página';
$string['book'] = 'Libro';
$string['assign'] = 'Tarea';
$string['glossary'] = 'Glosario';
$string['publication'] = 'Publicación';
$string['lightboxgallery'] = 'Galería';
$string['etherpadlite'] = 'Etherpad';

// Progress
$string['preparing'] = 'Preparando la descarga...';
$string['processing'] = 'Procesando {$a}...';
$string['compressing'] = 'Comprimiendo archivos...';
$string['done'] = '¡Listo!';
