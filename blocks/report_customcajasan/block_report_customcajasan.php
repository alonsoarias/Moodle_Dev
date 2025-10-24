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
 * Block definition class for report_customcajasan
 *
 * @package    block_report_customcajasan
 * @copyright  2025 Cajasan
 * @author     Pedro Arias <soporte@ingeweb.co>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/lib.php');

/**
 * Class block_report_customcajasan
 *
 * @package    block_report_customcajasan
 * @copyright  2025 Cajasan
 * @author     Pedro Arias <soporte@ingeweb.co>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class block_report_customcajasan extends block_base {

    /** @var string Option key for displaying the report link. */
    public const INFO_REPORT_LINK = 'reportlink';

    /** @var string Option key for displaying the usage instructions. */
    public const INFO_INSTRUCTIONS = 'instructions';

    /** @var string Option key for displaying the status legend. */
    public const INFO_STATUS_LEGEND = 'statuslegend';

    /** @var string Option key for selected course filters. */
    public const CONFIG_COURSES = 'coursefilters';

    /** @var string Option key for selected category filters. */
    public const CONFIG_CATEGORIES = 'categoryfilters';

    /**
     * Initialize the block.
     */
    public function init() {
        $this->title = get_string('pluginname', 'block_report_customcajasan');
    }

    /**
     * Get the block content.
     *
     * @return stdClass The block content.
     */
    public function get_content() {
        if ($this->content !== null) {
            return $this->content;
        }

        $this->content = new stdClass();
        $this->content->text = '';
        $this->content->footer = '';

        if (!isloggedin()) {
            return $this->content;
        }

        if (!$this->user_has_view_permission()) {
            return $this->content;
        }

        $this->ensure_config_defaults();

        $canviewreport = $this->can_view_report();

        $contentparts = [];
        $selectedoptions = $this->config->displayoptions ?? [];

        $restrictionsummary = '';
        if ($this->is_course_parent_context()) {
            $restrictions = block_report_customcajasan_compute_restrictions($this->config);
            if (!empty($restrictions['courses']) || !empty($restrictions['categories'])) {
                $restrictionsummary = $this->format_restriction_summary($restrictions);
            }
        }

        if (in_array(self::INFO_REPORT_LINK, $selectedoptions, true) && $canviewreport) {
            $params = [];
            $courseid = $this->get_parent_course_id();
            if ($courseid > 0) {
                $params['courseid'] = $courseid;
            }
            if (!empty($this->instance->id)) {
                $params['blockinstanceid'] = $this->instance->id;
            }
            $reporturl = new moodle_url('/blocks/report_customcajasan/report.php', $params);
            $contentparts[] = html_writer::div(
                html_writer::link(
                    $reporturl,
                    get_string('enrollment_report', 'block_report_customcajasan'),
                    ['class' => 'btn btn-primary btn-block mb-2']
                ),
                'report-links'
            );
        }

        if (!empty($restrictionsummary)) {
            $contentparts[] = html_writer::div($restrictionsummary, 'alert alert-info mb-2 restrictions-summary');
        }

        if (in_array(self::INFO_INSTRUCTIONS, $selectedoptions, true)) {
            $contentparts[] = html_writer::div(
                get_string('block_instructionstext', 'block_report_customcajasan'),
                'alert alert-secondary mb-2'
            );
        }

        if (in_array(self::INFO_STATUS_LEGEND, $selectedoptions, true)) {
            $legend = html_writer::div(
                html_writer::tag('strong', get_string('status_explanation', 'block_report_customcajasan')) . ' ' .
                html_writer::span(get_string('state_aprobado', 'block_report_customcajasan'), 'badge badge-success mr-1') .
                html_writer::span(get_string('state_encurso', 'block_report_customcajasan'), 'badge badge-warning mr-1') .
                html_writer::span(get_string('state_noiniciado', 'block_report_customcajasan'), 'badge badge-danger mr-1') .
                html_writer::span(get_string('state_soloconsulta', 'block_report_customcajasan'), 'badge badge-secondary mr-1'),
                'mb-2'
            );
            $contentparts[] = $legend;
        }

        if (!empty($this->config->custommessage)) {
            $contentparts[] = html_writer::div(
                nl2br(s($this->config->custommessage)),
                'small text-muted'
            );
        }

        $this->content->text = implode('', $contentparts);

        return $this->content;
    }

    /**
     * Ensure default configuration options are set for the block instance.
     */
    protected function ensure_config_defaults(): void {
        if (empty($this->config)) {
            $this->config = new stdClass();
        }

        $availableoptions = array_keys($this->get_available_info_options());

        if (empty($this->config->displayoptions)) {
            $this->config->displayoptions = [self::INFO_REPORT_LINK, self::INFO_INSTRUCTIONS];
        } else if (is_string($this->config->displayoptions)) {
            $this->config->displayoptions = array_filter(explode(',', $this->config->displayoptions));
        } else if (!is_array($this->config->displayoptions)) {
            $this->config->displayoptions = (array)$this->config->displayoptions;
        }

        $this->config->displayoptions = array_values(array_intersect($this->config->displayoptions, $availableoptions));

        if (empty($this->config->displayoptions)) {
            $this->config->displayoptions = [self::INFO_REPORT_LINK];
        }

        if (!isset($this->config->custommessage)) {
            $this->config->custommessage = '';
        }

        if (!isset($this->config->{self::CONFIG_COURSES})) {
            $this->config->{self::CONFIG_COURSES} = [];
        } else if (is_string($this->config->{self::CONFIG_COURSES})) {
            $this->config->{self::CONFIG_COURSES} = array_filter(array_map('intval', explode(',', $this->config->{self::CONFIG_COURSES})));
        } else if (!is_array($this->config->{self::CONFIG_COURSES})) {
            $this->config->{self::CONFIG_COURSES} = array_map('intval', (array)$this->config->{self::CONFIG_COURSES});
        }

        if (!isset($this->config->{self::CONFIG_CATEGORIES})) {
            $this->config->{self::CONFIG_CATEGORIES} = [];
        } else if (is_string($this->config->{self::CONFIG_CATEGORIES})) {
            $this->config->{self::CONFIG_CATEGORIES} = array_filter(array_map('intval', explode(',', $this->config->{self::CONFIG_CATEGORIES})));
        } else if (!is_array($this->config->{self::CONFIG_CATEGORIES})) {
            $this->config->{self::CONFIG_CATEGORIES} = array_map('intval', (array)$this->config->{self::CONFIG_CATEGORIES});
        }
    }

    /**
     * Returns the available content options that can be shown in the block.
     *
     * @return array<string, string> Array of option keys mapped to language strings.
     */
    public function get_available_info_options(): array {
        return [
            self::INFO_REPORT_LINK => get_string('config_option_reportlink', 'block_report_customcajasan'),
            self::INFO_INSTRUCTIONS => get_string('config_option_instructions', 'block_report_customcajasan'),
            self::INFO_STATUS_LEGEND => get_string('config_option_statuslegend', 'block_report_customcajasan'),
        ];
    }

    /**
     * Determine whether the current user can view this block.
     *
     * @return bool
     */
    protected function user_has_view_permission(): bool {
        if (!isloggedin() || isguestuser()) {
            return false;
        }

        $context = $this->resolve_block_context();

        if ($context instanceof context_block) {
            if (!has_capability('block/report_customcajasan:viewblock', $context)) {
                return false;
            }
        }

        return block_report_customcajasan_user_has_view_capability($context);
    }

    /**
     * Determine whether the current user can access the report link.
     *
     * @return bool
     */
    protected function can_view_report(): bool {
        return $this->user_has_view_permission();
    }

    /**
     * Resolve the context that represents this block instance.
     *
     * @return context
     */
    protected function resolve_block_context(): context {
        if (!empty($this->context)) {
            return $this->context;
        }

        if (!empty($this->instance->id)) {
            $blockcontext = context_block::instance($this->instance->id, IGNORE_MISSING);
            if ($blockcontext) {
                return $blockcontext;
            }
        }

        return context_system::instance();
    }

    /**
     * Require additional capability checks before displaying the block.
     *
     * @param renderer_base $output The renderer requesting the content.
     * @return block_contents|null
     */
    public function get_content_for_output($output) {
        if (!$this->user_has_view_permission()) {
            return null;
        }

        return parent::get_content_for_output($output);
    }

    /**
     * Specify which page formats this block can be displayed in.
     *
     * @return array Array of page formats.
     */
    public function applicable_formats() {
        return [
            'site-index' => true,
            'course-view' => true,
            'course' => true,
            'my' => false,
            'admin' => false,
        ];
    }

    /**
     * Can multiple instances of this block be used on a page?
     *
     * @return bool False means only one instance allowed.
     */
    public function instance_allow_multiple() {
        return false;
    }

    /**
     * Does this block have global configuration?
     *
     * @return bool False as this block doesn't have config.
     */
    public function has_config() {
        return false;
    }

    /**
     * Does this block have instance-specific configuration?
     *
     * @return bool True if the block can be configured.
     */
    public function instance_allow_config() {
        return $this->is_course_parent_context();
    }

    /**
     * Save the instance configuration, normalising the data before storage.
     *
     * @param stdClass $data Configuration data submitted via the form.
     * @param bool $nolongerused Moodle core reserved parameter (unused).
     */
    public function instance_config_save($data, $nolongerused = false) {
        $availableoptions = array_keys($this->get_available_info_options());

        if (!isset($data->displayoptions)) {
            $data->displayoptions = [];
        } else if (is_string($data->displayoptions)) {
            $data->displayoptions = [$data->displayoptions];
        } else if (!is_array($data->displayoptions)) {
            $data->displayoptions = (array)$data->displayoptions;
        }

        $data->displayoptions = array_values(array_intersect($data->displayoptions, $availableoptions));

        if (empty($data->displayoptions)) {
            $data->displayoptions = [self::INFO_REPORT_LINK];
        }

        if (!empty($data->custommessage)) {
            $data->custommessage = clean_param($data->custommessage, PARAM_TEXT);
        } else {
            $data->custommessage = '';
        }

        if (!$this->is_course_parent_context()) {
            $data->{self::CONFIG_COURSES} = [];
            $data->{self::CONFIG_CATEGORIES} = [];
            parent::instance_config_save($data, $nolongerused);
            return;
        }

        $restrictions = $this->prepare_restriction_config($data);
        $data->{self::CONFIG_COURSES} = $restrictions['courses'];
        $data->{self::CONFIG_CATEGORIES} = $restrictions['categories'];

        parent::instance_config_save($data, $nolongerused);
    }

    /**
     * Determine if the block's parent context is a course.
     *
     * @return bool
     */
    protected function is_course_parent_context(): bool {
        $parentcontext = $this->get_parent_context();
        return $parentcontext &&
            $parentcontext->contextlevel === CONTEXT_COURSE &&
            (int)$parentcontext->instanceid !== SITEID;
    }

    /**
     * Retrieve the context the block belongs to.
     *
     * @return context|null
     */
    protected function get_parent_context(): ?context {
        if (!empty($this->instance->parentcontextid)) {
            return context::instance_by_id($this->instance->parentcontextid, IGNORE_MISSING);
        }

        return $this->page->context ?? null;
    }

    /**
     * Get the course identifier associated with the block's parent context.
     *
     * @return int
     */
    protected function get_parent_course_id(): int {
        $parentcontext = $this->get_parent_context();
        if ($parentcontext &&
                $parentcontext->contextlevel === CONTEXT_COURSE &&
                (int)$parentcontext->instanceid !== SITEID) {
            return (int)$parentcontext->instanceid;
        }

        return 0;
    }

    /**
     * Generate a human-readable summary of the configured restrictions.
     *
     * @param array $restrictions Data returned by block_report_customcajasan_compute_restrictions().
     * @return string
     */
    protected function format_restriction_summary(array $restrictions): string {
        global $DB;

        $coursenames = [];
        $categorynames = [];

        if (!empty($restrictions['courses'])) {
            list($insql, $params) = $DB->get_in_or_equal($restrictions['courses'], SQL_PARAMS_NAMED, 'crs');
            $records = $DB->get_records_sql("SELECT id, fullname FROM {course} WHERE id $insql ORDER BY fullname ASC", $params);
            foreach ($restrictions['courses'] as $courseid) {
                if (!empty($records[$courseid])) {
                    $coursecontext = context_course::instance($courseid, IGNORE_MISSING);
                    $coursenames[] = format_string($records[$courseid]->fullname, true, ['context' => $coursecontext ?: null]);
                }
            }
        }

        if (!empty($restrictions['categories'])) {
            foreach ($restrictions['categories'] as $categoryid) {
                try {
                    $category = \core_course_category::get($categoryid, IGNORE_MISSING);
                } catch (moodle_exception $e) {
                    $category = null;
                }
                if ($category) {
                    $categorycontext = context_coursecat::instance($categoryid, IGNORE_MISSING);
                    $categorynames[] = format_string($category->get_formatted_name(), true, ['context' => $categorycontext ?: null]);
                }
            }
        }

        $parts = [];
        if (!empty($coursenames)) {
            $parts[] = get_string('config_summary_courses', 'block_report_customcajasan', implode(', ', $coursenames));
        }

        if (!empty($categorynames)) {
            $parts[] = get_string('config_summary_categories', 'block_report_customcajasan', implode(', ', $categorynames));
        }

        return implode(' ', $parts);
    }

    /**
     * Validate and normalise restriction selections before saving.
     *
     * @param stdClass $data Configuration data submitted via the form.
     * @return array{courses: array<int>, categories: array<int>}
     */
    protected function prepare_restriction_config(stdClass $data): array {
        $courses = [];
        $categories = [];

        if (!empty($data->{self::CONFIG_COURSES})) {
            $courses = array_map('intval', (array)$data->{self::CONFIG_COURSES});
        }

        if (!empty($data->{self::CONFIG_CATEGORIES})) {
            $categories = array_map('intval', (array)$data->{self::CONFIG_CATEGORIES});
        }

        $restrictions = block_report_customcajasan_compute_restrictions((object)[
            self::CONFIG_COURSES => $courses,
            self::CONFIG_CATEGORIES => $categories,
        ]);

        return [
            'courses' => $restrictions['courses'],
            'categories' => $restrictions['categories'],
        ];
    }
}