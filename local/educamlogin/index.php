<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

require_once('../../config.php');
require_once($CFG->libdir.'/authlib.php');
require_once('lib.php');

// Check if plugin is enabled
$enabled = local_educamlogin_get_config('enabled', 1);
if (!$enabled) {
    redirect(new moodle_url('/login/index.php'));
}

// Check if user is already logged in
if (isloggedin() && !isguestuser()) {
    redirect(new moodle_url('/'));
}

$PAGE->set_context(context_system::instance());
$PAGE->set_url('/local/educamlogin/index.php');
$PAGE->set_pagelayout('login');
$PAGE->set_title(get_string('pluginname', 'local_educamlogin'));

// Get layout selection
$layout = local_educamlogin_get_config('layout', 'layout1');

// Handle form submission
$errormsg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = optional_param('username', '', PARAM_USERNAME);
    $password = optional_param('password', '', PARAM_RAW);
    $recaptcharesponse = optional_param('g-recaptcha-response', '', PARAM_RAW);
    
    // Verify reCAPTCHA if configured
    $recaptcha_sitekey = local_educamlogin_get_config('recaptcha_sitekey');
    if (!empty($recaptcha_sitekey) && !local_educamlogin_verify_recaptcha($recaptcharesponse)) {
        $errormsg = get_string('recaptchaerror', 'local_educamlogin');
    } else {
        // Authenticate user
        $user = authenticate_user_login($username, $password);
        
        if ($user) {
            complete_user_login($user);
            
            // Redirect to wantsurl or default page
            $wantsurl = optional_param('wantsurl', '', PARAM_LOCALURL);
            if (!empty($wantsurl)) {
                redirect(new moodle_url($wantsurl));
            } else {
                redirect(new moodle_url('/'));
            }
        } else {
            $errormsg = get_string('loginerror', 'local_educamlogin');
        }
    }
}

// Get the wantsurl parameter
$wantsurl = optional_param('wantsurl', '', PARAM_LOCALURL);

// Prepare template context
$context = local_educamlogin_prepare_context($layout, $errormsg, $wantsurl);

// Render template using Moodle's standard method
$OUTPUT->header();

// Use Moodle's render_from_template method
$templatename = 'local_educamlogin/' . $layout;
echo $OUTPUT->render_from_template($templatename, $context);

$OUTPUT->footer();