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

require_once($CFG->libdir . '/behat/lib.php');
require_once($CFG->dirroot . '/course/lib.php');

// Se removieron los mensajes de debugging que causaban problemas

// Add block button in editing mode.
$addblockbutton = $OUTPUT->addblockbutton();

if (isloggedin()) {
    $courseindexopen = (get_user_preferences('drawer-open-index', true) == true);
    $blockdraweropen = (get_user_preferences('drawer-open-block') == true);
} else {
    $courseindexopen = false;
    $blockdraweropen = false;
}

if (defined('BEHAT_SITE_RUNNING') && get_user_preferences('behat_keep_drawer_closed') != 1) {
    $blockdraweropen = true;
}

$extraclasses = ['uses-drawers'];
if ($courseindexopen) {
    $extraclasses[] = 'drawer-open-index';
}

$blockshtml = $OUTPUT->blocks('side-pre');
$hasblocks = (strpos($blockshtml, 'data-block=') !== false || !empty($addblockbutton));
if (!$hasblocks) {
    $blockdraweropen = false;
}

$themesettings = new \theme_moove\util\settings();
$courseindex = core_course_drawer();
if (!$courseindex) {
    $courseindexopen = false;
}
$forceblockdraweropen = $OUTPUT->firstview_fakeblocks();
$secondarynavigation = false;
$overflow = '';

if ($PAGE->has_secondary_navigation()) {
    $secondary = $PAGE->secondarynav;
    if ($secondary->get_children_key_list()) {
        $tablistnav = $PAGE->has_tablist_secondary_navigation();
        $moremenu = new \core\navigation\output\more_menu($PAGE->secondarynav, 'nav-tabs', true, $tablistnav);
        $secondarynavigation = $moremenu->export_for_template($OUTPUT);
        $extraclasses[] = 'has-secondarynavigation';
    }
    $overflowdata = $PAGE->secondarynav->get_overflow_menu_data();
    if (!is_null($overflowdata)) {
        $overflow = $overflowdata->export_for_template($OUTPUT);
    }
}

$primary = new core\navigation\output\primary($PAGE);
$renderer = $PAGE->get_renderer('core');
$primarymenu = $primary->export_for_template($renderer);
$buildregionmainsettings = !$PAGE->include_region_main_settings_in_header_actions() && !$PAGE->has_secondary_navigation();
$regionmainsettingsmenu = $buildregionmainsettings ? $OUTPUT->region_main_settings_menu() : false;
$header = $PAGE->activityheader;
$headercontent = $header->export_for_template($renderer);

// Eliminado el código de depuración que podría causar problemas

$templatecontext = [
    'sitename'             => format_string($SITE->shortname, true, ['context' => context_course::instance(SITEID), "escape" => false]),
    'output'               => $OUTPUT,
    'sidepreblocks'        => $blockshtml,
    'hasblocks'            => $hasblocks,
    'bodyattributes'       => $OUTPUT->body_attributes($extraclasses),
    'courseindexopen'      => $courseindexopen,
    'blockdraweropen'      => $blockdraweropen,
    'courseindex'          => $courseindex,
    'primarymoremenu'      => $primarymenu['moremenu'],
    'secondarymoremenu'    => $secondarynavigation ?: false,
    'mobileprimarynav'     => $primarymenu['mobileprimarynav'],
    'usermenu'             => $primarymenu['user'],
    'langmenu'             => $primarymenu['lang'],
    'forceblockdraweropen' => $forceblockdraweropen,
    'regionmainsettingsmenu'=> $regionmainsettingsmenu,
    'hasregionmainsettingsmenu'=> !empty($regionmainsettingsmenu),
    'overflow'             => $overflow,
    'headercontent'        => $headercontent,
    'addblockbutton'       => $addblockbutton,
    'isloggedin'           => isloggedin()
];

/* --- Sección Slider --- */
$sliderenabled = !empty($PAGE->theme->settings->enable_slider);
$slidercount = !empty($PAGE->theme->settings->frontpage_slidercount) ? $PAGE->theme->settings->frontpage_slidercount : 1;

