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
 * External API for the download center plugin.
 *
 * @package    local_downloadcenter
 * @copyright  2025 Academic Moodle Cooperation
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_downloadcenter;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/externallib.php');

use context_system;
use external_api;
use external_function_parameters;
use external_single_structure;
use external_value;
use local_downloadcenter\output\admin_tree_renderer;

/**
 * External API entry points used by AMD modules.
 */
class external extends external_api {
    /**
     * Describe parameters for get_category_children.
     *
     * @return external_function_parameters
     */
    public static function get_category_children_parameters(): external_function_parameters {
        return new external_function_parameters([
            'categoryid' => new external_value(PARAM_INT, 'Category ID', VALUE_REQUIRED),
        ]);
    }

    /**
     * Return rendered HTML for the requested category children.
     *
     * @param int $categoryid Category id
     * @return array
     */
    public static function get_category_children(int $categoryid): array {
        global $USER;

        $params = self::validate_parameters(self::get_category_children_parameters(), [
            'categoryid' => $categoryid,
        ]);

        require_login();
        $context = context_system::instance();
        self::validate_context($context);
        require_capability('local/downloadcenter:downloadmultiple', $context);

        $selectionmanager = new selection_manager($USER->id);
        $allowrestricted = has_capability('local/downloadcenter:downloadmultiple', $context);
        $renderer = new admin_tree_renderer($selectionmanager, $allowrestricted);
        $html = $renderer->render_category_children($params['categoryid']);

        return [
            'html' => $html,
            'selectioncount' => count($selectionmanager->get_course_selections()),
        ];
    }

    /**
     * Describe return value for get_category_children.
     *
     * @return external_single_structure
     */
    public static function get_category_children_returns(): external_single_structure {
        return new external_single_structure([
            'html' => new external_value(PARAM_RAW, 'Rendered HTML for the category children'),
            'selectioncount' => new external_value(PARAM_INT, 'Number of selected courses'),
        ]);
    }

    /**
     * Parameters for get_course_resources.
     *
     * @return external_function_parameters
     */
    public static function get_course_resources_parameters(): external_function_parameters {
        return new external_function_parameters([
            'courseid' => new external_value(PARAM_INT, 'Course ID'),
        ]);
    }

    /**
     * Return rendered HTML for a course resources block.
     *
     * @param int $courseid Course ID
     * @return array
     */
    public static function get_course_resources(int $courseid): array {
        global $DB, $USER;

        $params = self::validate_parameters(self::get_course_resources_parameters(), [
            'courseid' => $courseid,
        ]);

        require_login();
        $context = context_system::instance();
        self::validate_context($context);
        require_capability('local/downloadcenter:downloadmultiple', $context);

        $course = $DB->get_record('course', ['id' => $params['courseid']]);
        if (!$course) {
            throw new \invalid_parameter_exception('Invalid course');
        }

        $allowrestricted = has_capability('local/downloadcenter:downloadmultiple', $context);
        if (!$allowrestricted && !can_access_course($course)) {
            throw new \required_capability_exception(context_system::instance(),
                'local/downloadcenter:downloadmultiple', 'nopermissions', '');
        }

        $selectionmanager = new selection_manager($USER->id);
        $factory = new factory($course, $USER);
        $factory->set_download_options($selectionmanager->get_download_options());
        $renderer = new admin_tree_renderer($selectionmanager, $allowrestricted);
        $selection = $selectionmanager->get_course_selection($course->id);
        $html = $renderer->render_course_resources($factory, $selection);

        return [
            'html' => $html,
            'selection' => json_encode($selection),
        ];
    }

    /**
     * Return structure for get_course_resources.
     *
     * @return external_single_structure
     */
    public static function get_course_resources_returns(): external_single_structure {
        return new external_single_structure([
            'html' => new external_value(PARAM_RAW, 'Rendered HTML for the course resources'),
            'selection' => new external_value(PARAM_RAW, 'Current selection state as JSON'),
        ]);
    }

    /**
     * Parameters for set_course_selection.
     *
     * @return external_function_parameters
     */
    public static function set_course_selection_parameters(): external_function_parameters {
        return new external_function_parameters([
            'courseid' => new external_value(PARAM_INT, 'Course ID'),
            'selection' => new external_value(PARAM_RAW, 'JSON encoded selection for the course'),
        ]);
    }

    /**
     * Persist course selection for the current user.
     *
     * @param int $courseid Course id
     * @param string $selection JSON selection payload
     * @return array
     */
    public static function set_course_selection(int $courseid, string $selection): array {
        global $USER;

        $params = self::validate_parameters(self::set_course_selection_parameters(), [
            'courseid' => $courseid,
            'selection' => $selection,
        ]);

        require_login();
        $context = context_system::instance();
        self::validate_context($context);
        require_capability('local/downloadcenter:downloadmultiple', $context);

        $selectionmanager = new selection_manager($USER->id);
        $decoded = json_decode($params['selection'], true) ?: [];
        $selectionmanager->set_course_selection($params['courseid'], $decoded);

        return [
            'selectioncount' => count($selectionmanager->get_course_selections()),
        ];
    }

    /**
     * Returns description for set_course_selection.
     *
     * @return external_single_structure
     */
    public static function set_course_selection_returns(): external_single_structure {
        return new external_single_structure([
            'selectioncount' => new external_value(PARAM_INT, 'Number of selected courses'),
        ]);
    }

    /**
     * Parameters for set_download_options.
     *
     * @return external_function_parameters
     */
    public static function set_download_options_parameters(): external_function_parameters {
        return new external_function_parameters([
            'options' => new external_value(PARAM_RAW, 'JSON encoded options'),
        ]);
    }

    /**
     * Persist download options for the current user.
     *
     * @param string $options JSON options payload
     * @return array
     */
    public static function set_download_options(string $options): array {
        global $USER;

        $params = self::validate_parameters(self::set_download_options_parameters(), [
            'options' => $options,
        ]);

        require_login();
        $context = context_system::instance();
        self::validate_context($context);
        require_capability('local/downloadcenter:downloadmultiple', $context);

        $selectionmanager = new selection_manager($USER->id);
        $decoded = json_decode($params['options'], true) ?: [];

        $filtered = [
            'excludestudent' => !empty($decoded['excludestudent']) ? 1 : 0,
            'filesrealnames' => !empty($decoded['filesrealnames']) ? 1 : 0,
            'addnumbering' => !empty($decoded['addnumbering']) ? 1 : 0,
        ];
        $selectionmanager->set_download_options($filtered);

        return $filtered;
    }

    /**
     * Return structure for set_download_options.
     *
     * @return external_single_structure
     */
    public static function set_download_options_returns(): external_single_structure {
        return new external_single_structure([
            'excludestudent' => new external_value(PARAM_BOOL, 'Exclude student content'),
            'filesrealnames' => new external_value(PARAM_BOOL, 'Download files with real names'),
            'addnumbering' => new external_value(PARAM_BOOL, 'Add numbering to files'),
        ]);
    }
}
