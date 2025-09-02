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

namespace local_rolestyles\output;

use assign_grading_table;
use mod_assign\output\renderer as assign_renderer_base;

/**
 * Renderer extension for mod_assign to filter out students without submissions.
 *
 * @package    local_rolestyles
 */
class assign_renderer extends assign_renderer_base {
    /**
     * Render the grading table filtering out users without submissions when role filtering is active.
     *
     * @param assign_grading_table $table Grading table
     * @return string HTML
     */
    public function render_assign_grading_table(assign_grading_table $table) {
        if (\local_rolestyles_has_selected_role()) {
            $table->setup();
            [$filtered, $total] = \local_rolestyles_filter_assign_grading($table);
            $visible = count($filtered);

            $table->rawdata = $filtered;
            $table->build_table();
            $table->close_recordset();

            $o = '';
            $summary = \local_rolestyles_get_filter_summary($total, $visible);
            $o .= $this->output->notification($summary, 'info');
            $o .= $this->output->box_start('boxaligncenter gradingtable position-relative');
            $this->page->requires->js_init_call('M.mod_assign.init_grading_table', []);
            ob_start();
            $table->finish_output();
            $o .= ob_get_clean();
            $o .= $this->output->box_end();
            return $o;
        }
        return parent::render_assign_grading_table($table);
    }
}
