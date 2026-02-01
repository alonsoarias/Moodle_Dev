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
 * Dashboard renderable class.
 *
 * @package    report_usage_monitor
 * @copyright  2025 Soporte IngeWeb <soporte@ingeweb.co>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace report_usage_monitor\output;

defined('MOODLE_INTERNAL') || die();

use renderable;
use templatable;
use renderer_base;
use stdClass;

require_once(__DIR__ . '/../../locallib.php');

/**
 * Dashboard renderable class for the usage monitor report.
 */
class dashboard implements renderable, templatable {

    /** @var stdClass Plugin configuration */
    private $config;

    /** @var array Formatted user daily records */
    private $userdailyrecords;

    /** @var array Formatted top user records */
    private $userdailytop;

    /** @var array Disk history data */
    private $diskhistory;

    /** @var array Largest courses */
    private $largestcourses;

    /** @var array Directory analysis */
    private $diranalysis;

    /**
     * Constructor.
     */
    public function __construct() {
        $this->config = get_config('report_usage_monitor');
        $this->load_data();
    }

    /**
     * Load all required data for the dashboard.
     */
    private function load_data(): void {
        global $DB;

        // Load user daily records.
        $this->userdailyrecords = $this->get_user_daily_records();

        // Load top user records.
        $this->userdailytop = $this->get_top_user_records();

        // Load disk history.
        $this->diskhistory = $this->get_disk_history();

        // Load largest courses.
        $this->largestcourses = $this->get_largest_courses();

        // Load directory analysis.
        $this->diranalysis = $this->get_directory_analysis();
    }

    /**
     * Get user daily records for last 10 days.
     *
     * @return array
     */
    private function get_user_daily_records(): array {
        // report_user_daily_sql() ya devuelve un array de registros.
        $records = report_user_daily_sql();

        $formatted = [];
        foreach ($records as $record) {
            $timestamp = (int)$record->timestamp_fecha;
            $formatted[] = [
                'users' => (int)$record->conteo_accesos_unicos,
                'date' => $this->format_date($timestamp),
                'timestamp' => $timestamp,
            ];
        }

        return $formatted;
    }

    /**
     * Get top user records.
     *
     * @return array
     */
    private function get_top_user_records(): array {
        // report_user_daily_top_sql() ya devuelve un array de registros.
        $records = report_user_daily_top_sql();

        $threshold = (int)($this->config->max_daily_users_threshold ?? 100);
        $formatted = [];

        foreach ($records as $record) {
            $timestamp = (int)$record->timestamp_fecha;
            $users = (int)$record->cantidad_usuarios;
            $percent = $threshold > 0 ? round(($users / $threshold) * 100, 1) : 0;

            $warningclass = '';
            if ($percent >= 90) {
                $warningclass = 'text-danger';
            } else if ($percent >= 70) {
                $warningclass = 'text-warning';
            }

            $formatted[] = [
                'users' => $users,
                'date' => $this->format_date($timestamp),
                'percent' => $percent,
                'warning_class' => $warningclass,
            ];
        }

        return $formatted;
    }

    /**
     * Get disk history data.
     *
     * @return array
     */
    private function get_disk_history(): array {
        global $DB;

        $monthago = time() - (30 * 24 * 60 * 60);
        $sql = "SELECT timecreated, value, percentage
                FROM {report_usage_monitor_history}
                WHERE type = 'disk' AND timecreated > ?
                ORDER BY timecreated ASC";
        $records = $DB->get_records_sql($sql, [$monthago]);

        $dailydata = [];
        foreach ($records as $record) {
            if (is_numeric($record->timecreated) && $record->timecreated > 0) {
                $datekey = date('Y-m-d', (int)$record->timecreated);
                $dailydata[$datekey] = [
                    'label' => $this->format_date((int)$record->timecreated),
                    'percentage' => round($record->percentage, 1),
                ];
            }
        }

        // Fill in missing dates.
        if (count($dailydata) < 30) {
            $alldates = [];
            for ($i = 29; $i >= 0; $i--) {
                $date = time() - ($i * 24 * 60 * 60);
                $datekey = date('Y-m-d', $date);
                $alldates[$datekey] = $this->format_date($date);
            }

            $combineddata = [];
            $lastvalue = null;

            foreach ($alldates as $datekey => $formatteddate) {
                if (isset($dailydata[$datekey])) {
                    $lastvalue = $dailydata[$datekey]['percentage'];
                    $combineddata[$datekey] = $dailydata[$datekey];
                } else {
                    $combineddata[$datekey] = [
                        'label' => $formatteddate,
                        'percentage' => $lastvalue,
                    ];
                }
            }

            ksort($combineddata);
            $dailydata = $combineddata;
        }

        $labels = [];
        $data = [];
        foreach ($dailydata as $item) {
            $labels[] = $item['label'];
            $data[] = $item['percentage'];
        }

        return [
            'labels' => $labels,
            'data' => $data,
        ];
    }

