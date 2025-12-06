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
 * Widget renderable class.
 *
 * @package     local_educambot
 * @copyright   2025 EducamBot Team
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_educambot\output;

defined('MOODLE_INTERNAL') || die();

use renderable;
use templatable;
use renderer_base;

/**
 * Widget class.
 */
class widget implements renderable, templatable {

    /**
     * Export data for template.
     *
     * @param renderer_base $output
     * @return array
     */
    public function export_for_template(renderer_base $output) {
        global $CFG, $USER, $COURSE, $DB;

        // Get bot configuration.
        $botname = get_config('local_educambot', 'botname') ?: get_string('botname_default', 'local_educambot');
        $widgetlabel = get_config('local_educambot', 'widgetlabel') ?: get_string('widgetlabel_default', 'local_educambot');
        $greetingtemplate = get_config('local_educambot', 'greetingtemplate') ?: get_string('greeting_default', 'local_educambot');

        // Get v1.9.0 configuration options.
        $inactivitytimeout = get_config('local_educambot', 'inactivitytimeout') ?: 600000; // 10 minutes default.
        $enablehistory = get_config('local_educambot', 'enablehistory') ?? 1;

        // Get selected theme (v1.8.0).
        $theme = $this->get_current_theme();

        // Interpolate greeting message.
        $greetingmessage = $this->interpolate_message($greetingtemplate, $USER, $botname);

        // Get current course ID for context.
        $courseid = isset($COURSE->id) ? $COURSE->id : SITEID;

        // Prepare widget icon data (v1.8.1).
        $widgeticon = $this->prepare_widget_icon($theme);

        // Prepare mascot data (v1.8.1).
        $mascot = $this->prepare_mascot($theme);

        // Get user role archetype (v1.9.0).
        $userrolearchetype = $this->get_user_role_archetype($USER->id, $courseid);

        return [
            'botname' => $botname,
            'widgetlabel' => $widgetlabel,
            'primarycolor' => $theme->primarycolor,
            'secondarycolor' => $theme->secondarycolor,
            'textcolor' => $theme->textcolor,
            'backgroundcolor' => $theme->backgroundcolor,
            'usercolor' => $theme->usercolor,
            'botcolor' => $theme->botcolor,
            'greetingmessage' => $greetingmessage,
            'serviceurl' => $CFG->wwwroot . '/local/educambot/service.php',
            'startupurl' => $CFG->wwwroot . '/local/educambot/startup.php',
            'historyurl' => $CFG->wwwroot . '/local/educambot/history.php',
            'sesskey' => sesskey(),
            'courseid' => $courseid,
            // Widget icon (v1.8.1).
            'widgeticon' => $widgeticon,
            // Mascot (v1.8.1).
            'mascot' => $mascot,
            // User info and role (v1.9.0).
            'userid' => $USER->id,
            'userfirstname' => $USER->firstname ?? '',
            'userlastname' => $USER->lastname ?? '',
            'userfullname' => trim(($USER->firstname ?? '') . ' ' . ($USER->lastname ?? '')),
            'userrolearchetype' => $userrolearchetype,
            // Configuration options (v1.9.0).
            'inactivitytimeout' => (int) $inactivitytimeout,
            'enablehistory' => (int) $enablehistory,
        ];
    }

    /**
     * Get the current active theme.
     *
     * @return object Theme object with color properties.
     */
    private function get_current_theme() {
        global $DB;

        // Check if theme table exists (for upgrades).
        $dbman = $DB->get_manager();
        $table = new \xmldb_table('local_educambot_theme');

        if ($dbman->table_exists($table)) {
            // Try to get default theme.
            $theme = $DB->get_record('local_educambot_theme', ['isdefault' => 1]);
            if ($theme) {
                return $theme;
            }

            // Fallback to first theme.
            $theme = $DB->get_record('local_educambot_theme', [], '*', IGNORE_MULTIPLE);
            if ($theme) {
                return $theme;
            }
        }

        // Return default colors if no theme found (pre-v1.8.0 or fresh install).
        $default = new \stdClass();
        $default->primarycolor = get_config('local_educambot', 'primarycolor') ?: '#0f6fc5';
        $default->secondarycolor = '#084a8a';
        $default->textcolor = '#1f2937';
        $default->backgroundcolor = '#f9fafb';
        $default->usercolor = $default->primarycolor;
        $default->botcolor = '#ffffff';
        $default->widgeticontype = 'default';
        $default->widgeticonurl = '';
        $default->mascotenabled = 0;
        $default->mascottype = 'none';

        return $default;
    }

