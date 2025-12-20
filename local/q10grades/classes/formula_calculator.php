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
 * Formula calculator for combining grades from multiple activities.
 *
 * @package    local_q10grades
 * @copyright  2025 Your Institution
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_q10grades;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/gradelib.php');

use grade_item;
use grade_grade;
use stdClass;

/**
 * Class to calculate grades based on configured formulas.
 */
class formula_calculator {

    /** @var int Course ID */
    private $courseid;

    /** @var array Grade items cache */
    private $gradeitems = [];

    /** @var string Grade scale */
    private $gradescale;

    /**
     * Constructor.
     *
     * @param int $courseid Course ID.
     */
    public function __construct(int $courseid) {
        $this->courseid = $courseid;
        $this->gradescale = get_config('local_q10grades', 'gradescale') ?: 'percentage';
        $this->load_grade_items();
    }

    /**
     * Load grade items for the course.
     */
    private function load_grade_items(): void {
        $items = grade_item::fetch_all(['courseid' => $this->courseid]);
        if ($items) {
            foreach ($items as $item) {
                $this->gradeitems[$item->id] = $item;
            }
        }
    }

    /**
     * Get all formulas for the course.
     *
     * @param bool $enabledonly Only return enabled formulas.
     * @return array Array of formula records.
     */
    public function get_formulas(bool $enabledonly = true): array {
        global $DB;

        $conditions = ['courseid' => $this->courseid];
        if ($enabledonly) {
            $conditions['enabled'] = 1;
        }

        return $DB->get_records('local_q10grades_formula', $conditions, 'sortorder');
    }

    /**
     * Get a single formula.
     *
     * @param int $formulaid Formula ID.
     * @return stdClass|null Formula record or null.
     */
    public function get_formula(int $formulaid): ?stdClass {
        global $DB;
        return $DB->get_record('local_q10grades_formula', ['id' => $formulaid, 'courseid' => $this->courseid]) ?: null;
    }

    /**
     * Calculate grades for all students based on a formula.
     *
     * @param stdClass $formula Formula record.
     * @return array Array of calculated grades indexed by user ID.
     */
    public function calculate_formula_grades(stdClass $formula): array {
        global $DB;

        $selecteditems = json_decode($formula->selected_items, true) ?? [];
        $weights = json_decode($formula->weights, true) ?? [];

        if (empty($selecteditems)) {
            return [];
        }

        // Get enrolled students.
        $context = \context_course::instance($this->courseid);
        $students = get_enrolled_users($context, '', 0, 'u.*', null, 0, 0, true);

        $results = [];

        foreach ($students as $student) {
            $usergrades = [];

            // Collect grades for selected items.
            foreach ($selecteditems as $itemid) {
                if (!isset($this->gradeitems[$itemid])) {
                    continue;
                }

                $gradeitem = $this->gradeitems[$itemid];
                $gradegrade = new grade_grade(['itemid' => $itemid, 'userid' => $student->id]);

                if ($gradegrade->finalgrade !== null) {
                    // Normalize grade to percentage.
                    $normalized = $gradeitem->grademax > 0 ?
                        ($gradegrade->finalgrade / $gradeitem->grademax) * 100 : 0;

                    $usergrades[$itemid] = [
                        'raw' => $gradegrade->finalgrade,
                        'normalized' => $normalized,
                        'weight' => $weights[$itemid] ?? 1,
                        'itemname' => $gradeitem->itemname,
                    ];
                }
            }

            if (empty($usergrades)) {
                continue;
            }

            // Calculate based on formula type.
            $calculatedgrade = $this->apply_formula($formula->formula_type, $usergrades, $formula->custom_formula ?? '');

            if ($calculatedgrade !== null) {
                $results[$student->id] = [
                    'userid' => $student->id,
                    'user' => $student,
                    'calculated_grade' => round($calculatedgrade, 2),
                    'component_grades' => $usergrades,
                    'formula_name' => $formula->name,
                    'q10_component_id' => $formula->q10_component_id,
                ];
            }
        }

        return $results;
    }

