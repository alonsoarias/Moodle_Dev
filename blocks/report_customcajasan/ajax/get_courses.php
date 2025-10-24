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

require_login(null, false);

$categoryid = optional_param('categoryid', 0, PARAM_INT);
$blockinstanceid = optional_param('blockinstanceid', 0, PARAM_INT);
$blockrestrictions = block_report_customcajasan_get_block_restrictions($blockinstanceid);

if (!confirm_sesskey()) {
    echo json_encode([
        'success' => false,
        'error' => get_string('invalidsesskey', 'error')
    ]);
    die();
}

$systemcontext = context_system::instance();
$hasaccess = false;

try {
    if (!empty($blockrestrictions['parentcontext']) && 
        $blockrestrictions['parentcontext']->contextlevel == CONTEXT_COURSE) {
        $context = $blockrestrictions['parentcontext'];
        $hasaccess = has_capability('moodle/course:update', $context) ||
                     has_capability('moodle/course:manageactivities', $context);
    } else {
        $hasaccess = has_capability('moodle/site:config', $systemcontext);
    }

    if (!$hasaccess) {
        throw new required_capability_exception(
            $systemcontext, 
            'block/report_customcajasan:viewreport', 
            'nopermissions', 
            ''
        );
    }
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => get_string('nopermissions', 'error')
    ]);
    die();
}

try {
    $allowedcourses = $blockrestrictions['courses'];
    $allowedcategories = $blockrestrictions['expandedcategories'];

    if (!empty($allowedcategories) && !empty($categoryid) &&
        !in_array((int)$categoryid, $allowedcategories, true)) {
        $categoryid = 0;
    }

    $courses = report_customcajasan_get_courses($categoryid, $allowedcourses, $allowedcategories);
    
    $response = array(
        'success' => true,
        'courses' => array_values($courses),
        'count' => count($courses)
    );
    
    echo json_encode($response);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => 'Error getting courses: ' . $e->getMessage()
    ]);
}