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
 * Plugin renderer for Educam Bot admin pages.
 *
 * @package     local_educambot
 * @copyright   2024 Educam
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_educambot\output;

use plugin_renderer_base;

/**
 * Renderer that delegates to mustache templates.
 */
class renderer extends plugin_renderer_base {
    /**
     * Renders the rule management table.
     *
     * @param rule_table $renderable
     * @return string
     */
    public function render_rule_table(rule_table $renderable): string {
        return $this->render_from_template('local_educambot/manage_rules', $renderable->export_for_template($this));
    }

    /**
     * Renders the knowledge management table.
     *
     * @param knowledge_table $renderable
     * @return string
     */
    public function render_knowledge_table(knowledge_table $renderable): string {
        return $this->render_from_template('local_educambot/manage_knowledge', $renderable->export_for_template($this));
    }
}
