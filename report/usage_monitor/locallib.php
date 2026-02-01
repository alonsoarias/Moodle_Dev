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
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * Local functions for report_usage_monitor.
 *
 * @package     report_usage_monitor
 * @category    admin
 * @copyright   2023 Soporte IngeWeb <soporte@ingeweb.co>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Check if the current hostname is valid for this plugin.
 *
 * The plugin is only authorized to run on moodlesoporte.net domains
 * (e.g., hera.moodlesoporte.net, zeus.moodlesoporte.net, etc.)
 *
 * @return bool True if hostname is valid, false otherwise.
 */
function report_usage_monitor_is_hostname_valid(): bool {
    global $CFG;

    // Valid hostname pattern for IngeWeb hosting.
    $validhostname = 'moodlesoporte.net';

    // First, check if wwwroot contains the valid hostname (case insensitive).
    // This covers cases where the full URL contains the valid domain.
    if (!empty($CFG->wwwroot) && stripos($CFG->wwwroot, $validhostname) !== false) {
        return true;
    }

    // Try to get hostname from wwwroot.
    $hostname = '';
    if (!empty($CFG->wwwroot)) {
        $parsed = parse_url($CFG->wwwroot);
        $hostname = $parsed['host'] ?? '';
    }

    // Fallback to server hostname.
    if (empty($hostname) && !empty($_SERVER['HTTP_HOST'])) {
        $hostname = $_SERVER['HTTP_HOST'];
    }

    // If no hostname could be determined, plugin is not authorized.
    if (empty($hostname)) {
        return false;
    }

    // Check if hostname contains the valid domain (case insensitive).
    // Valid examples: hera.moodlesoporte.net, zeus.moodlesoporte.net, moodlesoporte.net
    if (stripos($hostname, $validhostname) !== false) {
        return true;
    }

    return false;
}

/**
 * Check if the plugin is enabled based on hostname validation.
 *
 * The plugin is automatically disabled if the hostname does NOT contain
 * 'moodlesoporte.net' (unauthorized server).
 *
 * This plugin is exclusive to IngeWeb.co hosting services.
 *
 * @return bool True if plugin is enabled, false otherwise.
 */
function report_usage_monitor_is_enabled(): bool {
    return report_usage_monitor_is_hostname_valid();
}

/**
 * Get human-readable status of the plugin.
 *
 * @return array Array with 'status' (enabled|unauthorized) and 'reason' string.
 */
function report_usage_monitor_get_status(): array {
    if (!report_usage_monitor_is_hostname_valid()) {
        return [
            'status' => 'unauthorized',
            'reason' => get_string('pluginstatus_unauthorized', 'report_usage_monitor'),
        ];
    }

    return [
        'status' => 'enabled',
        'reason' => get_string('pluginstatus_enabled', 'report_usage_monitor'),
    ];
}

/**
 * Disable or enable all scheduled tasks for this plugin.
 *
 * When the hostname is not valid, all scheduled tasks are disabled.
 * When the hostname becomes valid, tasks are re-enabled.
 *
 * @param bool $enabled True to enable tasks, false to disable them.
 * @return void
 */
function report_usage_monitor_set_tasks_enabled(bool $enabled): void {
    global $DB;

    // List of all task class names for this plugin.
    $taskclasses = [
        '\report_usage_monitor\task\disk_usage',
        '\report_usage_monitor\task\last_users',
        '\report_usage_monitor\task\notification_disk',
        '\report_usage_monitor\task\notification_userlimit',
        '\report_usage_monitor\task\users_daily',
        '\report_usage_monitor\task\users_daily_90_days',
    ];

    $disabled = $enabled ? 0 : 1;

    foreach ($taskclasses as $classname) {
        $DB->set_field('task_scheduled', 'disabled', $disabled, ['classname' => $classname]);
    }
}

/**
 * Synchronize scheduled tasks state based on hostname validity.
 *
 * This function should be called during install, upgrade, and when settings change.
 * If hostname is invalid, all tasks are disabled. Otherwise, they are enabled.
 *
 * @return bool True if tasks are enabled (hostname valid), false if disabled.
 */
function report_usage_monitor_sync_tasks_state(): bool {
    $hostnamevalid = report_usage_monitor_is_hostname_valid();
    report_usage_monitor_set_tasks_enabled($hostnamevalid);
    return $hostnamevalid;
}

/**
 * Get SQL fragment for grouping by day in a cross-database compatible way.
 *
 * @param string $fieldname The timestamp field name.
 * @return string SQL fragment for day grouping.
 */
function report_usage_monitor_sql_day_group(string $fieldname): string {
    global $DB;

    $dbfamily = $DB->get_dbfamily();

    switch ($dbfamily) {
        case 'postgres':
            // PostgreSQL: Use date_trunc.
            return "EXTRACT(EPOCH FROM date_trunc('day', to_timestamp($fieldname)))";
        case 'mssql':
            // SQL Server: Use DATEADD/DATEDIFF.
            return "DATEDIFF(day, '1970-01-01', DATEADD(second, $fieldname, '1970-01-01')) * 86400";
        case 'oracle':
            // Oracle: Use TRUNC.
            return "TRUNC(TO_DATE('1970-01-01','YYYY-MM-DD') + ($fieldname/86400)) - TO_DATE('1970-01-01','YYYY-MM-DD')) * 86400";
        default:
            // MySQL/MariaDB and others: Use arithmetic.
            return "($fieldname - ($fieldname % 86400))";
    }
}

