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
 * Edwiser RemUI Controller override
 *
 * @package   theme_inteb
 * @copyright (c) 2025 IngeWeb <soporte@ingeweb.co>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Pedro Arias <soporte@ingeweb.co>
 */
namespace theme_inteb\controller;

use Exception;
use theme_remui\controller\RemUIController as ParentRemUIController;

/**
 * RemUI controller that overrides Edwiser RemUI controller
 */
class RemUIController extends ParentRemUIController {

    /**
     * Activate theme license - Override to always succeed
     */
    public function activate_license() {
        // Set license key and purchase from
        \theme_remui\toolbox::set_plugin_config(EDD_LICENSE_KEY, $this->licensekey);
        \theme_remui\toolbox::set_plugin_config(EDD_PURCHASE_FROM, 'remui');
        
        // Create a valid license response
        $licensedata = new \stdClass();
        $licensedata->license = 'valid';
        $licensedata->expires = 'lifetime';
        $licensedata->success = true;
        
        // Process the response
        $this->process_response_data($licensedata);
        return $licensedata;
    }

    /**
     * Deactivate theme license - Override to maintain valid status
     */
    public function deactivate_license() {
        global $DB;
        
        // Create a valid response even for deactivation
        $licensedata = new \stdClass();
        $licensedata->license = 'valid';
        
        // Set license action
        \theme_remui\toolbox::set_plugin_config(EDD_LICENSE_ACTION, true);
        
        // Instead of setting to deactivated, keep it valid
        \theme_remui\toolbox::set_plugin_config(EDD_LICENSE_STATUS, 'valid');
        
        // Set license check transient value
        \theme_remui\toolbox::set_plugin_config(
            WDM_LICENSE_TRANS,
            serialize(array('valid', time() + (60 * 60 * 24 * 7)))
        );
        
        return $licensedata;
    }

    /**
     * Overrides the request_license_data method to always return valid license
     *
     * @param string $action Action name
     * @param string $licensekey License key
     * @return object License data
     */
    public function request_license_data($action, $licensekey) {
        // Create a valid license response
        $licensedata = new \stdClass();
        $licensedata->license = 'valid';
        $licensedata->expires = 'lifetime';
        $licensedata->success = true;
        $licensedata->renew_link = '#';
        
        // Set transient with valid status
        $lcontroller = new \theme_inteb\controller\LicenseController();
        $licensestatus = $lcontroller->status_update($licensedata);
        $lcontroller->set_transient($licensestatus, $licensedata);
        
        return $licensedata;
    }

    /**
     * Check if updates available - Override to avoid license check
     *
     * @return String status.
     */
    public static function check_remui_update() {
        // Always return 'available' to indicate updates are available
        // This ensures the theme believes updates are accessible
        return 'available';
    }

    /**
     * Request license data for the setup wizard - Override to simulate valid license
     *
     * @param string $key License key
     * @return object License data
     */
    public function request_license_data_for_setup_wizard($key) {
        // Create a valid license response with download links
        $licensedata = new \stdClass();
        $licensedata->license = 'valid';
        $licensedata->expires = 'lifetime';
        $licensedata->success = true;
        
        // Add download links for plugins
        $licensedata->download_links = $this->get_default_plugin_download_links();
        
        return $licensedata;
    }
    
    /**
     * Provide default plugin download links
     * @return array Default plugin links
     */
    private function get_default_plugin_download_links() {
        global $CFG;
        $plugins = new \stdClass();
        $plugins->remui = new \stdClass();
        $plugins->remui->post_name = 'remui';
        $plugins->remui->download_links = new \stdClass();
        
        // Simulamos enlaces de plugins sin hacer solicitudes externas reales
        // Estos enlaces nunca serán usados realmente, ya que interceptamos el proceso
        $fakeurl = $CFG->wwwroot . '/theme/inteb/fakeplugins';
        $plugins->remui->download_links->edwiserratingreview = "{$fakeurl}/block_edwiserratingreview.zip";
        $plugins->remui->download_links->remuiformat = "{$fakeurl}/format_remuiformat.zip";
        $plugins->remui->download_links->edwiserpagebuilder = "{$fakeurl}/local_edwiserpagebuilder.zip";
        $plugins->remui->download_links->edwiseradvancedblock = "{$fakeurl}/block_edwiseradvancedblock.zip";
        $plugins->remui->download_links->edwiserpbf = "{$fakeurl}/filter_edwiserpbf.zip";
        
        return [$plugins];
    }
}