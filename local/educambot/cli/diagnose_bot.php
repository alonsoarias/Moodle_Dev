<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * Diagnostic script to check why bot is not responding.
 *
 * @package     local_educambot
 * @copyright   2025 Educam
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('CLI_SCRIPT', true);

require(__DIR__ . '/../../../config.php');
require_once($CFG->libdir . '/clilib.php');

echo "\n========================================\n";
echo "EDUCAM BOT DIAGNOSTIC SCRIPT\n";
echo "========================================\n\n";

// Check 1: Database connection.
echo "1. DATABASE CONNECTION\n";
echo "   Status: ";
try {
    $DB->get_manager();
    echo "✅ OK\n\n";
} catch (Exception $e) {
    echo "❌ FAILED: " . $e->getMessage() . "\n\n";
    exit(1);
}

// Check 2: Rules table exists.
echo "2. RULES TABLE\n";
$tables = $DB->get_tables();
if (in_array('local_educambot_rule', $tables)) {
    echo "   Table exists: ✅ YES\n";
} else {
    echo "   Table exists: ❌ NO - Plugin not installed correctly!\n\n";
    exit(1);
}

// Check 3: Count rules.
echo "   Total rules: ";
$totalrules = $DB->count_records('local_educambot_rule');
echo $totalrules . "\n";

echo "   Enabled rules: ";
$enabledrules = $DB->count_records('local_educambot_rule', ['enabled' => 1]);
echo $enabledrules . "\n";

if ($enabledrules == 0) {
    echo "   ⚠️  WARNING: NO ENABLED RULES FOUND!\n";
    echo "   This is why the bot doesn't respond.\n\n";
} else {
    echo "   ✅ Rules found\n\n";
}

// Check 4: Sample rules.
echo "3. SAMPLE RULES (First 5)\n";
$samplerules = $DB->get_records('local_educambot_rule', ['enabled' => 1], 'id ASC', 'id, pattern, keywords', 0, 5);
if (empty($samplerules)) {
    echo "   ❌ No rules to display\n\n";
} else {
    foreach ($samplerules as $rule) {
        echo "   - ID: {$rule->id}\n";
        echo "     Pattern: " . substr($rule->pattern, 0, 60) . "\n";
        echo "     Keywords: " . substr($rule->keywords ?? 'N/A', 0, 60) . "\n";
        echo "\n";
    }
}

// Check 5: Cache status.
echo "4. CACHE STATUS\n";
$cache = cache::make('local_educambot', 'rules');
$cachedrules = $cache->get('all');
if ($cachedrules === false) {
    echo "   Status: ⚠️  EMPTY (will be populated on first request)\n\n";
} else if (is_array($cachedrules)) {
    echo "   Status: ✅ POPULATED\n";
    echo "   Cached rules: " . count($cachedrules) . "\n\n";
} else {
    echo "   Status: ❓ UNKNOWN TYPE: " . gettype($cachedrules) . "\n\n";
}

// Check 6: Test a simple question.
echo "5. TEST QUESTION\n";
echo "   Testing: '¿Cómo enviar un trabajo?'\n\n";

try {
    require_once($CFG->dirroot . '/local/educambot/classes/bot/engine.php');

    $engine = new \local_educambot\bot\engine(null, '/course/view.php', null);
    $result = $engine->respond('¿Cómo enviar un trabajo?');

    echo "   Response found: " . ($result['response'] ? "✅ YES" : "❌ NO") . "\n";
    echo "   Confidence: " . ($result['confidence'] ?? 0) . "\n";
    echo "   Rule ID: " . ($result['ruleid'] ?? 'N/A') . "\n";

    if ($result['response']) {
        echo "   Response preview: " . substr(strip_tags($result['response']), 0, 100) . "...\n\n";
    } else {
        echo "   ❌ Bot returned no answer\n";
        echo "   Suggestions count: " . count($result['suggestions'] ?? []) . "\n\n";
    }

} catch (Exception $e) {
    echo "   ❌ ERROR: " . $e->getMessage() . "\n";
    echo "   " . $e->getTraceAsString() . "\n\n";
}

// Check 7: Seed availability.
echo "6. SEED AVAILABILITY\n";
$seedfile = $CFG->dirroot . '/local/educambot/classes/local/setup/common_questions_seed.php';
if (file_exists($seedfile)) {
    echo "   Seed file: ✅ EXISTS\n";
    echo "   Location: $seedfile\n\n";

    echo "   To manually execute seed, run:\n";
    echo "   php -r \"require_once('$seedfile'); \n";
    echo "   echo json_encode(\\local_educambot\\local\\setup\\common_questions_seed::seed());\"\n\n";
} else {
    echo "   Seed file: ❌ NOT FOUND\n\n";
}

// Summary.
echo "========================================\n";
echo "SUMMARY\n";
echo "========================================\n";
if ($enabledrules == 0) {
    echo "❌ CRITICAL ISSUE: No enabled rules found!\n";
    echo "   ACTION REQUIRED: Execute the common questions seed:\n";
    echo "   1. Via CLI:\n";
    echo "      cd " . $CFG->dirroot . "\n";
    echo "      php local/educambot/cli/seed_common_questions.php\n\n";
    echo "   2. Or visit: Site Administration → Plugins → Local plugins → Educam Bot\n";
    echo "      Look for 'Seed Common Questions' option\n\n";
} else if (!$result['response']) {
    echo "⚠️  WARNING: Rules exist but bot didn't match the test question\n";
    echo "   This might indicate:\n";
    echo "   1. Matching algorithm issue\n";
    echo "   2. Cache problem\n";
    echo "   3. Rules not properly formatted\n";
    echo "   ACTION: Try clearing all caches:\n";
    echo "   Site Administration → Development → Purge all caches\n\n";
} else {
    echo "✅ ALL CHECKS PASSED\n";
    echo "   Bot should be working correctly.\n\n";
}

echo "========================================\n\n";
