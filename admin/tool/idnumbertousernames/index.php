<?php
/*
// Tool idnumbertousernames
//
// By @pabloapico - www.promwebsoft.com  - 2017
 */

require('../../../config.php');
require_once($CFG->libdir.'/adminlib.php');
require_once($CFG->libdir.'/csvlib.class.php');
require_once($CFG->dirroot.'/user/profile/lib.php');
require_once($CFG->dirroot.'/user/lib.php');
require_once($CFG->dirroot.'/group/lib.php');
require_once($CFG->dirroot.'/cohort/lib.php');
require_once('../uploaduser/locallib.php');
require_once('locallib.php');
require_once('user_form.php');

$iid         = optional_param('iid', '', PARAM_INT);
$previewrows = optional_param('previewrows', 10, PARAM_INT);

core_php_time_limit::raise(60*60); // 1 hour should be enough
raise_memory_limit(MEMORY_HUGE);

require_login();
admin_externalpage_setup('toolidnumbertousernames');
require_capability('tool/idnumbertousernames:idnumbertousernames', context_system::instance());

$errorstr                   = get_string('error');

$stryes                     = get_string('yes');
$strno                      = get_string('no');
$stryesnooptions = array(0=>$strno, 1=>$stryes);
$delimiter = 'semicolon'; //delimitador del archivo a descargar
$returnurl = new moodle_url('/admin/tool/idnumbertousernames/index.php');

$today = time();
$today = make_timestamp(date('Y', $today), date('m', $today), date('d', $today), 0, 0, 0);

// array of all valid fields for validation
$STD_FIELDS = array('id', 'username', 'email',
        'city', 'country', 'lang', 'timezone', 'mailformat',
        'maildisplay', 'maildigest', 'htmleditor', 'autosubscribe',
        'institution', 'department', 'idnumber', 'skype',
        'msn', 'aim', 'yahoo', 'icq', 'phone1', 'phone2', 'address',
        'url', 'description', 'descriptionformat', 'password',
        'auth',        // watch out when changing auth type or using external auth plugins!
        'oldusername', // use when renaming users - this is the original username
        'suspended',   // 1 means suspend user account, 0 means activate user account, nothing means keep as is for existing users
        'deleted',     // 1 means delete user
        'mnethostid',  // Can not be used for adding, updating or deleting of users - only for enrolments, groups, cohorts and suspending.
    );
// Include all name fields.
$STD_FIELDS = array_merge($STD_FIELDS, get_all_user_name_fields());

$PRF_FIELDS = array();

if ($proffields = $DB->get_records('user_info_field')) {
    foreach ($proffields as $key => $proffield) {
        $profilefieldname = 'profile_field_'.$proffield->shortname;
        $PRF_FIELDS[] = $profilefieldname;
        // Re-index $proffields with key as shortname. This will be
        // used while checking if profile data is key and needs to be converted (eg. menu profile field)
        $proffields[$profilefieldname] = $proffield;
        unset($proffields[$key]);
    }
}

if (empty($iid)) {

    $mform1 = new admin_idnumbertousernames_form1();

    if ($formdata = $mform1->get_data()) {
        $iid = csv_import_reader::get_new_iid('idnumbertousernames');
        $cir = new csv_import_reader($iid, 'idnumbertousernames');

        $content = $mform1->get_file_content('userfile');

        $readcount = $cir->load_csv_content($content, $formdata->encoding, $formdata->delimiter_name);
        $csvloaderror = $cir->get_error();
        unset($content);

        if (!is_null($csvloaderror)) {
            print_error('csvloaderror', '', $returnurl, $csvloaderror);
        }
        // test if columns ok
        $filecolumns = uu_validate_user_upload_columns($cir, $STD_FIELDS, $PRF_FIELDS, $returnurl);
        // continue to form2

    } else {
        //PASO 1 - APENAS VA A COMENZAR
        echo $OUTPUT->header();
        echo $OUTPUT->heading_with_help(get_string('uploadidnumbers', 'tool_idnumbertousernames'), 'uploadidnumbers', 'tool_idnumbertousernames');
        echo html_writer::tag('p', get_string('credits', 'tool_idnumbertousernames'));

        $mform1->display();
        echo $OUTPUT->footer();
        die;
    }
} else {
    //YA SUBIÓ UN ARCHIVO.... CALCULANDO VARIABLES PARA EL PASO 2 O 3
    $cir = new csv_import_reader($iid, 'idnumbertousernames');
    $filecolumns = uu_validate_user_upload_columns($cir, $STD_FIELDS, $PRF_FIELDS, $returnurl);
}


