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
 * Strings for component 'theme_inteb'
 *
 * @package   theme_inteb
 * @copyright (c) 2025 IngeWeb <soporte@ingeweb.co>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// Cadenas generales
$string['pluginname'] = 'INTEB';
$string['configtitle'] = 'Tema INTEB';
$string['choosereadme'] = 'INTEB es un tema creado por el Ing. Pablo A Pico (<a href="http://ingeweb.co/">IngeWeb</a>) exclusivamente para nuestros clientes.';

// Nuevas cadenas para tareas programadas
$string['license_check_task'] = 'Tarea de verificación de licencia para INTEB';
$string['license_activated'] = 'Licencia activada automáticamente';
$string['license_activation_automatic'] = 'La licencia ha sido activada automáticamente. No es necesario realizar ninguna acción adicional.';

// Configuración del tema
$string['themesettings'] = 'Configuración del tema';
$string['themesettingsgeneral'] = 'General';
$string['themesettingslogin'] = 'Página de acceso';
$string['dashboardsettings'] = 'Panel de control';
$string['footersettings'] = 'Pie de página';

// Aviso general
$string['generalnoticemode'] = 'Modo de aviso general';
$string['generalnoticemodedesc'] = 'Elige el tipo de aviso general que se mostrará en el sitio.';
$string['generalnoticemode_off'] = 'Desactivado';
$string['generalnoticemode_info'] = 'Información (azul)';
$string['generalnoticemode_danger'] = 'Advertencia (rojo)';
$string['generalnotice'] = 'Aviso general';
$string['generalnoticedesc'] = 'Texto que se mostrará en el aviso general en todas las páginas.';
$string['generalnotice_create'] = 'Haz clic aquí para crear un aviso general';

// Configuración del chat
$string['themesettingschat'] = 'Configuración del chat';
$string['themesettingschatdesc'] = 'Configurar las opciones del widget de chat en el sitio.';
$string['enable_chat'] = 'Activar chat';
$string['enable_chatdesc'] = 'Mostrar widget de chat en el sitio.';
$string['tawkto_embed_url'] = 'URL de incrustación de Tawk.to';
$string['tawkto_embed_urldesc'] = 'Introduce la URL de incrustación proporcionada por Tawk.to.';

// Protección de contenido
$string['themesettingscopypaste'] = 'Protección de contenido';
$string['themesettingscopypaste_desc'] = 'Evita que ciertos roles puedan copiar y pegar contenido.';
$string['copypaste_prevention'] = 'Prevención de copia y pegado';
$string['copypaste_preventiondesc'] = 'Evita que ciertos roles puedan copiar y pegar contenido.';
$string['copypaste_roles'] = 'Roles a proteger';
$string['copypaste_rolesdesc'] = 'Selecciona los roles a los que se aplicará la prevención de copia y pegado.';

// Configuración del carrusel
$string['carouselsettings'] = 'Configuración del carrusel';
$string['carouselsettings_desc'] = 'Configura las diapositivas del carrusel en la página de acceso.';
$string['numberofslides'] = 'Número de diapositivas';
$string['numberofslides_desc'] = 'Selecciona cuántas diapositivas mostrar en el carrusel.';
$string['slidetitle'] = 'Título de diapositiva {$a}';
$string['slidetitle_desc'] = 'Título para la diapositiva {$a}.';
$string['slideimage'] = 'Imagen de diapositiva {$a}';
$string['slideimage_desc'] = 'Sube una imagen para la diapositiva {$a} (tamaño recomendado: 1600x900px).';
$string['slideurl'] = 'Enlace de diapositiva {$a}';
$string['slideurldesc'] = 'URL a la que enlazará la diapositiva {$a}.';
$string['carouselinterval'] = 'Intervalo del carrusel';
$string['carouselintervaldesc'] = 'Tiempo en milisegundos entre cambios de diapositivas.';

// Encabezados de área personal
$string['showpersonalareaheader'] = 'Mostrar encabezado de área personal';
$string['showpersonalareaheader_desc'] = 'Mostrar una imagen de encabezado en la página de Área personal.';
$string['personalareaheader'] = 'Imagen de encabezado de área personal';
$string['personalareaheaderdesc'] = 'Sube una imagen para el encabezado del área personal (tamaño recomendado: 1600x300px). {$a->example_banner}';

// Encabezados de mis cursos
$string['showmycoursesheader'] = 'Mostrar encabezado de Mis cursos';
$string['showmycoursesheader_desc'] = 'Mostrar una imagen de encabezado en la página de Mis cursos.';
$string['mycoursesheader'] = 'Imagen de encabezado de Mis cursos';
$string['mycoursesheaderdesc'] = 'Sube una imagen para el encabezado de Mis cursos (tamaño recomendado: 1600x300px). {$a->example_banner}';

// Página de inicio
$string['hidefrontpagesections'] = 'Ocultar secciones de la página principal';
$string['hidefrontpagesections_desc'] = 'Oculta las secciones frontales de la página principal.';

// Pie de página
$string['hidefootersections'] = 'Ocultar secciones del pie de página';
$string['hidefootersections_desc'] = 'Oculta las secciones del pie de página.';
$string['abouttitle'] = 'Título de Acerca de';
$string['abouttitledesc'] = 'Título para la sección Acerca de en el pie de página.';
$string['abouttitle_default'] = 'Acerca de nosotros';
$string['abouttext'] = 'Texto de Acerca de';
$string['abouttextdesc'] = 'Texto para la sección Acerca de en el pie de página.';
$string['abouttext_default'] = '<p>INTEB - Instituto Técnico Educativo Bilingüe.</p>';
$string['credit'] = 'Tema creado por <a target="_blank" class="my-credit-link" href="http://ingeweb.co/">IngeWeb</a> © 2025 - Todos los derechos reservados';

// Página de acceso
$string['default_slide_title'] = 'Bienvenido a nuestra plataforma educativa';
$string['hide'] = 'Ocultar';
$string['show'] = 'Mostrar';

// Licencia
$string['activatelicense'] = 'Activar licencia';
$string['licenseactivation'] = 'Activación de licencia';
$string['licenseactivationdesc'] = 'La licencia se activa automáticamente. Si experimentas problemas, puedes usar el botón a continuación para activarla manualmente.';
$string['themeinfotext'] = 'Este tema fue creado para <strong>otro proyecto de Moodle</strong> por <a target="_blank" href="http://ingeweb.co/">IngeWeb - Soluciones para triunfar en Internet</a>.';

// Mensajes para acceso no autorizado
$string['unauthorized_access'] = 'Acceso no autorizado';
$string['unauthorized_access_msg'] = 'Estás accediendo a este sitio desde un dominio no autorizado. Por favor, contacta con el administrador del sistema.';
$string['devtools_access_disabled'] = 'El acceso a las herramientas de desarrollador está deshabilitado en este sitio.';

// Cadenas adicionales para el panel de control
$string['dashboardpersonalizerinfo'] = 'Información del personalizador del panel';
$string['defaultheader'] = 'Encabezado predeterminado';