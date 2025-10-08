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
 * Plugin upgrade steps are defined here.
 *
 * @package     theme_nexo
 * @category    upgrade
 * @copyright   2022 Soporte ingeweb <alonsoariasb@gmail.comm>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__.'/upgradelib.php');

/**
 * Execute theme_nexo upgrade from the given old version.
 *
 * @param int $oldversion
 * @return bool
 */
function xmldb_theme_nexo_upgrade($oldversion) {
    global $CFG, $DB;

    $dbman = $DB->get_manager();

    // Cargar y activar automáticamente la licencia en cada actualización
    require_once($CFG->dirroot . '/theme/nexo/classes/license_autoload.php');
    theme_nexo_license_autoload();
    
    // Restablecer la licencia a válida en cada actualización
    if (defined('EDD_LICENSE_STATUS')) {
        set_config(EDD_LICENSE_STATUS, 'valid', 'theme_remui');
        set_config(EDD_LICENSE_KEY, 'license-auto-activated-by-nexo', 'theme_remui');
        set_config(EDD_LICENSE_ACTION, true, 'theme_remui');
        
        // Configurar transient de larga duración
        if (defined('WDM_LICENSE_TRANS')) {
            $transient = serialize(array('valid', time() + (60 * 60 * 24 * 365)));
            set_config(WDM_LICENSE_TRANS, $transient, 'theme_remui');
        }
        
        mtrace('Theme NEXO: Licencia reactivada automáticamente durante la actualización');
    } else {
        mtrace('Theme NEXO: No se pudo reactivar la licencia - constantes no definidas');
    }

    return true;
}