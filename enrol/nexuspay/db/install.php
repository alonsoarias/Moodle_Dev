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
 * NexusPay enrollment plugin installer script.
 *
 * @package    enrol_nexuspay
 * @copyright  2025 Alonso Arias <soporte@nexuslabs.com.co>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Install function for NexusPay enrollment plugin.
 * 
 * This function is called once during the installation of the plugin.
 * It enables the plugin by default and sets up initial configuration.
 *
 * @return bool Always returns true
 */
function xmldb_enrol_nexuspay_install() {
    global $CFG, $DB;
    
    // Enable the plugin by default.
    $enabled = enrol_get_plugins(false);
    $enabled['nexuspay'] = true;
    $enabled = array_keys($enabled);
    set_config('enrol_plugins_enabled', implode(',', $enabled));
    
    // Set default configuration values if not already set.
    $defaults = [
        'status' => ENROL_INSTANCE_DISABLED,
        'cost' => 0,
        'currency' => 'COP', // Default to Colombian Peso.
        'roleid' => get_config('enrol_manual', 'roleid'), // Use same default as manual enrollment.
        'enrolperiod' => 0,
        'expirynotify' => 0,
        'expirythreshold' => 86400,
        'expirynotifyhour' => 6,
        'expirynotifyperiod' => 900,
        'newenrols' => 1,
        'groupkey' => 0,
        'uninterrupted' => 0,
        'freetrial' => 0,
        'showduration' => 1,
        'forcepayment' => 0,
        'expiredaction' => ENROL_EXT_REMOVED_KEEP,
    ];
    
    foreach ($defaults as $key => $value) {
        if (get_config('enrol_nexuspay', $key) === false) {
            set_config($key, $value, 'enrol_nexuspay');
        }
    }
    
    // Log installation.
    error_log('NexusPay enrollment plugin installed successfully');
    
    return true;
}