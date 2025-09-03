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
 * Manual user enrolment UI.
 *
 * @package    enrol_yafee
 * @copyright 2024 Alex Orlov <snickser@gmail.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require('../../config.php');
require_once($CFG->dirroot . '/enrol/manual/locallib.php');

$enrolid      = required_param('enrolid', PARAM_INT);
$roleid       = optional_param('roleid', -1, PARAM_INT);
$extendperiod = optional_param('extendperiod', 0, PARAM_INT);
$extendbase   = optional_param('extendbase', 6, PARAM_INT);
$timeend      = optional_param_array('timeend', [], PARAM_INT);
$groupid      = optional_param('groupid', false, PARAM_INT);

$instance = $DB->get_record('enrol', ['id' => $enrolid, 'enrol' => 'yafee'], '*', MUST_EXIST);
$course = $DB->get_record('course', ['id' => $instance->courseid], '*', MUST_EXIST);
$context = context_course::instance($course->id, MUST_EXIST);

require_login($course);

$canenrol = has_capability('enrol/yafee:enrol', $context);
$canunenrol = has_capability('enrol/yafee:unenrol', $context);

// Note: manage capability not used here because it is used for editing
// of existing enrolments which is not possible here.

if (!$canenrol || !$canunenrol) {
    // No need to invent new error strings here...
    require_capability('enrol/yafee:enrol', $context);
    require_capability('enrol/yafee:unenrol', $context);
}

if ($roleid < 0) {
    $roleid = $instance->roleid;
}
$roles = get_assignable_roles($context);
$roles = ['0' => get_string('none')] + $roles;

if (!isset($roles[$roleid])) {
    // Weird - security always first!
    $roleid = 0;
}

if (!$enrolyafee = enrol_get_plugin('yafee')) {
    throw new coding_exception('Can not instantiate enrol_yafee');
}

$url = new moodle_url('/enrol/yafee/manage.php', ['enrolid' => $instance->id]);
$title = get_string('managemanualenrolements', 'enrol_yafee');

$PAGE->set_url($url);
$PAGE->set_pagelayout('admin');
$PAGE->set_title($title);
$PAGE->set_heading($course->fullname);
navigation_node::override_active_url(new moodle_url('/enrol/instances.php', ['id' => $course->id]));
$PAGE->navbar->add($title, $url);

// Create the user selector objects.
$options = ['enrolid' => $enrolid, 'accesscontext' => $context];

$potentialuserselector = new enrol_manual_potential_participant('addselect', $options);
$currentuserselector = new enrol_manual_current_participant('removeselect', $options);

// Build the list of options for the starting from dropdown.
$now = time();
$today = make_timestamp(date('Y', $now), date('m', $now), date('d', $now), 0, 0, 0);
$thismonth = make_timestamp(date('Y', $now), date('m', $now), 1, 0, 0, 0);
$thisyear = make_timestamp(date('Y', $now), 1, 1, 0, 0, 0);

$once = strtotime('this week');
$thisweek = make_timestamp(date('Y', $once), date('m', $once), date('d', $once), 0, 0, 0);

$once = strtotime('tomorrow');
$tomorrow = make_timestamp(date('Y', $once), date('m', $once), date('d', $once), 0, 0, 0);

$once = strtotime('next week');
$nextweek = make_timestamp(date('Y', $once), date('m', $once), date('d', $once), 0, 0, 0);

$once = strtotime('next month');
$nextmonth = make_timestamp(date('Y', $once), date('m', $once), 1, 0, 0, 0);

$dateformat = '%d %b %Y';

// Enrolment start.
$basemenu = [];
if ($course->startdate > 0) {
    $basemenu[2] = get_string('coursestart') . ' (' . userdate($course->startdate, $dateformat) . ')';
}
$basemenu[3] = get_string('thisyear', 'enrol_yafee') . ' (' . userdate($thisyear, $dateformat) . ')';
$basemenu[4] = get_string('monththis', 'calendar') . ' (' . userdate($thismonth, $dateformat) . ')';
$basemenu[5] = get_string('weekthis', 'calendar') . ' (' . userdate($thisweek, $dateformat) . ')';
$basemenu[6] = get_string('today') . ' (' . userdate($today, $dateformat) . ')';
$basemenu[7] = get_string('now', 'enrol_manual') . ' (' . userdate($now, get_string('strftimedatetimeshort')) . ')';
$basemenu[8] = get_string('tomorrow', 'calendar') . ' (' . userdate($tomorrow, $dateformat) . ')';
$basemenu[9] = get_string('weeknext', 'calendar') . ' (' . userdate($nextweek, $dateformat) . ')';
$basemenu[10] = get_string('monthnext', 'calendar') . ' (' . userdate($nextmonth, $dateformat) . ')';

