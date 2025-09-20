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
 * Admin tree renderer helpers.
 *
 * @package    local_downloadcenter
 * @copyright  2025 Academic Moodle Cooperation
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_downloadcenter\output;

use context_course;
use context_coursecat;
use core_course_category;
use core_course_list_element;
use html_writer;
use local_downloadcenter\factory;
use local_downloadcenter\selection_manager;

/**
 * Helper class to render the administrator course tree.
 */
class admin_tree_renderer {
    /** @var selection_manager */
    protected $selectionmanager;

    /** @var bool Whether the current user may access restricted courses */
    protected $allowrestricted;

    /**
     * Constructor.
     *
     * @param selection_manager $selectionmanager Current user selection manager
     * @param bool $allowrestricted Whether restricted courses should be listed
     */
    public function __construct(selection_manager $selectionmanager, bool $allowrestricted) {
        $this->selectionmanager = $selectionmanager;
        $this->allowrestricted = $allowrestricted;
    }

    /**
     * Render the list of root categories.
     *
     * @return string
     */
    public function render_root_categories(): string {
        $output = '';
        $rootcategory = core_course_category::top();
        foreach ($rootcategory->get_children() as $category) {
            $output .= $this->render_category_node($category, true);
        }

        return $output;
    }

    /**
     * Render immediate children (sub categories + courses) for a category.
     *
     * @param int $categoryid Category id
     * @return string HTML
     */
    public function render_category_children(int $categoryid): string {
        $output = '';

        if (!$categoryid) {
            $category = core_course_category::get_default();
        } else {
            $category = core_course_category::get($categoryid, IGNORE_MISSING, true);
        }

        if (!$category) {
            return '';
        }

        foreach ($category->get_children() as $child) {
            $output .= $this->render_category_node($child, false);
        }

        $courses = $category->get_courses(['recursive' => false, 'sort' => ['fullname' => 1]]);
        foreach ($courses as $course) {
            if (!$course instanceof core_course_list_element) {
                $course = new core_course_list_element($course);
            }

            if (!$this->allowrestricted && !$course->can_access()) {
                continue;
            }

            $output .= $this->render_course_node($course);
        }

        return $output ?: html_writer::div(\get_string('nocoursesfound', 'local_downloadcenter'),
            'alert alert-light mb-3');
    }

    /**
     * Render a single category node.
     *
     * @param core_course_category $category Category to render
     * @param bool $lazy Whether the children should be lazy loaded
     * @return string
     */
    public function render_category_node(core_course_category $category, bool $lazy = true): string {
        $categorycontext = context_coursecat::instance($category->id);
        $label = \format_string($category->name, true, ['context' => $categorycontext]);
        $childcount = $category->get_children_count();
        $coursecount = $category->get_courses_count();

        $selection = $this->selectionmanager->get_course_selections_for_category($category->id);
        $summarylabel = $label;
        if ($coursecount) {
            $summarylabel .= ' (' . $coursecount . ')';
        }

        $detailsattrs = [
            'class' => 'downloadcenter-category mb-2',
            'data-categoryid' => $category->id,
            'data-loaded' => $lazy ? 0 : 1,
        ];

        if ($selection['selected']) {
            $detailsattrs['open'] = 'open';
        }

        $checkboxattrs = [
            'type' => 'checkbox',
            'class' => 'form-check-input category-checkbox',
            'data-categoryid' => $category->id,
        ];
        if ($selection['checked']) {
            $checkboxattrs['checked'] = 'checked';
        }
        if ($selection['indeterminate']) {
            $checkboxattrs['data-indeterminate'] = 1;
        }

        $summarycontent = html_writer::span('', 'downloadcenter-chevron', ['aria-hidden' => 'true']);
        $summarycontent .= html_writer::empty_tag('input', $checkboxattrs);
        $summarycontent .= html_writer::tag('span', $summarylabel, ['class' => 'ml-2 font-weight-bold']);

        if (!$coursecount && !$childcount) {
            $summarycontent .= html_writer::span(\get_string('nocoursesfound', 'local_downloadcenter'),
                'badge badge-light ml-2');
        }

        $summary = html_writer::tag('summary', $summarycontent,
            ['class' => 'd-flex align-items-center']);

        $childrenattrs = [
            'class' => 'category-children pl-4',
            'id' => 'category-node-' . $category->id,
        ];

        $body = $lazy ? '' : $this->render_category_children($category->id);
        $body = html_writer::div($body, $childrenattrs['class'], $childrenattrs);

        return html_writer::tag('details', $summary . $body, $detailsattrs);
    }