$mform3 = new admin_idnumbertousernames_form3(null, array('columns'=>$filecolumns, 'data'=>array('iid'=>$iid, 'previewrows'=>$previewrows)));

    if ($formdata = $mform3->is_cancelled()) {
      $cir->cleanup(true);
      redirect($returnurl);
      die;

    } elseif ($formdata = $mform3->get_data()) { //CUARTO PASO: DESCARGAR
        // init csv import helper
        $cir->init();
        $linenum = 1; //column header is first line

        $username_keynum = array_search('username',$filecolumns);
        $idnumber_keynum = array_search('idnumber',$filecolumns);
        $optional_keynums_allowed = array('id','auth','suspended','deleted','firstname','lastname');
        $optional_keynums = array_intersect($optional_keynums_allowed, $filecolumns);

        //Archivo a exportar
        $filename = clean_filename(get_string('users'));
        $csvexport = new csv_export_writer($delimiter);
        $csvexport->set_filename($filename);
        $csvexport->add_data($filecolumns);

        while ($line = $cir->next()) {
          $user = new stdClass();

          // add fields to user object
          foreach ($line as $keynum => $value) {
              if (!isset($filecolumns[$keynum])) {
                  // this should not happen
                  continue;
              }

              $key = $filecolumns[$keynum];

              if (strpos($key, 'profile_field_') === 0) {
                  //NOTE: bloody mega hack alert!!
                  if (isset($USER->$key) and is_array($USER->$key)) {
                      // this must be some hacky field that is abusing arrays to store content and format
                      $user->$key = array();
                      $user->$key['text']   = $value;
                      $user->$key['format'] = FORMAT_MOODLE;
                  } else {
                      $user->$key = trim($value);
                  }
              } else {
                  $user->$key = trim($value);
              }


          }

          $user->idnumber = $line[$idnumber_keynum];
          $user->username = '';

          //Buscar el usuario
          //if ($existinguser = $DB->get_record('user', array('idnumber'=>$user->idnumber, 'mnethostid'=>$user->mnethostid))) {
          if ($existinguser = $DB->get_record('user', array('idnumber'=>$user->idnumber))) {

              $user->username = $existinguser->username;
              $user->firstname = $existinguser->firstname;
              $user->lastname = $existinguser->lastname;
              $user->auth = $existinguser->auth;
              $user->suspended = $existinguser->suspended;
              $user->deleted = $existinguser->deleted;
              $user->id = $existinguser->id;
          }

          $line[$username_keynum] = $user->username;
          //añadir los campos opcionales
          foreach ($optional_keynums as $mycol) {
            $mykeynum = array_search($mycol,$filecolumns);
            $line[$mykeynum] = $user->$mycol;
          }

          $csvexport->add_data($line);

          if (!isset($user->username)) {
              // prevent warnings below
              $user->username = '';
          }

          if (empty($user->mnethostid)) {
              $user->mnethostid = $CFG->mnet_localhost_id;
          }

          $user->idnumber = $line[$idnumber_keynum];

          // normalize username... just in case :)
          $user->username = clean_param($existinguser->username, PARAM_USERNAME);

        }

        $cir->close();
        //$cir->cleanup(true);

        $csvexport->download_file();
        die;
    } // fin cuarto paso



//SEGUNDO PASO
$mform2 = new admin_idnumbertousernamesr_form2(null, array('columns'=>$filecolumns, 'data'=>array('iid'=>$iid, 'previewrows'=>$previewrows)));

