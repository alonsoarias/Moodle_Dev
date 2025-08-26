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
 * Settings for the PayU payment gateway.
 *
 * @package    paygw_payu
 * @copyright  2024 Orion Cloud Consulting SAS
 * @author     Alonso Arias <soporte@orioncloud.com.co>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if ($ADMIN->fulltree) {
    // Introduction.
    $settings->add(new admin_setting_heading(
        'paygw_payu_settings',
        '',
        get_string('pluginname_desc', 'paygw_payu')
    ));
    
    // Debug mode.
    $settings->add(new admin_setting_configcheckbox(
        'paygw_payu/debugmode',
        get_string('debugmode', 'paygw_payu'),
        get_string('debugmode_help', 'paygw_payu'),
        0
    ));
    
    // Add common gateway settings.
    \core_payment\helper::add_common_gateway_settings($settings, 'paygw_payu');
}