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
 * Export knowledge base to JSON file.
 *
 * @package     local_educambot
 * @copyright   2025 EducamBot Team
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');

require_login();
require_capability('local/educambot:manage', context_system::instance());
require_sesskey();

global $USER;

// Get all data.
$categories = $DB->get_records('local_educambot_category', null, 'sortorder ASC');
$rules = $DB->get_records('local_educambot_rule', null, 'timecreated ASC');
$options = $DB->get_records('local_educambot_option', null, 'ruleid ASC, sortorder ASC');

// Convert to arrays and clean up.
$exportcategories = [];
foreach ($categories as $cat) {
    $exportcategories[] = [
        'id' => (int)$cat->id,
        'name' => $cat->name,
        'description' => $cat->description,
        'parent' => $cat->parent ? (int)$cat->parent : null,
        'sortorder' => (int)$cat->sortorder,
        'enabled' => (int)$cat->enabled,
    ];
}

$exportrules = [];
foreach ($rules as $rule) {
    $exportrules[] = [
        'id' => (int)$rule->id,
        'categoryid' => $rule->categoryid ? (int)$rule->categoryid : null,
        'pattern' => $rule->pattern,
        'keywords' => $rule->keywords,
        'response' => $rule->response,
        'tags' => $rule->tags ?? '',
        'enabled' => (int)$rule->enabled,
        'showoptions' => (int)$rule->showoptions,
    ];
}

$exportoptions = [];
foreach ($options as $opt) {
    $exportoptions[] = [
        'id' => (int)$opt->id,
        'ruleid' => (int)$opt->ruleid,
        'text' => $opt->text,
        'targetruleid' => $opt->targetruleid ? (int)$opt->targetruleid : null,
        'icon' => $opt->icon,
        'sortorder' => (int)$opt->sortorder,
        'enabled' => (int)$opt->enabled,
    ];
}

// Build export data structure.
$exportdata = [
    'version' => '1.6.0',
    'exported_at' => date('Y-m-d H:i:s'),
    'exported_by' => fullname($USER),
    'site' => $CFG->wwwroot,
    'statistics' => [
        'categories' => count($exportcategories),
        'rules' => count($exportrules),
        'options' => count($exportoptions),
    ],
    'categories' => $exportcategories,
    'rules' => $exportrules,
    'options' => $exportoptions,
];

// Generate JSON.
$json = json_encode($exportdata, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

// Set headers for download.
$filename = 'educambot_export_' . date('Y-m-d_His') . '.json';

header('Content-Type: application/json; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Content-Length: ' . strlen($json));
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

echo $json;
exit;
