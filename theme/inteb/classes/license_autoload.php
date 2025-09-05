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
 * Autoload hook for license override
 *
 * @package   theme_inteb
 * @copyright (c) 2025 IngeWeb <soporte@ingeweb.co>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Pedro Arias <soporte@ingeweb.co>
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Esta función se llama temprano en el proceso de bootstrap de Moodle para garantizar
 * que nuestros overrides de licencia se carguen antes que las clases originales de RemUI.
 */
function theme_inteb_license_autoload() {
    global $CFG;
    
    // Para evitar bucles infinitos o cargas redundantes
    static $loaded = false;
    if ($loaded) {
        return;
    }
    
    // Marcar como cargado para evitar llamadas múltiples
    $loaded = true;
    
    // Asegurar que el tema está instalado y que estos archivos existen
    if (!file_exists($CFG->dirroot . '/theme/inteb/classes/controller/LicenseController.php') || 
        !file_exists($CFG->dirroot . '/theme/inteb/classes/controller/RemUIController.php')) {
        return;
    }
    
    // Cargar nuestras clases primero
    require_once($CFG->dirroot . '/theme/inteb/classes/controller/LicenseController.php');
    require_once($CFG->dirroot . '/theme/inteb/classes/controller/RemUIController.php');
    
    // Definir constantes de licencia si aún no están definidas
    if (!defined("PLUGINSHORTNAME")) {
        // Nombre corto del plugin que aparece en la página del menú de licencia.
        define('PLUGINSHORTNAME', 'Edwiser RemUI');
        // Este slug se usa para almacenar los datos en la base de datos.
        // La licencia se verifica usando dos opciones: edd_<slug>_license_key y edd_<slug>_license_status.
        define('PLUGINSLUG', 'remui');
        // Versión actual del plugin. Debe ser similar a la etiqueta de versión mencionada en los encabezados del plugin.
        define('PLUGINVERSION', '4.5.0');
        // Bajo este nombre debe crearse el producto en el sitio de WisdmLabs.
        define('PLUGINNAME', 'Edwiser RemUI');
        // URL local para evitar solicitudes externas
        define('STOREURL', '/theme/inteb/fakecheckurl.php');
        // Nombre del autor.
        define('AUTHORNAME', 'WisdmLabs');

        define('EDD_LICENSE_ACTION', 'licenseactionperformed');
        define('EDD_LICENSE_KEY', 'edd_' . PLUGINSLUG . '_license_key');
        define('EDD_LICENSE_DATA', 'edd_' . PLUGINSLUG . '_license_data');
        define('EDD_PURCHASE_FROM', 'edd_' . PLUGINSLUG . '_purchase_from');
        define('EDD_LICENSE_STATUS', 'edd_' . PLUGINSLUG . '_license_status');
        define('EDD_LICENSE_ACTIVATE', 'edd_' . PLUGINSLUG . '_license_activate');
        define('EDD_LICENSE_DEACTIVATE', 'edd_' . PLUGINSLUG . '_license_deactivate');
        define('WDM_LICENSE_TRANS', 'wdm_' . PLUGINSLUG . '_license_trans');
        define('WDM_LICENSE_PRODUCTSITE', 'wdm_' . PLUGINSLUG . '_product_site');
    }
    
    // Establecer estado de licencia válido
    if (get_config('theme_remui', EDD_LICENSE_STATUS) !== 'valid') {
        set_config(EDD_LICENSE_STATUS, 'valid', 'theme_remui');
        set_config(EDD_LICENSE_KEY, 'license-auto-activated-by-inteb', 'theme_remui');
        set_config(EDD_LICENSE_ACTION, true, 'theme_remui');
        
        // Establecer transient
        $transient = serialize(array('valid', time() + (60 * 60 * 24 * 365)));
        set_config(WDM_LICENSE_TRANS, $transient, 'theme_remui');
    }
}

// Cargar nuestros overrides inmediatamente al incluir este archivo
theme_inteb_license_autoload();