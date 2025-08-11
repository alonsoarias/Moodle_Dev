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
 * Download center plugin
 *
 * @package       local_downloadcenter
 * @author        Simeon Naydenov (moniNaydenov@gmail.com)
 * @copyright     2020 Academic Moodle Cooperation {@link http://www.academic-moodle-cooperation.org}
 * @license       http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


require_once(__DIR__ . '/../../config.php');
require_once(__DIR__ . '/locallib.php');
require_once(__DIR__ . '/download_form.php');
require_once(__DIR__ . '/course_select_form.php');

// Raise timelimit as this could take a while for big archives.
core_php_time_limit::raise();
raise_memory_limit(MEMORY_HUGE);

$courseid = optional_param('courseid', 0, PARAM_INT);
$courseids = optional_param_array('courseids', $courseid ? [$courseid] : [], PARAM_INT);
$downloadall = optional_param('downloadall', 0, PARAM_BOOL);

require_login();
$systemcontext = context_system::instance();
require_capability('local/downloadcenter:view', $systemcontext);

if (empty($courseids)) {
    $PAGE->set_url(new moodle_url('/local/downloadcenter/index.php'));
    $PAGE->set_context($systemcontext);
    $PAGE->set_pagelayout('standard');
    $PAGE->set_title(get_string('navigationlink', 'local_downloadcenter'));
    $PAGE->set_heading($SITE->fullname);

    $selectform = new local_downloadcenter_course_select_form();
    if ($data = $selectform->get_data()) {
        $params = [];
        if (!empty($data->courseids)) {
            $params['courseids'] = $data->courseids;
        }
        if (!empty($data->downloadall)) {
            $params['downloadall'] = 1;
        }
        redirect(new moodle_url('/local/downloadcenter/index.php', $params));
    }

    echo $OUTPUT->header();
    $selectform->display();
    echo $OUTPUT->footer();
    exit;
}

if (count($courseids) > 1) {
    if (!$downloadall) {
        throw new moodle_exception('selectonecourse', 'local_downloadcenter');
    }

    $filename = sprintf('courses_%s.zip', userdate(time(), '%Y%m%d_%H%M'));
    $zipwriter = \core_files\archive_writer::get_stream_writer($filename, \core_files\archive_writer::ZIP_WRITER);
    foreach ($courseids as $cid) {
        $course = $DB->get_record('course', ['id' => $cid], '*', MUST_EXIST);
        require_login($course);
        $downloadcenter = new local_downloadcenter_factory($course, $USER);
        $downloadcenter->select_all_resources();
        $filelist = $downloadcenter->build_filelist(local_downloadcenter_factory::shorten_filename($course->shortname) . '/');
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
    die;
}

$courseid = reset($courseids);
$course = $DB->get_record('course', ['id' => $courseid], '*', MUST_EXIST);
require_login($course);

$PAGE->set_url(new moodle_url('/local/downloadcenter/index.php', ['courseid' => $course->id]));
$PAGE->set_pagelayout('incourse');


$downloadcenter = new local_downloadcenter_factory($course, $USER);

if ($downloadall) {
    $downloadcenter->select_all_resources();
    $downloadcenter->create_zip();
}

$userresources = $downloadcenter->get_resources_for_user();

$PAGE->requires->js_call_amd('local_downloadcenter/modfilter', 'init', $downloadcenter->get_js_modnames());

$downloadform = new local_downloadcenter_download_form(null,
    ['res' => $userresources],
    'post',
    '',
    ['data-double-submit-protection' => 'off']);

$PAGE->set_title(get_string('navigationlink', 'local_downloadcenter') . ': ' . $course->fullname);
$PAGE->set_heading($course->fullname);

if ($data = $downloadform->get_data()) {
    $event = \local_downloadcenter\event\zip_downloaded::create(array(
        'objectid' => $PAGE->course->id,
        'context' => $PAGE->context
    ));
    $event->add_record_snapshot('course', $PAGE->course);
    $event->trigger();

    $downloadcenter->parse_form_data($data);
    $hash = $downloadcenter->create_zip();
} else if ($downloadform->is_cancelled()) {
    redirect(new moodle_url('/course/view.php', array('id' => $course->id)));
    die;
} else {
    $event = \local_downloadcenter\event\plugin_viewed::create(array(
        'objectid' => $PAGE->course->id,
        'context' => $PAGE->context
    ));
    $event->add_record_snapshot('course', $PAGE->course);
    $event->trigger();
    echo $OUTPUT->header();
}

echo $OUTPUT->heading(get_string('navigationlink', 'local_downloadcenter'), 1);
$downloadform->display();
echo $OUTPUT->footer();