    /**
     * Apply formula calculation.
     *
     * @param string $formulatype Formula type.
     * @param array $grades Array of grades with 'normalized' and 'weight' keys.
     * @param string $customformula Custom formula expression (for 'custom' type).
     * @return float|null Calculated grade or null.
     */
    private function apply_formula(string $formulatype, array $grades, string $customformula = ''): ?float {
        if (empty($grades)) {
            return null;
        }

        switch ($formulatype) {
            case 'average':
                return $this->calculate_average($grades);

            case 'weighted':
                return $this->calculate_weighted_average($grades);

            case 'sum':
                return $this->calculate_sum($grades);

            case 'highest':
                return $this->calculate_highest($grades);

            case 'lowest':
                return $this->calculate_lowest($grades);

            case 'custom':
                return $this->calculate_custom($grades, $customformula);

            default:
                return $this->calculate_average($grades);
        }
    }

    /**
     * Calculate simple average.
     *
     * @param array $grades Grades array.
     * @return float Average.
     */
    private function calculate_average(array $grades): float {
        $sum = 0;
        $count = 0;

        foreach ($grades as $grade) {
            $sum += $grade['normalized'];
            $count++;
        }

        return $count > 0 ? $sum / $count : 0;
    }

    /**
     * Calculate weighted average.
     *
     * @param array $grades Grades array with weights.
     * @return float Weighted average.
     */
    private function calculate_weighted_average(array $grades): float {
        $weightedsum = 0;
        $totalweight = 0;

        foreach ($grades as $grade) {
            $weight = $grade['weight'] ?? 1;
            $weightedsum += $grade['normalized'] * $weight;
            $totalweight += $weight;
        }

        return $totalweight > 0 ? $weightedsum / $totalweight : 0;
    }

    /**
     * Calculate sum of grades.
     *
     * @param array $grades Grades array.
     * @return float Sum (capped at 100).
     */
    private function calculate_sum(array $grades): float {
        $sum = 0;

        foreach ($grades as $grade) {
            $sum += $grade['normalized'];
        }

        return min($sum, 100); // Cap at 100%.
    }

    /**
     * Get highest grade.
     *
     * @param array $grades Grades array.
     * @return float Highest grade.
     */
    private function calculate_highest(array $grades): float {
        $highest = 0;

        foreach ($grades as $grade) {
            if ($grade['normalized'] > $highest) {
                $highest = $grade['normalized'];
            }
        }

        return $highest;
    }

    /**
     * Get lowest grade.
     *
     * @param array $grades Grades array.
     * @return float Lowest grade.
     */
    private function calculate_lowest(array $grades): float {
        $lowest = 100;

        foreach ($grades as $grade) {
            if ($grade['normalized'] < $lowest) {
                $lowest = $grade['normalized'];
            }
        }

        return $lowest;
    }

    /**
     * Calculate custom formula.
     *
     * Supports simple mathematical expressions with variables like:
     * - [[item_123]] for grade item 123
     * - Basic operators: + - * / ()
     *
     * @param array $grades Grades array.
     * @param string $formula Custom formula expression.
     * @return float|null Calculated result or null on error.
     */
    private function calculate_custom(array $grades, string $formula): ?float {
        if (empty($formula)) {
            return $this->calculate_average($grades);
        }

        // Replace item placeholders with values.
        $expression = $formula;

        foreach ($grades as $itemid => $grade) {
            $placeholder = '[[item_' . $itemid . ']]';
            $expression = str_replace($placeholder, $grade['normalized'], $expression);
        }

        // Also support named placeholders.
        $i = 1;
        foreach ($grades as $grade) {
            $expression = str_replace('[[grade' . $i . ']]', $grade['normalized'], $expression);
            $i++;
        }

        // Sanitize expression - only allow numbers, operators, and parentheses.
        $sanitized = preg_replace('/[^0-9.+\-*\/()% ]/', '', $expression);

        if ($sanitized !== $expression) {
            debugging('Custom formula contains invalid characters: ' . $formula, DEBUG_DEVELOPER);
            return $this->calculate_average($grades);
        }

        // Evaluate expression safely.
        try {
            // Use a simple expression evaluator.
            $result = $this->evaluate_expression($sanitized);
            return max(0, min(100, $result)); // Clamp to 0-100.
        } catch (\Exception $e) {
            debugging('Error evaluating custom formula: ' . $e->getMessage(), DEBUG_DEVELOPER);
            return $this->calculate_average($grades);
        }
    }

