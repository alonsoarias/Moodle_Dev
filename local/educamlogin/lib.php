<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

defined('MOODLE_INTERNAL') || die();

/**
 * Synchronise Moodle's alternate login URL with the plugin setting.
 */
function local_educamlogin_update_alternatelogin() {
    $enabled = (int)get_config('local_educamlogin', 'ed_enabled');
    $targeturl = (new moodle_url('/local/educamlogin/index.php'))->out(false);
    $currentalt = get_config('moodle', 'alternateloginurl');
    $backup = get_config('local_educamlogin', 'ed_alternatelogin_backup');

    if ($enabled) {
        if (!empty($currentalt) && $currentalt !== $targeturl && empty($backup)) {
            set_config('ed_alternatelogin_backup', $currentalt, 'local_educamlogin');
        }

        if ($currentalt !== $targeturl) {
            set_config('alternateloginurl', $targeturl);
        }
    } else {
        if (!empty($backup)) {
            set_config('alternateloginurl', $backup);
        } else if ($currentalt === $targeturl) {
            unset_config('alternateloginurl');
        }

        unset_config('ed_alternatelogin_backup', 'local_educamlogin');
    }
}

/**
 * Get the URL for a stored file
 * ONLY returns URLs for files uploaded via settings.php with ed_ prefix
 *
 * @param string $filearea The file area (without ed_ prefix)
 * @return string|null Returns URL if file exists, null otherwise
 */
function local_educamlogin_get_file_url($filearea) {
    $fs = get_file_storage();
    $context = context_system::instance();
    
    // Prepend ed_ prefix to filearea
    $full_filearea = 'ed_' . $filearea;
    
    $files = $fs->get_area_files($context->id, 'local_educamlogin', $full_filearea, 0, 'itemid', false);
    
    if (count($files) > 0) {
        $file = reset($files);
        return moodle_url::make_pluginfile_url(
            $file->get_contextid(),
            $file->get_component(),
            $file->get_filearea(),
            $file->get_itemid(),
            $file->get_filepath(),
            $file->get_filename()
        )->out();
    }
    
    return null;
}

/**
 * Get fallback image URL
 *
 * @param string $type Type of fallback (team, background, logo_educam, logo_americas)
 * @return string Dummy image URL
 */
function local_educamlogin_get_fallback_image($type) {
    $fallbacks = array(
        'team' => 'https://images.unsplash.com/photo-1557264337-e8a93017fe92?q=80&w=1600&auto=format&fit=crop',
        'background' => 'https://images.unsplash.com/photo-1522075469751-3a6694fb2f61?q=80&w=2000&auto=format&fit=crop',
        'logo_educam' => 'https://dummyimage.com/210x66/ffffff/3a57c4&text=educam',
        'logo_americas' => 'https://dummyimage.com/180x40/ffffff/1a2d66&text=americas'
    );
    
    return isset($fallbacks[$type]) ? $fallbacks[$type] : '';
}

/**
 * Get configuration value with ed_ prefix
 *
 * @param string $name Config name (without ed_ prefix)
 * @param mixed $default Default value
 * @return mixed Config value
 */
function local_educamlogin_get_config($name, $default = null) {
    $value = get_config('local_educamlogin', 'ed_' . $name);

    if ($value === false || $value === null) {
        return $default;
    }

    return $value;
}

/**
 * Check whether the current request IP is whitelisted to bypass reCAPTCHA.
 *
 * @param string|null $ip Optional IP address to check. Defaults to the remote address detected by Moodle.
 * @return bool True when the IP is in the whitelist.
 */
function local_educamlogin_is_recaptcha_ip_whitelisted(?string $ip = null): bool {
    global $CFG;

    $whitelist = (string)local_educamlogin_get_config('recaptcha_whitelist', '');
    if (trim($whitelist) === '') {
        return false;
    }

    if ($ip === null) {
        $ip = getremoteaddr(null);
    }

    if (empty($ip)) {
        return false;
    }

    $entries = preg_split('/[\r\n,]+/', $whitelist);
    $normalized = array();

    foreach ($entries as $entry) {
        $entry = trim($entry);
        if ($entry === '') {
            continue;
        }

        $parts = explode('#', $entry, 2);
        $candidate = trim($parts[0]);
        if ($candidate === '') {
            continue;
        }

        $normalised = \core\ip_utils::normalize_internet_address($candidate);
        if ($normalised === '') {
            continue;
        }

        $normalized[] = $normalised;
    }

    if (empty($normalized)) {
        return false;
    }

    require_once($CFG->libdir . '/ipv6lib.php');

    $list = implode("\n", $normalized);

    return \core\ip_utils::is_ip_in_subnet_list($ip, $list);
}

