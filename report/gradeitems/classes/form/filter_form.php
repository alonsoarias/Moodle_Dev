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
 * Filter form for the Grade Items Report.
 *
 * @package    report_gradeitems
 * @copyright  2026 Alonso Arias <soporte@orioncloud.com.co>
 * @author     Alonso Arias <soporte@orioncloud.com.co>
 * @link       https://orioncloud.com.co
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace report_gradeitems\form;

defined('MOODLE_INTERNAL') || die;

require_once($CFG->libdir . '/formslib.php');

/**
 * Filter form class.
 */
class filter_form extends \moodleform {

    /**
     * Form definition.
     */
    protected function definition() {
        global $DB;

        $mform = $this->_form;

        $mform->addElement('header', 'filterheader', get_string('filters', 'report_gradeitems'));
        $mform->setExpanded('filterheader', true);

        // Category filter.
        $categories = $this->get_categories();
        $mform->addElement('select', 'category', get_string('filter_category', 'report_gradeitems'), $categories);
        $mform->setType('category', PARAM_INT);

        // Course filter.
        $courses = $this->get_courses();
        $mform->addElement('select', 'course', get_string('filter_course', 'report_gradeitems'), $courses);
        $mform->setType('course', PARAM_INT);

        // Visibility filter.
        $visibility = [
            '' => get_string('allvisibility', 'report_gradeitems'),
            '1' => get_string('visible', 'report_gradeitems'),
            '0' => get_string('hidden', 'report_gradeitems'),
        ];
        $mform->addElement('select', 'visibility', get_string('filter_visibility', 'report_gradeitems'), $visibility);
        $mform->setType('visibility', PARAM_INT);

        // Button group.
        $buttonarray = [];
        $buttonarray[] = $mform->createElement('submit', 'submitbutton', get_string('applyfilters', 'report_gradeitems'));
        $buttonarray[] = $mform->createElement('submit', 'resetbutton', get_string('clearfilters', 'report_gradeitems'));
        $mform->addGroup($buttonarray, 'buttonar', '', ' ', false);
    }

    /**
     * Get all categories for the filter.
     *
     * @return array
     */
    protected function get_categories(): array {
        global $DB;

        $categories = ['' => get_string('allcategories', 'report_gradeitems')];

        $allcats = $DB->get_records('course_categories', [], 'name ASC', 'id, name, parent, depth');

        // Build hierarchical names.
        foreach ($allcats as $cat) {
            $path = $this->get_category_path($cat->id, $allcats);
            $categories[$cat->id] = $path;
        }

        asort($categories);
        return $categories;
    }

    /**
     * Get category path name.
     *
     * @param int $catid
     * @param array $allcats
     * @return string
     */
    protected function get_category_path(int $catid, array $allcats): string {
        if (!isset($allcats[$catid])) {
            return '';
        }

        $cat = $allcats[$catid];
        $path = $cat->name;

        if ($cat->parent > 0 && isset($allcats[$cat->parent])) {
            $path = $this->get_category_path($cat->parent, $allcats) . ' / ' . $path;
        }

        return $path;
    }

    /**
     * Get all courses for the filter.
     *
     * @return array
     */
    protected function get_courses(): array {
        global $DB;

        $courses = ['' => get_string('allcourses', 'report_gradeitems')];

        $sql = "SELECT c.id, c.shortname, c.fullname, cc.name AS categoryname
                  FROM {course} c
                  JOIN {course_categories} cc ON cc.id = c.category
                 WHERE c.id > 1
              ORDER BY cc.name, c.fullname";

        $allcourses = $DB->get_records_sql($sql);

        foreach ($allcourses as $course) {
            $courses[$course->id] = $course->shortname . ' - ' . $course->fullname;
        }

        return $courses;
    }
}