/**
 * Get user daily records for the last 10 days.
 * Cross-database compatible version.
 *
 * @return array Array of records with timestamp_fecha and conteo_accesos_unicos.
 */
function report_user_daily_sql(): array {
    global $DB;

    // Calculate timestamp constants.
    $todaystart = strtotime('today midnight');
    $tendaysago = strtotime('-10 days midnight');
    $yesterdayend = $todaystart - 1;

    $daygroupsql = report_usage_monitor_sql_day_group('timecreated');

    $sql = "SELECT $daygroupsql as timestamp_fecha,
                   COUNT(DISTINCT userid) as conteo_accesos_unicos
            FROM {logstore_standard_log}
            WHERE action = :action
              AND timecreated >= :starttime
              AND timecreated <= :endtime
            GROUP BY $daygroupsql
            ORDER BY timestamp_fecha DESC";

    $params = [
        'action' => 'loggedin',
        'starttime' => $tendaysago,
        'endtime' => $yesterdayend,
    ];

    return $DB->get_records_sql($sql, $params);
}

/**
 * Get top daily user records from the monitor table.
 *
 * @return array Array of records.
 */
function report_user_daily_top_sql(): array {
    global $DB;

    $sql = "SELECT id, fecha as timestamp_fecha, cantidad_usuarios
            FROM {report_usage_monitor}
            ORDER BY cantidad_usuarios DESC, fecha DESC";

    return $DB->get_records_sql($sql);
}

/**
 * Get top daily user records for task processing.
 *
 * @return array Array of records.
 */
function report_user_daily_top_task(): array {
    global $DB;

    $sql = "SELECT id, fecha, cantidad_usuarios
            FROM {report_usage_monitor}
            ORDER BY cantidad_usuarios DESC, fecha DESC";

    return $DB->get_records_sql($sql);
}

/**
 * Update the minimum record in the top users table.
 *
 * @param int $fecha Timestamp of the date to update.
 * @param int $usuarios Number of users.
 * @param int $min Minimum value to compare.
 */
function update_min_top_sql(int $fecha, int $usuarios, int $min): void {
    global $DB;

    // Validate timestamp.
    if ($fecha <= 0) {
        debugging('update_min_top_sql: Invalid timestamp provided: ' . $fecha, DEBUG_DEVELOPER);
        return;
    }

    // Use transaction for consistency.
    $transaction = $DB->start_delegated_transaction();

    try {
        // Get the oldest record with minimum users.
        $sql = "SELECT id, fecha FROM {report_usage_monitor}
                WHERE cantidad_usuarios = :min
                ORDER BY fecha ASC";
        $record = $DB->get_record_sql($sql, ['min' => $min], IGNORE_MULTIPLE);

        if ($record) {
            $DB->set_field('report_usage_monitor', 'fecha', $fecha, ['id' => $record->id]);
            $DB->set_field('report_usage_monitor', 'cantidad_usuarios', $usuarios, ['id' => $record->id]);
        }

        $transaction->allow_commit();
    } catch (Exception $e) {
        $transaction->rollback($e);
        debugging('update_min_top_sql: Error updating record: ' . $e->getMessage(), DEBUG_DEVELOPER);
    }
}

/**
 * Insert a new record into the top users table.
 * Maintains only the 10 most recent records.
 *
 * @param int $fecha Timestamp of the date.
 * @param int $cantidadusuarios Number of users.
 */
function insert_top_sql(int $fecha, int $cantidadusuarios): void {
    global $DB;

    // Validate timestamp.
    if ($fecha <= 0) {
        debugging('insert_top_sql: Invalid timestamp provided: ' . $fecha, DEBUG_DEVELOPER);
        return;
    }

    // Use transaction for consistency.
    $transaction = $DB->start_delegated_transaction();

    try {
        // Insert the new record.
        $record = new stdClass();
        $record->fecha = $fecha;
        $record->cantidad_usuarios = $cantidadusuarios;
        $DB->insert_record('report_usage_monitor', $record);

        // Count and remove excess records.
        $count = $DB->count_records('report_usage_monitor');
        if ($count > 10) {
            // Get IDs of oldest records to delete.
            $sql = "SELECT id FROM {report_usage_monitor} ORDER BY fecha ASC";
            $records = $DB->get_records_sql($sql, [], 0, $count - 10);

            if (!empty($records)) {
                $ids = array_keys($records);
                $DB->delete_records_list('report_usage_monitor', 'id', $ids);

                if (debugging('', DEBUG_DEVELOPER)) {
                    mtrace("Removed " . count($ids) . " old records to maintain 10 record limit.");
                }
            }
        }

        $transaction->allow_commit();
    } catch (Exception $e) {
        $transaction->rollback($e);
        debugging('insert_top_sql: Error inserting record: ' . $e->getMessage(), DEBUG_DEVELOPER);
    }
}

