<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * Role Styles Plugin - Spanish language strings
 *
 * @package    local_rolestyles
 * @copyright  2024 Alonso Arias <soporte@ingeweb.co> - aulatecnos.es - tecnoszubia.es
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['pluginname'] = 'Estilos por Rol';
$string['privacy:metadata'] = 'El plugin de Estilos por Rol no almacena datos personales de los usuarios.';

// Información del plugin
$string['plugin_description'] = 'Este plugin permite aplicar estilos CSS personalizados según los roles que tenga el usuario de manera global en toda la plataforma. Los estilos se aplican automáticamente en todas las páginas de Moodle para los roles seleccionados.';

// Configuración general
$string['enabled'] = 'Habilitar plugin';
$string['enabled_desc'] = 'Activa o desactiva la aplicación de estilos basados en roles de usuario';

// Sección de roles
$string['roles_section'] = 'Selección de Roles';
$string['roles_section_desc'] = 'Selecciona los roles a los que deseas aplicar estilos CSS personalizados de manera global en toda la plataforma';
$string['selected_roles'] = 'Roles seleccionados';
$string['selected_roles_desc'] = 'Mantén presionado Ctrl (o Cmd en Mac) para seleccionar múltiples roles. Los usuarios con estos roles verán los estilos personalizados en toda la plataforma Moodle.';

// Sección de CSS
$string['css_section'] = 'Editor de CSS Personalizado';
$string['css_section_desc'] = 'Escribe aquí los estilos CSS que deseas aplicar globalmente a los usuarios con los roles seleccionados';
$string['custom_css'] = 'CSS Personalizado';
$string['custom_css_desc'] = 'Introduce tu código CSS personalizado. Los estilos se aplicarán globalmente en toda la plataforma. Puedes usar las clases generadas automáticamente como .role-teacher, .role-student, .roleid-5, etc.';

// Ejemplos de CSS
$string['css_examples_title'] = 'Ejemplos de CSS';
$string['css_examples'] = '<strong>Ejemplos de CSS que puedes usar:</strong><br><br>
<code>
/* Cambiar color de fondo para profesores */<br>
.role-teacher .main-content {<br>
&nbsp;&nbsp;&nbsp;&nbsp;background-color: #e8f5e8;<br>
&nbsp;&nbsp;&nbsp;&nbsp;border-left: 4px solid #28a745;<br>
}<br><br>

/* Cambiar navbar para estudiantes */<br>
.role-student .navbar {<br>
&nbsp;&nbsp;&nbsp;&nbsp;background-color: #007bff !important;<br>
}<br><br>

/* Estilos para rol específico por ID */<br>
.roleid-5 .course-content {<br>
&nbsp;&nbsp;&nbsp;&nbsp;box-shadow: 0 2px 10px rgba(0,0,0,0.1);<br>
}<br><br>

/* Aplicar a múltiples roles */<br>
.role-teacher, .role-editingteacher {<br>
&nbsp;&nbsp;&nbsp;&nbsp;/* Estilos para profesores */<br>
}<br>
</code>';

// Configuración avanzada
$string['advanced_section'] = 'Configuración Avanzada';
$string['advanced_section_desc'] = 'Opciones adicionales para usuarios avanzados';
$string['course_categories'] = 'Categorías de curso (IDs)';
$string['course_categories_desc'] = 'IDs de categorías separados por comas. Dejar vacío para aplicar en todos los cursos. Ejemplo: 1,5,10';

// Información del desarrollador
$string['developer_info'] = 'Créditos';
$string['developer_info_desc'] = '<strong>Este plugin ha sido creado para otro caso de éxito de <a href="https://ingeweb.co" target="_blank" style="color: #007bff; text-decoration: none; font-weight: bold;">IngeWeb</a>.</strong><br>';

// Mensajes del sistema
$string['role_styles_applied'] = 'Estilos aplicados para los roles: {$a}';
$string['no_roles_found'] = 'No se encontraron roles coincidentes para este usuario';

// Errores
$string['error_no_roles_selected'] = 'No se han seleccionado roles. Por favor, selecciona al menos un rol en la configuración.';
$string['error_invalid_css'] = 'El CSS contiene elementos no permitidos por seguridad.';
$string['error_saving_config'] = 'Error al guardar la configuración: {$a}';