/**
 * Verify Google reCAPTCHA response.
 *
 * @param string $response reCAPTCHA response token
 * @param string $action Expected action name
 * @param float $threshold Minimum acceptable score for v3 (0-1)
 * @param string|null $errordetail Failure detail (by reference)
 * @return bool True if verification passed
 */
function local_educamlogin_verify_recaptcha($response, $action = null, $threshold = null, &$errordetail = null) {
    global $CFG;

    $errordetail = null;
    $secretkey = trim((string)local_educamlogin_get_config('recaptcha_secretkey'));

    if ($secretkey === '') {
        $errordetail = 'missing secret key';
        debugging('local_educamlogin: reCAPTCHA secret key is not configured.', DEBUG_DEVELOPER);
        return false;
    }

    if (empty($response)) {
        $errordetail = 'empty response token';
        debugging('local_educamlogin: Empty reCAPTCHA response token received.', DEBUG_DEVELOPER);
        return false;
    }

    if ($action === null) {
        $action = trim((string)local_educamlogin_get_config('recaptcha_action', 'login'));
    }

    if ($threshold === null) {
        $thresholdconfig = local_educamlogin_get_config('recaptcha_threshold', 0.5);
        $threshold = is_numeric($thresholdconfig) ? (float)$thresholdconfig : 0.5;
    } else {
        $threshold = (float)$threshold;
    }

    if ($threshold < 0) {
        $threshold = 0.0;
    } else if ($threshold > 1) {
        $threshold = 1.0;
    }

    require_once($CFG->libdir . '/filelib.php');

    $verifyurl = 'https://www.google.com/recaptcha/api/siteverify';
    $postdata = array(
        'secret' => $secretkey,
        'response' => $response,
    );

    if (!empty($_SERVER['REMOTE_ADDR'])) {
        $postdata['remoteip'] = $_SERVER['REMOTE_ADDR'];
    }

    $curl = new curl(array('proxy' => true));
    $options = array(
        'CURLOPT_TIMEOUT' => 10,
        'CURLOPT_CONNECTTIMEOUT' => 5,
    );

    try {
        $result = $curl->post($verifyurl, $postdata, $options);
    } catch (Exception $e) {
        $errordetail = 'exception: ' . $e->getMessage();
        debugging('local_educamlogin: Error contacting reCAPTCHA service - ' . $e->getMessage(), DEBUG_DEVELOPER);
        return false;
    }

    if ($result === false) {
        $errordetail = 'http request returned false';
        debugging('local_educamlogin: Empty response from reCAPTCHA service.', DEBUG_DEVELOPER);
        return false;
    }

    if ($curl->get_errno()) {
        $errordetail = 'http error: ' . $curl->error;
        debugging('local_educamlogin: HTTP error contacting reCAPTCHA service - ' . $curl->error, DEBUG_DEVELOPER);
        return false;
    }

    $responsedata = json_decode($result);

    if (json_last_error() !== JSON_ERROR_NONE) {
        $errordetail = 'json decode error: ' . json_last_error_msg();
        debugging('local_educamlogin: Failed to decode reCAPTCHA response - ' . $errordetail, DEBUG_DEVELOPER);
        return false;
    }

    if (empty($responsedata) || empty($responsedata->success)) {
        $errorcodes = array();
        if (!empty($responsedata->{'error-codes'})) {
            if (is_array($responsedata->{'error-codes'})) {
                $errorcodes = $responsedata->{'error-codes'};
            } else if (is_string($responsedata->{'error-codes'})) {
                $errorcodes = array($responsedata->{'error-codes'});
            }
        }
        $errordetail = empty($errorcodes) ? 'invalid response payload' : ('api errors: ' . implode(', ', $errorcodes));
        debugging('local_educamlogin: reCAPTCHA verification failed - ' . $errordetail, DEBUG_DEVELOPER);
        return false;
    }

    if ($action !== '' && isset($responsedata->action) && $responsedata->action !== $action) {
        $errordetail = 'action mismatch: expected ' . $action . ', got ' . $responsedata->action;
        debugging('local_educamlogin: reCAPTCHA action mismatch - expected "' . $action . '" but received "' .
            $responsedata->action . '".', DEBUG_DEVELOPER);
        return false;
    }

    if (isset($responsedata->score)) {
        $score = (float)$responsedata->score;
        if ($score < $threshold) {
            $errordetail = 'score below threshold: ' . $score;
            debugging('local_educamlogin: reCAPTCHA score ' . $score . ' is below threshold ' .
                $threshold . '.', DEBUG_DEVELOPER);
            return false;
        }
    }

    if (!empty($responsedata->{'error-codes'})) {
        $codes = $responsedata->{'error-codes'};
        if (!is_array($codes)) {
            $codes = array($codes);
        }
        $errordetail = 'api errors: ' . implode(', ', $codes);
        debugging('local_educamlogin: reCAPTCHA returned error codes - ' . $errordetail, DEBUG_DEVELOPER);
        return false;
    }

    if (!empty($responsedata->success)) {
        $summary = array();
        if (isset($responsedata->score)) {
            $summary[] = 'score ' . (float)$responsedata->score;
        }
        if (isset($responsedata->action)) {
            $summary[] = 'action "' . $responsedata->action . '"';
        }
        if (isset($responsedata->hostname)) {
            $summary[] = 'host ' . $responsedata->hostname;
        }
        if (!empty($responsedata->{'challenge_ts'})) {
            $summary[] = 'challenge ' . $responsedata->{'challenge_ts'};
        }
        if (!empty($summary)) {
            debugging('local_educamlogin: reCAPTCHA verification succeeded (' . implode(', ', $summary) . ').', DEBUG_DEVELOPER);
        }
    }

    return true;
}

