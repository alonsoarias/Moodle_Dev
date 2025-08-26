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
 * PayU payment gateway installation script.
 *
 * @package    paygw_payu
 * @copyright  2024 Orion Cloud Consulting SAS
 * @author     Alonso Arias <soporte@orioncloud.com.co>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Enable PayU payment gateway on installation.
 *
 * @return bool
 */
function xmldb_paygw_payu_install() {
    global $CFG;

    // Enable the PayU payment gateway on installation.
    $order = (!empty($CFG->paygw_plugins_sortorder)) ? explode(',', $CFG->paygw_plugins_sortorder) : [];
    if (!in_array('payu', $order)) {
        $order[] = 'payu';
        set_config('paygw_plugins_sortorder', implode(',', $order));
    }

    return true;
}