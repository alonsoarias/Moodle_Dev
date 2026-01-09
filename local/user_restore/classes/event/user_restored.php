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
 * User restored event class.
 *
 * @package    local_user_restore
 * @copyright  2024 Your Institution
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_user_restore\event;

defined('MOODLE_INTERNAL') || die();

/**
 * Event triggered when a user is restored.
 */
class user_restored extends \core\event\base {

    /**
     * Initialize the event.
     */
    protected function init() {
        $this->data['objecttable'] = 'user';
        $this->data['crud'] = 'u';
        $this->data['edulevel'] = self::LEVEL_OTHER;
    }

    /**
     * Get the event name.
     *
     * @return string
     */
    public static function get_name() {
        return get_string('eventuserrestored', 'local_user_restore');
    }

    /**
     * Get the event description.
     *
     * @return string
     */
    public function get_description() {
        $username = $this->other['username'] ?? 'unknown';
        $restoredby = $this->other['restoredby'] ?? 0;

        return "The user with id '{$this->objectid}' was restored with username '{$username}' " .
               "by the user with id '{$restoredby}'.";
    }

    /**
     * Get the URL related to this event.
     *
     * @return \moodle_url
     */
    public function get_url() {
        return new \moodle_url('/user/profile.php', ['id' => $this->objectid]);
    }

    /**
     * Custom validation.
     *
     * @throws \coding_exception
     */
    protected function validate_data() {
        parent::validate_data();

        if (!isset($this->objectid)) {
            throw new \coding_exception('The \'objectid\' value must be set.');
        }
    }
}
