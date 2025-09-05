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
 * Edwiser RemUI License Controller override
 *
 * @package   theme_inteb
 * @copyright (c) 2025 IngeWeb <soporte@ingeweb.co>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Pedro Arias <soporte@ingeweb.co>
 */
namespace theme_inteb\controller;

defined('MOODLE_INTERNAL') || die();

use context_system;
use Exception;
use theme_remui\controller\LicenseController as ParentLicenseController;

/**
 * License controller that overrides the RemUI License Controller
 */
class LicenseController extends ParentLicenseController {

    /**
     * Response data
     * @var object
     */
    public static $responsedata;

    /**
     * Get data from database - Override to always return 'available'
     * @return string License status
     */
    public function get_data_from_db() {
        if (null !== self::$responsedata) {
            return self::$responsedata;
        }

        // Always set to available
        self::$responsedata = 'available';
        
        return self::$responsedata;
    }

    /**
     * Update license activation/deactivation status to database.
     * Override to simulate successful activation.
     * @return string Status
     */
    public function serve_license_data() {
        global $CFG;
        if (is_siteadmin()) {
            try {
                // Return if did not come from license page.
                if (!isset($_POST['onLicensePage']) || $_POST['onLicensePage'] == 0) {
                    return;
                }
                $_POST['onLicensePage'] = false;
                
                // Set license data to valid
                \theme_remui\toolbox::set_plugin_config(EDD_LICENSE_STATUS, 'valid');
                \theme_remui\toolbox::set_plugin_config(EDD_LICENSE_ACTION, true);
                
                // Create a valid license response
                $licensedata = new \stdClass();
                $licensedata->license = 'valid';
                $licensedata->expires = 'lifetime';
                $licensedata->success = true;
                
                // Set transient
                $this->set_transient('valid', $licensedata);
                
                return $licensedata;
            } catch (Exception $ex) {
                // Set the error message, received via exception.
                set_config(EDD_LICENSE_DATA, $ex->getMessage(), 'theme_remui');
            }
        }
    }

    /**
     * Get Remui license template context - Override to show always valid
     * @return array Template context
     */
    public function get_remui_license_template_context() {
        global $OUTPUT, $PAGE;

        $systemcontext = context_system::instance();
        $PAGE->set_context($systemcontext);

        $templatecontext = array();
        $templatecontext['pluginslug'] = PLUGINSLUG;
        $templatecontext['licensestatus'] = get_string('active', 'theme_remui');
        $templatecontext['licensestatuscolor'] = "color:green";
        $templatecontext['licensekey'] = 'license-automatically-activated';
        $templatecontext['sesskey'] = sesskey();
        
        // Set as valid license
        $templatecontext["readonly"] = true;
        $templatecontext["isvalid"] = true;
        $templatecontext['buttons'] = [
            [
                "name" => "edd_".PLUGINSLUG."_license_deactivate",
                "value" => get_string('deactivatelicense', 'theme_remui'),
                "classes" => "btn-danger",
            ]
        ];
        
        // Add success alert
        $templatecontext['alert'] = [
            'icon' => "fa-check",
            'subtext' => "Success",
            'classes' => 'alert-success',
            'text' => get_string('licensekeyactivated', 'theme_remui')
        ];
        
        return $templatecontext;
    }

    /**
     * Override to always return a valid license response
     */
    public function license_handler_for_setup_wizard($key) {
        global $CFG;

        if (is_siteadmin()) {
            try {
                // Create a valid license response
                $licensedata = new \stdClass();
                $licensedata->license = 'valid';
                $licensedata->expires = 'lifetime';
                $licensedata->success = true;
                $licensedata->download_links = $this->get_default_plugin_download_links();
                
                // Set license data to valid
                \theme_remui\toolbox::set_plugin_config(EDD_LICENSE_KEY, $key);
                \theme_remui\toolbox::set_plugin_config(EDD_LICENSE_STATUS, 'valid');
                \theme_remui\toolbox::set_plugin_config(EDD_LICENSE_ACTION, true);
                
                set_config('edd_remui_setup_license_data', json_encode($licensedata), 'theme_remui');
                
                return $licensedata;
            } catch (Exception $ex) {
                set_config(EDD_LICENSE_DATA, $ex->getMessage(), 'theme_remui');
                return $ex->getMessage();
            }
        }
    }
    
    /**
     * Provide default plugin download links
     * @return object Default plugin links
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