// If a file has been uploaded, then process it
if ($formdata = $mform2->is_cancelled()) {
    $cir->cleanup(true);
    redirect($returnurl);
    die;

} else if ($formdata = $mform2->get_data()) {

    //TERCER PASO
    // Print the header
    echo $OUTPUT->header();
    echo $OUTPUT->heading(get_string('uploadidnumbersresult', 'tool_idnumbertousernames'));
    echo html_writer::tag('p', get_string('credits', 'tool_idnumbertousernames'));

    $userserrors   = 0;
    $idnumbernotexist  = 0;
    $usersnamesfound = 0;


    // init csv import helper
    $cir->init();
    $linenum = 1; //column header is first line

    // init upload progress tracker
    $upt = new toolidnumbertousernames_progress_tracker();

    $upt->start(); // start table

    $username_keynum = array_search('username',$filecolumns);
    $idnumber_keynum = array_search('idnumber',$filecolumns);

    while ($line = $cir->next()) {
        $upt->flush();
        $linenum++;

        $upt->track('line', $linenum);

        $user = new stdClass();

        // add fields to user object
        foreach ($line as $keynum => $value) {
            if (!isset($filecolumns[$keynum])) {
                // this should not happen
                continue;
            }

            $key = $filecolumns[$keynum];

            if (strpos($key, 'profile_field_') === 0) {
                //NOTE: bloody mega hack alert!!
                if (isset($USER->$key) and is_array($USER->$key)) {
                    // this must be some hacky field that is abusing arrays to store content and format
                    $user->$key = array();
                    $user->$key['text']   = $value;
                    $user->$key['format'] = FORMAT_MOODLE;
                } else {
                    $user->$key = trim($value);
                }
            } else {
                $user->$key = trim($value);
            }

            if (in_array($key, $upt->columns)) {
                // default value in progress tracking table, can be changed later
                $upt->track($key, s($value), 'normal');
            }


        }

        $user->idnumber = $line[$idnumber_keynum];
        $user->username = '';

        //Buscar el usuario
        //if ($existinguser = $DB->get_record('user', array('idnumber'=>$user->idnumber, 'mnethostid'=>$user->mnethostid))) {
        if ($existinguser = $DB->get_record('user', array('idnumber'=>$user->idnumber))) {

            $user->username = $existinguser->username;
            $user->firstname = $existinguser->firstname;
            $user->lastname = $existinguser->lastname;
            $user->auth = $existinguser->auth;
            $user->suspended = $existinguser->suspended;
            $user->deleted = $existinguser->deleted;
            $upt->track('id', $existinguser->id, 'normal', false);
            $upt->track('username', $existinguser->username, 'normal', false);
            $upt->track('firstname', s($existinguser->firstname), 'normal', false);
            $upt->track('lastname', $existinguser->lastname, 'normal', false);
            $upt->track('auth', $existinguser->auth, 'normal', false);
            $upt->track('suspended', $existinguser->suspended, 'normal', false);
            $upt->track('deleted', $existinguser->deleted, 'deleted', false);

            $usersnamesfound += 1;
        } else {
          $idnumbernotexist += 1;
        }

        $line[$username_keynum] = $user->username;

        if (!isset($user->username)) {
            // prevent warnings below
            $user->username = '';
        }

        if (empty($user->mnethostid)) {
            $user->mnethostid = $CFG->mnet_localhost_id;
        }

        $user->idnumber = $line[$idnumber_keynum];

        // normalize username... just in case :)
        $user->username = clean_param($existinguser->username, PARAM_USERNAME);


        // make sure we really have username
        if (empty($user->username)) {
            $upt->track('status', get_string('missingfield', 'error', 'username'), 'error');
            $upt->track('username', $errorstr, 'error');
            $userserrors++;
            continue;
        } else if ($user->username === 'guest') {
            $upt->track('status', get_string('guestnoeditprofileother', 'error'), 'error');
            $userserrors++;
            continue;
        }

        if ($user->username !== clean_param($user->username, PARAM_USERNAME)) {
            $upt->track('status', get_string('invalidusername', 'error', 'username'), 'error');
            $upt->track('username', $errorstr, 'error');
            $userserrors++;
        }


        $skip = false;
        //TODO: Validar si por alguna razón el renglón debe omitirse?
        //TODO: Validar si el idnumber corresponde a mas de un username
        if ($skip) {
            continue;
        }

    }

    $upt->close(); // close table

    $cir->close();

    echo $OUTPUT->box_start('boxwidthnarrow boxaligncenter generalbox', 'uploadresults');
    echo '<p>';
    if ($usersnamesfound) {
        echo get_string('usersnamesfound', 'tool_idnumbertousernames').': '.$usersnamesfound.'<br />';
    }

    if ($idnumbernotexist) {
        echo get_string('idnumbernotexist', 'tool_idnumbertousernames').': '.$idnumbernotexist.'<br />';
    }
    echo get_string('errors', 'tool_idnumbertousernames').': '.$userserrors.'</p>';
    echo $OUTPUT->box_end();

    //echo $OUTPUT->continue_button($returnurl);
    //echo $OUTPUT->single_button($returnurl, get_string('download', 'tool_idnumbertousernames'));
    //echo $OUTPUT->single_button($returnurl, get_string('finish', 'tool_idnumbertousernames'));
    $mform3->display();

    echo $OUTPUT->footer();
    die;
}

