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
 * Export knowledge base to JSON or CSV file (v3.6.0).
 *
 * Exports the complete knowledge base including all fields.
 * CSV now includes options as JSON array field for full compatibility.
 *
 * @package     local_educambot
 * @author      Alonso Arias <soporte@ingeweb.co>
 * @copyright   2025 Ingeweb <https://ingeweb.co>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');

require_login();
require_capability('local/educambot:manage', context_system::instance());
require_sesskey();

global $USER;

// Get export format.
$format = optional_param('format', 'json', PARAM_ALPHA);

// Get all data.
$categories = $DB->get_records('local_educambot_category', null, 'sortorder ASC');
$rules = $DB->get_records('local_educambot_rule', null, 'categoryid ASC, priority DESC, timecreated ASC');
$options = $DB->get_records('local_educambot_option', null, 'ruleid ASC, sortorder ASC');

// Build category name map for CSV export.
$categoryNames = [];
foreach ($categories as $cat) {
    $categoryNames[$cat->id] = $cat->name;
}

// Convert to arrays with ALL fields.
$exportcategories = [];
foreach ($categories as $cat) {
    $exportcategories[] = [
        'id' => (int)$cat->id,
        'name' => $cat->name,
        'description' => $cat->description ?? '',
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
        'category' => $rule->categoryid && isset($categoryNames[$rule->categoryid])
            ? $categoryNames[$rule->categoryid] : '',
        'pattern' => $rule->pattern,
        'keywords' => $rule->keywords ?? '',
        'response' => $rule->response,
        'tags' => $rule->tags ?? '',
        'enabled' => (int)$rule->enabled,
        'showoptions' => (int)$rule->showoptions,
        'contextaware' => (int)($rule->contextaware ?? 0),
        'dynamicresponse' => (int)($rule->dynamicresponse ?? 0),
        'requiredcontext' => $rule->requiredcontext ?? '',
        'roles' => $rule->roles ?? '',
        'courses' => $rule->courses ?? '',
        'lang' => $rule->lang ?? 'es',
        'langparent' => $rule->langparent ? (int)$rule->langparent : null,
        'helpfulcount' => (int)($rule->helpfulcount ?? 0),
        'nothelpfulcount' => (int)($rule->nothelpfulcount ?? 0),
        'priority' => (int)($rule->priority ?? 0),
        'needs_review' => (int)($rule->needs_review ?? 0),
        'useroleoptions' => (int)($rule->useroleoptions ?? 0),
    ];
}

$exportoptions = [];
$optionsByRule = [];
foreach ($options as $opt) {
    $optData = [
        'id' => (int)$opt->id,
        'ruleid' => (int)$opt->ruleid,
        'text' => $opt->text,
        'action' => $opt->action ?? '',
        'targetruleid' => $opt->targetruleid ? (int)$opt->targetruleid : null,
        'icon' => $opt->icon ?? '',
        'sortorder' => (int)$opt->sortorder,
        'enabled' => (int)$opt->enabled,
    ];
    $exportoptions[] = $optData;

    // Group options by rule ID for CSV export.
    if (!isset($optionsByRule[$opt->ruleid])) {
        $optionsByRule[$opt->ruleid] = [];
    }
    $optionsByRule[$opt->ruleid][] = [
        'text' => $opt->text,
        'action' => $opt->action ?? '',
        'icon' => $opt->icon ?? '',
    ];
}

if ($format === 'csv') {
    // Export as CSV with options included as JSON field (v3.6.0).
    // Using semicolon separator for Excel compatibility (Spanish/European locale).
    $filename = 'educambot_rules_' . date('Y-m-d_His') . '.csv';

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Cache-Control: no-cache, no-store, must-revalidate');
    header('Pragma: no-cache');
    header('Expires: 0');

    // CSV header - all fields including options as JSON (v3.6.0).
    $csvHeader = [
        'id',
        'category',
        'categoryid',
        'pattern',
        'keywords',
        'response',
        'tags',
        'enabled',
        'showoptions',
        'options',  // JSON array of options (v3.6.0).
        'contextaware',
        'dynamicresponse',
        'requiredcontext',
        'roles',
        'courses',
        'lang',
        'langparent',
        'helpfulcount',
        'nothelpfulcount',
        'priority',
        'needs_review',
        'useroleoptions',
    ];

    // Output BOM for Excel UTF-8 compatibility.
    echo "\xEF\xBB\xBF";

    // Output header with semicolon separator.
    $output = fopen('php://output', 'w');
    fputcsv($output, $csvHeader, ';', '"', '\\');

    // Output rows.
    foreach ($exportrules as $rule) {
        $row = [];
        foreach ($csvHeader as $field) {
            if ($field === 'options') {
                // Include options as JSON array (v3.6.0).
                $ruleId = $rule['id'] ?? 0;
                $ruleOptions = $optionsByRule[$ruleId] ?? [];
                $value = !empty($ruleOptions) ? json_encode($ruleOptions, JSON_UNESCAPED_UNICODE) : '';
            } else {
                $value = $rule[$field] ?? '';
            }
            // Handle multiline fields - preserve as literal \n for Excel.
            if (is_string($value)) {
                $value = str_replace(["\r\n", "\r", "\n"], "\\n", $value);
            }
            $row[] = $value;
        }
        fputcsv($output, $row, ';', '"', '\\');
    }

    fclose($output);
    exit;
} else {
    // Export as JSON (full export with categories, rules, options).
    $exportdata = [
        'version' => '3.6.0',
        'exported_at' => date('Y-m-d H:i:s'),
        'exported_by' => fullname($USER),
        'site' => $CFG->wwwroot,
        'statistics' => [
            'categories' => count($exportcategories),
            'rules' => count($exportrules),
            'options' => count($exportoptions),
        ],
        'field_descriptions' => [
            'pattern' => 'The main question pattern that triggers this rule',
            'keywords' => 'Additional keywords for matching (newline separated)',
            'response' => 'The response text (can contain HTML and placeholders)',
            'tags' => 'Comma-separated tags for categorization',
            'enabled' => '1=active, 0=disabled',
            'showoptions' => '1=show quick options after response, 0=hide',
            'contextaware' => '1=uses context data in response, 0=static',
            'dynamicresponse' => '1=has placeholders like {USER_NAME}, 0=static',
            'requiredcontext' => 'Required context: site, course, or activity',
            'roles' => 'Comma-separated role shortnames (student,teacher,manager)',
            'courses' => 'Comma-separated course IDs (empty = all courses)',
            'lang' => 'Language code (es, en, etc.)',
            'priority' => 'Manual priority for rule ordering (higher = first)',
            'useroleoptions' => '1=show role-specific menu options, 0=show rule options',
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
}
