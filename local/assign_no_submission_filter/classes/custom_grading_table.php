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
 * Custom grading table that filters out students without submissions
 *
 * @package    local_assign_no_submission_filter
 * @copyright  2024 Your Organization
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_assign_no_submission_filter;

defined('MOODLE_INTERNAL') || die();

if (class_exists('\assign_grading_table')) {
    /**
     * Extended grading table with submission filtering.
     */
    class custom_grading_table extends \assign_grading_table {
        /**
         * Constructor - apply filtering configuration and inject SQL restriction.
         *
         * @param \assign $assignment Assignment instance
         * @param int $perpage Rows per page
         * @param int $filter Current filter
         * @param int $rowoffset Row offset
         * @param bool $quickgrading Quick grading flag
         * @param string|null $downloadfilename Download filename
         */
        public function __construct($assignment, $perpage, $filter, $rowoffset, $quickgrading, $downloadfilename = null) {
            if (get_config('local_assign_no_submission_filter', 'autoapply')) {
                $filter = ASSIGN_FILTER_NONE;
            }

            parent::__construct($assignment, $perpage, $filter, $rowoffset, $quickgrading, $downloadfilename);

            if (get_config('local_assign_no_submission_filter', 'enabled')) {
                $this->add_submission_filter();
            }
        }

        /**
         * Restrict results to users who have submitted at least once.
         */
        protected function add_submission_filter() {
            global $DB;

            $assignid = $this->assignment->get_instance()->id;
            $submitted = \local_assign_no_submission_filter_get_submitted_users($assignid);
            if (empty($submitted)) {
                $submitted = [-1];
            }

            list($insql, $inparams) = $DB->get_in_or_equal($submitted, SQL_PARAMS_NAMED, 'nosub');

            if (empty($this->sql->where)) {
                $this->sql->where = 'u.id ' . $insql;
            } else {
                $this->sql->where = 'u.id ' . $insql . ' AND ' . $this->sql->where;
            }

            if (!isset($this->sql->params)) {
                $this->sql->params = [];
            }
            $this->sql->params = array_merge($this->sql->params, $inparams);
        }
    }
} else {
    /**
     * Fallback when parent class is unavailable.
     */
    class custom_grading_table {
        public function __construct() {
            debugging('Parent class assign_grading_table not available', DEBUG_DEVELOPER);
        }
    }
}
