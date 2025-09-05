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
 * Fake check URL to intercept and simulate license validation
 *
 * @package   theme_inteb
 * @copyright (c) 2025 IngeWeb <soporte@ingeweb.co>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Pedro Arias <soporte@ingeweb.co>
 */

define('NO_MOODLE_COOKIES', true); // No need for a session.
define('NO_UPGRADE_CHECK', true);

require_once('../../../config.php');

// Fake license validation response - always return valid
$response = new stdClass();
$response->license = 'valid';
$response->item_name = 'Edwiser RemUI';
$response->expires = 'lifetime';
$response->payment_id = '12345';
$response->customer_name = 'InteB';
$response->customer_email = 'admin@example.com';
$response->success = true;

// Send response as JSON
header('Content-Type: application/json');
echo json_encode($response);
die;