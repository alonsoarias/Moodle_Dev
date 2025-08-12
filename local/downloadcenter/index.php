<?php
// This file is part of local_downloadcenter for Moodle - http://moodle.org/
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
 * Download center plugin entry point.
 *
 * @package       local_downloadcenter
 * @author        Simeon Naydenov (moniNaydenov@gmail.com)
 * @author        ChatGPT
 * @license       http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once(__DIR__ . '/locallib.php');
require_once(__DIR__ . '/download_form.php');
require_once(__DIR__ . '/category_select_form.php');

core_php_time_limit::raise();
raise_memory_limit(MEMORY_HUGE);

$catid = optional_param('catid', 0, PARAM_INT);
$courseid = optional_param('courseid', 0, PARAM_INT);
$action = optional_param('action', '', PARAM_ALPHA);

require_login();
$systemcontext = context_system::instance();
require_capability('local/downloadcenter:view', $systemcontext);

$selection = $SESSION->local_downloadcenter_selection ?? [];

if ($action === 'clear') {
    unset($SESSION->local_downloadcenter_selection);
    redirect(new moodle_url('/local/downloadcenter/index.php', ['catid' => $catid]));
}

if ($action === 'download') {
    if (empty($selection)) {
        redirect(new moodle_url('/local/downloadcenter/index.php'));
    }

    \core\session\manager::write_close();
    $filename = sprintf('courses_%s.zip', userdate(time(), '%Y%m%d_%H%M'));
    $zipwriter = \core_files\archive_writer::get_stream_writer($filename, \core_files\archive_writer::ZIP_WRITER);

    foreach ($selection as $cid => $data) {
        $course = $DB->get_record('course', ['id' => $cid], '*', MUST_EXIST);
        require_login($course);
        $downloadcenter = new local_downloadcenter_factory($course, $USER);
        $downloadcenter->parse_form_data((object)$data);
        $prefix = local_downloadcenter_factory::shorten_filename(clean_filename($course->shortname)) . '/';
        $filelist = $downloadcenter->build_filelist($prefix);
        foreach ($filelist as $pathinzip => $file) {
            if ($file instanceof \stored_file) {
                $zipwriter->add_file_from_stored_file($pathinzip, $file);
            } else if (is_array($file)) {
                $content = reset($file);
                $zipwriter->add_file_from_string($pathinzip, $content);
            } else if (is_string($file)) {
                $zipwriter->add_file_from_filepath($pathinzip, $file);
            }
        }
    }
    $zipwriter->finish();
    unset($SESSION->local_downloadcenter_selection);
    die;
}

if (empty($catid)) {
    $PAGE->set_url(new moodle_url('/local/downloadcenter/index.php'));
    $PAGE->set_context($systemcontext);
    $PAGE->set_pagelayout('standard');
    $PAGE->set_title(get_string('navigationlink', 'local_downloadcenter'));
    $PAGE->set_heading($SITE->fullname);

    $catform = new local_downloadcenter_category_select_form();
    if ($data = $catform->get_data()) {
        redirect(new moodle_url('/local/downloadcenter/index.php', ['catid' => $data->catid]));
    }

    echo $OUTPUT->header();
    $catform->display();
    echo $OUTPUT->footer();
    exit;
}

if ($courseid) {
    $course = $DB->get_record('course', ['id' => $courseid], '*', MUST_EXIST);
    require_login($course);

    $PAGE->set_url(new moodle_url('/local/downloadcenter/index.php', ['catid' => $catid, 'courseid' => $courseid]));
    $PAGE->set_pagelayout('incourse');

    $downloadcenter = new local_downloadcenter_factory($course, $USER);
    $userresources = $downloadcenter->get_resources_for_user();
    $PAGE->requires->js_call_amd('local_downloadcenter/modfilter', 'init', $downloadcenter->get_js_modnames());

    $downloadform = new local_downloadcenter_download_form(null, ['res' => $userresources], 'post', '', ['data-double-submit-protection' => 'off']);

    $PAGE->set_title(get_string('navigationlink', 'local_downloadcenter') . ': ' . $course->fullname);
    $PAGE->set_heading($course->fullname);

    if ($data = $downloadform->get_data()) {
        $downloadcenter->parse_form_data($data);
        $selection[$courseid] = (array)$data;
        $SESSION->local_downloadcenter_selection = $selection;
        redirect(new moodle_url('/local/downloadcenter/index.php', ['catid' => $catid]));
    } else if ($downloadform->is_cancelled()) {
        redirect(new moodle_url('/local/downloadcenter/index.php', ['catid' => $catid]));
    } else {
        echo $OUTPUT->header();
        echo $OUTPUT->heading(get_string('navigationlink', 'local_downloadcenter'), 1);
        $downloadform->display();
        echo $OUTPUT->footer();
        exit;
    }
}

$category = \core_course_category::get($catid, MUST_EXIST);
$courses = $category->get_courses(['recursive' => false, 'sort' => ['fullname' => 1]]);

$PAGE->set_url(new moodle_url('/local/downloadcenter/index.php', ['catid' => $catid]));
$PAGE->set_context($systemcontext);
$PAGE->set_pagelayout('standard');
$PAGE->set_title(get_string('navigationlink', 'local_downloadcenter'));
$PAGE->set_heading($SITE->fullname);

echo $OUTPUT->header();

echo html_writer::start_tag('ul');
foreach ($courses as $course) {
    if (!$course->can_access()) {
        continue;
    }
    $url = new moodle_url('/local/downloadcenter/index.php', ['catid' => $catid, 'courseid' => $course->id]);
    $label = $course->get_formatted_name();
    if (isset($selection[$course->id])) {
        $label .= ' (' . get_string('selected', 'local_downloadcenter') . ')';
    }
    echo html_writer::tag('li', html_writer::link($url, $label));
}
echo html_writer::end_tag('ul');

if (!empty($selection)) {
    $downloadurl = new moodle_url('/local/downloadcenter/index.php', ['action' => 'download']);
    echo html_writer::link($downloadurl, get_string('downloadselection', 'local_downloadcenter'), ['class' => 'btn btn-primary mr-2']);
    $clearurl = new moodle_url('/local/downloadcenter/index.php', ['action' => 'clear', 'catid' => $catid]);
    echo html_writer::link($clearurl, get_string('clearselection', 'local_downloadcenter'), ['class' => 'btn btn-secondary']);
}

echo $OUTPUT->footer();