/**
 * Get yesterday's user count.
 * Cross-database compatible version.
 *
 * @return array Array with user count and timestamp.
 */
function user_limit_daily_sql(): array {
    global $DB;

    $yesterdaystart = strtotime('yesterday midnight');
    $todaystart = strtotime('today midnight');
    $daygroupsql = report_usage_monitor_sql_day_group('timecreated');

    $sql = "SELECT COUNT(DISTINCT userid) as conteo_accesos_unicos,
                   $daygroupsql as timestamp_fecha
            FROM {logstore_standard_log}
            WHERE action = :action
              AND timecreated >= :starttime
              AND timecreated < :endtime
            GROUP BY $daygroupsql";

    $params = [
        'action' => 'loggedin',
        'starttime' => $yesterdaystart,
        'endtime' => $todaystart,
    ];

    return $DB->get_records_sql($sql, $params);
}

/**
 * Get yesterday's user limit for task processing.
 *
 * @return array Array with date and user count.
 */
function user_limit_daily_task(): array {
    global $DB;

    $yesterdaystart = strtotime('yesterday midnight');
    $todaystart = strtotime('today midnight');
    $daygroupsql = report_usage_monitor_sql_day_group('timecreated');

    $sql = "SELECT $daygroupsql as fecha,
                   COUNT(DISTINCT userid) as conteo_accesos_unicos
            FROM {logstore_standard_log}
            WHERE action = :action
              AND timecreated >= :starttime
              AND timecreated < :endtime
            GROUP BY $daygroupsql";

    $params = [
        'action' => 'loggedin',
        'starttime' => $yesterdaystart,
        'endtime' => $todaystart,
    ];

    return $DB->get_records_sql($sql, $params);
}

/**
 * Get users connected today.
 *
 * @return array Array of records.
 */
function users_today(): array {
    global $DB;

    $onedayago = time() - 86400;
    $daygroupsql = report_usage_monitor_sql_day_group('lastaccess');

    $sql = "SELECT $daygroupsql as timestamp_fecha,
                   COUNT(DISTINCT id) as conteo_accesos_unicos
            FROM {user}
            WHERE lastaccess >= :threshold
            GROUP BY $daygroupsql";

    return $DB->get_records_sql($sql, ['threshold' => $onedayago]);
}

/**
 * Get maximum user accesses in the last 90 days.
 *
 * @return object|false Record with fecha and usuarios, or false.
 */
function max_userdaily_for_90_days() {
    global $DB;

    $ninetydaysago = time() - (90 * 86400);
    $daygroupsql = report_usage_monitor_sql_day_group('timecreated');

    $sql = "SELECT $daygroupsql as fecha,
                   COUNT(DISTINCT userid) as usuarios
            FROM {logstore_standard_log}
            WHERE action = :action
              AND timecreated >= :threshold
            GROUP BY $daygroupsql
            ORDER BY usuarios DESC, fecha DESC";

    $params = [
        'action' => 'loggedin',
        'threshold' => $ninetydaysago,
    ];

    return $DB->get_record_sql($sql, $params, IGNORE_MULTIPLE);
}

/**
 * Get database size in a cross-database compatible way.
 *
 * @return int Database size in bytes.
 */
function size_database(): int {
    global $DB, $CFG;

    $dbfamily = $DB->get_dbfamily();

    try {
        switch ($dbfamily) {
            case 'mysql':
            case 'mariadb':
                $sql = "SELECT ROUND(SUM(DATA_LENGTH + INDEX_LENGTH)) AS size
                        FROM information_schema.TABLES
                        WHERE TABLE_SCHEMA = :dbname";
                $result = $DB->get_record_sql($sql, ['dbname' => $CFG->dbname]);
                return (int)($result->size ?? 0);

            case 'postgres':
                $sql = "SELECT pg_database_size(current_database()) AS size";
                $result = $DB->get_record_sql($sql);
                return (int)($result->size ?? 0);

            case 'mssql':
                // SQL Server - get size from sys.database_files.
                $sql = "SELECT SUM(size * 8 * 1024) AS size FROM sys.database_files";
                $result = $DB->get_record_sql($sql);
                return (int)($result->size ?? 0);

            case 'oracle':
                // Oracle - estimate from user segments.
                $sql = "SELECT SUM(bytes) AS size FROM user_segments";
                $result = $DB->get_record_sql($sql);
                return (int)($result->size ?? 0);

            default:
                // Fallback: estimate from files table.
                $sql = "SELECT SUM(filesize) AS size FROM {files}";
                $result = $DB->get_record_sql($sql);
                return (int)($result->size ?? 0);
        }
    } catch (Exception $e) {
        debugging('size_database: Error calculating database size: ' . $e->getMessage(), DEBUG_DEVELOPER);
        return 0;
    }
}

/**
 * Generate a user info object for email operations.
 *
 * @param string $email Email address.
 * @param string $name Optional name.
 * @param int $id Optional user ID.
 * @return stdClass User object.
 */
