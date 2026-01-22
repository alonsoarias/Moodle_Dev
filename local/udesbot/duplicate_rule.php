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
 * Duplicate a rule with its options.
 *
 * @package     local_udesbot
 * @author      Alonso Arias <soporte@orioncloud.com.co>
 * @copyright   2025 OrionCloud<https://orioncloud.com.co>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');

require_login();
require_capability('local/udesbot:manage', context_system::instance());
require_sesskey();

$id = required_param('id', PARAM_INT);

// Get original rule.
$rule = $DB->get_record('local_udesbot_rule', ['id' => $id], '*', MUST_EXIST);

$now = time();

// Create copy of rule.
$newrule = clone $rule;
unset($newrule->id);
$newrule->pattern = $rule->pattern . ' (' . get_string('copy', 'local_udesbot') . ')';
$newrule->timecreated = $now;
$newrule->timemodified = $now;

$newid = $DB->insert_record('local_udesbot_rule', $newrule);

// Copy options.
$options = $DB->get_records('local_udesbot_option', ['ruleid' => $id]);
foreach ($options as $opt) {
    $newopt = clone $opt;
    unset($newopt->id);
    $newopt->ruleid = $newid;
    $DB->insert_record('local_udesbot_option', $newopt);
}

// Redirect to edit the new rule.
redirect(new moodle_url('/local/udesbot/manage.php', ['action' => 'edit', 'id' => $newid]),
    get_string('ruleduplicated', 'local_udesbot'),
    null, \core\output\notification::NOTIFY_SUCCESS);