// Set start time.
switch ($extendbase) {
    case 2:
        $timestart = $course->startdate;
        break;
    case 3:
        $timestart = $thisyear;
        break;
    case 4:
        $timestart = $thismonth;
        break;
    case 5:
        $timestart = $thisweek;
        break;
    case 7:
        $timestart = $now;
        break;
    case 8:
        $timestart = $tomorrow;
        break;
    case 9:
        $timestart = $nextweek;
        break;
    case 10:
        $timestart = $nextmonth;
        break;
    case 6:
    default:
        $timestart = $today;
        break;
}

// Build the list of options for the enrolment period dropdown.
$unlimitedperiod = get_string('unlimited');
$periodmenu = [];

// Build month and year.
$periodmenu = [];
$period = 0;
if ($instance->customchar1 == 'month' && $instance->customint7 > 0) {
    $period = strtotime('+' . $instance->customint7 . 'month', $timestart) - $timestart;
    $periodmenu[$period] = $instance->customint7 . ' ' . get_string('month');
} else if ($instance->customchar1 == 'year' && $instance->customint7 > 0) {
    $period = strtotime('+' . $instance->customint7 . 'year', $timestart) - $timestart;
    $periodmenu[$period] = $instance->customint7 . ' ' . get_string('year');
} else if ($instance->enrolperiod >= 86400 * 7) {
    $periodmenu[$instance->enrolperiod * 2] = get_string('numweeks', '', round($instance->enrolperiod * 2 / (86400 * 7)));
} else if ($instance->enrolperiod >= 86400) {
    $periodmenu[$instance->enrolperiod * 2] = get_string('numdays', '', round($instance->enrolperiod * 2 / 86400));
} else if ($instance->enrolperiod >= 3600) {
    $periodmenu[$instance->enrolperiod * 2] = get_string('numhours', '', round($instance->enrolperiod * 2 / 3600));
}

if ($period) {
    $defaultperiod = $period;
    $extendperiod = $period;
}

// Work out the apropriate default settings.
if ($extendperiod) {
    $defaultperiod = $extendperiod;
} else if ($instance->enrolperiod) {
    $defaultperiod = $instance->enrolperiod;
}
if ($instance->enrolperiod > 0 && !isset($periodmenu[$instance->enrolperiod])) {
    $periodmenu[$instance->enrolperiod] = format_time($instance->enrolperiod);
}

// Get groups.
$groups = [false => get_string('no')];
$grs = groups_get_all_groups($course->id);
foreach ($grs as $gr) {
    $groups[$gr->id] = $gr->name;
}
// Check groupid.
if ($groupid && !isset($groups[$groupid])) {
    $groupid = false;
}

// Process add and removes.
if ($canenrol && optional_param('add', false, PARAM_BOOL) && confirm_sesskey()) {
    $userstoassign = $potentialuserselector->get_selected_users();
    if (!empty($userstoassign)) {
        foreach ($userstoassign as $adduser) {
            if ($timeend) {
                $timeend = make_timestamp(
                    $timeend['year'],
                    $timeend['month'],
                    $timeend['day'],
                    $timeend['hour'],
                    $timeend['minute']
                );
            } else if ($extendperiod <= 0) {
                $timeend = 0;
            } else {
                $timeend = $timestart + $extendperiod;
            }

            $enrolyafee->enrol_user($instance, $adduser->id, $roleid, $timestart, $timeend);

            // Add to group.
            if ($groupid) {
                groups_add_member($groupid, $adduser->id);
            }
        }

        $potentialuserselector->invalidate_selected_users();
        $currentuserselector->invalidate_selected_users();
    }
}

// Process incoming role unassignments.
if ($canunenrol && optional_param('remove', false, PARAM_BOOL) && confirm_sesskey()) {
    $userstounassign = $currentuserselector->get_selected_users();
    if (!empty($userstounassign)) {
        foreach ($userstounassign as $removeuser) {
            $enrolyafee->unenrol_user($instance, $removeuser->id);
        }

        $potentialuserselector->invalidate_selected_users();
        $currentuserselector->invalidate_selected_users();
    }
}

echo $OUTPUT->header();
echo $OUTPUT->heading($title);

$addenabled = $canenrol ? '' : 'disabled="disabled"';
$removeenabled = $canunenrol ? '' : 'disabled="disabled"';

