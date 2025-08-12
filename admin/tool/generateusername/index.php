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
 * Generate usernames from CSV data.
 *
 * @package   tool_generateusername
 * @copyright 2024 Alonso Arias
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require('../../../config.php');
require_once($CFG->libdir . '/adminlib.php');
require_once($CFG->libdir . '/csvlib.class.php');
require_once($CFG->libdir . '/excellib.class.php');
require_once($CFG->dirroot . '/user/lib.php');
require_once($CFG->dirroot . '/user/profile/lib.php');
require_once($CFG->dirroot . '/lib/moodlelib.php');
require_once($CFG->dirroot . '/admin/tool/uploaduser/locallib.php');
require_once(__DIR__ . '/locallib.php');
require_once(__DIR__ . '/user_form.php');

require_login();
admin_externalpage_setup('toolgenerateusername');
require_capability('tool/generateusername:generateusername', context_system::instance());

$download = optional_param('download', '', PARAM_ALPHA);

if ($download) {
    $content = $SESSION->tool_generateusername_export ?? null;
    if ($content) {
        $filename = 'generated_users';
        if ($download === 'xlsx') {
            $workbook = new MoodleExcelWorkbook('-');
            $workbook->send($filename . '.xlsx');
            $sheet = $workbook->add_worksheet('users');
            foreach ($content as $r => $row) {
                $c = 0;
                foreach ($row as $value) {
                    $sheet->write_string($r, $c++, $value);
                }
            }
            $workbook->close();
        } else {
            $export = new csv_export_writer('semicolon');
            $export->set_filename($filename);
            foreach ($content as $row) {
                $export->add_data($row);
            }
            $export->download_file();
        }
    }
    die();
}

$form = new tool_generateusername_form();

if ($data = $form->get_data()) {
    $iid = csv_import_reader::get_new_iid('toolgenerateusername');
    $content = $form->get_file_content('userfile');
    $cir = new csv_import_reader($iid, 'toolgenerateusername');
    $cir->load_csv_content($content, $data->encoding, $data->delimiter_name);

    $returnurl = new moodle_url('/admin/tool/generateusername/index.php');

    // Valid columns as in tool_uploaduser.
    $STD_FIELDS = ['id', 'username', 'email', 'city', 'country', 'lang', 'timezone',
        'mailformat', 'maildisplay', 'maildigest', 'htmleditor', 'autosubscribe',
        'institution', 'department', 'idnumber', 'skype', 'msn', 'aim', 'yahoo', 'icq',
        'phone1', 'phone2', 'address', 'url', 'description', 'descriptionformat',
        'password', 'auth', 'oldusername', 'suspended', 'deleted', 'mnethostid'];
    $STD_FIELDS = array_merge($STD_FIELDS, get_all_user_name_fields());

    $PRF_FIELDS = [];
    if ($proffields = $DB->get_records('user_info_field')) {
        foreach ($proffields as $key => $proffield) {
            $profilefieldname = 'profile_field_' . $proffield->shortname;
            $PRF_FIELDS[] = $profilefieldname;
            $proffields[$profilefieldname] = $proffield;
            unset($proffields[$key]);
        }
    }

    $filecolumns = uu_validate_user_upload_columns($cir, $STD_FIELDS, $PRF_FIELDS, $returnurl);
    if (in_array('username', $filecolumns)) {
        print_error('usernamepresent', 'tool_generateusername');
    }

    $required = ['firstname', 'lastname', 'email', 'idnumber', 'password'];
    if ($missing = array_diff($required, $filecolumns)) {
        print_error('missingrequiredfields', 'tool_generateusername');
    }

    // Determine columns to output: username, firstname, lastname, email, idnumber, password, profile fields, others.
    $profilecolumns = [];
    foreach ($filecolumns as $column) {
        if (strpos($column, 'profile_field_') === 0) {
            $profilecolumns[] = $column;
        }
    }
    $othercolumns = array_diff($filecolumns, $required);
    $othercolumns = array_diff($othercolumns, $profilecolumns);
    $basecolumns = ['firstname', 'lastname', 'email', 'idnumber', 'password'];
    $exportcolumns = array_merge(['username'], $basecolumns, $profilecolumns, $othercolumns);
    $exportdata = [$exportcolumns];

    $cir->init();
    $existing = [];
    while ($line = $cir->next()) {
        $record = array_combine($filecolumns, $line);
        $idnumber = trim($record['idnumber']);
        $email = trim($record['email']);

        if ($existinguser = $DB->get_record('user', ['idnumber' => $idnumber, 'email' => $email])) {
            $username = $existinguser->username;
            $firstname = $existinguser->firstname;
            $lastname = $existinguser->lastname;
            $email = $existinguser->email;
            $profiledata = profile_user_record($existinguser->id, false);
        } else {
            $firstname = $record['firstname'];
            $lastname = $record['lastname'];
            $email = $record['email'];
            $base = tool_generateusername_generate_username($firstname, $lastname);
            $username = tool_generateusername_make_unique($base, $existing);
            $profiledata = null;
        }
        $existing[] = $username;

        $output = [
            'username' => $username,
            'firstname' => $firstname,
            'lastname' => $lastname,
            'email' => $email,
            'idnumber' => $idnumber,
            'password' => $record['password'] ?? ''
        ];

        foreach ($profilecolumns as $column) {
            if ($profiledata && property_exists($profiledata, $column)) {
                $output[$column] = $profiledata->$column;
            } else {
                $output[$column] = $record[$column] ?? '';
            }
        }
        foreach ($othercolumns as $column) {
            if ($column === 'suspended') {
                $output[$column] = $record[$column] ?? '0';
            } else {
                $output[$column] = $record[$column] ?? '';
            }
        }
        $row = [];
        foreach ($exportcolumns as $column) {
            $row[] = $output[$column] ?? '';
        }
        $exportdata[] = $row;
    }
    $cir->close();

    $SESSION->tool_generateusername_export = $exportdata;

    echo $OUTPUT->header();
    echo $OUTPUT->heading(get_string('pluginname', 'tool_generateusername'));
    $urlcsv = new moodle_url('/admin/tool/generateusername/index.php', ['download' => 'csv']);
    $urlexcel = new moodle_url('/admin/tool/generateusername/index.php', ['download' => 'xlsx']);
    echo html_writer::div(html_writer::link($urlexcel, get_string('downloadexcel', 'tool_generateusername')));
    echo html_writer::div(html_writer::link($urlcsv, get_string('downloadcsv', 'tool_generateusername')));
    echo $OUTPUT->footer();
    die();
}

// Show upload form.
echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('pluginname', 'tool_generateusername'));
$form->display();
echo $OUTPUT->footer();