    /**
     * Get largest courses.
     *
     * @return array
     */
    private function get_largest_courses(): array {
        global $CFG;

        $coursesjson = $this->config->largest_courses ?? '[]';
        $courses = json_decode($coursesjson);

        if (empty($courses)) {
            $courses = get_largest_courses(5);
        }

        $formatted = [];
        foreach ($courses as $course) {
            if (!isset($course->id)) {
                continue;
            }

            $formatted[] = [
                'id' => (int)$course->id,
                'fullname' => format_string($course->fullname ?? ''),
                'shortname' => format_string($course->shortname ?? ''),
                'size' => display_size($course->totalsize ?? 0),
                'percentage' => (float)($course->percentage ?? 0),
                'backupcount' => (int)($course->backupcount ?? 0),
                'url' => $CFG->wwwroot . '/course/view.php?id=' . $course->id,
            ];
        }

        return $formatted;
    }

    /**
     * Get directory analysis.
     *
     * @return array
     */
    private function get_directory_analysis(): array {
        $analysisjson = $this->config->dir_analysis ?? '{}';
        $analysis = json_decode($analysisjson, true);

        if (empty($analysis) || !is_array($analysis)) {
            $analysis = [
                'database' => 0,
                'filedir' => 0,
                'cache' => 0,
                'others' => 0,
            ];
        }

        return $analysis;
    }

    /**
     * Format a timestamp to user-friendly date.
     *
     * @param int $timestamp Unix timestamp
     * @param bool $withtime Include time in output
     * @return string Formatted date
     */
    private function format_date(int $timestamp, bool $withtime = false): string {
        if ($timestamp <= 0) {
            return get_string('notcalculatedyet', 'report_usage_monitor');
        }

        $format = $withtime ? get_string('strftimedatetime', 'langconfig')
                           : get_string('strftimedate', 'langconfig');
        return userdate($timestamp, $format);
    }

    /**
     * Get warning class based on percentage.
     *
     * @param float $percent Percentage value
     * @return string CSS class
     */
    private function get_warning_class(float $percent): string {
        if ($percent < 70) {
            return 'bg-success';
        } else if ($percent < 90) {
            return 'bg-warning';
        }
        return 'bg-danger';
    }

