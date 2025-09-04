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
 * Settings for the PayU payment gateway
 *
 * @package     paygw_payu
 * @copyright   2025 Alonso Arias <soporte@nexuslabs.com.co>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if ($ADMIN->fulltree) {
    // Add heading
    $settings->add(new admin_setting_heading(
        'paygw_payu_settings',
        '',
        get_string('pluginname_desc', 'paygw_payu')
    ));

    // Add common gateway settings
    \core_payment\helper::add_common_gateway_settings($settings, 'paygw_payu');
    
    // Display information about supported countries
    $countries_info = '<div class="alert alert-info">';
    $countries_info .= '<h5>' . get_string('country', 'paygw_payu') . '</h5>';
    $countries_info .= '<ul>';
    foreach (\paygw_payu\gateway::SUPPORTED_COUNTRIES as $code => $name) {
        $currencies = implode(', ', \paygw_payu\gateway::COUNTRY_CURRENCIES[$code]);
        $countries_info .= '<li><strong>' . $name . '</strong> (' . $code . '): ' . $currencies . '</li>';
    }
    $countries_info .= '</ul>';
    $countries_info .= '</div>';
    
    $settings->add(new admin_setting_heading(
        'paygw_payu_countries',
        get_string('country', 'paygw_payu'),
        $countries_info
    ));
}