?>
<form id="assignform" method="post" action="<?php
    /**
     * Fake
     *
     * @package    enrol_yafee
     * @copyright 2025 Alex Orlov <snickser@gmail.com>
     * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
     */
    echo $PAGE->url ?>"><div>
  <input type="hidden" name="sesskey" value="<?php
    /**
     * Fake
     *
     * @package    enrol_yafee
     * @copyright 2025 Alex Orlov <snickser@gmail.com>
     * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
     */
    echo sesskey() ?>"/>
  <table summary="" class="roleassigntable generaltable generalbox boxaligncenter" cellspacing="0">
    <tr>
      <td id="existingcell">
          <p><label for="removeselect"><?php
            /**
             * Fake
             *
             * @package    enrol_yafee
             * @copyright 2025 Alex Orlov <snickser@gmail.com>
             * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
             */
            print_string('enrolledusers', 'enrol'); ?></label></p>
          <?php
            /**
             * Fake
             *
             * @package    enrol_yafee
             * @copyright 2025 Alex Orlov <snickser@gmail.com>
             * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
             */
                $currentuserselector->display() ?>
        </td>
      <td id="buttonscell">
          <div id="addcontrols" style="margin-top: 6.25rem;">
              <input class="btn btn-secondary" name="add" <?php
                /**
                 * Fake
                 *
                 * @package    enrol_yafee
                 * @copyright 2025 Alex Orlov <snickser@gmail.com>
                 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
                 */
                echo $addenabled; ?> id="add" type="submit"
                     value="<?php
                        /**
                         * Fake
                         *
                         * @package    enrol_yafee
                         * @copyright 2025 Alex Orlov <snickser@gmail.com>
                         * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
                         */
                        echo $OUTPUT->larrow() . '&nbsp;' . get_string('add'); ?>"
                        title="<?php print_string('add'); ?>" /><br />

              <div class="enroloptions">

              <p><label for="menuroleid"><?php
                /**
                 * Fake
                 *
                 * @package    enrol_yafee
                 * @copyright 2025 Alex Orlov <snickser@gmail.com>
                 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
                 */
                print_string('assignrole', 'enrol_manual') ?></label><br />
              <?php
                /**
                 * Fake
                 *
                 * @package    enrol_yafee
                 * @copyright 2025 Alex Orlov <snickser@gmail.com>
                 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
                 */
                echo html_writer::select($roles, 'roleid', $roleid, false); ?></p>
              <p><label for="menugroups"><?php
                /**
                 * Fake
                 *
                 * @package    enrol_yafee
                 * @copyright 2025 Alex Orlov <snickser@gmail.com>
                 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
                 */
                print_string('addgroup', 'enrol_cohort') ?></label><br />
              <?php
                /**
                 * Fake
                 *
                 * @package    enrol_yafee
                 * @copyright 2025 Alex Orlov <snickser@gmail.com>
                 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
                 */
                echo html_writer::select($groups, 'groupid', $groupid, false); ?></p>
              <p><label for="menuextendperiod"><?php
                /**
                 * Fake
                 *
                 * @package    enrol_yafee
                 * @copyright 2025 Alex Orlov <snickser@gmail.com>
                 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
                 */
                print_string('enrolperiod', 'enrol') ?></label><br />
              <?php
                /**
                 * Fake
                 *
                 * @package    enrol_yafee
                 * @copyright 2025 Alex Orlov <snickser@gmail.com>
                 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
                 */
                echo html_writer::select($periodmenu, 'extendperiod', $defaultperiod, $unlimitedperiod); ?></p>

              <p><label for="menuextendbase"><?php print_string('startingfrom') ?></label><br />
              <?php
                /**
                 * Fake
                 *
                 * @package    enrol_yafee
                 * @copyright 2025 Alex Orlov <snickser@gmail.com>
                 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
                 */
                echo html_writer::select($basemenu, 'extendbase', $extendbase, false); ?></p>

              </div>
          </div>

          <div id="removecontrols">
              <input class="btn btn-secondary" name="remove" id="remove" <?php
                /**
                 * Fake
                 *
                 * @package    enrol_yafee
                 * @copyright 2025 Alex Orlov <snickser@gmail.com>
                 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
                 */
                echo $removeenabled; ?> type="submit"
                     value="<?php echo get_string('remove') . '&nbsp;' . $OUTPUT->rarrow(); ?>"
                     title="<?php print_string('remove'); ?>" />
          </div>
      </td>
      <td id="potentialcell">
          <p><label for="addselect"><?php
                /**
                 * Fake
                 *
                 * @package    enrol_yafee
                 * @copyright 2025 Alex Orlov <snickser@gmail.com>
                 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
                 */
            print_string('enrolcandidates', 'enrol'); ?></label></p>
          <?php
                /**
                 * Fake
                 *
                 * @package    enrol_yafee
                 * @copyright 2025 Alex Orlov <snickser@gmail.com>
                 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
                 */
            $potentialuserselector->display() ?>
      </td>
    </tr>
  </table>
</div></form>
<?php
    /**
     * Fake
     *
     * @package    enrol_yafee
     * @copyright 2025 Alex Orlov <snickser@gmail.com>
     * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
     */

echo $OUTPUT->footer();
