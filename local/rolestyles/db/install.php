<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * Role Styles Plugin - Installation script
 *
 * @package    local_rolestyles
 * @copyright  2024 Alonso Arias <soporte@ingeweb.co> - aulatecnos.es - tecnoszubia.es
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Function executed during plugin installation
 * 
 * @return bool
 */
function xmldb_local_rolestyles_install() {
    global $CFG;
    
    // Configuración por defecto del plugin
    $default_settings = array(
        'enabled' => 1,
        'selected_roles' => '', // No roles seleccionados por defecto
        'custom_css' => '/* Ejemplo de CSS para empezar - Aplicación Global */
.role-teacher .main-content {
    background-color: rgba(40, 167, 69, 0.05);
    border-left: 4px solid #28a745;
    padding: 15px;
}

.role-student .main-content {
    background-color: rgba(0, 123, 255, 0.05);
    border-left: 4px solid #007bff;
    padding: 15px;
}

/* Los estilos se aplicarán globalmente en toda la plataforma */
.role-manager .navbar {
    background-color: #dc3545 !important;
}

/* Agregar más estilos aquí */',
        'course_categories' => ''
    );
    
    // Establecer configuraciones por defecto solo si no existen
    foreach ($default_settings as $name => $value) {
        $current_value = get_config('local_rolestyles', $name);
        if ($current_value === false) {
            set_config($name, $value, 'local_rolestyles');
        }
    }
    
    return true;
}