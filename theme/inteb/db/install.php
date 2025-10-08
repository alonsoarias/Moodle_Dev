<?php
// This file is part of Moodle - https://moodle.org/
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
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * Code to be executed after the plugin's database scheme has been installed is defined here.
 *
 * @package     theme_inteb
 * @category    upgrade
 * @copyright   2022 Soporte ingeweb <soporte@ingeweb.com>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Custom code to be run on installing the plugin.
 */
function xmldb_theme_inteb_install() {
    global $CFG, $DB;

    // Cargar y activar automáticamente la licencia
    require_once($CFG->dirroot . '/theme/inteb/classes/license_autoload.php');
    theme_inteb_license_autoload();
    
    // Activar la licencia automáticamente
    if (defined('EDD_LICENSE_STATUS')) {
        set_config(EDD_LICENSE_STATUS, 'valid', 'theme_remui');
        set_config(EDD_LICENSE_KEY, 'license-auto-activated-by-inteb', 'theme_remui');
        set_config(EDD_LICENSE_ACTION, true, 'theme_remui');
        
        // Configurar transient de larga duración
        if (defined('WDM_LICENSE_TRANS')) {
            $transient = serialize(array('valid', time() + (60 * 60 * 24 * 365)));
            set_config(WDM_LICENSE_TRANS, $transient, 'theme_remui');
        }
        
        mtrace('Theme INTEB: Licencia activada automáticamente');
    } else {
        mtrace('Theme INTEB: No se pudo activar la licencia automáticamente - constantes no definidas');
    }

    return true;
}