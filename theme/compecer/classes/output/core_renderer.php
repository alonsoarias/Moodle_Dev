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
 * Core renderer file.
 *
 * @package    theme_compecer
 * @copyright  2024 IngeWeb https://www.ingeweb.co
 * @author     Pedro Arias <soporte@ingeweb.co>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace theme_compecer\output;

use html_writer;
use custom_menu;
use action_menu_filler;
use action_menu_link_secondary;
use stdClass;
use moodle_url;
use action_menu;
use pix_icon;
use theme_config;
use core_text;
use help_icon;
use context_system;
use core_course_list_element;

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/../../../moove/classes/output/core_renderer.php');
require_once(__DIR__ . '/../util/theme_settings.php');

/**
 * Renderers to align Moodle's HTML with that expected by Bootstrap
 *
 * @package    theme_compecer
 * @copyright  2024 IngeWeb https://www.ingeweb.co
 */
class core_renderer extends \theme_moove\output\core_renderer {
    
    /**
     * Cached theme config
     * @var object
     */
    protected $themeConfig = null;

    /**
     * Get theme config with caching
     * @return object
     */
    protected function get_theme_config() {
        if ($this->themeConfig === null) {
            $this->themeConfig = theme_config::load('compecer');
        }
        return $this->themeConfig;
    }

    /**
     * Renders the login form.
     *
     * @param \core_auth\output\login $form The renderable.
     * @return string
     */
    public function render_login(\core_auth\output\login $form) {
        global $SITE, $CFG;

        $context = $form->export_for_template($this);

        // Override because rendering is not supported in template yet.
        $context->cookieshelpiconformatted = $this->help_icon('cookiesenabled');
        $context->errorformatted = $this->error_text($context->error);

        $context->logourl = $this->get_logo();
        $context->sitename = format_string($SITE->fullname, true, ['context' => \context_course::instance(SITEID)]);
        $context->my_credit = get_string('credit', 'theme_compecer');

        if (file_exists(__DIR__ . "/../../templates/core/login-custom.mustache")) {
            return $this->render_from_template('core/login-custom', $context);
        }

        return $this->render_from_template('core/login', $context);
    }

    /**
     * Returns full header.
     *
     * @return string HTML for header
     */
    public function full_header() {
        global $CFG, $USER, $PAGE, $COURSE;

        if ($USER->id != 2) {
            $CFG->perfdebug = 0;
        }

        $theme = $this->get_theme_config();
        
        // Prepare template context
        $header = new stdClass();
        
        // Initialize general notice content
        $header->generalnotice = '';
        
        // Handle general notice display
        if (!empty(trim($theme->settings->generalnotice))) {
            $mode = $theme->settings->generalnoticemode;
            
            if ($mode === 'info') {
                $header->generalnotice = '<div class="alert alert-info mt-4">' .
                    '<strong><i class="fa fa-info-circle"></i></strong> ' . 
                    $theme->settings->generalnotice . 
                    '</div>';
            } else if ($mode === 'danger') {
                $header->generalnotice = '<div class="alert alert-danger mt-4">' .
                    '<strong><i class="fa fa-warning"></i></strong> ' . 
                    $theme->settings->generalnotice . 
                    '</div>';
            }
        }

        // Admin reminder for disabled notice
        if (is_siteadmin() && 
            (!empty($theme->settings->generalnoticemode) && 
             $theme->settings->generalnoticemode === 'off')) {
            $header->generalnotice = '<div class="alert mt-4">' .
                '<a href="' . $CFG->wwwroot . '/admin/settings.php?section=themesettingcompecer#theme_compecer">' .
                '<strong><i class="fa fa-edit"></i></strong> ' . 
                get_string('generalnotice_create', 'theme_compecer') . 
                '</a></div>';
        }
        
        // Get course image if in course context
        $header->courseimageurl = '';
        if ($PAGE->course && $PAGE->course->id > 1) {
            require_once($CFG->dirroot . '/theme/compecer/lib.php');
            $header->courseimageurl = theme_compecer_get_course_image($PAGE->course);
        }
        
        // Add context information
        $header->contextheader = $this->context_header();
        $header->pageheadingbutton = $this->page_heading_button();
        $header->courseheader = $this->course_header();
        
        if ($this->page->navbar) {
            $header->hasnavbar = true;
            $header->navbar = $this->navbar();
        }

        $header->headeractions = $this->page->get_header_actions();
        
        return $this->render_from_template('theme_compecer/core/full_header', $header);
    }

