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
 * NexusPay enrolment plugin version specification.
 *
 * @package    enrol_nexuspay
 * @copyright  2025 NexusPay Development Team
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$plugin->version   = 2025010100;        // The current plugin version (Date: YYYYMMDDXX).
$plugin->requires  = 2022112800;        // Requires Moodle 4.1 LTS minimum.
$plugin->component = 'enrol_nexuspay';  // Full name of the plugin.
$plugin->release   = '1.0.0';           // Human-friendly version number.
$plugin->maturity  = MATURITY_STABLE;   // This version's maturity level.
$plugin->cron      = 0;                 // Period for cron to run (0 means disabled).