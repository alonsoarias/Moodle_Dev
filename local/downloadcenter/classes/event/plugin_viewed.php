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
 * Plugin viewed event
 *
 * @package    local_downloadcenter
 * @copyright  2025 Original: Academic Moodle Cooperation
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_downloadcenter\event;

defined('MOODLE_INTERNAL') || die();

/**
 * Event triggered when download center is viewed.
 *
 * @package    local_downloadcenter
 * @copyright  2025 Original: Academic Moodle Cooperation
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class plugin_viewed extends \core\event\base {
    
    /**
     * Init method.
     *
     * @return void
     */
    protected function init() {
        $this->data['crud'] = 'r';
        $this->data['edulevel'] = self::LEVEL_PARTICIPATING;
        $this->data['objecttable'] = 'course';
    }
    
    /**
     * Returns the event name.
     *
     * @return string
     */
    public static function get_name() {
        return get_string('eventviewed', 'local_downloadcenter');
    }
    
    /**
     * Returns description of what happened.
     *
     * @return string
     */
    public function get_description() {
        if ($this->contextlevel == CONTEXT_COURSE) {
            return "The user with id {$this->userid} viewed the download center for the course with id {$this->objectid}.";
        } else {
            return "The user with id {$this->userid} viewed the admin download center.";
        }
    }
    
    /**
     * Returns relevant URL.
     *
     * @return \moodle_url
     */
    public function get_url() {
        if ($this->contextlevel == CONTEXT_COURSE) {
            return new \moodle_url('/local/downloadcenter/index.php', ['courseid' => $this->objectid]);
        } else {
            return new \moodle_url('/local/downloadcenter/index.php', ['mode' => 'admin']);
        }
    }
    
    /**
     * Custom validation.
     *
     * @return void
     * @throws \coding_exception
     */
    protected function validate_data() {
        parent::validate_data();
        
        if (!in_array($this->contextlevel, [CONTEXT_COURSE, CONTEXT_SYSTEM])) {
            throw new \coding_exception('Context level must be CONTEXT_COURSE or CONTEXT_SYSTEM.');
        }
    }
}