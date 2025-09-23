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
 * CLI utility that synchronises the AmericasBPS service with every read-only external function.
 *
 * The script ensures the target service exists, inspects the installed external functions,
 * classifies the read-only ones using metadata and naming conventions requested by
 * AmericasBPS, and adds the eligible functions while removing write operations. A JSON
 * report is generated with the function documentation so it can be reviewed or shared with
 * consumers of the service.
 *
 * @package    core
 * @subpackage cli
 * @copyright  2025
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

declare(strict_types=1);

define('CLI_SCRIPT', true);

require(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/clilib.php');
require_once($CFG->dirroot . '/webservice/lib.php');

use core_external\external_api;
use core_external\external_description;
use core_external\external_function_parameters;
use core_external\external_multiple_structure;
use core_external\external_single_structure;
use core_external\external_value;

$defaultshortname = 'AmericasBPS';
$defaultworkdir = make_temp_directory('americasbps');
$defaultexport = $defaultworkdir . '/service_functions.json';
$defaultlog = $defaultworkdir . '/service_sync.log';

$longoptions = [
    'service' => $defaultshortname,
    'dry-run' => false,
    'verbose' => false,
    'export' => $defaultexport,
    'logfile' => $defaultlog,
    'help' => false,
];
$shortmapping = [
    's' => 'service',
    'n' => 'dry-run',
    'v' => 'verbose',
    'e' => 'export',
    'l' => 'logfile',
    'h' => 'help',
];

list($options, $unrecognized) = cli_get_params($longoptions, $shortmapping);

if ($unrecognized) {
    $unrecognized = implode("\n  ", $unrecognized);
    cli_error(get_string('cliunknowoption', 'admin', $unrecognized));
}

if (!empty($options['help'])) {
    $help = <<<HELP
Synchronise the AmericasBPS web service with all read-only external functions.

Options:
    --service=SHORTNAME   Web service shortname (default: {$defaultshortname}).
 -n --dry-run             Analyse and report without modifying the database.
 -v --verbose             Display detailed information while processing.
 -e --export=PATH         Path for the JSON documentation report. Use "none" to skip.
 -l --logfile=PATH        Destination file for the execution log. Use "stdout" to disable.
 -h --help                Show this help.

The script ensures the requested service exists (creating it when missing) and
then scans every installed external function using
core_external\external_api::external_function_info(). Functions declared with
"type => read" are always selected. Functions without a type declaration are
included if their name contains one of the patterns requested by AmericasBPS:
get, view, list, search, fetch or retrieve. Names containing write operations
(create, add, insert, update, edit, modify, delete, remove, set or assign) are
ignored and removed from the service if already present. Deprecated functions
are skipped automatically.

Example (dry run):
    php admin/cli/sync_americasbps_service.php --dry-run --verbose

Example (persist changes and export to a custom path):
    php admin/cli/sync_americasbps_service.php --export="/tmp/americasbps.json"
HELP;
    echo $help . "\n";
    exit(0);
}

$servicename = trim((string)$options['service']);
if ($servicename === '') {
    cli_error('The service shortname cannot be empty.');
}

$dryrun = !empty($options['dry-run']);
$verbose = !empty($options['verbose']);

$exportpath = (string)$options['export'];
$skipreport = false;
if ($exportpath === '' || strtolower($exportpath) === 'none' || strtolower($exportpath) === 'off') {
    $skipreport = true;
}

$logpath = (string)$options['logfile'];
$logtostdoutonly = false;
if ($logpath === '' || strtolower($logpath) === 'stdout') {
    $logtostdoutonly = true;
}

/**
 * Simple logger that mirrors messages to stdout and an optional file.
 */
class americasbps_logger {
    /** @var resource|null */
    private $handle = null;

    /** @var string|null */
    private $path = null;

    /**
     * @param string|null $path
     */
    public function __construct(?string $path) {
        global $CFG;
        $this->path = $path;
        if ($path !== null) {
            $directory = dirname($path);
            if (!is_dir($directory) && !mkdir($directory, $CFG->directorypermissions ?? 0777, true) && !is_dir($directory)) {
                cli_error("Unable to create log directory: {$directory}");
            }
            $this->handle = fopen($path, 'ab');
            if ($this->handle === false) {
                cli_error("Unable to open log file for writing: {$path}");
            }
        }
    }

    /**
     * Write a message to the log.
     *
     * @param string $message
     */
    public function log(string $message): void {
        $timestamp = date('Y-m-d H:i:s');
        $line = "[{$timestamp}] {$message}";
        mtrace($line);
        if ($this->handle) {
            fwrite($this->handle, $line . PHP_EOL);
        }
    }

    /**
     * Close the log file handle.
     */
    public function close(): void {
        if ($this->handle) {
            fclose($this->handle);
            $this->handle = null;
        }
    }
}

$logger = new americasbps_logger($logtostdoutonly ? null : $logpath);

$logger->log("Starting AmericasBPS service synchronisation (service: {$servicename}).");
if ($dryrun) {
    $logger->log('Dry-run mode enabled. No database changes will be performed.');
}

$webservice = new webservice();
$servicecreated = false;

$service = $webservice->get_external_service_by_shortname($servicename);
if (!$service) {
    $logger->log("Service '{$servicename}' does not exist and will be created automatically.");
    $servicecreated = true;

    $serviceconfig = new stdClass();
    $serviceconfig->name = $servicename;
    $serviceconfig->shortname = $servicename;
    $serviceconfig->enabled = 1;
    $serviceconfig->restrictedusers = 0;
    $serviceconfig->requiredcapability = '';
    $serviceconfig->component = '';
    $serviceconfig->downloadfiles = 1;
    $serviceconfig->uploadfiles = 0;

    if ($dryrun) {
        $service = clone $serviceconfig;
        $service->id = 0;
        $service->timecreated = time();
        $service->timemodified = $service->timecreated;
        $logger->log('Dry-run: service creation simulated.');
    } else {
        $serviceid = $webservice->add_external_service($serviceconfig);
        $service = $webservice->get_external_service_by_id($serviceid, MUST_EXIST);
        $logger->log("Created service '{$service->name}' (ID: {$service->id}).");
    }
} else {
    $logger->log("Found service '{$service->name}' (ID: {$service->id}).");
}

$existingfunctions = [];
if (!empty($service->id)) {
    $existingfunctions = $DB->get_records_menu(
        'external_services_functions',
        ['externalserviceid' => $service->id],
        'functionname ASC',
        'functionname, id'
    );
}

$logger->log('Loaded current service function assignments: ' . count($existingfunctions) . ' entries.');

$allfunctions = $DB->get_records('external_functions', null, 'name ASC');
$logger->log('Discovered ' . count($allfunctions) . ' registered external functions.');

$readpattern = '/(^|_)(get|view|list|search|fetch|retrieve)(_|$)/';
$writepattern = '/(^|_)(add|assign|create|delete|drop|insert|modify|remove|set|update|edit|save|post|send|submit)(_|$)/';

$added = [];
$skipped = [];
$already = [];
$errors = [];
$removed = [];
$eligible = [];
$skipreasons = [];
$loadfailures = [];
$functioncomponents = [];

$transaction = null;
if (!$dryrun) {
    $transaction = $DB->start_delegated_transaction();
}

foreach ($allfunctions as $function) {
    $functioncomponents[$function->name] = $function->component;
    try {
        $info = external_api::external_function_info($function);
    } catch (Throwable $e) {
        $errors[] = [
            'name' => $function->name,
            'component' => $function->component,
            'reason' => 'Failed to load definition: ' . $e->getMessage(),
        ];
        $logger->log("Skipping {$function->name}: unable to load definition ({$e->getMessage()}).");
        $loadfailures[$function->name] = $e->getMessage();
        continue;
    }

    if (!empty($info->deprecated)) {
        $skipped[] = [
            'name' => $function->name,
            'component' => $function->component,
            'reason' => 'Deprecated function',
        ];
        $skipreasons[$function->name] = 'Deprecated function';
        if ($verbose) {
            $logger->log("Skipping {$function->name}: function marked as deprecated.");
        }
        continue;
    }

    $name = strtolower($function->name);
    $decision = [
        'include' => false,
        'reason' => '',
        'classification' => null,
    ];

    if (!empty($info->type)) {
        if ($info->type === 'read') {
            $decision['include'] = true;
            $decision['reason'] = 'Declared as read-only in services.php';
            $decision['classification'] = 'read';
        } else {
            $decision['include'] = false;
            $decision['reason'] = 'Declared as write operation';
        }
    }

    if (!$decision['include']) {
        if (preg_match($writepattern, $name)) {
            $decision['include'] = false;
            $decision['reason'] = 'Excluded by write operation naming pattern';
        } else if (preg_match($readpattern, $name)) {
            $decision['include'] = true;
            $decision['reason'] = 'Matched read-only naming pattern';
            $decision['classification'] = $decision['classification'] ?? 'pattern';
        }
    }

    if (!$decision['include']) {
        $skipped[] = [
            'name' => $function->name,
            'component' => $function->component,
            'reason' => $decision['reason'] ?: 'Did not match read filters',
        ];
        $skipreasons[$function->name] = $decision['reason'] ?: 'Did not match read filters';
        if ($verbose) {
            $logger->log("Skipping {$function->name}: {$skipped[array_key_last($skipped)]['reason']}");
        }
        continue;
    }

    $eligible[$function->name] = $decision['classification'] ?? ($info->type ?: 'unknown');

    if (isset($existingfunctions[$function->name])) {
        $already[] = [
            'name' => $function->name,
            'component' => $function->component,
            'reason' => 'Already linked to the service',
        ];
        if ($verbose) {
            $logger->log("Already present {$function->name}, skipping addition.");
        }
        continue;
    }

    if (!$dryrun) {
        $record = new stdClass();
        $record->externalserviceid = $service->id;
        $record->functionname = $function->name;
        $DB->insert_record('external_services_functions', $record);
    }

    $documentation = [
        'name' => $function->name,
        'component' => $function->component,
        'classname' => $function->classname,
        'methodname' => $function->methodname,
        'type' => $info->type ?? $decision['classification'] ?? 'unknown',
        'description' => $info->description,
        'selectionreason' => $decision['reason'],
        'capabilities' => normalise_capabilities($function->capabilities ?? ''),
        'loginrequired' => $info->loginrequired ?? true,
        'readonlysession' => $info->readonlysession ?? false,
        'allowedfromajax' => $info->allowed_from_ajax ?? false,
        'parameters' => describe_external_description($info->parameters_desc),
        'returns' => $info->returns_desc ? describe_external_description($info->returns_desc) : null,
    ];
    $added[] = $documentation;

    if ($dryrun) {
        $logger->log("Dry-run: would add {$function->name} ({$function->component}).");
    } else {
        $logger->log("Added {$function->name} ({$function->component}).");
    }
}

foreach ($existingfunctions as $functionname => $assignmentid) {
    if (isset($eligible[$functionname])) {
        continue;
    }
    if (isset($loadfailures[$functionname])) {
        if ($verbose) {
            $logger->log("Preserving {$functionname}: definition could not be analysed earlier.");
        }
        continue;
    }

    $reason = $skipreasons[$functionname] ?? 'Not classified as read-only';
    if (!isset($functioncomponents[$functionname])) {
        $reason = 'Function no longer registered in Moodle';
    }
    $removed[] = [
        'name' => $functionname,
        'component' => $functioncomponents[$functionname] ?? null,
        'reason' => $reason,
    ];

    if (!$dryrun) {
        $DB->delete_records('external_services_functions', ['id' => $assignmentid]);
        $logger->log("Removed {$functionname} from service ({$reason}).");
    } else {
        $logger->log("Dry-run: would remove {$functionname} from service ({$reason}).");
    }
}

if ($transaction) {
    $transaction->allow_commit();
}

$summary = [
    'service' => [
        'id' => (int)$service->id,
        'name' => $service->name,
        'shortname' => $service->shortname,
        'enabled' => (bool)$service->enabled,
        'restrictedusers' => (bool)$service->restrictedusers,
        'requiredcapability' => (string)($service->requiredcapability ?? ''),
        'component' => (string)($service->component ?? ''),
        'downloadfiles' => (bool)($service->downloadfiles ?? 0),
        'uploadfiles' => (bool)($service->uploadfiles ?? 0),
        'timecreated' => (int)($service->timecreated ?? 0),
        'timemodified' => (int)($service->timemodified ?? 0),
        'created' => $servicecreated,
    ],
    'execution' => [
        'timestamp' => time(),
        'dryrun' => $dryrun,
        'totalfunctions' => count($allfunctions),
        'added' => count($added),
        'alreadyassigned' => count($already),
        'removed' => count($removed),
        'skipped' => count($skipped),
        'errors' => count($errors),
    ],
    'added' => $added,
    'alreadyassigned' => $already,
    'skipped' => $skipped,
    'removed' => $removed,
    'errors' => $errors,
];

$logger->log('Added functions: ' . count($added));
$logger->log('Functions already assigned: ' . count($already));
$logger->log('Functions removed: ' . count($removed));
$logger->log('Functions skipped: ' . count($skipped));
$logger->log('Errors: ' . count($errors));

if (!$skipreport) {
    $reportpath = $exportpath;
    $directory = dirname($reportpath);
    if (!is_dir($directory) && !mkdir($directory, $CFG->directorypermissions ?? 0777, true) && !is_dir($directory)) {
        $logger->log("Unable to create report directory: {$directory}");
    } else {
        file_put_contents($reportpath, json_encode($summary, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        $logger->log("Documentation exported to {$reportpath}.");
    }
} else {
    $logger->log('Report generation disabled by configuration.');
}

$logger->log('AmericasBPS service synchronisation completed.');
$logger->close();

exit(0);

/**
 * Normalise a capability string to an array of capability names.
 *
 * @param string $capabilitystring
 * @return array
 */
function normalise_capabilities(string $capabilitystring): array {
    $capabilitystring = trim($capabilitystring);
    if ($capabilitystring === '') {
        return [];
    }
    $parts = array_map('trim', explode(',', $capabilitystring));
    $parts = array_filter($parts, static function($value) {
        return $value !== '';
    });
    return array_values(array_unique($parts));
}

/**
 * Convert an external_description tree into an associative array.
 *
 * @param external_description $description
 * @return array
 */
function describe_external_description(external_description $description): array {
    $base = [
        'type' => get_class_shortname($description),
        'description' => (string)$description->desc,
        'required' => describe_required_flag($description->required),
        'default' => normalise_default_value($description->default),
        'allownull' => (bool)$description->allownull,
    ];

    if ($description instanceof external_value) {
        $base['valuetype'] = describe_param_type($description->type);
    } else if ($description instanceof external_single_structure || $description instanceof external_function_parameters) {
        $keys = [];
        foreach ($description->keys as $key => $subdescription) {
            $keys[$key] = describe_external_description($subdescription);
        }
        $base['keys'] = $keys;
    } else if ($description instanceof external_multiple_structure) {
        $base['content'] = describe_external_description($description->content);
    }

    return $base;
}

/**
 * Return the short name of a class (without namespace).
 *
 * @param object $object
 * @return string
 */
function get_class_shortname(object $object): string {
    $parts = explode('\\\\', get_class($object));
    return end($parts);
}

/**
 * Translate parameter requirement constants into readable strings.
 *
 * @param int $flag
 * @return string
 */
function describe_required_flag(int $flag): string {
    switch ($flag) {
        case VALUE_REQUIRED:
            return 'required';
        case VALUE_OPTIONAL:
            return 'optional';
        case VALUE_DEFAULT:
            return 'default';
        default:
            return (string)$flag;
    }
}

/**
 * Resolve PARAM_* constant values into readable strings when possible.
 *
 * @param mixed $type
 * @return string
 */
function describe_param_type($type): string {
    static $map = null;

    if ($map === null) {
        $map = [];
        $constants = get_defined_constants(true);
        foreach ($constants['user'] ?? [] as $name => $value) {
            if (strpos($name, 'PARAM_') === 0) {
                $map[$value] = $name;
            }
        }
    }

    if (isset($map[$type])) {
        return $map[$type];
    }

    if (is_scalar($type)) {
        return (string)$type;
    }

    return gettype($type);
}

/**
 * Normalise default values (arrays/objects) so they can be exported safely.
 *
 * @param mixed $value
 * @return mixed
 */
function normalise_default_value($value) {
    if (is_array($value)) {
        return array_map('normalise_default_value', $value);
    }
    if (is_object($value)) {
        if ($value instanceof stdClass) {
            $result = [];
            foreach ((array)$value as $key => $item) {
                $result[$key] = normalise_default_value($item);
            }
            return $result;
        }
        return get_class($value);
    }

    return $value;
}
