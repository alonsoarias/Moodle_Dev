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
 * Post-installation script for mod_caracterizacion.
 *
 * Creates the 8 UDES roles required for the characterization workflow.
 *
 * @package     mod_caracterizacion
 * @copyright   2024 Alonso Arias <soporte@orioncloud.com.co>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

// Include upgrade.php which contains the role creation function.
require_once(__DIR__ . '/upgrade.php');

/**
 * Post-installation procedure.
 *
 * Creates the 8 UDES workflow roles with their specific capabilities.
 *
 * @return bool
 */
function xmldb_mod_caracterizacion_install() {
    // Use the shared function from upgrade.php to create UDES roles.
    caracterizacion_create_udes_roles();

    return true;
}