    /**
     * Render a course node placeholder.
     *
     * @param core_course_list_element $course Course to render
     * @return string HTML
     */
    public function render_course_node(core_course_list_element $course): string {
        $coursecontext = context_course::instance($course->id);
        $coursename = method_exists($course, 'get_formatted_name') ?
            $course->get_formatted_name() :
            \format_string($course->fullname, true, ['context' => $coursecontext]);
        $shortname = \format_string($course->shortname, true, ['context' => $coursecontext]);

        $selection = $this->selectionmanager->get_course_selection($course->id);
        $isfull = !empty($selection['__fullcourse']);
        $haspartial = $this->selectionmanager->course_has_partial_selection($course->id);

        $checkboxattrs = [
            'type' => 'checkbox',
            'class' => 'form-check-input course-checkbox',
            'data-courseid' => $course->id,
        ];
        if ($isfull) {
            $checkboxattrs['checked'] = 'checked';
        }
        if (!$isfull && $haspartial) {
            $checkboxattrs['data-indeterminate'] = 1;
        }

        $summarycontent = html_writer::span('', 'downloadcenter-chevron', ['aria-hidden' => 'true']);
        $summarycontent .= html_writer::empty_tag('input', $checkboxattrs);
        $summarycontent .= html_writer::tag('span',
            $coursename . ' (' . $shortname . ')',
            ['class' => 'ml-2 d-inline-flex align-items-center']);

        if (!$course->visible) {
            $summarycontent .= html_writer::span(\get_string('hidden'), 'badge badge-warning ml-2');
        }

        $summarycontent .= html_writer::span('0', 'badge badge-primary ml-auto selection-counter',
            ['data-courseid' => $course->id]);

        $summary = html_writer::tag('summary', $summarycontent,
            ['class' => 'd-flex align-items-center']);

        $detailsattrs = [
            'class' => 'downloadcenter-course mb-2',
            'data-courseid' => $course->id,
            'data-loaded' => 0,
        ];
        if ($isfull || $haspartial) {
            $detailsattrs['open'] = 'open';
        }

        $resourcecontainer = html_writer::div('', 'course-resources', ['id' => 'course-node-' . $course->id]);
        $hiddenflag = html_writer::empty_tag('input', [
            'type' => 'hidden',
            'class' => 'course-fullcourse-flag',
            'data-courseid' => $course->id,
            'name' => 'coursedata[' . $course->id . '][__fullcourse]',
            'value' => 1,
            'disabled' => $isfull ? null : 'disabled',
        ]);

        return html_writer::tag('details', $summary . $resourcecontainer, $detailsattrs) . $hiddenflag;
    }

    /**
     * Render course resources list.
     *
     * @param factory $factory Factory instance already prepared for the course
     * @param array $selection Current selection for the course
     * @return string
     */
    public function render_course_resources(factory $factory, array $selection): string {
        $resources = $factory->get_resources_for_user();
        if (empty($resources)) {
            return html_writer::div(\get_string('nocontentavailable', 'local_downloadcenter'),
                'alert alert-light mb-2');
        }

        $courseid = $factory->get_course()->id;
        $output = '';

        foreach ($resources as $sectionid => $sectioninfo) {
            $sectioncheckboxattrs = [
                'type' => 'checkbox',
                'class' => 'form-check-input resource-checkbox section-checkbox',
                'name' => 'coursedata[' . $courseid . '][item_topic_' . $sectionid . ']',
                'data-courseid' => $courseid,
                'data-sectionid' => $sectionid,
            ];
            if (isset($selection['item_topic_' . $sectionid])) {
                $sectioncheckboxattrs['checked'] = 'checked';
            }

            $sectionlabel = html_writer::span($sectioninfo->title, 'ml-2 font-weight-bold');
            if (!$sectioninfo->visible) {
                $sectionlabel .= html_writer::span(\get_string('hiddenfromstudents'),
                    'badge badge-info text-white ml-2');
            }

            $sectionheader = html_writer::div(
                html_writer::empty_tag('input', $sectioncheckboxattrs) .
                html_writer::span($sectionlabel, 'section-title'),
                'form-check form-check-inline mb-2 section-header d-flex align-items-center'
            );

            $resourceitems = '';
            foreach ($sectioninfo->res as $res) {
                $reskey = 'item_' . $res->modname . '_' . $res->instanceid;
                $rescheckboxattrs = [
                    'type' => 'checkbox',
                    'class' => 'form-check-input resource-checkbox resource-item',
                    'name' => 'coursedata[' . $courseid . '][' . $reskey . ']',
                    'value' => 1,
                    'data-courseid' => $courseid,
                    'data-sectionid' => $sectionid,
                    'data-resourcekey' => $reskey,
                ];
                if (isset($selection[$reskey]) || !empty($selection['__fullcourse'])) {
                    $rescheckboxattrs['checked'] = 'checked';
                }

                $labelcontent = $res->icon . html_writer::span($res->name, 'ml-2');
                if (!$res->visible || $res->isstealth) {
                    $labelcontent .= html_writer::span(\get_string('hidden', 'local_downloadcenter'),
                        'badge badge-warning ml-2');
                }

                $resourceitems .= html_writer::div(
                    html_writer::empty_tag('input', $rescheckboxattrs) .
                    html_writer::tag('span', $labelcontent, ['class' => 'resource-title ml-1']),
                    'form-check form-check-inline d-flex align-items-center mb-1'
                );
            }

            $output .= html_writer::div($sectionheader .
                html_writer::div($resourceitems, 'resource-items pl-4'),
                'downloadcenter-section card card-body mb-2',
                ['data-sectionid' => $sectionid]
            );
        }

        return $output;
    }
}
