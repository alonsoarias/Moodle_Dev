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
 * Version information for local_downloadcenter
 *
 * @package    local_downloadcenter
 * @copyright  2025 Original: Academic Moodle Cooperation, Extended: Alonso Arias <soporte@ingeweb.co>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$plugin->component = 'local_downloadcenter';
$plugin->version   = 2025120201;  // YYYYMMDDXX format.
$plugin->release   = 'v5.1.0-extended';
$plugin->requires  = 2024100700;  // Requires Moodle 4.5+.
$plugin->maturity  = MATURITY_STABLE;
$plugin->cron      = 0;