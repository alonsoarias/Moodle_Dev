<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

namespace local_rolestyles\output;

use assign_grading_table;
use mod_assign\output\renderer as base_renderer;

/**
 * Custom renderer for mod_assign that filters out students without submissions.
 *
 * @package    local_rolestyles
 */
class assign_renderer extends base_renderer {
    /**
     * Render the grading table, removing users without submissions when active.
     *
     * @param assign_grading_table $table
     * @return string
     */
    public function render_assign_grading_table(assign_grading_table $table) {
        if (\local_rolestyles\assign_filter::is_active()) {
            $table->setup();
            $table->query_db($table->get_rows_per_page(), false);
            [$rows, $total] = \local_rolestyles\assign_filter::apply($table);
            $table->rawdata = $rows;

            ob_start();
            $table->build_table();
            $table->close_recordset();
            $table->finish_output();
            $html = ob_get_clean();

            $summary = \local_rolestyles\assign_filter::summary($total, count($rows));
            $out = $this->output->notification($summary, 'info');
            $out .= $this->output->box_start('boxaligncenter gradingtable position-relative');
            $this->page->requires->js_init_call('M.mod_assign.init_grading_table', []);
            $out .= $html;
            $out .= $this->output->box_end();
            return $out;
        }
        return parent::render_assign_grading_table($table);
    }
}
