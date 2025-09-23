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
 * The script ensures the target service exists, aligns its configuration with the
 * administrative UI, inspects the installed external functions, classifies the
 * read-only ones using metadata and naming conventions requested by AmericasBPS,
 * and adds the eligible functions while removing write operations.
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
require_once($CFG->dirroot . '/user/lib.php');

use core_external\external_api;
use core_external\util;

$defaultshortname = 'AmericasBPS';
$defaultworkdir = make_temp_directory('americasbps');
$defaultlog = $defaultworkdir . '/service_sync.log';

$longoptions = [
    'service' => $defaultshortname,
    'displayname' => $defaultshortname,
    'user' => null,
    'userid' => null,
    'tokenname' => '',
    'dry-run' => false,
    'verbose' => false,
    'logfile' => $defaultlog,
    'help' => false,
];
$shortmapping = [
    's' => 'service',
    'u' => 'user',
    'i' => 'userid',
    't' => 'tokenname',
    'n' => 'dry-run',
    'v' => 'verbose',
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
       --displayname=NAME Friendly name shown in the administration UI (default: {$defaultshortname}).
    --user=USERNAME       Username that will own the generated web service token.
    --userid=ID           Numeric ID of the token owner (alternative to --user).
    --tokenname=NAME      Optional label stored with the generated token.
 -n --dry-run             Analyse and report without modifying the database.
 -v --verbose             Display detailed information while processing.
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

The script also ensures a permanent token exists for the selected user and
prints connection instructions at the end of the execution. The user can be
specified either by username or by numeric ID.

Example (dry run):
    php admin/cli/sync_americasbps_service.php --dry-run --verbose

The script updates the service configuration using the same APIs exposed by the
web administration interface (/admin/webservice/service.php) so the resulting
record matches what would be stored when creating the service manually.
HELP;
    echo $help . "\n";
    exit(0);
}

$servicename = trim((string)$options['service']);
if ($servicename === '') {
    cli_error('The service shortname cannot be empty.');
}

$servicedisplayname = trim((string)($options['displayname'] ?? ''));
if ($servicedisplayname === '') {
    $servicedisplayname = $servicename;
}

$tokenname = trim((string)($options['tokenname'] ?? ''));
$user = null;
$useridoption = $options['userid'];
$usernameoption = $options['user'];

if ($useridoption !== null && $useridoption !== '') {
    if (!is_numeric($useridoption)) {
        cli_error('The --userid option must be a numeric value.');
    }
    $userid = (int)$useridoption;
    if ($userid <= 0) {
        cli_error('The --userid option must refer to a positive user ID.');
    }
    $user = $DB->get_record('user', ['id' => $userid], '*', IGNORE_MISSING);
    if (!$user) {
        cli_error("Unable to find a user with ID {$userid}.");
    }
} else if ($usernameoption !== null && $usernameoption !== '') {
    $username = trim((string)$usernameoption);
    if ($username === '') {
        cli_error('The --user option cannot be empty.');
    }
    $user = \core_user::get_user_by_username($username, '*', null, IGNORE_MISSING);
    if (!$user) {
        cli_error("Unable to find a user with username '{$username}'.");
    }
}

if (!$user) {
    cli_error('You must provide either --user or --userid to select the token owner.');
}

$user->id = (int)$user->id;

try {
    \core_user::require_active_user($user, true, true);
} catch (moodle_exception $exception) {
    cli_error('The selected user cannot be used: ' . $exception->getMessage());
}

$adminuser = get_admin();
if (!$adminuser) {
    cli_error('Unable to determine the site administrator account.');
}
\core\session\manager::init_empty_session();
\core\session\manager::set_user($adminuser);

$dryrun = !empty($options['dry-run']);
$verbose = !empty($options['verbose']);

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
$logger->log("Token owner: {$user->username} (ID: {$user->id}).");
if ($tokenname !== '') {
    $logger->log("Token name override provided: {$tokenname}.");
}
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
    $serviceconfig->name = $servicedisplayname;
    $serviceconfig->shortname = $servicename;
    $serviceconfig->enabled = 1;
    $serviceconfig->restrictedusers = 0;
    $serviceconfig->requiredcapability = '';
    $serviceconfig->component = null;
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