function generate_email_user(string $email, string $name = '', int $id = -99): stdClass {
    $emailuser = new stdClass();

    // Sanitize email.
    $email = trim($email);
    $emailuser->email = filter_var($email, FILTER_VALIDATE_EMAIL) ? $email : '';

    // Sanitize name using clean_param instead of deprecated FILTER_SANITIZE_STRING.
    $emailuser->firstname = clean_param(trim($name), PARAM_TEXT);
    $emailuser->lastname = '';
    $emailuser->maildisplay = true;
    $emailuser->mailformat = 1; // HTML emails.
    $emailuser->id = $id;
    $emailuser->firstnamephonetic = '';
    $emailuser->lastnamephonetic = '';
    $emailuser->middlename = '';
    $emailuser->alternatename = '';

    return $emailuser;
}

/**
 * Calculate directory size.
 * Uses 'du' command when available for better performance.
 *
 * @param string $rootdir Directory to calculate.
 * @param string $excludefile File to exclude.
 * @return int Size in bytes.
 */
function directory_size(string $rootdir, string $excludefile = ''): int {
    global $CFG;

    // Try using 'du' command on Linux if available.
    if (!empty($CFG->pathtodu) && is_executable(trim($CFG->pathtodu))) {
        $escapeddir = escapeshellarg($rootdir);
        $command = trim($CFG->pathtodu) . ' -Lsk ' . $escapeddir;

        if (PHP_OS_FAMILY === 'Linux') {
            // Use nice and ionice on Linux to reduce priority.
            $command = 'nice -n 19 ionice -c3 ' . $command;
        }

        if (!empty($excludefile)) {
            $command .= ' --exclude=' . escapeshellarg($excludefile);
        }

        $output = null;
        $returncode = null;
        exec($command, $output, $returncode);

        if ($returncode === 0 && is_array($output) && isset($output[0])) {
            // Convert from KB to bytes.
            return (int)$output[0] * 1024;
        }
    }

    // Fallback: Calculate recursively with PHP.
    if (!is_dir($rootdir)) {
        return 0;
    }

    $size = 0;

    try {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($rootdir, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iterator as $file) {
            if ($file->isFile() && ($excludefile === '' || $file->getFilename() !== $excludefile)) {
                $size += $file->getSize();
            }
        }
    } catch (Exception $e) {
        debugging('directory_size: Error calculating size: ' . $e->getMessage(), DEBUG_DEVELOPER);
    }

    return $size;
}

/**
 * Get SQL for calculating course file sizes including all relevant contexts.
 *
 * @return string SQL query.
 */
function enhanced_course_filesize_sql(): string {
    $contextcourse = CONTEXT_COURSE;
    $contextblock = CONTEXT_BLOCK;
    $contextmodule = CONTEXT_MODULE;

    return "SELECT id AS course, SUM(filesize) AS filesize
            FROM (
                SELECT c.id, f.filesize
                FROM {course} c
                JOIN {context} cx ON cx.contextlevel = $contextcourse AND cx.instanceid = c.id
                JOIN {files} f ON f.contextid = cx.id
                UNION ALL
                SELECT c.id, f.filesize
                FROM {block_instances} bi
                JOIN {context} cx1 ON cx1.contextlevel = $contextblock AND cx1.instanceid = bi.id
                JOIN {context} cx2 ON cx2.contextlevel = $contextcourse AND cx2.id = bi.parentcontextid
                JOIN {course} c ON c.id = cx2.instanceid
                JOIN {files} f ON f.contextid = cx1.id
                UNION ALL
                SELECT c.id, f.filesize
                FROM {course_modules} cm
                JOIN {context} cx ON cx.contextlevel = $contextmodule AND cx.instanceid = cm.id
                JOIN {course} c ON c.id = cm.course
                JOIN {files} f ON f.contextid = cx.id
            ) x
            GROUP BY id";
}

/**
 * Get SQL for calculating course backup sizes.
 *
 * @return string SQL query.
 */
function enhanced_course_backupsize_sql(): string {
    $contextcourse = CONTEXT_COURSE;

    return "SELECT id AS course, SUM(filesize) AS filesize
            FROM (
                SELECT c.id, f.filesize
                FROM {course} c
                JOIN {context} cx ON cx.contextlevel = $contextcourse AND cx.instanceid = c.id
                JOIN {files} f ON f.contextid = cx.id AND f.component = 'backup'
            ) x
            GROUP BY id";
}

/**
 * Analyze disk usage by directory.
 *
 * @param string $rootdir Root directory to analyze.
 * @return array Array with sizes for each directory type.
 */
function analyze_disk_usage_by_directory(string $rootdir): array {
    $directories = [
        'filedir' => $rootdir . '/filedir',
        'cache' => $rootdir . '/cache',
    ];

    $usage = [];
    $totalanalyzed = 0;

    // Calculate size of each directory.
    foreach ($directories as $key => $dir) {
        if (is_dir($dir)) {
            $size = directory_size($dir);
            $usage[$key] = $size;
            $totalanalyzed += $size;
        } else {
            $usage[$key] = 0;
        }
    }

    // Calculate total root size.
    $totalsize = directory_size($rootdir);

    // Calculate "others" as the difference.
    $usage['others'] = max(0, $totalsize - $totalanalyzed);

    // Add database size.
    $usage['database'] = size_database();

    return $usage;
}