    /**
     * Returns standard footer content.
     *
     * @return string HTML footer content
     */
    public function standard_footer_html() {
        global $CFG, $USER;

        $output = parent::standard_footer_html();
        $theme = $this->get_theme_config();

        // Add chat widget if enabled and user is logged in
        if (!empty($this->page->theme->settings->enable_chat) && isloggedin()) {
            $output .= $this->add_chat_widget();
        }

        // Add accessibility widget only if enabled and user is logged in
        if (isloggedin() && !empty($this->page->theme->settings->accessibility_widget)) {
            $output .= '<script src="https://website-widgets.pages.dev/dist/sienna.min.js" defer></script>';
            debugging('Accessibility widget loaded for user ID: ' . $USER->id, DEBUG_DEVELOPER);
        }

        // Add copy paste prevention if enabled
        if (!empty($this->page->theme->settings->copypaste_prevention)) {
            $this->add_copy_paste_prevention();
        }

        // Check if about text should be hidden
        if (isset($this->page->theme->settings->hideabouttext) && 
            $this->page->theme->settings->hideabouttext == 1) {
            $output .= '<style>
                body section#top-footer { 
                    display: none !important; 
                }
            </style>';
        }

        return $output;
    }

    /**
     * Get theme image URL.
     *
     * @param string $img Image name
     * @return string Image URL
     */
    public function get_theme_img_url($img) {
        $theme = $this->get_theme_config();
        return $theme->setting_file_url($img, $img);
    }

    /**
     * Adds chat widget if enabled.
     *
     * @return string Chat widget HTML
     */
    protected function add_chat_widget() {
        global $USER;
        
        if (empty($this->page->theme->settings->tawkto_embed_url)) {
            return '';
        }

        // Sanitize user data
        $userData = [
            'name' => clean_param($USER->firstname . " " . $USER->lastname, PARAM_TEXT),
            'email' => clean_param($USER->email, PARAM_EMAIL),
            'username' => clean_param($USER->username, PARAM_USERNAME),
            'idnumber' => clean_param($USER->idnumber, PARAM_TEXT)
        ];

        return "<!--Start of Chat Script-->
        <script type=\"text/javascript\">
        var Tawk_API=Tawk_API||{}, Tawk_LoadStart=new Date();
        Tawk_API.visitor = " . json_encode($userData) . ";
        Tawk_API.onLoad = function(){
            Tawk_API.setAttributes(" . json_encode($userData) . ", function(error){});
        };
        (function(){
            var s1=document.createElement(\"script\"),s0=document.getElementsByTagName(\"script\")[0];
            s1.async=true;
            s1.src='" . clean_param($this->page->theme->settings->tawkto_embed_url, PARAM_URL) . "';
            s1.charset='UTF-8';
            s1.setAttribute('crossorigin','*');
            s0.parentNode.insertBefore(s1,s0);
        })();
        </script>
        <!--End of Chat Script-->";
    }

    /**
     * Adds copy/paste prevention JavaScript for specified roles.
     */
    protected function add_copy_paste_prevention() {
        global $USER, $PAGE, $COURSE;

        try {
            // Get restricted roles from theme settings
            $restricted_roles = $this->page->theme->settings->copypaste_roles;

            // Return if no roles are restricted or user is admin
            if (empty($restricted_roles) || is_siteadmin()) {
                return;
            }

            // Get appropriate context
            $context = null;
            if (!empty($COURSE->id) && $COURSE->id > 1) {
                $context = \context_course::instance($COURSE->id);
            } else if (!empty($PAGE->context)) {
                $context = $PAGE->context;
            }

            if (!$context) {
                return;
            }

            // Convert roles to array if needed
            if (!is_array($restricted_roles)) {
                $restricted_roles = explode(',', $restricted_roles);
            }

            // Check user roles
            $has_restricted_role = false;
            $user_roles = get_user_roles($context, $USER->id);

            foreach ($user_roles as $role) {
                if (in_array($role->roleid, $restricted_roles)) {
                    $has_restricted_role = true;
                    break;
                }
            }

            // Apply restrictions if needed
            if (isloggedin() && $has_restricted_role) {
                $PAGE->requires->js_call_amd('theme_compecer/prevent_copy_paste', 'init');
            }

        } catch (\moodle_exception $e) {
            debugging('Error in copy/paste prevention: ' . $e->getMessage(), DEBUG_DEVELOPER);
            return;
        }
    }
}