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
 * Schedule checker for bot availability.
 *
 * @package     local_educambot
 * @copyright   2025 EducamBot Team
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_educambot\bot;

defined('MOODLE_INTERNAL') || die();

/**
 * Class to check bot availability schedule.
 */
class schedule_checker {

    /**
     * Check if the bot is available now based on schedule.
     *
     * @return bool True if bot is available, false otherwise.
     */
    public static function is_available(): bool {
        global $DB;

        // Check if schedule is enabled in settings.
        $scheduleenabled = get_config('local_educambot', 'scheduleenabled');
        if (!$scheduleenabled) {
            return true; // Schedule not enforced, always available.
        }

        $now = time();
        $dayofweek = (int) date('w', $now); // 0=Sunday, 6=Saturday.
        $currenttime = date('H:i', $now);

        $schedule = $DB->get_record('local_educambot_schedule', [
            'dayofweek' => $dayofweek,
            'enabled' => 1,
        ]);

        if (!$schedule) {
            return false; // No schedule for today or disabled = not available.
        }

        // Check time range.
        return ($currenttime >= $schedule->timefrom && $currenttime <= $schedule->timeto);
    }

    /**
     * Get message when bot is offline.
     *
     * @return string Offline message with next available time.
     */
    public static function get_offline_message(): string {
        $nextavailable = self::get_next_available_time();
        return get_string('botoffline', 'local_educambot', $nextavailable);
    }

    /**
     * Calculate the next available time slot.
     *
     * @return string Formatted string with next available time.
     */
    public static function get_next_available_time(): string {
        global $DB;

        $now = time();
        $currentday = (int) date('w', $now);
        $currenttime = date('H:i', $now);
        $daynames = self::get_day_names();

        // Check remaining time today.
        $todayschedule = $DB->get_record('local_educambot_schedule', [
            'dayofweek' => $currentday,
            'enabled' => 1,
        ]);

        if ($todayschedule && $currenttime < $todayschedule->timefrom) {
            // Bot will be available later today.
            return get_string('todayat', 'local_educambot', $todayschedule->timefrom);
        }

        // Check next days.
        for ($i = 1; $i <= 7; $i++) {
            $nextday = ($currentday + $i) % 7;
            $nextschedule = $DB->get_record('local_educambot_schedule', [
                'dayofweek' => $nextday,
                'enabled' => 1,
            ]);

            if ($nextschedule) {
                $dayname = $daynames[$nextday];
                return get_string('dayat', 'local_educambot', [
                    'day' => $dayname,
                    'time' => $nextschedule->timefrom,
                ]);
            }
        }

        return get_string('notscheduled', 'local_educambot');
    }

    /**
     * Get all schedule records.
     *
     * @return array Array of schedule records.
     */
    public static function get_all_schedules(): array {
        global $DB;
        return $DB->get_records('local_educambot_schedule', [], 'dayofweek ASC');
    }

    /**
     * Get localized day names.
     *
     * @return array Array of day names indexed by day number.
     */
    public static function get_day_names(): array {
        return [
            0 => get_string('sunday', 'calendar'),
            1 => get_string('monday', 'calendar'),
            2 => get_string('tuesday', 'calendar'),
            3 => get_string('wednesday', 'calendar'),
            4 => get_string('thursday', 'calendar'),
            5 => get_string('friday', 'calendar'),
            6 => get_string('saturday', 'calendar'),
        ];
    }

    /**
     * Update a schedule record.
     *
     * @param int $id Schedule ID.
     * @param string $timefrom Start time HH:MM.
     * @param string $timeto End time HH:MM.
     * @param int $enabled Whether the day is enabled.
     * @return bool Success.
     */
    public static function update_schedule(int $id, string $timefrom, string $timeto, int $enabled): bool {
        global $DB;

        $schedule = $DB->get_record('local_educambot_schedule', ['id' => $id]);
        if (!$schedule) {
            return false;
        }

        $schedule->timefrom = $timefrom;
        $schedule->timeto = $timeto;
        $schedule->enabled = $enabled;

        return $DB->update_record('local_educambot_schedule', $schedule);
    }

    /**
     * Get formatted schedule summary for display.
     *
     * @return string HTML formatted schedule summary.
     */
    public static function get_schedule_summary(): string {
        $schedules = self::get_all_schedules();
        $daynames = self::get_day_names();
        $summary = [];

        foreach ($schedules as $schedule) {
            $dayname = $daynames[$schedule->dayofweek];
            if ($schedule->enabled) {
                $summary[] = "<strong>{$dayname}:</strong> {$schedule->timefrom} - {$schedule->timeto}";
            } else {
                $summary[] = "<strong>{$dayname}:</strong> " . get_string('disabled', 'local_educambot');
            }
        }

        return implode('<br>', $summary);
    }
}