/**
 * Get the largest courses by file size.
 *
 * @param int $limit Number of courses to return.
 * @return array Array of course objects.
 */
function get_largest_courses(int $limit = 5): array {
    global $DB;

    $filesql = enhanced_course_filesize_sql();

    // Use LEFT JOIN to include courses with no files (filesize will be NULL/0).
    $sql = "SELECT c.id, c.fullname, c.shortname, c.category, COALESCE(rc.filesize, 0) AS filesize
            FROM {course} c
            LEFT JOIN ($filesql) rc ON rc.course = c.id
            WHERE c.id <> :siteid
            ORDER BY filesize DESC";

    $courses = $DB->get_records_sql($sql, ['siteid' => SITEID], 0, $limit);

    // Get backup sizes.
    $backupsql = enhanced_course_backupsize_sql();
    $backupsizes = $DB->get_records_sql($backupsql);

    // Calculate total files size.
    $totalfilessize = $DB->get_field_sql("SELECT SUM(filesize) FROM {files} WHERE filesize > 0");

    $contextcourse = CONTEXT_COURSE;

    foreach ($courses as $course) {
        // Ensure filesize is numeric.
        $course->filesize = (int)($course->filesize ?? 0);

        // Add backup size.
        $course->backupsize = isset($backupsizes[$course->id]) ? (int)$backupsizes[$course->id]->filesize : 0;

        // Count backups.
        $course->backupcount = $DB->count_records_sql(
            "SELECT COUNT(f.id)
             FROM {files} f
             JOIN {context} ctx ON f.contextid = ctx.id
             WHERE ctx.instanceid = :courseid
               AND ctx.contextlevel = $contextcourse
               AND f.component = 'backup'
               AND f.filearea = 'automated'",
            ['courseid' => $course->id]
        );

        // Calculate percentage.
        $course->percentage = ($totalfilessize > 0 && $course->filesize > 0)
            ? round(($course->filesize / $totalfilessize) * 100, 2)
            : 0;

        // Total size including backups.
        $course->totalsize = $course->filesize + $course->backupsize;
    }

    return $courses;
}

/**
 * Convert bytes to gigabytes.
 *
 * @param mixed $sizeinbytes Size in bytes.
 * @param int $precision Decimal places.
 * @return float Size in GB.
 */
function display_size_in_gb($sizeinbytes, int $precision = 2): float {
    if (!is_numeric($sizeinbytes) || $sizeinbytes === null) {
        return 0.0;
    }

    return round($sizeinbytes / (1024 * 1024 * 1024), $precision);
}

/**
 * Calculate percentage of threshold usage.
 *
 * @param mixed $currentvalue Current value.
 * @param mixed $threshold Threshold value.
 * @return float Percentage.
 */
function calculate_threshold_percentage($currentvalue, $threshold): float {
    if (!is_numeric($currentvalue)) {
        $currentvalue = 0;
    }

    if (!is_numeric($threshold) || $threshold <= 0) {
        return 0.0;
    }

    return ($currentvalue / $threshold) * 100;
}

/**
 * Calculate growth rate for users or disk.
 *
 * @param string $type Type: 'users' or 'disk'.
 * @param int $days Number of days to analyze.
 * @return float Growth rate percentage.
 */
function calculate_growth_rate(string $type = 'users', int $days = 30): float {
    global $DB;

    if (!in_array($type, ['users', 'disk'])) {
        return 0.0;
    }

    if ($days <= 0) {
        $days = 30;
    }

    if ($type === 'users') {
        $now = time();
        $dayseconds = 86400;

        // Get first day users.
        $sql1 = "SELECT COUNT(DISTINCT userid) as cnt
                 FROM {logstore_standard_log}
                 WHERE action = :action
                   AND timecreated >= :starttime
                   AND timecreated < :endtime";

        $firstdayusers = $DB->get_field_sql($sql1, [
            'action' => 'loggedin',
            'starttime' => $now - ($days * $dayseconds),
            'endtime' => $now - (($days - 1) * $dayseconds),
        ]);

        // Get last day users.
        $lastdayusers = $DB->get_field_sql($sql1, [
            'action' => 'loggedin',
            'starttime' => $now - $dayseconds,
            'endtime' => $now,
        ]);

        if ($firstdayusers == 0) {
            return 0.0;
        }

        return round((($lastdayusers - $firstdayusers) / $firstdayusers) * 100, 2);

    } else if ($type === 'disk') {
        $timethreshold = time() - ($days * 86400);

        $sql = "SELECT MIN(timecreated) AS oldest_time, MAX(timecreated) AS newest_time,
                       MIN(value) AS oldest_size, MAX(value) AS newest_size
                FROM {report_usage_monitor_history}
                WHERE type = :type
                  AND timecreated > :threshold";

        $result = $DB->get_record_sql($sql, ['type' => 'disk', 'threshold' => $timethreshold]);

        if ($result && $result->oldest_time && $result->oldest_size > 0) {
            $timediff = $result->newest_time - $result->oldest_time;
            $sizediff = $result->newest_size - $result->oldest_size;

            if ($timediff > 0 && $sizediff != 0) {
                $daysdiff = $timediff / 86400;
                $dailychange = $sizediff / $daysdiff;
                $dailypercent = ($dailychange / $result->oldest_size) * 100;

                return round($dailypercent * 30, 2);
            }
        }

        return 5.0; // Default 5% monthly growth.
    }

    return 0.0;
}

