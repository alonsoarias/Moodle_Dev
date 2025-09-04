<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

namespace local_rolestyles;

use core\output\renderer_factory\standard_renderer_factory;
use moodle_page;

/**
 * Renderer factory that swaps the mod_assign renderer when filtering is active.
 *
 * @package    local_rolestyles
 */
class assign_renderer_factory extends standard_renderer_factory {
    /**
     * {@inheritdoc}
     */
    public function get_renderer(moodle_page $page, $component, $subtype = null, $target = null) {
        if ($component === 'mod_assign' && assign_filter::is_active()) {
            return new \local_rolestyles\output\assign_renderer($page, $target);
        }
        return parent::get_renderer($page, $component, $subtype, $target);
    }
}
