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
 * Privacy provider for the Assignment No Submission Filter plugin.
 *
 * This plugin does not store any personal data. It only modifies
 * the display of existing assignment data based on configuration.
 *
 * @package    local_assign_no_submission_filter
 * @author     IngeWeb
 * @copyright  2026 IngeWeb para TecnosZubia
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_assign_no_submission_filter\privacy;

defined('MOODLE_INTERNAL') || die();

/**
 * Privacy provider implementation.
 *
 * This class declares that the plugin does not store any personal
 * user data, implementing the null_provider interface.
 *
 * @package    local_assign_no_submission_filter
 * @author     IngeWeb
 * @copyright  2026 IngeWeb para TecnosZubia
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class provider implements \core_privacy\local\metadata\null_provider {

    /**
     * Get the language string identifier explaining why this plugin stores no data.
     *
     * @return string The language string identifier.
     */
    public static function get_reason(): string {
        return 'privacy:metadata';
    }
}
