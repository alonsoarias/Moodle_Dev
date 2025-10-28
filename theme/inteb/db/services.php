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

    // Inteb's custom webservice for myoverview courses
    // Uses unique name to avoid conflicts with RemUI parent theme
    'theme_inteb_get_myoverviewcourses' => [
        'classname'   => 'theme_inteb\external\api',
        'methodname'  => 'get_myoverviewcourses',
        'description' => 'Get user courses with custom fields and all instructors - Enhanced for theme_inteb',
        'type'        => 'read',
        'ajax'        => true,
        'loginrequired' => true,
        'services'    => [MOODLE_OFFICIAL_MOBILE_SERVICE],
    ],
];
