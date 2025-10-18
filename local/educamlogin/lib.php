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
    return get_config('local_educamlogin', 'ed_' . $name) ?: $default;
}

/**
 * Verify Google reCAPTCHA response
 *
 * @param string $response reCAPTCHA response token
 * @return bool True if verification passed
 */
function local_educamlogin_verify_recaptcha($response) {
    $secretkey = local_educamlogin_get_config('recaptcha_secretkey');
    
    if (empty($secretkey) || empty($response)) {
        return false;
    }
    
    $verifyurl = 'https://www.google.com/recaptcha/api/siteverify';
    $data = array(
        'secret' => $secretkey,
        'response' => $response,
        'remoteip' => $_SERVER['REMOTE_ADDR']
    );
    
    $options = array(
        'http' => array(
            'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
            'method'  => 'POST',
            'content' => http_build_query($data)
        )
    );
    
    $context  = stream_context_create($options);
    $result = file_get_contents($verifyurl, false, $context);
    
    if ($result === FALSE) {
        return false;
    }
    
    $responsedata = json_decode($result);
    return $responsedata->success;
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
    
    // Get reCAPTCHA
    $recaptcha_sitekey = local_educamlogin_get_config('recaptcha_sitekey');
    $has_recaptcha = !empty($recaptcha_sitekey);
    
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
        
        // reCAPTCHA
        'recaptcha_sitekey' => $recaptcha_sitekey,
        'has_recaptcha' => $has_recaptcha,
        
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