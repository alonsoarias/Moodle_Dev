<?php
// This file part of a custom tool devoled by www.promwebsoft.com

/**
 * Plugin version info
 *
 * @package    tool
 * @subpackage idnumbertousernames
 * @copyright  2016 Pablo A Pico @pabloapico
 * @license    Para uso exclusivo de www.promwebsoft.com y sus clientes
 */

defined('MOODLE_INTERNAL') || die();

$capabilities = array(

    // Allows the user to upload user pictures.
    'tool/idnumbertousernames:idnumbertousernames' => array(
        'riskbitmask' => RISK_PERSONAL,
        'captype' => 'read',
        'contextlevel' => CONTEXT_SYSTEM,
        'archetypes' => array(
            'manager' => CAP_ALLOW
        ),
        'clonepermissionsfrom' =>  'moodle/site:uploadusers',
    ),
);
