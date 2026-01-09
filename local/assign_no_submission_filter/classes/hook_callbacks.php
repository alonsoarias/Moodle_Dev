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
 * Hook callbacks for local_assign_no_submission_filter
 *
 * @package    local_assign_no_submission_filter
 * @copyright  2024 Your Organization
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_assign_no_submission_filter;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/local/assign_no_submission_filter/lib.php');

/**
 * Hook callbacks class
 */
class hook_callbacks {
    
    /**
     * Before standard head HTML generation hook
     */
    public static function before_standard_head_html(\core\hook\output\before_standard_head_html_generation $hook): void {
        global $PAGE, $CFG, $USER;
        
        if (!self::should_activate()) {
            return;
        }
        
        // Get the context
        try {
            $context = $PAGE->context;
        } catch (\Exception $e) {
            return;
        }
        
        // Check if current user has one of the selected roles
        if (!local_assign_no_submission_filter_user_has_selected_role($context)) {
            return;
        }
        
        // Override the grading table class for grading pages
        $action = optional_param('action', '', PARAM_ALPHA);
        if (in_array($action, ['grading', 'grader'])) {
            require_once($CFG->dirroot . '/local/assign_no_submission_filter/classes/table_override.php');
        }
        
        // Add CSS for filtering and hiding participant count
        $css = self::get_filter_css();
        $hook->add_html($css);
        
        // Add JavaScript for client-side operations
        $PAGE->requires->js_call_amd('local_assign_no_submission_filter/filter', 'init', [
            'userHasRole' => true
        ]);
    }
    
    /**
     * Check if we should activate the plugin features
     */
    private static function should_activate(): bool {
        global $PAGE;
        
        // Check if plugin is enabled
        if (!get_config('local_assign_no_submission_filter', 'enabled')) {
            return false;
        }
        
        // Check page type
        $validpagetypes = ['mod-assign-view', 'mod-assign-grading'];
        if (!in_array($PAGE->pagetype, $validpagetypes)) {
            return false;
        }
        
        return true;
    }
    
    /**
     * Get filter CSS
     */
    private static function get_filter_css(): string {
        global $PAGE;
        
        // Check context for role-based CSS
        try {
            $context = $PAGE->context;
            $shouldApply = local_assign_no_submission_filter_user_has_selected_role($context);
        } catch (\Exception $e) {
            $shouldApply = false;
        }
        
        if (!$shouldApply) {
            return '';
        }
        
        $css = '
        <style>
        /* Hide rows without submissions in grading table */
        tr.no-submission-hidden {
            display: none !important;
        }
        ';
        
        // Add CSS to hide participant count if enabled
        if (get_config('local_assign_no_submission_filter', 'hide_participant_count')) {
            $css .= '
            /* Hide participant count row in assignment summary table */
            .path-mod-assign .generaltable tr:has(th:contains("Participants")),
            .path-mod-assign .generaltable tr:has(th:contains("Participantes")) {
                display: none !important;
            }

            /* Alternative selector for better compatibility */
            .path-mod-assign .generaltable tr.participant-count-row {
                display: none !important;
            }
            ';
        }

        // Add CSS to hide submitted count if enabled
        if (get_config('local_assign_no_submission_filter', 'hide_submitted_count')) {
            $css .= '
            /* Hide submitted count row in assignment summary table */
            .path-mod-assign .generaltable tr:has(th:contains("Submitted")),
            .path-mod-assign .generaltable tr:has(th:contains("Enviados")),
            .path-mod-assign .generaltable tr:has(th:contains("Enviado")) {
                display: none !important;
            }

            /* Alternative selector for submitted count */
            .path-mod-assign .generaltable tr.submitted-count-row {
                display: none !important;
            }
            ';
        }
        
        $css .= '</style>';
        
        return $css;
    }
}