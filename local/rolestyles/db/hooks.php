<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * Role Styles Plugin - Hooks configuration for Moodle 4.0+
 *
 * @package    local_rolestyles
 * @copyright  2024 Alonso Arias <soporte@ingeweb.co> - aulatecnos.es - tecnoszubia.es
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

// Configuración de hooks para Moodle 4.0+
$hooks = [
    // Nuevo hook system para Moodle 4.0+
    [
        'callback' => 'local_rolestyles_hook_before_http_headers',
        'hookname' => 'core\\hook\\output\\before_http_headers',
        'priority' => 100,
    ],
];

