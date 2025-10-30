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
 * Autoloader para sobrescribir clases de format_remuiformat en theme_inteb
 *
 * Este autoloader intercepta la carga de clases específicas de format_remuiformat
 * y carga en su lugar nuestras versiones extendidas que muestran TODOS los profesores.
 *
 * @package   theme_inteb
 * @copyright 2025 INTEB
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Autoloader personalizado para interceptar clases de format_remuiformat
 *
 * @param string $classname Nombre completo de la clase a cargar
 * @return bool True si se manejó la clase, false si no
 */
function theme_inteb_format_remuiformat_autoloader($classname) {
    // Solo interceptar clases de format_remuiformat\output
    if (strpos($classname, 'format_remuiformat\\output\\') !== 0) {
        return false;
    }

    // Mapeo de clases originales a nuestras versiones extendidas
    $class_overrides = [
        'format_remuiformat\\output\\format_remuiformat_card_one_section' =>
            '\\theme_inteb\\output\\format_remuiformat_card_one_section',
        'format_remuiformat\\output\\format_remuiformat_list_one_section' =>
            '\\theme_inteb\\output\\format_remuiformat_list_one_section',
    ];

    // Si la clase está en nuestro mapeo, crear un alias
    if (isset($class_overrides[$classname])) {
        $override_class = $class_overrides[$classname];

        // Asegurarse de que nuestra clase override está cargada
        $override_file = __DIR__ . '/output/' . basename(str_replace('\\', '/', $override_class)) . '.php';
        if (file_exists($override_file)) {
            require_once($override_file);

            // Crear alias de clase: cuando se pida la clase original, usar la nuestra
            if (class_exists($override_class)) {
                class_alias($override_class, $classname);
                return true;
            }
        }
    }

    return false;
}

// Registrar nuestro autoloader ANTES del autoloader de Moodle
// para que tenga prioridad al cargar las clases
spl_autoload_register('theme_inteb_format_remuiformat_autoloader', true, true);
