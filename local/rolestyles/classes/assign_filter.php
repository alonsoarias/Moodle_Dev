<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

namespace local_rolestyles;

use assign_grading_table;

/**
 * Helper to filter assignment grading tables for selected roles.
 *
 * @package    local_rolestyles
 */
class assign_filter {
    /**
     * Determine if filtering is active for the current user.
     *
     * @return bool
     */
    public static function is_active(): bool {
        return !empty(self::active_roleids());
    }

    /**
     * Get the role IDs that are both configured and held by the current user.
     *
     * @return int[]
     */
    public static function active_roleids(): array {
        global $USER;

        if (!local_rolestyles_user_has_role()) {
            return [];
        }

        $configured = explode(',', (string) get_config('local_rolestyles', 'selected_roles'));
        $configured = array_filter(array_map('intval', $configured));
        if (empty($configured)) {
            return [];
        }

        $context = \local_rolestyles_get_context();
        $roles = get_user_roles($context, $USER->id, true);
        $active = [];
        foreach ($roles as $role) {
            $id = (int) $role->roleid;
            if (in_array($id, $configured, true)) {
                $active[$id] = $id;
            }
        }
        return array_values($active);
    }

    /**
     * Filter rows to only include participants with submitted work.
     *
     * @param assign_grading_table $table
     * @return array [filtered rows, total rows]
     */
    public static function apply(assign_grading_table $table): array {
        $rows = $table->rawdata ?? [];
        if ($rows instanceof \Traversable) {
            $rows = iterator_to_array($rows);
        }
        $total = count($rows);
        $filtered = array_filter($rows, static function($row) {
            $status = $row->status ?? '';
            return $status === ASSIGN_SUBMISSION_STATUS_SUBMITTED;
        });
        return [array_values($filtered), $total];
    }

    /**
     * Build a summary string for the filtered table.
     *
     * @param int $total
     * @param int $visible
     * @return string
     */
    public static function summary(int $total, int $visible): string {
        $a = (object) [
            'visible' => $visible,
            'total' => $total,
            'hidden' => max($total - $visible, 0),
        ];
        return get_string('filtersummary', 'local_rolestyles', $a);
    }
}
