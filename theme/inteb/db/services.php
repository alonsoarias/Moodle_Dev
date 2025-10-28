<?php
/**
 * Web service definitions for theme_inteb.
 *
 * Defines external functions/services that can be called via AJAX
 * to get enhanced course data with all instructors and custom fields.
 *
 * @package    theme_inteb
 * @category   db
 * @author     Pedro Alonso Arias Balcucho
 * @copyright  2025 Soporte IngeWeb <soporte@ingeweb.co>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$functions = [
    // Enhanced course data retrieval
    'theme_inteb_get_enhanced_courses' => [
        'classname'   => 'theme_inteb\external\get_enhanced_courses',
        'methodname'  => 'get_courses',
        'description' => 'Get enhanced course data including all instructors and RemUI custom fields',
        'type'        => 'read',
        'ajax'        => true,
        'loginrequired' => true,
    ],
];
