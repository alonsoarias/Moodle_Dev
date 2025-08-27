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
 * Upgrade script for PayU payment gateway.
 *
 * @package    paygw_payu
 * @copyright  2024 Orion Cloud Consulting SAS
 * @author     Alonso Arias <soporte@orioncloud.com.co>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Upgrade the PayU payment gateway plugin.
 *
 * @param int $oldversion The old version of the plugin
 * @return bool
 */
function xmldb_paygw_payu_upgrade($oldversion) {
    global $DB;
    
    $dbman = $DB->get_manager();
    
    if ($oldversion < 2024121900) {
        // Define field extra_parameters to be added to paygw_payu.
        $table = new xmldb_table('paygw_payu');
        $field = new xmldb_field('extra_parameters', XMLDB_TYPE_TEXT, null, null, null, null, null, 'response_code');
        
        // Conditionally launch add field extra_parameters.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        
        // PayU savepoint reached.
        upgrade_plugin_savepoint(true, 2024121900, 'paygw', 'payu');
    }
    
    return true;
}