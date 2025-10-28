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
 * A drawer based layout for the remui theme.
 *
 * @package   theme_remui
 * @copyright (c) 2023 WisdmLabs (https://wisdmlabs.com/) <support@wisdmlabs.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

global $CFG, $PAGE, $COURSE;

require_once($CFG->dirroot . '/theme/remui/layout/common.php');

$coursecontext = context_course::instance($COURSE->id);
if (!is_guest($coursecontext, $USER) &&
    \theme_remui\toolbox::get_setting('enabledashboardcoursestats') &&
    $PAGE->pagelayout == 'mydashboard' && $PAGE->pagetype == 'my-index') {
    $templatecontext['isdashboardstatsshow'] = true;
    $setupstatus = get_config("theme_remui","setupstatus");
    if(get_config("theme_remui","dashboardpersonalizerinfo") == "show" && ( $setupstatus == "final" || $setupstatus == 'finished' )) {
        $templatecontext['showpersonlizerinfo'] = true;
    }
}

// Must be called before rendering the template.
// This will ease us to add body classes directly to the array.
require_once($CFG->dirroot . '/theme/remui/layout/common_end.php');
$themesettings = new \theme_inteb\util\settings();
$templatecontext = array_merge($templatecontext, $themesettings->footer());

// Añadir la configuración del encabezado del área personal solo en la página del área personal
if ($PAGE->pagetype === 'my-index' && $PAGE->context->contextlevel == CONTEXT_USER) {
    $templatecontext = array_merge($templatecontext, $themesettings->personal_area_header());
}

// Añadir la configuración del encabezado de Mis cursos solo en la página Mis cursos
if ($PAGE->pagetype === 'my-index' && $PAGE->context->contextlevel == CONTEXT_SYSTEM) {
    $templatecontext = array_merge($templatecontext, $themesettings->my_courses_header());
}

echo $OUTPUT->render_from_template('theme_remui/drawers', $templatecontext);