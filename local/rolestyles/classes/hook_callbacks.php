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

namespace local_rolestyles;

use core\hook\output\before_http_headers as before_http_headers_hook;

/**
 * Hook callbacks for local_rolestyles.
 *
 * @package   local_rolestyles
 */
class hook_callbacks {
    /**
     * Execute tasks before headers are sent.
     *
     * @param before_http_headers_hook $hook The hook data.
     */
    public static function before_http_headers(before_http_headers_hook $hook): void {
        global $PAGE, $CFG;

        // Inject CSS and JS according to selected roles.
        local_rolestyles_inject_css();

        // Replace the assign renderer when filtering is active.
        if (local_rolestyles_has_selected_role()) {
            require_once($CFG->dirroot . '/local/rolestyles/classes/assign_renderer_factory.php');
            $factory = new \local_rolestyles\assign_renderer_factory($PAGE->theme);
            $ref = new \ReflectionObject($PAGE->theme);
            if ($ref->hasProperty('rf')) {
                $prop = $ref->getProperty('rf');
                $prop->setAccessible(true);
                $prop->setValue($PAGE->theme, $factory);
            }
        }
    }
}