//SEGUNDO PASO
// Print the header -
echo $OUTPUT->header();

echo $OUTPUT->heading(get_string('uploadidnumbersresult', 'tool_idnumbertousernames'));
echo html_writer::tag('p', get_string('credits', 'tool_idnumbertousernames'));

// NOTE: this is JUST csv processing preview, we must not prevent import from here if there is something in the file!!
//       this was intended for validation of csv formatting and encoding, not filtering the data!!!!
//       we definitely must not process the whole file!


// preview table data
$data = array();
$cir->init();
$linenum = 1; //column header is first line
$noerror = true; // Keep status of any error.
while ($linenum <= $previewrows and $fields = $cir->next()) {
    $linenum++;
    $rowcols = array();
    $rowcols['line'] = $linenum;
    foreach($fields as $key => $field) {
        $rowcols[$filecolumns[$key]] = s(trim($field));
    }
    $rowcols['status'] = array();

    if (isset($rowcols['username'])) {
        $stdusername = clean_param($rowcols['username'], PARAM_USERNAME);
        if ($rowcols['username'] !== $stdusername) {
            $rowcols['status'][] = get_string('invalidusernameupload');
        }
        if ($userid = $DB->get_field('user', 'id', array('username'=>$stdusername, 'mnethostid'=>$CFG->mnet_localhost_id))) {
            $rowcols['username'] = html_writer::link(new moodle_url('/user/profile.php', array('id'=>$userid)), $rowcols['username']);
        }
    } else {
        $rowcols['status'][] = get_string('missingusername');
    }

    if (isset($rowcols['email'])) {
        if (!validate_email($rowcols['email'])) {
            $rowcols['status'][] = get_string('invalidemail');
        }

        $select = $DB->sql_like('email', ':email', false, true, false, '|');
        $params = array('email' => $DB->sql_like_escape($rowcols['email'], '|'));
        if ($DB->record_exists_select('user', $select , $params)) {
            $rowcols['status'][] = $stremailduplicate;
        }
    }


    // Check if rowcols have custom profile field with correct data and update error state.
    $rowcols['status'] = implode('<br />', $rowcols['status']);
    $data[] = $rowcols;
}
if ($fields = $cir->next()) {
    $data[] = array_fill(0, count($fields) + 2, '...');
}
$cir->close();

$table = new html_table();
$table->id = "idnumbertousernames_preview";
$table->attributes['class'] = 'generaltable';
$table->tablealign = 'center';
$table->summary = get_string('uploadidnumberspreview', 'tool_idnumbertousernames');
$table->head = array();
$table->data = $data;

$table->head[] = get_string('csvline', 'tool_idnumbertousernames');
foreach ($filecolumns as $column) {
    $table->head[] = $column;
}
$table->head[] = get_string('status');

echo html_writer::tag('div', html_writer::table($table), array('class'=>'flexible-wrap'));

// Print the form if valid values are available
if ($noerror) {
    $mform2->display();
}
echo $OUTPUT->footer();

