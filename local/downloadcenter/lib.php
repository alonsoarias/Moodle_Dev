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
 * Library functions for local_downloadcenter
 *
 * @package    local_downloadcenter
 * @copyright  2025 Original: Academic Moodle Cooperation, Extended: Alonso Arias <soporte@ingeweb.co>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Extend course navigation with download center link.
 *
 * @param navigation_node $parentnode Parent navigation node
 * @param stdClass $course Current course object
 * @param context_course $context Course context
 * @return void
 */
function local_downloadcenter_extend_navigation_course(navigation_node $parentnode, stdClass $course, context_course $context) {
    if (!has_capability('local/downloadcenter:view', $context)) {
        return;
    }

    $url = new moodle_url('/local/downloadcenter/index.php', ['courseid' => $course->id, 'mode' => 'course']);
    $title = get_string('navigationlink', 'local_downloadcenter');
    $pix = new pix_icon('i/download', $title);
    
    $childnode = navigation_node::create(
        $title,
        $url,
        navigation_node::TYPE_SETTING,
        'downloadcenter',
        'downloadcenter',
        $pix
    );

    $node = $parentnode->add_node($childnode);
    if ($node) {
        $node->nodetype = navigation_node::TYPE_SETTING;
        $node->collapse = true;
        $node->add_class('downloadcenterlink');
    }
}

/**
 * Add admin menu entry for multi-course download.
 *
 * @param navigation_node $navigation Main navigation node
 * @return void
 */
function local_downloadcenter_extend_navigation(navigation_node $navigation) {
    global $PAGE;
    
    // Only add admin menu if user has admin capabilities.
    if (!has_capability('moodle/site:config', context_system::instance())) {
        return;
    }
    
    // Only add in system context.
    if ($PAGE->context->contextlevel == CONTEXT_SYSTEM) {
        $settings = $navigation->find('courses', navigation_node::TYPE_SETTING);
        if ($settings) {
            $url = new moodle_url('/local/downloadcenter/index.php', ['mode' => 'admin']);
            $settings->add(
                get_string('admindownloadcenter', 'local_downloadcenter'),
                $url,
                navigation_node::TYPE_SETTING,
                null,
                'downloadcenteradmin',
                new pix_icon('i/download', '')
            );
        }
    }
}

/**
 * Get the fontawesome icon map for this plugin.
 *
 * @return array Font awesome icon map
 */
function local_downloadcenter_get_fontawesome_icon_map() {
    return [
        'local_downloadcenter:icon' => 'fa-download',
    ];
}

/**
 * Serves the files from the local_downloadcenter areas.
 *
 * @param stdClass $course Course object
 * @param stdClass $cm Course module object
 * @param context $context Context
 * @param string $filearea File area
 * @param array $args Extra arguments
 * @param bool $forcedownload Whether to force download
 * @param array $options Additional options
 * @return bool False if file not found, does not return if found
 */
function local_downloadcenter_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload, array $options = []) {
    // Check the contextlevel is as expected.
    if ($context->contextlevel != CONTEXT_SYSTEM) {
        return false;
    }

    // Make sure the user is logged in.
    require_login();

    // Check the relevant capabilities.
    if (!has_capability('local/downloadcenter:view', $context)) {
        return false;
    }

    // Leave this function if file area is not controlled by this plugin.
    if ($filearea !== 'downloadcenter') {
        return false;
    }

    // File handling would go here if needed.
    return false;
}