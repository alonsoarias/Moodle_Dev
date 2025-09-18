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
 * Local library functions for backward compatibility
 *
 * @package    local_downloadcenter
 * @copyright  2025 Original: Academic Moodle Cooperation, Extended: Alonso Arias <soporte@ingeweb.co>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Legacy factory class for backward compatibility.
 * Redirects to new namespaced class.
 *
 * @deprecated Since version 5.1.0, use \local_downloadcenter\factory instead
 * @package    local_downloadcenter
 * @copyright  2025 Original: Academic Moodle Cooperation, Extended: Alonso Arias <soporte@ingeweb.co>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class local_downloadcenter_factory {
    
    /** @var \local_downloadcenter\factory Internal factory instance */
    protected $factory;
    
    /** @var stdClass Course object */
    protected $course;
    
    /** @var stdClass User object */
    protected $user;
    
    /**
     * Constructor - creates internal factory instance.
     *
     * @param stdClass $course Course object
     * @param stdClass $user User object
     */
    public function __construct($course, $user) {
        $this->factory = new \local_downloadcenter\factory($course, $user);
        $this->course = $course;
        $this->user = $user;
    }
    
    /**
     * Get resources available for user.
     *
     * @return array Array of resources grouped by section
     */
    public function get_resources_for_user() {
        return $this->factory->get_resources_for_user();
    }
    
    /**
     * Get JavaScript module names.
     *
     * @return array Array of module names
     */
    public function get_js_modnames() {
        return $this->factory->get_js_modnames();
    }
    
    /**
     * Parse form data and set filtered resources.
     *
     * @param stdClass $data Form data
     * @return void
     */
    public function parse_form_data($data) {
        return $this->factory->parse_form_data($data);
    }
    
    /**
     * Create ZIP file for download.
     *
     * @return void Outputs ZIP file and exits
     */
    public function create_zip() {
        return $this->factory->create_zip();
    }
    
    /**
     * Shorten filename to specified length.
     *
     * @param string $filename Filename to shorten
     * @param int $maxlength Maximum length
     * @return string Shortened filename
     */
    public static function shorten_filename($filename, $maxlength = 64) {
        return \local_downloadcenter\factory::shorten_filename($filename, $maxlength);
    }
    
    /**
     * Convert content to HTML document.
     *
     * @param string $title Document title
     * @param string $content Document content
     * @param string $additionalhead Additional head content
     * @return string Complete HTML document
     */
    public static function convert_content_to_html_doc($title, $content, $additionalhead = '') {
        return \local_downloadcenter\factory::convert_content_to_html_doc($title, $content, $additionalhead);
    }
}