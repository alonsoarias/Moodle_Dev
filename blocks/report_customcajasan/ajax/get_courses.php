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
 * AJAX endpoint to get courses for a category
 *
 * @package    block_report_customcajasan
 * @copyright  2025 Cajasan
 * @author     Pedro Arias <soporte@ingeweb.co>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('AJAX_SCRIPT', true);

require_once(__DIR__ . '/../../../config.php');
require_once($CFG->dirroot . '/blocks/report_customcajasan/lib.php');

// Check user login
require_login(null, false);

// Verify AJAX parameters
$categoryid = optional_param('categoryid', 0, PARAM_INT);
$blockinstanceid = optional_param('blockinstanceid', 0, PARAM_INT);
$blockrestrictions = block_report_customcajasan_get_block_restrictions($blockinstanceid);

// Verify sesskey
if (!confirm_sesskey()) {
    $error = array(
        'success' => false,
        'error' => get_string('invalidsesskey', 'error')
    );
    echo json_encode($error);
    die();
}

// Check capability or manager role
$systemcontext = context_system::instance();
try {
    // Verificar permisos - permitir acceso a gestores o usuarios con la capacidad específica
    $can_view = has_capability('block/report_customcajasan:viewreport', $systemcontext);
    $is_manager = has_any_capability(['moodle/site:config', 'moodle/course:update'], $systemcontext);
    $can_view_parent = !empty($blockrestrictions['parentcontext']) &&
        has_capability('block/report_customcajasan:viewreport', $blockrestrictions['parentcontext']);

    if (!$can_view && !$is_manager && !$can_view_parent) {
        throw new required_capability_exception($systemcontext, 'block/report_customcajasan:viewreport', 'nopermissions', '');
    }
} catch (Exception $e) {
    $error = array(
        'success' => false,
        'error' => get_string('nopermissions', 'error', 'block/report_customcajasan:viewreport')
    );
    echo json_encode($error);
    die();
}

try {
    $allowedcourses = $blockrestrictions['courses'];
    $allowedcategories = $blockrestrictions['expandedcategories'];

    if (!empty($allowedcategories) && !empty($categoryid) &&
        !in_array((int)$categoryid, $allowedcategories, true)) {
        $categoryid = 0;
    }

    // Get courses
    $courses = report_customcajasan_get_courses($categoryid, $allowedcourses, $allowedcategories);
    
    // Format response
    $response = array(
        'success' => true,
        'courses' => array_values($courses),
        'count' => count($courses)
    );
    
    // Send JSON response
    echo json_encode($response);
} catch (Exception $e) {
    $error = array(
        'success' => false,
        'error' => 'Error getting courses: ' . $e->getMessage()
    );
    echo json_encode($error);
}