if (!empty($service->component)) {
    $logger->log("Service '{$service->shortname}' belongs to component '{$service->component}' and cannot be modified by this script.");
    $logger->close();
    exit(1);
}

if (!$servicecreated) {
    $desiredconfig = [
        'name' => $servicedisplayname,
        'enabled' => 1,
        'restrictedusers' => 0,
        'requiredcapability' => '',
        'downloadfiles' => 1,
        'uploadfiles' => 0,
        'component' => null,
    ];
    $needsupdate = false;
    foreach ($desiredconfig as $field => $value) {
        $current = $service->$field ?? null;
        $matches = ($value === null) ? ($current === null) : ((string)$current === (string)$value);
        if (!$matches) {
            $service->$field = $value;
            $needsupdate = true;
        }
    }
    if ($needsupdate) {
        if ($dryrun) {
            $logger->log('Dry-run: service configuration would be updated to match admin/webservice/service.php defaults.');
        } else {
            $webservice->update_external_service($service);
            $logger->log('Updated existing service configuration to match admin/webservice/service.php defaults.');
            $service = $webservice->get_external_service_by_id($service->id, MUST_EXIST);
        }
    }
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

$addedcount = 0;
$skippedcount = 0;
$alreadycount = 0;
$errorcount = 0;
$removedcount = 0;
$eligible = [];
$skipreasons = [];
$loadfailures = [];
$functioncomponents = [];
$tokenrecord = null;
$tokencreated = false;
$tokenreused = false;

$transaction = null;
if (!$dryrun) {
    $transaction = $DB->start_delegated_transaction();
}

foreach ($allfunctions as $function) {
    $functioncomponents[$function->name] = $function->component;
    try {
        $info = external_api::external_function_info($function);
    } catch (Throwable $e) {
        $errorcount++;
        $logger->log("Skipping {$function->name}: unable to load definition ({$e->getMessage()}).");
        $loadfailures[$function->name] = $e->getMessage();
        continue;
    }

    if (!empty($info->deprecated)) {
        $skipreasons[$function->name] = 'Deprecated function';
        $skippedcount++;
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
        $reason = $decision['reason'] ?: 'Did not match read filters';
        $skipreasons[$function->name] = $reason;
        $skippedcount++;
        if ($verbose) {
            $logger->log("Skipping {$function->name}: {$reason}.");
        }
        continue;
    }

    $eligible[$function->name] = $decision['classification'] ?? ($info->type ?: 'unknown');

    if (isset($existingfunctions[$function->name])) {
        $alreadycount++;
        if ($verbose) {
            $logger->log("Already present {$function->name}, skipping addition.");
        }
        continue;
    }

    if ($dryrun) {
        $logger->log("Dry-run: would add {$function->name} ({$function->component}).");
    } else {
        $webservice->add_external_function_to_service($function->name, $service->id);
        $logger->log("Added {$function->name} ({$function->component}).");
    }
    $addedcount++;
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
    $removedcount++;

    if ($dryrun) {
        $logger->log("Dry-run: would remove {$functionname} from service ({$reason}).");
    } else {
        $webservice->remove_external_function_from_service($functionname, $service->id);
        $logger->log("Removed {$functionname} from service ({$reason}).");
    }
}

if (!empty($service->id)) {
    $tokenconditions = [
        'externalserviceid' => $service->id,
        'userid' => $user->id,
        'tokentype' => EXTERNAL_TOKEN_PERMANENT,
    ];
    $existingtokens = $DB->get_records('external_tokens', $tokenconditions, 'timecreated DESC, id DESC');
    if ($existingtokens) {
        $tokenrecord = reset($existingtokens);
        $tokenreused = true;
        $logger->log(
            'Existing permanent token found for the selected user (token ID: ' . $tokenrecord->id . ').'
        );
        if (count($existingtokens) > 1 && $verbose) {
            $logger->log('Multiple tokens detected; the most recent one will be reported.');
        }
    } else if ($dryrun) {
        $logger->log('Dry-run: would generate a permanent token for the selected user.');
    } else {
        try {
            $generatedtoken = util::generate_token(
                EXTERNAL_TOKEN_PERMANENT,
                $service,
                $user->id,
                \context_system::instance(),
                0,
                '',
                $tokenname
            );
        } catch (Throwable $exception) {
            if ($transaction) {
                $transaction->rollback($exception);
            }
            $logger->log('Failed to create the permanent token: ' . $exception->getMessage());
            $logger->close();
            cli_error('Failed to create the web service token: ' . $exception->getMessage());
        }

        $tokenrecord = $DB->get_record('external_tokens', ['token' => $generatedtoken], '*', MUST_EXIST);
        $tokencreated = true;
        $logger->log('Created permanent token (ID: ' . $tokenrecord->id . ').');
    }
} else if ($dryrun) {
    $logger->log('Dry-run: token generation skipped because the service would be created.');
}

if ($transaction) {
    $transaction->allow_commit();
}

$logger->log('Added functions: ' . $addedcount);
$logger->log('Functions already assigned: ' . $alreadycount);
$logger->log('Functions removed: ' . $removedcount);
$logger->log('Functions skipped: ' . $skippedcount);
$logger->log('Errors while loading definitions: ' . $errorcount);

$logger->log('AmericasBPS service synchronisation completed.');
$logger->close();

$serviceidtext = !empty($service->id) ? (string)$service->id : 'pending creation';
$tokenstatus = 'not created';
if ($tokencreated) {
    $tokenstatus = 'created during this run';
} else if ($tokenreused) {
    $tokenstatus = 'existing token reused';
} else if ($dryrun) {
    $tokenstatus = 'dry run (no changes applied)';
}

$summarylines = [
    'Servicio: ' . $service->name . " ({$service->shortname})",
    'ID del servicio: ' . $serviceidtext,
    'Usuario asociado: ' . $user->username . ' (ID ' . $user->id . ')',
    'Estado del token: ' . $tokenstatus,
];

if ($tokenrecord) {
    $summarylines[] = 'ID del token: ' . $tokenrecord->id;
    $tokennameoutput = trim((string)($tokenrecord->name ?? ''));
    if ($tokennameoutput === '') {
        $tokennameoutput = '(generado automáticamente)';
    }
    $summarylines[] = 'Nombre del token: ' . $tokennameoutput;
    $summarylines[] = 'Token: ' . $tokenrecord->token;
    $validuntil = (int)($tokenrecord->validuntil ?? 0);
    $summarylines[] = 'Vigencia: ' . ($validuntil ? userdate($validuntil) : 'sin caducidad');
    if (!empty($tokenrecord->iprestriction)) {
        $summarylines[] = 'Restricción de IP: ' . $tokenrecord->iprestriction;
    }
}

cli_writeln('');
cli_heading('Resumen del servicio AmericasBPS');
foreach ($summarylines as $line) {
    cli_writeln('  - ' . $line);
}

$endpoint = rtrim($CFG->wwwroot, '/') . '/webservice/rest/server.php';
$exampletoken = $tokenrecord->token ?? '<TOKEN>';
$examplefunction = 'core_webservice_get_site_info';
$examplecurl = 'curl -X POST ' . $endpoint . ' --data "wstoken=' . $exampletoken
    . '&wsfunction=' . $examplefunction . '&moodlewsrestformat=json"';

cli_writeln('');
cli_heading('Instrucciones de uso');
cli_writeln('  1. Envía tus peticiones REST a: ' . $endpoint);
cli_writeln('  2. Incluye los parámetros wstoken, wsfunction y moodlewsrestformat (por ejemplo, json).');
cli_writeln('  3. Ejemplo rápido: ' . $examplecurl);

exit(0);
