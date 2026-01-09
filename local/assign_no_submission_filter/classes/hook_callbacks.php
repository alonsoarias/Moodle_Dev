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
 * Hook callbacks for the Assignment No Submission Filter plugin.
 *
 * This class handles Moodle output hooks to inject CSS and JavaScript
 * into assignment pages for filtering functionality.
 *
 * @package    local_assign_no_submission_filter
 * @author     IngeWeb
 * @copyright  2026 IngeWeb para TecnosZubia
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_assign_no_submission_filter;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/local/assign_no_submission_filter/lib.php');

/**
 * Hook callbacks class for assignment filtering.
 *
 * Implements callbacks for Moodle's hook system to inject filtering
 * CSS and JavaScript into assignment pages based on configuration.
 *
 * @package    local_assign_no_submission_filter
 * @author     IngeWeb
 * @copyright  2026 IngeWeb para TecnosZubia
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class hook_callbacks {

    /** @var string Plugin component name for configuration access */
    const COMPONENT = 'local_assign_no_submission_filter';

    /**
     * Callback for the before_standard_head_html_generation hook.
     *
     * Injects CSS and JavaScript for filtering assignment views when
     * the plugin is enabled and the user has an appropriate role.
     *
     * @param \core\hook\output\before_standard_head_html_generation $hook The hook instance.
     * @return void
     */
    public static function before_standard_head_html(\core\hook\output\before_standard_head_html_generation $hook): void {
        global $PAGE, $CFG;

        // Early exit if plugin shouldn't activate on this page.
        if (!self::should_activate()) {
            return;
        }

        // Get the context safely.
        try {
            $context = $PAGE->context;
        } catch (\Exception $e) {
            return;
        }

        // Check if current user has one of the selected roles.
        if (!local_assign_no_submission_filter_user_has_selected_role($context)) {
            return;
        }

        // Load filtered grading table class for grading pages.
        $action = optional_param('action', '', PARAM_ALPHA);
        if (in_array($action, ['grading', 'grader'])) {
            require_once($CFG->dirroot . '/local/assign_no_submission_filter/classes/table_override.php');
        }

        // Inject CSS for hiding elements.
        $css = self::build_filter_css();
        if (!empty($css)) {
            $hook->add_html($css);
        }

        // Load JavaScript module with configuration.
        self::load_javascript_module();
    }

    /**
     * Check if the plugin should activate on the current page.
     *
     * Verifies that the plugin is enabled and we're on a valid
     * assignment page type.
     *
     * @return bool True if plugin should activate.
     */
    private static function should_activate(): bool {
        global $PAGE;

        // Check if plugin is enabled.
        if (!get_config(self::COMPONENT, 'enabled')) {
            return false;
        }

        // Only activate on assignment pages.
        $validpagetypes = [
            'mod-assign-view',
            'mod-assign-grading',
        ];

        return in_array($PAGE->pagetype, $validpagetypes);
    }

    /**
     * Build CSS rules for filtering and hiding elements.
     *
     * Generates CSS based on configuration settings using standard
     * CSS selectors with classes added by JavaScript.
     *
     * @return string CSS wrapped in style tags, or empty string if not applicable.
     */
    private static function build_filter_css(): string {
        global $PAGE;

        // Verify role-based access.
        try {
            $context = $PAGE->context;
            $shouldapply = local_assign_no_submission_filter_user_has_selected_role($context);
        } catch (\Exception $e) {
            return '';
        }

        if (!$shouldapply) {
            return '';
        }

        $cssrules = [];

        // Base rule for hiding rows without submissions (applied by JavaScript).
        $cssrules[] = '
            /* Hide rows marked as no-submission by JavaScript */
            tr.no-submission-hidden {
                display: none !important;
            }';

        // Hide participant count row if enabled.
        if (get_config(self::COMPONENT, 'hide_participant_count')) {
            $cssrules[] = '
            /* Hide participant count row (class added by JavaScript) */
            .path-mod-assign .generaltable tr.participant-count-row {
                display: none !important;
            }';
        }

        // Hide submitted count row if enabled.
        if (get_config(self::COMPONENT, 'hide_submitted_count')) {
            $cssrules[] = '
            /* Hide submitted count row (class added by JavaScript) */
            .path-mod-assign .generaltable tr.submitted-count-row {
                display: none !important;
            }';
        }

        if (empty($cssrules)) {
            return '';
        }

        return '<style>' . implode("\n", $cssrules) . "\n</style>";
    }

    /**
     * Load the JavaScript module with current configuration.
     *
     * Calls the AMD module with configuration options for hiding
     * participant and submitted counts.
     *
     * @return void
     */
    private static function load_javascript_module(): void {
        global $PAGE;

        $config = [
            'userHasRole' => true,
            'hideParticipantCount' => (bool)get_config(self::COMPONENT, 'hide_participant_count'),
            'hideSubmittedCount' => (bool)get_config(self::COMPONENT, 'hide_submitted_count'),
        ];

        $PAGE->requires->js_call_amd(
            'local_assign_no_submission_filter/filter',
            'init',
            [$config]
        );
    }
}
