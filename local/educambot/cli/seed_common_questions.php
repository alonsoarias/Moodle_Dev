<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * CLI script to seed common student questions.
 *
 * @package     local_educambot
 * @copyright   2025 Educam
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('CLI_SCRIPT', true);

require(__DIR__ . '/../../../config.php');
require_once($CFG->libdir . '/clilib.php');
require_once($CFG->dirroot . '/local/educambot/classes/local/setup/common_questions_seed.php');

echo "\n========================================\n";
echo "EDUCAM BOT - SEED COMMON QUESTIONS\n";
echo "========================================\n\n";

echo "Executing seed...\n\n";

try {
    $result = \local_educambot\local\setup\common_questions_seed::seed();

    echo "✅ SEED COMPLETED SUCCESSFULLY\n\n";
    echo "Results:\n";
    echo "  - Created: {$result['created']} new rules\n";
    echo "  - Updated: {$result['updated']} existing rules\n";
    echo "  - Total: {$result['total']} rules processed\n\n";

    // v2025103008: Purge ALL caches to ensure new rules are loaded immediately.
    echo "Purging all Educam Bot caches...\n";
    \cache::make('local_educambot', 'rules')->purge();
    \cache::make('local_educambot', 'knowledge')->purge();
    \cache::make('local_educambot', 'knowledge_topics')->purge();
    \cache::make('local_educambot', 'knowledge_context')->purge();
    echo "✅ All Educam Bot caches purged\n\n";

    // v2025103008: Also purge Moodle's application cache for this component.
    echo "Purging Moodle component cache...\n";
    \cache_helper::purge_by_definition('local_educambot', 'rules');
    echo "✅ Component cache purged\n\n";

    echo "========================================\n";
    echo "NEXT STEPS\n";
    echo "========================================\n";
    echo "1. Test the bot with these questions:\n";
    echo "   - ¿Cómo enviar un trabajo?\n";
    echo "   - ¿Cómo ver mis calificaciones?\n";
    echo "   - How to submit assignment?\n\n";
    echo "2. If bot still doesn't respond, run diagnostic:\n";
    echo "   php local/educambot/cli/diagnose_bot.php\n\n";

} catch (\Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
    echo "\nStack trace:\n";
    echo $e->getTraceAsString() . "\n\n";
    exit(1);
}
