<?php
// This file is part of Moodle - http://moodle.org/
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
 * Theme settings configuration for theme_compecer.
 *
 * @package    theme_compecer
 * @copyright  2024 Pedro Arias <soporte@ingeweb.co>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__ . '/../moove/settings.php');
require_once(__DIR__ . '/classes/admin_settingspage_tabs.php');
require_once(__DIR__ . '/lib.php');

// Capturar pestañas del tema padre (si existen).
$parent_tabs = null;
if (isset($settings) && method_exists($settings, 'get_tabs')) {
    $parent_tabs = $settings->get_tabs();
}

unset($settings);
$settings = null;

// Crear categoría en "Apariencia".
$ADMIN->add('appearance', new admin_category('theme_compecer', get_string('pluginname', 'theme_compecer')));

// Crear objeto de configuraciones con pestañas.
$asettings = new theme_compecer_admin_settingspage_tabs(
    'themesettingcompecer',
    get_string('themesettings', 'theme_compecer'),
    'moodle/site:config'
);

if ($ADMIN->fulltree) {

    // Variables comunes (por ejemplo, imágenes de ejemplo).
    $a = new stdClass();
    $a->example_banner  = (string)$OUTPUT->image_url('example_banner', 'theme_compecer');
    $a->cover_moove     = (string)$OUTPUT->image_url('cover_moove', 'theme');
    $a->example_cover1  = (string)$OUTPUT->image_url('login_bg', 'theme');
    $a->example_cover2  = (string)$OUTPUT->image_url('cover2', 'theme');
    $a->banner_compecer = (string)$OUTPUT->image_url('banner_compecer', 'theme');

    // Array de iconos.
    $icons = [
        // Iconos originales
        'message'            => get_string('icon_message', 'theme_compecer'),
        'user'               => get_string('icon_user', 'theme_compecer'),
        'cog'                => get_string('icon_settings', 'theme_compecer'),
        'chart-bar'          => get_string('icon_chart', 'theme_compecer'),
        'graduation-cap'     => get_string('icon_graduation', 'theme_compecer'),
        'book'               => get_string('icon_book', 'theme_compecer'),
        'laptop'             => get_string('icon_laptop', 'theme_compecer'),
        'users'              => get_string('icon_users', 'theme_compecer'),
        'globe'              => get_string('icon_globe', 'theme_compecer'),
        'lightbulb'          => get_string('icon_lightbulb', 'theme_compecer'),
        'certificate'        => get_string('icon_certificate', 'theme_compecer'),
        'chart-line'         => get_string('icon_chart', 'theme_compecer'),
        'medal'              => get_string('icon_medal', 'theme_compecer'),
        'star'               => get_string('icon_star', 'theme_compecer'),
        'rocket'             => get_string('icon_rocket', 'theme_compecer'),
        'code'               => get_string('icon_code', 'theme_compecer'),
        'microscope'         => get_string('icon_microscope', 'theme_compecer'),
        'flask'              => get_string('icon_flask', 'theme_compecer'),
        'atom'               => get_string('icon_atom', 'theme_compecer'),
        'brain'              => get_string('icon_brain', 'theme_compecer'),
        'university'         => get_string('icon_university', 'theme_compecer'),
        'award'              => get_string('icon_award', 'theme_compecer'),
        'user-graduate'      => get_string('icon_usergraduate', 'theme_compecer'),
        'chalkboard-teacher' => get_string('icon_teacher', 'theme_compecer'),

        // Iconos académicos adicionales
        'book-reader'        => get_string('icon_bookreader', 'theme_compecer'),
        'book-open'          => get_string('icon_bookopen', 'theme_compecer'),
        'books'              => get_string('icon_books', 'theme_compecer'),
        'pen'                => get_string('icon_pen', 'theme_compecer'),
        'pencil-alt'         => get_string('icon_pencil', 'theme_compecer'),
        'marker'             => get_string('icon_marker', 'theme_compecer'),
        'highlighter'        => get_string('icon_highlighter', 'theme_compecer'),

        // Iconos de tecnología.
        'desktop'            => get_string('icon_desktop', 'theme_compecer'),
        'laptop-code'        => get_string('icon_laptopcode', 'theme_compecer'),
        'mobile'             => get_string('icon_mobile', 'theme_compecer'),
        'tablet'             => get_string('icon_tablet', 'theme_compecer'),
        'keyboard'           => get_string('icon_keyboard', 'theme_compecer'),
        'mouse'              => get_string('icon_mouse', 'theme_compecer'),
        'wifi'               => get_string('icon_wifi', 'theme_compecer'),
        'signal'             => get_string('icon_signal', 'theme_compecer'),

        // Iconos de ciencia.
        'dna'                => get_string('icon_dna', 'theme_compecer'),
        'vial'               => get_string('icon_vial', 'theme_compecer'),
        'magnet'             => get_string('icon_magnet', 'theme_compecer'),
        'glasses'            => get_string('icon_glasses', 'theme_compecer'),
        'telescope'          => get_string('icon_telescope', 'theme_compecer'),
        'microscope'         => get_string('icon_microscope', 'theme_compecer'),

        // Iconos de negocios y productividad.
        'briefcase'          => get_string('icon_briefcase', 'theme_compecer'),
        'calculator'         => get_string('icon_calculator', 'theme_compecer'),
        'calendar'           => get_string('icon_calendar', 'theme_compecer'),
        'clock'              => get_string('icon_clock', 'theme_compecer'),
        'tasks'              => get_string('icon_tasks', 'theme_compecer'),
        'clipboard'          => get_string('icon_clipboard', 'theme_compecer'),
        'clipboard-check'    => get_string('icon_clipboardcheck', 'theme_compecer'),
        'clipboard-list'     => get_string('icon_clipboardlist', 'theme_compecer'),

        // Iconos de comunicación.
        'comments'           => get_string('icon_comments', 'theme_compecer'),
        'comment-dots'       => get_string('icon_commentdots', 'theme_compecer'),
        'envelope'           => get_string('icon_envelope', 'theme_compecer'),
        'bell'               => get_string('icon_bell', 'theme_compecer'),
        'broadcast-tower'    => get_string('icon_broadcast', 'theme_compecer'),
        'bullhorn'           => get_string('icon_bullhorn', 'theme_compecer'),

        // Iconos de logros y progreso.
        'trophy'             => get_string('icon_trophy', 'theme_compecer'),
        'crown'              => get_string('icon_crown', 'theme_compecer'),
        'medal'              => get_string('icon_medal', 'theme_compecer'),
        'badge'              => get_string('icon_badge', 'theme_compecer'),
        'certificate'        => get_string('icon_certificate', 'theme_compecer'),
        'stamp'              => get_string('icon_stamp', 'theme_compecer'),

        // Iconos de creatividad y arte.
        'palette'            => get_string('icon_palette', 'theme_compecer'),
        'paint-brush'        => get_string('icon_paintbrush', 'theme_compecer'),
        'music'              => get_string('icon_music', 'theme_compecer'),
        'theater-masks'      => get_string('icon_theater', 'theme_compecer'),
        'film'               => get_string('icon_film', 'theme_compecer'),
        'camera'             => get_string('icon_camera', 'theme_compecer'),

        // Iconos de deporte y salud.
        'running'            => get_string('icon_running', 'theme_compecer'),
        'swimming-pool'      => get_string('icon_swimming', 'theme_compecer'),
        'basketball-ball'    => get_string('icon_basketball', 'theme_compecer'),
        'football-ball'      => get_string('icon_football', 'theme_compecer'),
        'heartbeat'          => get_string('icon_heartbeat', 'theme_compecer'),
        'medkit'             => get_string('icon_medkit', 'theme_compecer'),

        // Iconos de navegación y ubicación.
        'compass'            => get_string('icon_compass', 'theme_compecer'),
        'map'                => get_string('icon_map', 'theme_compecer'),
        'map-marker-alt'     => get_string('icon_mapmarker', 'theme_compecer'),
        'directions'         => get_string('icon_directions', 'theme_compecer'),
        'location-arrow'     => get_string('icon_location', 'theme_compecer'),

        // Iconos de colaboración y comunidad.
        'hands-helping'      => get_string('icon_handshelping', 'theme_compecer'),
        'handshake'          => get_string('icon_handshake', 'theme_compecer'),
        'user-friends'       => get_string('icon_userfriends', 'theme_compecer'),
        'user-plus'          => get_string('icon_userplus', 'theme_compecer'),
        'users-cog'          => get_string('icon_userscog', 'theme_compecer'),
        'people-carry'       => get_string('icon_peoplecarry', 'theme_compecer')
    ];

    /* =========================================================================
       TAB 1: General Settings
       ========================================================================= */
    $page = new admin_settingpage('theme_compecer_generals', get_string('generalsettings', 'theme_compecer'));

    // --- Notificaciones ---
    $page->add(new admin_setting_heading(
        'theme_compecer/generalnoticeheading',
        get_string('generalnoticeheading', 'theme_compecer'),
        ''
    ));

    $name = 'theme_compecer/generalnoticemode';
    $title = get_string('generalnoticemode', 'theme_compecer');
    $description = get_string('generalnoticemodedesc', 'theme_compecer');
    $default = 'off';
    $choices = [
        'off'    => get_string('generalnoticemode_off', 'theme_compecer'),
        'info'   => get_string('generalnoticemode_info', 'theme_compecer'),
        'danger' => get_string('generalnoticemode_danger', 'theme_compecer')
    ];
    $setting = new admin_setting_configselect($name, $title, $description, $default, $choices);
    $setting->set_updatedcallback('theme_reset_all_caches');
    $page->add($setting);

    $name = 'theme_compecer/generalnotice';
    $title = get_string('generalnotice', 'theme_compecer');
    $description = get_string('generalnoticedesc', 'theme_compecer');
    $default = get_string('generalnoticedefault', 'theme_compecer');
    $setting = new admin_setting_confightmleditor($name, $title, $description, $default);
    $setting->set_updatedcallback('theme_reset_all_caches');
    $page->add($setting);

    // --- Chat Settings ---
    $page->add(new admin_setting_heading(
        'theme_compecer/chatheading',
        get_string('chatheading', 'theme_compecer'),
        ''
    ));

    $name = 'theme_compecer/enable_chat';
    $title = get_string('enable_chat', 'theme_compecer');
    $description = get_string('enable_chatdesc', 'theme_compecer');
    $default = 0;
    $setting = new admin_setting_configcheckbox($name, $title, $description, $default);
    $setting->set_updatedcallback('theme_reset_all_caches');
    $page->add($setting);

    $name = 'theme_compecer/tawkto_embed_url';
    $title = get_string('tawkto_embed_url', 'theme_compecer');
    $description = get_string('tawkto_embed_urldesc', 'theme_compecer');
    $default = get_string('tawkto_embed_urldefault', 'theme_compecer');
    $setting = new admin_setting_configtext($name, $title, $description, $default);
    $setting->set_updatedcallback('theme_reset_all_caches');
    $page->add($setting);

    // --- Accessibility ---
    $page->add(new admin_setting_heading(
        'theme_compecer/accessibilityheading',
        get_string('accessibilityheading', 'theme_compecer'),
        ''
    ));

    $name = 'theme_compecer/accessibility_widget';
    $title = get_string('accessibility_widget', 'theme_compecer');
    $description = get_string('accessibility_widgetdesc', 'theme_compecer');
    $default = 0;
    $setting = new admin_setting_configcheckbox($name, $title, $description, $default);
    $setting->set_updatedcallback('theme_reset_all_caches');
    $page->add($setting);

    // --- Content Protection ---
    $page->add(new admin_setting_heading(
        'theme_compecer/contentprotectionheading',
        get_string('contentprotectionheading', 'theme_compecer'),
        ''
    ));

    $name = 'theme_compecer/copypaste_prevention';
    $title = get_string('copypaste_prevention', 'theme_compecer');
    $description = get_string('copypaste_preventiondesc', 'theme_compecer');
    $default = 0;
    $setting = new admin_setting_configcheckbox($name, $title, $description, $default);
    $setting->set_updatedcallback('theme_reset_all_caches');
    $page->add($setting);

    // Roles para protección (requiere accesslib.php).
    require_once($CFG->libdir . '/accesslib.php');
    $roles = role_get_names(null, ROLENAME_ORIGINAL);
    $roles_array = [];
    foreach ($roles as $role) {
        $roles_array[$role->id] = $role->localname;
    }
    $name = 'theme_compecer/copypaste_roles';
    $title = get_string('copypaste_roles', 'theme_compecer');
    $description = get_string('copypaste_rolesdesc', 'theme_compecer');
    $default = [5];
    $setting = new admin_setting_configmultiselect($name, $title, $description, $default, $roles_array);
    $setting->set_updatedcallback('theme_reset_all_caches');
    $page->add($setting);

    $asettings->add_tab($page);

    /* =========================================================================
   TAB 2: Frontpage Settings
   ========================================================================= */
$page = new admin_settingpage('theme_compecer_frontpage', get_string('frontpagesettings', 'theme_compecer'));

/** SLIDER **/

// Título para la sección del slider.
$page->add(new admin_setting_heading(
    'theme_compecer/sliderheading',
    get_string('sliderheading', 'theme_compecer'),
    ''
));

// Opción para habilitar el slider.
$name = 'theme_compecer/enable_slider';
$title = get_string('enable_slider', 'theme_compecer');
$description = get_string('enable_sliderdesc', 'theme_compecer');
$default = 1;
$setting = new admin_setting_configcheckbox($name, $title, $description, $default);
$setting->set_updatedcallback('theme_reset_all_caches');
$page->add($setting);

// Opción para definir la cantidad de slides.
// Se ha cambiado el nombre a "frontpage_slidercount" para evitar conflictos.
$name = 'theme_compecer/frontpage_slidercount';
$title = get_string('slidercount', 'theme_compecer');
$description = get_string('slidercountdesc', 'theme_compecer');
$choices = [1 => '1', 2 => '2', 3 => '3'];
$default = 1;
$setting = new admin_setting_configselect($name, $title, $description, $default, $choices);
$setting->set_updatedcallback('theme_reset_all_caches');
$page->add($setting);

// Sección para definir las imágenes de cada slide (1 a 3).
for ($i = 1; $i <= 3; $i++) {
    $page->add(new admin_setting_heading(
        "theme_compecer/frontpage_slider{$i}heading",
        get_string('sliderheading', 'theme_compecer', $i),
        ''
    ));

    // Imagen para Desktop.
    $name = "theme_compecer/frontpage_sliderimage{$i}";
    $title = get_string('sliderimage', 'theme_compecer', $i) . ' (Desktop)';
    $description = get_string('sliderimagedesc', 'theme_compecer') . ' (Desktop)';
    $opts = ['subdirs' => 0, 'accepted_types' => ['web_image']];
    // La clave para guardar el archivo también se ha renombrado.
    $setting = new admin_setting_configstoredfile($name, $title, $description, "frontpage_sliderimage{$i}", 0, $opts);
    $setting->set_updatedcallback('theme_reset_all_caches');
    $page->add($setting);

    // Imagen para Mobile.
    $name = "theme_compecer/frontpage_sliderimage{$i}_mobile";
    $title = get_string('sliderimage', 'theme_compecer', $i) . ' (Mobile)';
    $description = get_string('sliderimagedesc', 'theme_compecer') . ' (Mobile)';
    $setting = new admin_setting_configstoredfile($name, $title, $description, "frontpage_sliderimage{$i}_mobile", 0, $opts);
    $setting->set_updatedcallback('theme_reset_all_caches');
    $page->add($setting);
}

/** CAREER BOXES **/

$page->add(new admin_setting_heading(
    'theme_compecer/careerboxesheading',
    get_string('careerboxesheading', 'theme_compecer'),
    ''
));

$name = 'theme_compecer/enablecareerboxes';
$title = get_string('enablecareerboxes', 'theme_compecer');
$description = get_string('enablecareerboxesdesc', 'theme_compecer');
$default = 1;
$setting = new admin_setting_configcheckbox($name, $title, $description, $default);
$page->add($setting);

$name = 'theme_compecer/careerboxcount';
$title = get_string('careerboxcount', 'theme_compecer');
$description = get_string('careerboxcountdesc', 'theme_compecer');
$choices = [1 => '1', 2 => '2', 3 => '3', 4 => '4', 5 => '5', 6 => '6'];
$default = 3;
$setting = new admin_setting_configselect($name, $title, $description, $default, $choices);
$page->add($setting);

$name = 'theme_compecer/careerboxesbgcolor';
$title = get_string('careerboxesbgcolor', 'theme_compecer');
$description = get_string('careerboxesbgcolordesc', 'theme_compecer');
$default = '#365ba3';
$setting = new admin_setting_configcolourpicker($name, $title, $description, $default);
$setting->set_updatedcallback('theme_reset_all_caches');
$page->add($setting);

$maxboxes = 6;
for ($i = 1; $i <= $maxboxes; $i++) {
    $page->add(new admin_setting_heading(
        "theme_compecer/careerbox{$i}heading",
        get_string('careerboxheading', 'theme_compecer', $i),
        ''
    ));

    $name = "theme_compecer/careerbox{$i}icon";
    $title = get_string('careerboxicon', 'theme_compecer', $i);
    $description = get_string('careerboxicondesc', 'theme_compecer');
    $default = 'graduation-cap';
    $setting = new admin_setting_configselect($name, $title, $description, $default, $icons);
    $page->add($setting);

    $name = "theme_compecer/careerbox{$i}title";
    $title = get_string('careerboxtitle', 'theme_compecer', $i);
    $description = get_string('careerboxtitledesc', 'theme_compecer');
    $default = get_string('careerboxtitledefault', 'theme_compecer', $i);
    $setting = new admin_setting_configtext($name, $title, $description, $default);
    $page->add($setting);

    $name = "theme_compecer/careerbox{$i}content";
    $title = get_string('careerboxcontent', 'theme_compecer', $i);
    $description = get_string('careerboxcontentdesc', 'theme_compecer');
    $default = get_string('careerboxcontentdefault', 'theme_compecer', $i);
    $setting = new admin_setting_confightmleditor($name, $title, $description, $default);
    $page->add($setting);
}

/** SEARCH CATEGORIES **/

$page->add(new admin_setting_heading(
    'theme_compecer/searchcategoriesheading',
    get_string('searchcategoriesheading', 'theme_compecer'),
    ''
));

$name = 'theme_compecer/enable_search_categories';
$title = get_string('enable_search_categories', 'theme_compecer');
$description = get_string('enable_search_categoriesdesc', 'theme_compecer');
$default = 1;
$setting = new admin_setting_configcheckbox($name, $title, $description, $default);
$setting->set_updatedcallback('theme_reset_all_caches');
$page->add($setting);

$name = 'theme_compecer/selectedcategories';
$title = get_string('selectedcategories', 'theme_compecer');
$description = get_string('selectedcategoriesdesc', 'theme_compecer');
$categories = core_course_category::make_categories_list();
$default = [];
$setting = new admin_setting_configmultiselect($name, $title, $description, $default, $categories);
$page->add($setting);

$name = 'theme_compecer/searchsectiontitle';
$title = get_string('searchsectiontitle', 'theme_compecer');
$description = get_string('searchsectiontitledesc', 'theme_compecer');
$default = get_string('searchsectiontitledefault', 'theme_compecer');
$setting = new admin_setting_configtext($name, $title, $description, $default);
$page->add($setting);

$name = 'theme_compecer/searchsectiondesc';
$title = get_string('searchsectiondesc', 'theme_compecer');
$description = get_string('searchsectiondescdesc', 'theme_compecer');
$default = get_string('searchsectiondescdefault', 'theme_compecer');
$setting = new admin_setting_confightmleditor($name, $title, $description, $default);
$page->add($setting);

$name = 'theme_compecer/categoriesbgcolor';
$title = get_string('categoriesbgcolor', 'theme_compecer');
$description = get_string('categoriesbgcolordesc', 'theme_compecer');
$default = '#f8f9fa';
$setting = new admin_setting_configcolourpicker($name, $title, $description, $default);
$setting->set_updatedcallback('theme_reset_all_caches');
$page->add($setting);

/** ABOUT FRONTPAGE **/

$page->add(new admin_setting_heading(
    'theme_compecer/aboutfrontpageheading',
    get_string('aboutfrontpageheading', 'theme_compecer'),
    ''
));

$name = 'theme_compecer/enable_about';
$title = get_string('enable_about', 'theme_compecer');
$description = get_string('enable_aboutdesc', 'theme_compecer');
$default = 1;
$setting = new admin_setting_configcheckbox($name, $title, $description, $default);
$setting->set_updatedcallback('theme_reset_all_caches');
$page->add($setting);

$name = 'theme_compecer/abouttitle';
$title = get_string('abouttitle', 'theme_compecer');
$description = get_string('abouttitledesc', 'theme_compecer');
$default = get_string('abouttitledefault', 'theme_compecer');
$setting = new admin_setting_configtext($name, $title, $description, $default);
$page->add($setting);

$name = 'theme_compecer/aboutcontent';
$title = get_string('aboutcontent', 'theme_compecer');
$description = get_string('aboutcontentdesc', 'theme_compecer');
$default = get_string('aboutcontentdefault', 'theme_compecer');
$setting = new admin_setting_confightmleditor($name, $title, $description, $default);
$page->add($setting);

$asettings->add_tab($page);

    /* =========================================================================
       TAB 3: MyDashboard Settings
       ========================================================================= */
    $page = new admin_settingpage('theme_compecer_mydashboard', get_string('mydashboardsettings', 'theme_compecer'));

    $page->add(new admin_setting_heading(
        'theme_compecer/dashboardgeneralheading',
        get_string('dashboardgeneralheading', 'theme_compecer'),
        ''
    ));

    $name = 'theme_compecer/enable_dashboard_cards';
    $title = get_string('enable_dashboard_cards', 'theme_compecer');
    $description = get_string('enable_dashboard_cardsdesc', 'theme_compecer');
    $default = 1;
    $setting = new admin_setting_configcheckbox($name, $title, $description, $default);
    $setting->set_updatedcallback('theme_reset_all_caches');
    $page->add($setting);

    $default_cards = [
        1 => [
            'title'    => 'Mensajes',
            'subtitle' => 'Comunicar',
            'url'      => $CFG->wwwroot . '/message/index.php',
            'color'    => '#2441e7',
            'icon'     => 'message'
        ],
        2 => [
            'title'    => 'Perfil',
            'subtitle' => 'Tu perfil',
            'url'      => $CFG->wwwroot . '/user/profile.php',
            'color'    => '#ff1053',
            'icon'     => 'user'
        ],
        3 => [
            'title'    => 'Configuración',
            'subtitle' => 'Preferencias',
            'url'      => $CFG->wwwroot . '/user/preferences.php',
            'color'    => '#00a78e',
            'icon'     => 'cog'
        ],
        4 => [
            'title'    => 'Calificaciones',
            'subtitle' => 'Rendimiento',
            'url'      => $CFG->wwwroot . '/grade/report/overview/index.php',
            'color'    => '#ecd06f',
            'icon'     => 'chart-bar'
        ]
    ];

    for ($i = 1; $i <= 4; $i++) {
        $card = $default_cards[$i];

        $page->add(new admin_setting_heading(
            "theme_compecer/dashboard_card_{$i}_heading",
            get_string('dashboard_card_heading', 'theme_compecer', $i),
            ''
        ));

        $page->add(new admin_setting_heading(
            "theme_compecer/dashboard_card_{$i}_visibility_heading",
            get_string('dashboard_card_visibility_heading', 'theme_compecer', $i),
            ''
        ));

        $name = "theme_compecer/dashboard_card_{$i}_visibility";
        $title = get_string('dashboard_card_visibility', 'theme_compecer', $i);
        $description = get_string('dashboard_card_visibilitydesc', 'theme_compecer', $i);
        $setting = new admin_setting_configcheckbox($name, $title, $description, 0);
        $setting->set_updatedcallback('theme_reset_all_caches');
        $page->add($setting);

        $page->add(new admin_setting_heading(
            "theme_compecer/dashboard_card_{$i}_content_heading",
            get_string('dashboard_card_content_heading', 'theme_compecer', $i),
            ''
        ));

        $name = "theme_compecer/dashboard_card_{$i}_title";
        $title = get_string('dashboard_card_title', 'theme_compecer', $i);
        $description = get_string('dashboard_card_titledesc', 'theme_compecer', $i);
        $setting = new admin_setting_configtext($name, $title, $description, $card['title']);
        $page->add($setting);

        $name = "theme_compecer/dashboard_card_{$i}_subtitle";
        $title = get_string('dashboard_card_subtitle', 'theme_compecer', $i);
        $description = get_string('dashboard_card_subtitledesc', 'theme_compecer', $i);
        $setting = new admin_setting_configtext($name, $title, $description, $card['subtitle']);
        $page->add($setting);

        $name = "theme_compecer/dashboard_card_{$i}_url";
        $title = get_string('dashboard_card_url', 'theme_compecer', $i);
        $description = get_string('dashboard_card_urldesc', 'theme_compecer', $i);
        $setting = new admin_setting_configtext($name, $title, $description, $card['url']);
        $page->add($setting);

        $page->add(new admin_setting_heading(
            "theme_compecer/dashboard_card_{$i}_appearance_heading",
            get_string('dashboard_card_appearance_heading', 'theme_compecer', $i),
            ''
        ));

        $name = "theme_compecer/dashboard_card_{$i}_color";
        $title = get_string('dashboard_card_color', 'theme_compecer', $i);
        $description = get_string('dashboard_card_colordesc', 'theme_compecer', $i);
        $setting = new admin_setting_configcolourpicker($name, $title, $description, $card['color']);
        $setting->set_updatedcallback('theme_reset_all_caches');
        $page->add($setting);

        $name = "theme_compecer/dashboard_card_{$i}_icon";
        $title = get_string('dashboard_card_icon', 'theme_compecer', $i);
        $description = get_string('dashboard_card_icondesc', 'theme_compecer', $i);
        $setting = new admin_setting_configselect($name, $title, $description, $card['icon'], $icons);
        $page->add($setting);
    }

    $asettings->add_tab($page);

    /* =========================================================================
       TAB 2: Footer Settings
       ========================================================================= */
    $page = new admin_settingpage('theme_compecer_footers', get_string('footersettings', 'theme_compecer'));

    $page->add(new admin_setting_heading(
        'theme_compecer/footeraboutheading',
        get_string('footeraboutheading', 'theme_compecer'),
        ''
    ));

    $name = 'theme_compecer/hideabouttext';
    $title = get_string('hideabouttext', 'theme_compecer');
    $description = get_string('hideabouttextdesc', 'theme_compecer');
    $default = 0;
    $choices = [
        0 => get_string('show', 'theme_compecer'),
        1 => get_string('hide', 'theme_compecer')
    ];
    $setting = new admin_setting_configselect($name, $title, $description, $default, $choices);
    $setting->set_updatedcallback('theme_reset_all_caches');
    $page->add($setting);

    $name = 'theme_compecer/abouttext';
    $title = get_string('abouttext', 'theme_compecer');
    $description = get_string('abouttextdesc', 'theme_compecer');
    $default = '<p><strong>IngeWeb - Soluciones para triunfar en Internet</strong><br>Expertos en Moodle, BigBlueButton, Wordpress y Joomla.<br><strong>www.ingeweb.co</strong></p>';
    $setting = new admin_setting_confightmleditor($name, $title, $description, $default);
    $setting->set_updatedcallback('theme_reset_all_caches');
    $page->add($setting);

    $asettings->add_tab($page);

    // Si existen pestañas del tema padre, combinarlas.
    if ($parent_tabs !== null) {
        $all_tabs = array_merge($asettings->get_tabs(), $parent_tabs);
        $asettings->set_tabs($all_tabs);
    }
}

// Agregar la página de configuraciones a la categoría de apariencia.
$ADMIN->add('theme_compecer', $asettings);
