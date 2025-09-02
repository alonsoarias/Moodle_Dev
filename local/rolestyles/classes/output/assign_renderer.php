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
            $pagesize = $table->get_rows_per_page();
            $table->setup();

            // Cache filtered rows per assignment and page to avoid repeated DB queries.
            $assignid = $table->assignment->get_instance()->id ?? 0;
            $page = property_exists($table, 'currpage') ? $table->currpage : 0;
            static $cache = [];
            $cachekey = $assignid . ':' . $page;

            if (!array_key_exists($cachekey, $cache)) {
                $table->query_db($pagesize, false);
                $allrows = $table->rawdata ?? [];
                if (!empty($allrows)) {
                    $filtered = array_filter($allrows, function($row) {
                        return $row->status !== ASSIGN_SUBMISSION_STATUS_NEW && $row->grade === null;
                    });
                } else {
                    $filtered = [];
                }
                $cache[$cachekey] = ['filtered' => $filtered, 'total' => count($allrows)];
            } else {
                // Ensure pagination setup.
                $table->query_db($pagesize, false);
            }

            $filtered = $cache[$cachekey]['filtered'];
            $total = $cache[$cachekey]['total'];
            $visible = count($filtered);

            $table->rawdata = $filtered;
            $table->build_table();
            $table->close_recordset();

            $o = '';
            $summary = \local_rolestyles_get_filter_summary($total, $visible);
            $o .= $this->output->notification($summary, 'info');
            $o .= $this->output->box_start('boxaligncenter gradingtable position-relative');
            $this->page->requires->js_init_call('M.mod_assign.init_grading_table', array());
            ob_start();
            $table->finish_output();
            $o .= ob_get_clean();
            $o .= $this->output->box_end();
            return $o;
        }
        return parent::render_assign_grading_table($table);
    }
}