/**
 * Project days until threshold is reached.
 *
 * @param int $currentvalue Current value.
 * @param float $thresholdvalue Threshold to reach.
 * @param float $growthrate Growth rate percentage.
 * @return int Days until threshold, or special codes.
 */
function project_limit_date($currentvalue, $thresholdvalue, $growthrate): int {
    if (!is_numeric($currentvalue) || !is_numeric($thresholdvalue) || !is_numeric($growthrate)) {
        return PHP_INT_MAX;
    }

    // Already exceeded.
    if ($currentvalue >= $thresholdvalue) {
        return -1;
    }

    // No growth or negative growth.
    if ($growthrate <= 0) {
        return PHP_INT_MAX;
    }

    // Convert monthly percentage to daily decimal.
    $dailygrowthrate = ($growthrate / 100) / 30;

    if ($dailygrowthrate < 0.000001) {
        return PHP_INT_MAX;
    }

    try {
        $ratio = $thresholdvalue / $currentvalue;
        $logratio = log($ratio);
        $loggrowth = log(1 + $dailygrowthrate);

        if ($loggrowth == 0) {
            return PHP_INT_MAX;
        }

        $days = $logratio / $loggrowth;

        if (!is_finite($days)) {
            return PHP_INT_MAX;
        }

        return max(1, (int)ceil($days));
    } catch (Exception $e) {
        return PHP_INT_MAX;
    }
}

/**
 * Generate HTML rows for historical user data.
 *
 * @param int $limit Number of records.
 * @param int $maxthreshold Maximum threshold.
 * @return string HTML table rows.
 */
function generate_historical_data_html(int $limit = 10, int $maxthreshold = 100): string {
    global $DB;

    $html = '';
    $limitdaysago = time() - ($limit * 86400);
    $daygroupsql = report_usage_monitor_sql_day_group('timecreated');

    $sql = "SELECT $daygroupsql as fecha,
                   COUNT(DISTINCT userid) as usuarios
            FROM {logstore_standard_log}
            WHERE action = :action
              AND timecreated > :threshold
            GROUP BY $daygroupsql
            ORDER BY fecha DESC";

    $records = $DB->get_records_sql($sql, [
        'action' => 'loggedin',
        'threshold' => $limitdaysago,
    ], 0, $limit);

    foreach ($records as $record) {
        if (!is_numeric($record->fecha) || $record->fecha <= 0) {
            continue;
        }

        $percent = round(($record->usuarios / $maxthreshold) * 100, 1);
        $class = $percent < 70 ? '' : ($percent < 90 ? 'text-warning' : 'text-danger');
        $formatteddate = userdate((int)$record->fecha, get_string('strftimedate', 'langconfig'));

        $html .= '<tr>';
        $html .= '<td>' . $formatteddate . '</td>';
        $html .= '<td>' . (int)$record->usuarios . '</td>';
        $html .= '<td class="' . $class . '">' . $percent . '%</td>';
        $html .= '</tr>';
    }

    return $html;
}

/**
 * Generate HTML rows for top courses table.
 *
 * @param array $courses Array of course objects.
 * @return string HTML table rows.
 */
function generate_top_courses_html(array $courses): string {
    $html = '';

    foreach ($courses as $course) {
        $html .= '<tr>';
        $html .= '<td>' . format_string($course->fullname) . ' (' . format_string($course->shortname) . ')</td>';
        $html .= '<td>' . display_size($course->totalsize) . '</td>';
        $html .= '<td>' . $course->percentage . '%</td>';
        $html .= '</tr>';
    }

    return $html;
}

/**
 * Send email notification when user limit is exceeded.
 *
 * @param int $numberofusers Number of users.
 * @param int $fecha Timestamp.
 * @param float $percentage Usage percentage.
 * @return bool True if email sent successfully.
 */