    /**
     * Interpolate message with user variables.
     *
     * @param string $message Message template
     * @param object $user User object
     * @param string $botname Bot name
     * @return string Interpolated message
     */
    private function interpolate_message($message, $user, $botname) {
        // Build fullname from firstname and lastname.
        $fullname = trim(($user->firstname ?? '') . ' ' . ($user->lastname ?? ''));

        $replacements = [
            '{{userfirstname}}' => $user->firstname ?? '',
            '{{userlastname}}' => $user->lastname ?? '',
            '{{fullname}}' => $fullname,
            '{{username}}' => $fullname, // Alias for fullname.
            '{{botname}}' => $botname,
        ];

        return str_replace(array_keys($replacements), array_values($replacements), $message);
    }

    /**
     * Get user's primary role archetype for behavior customization (v1.9.0).
     *
     * Moodle role archetypes: manager, coursecreator, editingteacher, teacher, student, guest, user, frontpage
     *
     * @param int $userid User ID
     * @param int $courseid Course ID
     * @return string Role archetype
     */
    private function get_user_role_archetype($userid, $courseid) {
        global $DB;

        // Check for site administrator first (special case - no archetype but highest privilege).
        if (is_siteadmin($userid)) {
            return 'manager';
        }

        $syscontext = \context_system::instance();

        // Priority order for archetypes (highest privilege first).
        $archetypepriority = ['manager', 'coursecreator', 'editingteacher', 'teacher', 'student'];

        // Get course context if not site.
        if ($courseid != SITEID) {
            $coursecontext = \context_course::instance($courseid, IGNORE_MISSING);
            if ($coursecontext) {
                $roles = get_user_roles($coursecontext, $userid, true);
                foreach ($archetypepriority as $archetype) {
                    foreach ($roles as $role) {
                        $rolerecord = $DB->get_record('role', ['id' => $role->roleid]);
                        if ($rolerecord && $rolerecord->archetype === $archetype) {
                            return $archetype;
                        }
                    }
                }
            }
        }

        // Check system-level roles.
        $sysroles = get_user_roles($syscontext, $userid, true);
        foreach ($archetypepriority as $archetype) {
            foreach ($sysroles as $role) {
                $rolerecord = $DB->get_record('role', ['id' => $role->roleid]);
                if ($rolerecord && $rolerecord->archetype === $archetype) {
                    return $archetype;
                }
            }
        }

        // Check if guest user.
        if (isguestuser($userid)) {
            return 'guest';
        }

        // Default to 'user' for authenticated users without specific roles.
        return isloggedin() ? 'user' : 'guest';
    }

    /**
     * Prepare widget icon data for template (v1.8.1).
     *
     * @param object $theme Theme object
     * @return array Icon data for template
     */
    private function prepare_widget_icon($theme) {
        $icon = [
            'isdefault' => false,
            'isemoji' => false,
            'isfontawesome' => false,
            'iscustom' => false,
            'iconcode' => '',
            'iconurl' => '',
            'faprefix' => '', // Font Awesome family prefix (fa, fa-solid, etc.).
        ];

        $icontype = $theme->widgeticontype ?? 'default';
        $iconurl = $theme->widgeticonurl ?? '';

        switch ($icontype) {
            case 'emoji':
                $icon['isemoji'] = true;
                $icon['iconcode'] = $iconurl;
                break;

            case 'fontawesome':
                $icon['isfontawesome'] = true;
                // Handle Font Awesome 6 icon classes properly.
                // FA6 families: fa-solid, fa-regular, fa-brands, fa-light, fa-thin, fa-duotone, fa-sharp.
                // For backward compatibility, 'fa' class also works as fa-solid.
                $faclass = trim($iconurl);

                if (empty($faclass)) {
                    $icon['isdefault'] = true;
                    $icon['isfontawesome'] = false;
                    break;
                }

                // FA6 family classes.
                $fa6families = ['fa-solid', 'fa-regular', 'fa-brands', 'fa-light', 'fa-thin', 'fa-duotone', 'fa-sharp', 'fab', 'far', 'fas'];

                // Check if user provided a family class.
                $hasfamily = false;
                foreach ($fa6families as $family) {
                    if (strpos($faclass, $family) !== false) {
                        $hasfamily = true;
                        break;
                    }
                }

                // Ensure icon name starts with 'fa-'.
                if (!$hasfamily) {
                    // Simple icon name like "robot" or "fa-robot".
                    if (strpos($faclass, 'fa-') !== 0) {
                        $faclass = 'fa-' . $faclass;
                    }
                    // Add default solid family for FA6 compatibility.
                    $icon['faprefix'] = 'fa';
                } else {
                    // User provided family, don't add prefix.
                    $icon['faprefix'] = '';
                }

                $icon['iconcode'] = $faclass;
                break;

            case 'custom':
                if (!empty($iconurl)) {
                    $icon['iscustom'] = true;
                    $icon['iconurl'] = $iconurl;
                } else {
                    $icon['isdefault'] = true;
                }
                break;

            case 'default':
            default:
                $icon['isdefault'] = true;
                break;
        }

        return $icon;
    }