/**
 * Serves the plugin files
 * Supports multiple image formats
 *
 * @param stdClass $course
 * @param stdClass $cm
 * @param context $context
 * @param string $filearea
 * @param array $args
 * @param bool $forcedownload
 * @param array $options
 * @return bool
 */
function local_educamlogin_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload, array $options = array()) {
    if ($context->contextlevel != CONTEXT_SYSTEM) {
        return false;
    }
    
    // List of valid file areas with ed_ prefix
    $valid_areas = array(
        'ed_background_image',
        'ed_team_photo',
        'ed_logo_educam',
        'ed_logo_americas'
    );
    
    if (!in_array($filearea, $valid_areas)) {
        return false;
    }
    
    $fs = get_file_storage();
    $filename = array_pop($args);
    $filepath = '/';
    $itemid = 0;
    
    $file = $fs->get_file($context->id, 'local_educamlogin', $filearea, $itemid, $filepath, $filename);
    
    if (!$file) {
        return false;
    }
    
    send_stored_file($file, 86400, 0, $forcedownload, $options);
}

/**
 * Prepare template context with all configuration values
 *
 * @param string $layout Layout name (layout1 or layout2)
 * @param string $errormsg Error message if any
 * @param string $wantsurl Redirect URL after login
 * @return array Template context
 */
