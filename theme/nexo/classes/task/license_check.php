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
 * License check scheduled task
 *
 * @package   theme_nexo
 * @copyright (c) 2025 Vicerrectoría Académica - ISER <alonsoariasb@gmail.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace theme_nexo\task;

defined('MOODLE_INTERNAL') || die();

/**
 * License check task to ensure RemUI license is always valid
 */
class license_check extends \core\task\scheduled_task {

    /**
     * Return the task's name as shown in admin screens.
     *
     * @return string
     */
    public function get_name() {
        return get_string('license_check_task', 'theme_nexo');
    }

    /**
     * Execute the task.
     */
    public function execute() {
        global $CFG;
        
        // Cargar el controlador de licencia automaticamente
        require_once($CFG->dirroot . '/theme/nexo/classes/license_autoload.php');
        
        if (function_exists('theme_nexo_license_autoload')) {
            theme_nexo_license_autoload();
            mtrace('NEXO license check executed successfully.');
        } else {
            mtrace('NEXO license autoload function not found.');
        }
        
        return true;
    }
}