    /**
     * Prepare mascot data for template (v1.8.1).
     *
     * @param object $theme Theme object
     * @return array Mascot data for template
     */
    private function prepare_mascot($theme) {
        global $CFG;

        $mascot = [
            'enabled' => false,
            'type' => 'none',
            'svgcontent' => '',
        ];

        $mascotenabled = $theme->mascotenabled ?? 0;
        $mascottype = $theme->mascottype ?? 'none';

        if (!$mascotenabled || $mascottype === 'none') {
            return $mascot;
        }

        $mascot['enabled'] = true;
        $mascot['type'] = $mascottype;

        // Get SVG content based on mascot type.
        $svgpath = '';
        switch ($mascottype) {
            case 'clippy':
                $svgpath = $CFG->dirroot . '/local/educambot/pix/mascots/clippy.svg';
                break;

            case 'robot':
                $svgpath = $CFG->dirroot . '/local/educambot/pix/mascots/robot.svg';
                break;

            case 'owl':
                $svgpath = $CFG->dirroot . '/local/educambot/pix/mascots/owl.svg';
                break;

            case 'cat':
                $svgpath = $CFG->dirroot . '/local/educambot/pix/mascots/cat.svg';
                break;

            case 'lightbulb':
                $svgpath = $CFG->dirroot . '/local/educambot/pix/mascots/lightbulb.svg';
                break;

            case 'custom':
                // Get custom SVG from file storage.
                if (!empty($theme->mascoturl)) {
                    $fs = get_file_storage();
                    $context = \context_system::instance();
                    $files = $fs->get_area_files(
                        $context->id,
                        'local_educambot',
                        'mascot',
                        $theme->id,
                        'id',
                        false
                    );

                    if ($file = reset($files)) {
                        $svgcontent = $file->get_content();
                        $mascot['svgcontent'] = $this->sanitize_svg($svgcontent);
                        return $mascot;
                    }
                }
                $mascot['enabled'] = false;
                return $mascot;

            default:
                $mascot['enabled'] = false;
                return $mascot;
        }

        // Read SVG file content.
        if (file_exists($svgpath)) {
            $svgcontent = file_get_contents($svgpath);
            $mascot['svgcontent'] = $this->sanitize_svg($svgcontent);
        } else {
            $mascot['enabled'] = false;
        }

        return $mascot;
    }

    /**
     * Sanitize SVG content to remove potentially dangerous elements.
     *
     * @param string $svgcontent Raw SVG content
     * @return string Sanitized SVG content
     */
    private function sanitize_svg($svgcontent) {
        // Remove script tags.
        $svgcontent = preg_replace('/<script\b[^>]*>(.*?)<\/script>/is', '', $svgcontent);

        // Remove event handlers (onclick, onload, etc.).
        $svgcontent = preg_replace('/\s*on\w+\s*=\s*["\'][^"\']*["\']/i', '', $svgcontent);

        // Remove javascript: URLs.
        $svgcontent = preg_replace('/javascript\s*:/i', '', $svgcontent);

        // Remove foreignObject elements (can contain HTML/JS).
        $svgcontent = preg_replace('/<foreignObject\b[^>]*>(.*?)<\/foreignObject>/is', '', $svgcontent);

        return $svgcontent;
    }
}