    /**
     * Simple and safe expression evaluator.
     *
     * @param string $expression Mathematical expression.
     * @return float Result.
     */
    private function evaluate_expression(string $expression): float {
        // Remove spaces.
        $expression = str_replace(' ', '', $expression);

        // Handle parentheses recursively.
        while (preg_match('/\(([^()]+)\)/', $expression, $matches)) {
            $inner = $this->evaluate_expression($matches[1]);
            $expression = str_replace($matches[0], $inner, $expression);
        }

        // Handle multiplication and division first.
        while (preg_match('/(-?\d+\.?\d*)\s*([*\/])\s*(-?\d+\.?\d*)/', $expression, $matches)) {
            $left = (float) $matches[1];
            $operator = $matches[2];
            $right = (float) $matches[3];

            if ($operator === '*') {
                $result = $left * $right;
            } else {
                $result = $right != 0 ? $left / $right : 0;
            }

            $expression = str_replace($matches[0], $result, $expression);
        }

        // Handle addition and subtraction.
        while (preg_match('/(-?\d+\.?\d*)\s*([+\-])\s*(-?\d+\.?\d*)/', $expression, $matches)) {
            $left = (float) $matches[1];
            $operator = $matches[2];
            $right = (float) $matches[3];

            if ($operator === '+') {
                $result = $left + $right;
            } else {
                $result = $left - $right;
            }

            $expression = str_replace($matches[0], $result, $expression);
        }

        return (float) $expression;
    }

    /**
     * Get all calculated grades for the course.
     *
     * @return array Array of grades grouped by formula.
     */
    public function get_all_calculated_grades(): array {
        $formulas = $this->get_formulas(true);
        $allgrades = [];

        foreach ($formulas as $formula) {
            $allgrades[$formula->id] = [
                'formula' => $formula,
                'grades' => $this->calculate_formula_grades($formula),
            ];
        }

        return $allgrades;
    }

    /**
     * Get preview data for all formulas and students.
     *
     * @return array Preview data array.
     */
    public function get_preview_data(): array {
        $formulas = $this->get_formulas(true);
        $context = \context_course::instance($this->courseid);
        $students = get_enrolled_users($context, '', 0, 'u.id, u.firstname, u.lastname, u.email, u.idnumber', 'u.lastname, u.firstname');

        $usermappingfield = get_config('local_q10grades', 'usermappingfield') ?: 'idnumber';

        $preview = [
            'formulas' => [],
            'students' => [],
            'grades' => [],
        ];

        // Prepare formula info.
        foreach ($formulas as $formula) {
            $selecteditems = json_decode($formula->selected_items, true) ?? [];
            $itemnames = [];
            foreach ($selecteditems as $itemid) {
                if (isset($this->gradeitems[$itemid])) {
                    $itemnames[] = $this->gradeitems[$itemid]->itemname ?: get_string('unnamed', 'local_q10grades');
                }
            }

            $preview['formulas'][$formula->id] = [
                'id' => $formula->id,
                'name' => $formula->name,
                'q10_component_id' => $formula->q10_component_id,
                'formula_type' => $formula->formula_type,
                'items' => $itemnames,
            ];
        }

        // Prepare student info.
        foreach ($students as $student) {
            $preview['students'][$student->id] = [
                'id' => $student->id,
                'fullname' => fullname($student),
                'email' => $student->email,
                'idnumber' => $student->idnumber,
                'q10_id' => $student->$usermappingfield ?? '',
            ];
        }

        // Calculate grades for each formula.
        foreach ($formulas as $formula) {
            $calculated = $this->calculate_formula_grades($formula);
            foreach ($calculated as $userid => $data) {
                if (!isset($preview['grades'][$userid])) {
                    $preview['grades'][$userid] = [];
                }
                $preview['grades'][$userid][$formula->id] = $data['calculated_grade'];
            }
        }

        return $preview;
    }
}