function email_notify_user_limit(int $numberofusers, int $fecha, float $percentage): bool {
    global $CFG, $DB;

    if ($fecha <= 0) {
        $fecha = time();
    }

    $site = get_site();
    $reportconfig = get_config('report_usage_monitor');

    $a = new stdClass();
    $a->sitename = format_string($site->fullname);
    $a->threshold = (int)($reportconfig->max_daily_users_threshold ?? 100);
    $a->numberofusers = $numberofusers;
    $a->lastday = userdate($fecha, get_string('strftimedate', 'langconfig'));
    $a->referer = $CFG->wwwroot . '/report/usage_monitor/index.php';
    $a->siteurl = $CFG->wwwroot;
    $a->percentaje = round($percentage, 2);
    $a->excess_users = max(0, $numberofusers - $a->threshold);

    $a->moodle_version = $CFG->version;
    $a->moodle_release = $CFG->release;
    $a->courses_count = $DB->count_records('course');
    $a->backup_auto_max_kept = (int)get_config('backup', 'backup_auto_max_kept');

    $quotadisk = ((int)($reportconfig->disk_quota ?? 0)) * 1024 * 1024 * 1024;
    $diskusage = ((int)($reportconfig->totalusagereadable ?? 0))
               + ((int)($reportconfig->totalusagereadabledb ?? 0));
    $a->diskusage = display_size($diskusage);
    $a->quotadisk = display_size($quotadisk);
    $a->disk_percent = round(calculate_threshold_percentage($diskusage, $quotadisk), 2);

    $growthrate = calculate_growth_rate('users');
    $a->days_to_critical = project_limit_date($numberofusers, $a->threshold * 1.2, $growthrate);
    $a->critical_threshold = 120;

    $a->historical_data_rows = generate_historical_data_html(7, $a->threshold);

    $toemail = generate_email_user($reportconfig->email ?? '', '');
    $fromemail = generate_email_user($CFG->noreplyaddress, format_string($CFG->supportname ?? ''));

    $subject = get_string('subjectemail1', 'report_usage_monitor') . " {$a->sitename}";
    $messagehtml = get_string('messagehtml_userlimit', 'report_usage_monitor', $a);
    $messagetext = html_to_text($messagehtml);

    $previousnoemailever = $CFG->noemailever ?? false;
    $CFG->noemailever = false;
    $result = email_to_user($toemail, $fromemail, $subject, $messagetext, $messagehtml, '', '', true, $fromemail->email);
    $CFG->noemailever = $previousnoemailever;

    return $result;
}

/**
 * Send email notification about disk usage.
 *
 * @param int $quotadisk Disk quota in bytes.
 * @param int $diskusage Current usage in bytes.
 * @param float $diskpercent Usage percentage.
 * @param int $useraccesscount Number of active users.
 * @return bool True if email sent successfully.
 */
function email_notify_disk_limit(int $quotadisk, int $diskusage, float $diskpercent, int $useraccesscount): bool {
    global $CFG, $DB;

    $site = get_site();
    $reportconfig = get_config('report_usage_monitor');

    $a = new stdClass();
    $a->sitename = format_string($site->fullname);
    $a->quotadisk = display_size($quotadisk);
    $a->diskusage = display_size($diskusage);
    $a->percentage = round($diskpercent, 2);
    $a->databasesize = display_size($reportconfig->totalusagereadabledb ?? 0);
    $a->available_space = display_size($quotadisk - $diskusage);
    $a->available_percent = round(100 - $diskpercent, 2);

    $a->warning_level_class = $diskpercent < 70 ? 'warning-level-low' :
                             ($diskpercent < 90 ? 'warning-level-medium' : 'warning-level-high');

    $a->backupcount = (int)get_config('backup', 'backup_auto_max_kept');
    $a->threshold = (int)($reportconfig->max_daily_users_threshold ?? 100);
    $a->numberofusers = $useraccesscount;
    $a->referer = $CFG->wwwroot . '/report/usage_monitor/index.php';
    $a->siteurl = $CFG->wwwroot;
    $a->lastday = userdate(time(), get_string('strftimedate', 'langconfig'));
    $a->coursescount = $DB->count_records('course');
    $a->user_percent = round(calculate_threshold_percentage($useraccesscount, $a->threshold), 2);
    $a->moodle_version = $CFG->version;
    $a->moodle_release = $CFG->release;

    // Directory analysis.
    $diranalysisjson = $reportconfig->dir_analysis ?? '{}';
    $diranalysis = json_decode($diranalysisjson, true);
    if (empty($diranalysis) || !is_array($diranalysis)) {
        $diranalysis = analyze_disk_usage_by_directory($CFG->dataroot);
    }

    $safediskusage = $diskusage > 0 ? $diskusage : 1;
    $a->db_percent = round((($diranalysis['database'] ?? 0) / $safediskusage) * 100, 2);
    $a->filedir_size = display_size($diranalysis['filedir'] ?? 0);
    $a->filedir_percent = round((($diranalysis['filedir'] ?? 0) / $safediskusage) * 100, 2);
    $a->cache_size = display_size($diranalysis['cache'] ?? 0);
    $a->cache_percent = round((($diranalysis['cache'] ?? 0) / $safediskusage) * 100, 2);
    $a->other_size = display_size($diranalysis['others'] ?? 0);
    $a->other_percent = round((($diranalysis['others'] ?? 0) / $safediskusage) * 100, 2);

    // Largest courses.
    $largestcoursesjson = $reportconfig->largest_courses ?? '[]';
    $largestcourses = json_decode($largestcoursesjson);
    if (empty($largestcourses)) {
        $largestcourses = get_largest_courses(5);
    }
    $a->top_courses_rows = generate_top_courses_html((array)$largestcourses);

    $toemail = generate_email_user($reportconfig->email ?? '', '');
    $fromemail = generate_email_user($CFG->noreplyaddress, format_string($CFG->supportname ?? ''));

    $subject = get_string('subjectemail2', 'report_usage_monitor') . " {$a->sitename}";
    $messagehtml = get_string('messagehtml_diskusage', 'report_usage_monitor', $a);
    $messagetext = html_to_text($messagehtml);

    $previousnoemailever = $CFG->noemailever ?? false;
    $CFG->noemailever = false;
    $result = email_to_user($toemail, $fromemail, $subject, $messagetext, $messagehtml, '', '', true, $fromemail->email);
    $CFG->noemailever = $previousnoemailever;

    return $result;
}