if ($sliderenabled && $slidercount) {
    $templatecontext['slidercount'] = true;
    $slides = [];
    $validSlideCount = 0;
    $hasAnyMobileImage = false;
    for ($i = 1; $i <= $slidercount; $i++) {
        $sliderimage = $PAGE->theme->setting_file_url("frontpage_sliderimage{$i}", "frontpage_sliderimage{$i}");
        $sliderimagemobile = $PAGE->theme->setting_file_url("frontpage_sliderimage{$i}_mobile", "frontpage_sliderimage{$i}_mobile");
        
        if ($sliderimage) {
            if ($sliderimagemobile) {
                $hasAnyMobileImage = true;
            }
            $slides[] = [
                'key'         => $validSlideCount,
                'active'      => ($validSlideCount === 0),
                'image'       => $sliderimage,
                'mobile_image'=> $sliderimagemobile,
                'has_mobile'  => !empty($sliderimagemobile)
            ];
            $validSlideCount++;
        }
    }
    
    if (!empty($slides)) {
        $templatecontext['slides'] = $slides;
        $templatecontext['hasanymobileimage'] = $hasAnyMobileImage;
    } else {
        $templatecontext['slidercount'] = false;
    }
}

/* --- Sección About --- */
if (!empty($PAGE->theme->settings->enable_about)) {
    $templatecontext['aboutsection'] = true;
    $templatecontext['abouttitle'] = !empty($PAGE->theme->settings->abouttitle) ?
        format_text($PAGE->theme->settings->abouttitle, FORMAT_HTML) : '';
    $templatecontext['aboutcontent'] = !empty($PAGE->theme->settings->aboutcontent) ?
        format_text($PAGE->theme->settings->aboutcontent, FORMAT_HTML) : '';
}

/* --- Sección Career Boxes --- */
if (!empty($PAGE->theme->settings->enablecareerboxes)) {
    $templatecontext['careerboxes'] = true;
    $templatecontext['careerboxesbgcolor'] = $PAGE->theme->settings->careerboxesbgcolor ?? '#365ba3';
    $boxcount = !empty($PAGE->theme->settings->careerboxcount) ? (int)$PAGE->theme->settings->careerboxcount : 3;
    $templatecontext['boxcount'] = $boxcount;
    $columnClass = 'col-md-4';
    $templatecontext['boxcolumnclass'] = $columnClass;
    $boxes = [];
    for ($i = 1; $i <= $boxcount; $i++) {
        if (!empty($PAGE->theme->settings->{"careerbox{$i}title"})) {
            $boxes[] = [
                'icon'        => $PAGE->theme->settings->{"careerbox{$i}icon"} ?? 'graduation-cap',
                'title'       => format_text($PAGE->theme->settings->{"careerbox{$i}title"} ?? '', FORMAT_HTML),
                'content'     => format_text($PAGE->theme->settings->{"careerbox{$i}content"} ?? '', FORMAT_HTML),
                'columnclass' => $columnClass,
                'row_break'   => ($i === 4)
            ];
        }
    }
    $templatecontext['boxes'] = $boxes;
}

/* --- Sección Search Categories --- */
if (!empty($PAGE->theme->settings->enable_search_categories) && !empty($PAGE->theme->settings->selectedcategories)) {
    $selectedcats = $PAGE->theme->settings->selectedcategories;
    if (!empty($selectedcats)) {
        if (is_array($selectedcats)) {
            $selectedcatsArray = $selectedcats;
        } else {
            $selectedcatsArray = explode(',', $selectedcats);
        }
        $categories = [];
        foreach ($selectedcatsArray as $catid) {
            $category = core_course_category::get($catid, IGNORE_MISSING);
            if ($category) {
                $categories[] = [
                    'id'          => $category->id,
                    'name'        => format_text($category->get_formatted_name(), FORMAT_HTML),
                    'description' => format_text($category->description, FORMAT_HTML),
                    'url'         => new moodle_url('/course/index.php', ['categoryid' => $category->id])
                ];
            }
        }
        if (!empty($categories)) {
            $templatecontext['hassearchcategories'] = true;
            $templatecontext['categories'] = $categories;
            $templatecontext['searchsectiontitle'] = format_text(
                $PAGE->theme->settings->searchsectiontitle ??
                get_string('searchsectiontitledefault', 'theme_compecer'),
                FORMAT_HTML
            );
            $templatecontext['searchsectiondesc'] = format_text(
                $PAGE->theme->settings->searchsectiondesc ??
                get_string('searchsectiondescdefault', 'theme_compecer'),
                FORMAT_HTML
            );
            $templatecontext['categoriesbgcolor'] = $PAGE->theme->settings->categoriesbgcolor ?? '#f8f9fa';
        }
    }
}

// Merge con el footer del tema.
$templatecontext = array_merge($templatecontext, $themesettings->footer());

// Renderizar la plantilla Mustache.
echo $OUTPUT->render_from_template('theme_compecer/frontpage', $templatecontext);