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
 * Selection manager for persistent course selection
 *
 * @package    local_downloadcenter
 * @copyright  2025 Alonso Arias <soporte@ingeweb.co>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_downloadcenter;

defined('MOODLE_INTERNAL') || die();

/**
 * Selection manager class for handling user selections.
 *
 * @package    local_downloadcenter
 * @copyright  2025 Alonso Arias <soporte@ingeweb.co>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class selection_manager {
    
    /** @var int User ID */
    protected $userid;
    
    /** @var array Selection data */
    protected $selection;
    
    /** @var array Download options */
    protected $options;
    
    /**
     * Constructor.
     *
     * @param int $userid User ID
     */
    public function __construct($userid) {
        $this->userid = $userid;
        $this->load_selection();
        $this->load_options();
    }
    
    /**
     * Load user selection from preferences.
     *
     * @return void
     */
    protected function load_selection() {
        $pref = get_user_preferences('downloadcenter_selection', '{}', $this->userid);
        $this->selection = json_decode($pref, true) ?: [];
    }
    
    /**
     * Load download options from preferences.
     *
     * @return void
     */
    protected function load_options() {
        $pref = get_user_preferences('downloadcenter_options', '{}', $this->userid);
        $this->options = json_decode($pref, true) ?: [
            'excludestudent' => get_config('local_downloadcenter', 'excludestudentdefault'),
            'includefiles' => 1,
            'filesrealnames' => 0,
            'addnumbering' => 0
        ];
    }
    
    /**
     * Save selection to preferences.
     *
     * @return void
     */
    protected function save_selection() {
        set_user_preference('downloadcenter_selection', json_encode($this->selection), $this->userid);
    }
    
    /**
     * Save download options to preferences.
     *
     * @return void
     */
    protected function save_options() {
        set_user_preference('downloadcenter_options', json_encode($this->options), $this->userid);
    }
    
    /**
     * Add course to selection.
     *
     * @param int $courseid Course ID
     * @param array $resources Resources selection (optional)
     * @return void
     */
    public function add_course($courseid, $resources = []) {
        $this->selection[$courseid] = empty($resources) ? ['all' => true] : $resources;
        $this->save_selection();
    }
    
    /**
     * Remove course from selection.
     *
     * @param int $courseid Course ID
     * @return void
     */
    public function remove_course($courseid) {
        unset($this->selection[$courseid]);
        $this->save_selection();
    }
    
    /**
     * Get selected course IDs.
     *
     * @return array Array of course IDs
     */
    public function get_selected_courses() {
        return array_keys($this->selection);
    }
    
    /**
     * Get selection for specific course.
     *
     * @param int $courseid Course ID
     * @return array Course selection data
     */
    public function get_course_selection($courseid) {
        return $this->selection[$courseid] ?? [];
    }
    
    /**
     * Get all course selections.
     *
     * @return array All selections
     */
    public function get_course_selections() {
        return $this->selection;
    }
    
    /**
     * Set download options.
     *
     * @param array $options Options array
     * @return void
     */
    public function set_download_options($options) {
        $this->options = array_merge($this->options, $options);
        $this->save_options();
    }
    
    /**
     * Get download options.
     *
     * @return array Download options
     */
    public function get_download_options() {
        return $this->options;
    }
    
    /**
     * Clear all selections.
     *
     * @return void
     */
    public function clear_selection() {
        $this->selection = [];
        $this->save_selection();
    }
    
    /**
     * Count selected courses.
     *
     * @return int Number of selected courses
     */
    public function count_selected() {
        return count($this->selection);
    }
    
    /**
     * Check if course is selected.
     *
     * @param int $courseid Course ID
     * @return bool True if selected
     */
    public function is_course_selected($courseid) {
        return isset($this->selection[$courseid]);
    }
    
    /**
     * Toggle course selection.
     *
     * @param int $courseid Course ID
     * @return bool New selection state
     */
    public function toggle_course($courseid) {
        if ($this->is_course_selected($courseid)) {
            $this->remove_course($courseid);
            return false;
        } else {
            $this->add_course($courseid);
            return true;
        }
    }
    
    /**
     * Update course resources selection.
     *
     * @param int $courseid Course ID
     * @param array $resources Resources selection
     * @return void
     */
    public function update_course_resources($courseid, $resources) {
        if (empty($resources)) {
            $this->remove_course($courseid);
        } else {
            $this->selection[$courseid] = $resources;
            $this->save_selection();
        }
    }
}