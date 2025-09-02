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

use core\output\renderer_factory\standard_renderer_factory;
use moodle_page;

/**
 * Renderer factory to override mod_assign renderer when role filtering is active.
 *
 * @package    local_rolestyles
 */
class assign_renderer_factory extends standard_renderer_factory {
    /**
     * Get the renderer for the given component.
     *
     * @param moodle_page $page The page that we are rendering
     * @param string $component Component name such as 'core' or 'mod_assign'
     * @param string|null $subtype Optional subtype
     * @param string|null $target Rendering target
     * @return \core\output\renderer_base
     */
    public function get_renderer(moodle_page $page, $component, $subtype = null, $target = null) {
        if ($component === 'mod_assign' && \local_rolestyles_has_selected_role()) {
            return new \local_rolestyles\output\assign_renderer($page, $target);
        }
        return parent::get_renderer($page, $component, $subtype, $target);
    }
}