    /**
     * Export data for template.
     *
     * @param renderer_base $output
     * @return array
     */
    public function export_for_template(renderer_base $output): array {
        global $CFG, $DB;

        // Calculate disk values.
        $diskusagebytes = (int)($this->config->totalusagereadable ?? 0)
                        + (int)($this->config->totalusagereadabledb ?? 0);
        $quotadiskbytes = ((int)($this->config->disk_quota ?? 0)) * 1024 * 1024 * 1024;

        $diskusagegb = display_size_in_gb($diskusagebytes, 2);
        $quotadiskgb = display_size_in_gb($quotadiskbytes, 2);
        $diskpercent = $quotadiskbytes > 0
                     ? round(($diskusagebytes / $quotadiskbytes) * 100, 1)
                     : 0;
        $diskwarningclass = $this->get_warning_class($diskpercent);

        // Calculate user values.
        $userstoday = (int)($this->config->totalusersdaily ?? 0);
        $maxthreshold = (int)($this->config->max_daily_users_threshold ?? 100);
        $userspercent = $maxthreshold > 0
                      ? round(($userstoday / $maxthreshold) * 100, 1)
                      : 0;
        $userswarningclass = $this->get_warning_class($userspercent);

        // Get timestamps.
        $lastdiskts = (int)($this->config->lastexecutioncalculate ?? 0);
        $lastuserts = (int)($this->config->lastexecution ?? 0);
        $max90datets = (int)($this->config->max_userdaily_for_90_days_date ?? 0);
        $lastcalc90ts = (int)($this->config->lastexecutioncalculateusers90days ?? 0);

        // System info.
        $totalcourses = $DB->count_records('course');
        $activeusers = $DB->count_records('user', ['deleted' => 0, 'suspended' => 0]) - 1;
        $suspendedusers = $DB->count_records('user', ['deleted' => 0, 'suspended' => 1]);
        $backupmaxkept = get_config('backup', 'backup_auto_max_kept') ?? 0;

        // Prepare chart data.
        $doughnutlabels = [
            get_string('database', 'report_usage_monitor'),
            get_string('files_dir', 'report_usage_monitor'),
            get_string('cache', 'report_usage_monitor'),
            get_string('others', 'report_usage_monitor'),
        ];
        $doughnutdata = [
            display_size_in_gb($this->diranalysis['database'] ?? 0, 2),
            display_size_in_gb($this->diranalysis['filedir'] ?? 0, 2),
            display_size_in_gb($this->diranalysis['cache'] ?? 0, 2),
            display_size_in_gb($this->diranalysis['others'] ?? 0, 2),
        ];

        // Prepare users chart data.
        $userlabels = [];
        $userdata = [];
        $userdataraw = [];
        foreach ($this->userdailyrecords as $record) {
            $userlabels[] = $record['date'];
            $percent = $maxthreshold > 0
                     ? min(100, round(($record['users'] / $maxthreshold) * 100, 1))
                     : 0;
            $userdata[] = $percent;
            $userdataraw[] = $record['users'];
        }

        // Directory table data.
        $directories = [];
        $dirkeys = [
            'database' => get_string('database', 'report_usage_monitor'),
            'filedir' => get_string('files_dir', 'report_usage_monitor'),
            'cache' => get_string('cache', 'report_usage_monitor'),
            'others' => get_string('others', 'report_usage_monitor'),
        ];
        foreach ($dirkeys as $key => $label) {
            $subbytes = (int)($this->diranalysis[$key] ?? 0);
            $subgb = display_size_in_gb($subbytes, 2);
            $percent = $diskusagebytes > 0 ? round(($subbytes / $diskusagebytes) * 100, 2) : 0;
            $directories[] = [
                'label' => $label,
                'size' => $subgb,
                'percent' => $percent,
            ];
        }

        // String translations for JS.
        $jsstrings = [
            'usersQuantity' => get_string('usersquantity', 'report_usage_monitor'),
            'warning70' => get_string('warning70', 'report_usage_monitor'),
            'critical90' => get_string('critical90', 'report_usage_monitor'),
            'limit100' => get_string('limit100', 'report_usage_monitor'),
            'percentOfThreshold' => get_string('percent_of_threshold', 'report_usage_monitor'),
            'percentageUsed' => get_string('percentage_used', 'report_usage_monitor'),
        ];

        return [
            'disclaimer' => get_string('exclusivedisclaimer', 'report_usage_monitor'),
            'credits' => get_string('reportinfotext', 'report_usage_monitor'),

            // Disk card.
            'disk_card' => [
                'title' => get_string('diskusage', 'report_usage_monitor'),
                'percent' => $diskpercent,
                'warning_class' => $diskwarningclass,
                'usage_gb' => $diskusagegb,
                'quota_gb' => $quotadiskgb,
                'last_execution_label' => get_string('lastexecutioncalculate', 'report_usage_monitor',
                                                     $this->format_date($lastdiskts, true)),
            ],

            // Users card.
            'users_card' => [
                'title' => get_string('users_today_card', 'report_usage_monitor'),
                'percent' => $userspercent,
                'warning_class' => $userswarningclass,
                'users_today' => $userstoday,
                'threshold' => $maxthreshold,
                'last_execution_label' => get_string('lastexecution', 'report_usage_monitor',
                                                     $this->format_date($lastuserts, true)),
            ],

            // Max 90 days card.
            'max90_card' => [
                'title' => get_string('max_userdaily_for_90_days', 'report_usage_monitor'),
                'max_users' => $this->config->max_userdaily_for_90_days_users
                             ?? get_string('notcalculatedyet', 'report_usage_monitor'),
                'threshold' => $maxthreshold,
                'show_date' => $max90datets > 0,
                'max_date' => $this->format_date($max90datets),
                'date_label' => get_string('date', 'report_usage_monitor'),
                'last_calculation' => $this->format_date($lastcalc90ts, true),
                'last_calc_label' => get_string('last_calculation', 'report_usage_monitor'),
            ],

            // Disk distribution.
            'disk_distribution' => [
                'title' => get_string('disk_usage_distribution', 'report_usage_monitor'),
                'has_data' => $diskusagebytes > 0,
                'no_data_message' => get_string('notcalculatedyet', 'report_usage_monitor'),
            ],

            // Disk tables.
            'disk_tables' => [
                'directories_title' => get_string('disk_usage_by_directory', 'report_usage_monitor'),
                'courses_title' => get_string('largest_courses', 'report_usage_monitor'),
                'has_data' => $diskusagebytes > 0,
                'has_courses' => !empty($this->largestcourses),
                'directories' => $directories,
                'courses' => $this->largestcourses,
                'no_data_message' => get_string('notcalculatedyet', 'report_usage_monitor'),
            ],

            // Disk history.
            'disk_history' => [
                'title' => get_string('disk_usage_history', 'report_usage_monitor'),
                'has_data' => !empty($this->diskhistory['labels']),
                'no_data_message' => get_string('notcalculatedyet', 'report_usage_monitor'),
            ],

            // Users last 10 days.
            'users_last10' => [
                'title' => get_string('lastusers', 'report_usage_monitor'),
                'tab_table' => get_string('usertable', 'report_usage_monitor'),
                'tab_chart' => get_string('userchart', 'report_usage_monitor'),
                'has_data' => !empty($this->userdailyrecords),
                'records' => $this->userdailyrecords,
                'no_data_message' => get_string('notcalculatedyet', 'report_usage_monitor'),
            ],

            // Top users.
            'top_users' => [
                'title' => get_string('topuser', 'report_usage_monitor'),
                'has_data' => !empty($this->userdailytop),
                'records' => $this->userdailytop,
                'no_data_message' => get_string('notcalculatedyet', 'report_usage_monitor'),
            ],

            // System info.
            'system_info' => [
                'title' => get_string('system_info', 'report_usage_monitor'),
                'moodle_version_label' => get_string('moodle_version', 'report_usage_monitor'),
                'moodle_version' => $CFG->release,
                'total_courses_label' => get_string('total_courses', 'report_usage_monitor'),
                'total_courses' => $totalcourses,
                'backup_label' => get_string('backup_per_course', 'report_usage_monitor'),
                'backup_count' => $backupmaxkept,
                'users_label' => get_string('registered_users', 'report_usage_monitor'),
                'active_users' => $activeusers,
                'suspended_users' => $suspendedusers,
                'active_label' => get_string('active_users', 'report_usage_monitor'),
                'suspended_label' => get_string('suspended_users', 'report_usage_monitor'),
            ],

            // Recommendations.
            'recommendations' => [
                'title' => get_string('recommendations', 'report_usage_monitor'),
                'show_disk_warning' => $diskpercent > 70,
                'disk_warning_level' => $diskpercent > 90 ? 'danger' : 'warning',
                'disk_tips_title' => get_string('space_saving_tips', 'report_usage_monitor'),
                'disk_tips' => [
                    get_string('tip_backups', 'report_usage_monitor', $backupmaxkept),
                    get_string('tip_files', 'report_usage_monitor'),
                    get_string('tip_courses', 'report_usage_monitor'),
                    get_string('tip_cache', 'report_usage_monitor'),
                ],
                'disk_ok_message' => get_string('disk_usage_ok', 'report_usage_monitor'),
                'show_user_warning' => $userspercent > 70,
                'user_warning_level' => $userspercent > 90 ? 'danger' : 'warning',
                'user_tips_title' => get_string('user_limit_tips', 'report_usage_monitor'),
                'user_tips' => [
                    get_string('tip_user_inactive', 'report_usage_monitor'),
                    get_string('tip_user_limit', 'report_usage_monitor'),
                ],
                'user_ok_message' => get_string('user_count_ok', 'report_usage_monitor'),
            ],

            // Common strings.
            'strings' => [
                'directory' => get_string('directory', 'report_usage_monitor'),
                'size' => get_string('size', 'report_usage_monitor'),
                'percentage' => get_string('percentage', 'report_usage_monitor'),
                'course' => get_string('course', 'report_usage_monitor'),
                'backup_count' => get_string('backup_count', 'report_usage_monitor'),
                'date' => get_string('date', 'report_usage_monitor'),
                'users_quantity' => get_string('usersquantity', 'report_usage_monitor'),
            ],

            // Chart data for AMD module.
            'chart_config' => json_encode([
                'doughnut' => [
                    'labels' => $doughnutlabels,
                    'data' => $doughnutdata,
                ],
                'usersLine' => [
                    'labels' => $userlabels,
                    'data' => $userdata,
                    'dataRaw' => $userdataraw,
                    'strings' => $jsstrings,
                ],
                'diskHistory' => [
                    'labels' => $this->diskhistory['labels'] ?? [],
                    'data' => $this->diskhistory['data'] ?? [],
                    'strings' => $jsstrings,
                ],
            ]),
        ];
    }
}
