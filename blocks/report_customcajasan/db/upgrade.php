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
 * Upgrade steps for the report_customcajasan block.
 *
 * @package    block_report_customcajasan
 * @copyright  2025 Cajasan
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Execute block upgrade steps.
 *
 * @param int $oldversion
 * @return bool
 */
function xmldb_block_report_customcajasan_upgrade(int $oldversion): bool {
    global $DB;

    $systemcontext = context_system::instance();

    if ($oldversion < 2025102702) {
        $roles = get_roles_with_capability('moodle/course:update', CAP_ALLOW, $systemcontext);
        $capabilities = [
            'block/report_customcajasan:viewblock',
            'block/report_customcajasan:viewreport',
            'block/report_customcajasan:configurecourse',
        ];

        foreach ($roles as $role) {
            foreach ($capabilities as $capability) {
                $conditions = [
                    'roleid' => $role->id,
                    'capability' => $capability,
                    'contextid' => $systemcontext->id,
                ];

                if (!$DB->record_exists('role_capabilities', $conditions)) {
                    assign_capability($capability, CAP_ALLOW, $role->id, $systemcontext->id, true);
                }
            }
        }

        accesslib_clear_all_caches(true);

        upgrade_plugin_savepoint(true, 2025102702, 'block', 'report_customcajasan');
    }

    return true;
}
