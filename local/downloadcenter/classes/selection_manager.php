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
 * Selection manager for persistent course selection
 *
 * @package    local_downloadcenter
 * @copyright  2025 Alonso Arias <soporte@ingeweb.co>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_downloadcenter;

defined('MOODLE_INTERNAL') || die();

/**
 * Selection manager class for handling user selections.
 *
 * @package    local_downloadcenter
 * @copyright  2025 Alonso Arias <soporte@ingeweb.co>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class selection_manager {

    /** Prefix used to mark compressed preference values. */
    private const COMPRESSED_PREFIX = 'gz:';

    /** Maximum number of characters accepted by the user preference value column. */
    private const PREFERENCE_MAX_LENGTH = 1333;
    
    /** @var int User ID */
    protected $userid;
    
    /** @var array Selection data */
    protected $selection;
    
    /** @var array Download options */
    protected $options;

    /** @var array<int, int[]> Cached course category paths */
    protected $coursecategories = [];
    
    /**
     * Constructor.
     *
     * @param int $userid User ID
     */
    public function __construct($userid) {
        $this->userid = $userid;
        $this->load_selection();
        $this->load_options();
    }
    
    /**
     * Load user selection from preferences.
     *
     * @return void
     */
    protected function load_selection() {
        $pref = get_user_preferences('downloadcenter_selection', '{}', $this->userid);
        $this->selection = $this->decode_preference_value($pref);
        $this->selection = array_filter($this->selection, function($value) {
            return is_array($value);
        });
        $this->build_course_categories_cache();
    }
    
    /**
     * Load download options from preferences.
     *
     * @return void
     */
    protected function load_options() {
        $pref = get_user_preferences('downloadcenter_options', '{}', $this->userid);
        $defaults = [
            'excludestudent' => (int)(get_config('local_downloadcenter', 'excludestudentdefault') ?? 0),
            'includefiles' => 1,
            'filesrealnames' => 0,
            'addnumbering' => 0,
        ];

        $options = $this->decode_preference_value($pref);
        $options = is_array($options) ? $options : [];
        $this->options = array_merge($defaults, array_intersect_key($options, $defaults));
    }
    
    /**
     * Save selection to preferences.
     *
     * @return void
     */
    protected function save_selection() {
        $this->persist_preference('downloadcenter_selection', $this->selection);
    }
    
    /**
     * Save download options to preferences.
     *
     * @return void
     */
    protected function save_options() {
        $this->persist_preference('downloadcenter_options', $this->options);
    }
    
    /**
     * Add course to selection.
     *
     * @param int $courseid Course ID
     * @param array $resources Resources selection (optional)
     * @return void
     */
    public function add_course($courseid, $resources = []) {
        if (empty($resources)) {
            $resources = ['__fullcourse' => 1];
        }
        $this->set_course_selection($courseid, $resources);
    }
    
    /**
     * Remove course from selection.
     *
     * @param int $courseid Course ID
     * @return void
     */
    public function remove_course($courseid) {
        unset($this->selection[$courseid]);
        unset($this->coursecategories[$courseid]);
        $this->save_selection();
    }
    
    /**
     * Get selected course IDs.
     *
     * @return array Array of course IDs
     */
    public function get_selected_courses() {
        return array_keys($this->selection);
    }
    
    /**
     * Get selection for specific course.
     *
     * @param int $courseid Course ID
     * @return array Course selection data
     */
    public function get_course_selection($courseid) {
        return $this->selection[$courseid] ?? [];
    }
    
    /**
     * Get all course selections.
     *
     * @return array All selections
     */
    public function get_course_selections() {
        return $this->selection;
    }
    
    /**
     * Set download options.
     *
     * @param array $options Options array
     * @return void
     */
    public function set_download_options($options) {
        $this->options = array_merge($this->options, $options);
        $this->save_options();
    }
    
    /**
     * Get download options.
     *
     * @return array Download options
     */
    public function get_download_options() {
        return $this->options;
    }
    
    /**
     * Clear all selections.
     *
     * @return void
     */
    public function clear_selection() {
        $this->selection = [];
        $this->coursecategories = [];
        $this->save_selection();
    }
    
    /**
     * Count selected courses.
     *
     * @return int Number of selected courses
     */
    public function count_selected() {
        return count($this->selection);
    }
    
    /**
     * Check if course is selected.
     *
     * @param int $courseid Course ID
     * @return bool True if selected
     */
    public function is_course_selected($courseid) {
        return isset($this->selection[$courseid]);
    }
    
    /**
     * Toggle course selection.
     *
     * @param int $courseid Course ID
     * @return bool New selection state
     */
    public function toggle_course($courseid) {
        if ($this->is_course_selected($courseid)) {
            $this->remove_course($courseid);
            return false;
        }

        $this->set_course_selection($courseid, ['__fullcourse' => 1]);
        return true;
    }
    
    /**
     * Update course resources selection.
     *
     * @param int $courseid Course ID
     * @param array $resources Resources selection
     * @return void
     */
    public function update_course_resources($courseid, $resources) {
        if (empty($resources)) {
            $this->remove_course($courseid);
            return;
        }

        $this->set_course_selection($courseid, $resources);
    }

    /**
     * Replace the current selection for the course.
     *
     * @param int $courseid Course id
     * @param array $selection Selection data (form compatible)
     * @return void
     */
    public function set_course_selection($courseid, array $selection): void {
        $courseid = (int)$courseid;
        if ($courseid <= 0) {
            return;
        }

        $filtered = [];
        foreach ($selection as $key => $value) {
            if ($value === null || $value === '' || $value === false) {
                continue;
            }
            if ($key === '__fullcourse' || preg_match('/^item_[a-z0-9]+_\d+$/i', $key)) {
                $filtered[$key] = 1;
            } else if (preg_match('/^item_topic_\d+$/', $key)) {
                $filtered[$key] = 1;
            }
        }

        if (empty($filtered)) {
            $this->remove_course($courseid);
            return;
        }

        $this->selection[$courseid] = $filtered;
        $this->coursecategories[$courseid] = $this->resolve_course_categories($courseid);
        $this->save_selection();
    }

    /**
     * Determine if the course has a partial selection.
     *
     * @param int $courseid Course id
     * @return bool
     */
    public function course_has_partial_selection(int $courseid): bool {
        if (!$this->is_course_selected($courseid)) {
            return false;
        }

        $selection = $this->selection[$courseid];
        return empty($selection['__fullcourse']);
    }

    /**
     * Calculate the category state based on selected courses.
     *
     * @param int $categoryid Category id
     * @return array{checked:bool,indeterminate:bool,selected:bool}
     */
    public function get_course_selections_for_category(int $categoryid): array {
        $categoryid = (int)$categoryid;
        $hasselection = false;
        $allfull = true;

        foreach ($this->selection as $courseid => $selection) {
            $categories = $this->get_course_categories($courseid);
            if (!in_array($categoryid, $categories, true)) {
                continue;
            }

            $hasselection = true;
            if (empty($selection['__fullcourse'])) {
                $allfull = false;
                break;
            }
        }

        if (!$hasselection) {
            return ['checked' => false, 'indeterminate' => false, 'selected' => false];
        }

        return [
            'checked' => $allfull,
            'indeterminate' => !$allfull,
            'selected' => true,
        ];
    }

    /**
     * Retrieve the cached course category path.
     *
     * @param int $courseid Course id
     * @return int[]
     */
    protected function get_course_categories(int $courseid): array {
        if (!isset($this->coursecategories[$courseid])) {
            $this->coursecategories[$courseid] = $this->resolve_course_categories($courseid);
        }
        return $this->coursecategories[$courseid];
    }

    /**
     * Build cache for selected courses.
     *
     * @return void
     */
    protected function build_course_categories_cache(): void {
        $this->coursecategories = [];
        foreach (array_keys($this->selection) as $courseid) {
            $this->coursecategories[$courseid] = $this->resolve_course_categories((int)$courseid);
        }
    }

    /**
     * Resolve all categories the course belongs to.
     *
     * @param int $courseid Course id
     * @return int[]
     */
    protected function resolve_course_categories(int $courseid): array {
        global $DB;

        $course = $DB->get_record('course', ['id' => $courseid], 'id, category');
        if (!$course) {
            return [];
        }

        $categoryids = [];
        if (!empty($course->category)) {
            try {
                $category = \core_course_category::get($course->category, \IGNORE_MISSING, true);
            } catch (\moodle_exception $e) {
                $category = null;
            }

            if ($category) {
                $categoryids = $category->get_parents();
                $categoryids[] = $category->id;
            }
        }

        return array_map('intval', array_filter($categoryids));
    }

    /**
     * Persist a preference array while respecting the user preference length limit.
     *
     * @param string $name Preference name
     * @param array $value Preference value to store
     * @return void
     */
    protected function persist_preference(string $name, array $value): void {
        $encoded = $this->encode_preference_value($value);
        if ($encoded === null) {
            unset_user_preference($name, $this->userid);
            return;
        }

        set_user_preference($name, $encoded, $this->userid);
    }

    /**
     * Encode a preference array for storage, compressing when necessary.
     *
     * @param array $value Value to encode
     * @return string|null Encoded preference value or null when it can not be persisted
     */
    protected function encode_preference_value(array $value): ?string {
        $json = json_encode($value);
        if ($json === false) {
            debugging('local_downloadcenter: failed to encode preference value as JSON', DEBUG_DEVELOPER);
            return null;
        }

        if (\core_text::strlen($json) <= self::PREFERENCE_MAX_LENGTH) {
            return $json;
        }

        if (!function_exists('gzcompress')) {
            debugging('local_downloadcenter: preference value exceeds storage limit and compression is unavailable',
                DEBUG_DEVELOPER);
            return null;
        }

        $compressed = gzcompress($json, 6);
        if ($compressed === false) {
            debugging('local_downloadcenter: failed to compress oversized preference value', DEBUG_DEVELOPER);
            return null;
        }

        $encoded = self::COMPRESSED_PREFIX . base64_encode($compressed);
        if (\core_text::strlen($encoded) <= self::PREFERENCE_MAX_LENGTH) {
            return $encoded;
        }

        debugging('local_downloadcenter: preference value exceeds storage limit even after compression',
            DEBUG_DEVELOPER);
        return null;
    }

    /**
     * Decode a preference that may have been stored in compressed form.
     *
     * @param string $value Stored preference value
     * @return array Decoded preference array
     */
    protected function decode_preference_value(string $value): array {
        if (strpos($value, self::COMPRESSED_PREFIX) === 0) {
            $encoded = substr($value, strlen(self::COMPRESSED_PREFIX));
            $compressed = base64_decode($encoded, true);
            if ($compressed !== false && function_exists('gzuncompress')) {
                $json = @gzuncompress($compressed);
                if ($json !== false) {
                    $value = $json;
                }
            } else if ($compressed !== false) {
                debugging('local_downloadcenter: unable to decompress stored preference value', DEBUG_DEVELOPER);
            }
        }

        $decoded = json_decode($value, true);
        return is_array($decoded) ? $decoded : [];
    }
}
