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
 * External functions for PayU payment gateway.
 *
 * @package    paygw_payu
 * @copyright  2024 Orion Cloud Consulting SAS
 * @author     Alonso Arias <soporte@orioncloud.com.co>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/externallib.php');

/**
 * External functions class for PayU payment gateway.
 */
class paygw_payu_external extends external_api {
    
    /**
     * Returns description of get_pse_banks parameters.
     *
     * @return external_function_parameters
     */
    public static function get_pse_banks_parameters() {
        return new external_function_parameters([]);
    }
    
    /**
     * Get list of PSE banks.
     *
     * @return array List of banks
     */
    public static function get_pse_banks() {
        global $USER;
        
        // Check user is logged in.
        require_login();
        
        // Get any gateway configuration to fetch banks.
        // This is a simplified approach - in production you might want to
        // pass component/paymentarea/itemid to get specific config.
        $config = get_config('paygw_payu');
        
        if (empty($config->apilogin) || empty($config->apikey)) {
            return [];
        }
        
        try {
            $api = new \paygw_payu\api($config);
            $banks = $api->get_pse_banks();
            
            $result = [];
            foreach ($banks as $code => $name) {
                $result[] = [
                    'code' => $code,
                    'name' => $name,
                ];
            }
            
            return $result;
            
        } catch (Exception $e) {
            debugging('Error fetching PSE banks: ' . $e->getMessage(), DEBUG_DEVELOPER);
            return [];
        }
    }
    
    /**
     * Returns description of get_pse_banks return value.
     *
     * @return external_multiple_structure
     */
    public static function get_pse_banks_returns() {
        return new external_multiple_structure(
            new external_single_structure([
                'code' => new external_value(PARAM_TEXT, 'Bank code'),
                'name' => new external_value(PARAM_TEXT, 'Bank name'),
            ])
        );
    }
}