function local_educamlogin_prepare_context($layout, $errormsg = '', $wantsurl = '') {
    global $CFG;
    
    // Get images - check if custom images exist
    $team_photo_custom = local_educamlogin_get_file_url('team_photo');
    $background_image_custom = local_educamlogin_get_file_url('background_image');
    $logo_educam_custom = local_educamlogin_get_file_url('logo_educam');
    $logo_americas_custom = local_educamlogin_get_file_url('logo_americas');
    
    // Use custom or fallback images
    $team_photo = $team_photo_custom ?: local_educamlogin_get_fallback_image('team');
    $background_image = $background_image_custom ?: local_educamlogin_get_fallback_image('background');
    $logo_educam = $logo_educam_custom ?: local_educamlogin_get_fallback_image('logo_educam');
    $logo_americas = $logo_americas_custom ?: local_educamlogin_get_fallback_image('logo_americas');
    
    // Check if background image was uploaded (not using fallback)
    $has_background_image = !empty($background_image_custom);
    
    // Get colors
    $colors = array(
        'bg_color' => local_educamlogin_get_config('bg_color', '#edf7fb'),
        'panel_top' => local_educamlogin_get_config('panel_top', '#4a64c8'),
        'panel_mid' => local_educamlogin_get_config('panel_mid', '#2d448e'),
        'panel_bottom' => local_educamlogin_get_config('panel_bottom', '#12213e'),
        'field_bg' => local_educamlogin_get_config('field_bg', '#e8eefb'),
        'link_color' => local_educamlogin_get_config('link_color', '#cfe0ff'),
        'btn_color' => local_educamlogin_get_config('btn_color', '#c0328a'),
        'btn_hover' => local_educamlogin_get_config('btn_hover', '#a52877')
    );
    
    // Get texts
    $welcome_text = local_educamlogin_get_config('welcome_text', 'Bienvenido');
    $copyright_text = local_educamlogin_get_config('copyright_text', 
        '© 2020 La plataforma Educam Virtual es una plataforma de capacitación que pertenece a Americas Business Process.');
    
    // Get forgot password link.
    $forgotpasswordurl = get_config('local_educamlogin', 'ed_forgotpassword_url');
    if ($forgotpasswordurl === false) {
        $forgotpasswordurl = $CFG->wwwroot . '/login/forgot_password.php';
    } else {
        $forgotpasswordurl = trim($forgotpasswordurl);
    }

    if ($forgotpasswordurl !== '') {
        $forgotpasswordurl = clean_param($forgotpasswordurl, PARAM_URL);
    }
    $hasforgotpassword = !empty($forgotpasswordurl);

    // Get reCAPTCHA
    $recaptcha_sitekey = local_educamlogin_get_config('recaptcha_sitekey');
    $recaptchabypass = local_educamlogin_is_recaptcha_ip_whitelisted();
    $has_recaptcha = !empty($recaptcha_sitekey) && !$recaptchabypass;
    $recaptchaaction = trim((string)local_educamlogin_get_config('recaptcha_action', 'login'));
    if ($recaptchaaction === '') {
        $recaptchaaction = 'login';
    }
    
    // Build context
    $context = array(
        'layout' => $layout,
        'wwwroot' => $CFG->wwwroot,
        'sesskey' => sesskey(),
        'errormsg' => $errormsg,
        'has_error' => !empty($errormsg),
        'wantsurl' => $wantsurl,
        'has_wantsurl' => !empty($wantsurl),
        
        // Images
        'team_photo' => $team_photo,
        'background_image' => $background_image,
        'logo_educam' => $logo_educam,
        'logo_americas' => $logo_americas,
        
        // Background image flag
        'has_background_image' => $has_background_image,
        
        // Colors
        'bg_color' => $colors['bg_color'],
        'panel_top' => $colors['panel_top'],
        'panel_mid' => $colors['panel_mid'],
        'panel_bottom' => $colors['panel_bottom'],
        'field_bg' => $colors['field_bg'],
        'link_color' => $colors['link_color'],
        'btn_color' => $colors['btn_color'],
        'btn_hover' => $colors['btn_hover'],

        // Texts
        'welcome_text' => $welcome_text,
        'copyright_text' => $copyright_text,
        'forgotpassword_url' => $forgotpasswordurl,
        'has_forgotpassword' => $hasforgotpassword,

        // reCAPTCHA
        'recaptcha_sitekey' => $recaptcha_sitekey,
        'has_recaptcha' => $has_recaptcha,
        'recaptcha_action' => $recaptchaaction,

        // Strings
        'str_username' => get_string('username', 'local_educamlogin'),
        'str_password' => get_string('password', 'local_educamlogin'),
        'str_forgotpassword' => get_string('forgotpassword', 'local_educamlogin'),
        'str_login' => get_string('login', 'local_educamlogin'),
        'str_showpassword' => get_string('showpassword', 'local_educamlogin'),
        'str_hidepassword' => get_string('hidepassword', 'local_educamlogin'),
        'str_skiptoform' => get_string('skiptoform', 'local_educamlogin'),
        'str_logininstructions' => get_string('logininstructions', 'local_educamlogin'),

        // CSS file path
        'css_url' => new moodle_url('/local/educamlogin/styles/' . $layout . '.css'),
    );
    
    return $context;
}