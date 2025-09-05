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

require_once(__DIR__ . '/../lib.php');

/**
 * Hook callbacks class
 */
class hook_callbacks {
    
    /**
     * Before HTTP headers hook
     *
     * @param \core\hook\output\before_http_headers $hook
     */
    public static function before_http_headers(\core\hook\output\before_http_headers $hook): void {
        global $PAGE, $USER;
        
        // Check if we're on assignment grading page
        if (!self::is_grading_page()) {
            return;
        }
        
        if (!local_assign_no_submission_filter_user_has_role($PAGE->context)) {
            return;
        }

        // Ensure default filter is used so only "no submission" rows are removed.
        if (get_config('local_assign_no_submission_filter', 'autoapply')) {
            set_user_preference('assign_filter', ASSIGN_FILTER_NONE, $USER);
        }
    }
    
    /**
     * Before standard head HTML generation (NEW HOOK)
     *
     * @param \core\hook\output\before_standard_head_html_generation $hook
     */
    public static function before_standard_head_html(\core\hook\output\before_standard_head_html_generation $hook): void {
        global $PAGE, $CFG;
        
        // Check if we're on assignment grading page
        if (!self::is_grading_page()) {
            return;
        }
        
        if (!local_assign_no_submission_filter_user_has_role($PAGE->context)) {
            return;
        }

        // Apply our grading table override
        self::override_grading_table_class();
        
        // Add CSS for filtering
        $css = '
        <style>
        .path-mod-assign .no-submission-hidden {
            display: none !important;
        }
        #assign-filter-notification {
            padding: 10px;
            margin: 10px 0;
            background: #d1ecf1;
            border: 1px solid #bee5eb;
            color: #0c5460;
            border-radius: 4px;
        }
        </style>';
        
        $hook->add_html($css);
    }
    
    /**
     * Inject filter controls
     *
     * @param \core\hook\output\before_standard_top_of_body_html_generation $hook
     */
    public static function inject_filter_controls(\core\hook\output\before_standard_top_of_body_html_generation $hook): void {
        global $PAGE, $SESSION;
        
        if (!self::is_grading_page()) {
            return;
        }
        
        if (!get_config('local_assign_no_submission_filter', 'enabled')) {
            return;
        }

        if (!local_assign_no_submission_filter_user_has_role($PAGE->context)) {
            return;
        }

        // Add filter control UI
        $html = self::get_filter_controls_html();
        $hook->add_html($html);
    }
    
    /**
     * Check if current page is grading page
     *
     * @return bool
     */
    protected static function is_grading_page() {
        global $PAGE;
        
        if (during_initial_install()) {
            return false;
        }
        
        if ($PAGE->pagetype !== 'mod-assign-view' && $PAGE->pagetype !== 'mod-assign-grading') {
            return false;
        }
        
        $action = optional_param('action', '', PARAM_ALPHA);
        return ($action === 'grading' || $action === 'grader');
    }
    
    /**
     * Override grading table class
     */
    protected static function override_grading_table_class() {
        global $CFG;
        
        if (!get_config('local_assign_no_submission_filter', 'enabled')) {
            return;
        }
        
        // Load our override function
        require_once($CFG->dirroot . '/local/assign_no_submission_filter/lib.php');
        local_assign_no_submission_filter_override_grading_table();
    }
    
    /**
     * Get filter controls HTML
     *
     * @return string
     */
    protected static function get_filter_controls_html() {
        global $USER;
        
        $checked = get_user_preferences('assign_hide_no_submissions', true) ? 'checked' : '';
        
        $html = '
        <div id="assign-filter-controls" style="padding: 10px; background: #f8f9fa; border: 1px solid #dee2e6; margin: 10px 0;">
            <form method="get" action="" style="margin: 0;">
                <input type="hidden" name="id" value="' . optional_param('id', 0, PARAM_INT) . '">
                <input type="hidden" name="action" value="grading">
                <label style="font-weight: bold;">
                    <input type="checkbox" name="hide_no_submission" value="1" ' . $checked . ' 
                           onchange="this.form.submit()" style="margin-right: 5px;">
                    ' . get_string('hidenosubmission', 'local_assign_no_submission_filter') . '
                </label>
            </form>
        </div>';
        
        return $html;
    }
}