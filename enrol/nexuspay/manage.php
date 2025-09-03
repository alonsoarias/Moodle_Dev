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
 * @package    enrol_nexuspay
 * @copyright 2024 Alonso Arias <soporte@nexuslabs.com.co>
 * @author    Alonso Arias
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

$instance = $DB->get_record('enrol', ['id' => $enrolid, 'enrol' => 'nexuspay'], '*', MUST_EXIST);
$course = $DB->get_record('course', ['id' => $instance->courseid], '*', MUST_EXIST);
$context = context_course::instance($course->id, MUST_EXIST);

require_login($course);

$canenrol = has_capability('enrol/nexuspay:enrol', $context);
$canunenrol = has_capability('enrol/nexuspay:unenrol', $context);

// Note: manage capability not used here because it is used for editing
// of existing enrolments which is not possible here.

if (!$canenrol || !$canunenrol) {
    // No need to invent new error strings here...
    require_capability('enrol/nexuspay:enrol', $context);
    require_capability('enrol/nexuspay:unenrol', $context);
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

if (!$enrolnexuspay = enrol_get_plugin('nexuspay')) {
    throw new coding_exception('Can not instantiate enrol_nexuspay');
}

$url = new moodle_url('/enrol/nexuspay/manage.php', ['enrolid' => $instance->id]);
$title = get_string('managemanualenrolements', 'enrol_nexuspay');

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
$basemenu[3] = get_string('thisyear', 'enrol_nexuspay') . ' (' . userdate($thisyear, $dateformat) . ')';
$basemenu[4] = get_string('monththis', 'calendar') . ' (' . userdate($thismonth, $dateformat) . ')';
$basemenu[5] = get_string('weekthis', 'calendar') . ' (' . userdate($thisweek, $dateformat) . ')';
$basemenu[6] = get_string('today') . ' (' . userdate($today, $dateformat) . ')';
$basemenu[7] = get_string('now', 'enrol_manual') . ' (' . userdate($now, get_string('strftimedatetimeshort')) . ')';
$basemenu[8] = get_string('tomorrow', 'calendar') . ' (' . userdate($tomorrow, $dateformat) . ')';
$basemenu[9] = get_string('weeknext', 'calendar') . ' (' . userdate($nextweek, $dateformat) . ')';
$basemenu[10] = get_string('monthnext', 'calendar') . ' (' . userdate($nextmonth, $dateformat) . ')';

// Set start time.
$startoptions = [];
if ($course->startdate > 0 && $course->startdate > $now) {
    $startoptions[2] = get_string('coursestart') . ' (' . userdate($course->startdate, $dateformat) . ')';
}
$startoptions[3] = get_string('today') . ' (' . userdate($today, $dateformat) . ')';
$startoptions[6] = get_string('now', 'enrol_manual') . ' (' . userdate($now, get_string('strftimedatetimeshort')) . ')';

// Build the list of options for the expiration dropdown.
$endmenu = [];
$endmenu[0] = get_string('never');
$endmenu[1] = get_string('duration');

// Build the list of options for groups.
require_once($CFG->dirroot . '/group/lib.php');
$groups = ['0' => get_string('nogroup', 'enrol')];
if (has_capability('moodle/course:managegroups', $context)) {
    $groups['-1'] = get_string('creategroup', 'enrol');
}
foreach (groups_get_all_groups($course->id) as $group) {
    $groups[$group->id] = format_string($group->name, true, ['context' => $context]);
}

// Process add and removes.
if (optional_param('add', false, PARAM_BOOL) && confirm_sesskey()) {
    $userstoassign = $potentialuserselector->get_selected_users();
    if (!empty($userstoassign)) {
        foreach ($userstoassign as $adduser) {
            $timestart = 0;
            $timeend = 0;
            $duration = 0;
            if ($extendperiod <= 0) {
                if ($instance->enrolperiod > 0) {
                    $timestart = $today;
                    $duration = $instance->enrolperiod;
                }
            } else if ($extendperiod > 0 && $extendbase < 3) {
                $duration = $extendperiod;
                switch ($extendbase) {
                    case 2:
                        $timestart = $course->startdate;
                        break;
                    case 6:
                    default:
                        $timestart = $today;
                        break;
                }
            }

            if ($duration > 0) {
                $timeend = $timestart + $duration;
            }

            $enrolnexuspay->enrol_user($instance, $adduser->id, $roleid, $timestart, $timeend);

            if ($groupid > 0) {
                groups_add_member($groupid, $adduser->id);
            } else if ($groupid == -1) {
                $newgroupname = trim(optional_param('newgroupname', '', PARAM_TEXT));
                if (!empty($newgroupname)) {
                    $newgroup = new stdClass();
                    $newgroup->name = $newgroupname;
                    $newgroup->courseid = $course->id;
                    $newgroup->description = '';
                    $newgroup->descriptionformat = FORMAT_HTML;
                    $newgroup->timecreated = time();
                    $newgroup->timemodified = time();
                    $newgroupid = groups_create_group($newgroup);
                    groups_add_member($newgroupid, $adduser->id);
                }
            }
        }
        $potentialuserselector->invalidate_selected_users();
    }
}

// Process removes.
if (optional_param('remove', false, PARAM_BOOL) && confirm_sesskey()) {
    $userstoremove = $currentuserselector->get_selected_users();
    if (!empty($userstoremove)) {
        foreach ($userstoremove as $removeuser) {
            $enrolnexuspay->unenrol_user($instance, $removeuser->id);
        }
        $currentuserselector->invalidate_selected_users();
    }
}

// Process time changes.
if (optional_param('timeextend', false, PARAM_BOOL) && confirm_sesskey()) {
    foreach ($timeend as $userid => $value) {
        $user = $DB->get_record('user_enrolments', ['userid' => $userid, 'enrolid' => $enrolid]);
        if ($user && $value !== false) {
            if ($value > 0) {
                $enrolnexuspay->update_user_enrol($instance, $userid, ENROL_USER_ACTIVE, $user->timestart, $value);
            } else {
                $enrolnexuspay->update_user_enrol($instance, $userid, ENROL_USER_ACTIVE, $user->timestart, 0);
            }
        }
    }
}

echo $OUTPUT->header();
echo $OUTPUT->heading($title);

$addenabled = $canenrol ? '' : 'disabled="disabled"';
$removeeabled = $canunenrol ? '' : 'disabled="disabled"';

// HTML for the user selectors and control buttons.
?>
<form id="assignform" method="post" action="<?php echo $PAGE->url ?>"><div>
<input type="hidden" name="sesskey" value="<?php echo sesskey() ?>" />

<table summary="" class="roleassigntable generaltable generalbox boxaligncenter" cellspacing="0">
<tr>
    <td id="existingcell">
        <p><label for="removeselect"><?php print_string('enrolledusers', 'enrol'); ?></label></p>
        <?php $currentuserselector->display() ?>
    </td>
    <td id="buttonscell">
      <div id="addcontrols">
          <input name="add" <?php echo $addenabled; ?> id="add" type="submit"
                       value="<?php echo $OUTPUT->larrow() . '&nbsp;' . get_string('add'); ?>"
                       title="<?php print_string('add'); ?>" /><br />

      <div class="enroloptions">

      <p><label for="menuroleid"><?php print_string('assignrole', 'enrol_manual') ?></label><br />
      <?php echo html_writer::select($roles, 'roleid', $roleid, false); ?></p>
      <p><label for="menugroups"><?php print_string('addgroup', 'enrol_cohort') ?></label><br />
      <?php echo html_writer::select($groups, 'groupid', $groupid, false); ?></p>
      <p><label for="menuextendperiod"><?php print_string('enrolperiod', 'enrol') ?></label><br />
      <?php
      $options = [0 => get_string('no')] + $basemenu;
      echo html_writer::select($options, 'extendperiod', $extendperiod, false);
      ?>
      </p>
      <p><label for="menuextendbase"><?php print_string('startingfrom') ?></label><br />
      <?php echo html_writer::select($startoptions, 'extendbase', $extendbase, false); ?></p>

      </div>
      </div>

      <div id="removecontrols">
          <input name="remove" <?php echo $removeeabled; ?> id="remove" type="submit"
                       value="<?php echo get_string('remove') . '&nbsp;' . $OUTPUT->rarrow(); ?>"
                       title="<?php print_string('remove'); ?>" />
      </div>
    </td>
    <td id="potentialcell">
        <p><label for="addselect"><?php print_string('enrolcandidates', 'enrol'); ?></label></p>
        <?php $potentialuserselector->display() ?>
    </td>
</tr>
</table>
</div></form>
<?php
echo $OUTPUT->footer();