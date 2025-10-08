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

defined('MOODLE_INTERNAL') || die();

/**
 * Theme settings for theme_nexo
 *
 * @package   theme_nexo
 * @copyright 2025, You Name <your@email.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// Keep includes from theme_nexo
require(__DIR__ . '/../remui/settings.php');
require_once(__DIR__ . '/classes/admin_settingspage_tabs.php');
require_once($CFG->libdir . '/accesslib.php');
require_once(__DIR__ . '/lib.php');

// Force license validation at settings load time
// Esta línea estaba llamando a theme_nexo_pre_settings_load() que ya no existe
// En su lugar, simplemente cargamos el archivo de autoload de licencia
require_once(__DIR__ . '/classes/license_autoload.php');
theme_nexo_license_autoload();

// Capturar pestañas del tema padre (si existen)
$parent_tabs = null;
if (isset($settings) && method_exists($settings, 'get_tabs')) {
    $parent_tabs = $settings->get_tabs();
}

unset($settings);
$settings = null;

// Crear categoría en "Apariencia"
$ADMIN->add('appearance', new admin_category('theme_nexo', get_string('configtitle', 'theme_nexo')));

// Crear objeto de configuraciones con pestañas
$asettings = new theme_nexo_admin_settingspage_tabs(
    'themesettingnexo',
    get_string('themesettings', 'theme_nexo'),
    'moodle/site:config'
);

if ($ADMIN->fulltree) {
    // Variables comunes
    $a = new stdClass();
    $a->example_banner = (string)$OUTPUT->image_url('example_banner', 'theme_nexo');
    $a->cover_remui = (string)$OUTPUT->image_url('cover_remui', 'theme');
    $a->example_cover1 = (string)$OUTPUT->image_url('login_bg_corp', 'theme');
    $a->example_cover2 = (string)$OUTPUT->image_url('login_bg', 'theme');

    /* =========================================================================
       TAB 1: General Settings
       ========================================================================= */
    $page = new admin_settingpage('ib_theme_generals', get_string('themesettingsgeneral', 'theme_nexo'));

    // Theme info text
    $name = 'theme_nexo/ib_themeinfotext';
    $title = '';
    $description = get_string('themeinfotext', 'theme_nexo');
    $page->add(new admin_setting_heading($name, $title, $description));

    // License activation link
    $licenseactivationurl = new moodle_url('/theme/nexo/apply_license.php');
    $licenseactivationlink = html_writer::link(
        $licenseactivationurl,
        get_string('activatelicense', 'theme_nexo'),
        ['class' => 'btn btn-secondary', 'target' => '_blank']
    );

    $name = 'theme_nexo/ib_licenseactivation';
    $title = get_string('licenseactivation', 'theme_nexo');
    $description = get_string('licenseactivationdesc', 'theme_nexo') . '<br>' . $licenseactivationlink;
    $setting = new admin_setting_heading($name, $title, $description);
    $page->add($setting);

    // --- Notificaciones Generales ---
    $page->add(new admin_setting_heading(
        'theme_nexo/ib_generalnoticeheading',
        get_string('generalnoticemode', 'theme_nexo'),
        ''
    ));

    $name = 'theme_nexo/ib_generalnoticemode';
    $title = get_string('generalnoticemode', 'theme_nexo');
    $description = get_string('generalnoticemodedesc', 'theme_nexo');
    $default = 'off';
    $choices = [
        'off' => get_string('generalnoticemode_off', 'theme_nexo'),
        'info' => get_string('generalnoticemode_info', 'theme_nexo'),
        'danger' => get_string('generalnoticemode_danger', 'theme_nexo')
    ];
    $setting = new admin_setting_configselect($name, $title, $description, $default, $choices);
    $setting->set_updatedcallback('theme_reset_all_caches');
    $page->add($setting);

    $name = 'theme_nexo/ib_generalnotice';
    $title = get_string('generalnotice', 'theme_nexo');
    $description = get_string('generalnoticedesc', 'theme_nexo');
    $default = '<strong>Estamos trabajando</strong> para mejorar...';
    $setting = new admin_setting_confightmleditor($name, $title, $description, $default);
    $setting->set_updatedcallback('theme_reset_all_caches');
    $page->add($setting);

    // --- Chat Settings ---
    $page->add(new admin_setting_heading(
        'theme_nexo/ib_chatheading',
        get_string('themesettingschat', 'theme_nexo'),
        get_string('themesettingschatdesc', 'theme_nexo')
    ));

    $name = 'theme_nexo/ib_enable_chat';
    $title = get_string('enable_chat', 'theme_nexo');
    $description = get_string('enable_chatdesc', 'theme_nexo');
    $default = 0;
    $setting = new admin_setting_configcheckbox($name, $title, $description, $default);
    $setting->set_updatedcallback('theme_reset_all_caches');
    $page->add($setting);

    $name = 'theme_nexo/ib_tawkto_embed_url';
    $title = get_string('tawkto_embed_url', 'theme_nexo');
    $description = get_string('tawkto_embed_urldesc', 'theme_nexo');
    $default = '';
    $setting = new admin_setting_configtext($name, $title, $description, $default);
    $setting->set_updatedcallback('theme_reset_all_caches');
    $page->add($setting);

    // --- Content Protection Settings ---
    $page->add(new admin_setting_heading(
        'theme_nexo/ib_contentprotectionheading',
        get_string('themesettingscopypaste', 'theme_nexo'),
        get_string('themesettingscopypaste_desc', 'theme_nexo')
    ));

    $name = 'theme_nexo/ib_copypaste_prevention';
    $title = get_string('copypaste_prevention', 'theme_nexo');
    $description = get_string('copypaste_preventiondesc', 'theme_nexo');
    $default = 0;
    $setting = new admin_setting_configcheckbox($name, $title, $description, $default);
    $setting->set_updatedcallback('theme_reset_all_caches');
    $page->add($setting);

    // Roles para protección
    $roles = role_get_names(null, ROLENAME_ORIGINAL);
    $roles_array = [];
    foreach ($roles as $role) {
        $roles_array[$role->id] = $role->localname;
    }

    $name = 'theme_nexo/ib_copypaste_roles';
    $title = get_string('copypaste_roles', 'theme_nexo');
    $description = get_string('copypaste_rolesdesc', 'theme_nexo');
    $default = [5]; // Rol de estudiante por defecto
    $setting = new admin_setting_configmultiselect($name, $title, $description, $default, $roles_array);
    $setting->set_updatedcallback('theme_reset_all_caches');
    $page->add($setting);

    $asettings->add_tab($page);

    /* =========================================================================
       TAB 2: Login Page Settings
       ========================================================================= */
    $page = new admin_settingpage('ib_theme_login', get_string('themesettingslogin', 'theme_nexo'));

    // Carousel Settings
    $page->add(new admin_setting_heading(
        'theme_nexo/ib_carousel',
        get_string('carouselsettings', 'theme_nexo'),
        get_string('carouselsettings_desc', 'theme_nexo')
    ));

    // Número de slides
    $name = 'theme_nexo/ib_login_numberofslides';
    $title = get_string('numberofslides', 'theme_nexo');
    $description = get_string('numberofslides_desc', 'theme_nexo');
    $choices = range(1, 10);
    $page->add(new admin_setting_configselect($name, $title, $description, 1, array_combine($choices, $choices)));

    // Settings para cada slide
    $numslides = get_config('theme_nexo', 'ib_login_numberofslides') ?: 1;
    for ($i = 1; $i <= $numslides; $i++) {
        // Título del slide
        $name = 'theme_nexo/ib_login_slidetitle' . $i;
        $title = get_string('slidetitle', 'theme_nexo', $i);
        $description = get_string('slidetitle_desc', 'theme_nexo', $i);
        $page->add(new admin_setting_configtext($name, $title, $description, ''));

        // Imagen del slide
        $name = 'theme_nexo/ib_login_slideimage' . $i;
        $title = get_string('slideimage', 'theme_nexo', $i);
        $description = get_string('slideimage_desc', 'theme_nexo', $i);
        $setting = new admin_setting_configstoredfile($name, $title, $description, 'ib_login_slideimage' . $i, 0, [
            'subdirs' => 0,
            'accepted_types' => ['web_image']
        ]);
        $setting->set_updatedcallback('theme_reset_all_caches');
        $page->add($setting);

        // URL del slide
        $name = 'theme_nexo/ib_login_slideurl' . $i;
        $title = get_string('slideurl', 'theme_nexo', $i);
        $description = get_string('slideurldesc', 'theme_nexo', $i);
        $page->add(new admin_setting_configtext($name, $title, $description, ''));
    }

    // Intervalo del carrusel
    $name = 'theme_nexo/ib_login_carouselinterval';
    $title = get_string('carouselinterval', 'theme_nexo');
    $description = get_string('carouselintervaldesc', 'theme_nexo');
    $setting = new admin_setting_configtext($name, $title, $description, '5000');
    $setting->set_updatedcallback('theme_reset_all_caches');
    $page->add($setting);

    $asettings->add_tab($page);

    /* =========================================================================
       TAB 3: Dashboard Settings
       ========================================================================= */
    $page = new admin_settingpage('ib_theme_dashboard', get_string('dashboardsettings', 'theme_nexo'));

    // Personal Area Header Settings
    $page->add(new admin_setting_heading(
        'theme_nexo/ib_personalareaheading',
        get_string('showpersonalareaheader', 'theme_nexo'),
        ''
    ));

    // Toggle de visibilidad del Personal Area Header
    $name = 'theme_nexo/ib_showpersonalareaheader';
    $title = get_string('showpersonalareaheader', 'theme_nexo');
    $description = get_string('showpersonalareaheader_desc', 'theme_nexo');
    $default = 0;
    $choices = [
        0 => get_string('hide', 'theme_nexo'),
        1 => get_string('show', 'theme_nexo')
    ];
    $setting = new admin_setting_configselect($name, $title, $description, $default, $choices);
    $setting->set_updatedcallback('theme_reset_all_caches');
    $page->add($setting);

    // Imagen del Personal Area Header
    $name = 'theme_nexo/ib_personalareaheader';
    $title = get_string('personalareaheader', 'theme_nexo');
    $description = get_string('personalareaheaderdesc', 'theme_nexo', $a);
    $setting = new admin_setting_configstoredfile($name, $title, $description, 'ib_personalareaheader', 0, [
        'subdirs' => 0,
        'accepted_types' => 'web_image'
    ]);
    $setting->set_updatedcallback('theme_reset_all_caches');
    $page->add($setting);

    // My Courses Header Settings
    $page->add(new admin_setting_heading(
        'theme_nexo/ib_mycoursesheading',
        get_string('showmycoursesheader', 'theme_nexo'),
        ''
    ));

    // Toggle de visibilidad del My Courses Header
    $name = 'theme_nexo/ib_showmycoursesheader';
    $title = get_string('showmycoursesheader', 'theme_nexo');
    $description = get_string('showmycoursesheader_desc', 'theme_nexo');
    $default = 0;
    $choices = [
        0 => get_string('hide', 'theme_nexo'),
        1 => get_string('show', 'theme_nexo')
    ];
    $setting = new admin_setting_configselect($name, $title, $description, $default, $choices);
    $setting->set_updatedcallback('theme_reset_all_caches');
    $page->add($setting);

    // Imagen del My Courses Header
    $name = 'theme_nexo/ib_mycoursesheader';
    $title = get_string('mycoursesheader', 'theme_nexo');
    $description = get_string('mycoursesheaderdesc', 'theme_nexo', $a);
    $setting = new admin_setting_configstoredfile($name, $title, $description, 'ib_mycoursesheader', 0, [
        'subdirs' => 0,
        'accepted_types' => 'web_image'
    ]);
    $setting->set_updatedcallback('theme_reset_all_caches');
    $page->add($setting);
    
    // Hide/Show frontpage sections
    $name = 'theme_nexo/ib_hidefrontpagesections';
    $title = get_string('hidefrontpagesections', 'theme_nexo');
    $description = get_string('hidefrontpagesections_desc', 'theme_nexo');
    $choices = [
        '0' => get_string('show', 'theme_nexo'),
        '1' => get_string('hide', 'theme_nexo')
    ];
    $page->add(new admin_setting_configselect($name, $title, $description, 0, $choices));

    $asettings->add_tab($page);

    /* =========================================================================
       TAB 4: Footer Settings
       ========================================================================= */
    $page = new admin_settingpage('ib_theme_footer', get_string('footersettings', 'theme_nexo'));

    // Visibilidad del Footer
    $name = 'theme_nexo/ib_hidefootersections';
    $title = get_string('hidefootersections', 'theme_nexo');
    $description = get_string('hidefootersections_desc', 'theme_nexo');
    $default = 0;
    $choices = [
        0 => get_string('show', 'theme_nexo'),
        1 => get_string('hide', 'theme_nexo')
    ];
    $setting = new admin_setting_configselect($name, $title, $description, $default, $choices);
    $page->add($setting);

    // About Section
    $page->add(new admin_setting_heading(
        'theme_nexo/ib_footeraboutheading',
        get_string('abouttitle', 'theme_nexo'),
        ''
    ));

    $name = 'theme_nexo/ib_abouttitle';
    $title = get_string('abouttitle', 'theme_nexo');
    $description = get_string('abouttitledesc', 'theme_nexo');
    $default = get_string('abouttitle_default', 'theme_nexo');
    $setting = new admin_setting_configtext($name, $title, $description, $default);
    $setting->set_updatedcallback('theme_reset_all_caches');
    $page->add($setting);

    $name = 'theme_nexo/ib_abouttext';
    $title = get_string('abouttext', 'theme_nexo');
    $description = get_string('abouttextdesc', 'theme_nexo');
    $default = get_string('abouttext_default', 'theme_nexo');
    $setting = new admin_setting_confightmleditor($name, $title, $description, $default);
    $setting->set_updatedcallback('theme_reset_all_caches');
    $page->add($setting);

    $asettings->add_tab($page);

    // Si existen pestañas del tema padre, combinarlas
    if ($parent_tabs !== null) {
        $all_tabs = array_merge($asettings->get_tabs(), $parent_tabs);
        $asettings->set_tabs($all_tabs);
    }
}

// Agregar la página de configuraciones a la categoría de apariencia
$ADMIN->add('theme_nexo', $asettings);