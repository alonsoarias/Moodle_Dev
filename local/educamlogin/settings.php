<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

defined('MOODLE_INTERNAL') || die();
require_once(__DIR__ . '/lib.php');

if ($hassiteconfig) {
    $settings = new admin_settingpage('local_educamlogin', get_string('pluginname', 'local_educamlogin'));

    // ==================== GENERAL ====================
    $settings->add(new admin_setting_heading(
        'local_educamlogin/general_heading',
        get_string('general_heading', 'local_educamlogin'),
        ''
    ));

    $enabledsetting = new admin_setting_configcheckbox(
        'local_educamlogin/ed_enabled',
        get_string('enabled', 'local_educamlogin'),
        get_string('enabled_desc', 'local_educamlogin'),
        1
    );
    $enabledsetting->set_updatedcallback('local_educamlogin_update_alternatelogin');
    $settings->add($enabledsetting);

    // ==================== LAYOUT SELECTION ====================
    $settings->add(new admin_setting_heading(
        'local_educamlogin/layout_heading',
        get_string('layout_heading', 'local_educamlogin'),
        get_string('layout_heading_desc', 'local_educamlogin')
    ));

    $layout_options = array(
        'layout1' => get_string('layout1', 'local_educamlogin'),
        'layout2' => get_string('layout2', 'local_educamlogin')
    );

    $settings->add(new admin_setting_configselect(
        'local_educamlogin/ed_layout',
        get_string('layout', 'local_educamlogin'),
        get_string('layout_desc', 'local_educamlogin'),
        'layout1',
        $layout_options
    ));

    // ==================== IMAGES ====================
    $settings->add(new admin_setting_heading(
        'local_educamlogin/images_heading',
        get_string('images_heading', 'local_educamlogin'),
        get_string('images_heading_desc', 'local_educamlogin')
    ));

    // Background image (for both layouts, but critical for layout2)
    $settings->add(new admin_setting_configstoredfile(
        'local_educamlogin/ed_background_image',
        get_string('background_image', 'local_educamlogin'),
        get_string('background_image_desc', 'local_educamlogin'),
        'ed_background_image',
        0,
        array('maxfiles' => 1, 'accepted_types' => array('.jpg', '.jpeg', '.png', '.webp', '.gif'))
    ));

    // Team/welcome photo (only for layout1)
    $settings->add(new admin_setting_configstoredfile(
        'local_educamlogin/ed_team_photo',
        get_string('team_photo', 'local_educamlogin'),
        get_string('team_photo_desc', 'local_educamlogin'),
        'ed_team_photo',
        0,
        array('maxfiles' => 1, 'accepted_types' => array('.jpg', '.jpeg', '.png', '.webp'))
    ));

    // Logo Educam
    $settings->add(new admin_setting_configstoredfile(
        'local_educamlogin/ed_logo_educam',
        get_string('logo_educam', 'local_educamlogin'),
        get_string('logo_educam_desc', 'local_educamlogin'),
        'ed_logo_educam',
        0,
        array('maxfiles' => 1, 'accepted_types' => array('.png', '.svg', '.jpg', '.webp'))
    ));

    // Logo Americas
    $settings->add(new admin_setting_configstoredfile(
        'local_educamlogin/ed_logo_americas',
        get_string('logo_americas', 'local_educamlogin'),
        get_string('logo_americas_desc', 'local_educamlogin'),
        'ed_logo_americas',
        0,
        array('maxfiles' => 1, 'accepted_types' => array('.png', '.svg', '.jpg', '.webp'))
    ));

    // ==================== COLORS ====================
    $settings->add(new admin_setting_heading(
        'local_educamlogin/colors_heading',
        get_string('colors_heading', 'local_educamlogin'),
        get_string('colors_heading_desc', 'local_educamlogin')
    ));

    // Background color (for layout1)
    $settings->add(new admin_setting_configcolourpicker(
        'local_educamlogin/ed_bg_color',
        get_string('bg_color', 'local_educamlogin'),
        get_string('bg_color_desc', 'local_educamlogin'),
        '#edf7fb'
    ));

    // Panel top color
    $settings->add(new admin_setting_configcolourpicker(
        'local_educamlogin/ed_panel_top',
        get_string('panel_top', 'local_educamlogin'),
        get_string('panel_top_desc', 'local_educamlogin'),
        '#4a64c8'
    ));

    // Panel mid color
    $settings->add(new admin_setting_configcolourpicker(
        'local_educamlogin/ed_panel_mid',
        get_string('panel_mid', 'local_educamlogin'),
        get_string('panel_mid_desc', 'local_educamlogin'),
        '#2d448e'
    ));

    // Panel bottom color
    $settings->add(new admin_setting_configcolourpicker(
        'local_educamlogin/ed_panel_bottom',
        get_string('panel_bottom', 'local_educamlogin'),
        get_string('panel_bottom_desc', 'local_educamlogin'),
        '#12213e'
    ));

    // Field background color
    $settings->add(new admin_setting_configcolourpicker(
        'local_educamlogin/ed_field_bg',
        get_string('field_bg', 'local_educamlogin'),
        get_string('field_bg_desc', 'local_educamlogin'),
        '#e8eefb'
    ));

    // Link color
    $settings->add(new admin_setting_configcolourpicker(
        'local_educamlogin/ed_link_color',
        get_string('link_color', 'local_educamlogin'),
        get_string('link_color_desc', 'local_educamlogin'),
        '#cfe0ff'
    ));

    // Button color
    $settings->add(new admin_setting_configcolourpicker(
        'local_educamlogin/ed_btn_color',
        get_string('btn_color', 'local_educamlogin'),
        get_string('btn_color_desc', 'local_educamlogin'),
        '#c0328a'
    ));

    // Button hover color
    $settings->add(new admin_setting_configcolourpicker(
        'local_educamlogin/ed_btn_hover',
        get_string('btn_hover', 'local_educamlogin'),
        get_string('btn_hover_desc', 'local_educamlogin'),
        '#a52877'
    ));

    // ==================== TEXTS ====================
    $settings->add(new admin_setting_heading(
        'local_educamlogin/texts_heading',
        get_string('texts_heading', 'local_educamlogin'),
        ''
    ));

    $settings->add(new admin_setting_configtext(
        'local_educamlogin/ed_welcome_text',
        get_string('welcome_text', 'local_educamlogin'),
        get_string('welcome_text_desc', 'local_educamlogin'),
        'Bienvenido',
        PARAM_TEXT
    ));

    $settings->add(new admin_setting_configtextarea(
        'local_educamlogin/ed_copyright_text',
        get_string('copyright_text', 'local_educamlogin'),
        get_string('copyright_text_desc', 'local_educamlogin'),
        '© 2020 La plataforma Educam Virtual es una plataforma de capacitación que pertenece a Americas Business Process.',
        PARAM_TEXT
    ));

    // ==================== reCAPTCHA ====================
    $settings->add(new admin_setting_heading(
        'local_educamlogin/recaptcha_heading',
        get_string('recaptcha_heading', 'local_educamlogin'),
        get_string('recaptcha_heading_desc', 'local_educamlogin')
    ));

    $settings->add(new admin_setting_configtext(
        'local_educamlogin/ed_recaptcha_sitekey',
        get_string('recaptcha_sitekey', 'local_educamlogin'),
        get_string('recaptcha_sitekey_desc', 'local_educamlogin'),
        '',
        PARAM_TEXT
    ));

    $settings->add(new admin_setting_configtext(
        'local_educamlogin/ed_recaptcha_secretkey',
        get_string('recaptcha_secretkey', 'local_educamlogin'),
        get_string('recaptcha_secretkey_desc', 'local_educamlogin'),
        '',
        PARAM_TEXT
    ));

    $ADMIN->add('localplugins', $settings);
    local_educamlogin_update_alternatelogin();
}
