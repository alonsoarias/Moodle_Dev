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
 * Scheduled task to sync grades to Q10.
 *
 * @package    local_q10grades
 * @copyright  2025 Your Institution
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_q10grades\task;

defined('MOODLE_INTERNAL') || die();

/**
 * Scheduled task class for syncing grades to Q10.
 */
class sync_grades_task extends \core\task\scheduled_task {

    /**
     * Get task name.
     *
     * @return string
     */
    public function get_name(): string {
        return get_string('task_sync_grades', 'local_q10grades');
    }

    /**
     * Execute the task.
     */
    public function execute(): void {
        global $DB;

        // Check if auto sync is enabled.
        if (!get_config('local_q10grades', 'enableautosync')) {
            mtrace('Q10 automatic sync is disabled.');
            return;
        }

        // Check API configuration.
        $apiclient = new \local_q10grades\q10_api_client();
        if (!$apiclient->is_configured()) {
            mtrace('Q10 API is not configured. Skipping sync.');
            return;
        }

        // Get all courses with enabled mappings.
        $mappings = $DB->get_records('local_q10grades_mapping', ['enabled' => 1]);

        if (empty($mappings)) {
            mtrace('No courses configured for Q10 sync.');
            return;
        }

        mtrace('Starting Q10 grade sync for ' . count($mappings) . ' courses...');

        $totalsuccess = 0;
        $totalerrors = 0;

        foreach ($mappings as $mapping) {
            $course = $DB->get_record('course', ['id' => $mapping->courseid]);
            if (!$course) {
                mtrace("  Course ID {$mapping->courseid} not found. Skipping.");
                continue;
            }

            mtrace("  Processing course: {$course->fullname} (ID: {$course->id})");

            try {
                $calculator = new \local_q10grades\formula_calculator($mapping->courseid);
                $formulas = $calculator->get_formulas(true);

                if (empty($formulas)) {
                    mtrace("    No formulas configured. Skipping.");
                    continue;
                }

                $syncmanager = new \local_q10grades\grade_sync_manager($mapping->courseid, $apiclient);
                $usermappingfield = get_config('local_q10grades', 'usermappingfield') ?: 'idnumber';

                foreach ($formulas as $formula) {
                    $calculated = $calculator->calculate_formula_grades($formula);

                    if (empty($calculated)) {
                        mtrace("    Formula '{$formula->name}': No grades to sync.");
                        continue;
                    }

                    $gradestosend = [];

                    foreach ($calculated as $userid => $data) {
                        $student = $data['user'];
                        $q10studentid = $student->$usermappingfield ?? '';

                        if (empty($q10studentid)) {
                            continue;
                        }

                        $gradestosend[] = [
                            'student_id' => $q10studentid,
                            'grade' => $data['calculated_grade'],
                        ];
                    }

                    if (empty($gradestosend)) {
                        mtrace("    Formula '{$formula->name}': No valid student mappings.");
                        continue;
                    }

                    try {
                        $response = $apiclient->upload_grades(
                            $mapping->q10_subject_id,
                            $formula->q10_component_id,
                            $gradestosend,
                            $mapping->q10_period_id
                        );

                        $totalsuccess += count($gradestosend);
                        mtrace("    Formula '{$formula->name}': Synced " . count($gradestosend) . " grades successfully.");

                        // Log success.
                        foreach ($calculated as $userid => $data) {
                            $syncmanager->log_sync_public(
                                $userid,
                                null,
                                $data['calculated_grade'],
                                'success',
                                null,
                                'scheduled',
                                null,
                                json_encode($response)
                            );
                        }
                    } catch (\Exception $e) {
                        $totalerrors += count($gradestosend);
                        mtrace("    Formula '{$formula->name}': Error - " . $e->getMessage());

                        // Log error.
                        foreach ($calculated as $userid => $data) {
                            $syncmanager->log_sync_public(
                                $userid,
                                null,
                                $data['calculated_grade'],
                                'error',
                                $e->getMessage(),
                                'scheduled',
                                null
                            );
                        }
                    }
                }
            } catch (\Exception $e) {
                mtrace("    Error processing course: " . $e->getMessage());
            }
        }

        mtrace("Q10 grade sync completed. Success: {$totalsuccess}, Errors: {$totalerrors}");
    }
}
