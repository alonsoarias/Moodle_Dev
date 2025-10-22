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
 * Renderable representing the knowledge management list.
 *
 * @package     local_educambot
 * @copyright   2024 Educam
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_educambot\output;

use renderable;
use renderer_base;
use templatable;

/**
 * Provides data for the knowledge management template.
 */
class knowledge_table implements renderable, templatable {
    /** @var array */
    protected array $records;

    /** @var array */
    protected array $filters;

    /** @var array */
    protected array $searchinfo;

    /** @var string */
    protected string $paginghtml;

    /**
     * Constructor.
     *
     * @param array $records
     * @param array $filters
     * @param array $searchinfo
     * @param string $paginghtml
     */
    public function __construct(array $records, array $filters, array $searchinfo, string $paginghtml) {
        $this->records = $records;
        $this->filters = $filters;
        $this->searchinfo = $searchinfo;
        $this->paginghtml = $paginghtml;
    }

    /**
     * Export data for mustache template.
     *
     * @param renderer_base $output
     * @return array
     */
    public function export_for_template(renderer_base $output): array {
        return [
            'records' => $this->records,
            'hasrecords' => !empty($this->records),
            'filters' => $this->filters,
            'search' => $this->searchinfo,
            'paging' => $this->paginghtml,
        ];
    }
}