/**
 * Update scheduled tasks configuration based on du command availability.
 *
 * When the path to 'du' command is detected and configured, this function
 * updates the disk_usage task to run more frequently (every 6 hours instead of 12).
 * It also schedules immediate execution of all plugin tasks if they haven't
 * run recently.
 *
 * @param string $pathtodu Path to the du command (empty if not available)
 * @return bool True if tasks were updated successfully
 */
function report_usage_monitor_update_scheduled_tasks(string $pathtodu = ''): bool {
    global $DB;

    $duavailable = !empty($pathtodu) && file_exists($pathtodu) && is_executable($pathtodu);

    // Get the disk_usage task record.
    $task = $DB->get_record('task_scheduled', [
        'classname' => '\\report_usage_monitor\\task\\disk_usage'
    ]);

    if ($task) {
        // Update the hour setting based on du availability.
        $newhour = $duavailable ? '*/6' : '12';

        if ($task->hour !== $newhour) {
            $DB->set_field('task_scheduled', 'hour', $newhour, ['id' => $task->id]);

            // Also reset the next run time to ensure the new schedule takes effect.
            $DB->set_field('task_scheduled', 'nextruntime', time(), ['id' => $task->id]);

            // Log the change.
            if (debugging('', DEBUG_DEVELOPER)) {
                mtrace("report_usage_monitor: Tarea disk_usage actualizada a ejecutarse cada " .
                       ($duavailable ? '6' : '12') . " horas.");
            }
        }
    }

    // If du is now available, schedule immediate execution of all tasks.
    if ($duavailable) {
        report_usage_monitor_schedule_tasks_now();
    }

    return true;
}

/**
 * Schedule all plugin tasks to run as soon as possible.
 *
 * This function sets the next run time of all plugin scheduled tasks to now,
 * so they will be picked up by the next cron execution.
 *
 * @return void
 */
function report_usage_monitor_schedule_tasks_now(): void {
    global $DB;

    $taskclasses = [
        '\\report_usage_monitor\\task\\disk_usage',
        '\\report_usage_monitor\\task\\last_users',
        '\\report_usage_monitor\\task\\users_daily',
        '\\report_usage_monitor\\task\\users_daily_90_days',
        '\\report_usage_monitor\\task\\notification_disk',
        '\\report_usage_monitor\\task\\notification_userlimit',
    ];

    $now = time();

    foreach ($taskclasses as $classname) {
        $task = $DB->get_record('task_scheduled', ['classname' => $classname]);
        if ($task && $task->nextruntime > $now) {
            $DB->set_field('task_scheduled', 'nextruntime', $now, ['id' => $task->id]);
        }
    }

    // Store timestamp of when tasks were scheduled.
    set_config('tasks_scheduled_at', $now, 'report_usage_monitor');
}

/**
 * Execute all plugin tasks immediately.
 *
 * This function directly executes the main plugin tasks to populate
 * dashboard data immediately after installation or upgrade.
 * Only executes the essential tasks for initial data: disk_usage, last_users,
 * users_daily, and users_daily_90_days.
 *
 * @return array Results of task execution with task name as key and success status as value
 */
function report_usage_monitor_run_tasks_now(): array {
    global $CFG;

    $results = [];

    // List of essential tasks to run for initial data (excluding notifications).
    $taskclasses = [
        'disk_usage' => '\\report_usage_monitor\\task\\disk_usage',
        'last_users' => '\\report_usage_monitor\\task\\last_users',
        'users_daily' => '\\report_usage_monitor\\task\\users_daily',
        'users_daily_90_days' => '\\report_usage_monitor\\task\\users_daily_90_days',
    ];

    foreach ($taskclasses as $name => $classname) {
        try {
            if (class_exists($classname)) {
                $task = new $classname();
                $task->execute();
                $results[$name] = true;

                if (debugging('', DEBUG_DEVELOPER)) {
                    mtrace("report_usage_monitor: Tarea $name ejecutada correctamente.");
                }
            } else {
                $results[$name] = false;
                if (debugging('', DEBUG_DEVELOPER)) {
                    mtrace("report_usage_monitor: Clase $classname no encontrada.");
                }
            }
        } catch (\Exception $e) {
            $results[$name] = false;
            if (debugging('', DEBUG_DEVELOPER)) {
                mtrace("report_usage_monitor: Error ejecutando tarea $name: " . $e->getMessage());
            }
        }
    }

    // Store timestamp of when tasks were executed.
    set_config('tasks_executed_at', time(), 'report_usage_monitor');

    return $results;
}
