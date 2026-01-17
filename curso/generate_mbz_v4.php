<?php
/**
 * Generador MBZ v4.0 - Curso "Huellas Invisibles"
 *
 * VERSION COMPLETA con actividades interactivas:
 * - mod_resource: PDFs, documentos
 * - mod_glossary: Glosarios con terminos extraidos
 * - mod_quiz: Tests con preguntas extraidas
 * - mod_feedback: Encuesta de cierre
 * - mod_customcert: Certificado del curso
 *
 * Completion automatico (completion=2, completionview=1)
 * Branding: #0170B9 (azul NADI), #3a3a3a (gris), Rubik font
 */

// ============================================================================
// CONFIGURACION GLOBAL
// ============================================================================

define('CONTENT_DIR', __DIR__ . '/../mbz_generator/curso_content/curso/Huellas invisibles');
define('OUTPUT_DIR', __DIR__ . '/backup_output_v4');
define('MBZ_FILENAME', 'backup-huellas-invisibles-v4-' . date('Ymd-His') . '.mbz');

define('COURSE_ID', 2);
define('COURSE_FULLNAME', 'Huellas Invisibles: Neurociencia del Desarrollo Infantil');
define('COURSE_SHORTNAME', 'huellas-invisibles');
define('COURSE_SUMMARY', 'Formacion especializada en neurodesarrollo infantil, emocion y aprendizaje, con foco en la primera infancia y el medio acuatico. Docente: Lic. Emilio Masabeu - Campus NADI');
define('MOODLE_VERSION', '2024042200');
define('ORIGINAL_WWWROOT', 'https://campus.rednadi.com');

// Branding NADI
define('BRAND_COLOR_PRIMARY', '#0170B9');
define('BRAND_COLOR_SECONDARY', '#3a3a3a');
define('BRAND_FONT', 'Rubik, sans-serif');

// Contadores globales
$nextModuleId = 1;
$nextContextId = 100;
$nextFileId = 1;
$nextInstanceId = 1;
$nextSectionId = 1;
$nextQuestionId = 1;
$nextAnswerId = 1;
$nextGlossaryEntryId = 1;

// Colecciones globales
$allFiles = [];
$allActivities = [];
$allSections = [];
$allQuestions = [];

// Tiempo de generacion
$generationTime = time();

// ============================================================================
// FUNCIONES AUXILIARES BASICAS
// ============================================================================

function getMimeType($filename) {
    $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    $types = [
        'pdf' => 'application/pdf',
        'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'doc' => 'application/msword',
        'png' => 'image/png',
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'gif' => 'image/gif',
        'mp4' => 'video/mp4',
        'txt' => 'text/plain'
    ];
    return isset($types[$ext]) ? $types[$ext] : 'application/octet-stream';
}

function formatBytes($bytes, $precision = 2) {
    $units = ['B', 'KB', 'MB', 'GB'];
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    return round($bytes / pow(1024, $pow), $precision) . ' ' . $units[$pow];
}

function escapeXml($str) {
    return htmlspecialchars($str, ENT_XML1 | ENT_QUOTES, 'UTF-8');
}

function generateUniqueId() {
    return bin2hex(random_bytes(16));
}

function logMessage($message, $icon = '  ') {
    echo "[" . date('H:i:s') . "] {$icon} {$message}\n";
}

function createDirectoryStructure() {
    $dirs = [
        OUTPUT_DIR,
        OUTPUT_DIR . '/course',
        OUTPUT_DIR . '/sections',
        OUTPUT_DIR . '/activities',
        OUTPUT_DIR . '/files'
    ];

    foreach ($dirs as $dir) {
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
    }
}

// ============================================================================
// CARGAR DATOS Y MODULOS
// ============================================================================

require_once __DIR__ . '/data/course_structure.php';
require_once __DIR__ . '/data/glossary_data_1_5.php';
require_once __DIR__ . '/data/glossary_data_6_10.php';
require_once __DIR__ . '/data/quiz_data_1_5.php';
require_once __DIR__ . '/data/quiz_data_6_10.php';

require_once __DIR__ . '/includes/mod_resource.php';
require_once __DIR__ . '/includes/mod_label.php';
require_once __DIR__ . '/includes/mod_glossary.php';
require_once __DIR__ . '/includes/mod_quiz.php';
require_once __DIR__ . '/includes/mod_feedback.php';
require_once __DIR__ . '/includes/mod_customcert.php';

// ============================================================================
// FUNCIONES DE COPIA DE ARCHIVOS
// ============================================================================

function copyFileToBackup($sourcePath, $contextId, $component, $filearea, $itemid = 0) {
    global $nextFileId, $allFiles, $generationTime;

    if (!file_exists($sourcePath)) {
        logMessage("ADVERTENCIA: Archivo no encontrado: $sourcePath", '⚠️');
        return null;
    }

    $filename = basename($sourcePath);
    $filesize = filesize($sourcePath);
    $contenthash = sha1_file($sourcePath);
    $mimetype = getMimeType($filename);
    $fileId = $nextFileId++;

    // Crear estructura de directorios para el archivo
    $hashDir = substr($contenthash, 0, 2);
    $filesDir = OUTPUT_DIR . '/files/' . $hashDir;
    if (!is_dir($filesDir)) {
        mkdir($filesDir, 0755, true);
    }

    // Copiar archivo
    copy($sourcePath, $filesDir . '/' . $contenthash);

    // Registrar archivo
    $allFiles[] = [
        'id' => $fileId,
        'contenthash' => $contenthash,
        'contextid' => $contextId,
        'component' => $component,
        'filearea' => $filearea,
        'itemid' => $itemid,
        'filepath' => '/',
        'filename' => $filename,
        'userid' => 2,
        'filesize' => $filesize,
        'mimetype' => $mimetype,
        'status' => 0,
        'timecreated' => $generationTime,
        'timemodified' => $generationTime,
        'source' => $filename,
        'author' => 'Emilio Masabeu',
        'license' => 'allrightsreserved',
        'sortorder' => 0
    ];

    return $fileId;
}

// ============================================================================
// GENERADORES DE XMLs GLOBALES
// ============================================================================

function generateMoodleBackupXML() {
    global $allSections, $allActivities, $generationTime;

    $sectionsXml = '';
    foreach ($allSections as $section) {
        $sectionsXml .= "      <section>\n";
        $sectionsXml .= "        <sectionid>{$section['id']}</sectionid>\n";
        $sectionsXml .= "        <title>" . escapeXml($section['name']) . "</title>\n";
        $sectionsXml .= "        <directory>sections/section_{$section['id']}</directory>\n";
        $sectionsXml .= "      </section>\n";
    }

    $activitiesXml = '';
    foreach ($allActivities as $act) {
        $activitiesXml .= "      <activity>\n";
        $activitiesXml .= "        <moduleid>{$act['moduleid']}</moduleid>\n";
        $activitiesXml .= "        <sectionid>{$act['sectionid']}</sectionid>\n";
        $activitiesXml .= "        <modulename>{$act['modulename']}</modulename>\n";
        $activitiesXml .= "        <title>" . escapeXml($act['name']) . "</title>\n";
        $activitiesXml .= "        <directory>activities/{$act['modulename']}_{$act['moduleid']}</directory>\n";
        $activitiesXml .= "      </activity>\n";
    }

    // Generar settings de secciones
    $sectionSettings = '';
    foreach ($allSections as $section) {
        $sectionSettings .= <<<SETTING
      <setting>
        <level>section</level>
        <section>section_{$section['id']}</section>
        <name>section_{$section['id']}_included</name>
        <value>1</value>
      </setting>
      <setting>
        <level>section</level>
        <section>section_{$section['id']}</section>
        <name>section_{$section['id']}_userinfo</name>
        <value>0</value>
      </setting>
SETTING;
    }

    // Generar settings de actividades
    $activitySettings = '';
    foreach ($allActivities as $act) {
        $actDir = "{$act['modulename']}_{$act['moduleid']}";
        $activitySettings .= <<<SETTING
      <setting>
        <level>activity</level>
        <activity>{$actDir}</activity>
        <name>{$actDir}_included</name>
        <value>1</value>
      </setting>
      <setting>
        <level>activity</level>
        <activity>{$actDir}</activity>
        <name>{$actDir}_userinfo</name>
        <value>0</value>
      </setting>
SETTING;
    }

    $xml = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<moodle_backup>
  <information>
    <name>backup-huellas-invisibles-v4.mbz</name>
    <moodle_version>2024042200</moodle_version>
    <moodle_release>4.4</moodle_release>
    <backup_version>2024042200</backup_version>
    <backup_release>4.4</backup_release>
    <backup_date>{$generationTime}</backup_date>
    <mnet_remoteusers>0</mnet_remoteusers>
    <include_files>1</include_files>
    <include_file_references_to_external_content>0</include_file_references_to_external_content>
    <original_wwwroot>https://campus.rednadi.com</original_wwwroot>
    <original_site_identifier_hash>mbzgen4</original_site_identifier_hash>
    <original_course_id>2</original_course_id>
    <original_course_format>topics</original_course_format>
    <original_course_fullname>Huellas Invisibles: Neurociencia del Desarrollo Infantil</original_course_fullname>
    <original_course_shortname>huellas-invisibles</original_course_shortname>
    <original_course_startdate>{$generationTime}</original_course_startdate>
    <original_course_enddate>0</original_course_enddate>
    <original_course_contextid>50</original_course_contextid>
    <original_system_contextid>1</original_system_contextid>
    <details>
      <detail backup_id="mbzgen4">
        <type>course</type>
        <format>moodle2</format>
        <interactive>0</interactive>
        <mode>10</mode>
        <execution>1</execution>
        <executiontime>0</executiontime>
      </detail>
    </details>
    <contents>
      <activities>
{$activitiesXml}      </activities>
      <sections>
{$sectionsXml}      </sections>
      <course>
        <courseid>2</courseid>
        <title>Huellas Invisibles</title>
        <directory>course</directory>
      </course>
    </contents>
    <settings>
      <setting>
        <level>root</level>
        <name>filename</name>
        <value>backup-huellas-invisibles-v4.mbz</value>
      </setting>
      <setting>
        <level>root</level>
        <name>users</name>
        <value>0</value>
      </setting>
      <setting>
        <level>root</level>
        <name>anonymize</name>
        <value>0</value>
      </setting>
      <setting>
        <level>root</level>
        <name>role_assignments</name>
        <value>0</value>
      </setting>
      <setting>
        <level>root</level>
        <name>activities</name>
        <value>1</value>
      </setting>
      <setting>
        <level>root</level>
        <name>blocks</name>
        <value>0</value>
      </setting>
      <setting>
        <level>root</level>
        <name>files</name>
        <value>1</value>
      </setting>
      <setting>
        <level>root</level>
        <name>filters</name>
        <value>0</value>
      </setting>
      <setting>
        <level>root</level>
        <name>comments</name>
        <value>0</value>
      </setting>
      <setting>
        <level>root</level>
        <name>badges</name>
        <value>0</value>
      </setting>
      <setting>
        <level>root</level>
        <name>calendarevents</name>
        <value>0</value>
      </setting>
      <setting>
        <level>root</level>
        <name>userscompletion</name>
        <value>0</value>
      </setting>
      <setting>
        <level>root</level>
        <name>logs</name>
        <value>0</value>
      </setting>
      <setting>
        <level>root</level>
        <name>grade_histories</name>
        <value>0</value>
      </setting>
      <setting>
        <level>root</level>
        <name>questionbank</name>
        <value>1</value>
      </setting>
      <setting>
        <level>root</level>
        <name>groups</name>
        <value>0</value>
      </setting>
      <setting>
        <level>root</level>
        <name>competencies</name>
        <value>0</value>
      </setting>
      <setting>
        <level>root</level>
        <name>customfield</name>
        <value>0</value>
      </setting>
      <setting>
        <level>root</level>
        <name>contentbankcontent</name>
        <value>0</value>
      </setting>
      <setting>
        <level>root</level>
        <name>legacyfiles</name>
        <value>0</value>
      </setting>
{$sectionSettings}
{$activitySettings}
    </settings>
  </information>
</moodle_backup>
XML;
    file_put_contents(OUTPUT_DIR . '/moodle_backup.xml', $xml);
}

function generateCourseXML() {
    global $generationTime;

    $summary = escapeXml(COURSE_SUMMARY);
    $fullname = escapeXml(COURSE_FULLNAME);
    $shortname = escapeXml(COURSE_SHORTNAME);

    $xml = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<course id="2" contextid="50">
  <shortname>{$shortname}</shortname>
  <fullname>{$fullname}</fullname>
  <idnumber></idnumber>
  <summary>{$summary}</summary>
  <summaryformat>1</summaryformat>
  <format>topics</format>
  <showgrades>1</showgrades>
  <newsitems>5</newsitems>
  <startdate>{$generationTime}</startdate>
  <enddate>0</enddate>
  <marker>0</marker>
  <maxbytes>0</maxbytes>
  <legacyfiles>0</legacyfiles>
  <showreports>0</showreports>
  <visible>1</visible>
  <groupmode>0</groupmode>
  <groupmodeforce>0</groupmodeforce>
  <defaultgroupingid>0</defaultgroupingid>
  <lang></lang>
  <theme></theme>
  <timecreated>{$generationTime}</timecreated>
  <timemodified>{$generationTime}</timemodified>
  <requested>0</requested>
  <showactivitydates>1</showactivitydates>
  <showcompletionconditions>1</showcompletionconditions>
  <pdfexportfont></pdfexportfont>
  <enablecompletion>1</enablecompletion>
  <completionnotify>0</completionnotify>
  <cacherev>{$generationTime}</cacherev>
  <originalcourseid>\$@NULL@\$</originalcourseid>
  <category id="1">
    <name>Cursos NADI</name>
    <description></description>
  </category>
  <tags></tags>
  <customfields></customfields>
  <courseformatoptions>
    <coursenumsections>11</coursenumsections>
  </courseformatoptions>
</course>
XML;
    file_put_contents(OUTPUT_DIR . '/course/course.xml', $xml);

    // Archivos adicionales del curso
    file_put_contents(OUTPUT_DIR . '/course/enrolments.xml',
        '<?xml version="1.0" encoding="UTF-8"?>' . "\n" .
        '<enrolments><enrols></enrols></enrolments>');

    file_put_contents(OUTPUT_DIR . '/course/roles.xml',
        '<?xml version="1.0" encoding="UTF-8"?>' . "\n" .
        '<roles><role_overrides></role_overrides><role_assignments></role_assignments></roles>');

    file_put_contents(OUTPUT_DIR . '/course/completiondefaults.xml',
        '<?xml version="1.0" encoding="UTF-8"?><course_completion_defaults></course_completion_defaults>');

    file_put_contents(OUTPUT_DIR . '/course/inforef.xml',
        '<?xml version="1.0" encoding="UTF-8"?><inforef></inforef>');
}

function generateSectionXML($section) {
    global $generationTime;

    $sectionDir = OUTPUT_DIR . '/sections/section_' . $section['id'];
    if (!is_dir($sectionDir)) {
        mkdir($sectionDir, 0755, true);
    }

    $name = escapeXml($section['name']);
    $summary = escapeXml($section['summary']);
    $sequence = implode(',', $section['sequence']);

    $xml = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<section id="{$section['id']}">
  <number>{$section['number']}</number>
  <name>{$name}</name>
  <summary>{$summary}</summary>
  <summaryformat>1</summaryformat>
  <sequence>{$sequence}</sequence>
  <visible>1</visible>
  <availabilityjson>\$@NULL@\$</availabilityjson>
  <timemodified>{$generationTime}</timemodified>
</section>
XML;
    file_put_contents($sectionDir . '/section.xml', $xml);

    file_put_contents($sectionDir . '/inforef.xml',
        '<?xml version="1.0" encoding="UTF-8"?><inforef></inforef>');
}

function generateFilesXML() {
    global $allFiles;

    $filesXml = '';
    foreach ($allFiles as $file) {
        $filesXml .= <<<FILE
  <file id="{$file['id']}">
    <contenthash>{$file['contenthash']}</contenthash>
    <contextid>{$file['contextid']}</contextid>
    <component>{$file['component']}</component>
    <filearea>{$file['filearea']}</filearea>
    <itemid>{$file['itemid']}</itemid>
    <filepath>{$file['filepath']}</filepath>
    <filename>{$file['filename']}</filename>
    <userid>{$file['userid']}</userid>
    <filesize>{$file['filesize']}</filesize>
    <mimetype>{$file['mimetype']}</mimetype>
    <status>{$file['status']}</status>
    <timecreated>{$file['timecreated']}</timecreated>
    <timemodified>{$file['timemodified']}</timemodified>
    <source>{$file['source']}</source>
    <author>{$file['author']}</author>
    <license>{$file['license']}</license>
    <sortorder>{$file['sortorder']}</sortorder>
    <repositorytype>\$@NULL@\$</repositorytype>
    <repositoryid>\$@NULL@\$</repositoryid>
    <reference>\$@NULL@\$</reference>
  </file>

FILE;
    }

    $xml = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<files>
{$filesXml}</files>
XML;
    file_put_contents(OUTPUT_DIR . '/files.xml', $xml);
}

function generateQuestionsXMLGlobal() {
    global $allQuestions, $generationTime;

    if (empty($allQuestions)) {
        file_put_contents(OUTPUT_DIR . '/questions.xml',
            '<?xml version="1.0" encoding="UTF-8"?><question_categories></question_categories>');
        return;
    }

    generateQuestionsXML(OUTPUT_DIR, $allQuestions, $generationTime);
}

function generateScalesXML() {
    $xml = '<?xml version="1.0" encoding="UTF-8"?><scales_definition></scales_definition>';
    file_put_contents(OUTPUT_DIR . '/scales.xml', $xml);
}

function generateOutcomesXML() {
    $xml = '<?xml version="1.0" encoding="UTF-8"?><outcomes_definition></outcomes_definition>';
    file_put_contents(OUTPUT_DIR . '/outcomes.xml', $xml);
}

function generateGradebookXML() {
    $xml = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<gradebook>
  <attributes>
  </attributes>
  <grade_categories>
  </grade_categories>
  <grade_items>
  </grade_items>
  <grade_letters>
  </grade_letters>
  <grade_settings>
  </grade_settings>
</gradebook>
XML;
    file_put_contents(OUTPUT_DIR . '/gradebook.xml', $xml);
}

function generateGroupsXML() {
    $xml = '<?xml version="1.0" encoding="UTF-8"?><groups><groupings></groupings></groups>';
    file_put_contents(OUTPUT_DIR . '/groups.xml', $xml);
}

function generateRolesXML() {
    $xml = '<?xml version="1.0" encoding="UTF-8"?><roles_definition></roles_definition>';
    file_put_contents(OUTPUT_DIR . '/roles.xml', $xml);
}

// ============================================================================
// FUNCION PRINCIPAL DE PROCESAMIENTO
// ============================================================================

function processAllSections() {
    global $COURSE_STRUCTURE, $GLOSSARY_DATA, $QUIZ_DATA;
    global $nextModuleId, $nextContextId, $nextInstanceId, $nextSectionId;
    global $nextQuestionId, $nextAnswerId, $nextGlossaryEntryId;
    global $allActivities, $allSections, $allQuestions, $generationTime;

    logMessage("Procesando secciones del curso...", '📚');

    foreach ($COURSE_STRUCTURE as $sectionNum => $sectionData) {
        $sectionId = $nextSectionId++;
        $sectionName = $sectionData['name'];
        $sectionSummary = $sectionData['summary'];
        $sectionDir = $sectionData['dir'];
        $activities = $sectionData['activities'];
        $sequence = [];

        logMessage("Seccion {$sectionNum}: {$sectionName}", '📁');

        foreach ($activities as $actData) {
            $moduleId = $nextModuleId++;
            $instanceId = $nextInstanceId++;
            $contextId = $nextContextId++;
            $moduleName = '';

            $activity = [
                'moduleid' => $moduleId,
                'instanceid' => $instanceId,
                'contextid' => $contextId,
                'sectionid' => $sectionId,
                'sectionnumber' => $sectionNum,
                'name' => $actData['name']
            ];

            // Determinar tipo y procesar
            $type = $actData['type'];

            switch ($type) {
                case 'resource':
                    $moduleName = 'resource';
                    $activity['modulename'] = 'resource';
                    $activityDir = OUTPUT_DIR . "/activities/resource_{$moduleId}";
                    mkdir($activityDir, 0755, true);

                    // Construir ruta del archivo
                    $filePath = $sectionDir
                        ? CONTENT_DIR . '/' . $sectionDir . '/' . $actData['file']
                        : CONTENT_DIR . '/' . $actData['file'];

                    generateResourceActivity($activity, $activityDir, $generationTime);

                    // Copiar archivo
                    $fileId = copyFileToBackup($filePath, $contextId, 'mod_resource', 'content');
                    if ($fileId) {
                        updateInforefWithFile($activityDir, $fileId);
                    }

                    logMessage("  + Resource: {$actData['name']}", '📄');
                    break;

                case 'video_placeholder':
                    $moduleName = 'resource';
                    $activity['modulename'] = 'resource';
                    $activity['video'] = str_replace('.txt', '.mp4', $actData['file']);
                    $activityDir = OUTPUT_DIR . "/activities/resource_{$moduleId}";
                    mkdir($activityDir, 0755, true);
                    generateVideoPlaceholderActivity($activity, $activityDir, $generationTime);
                    logMessage("  + Video placeholder: {$actData['name']}", '🎬');
                    break;

                case 'intro_label':
                    $moduleName = 'label';
                    $activity['modulename'] = 'label';
                    $activityDir = OUTPUT_DIR . "/activities/label_{$moduleId}";
                    mkdir($activityDir, 0755, true);

                    generateIntroLabelActivity($activity, $activityDir, $generationTime);
                    logMessage("  + Presentacion: {$actData['name']}", '📋');
                    break;

                case 'label':
                    $moduleName = 'label';
                    $activity['modulename'] = 'label';
                    $activity['filename'] = $actData['file'];
                    $activityDir = OUTPUT_DIR . "/activities/label_{$moduleId}";
                    mkdir($activityDir, 0755, true);

                    $filePath = CONTENT_DIR . '/' . $actData['file'];
                    generateLabelActivity($activity, $activityDir, $generationTime);

                    // Copiar imagen
                    $fileId = copyFileToBackup($filePath, $contextId, 'mod_label', 'intro');
                    if ($fileId) {
                        updateInforefWithFile($activityDir, $fileId);
                    }

                    logMessage("  + Label: {$actData['name']}", '🖼️');
                    break;

                case 'glossary':
                    $moduleName = 'glossary';
                    $activity['modulename'] = 'glossary';
                    $activity['chapter'] = $actData['chapter'];
                    $activityDir = OUTPUT_DIR . "/activities/glossary_{$moduleId}";
                    mkdir($activityDir, 0755, true);

                    $glossaryData = isset($GLOSSARY_DATA) ? $GLOSSARY_DATA : [];
                    $termsCount = generateGlossaryActivity($activity, $activityDir, $generationTime, $glossaryData, $nextGlossaryEntryId);
                    logMessage("  + Glossary: {$actData['name']} ({$termsCount} terminos)", '📖');
                    break;

                case 'quiz':
                    $moduleName = 'quiz';
                    $activity['modulename'] = 'quiz';
                    $activity['chapter'] = $actData['chapter'];
                    $activityDir = OUTPUT_DIR . "/activities/quiz_{$moduleId}";
                    mkdir($activityDir, 0755, true);

                    $quizData = isset($QUIZ_DATA) ? $QUIZ_DATA : [];
                    $questionsCount = generateQuizActivity($activity, $activityDir, $generationTime, $quizData, $nextQuestionId, $nextAnswerId, $allQuestions);
                    logMessage("  + Quiz: {$actData['name']} ({$questionsCount} preguntas)", '❓');
                    break;

                case 'feedback':
                    $moduleName = 'feedback';
                    $activity['modulename'] = 'feedback';
                    $activityDir = OUTPUT_DIR . "/activities/feedback_{$moduleId}";
                    mkdir($activityDir, 0755, true);
                    generateFeedbackActivity($activity, $activityDir, $generationTime);
                    logMessage("  + Feedback: {$actData['name']}", '📝');
                    break;

                case 'customcert':
                    $moduleName = 'customcert';
                    $activity['modulename'] = 'customcert';
                    $activityDir = OUTPUT_DIR . "/activities/customcert_{$moduleId}";
                    mkdir($activityDir, 0755, true);
                    generateCustomcertActivity($activity, $activityDir, $generationTime);
                    logMessage("  + Certificado: {$actData['name']}", '🎓');
                    break;
            }

            $allActivities[] = $activity;
            $sequence[] = $moduleId;
        }

        // Registrar seccion
        $allSections[] = [
            'id' => $sectionId,
            'number' => $sectionNum,
            'name' => $sectionName,
            'summary' => $sectionSummary,
            'sequence' => $sequence
        ];
    }

    logMessage("Total: " . count($allActivities) . " actividades en " . count($allSections) . " secciones", '✅');
}

// ============================================================================
// CREAR ARCHIVO MBZ
// ============================================================================

function createMBZ() {
    logMessage("Creando archivo MBZ...", '📦');

    $mbzPath = OUTPUT_DIR . '/' . MBZ_FILENAME;

    // Verificar si ZipArchive esta disponible
    if (!class_exists('ZipArchive')) {
        logMessage("ZipArchive no disponible, usando metodo alternativo", '⚠️');
        $currentDir = getcwd();
        chdir(OUTPUT_DIR);
        exec('zip -r "' . MBZ_FILENAME . '" . -x "*.mbz"', $output, $returnCode);
        chdir($currentDir);

        if ($returnCode !== 0) {
            logMessage("Error al crear ZIP", '❌');
            return false;
        }
    } else {
        $zip = new ZipArchive();
        if ($zip->open($mbzPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            logMessage("No se pudo crear el archivo MBZ", '❌');
            return false;
        }

        // Funcion recursiva para agregar archivos
        $addFilesToZip = function($dir, $basePath = '') use (&$addFilesToZip, $zip) {
            $files = scandir($dir);
            foreach ($files as $file) {
                if ($file === '.' || $file === '..') continue;
                if (pathinfo($file, PATHINFO_EXTENSION) === 'mbz') continue;

                $fullPath = $dir . '/' . $file;
                $zipPath = $basePath ? $basePath . '/' . $file : $file;

                if (is_dir($fullPath)) {
                    $zip->addEmptyDir($zipPath);
                    $addFilesToZip($fullPath, $zipPath);
                } else {
                    $zip->addFile($fullPath, $zipPath);
                }
            }
        };

        $addFilesToZip(OUTPUT_DIR);
        $zip->close();
    }

    $fileSize = file_exists($mbzPath) ? formatBytes(filesize($mbzPath)) : '0 B';
    logMessage("MBZ creado: " . MBZ_FILENAME . " ({$fileSize})", '✅');

    return $mbzPath;
}

// ============================================================================
// EJECUCION PRINCIPAL
// ============================================================================

echo "=======================================================\n";
echo " MBZ Generator v4.0 - Huellas Invisibles\n";
echo " " . date('Y-m-d H:i:s') . "\n";
echo "=======================================================\n\n";

// Paso 1: Crear estructura de directorios
logMessage("Creando estructura de directorios...", '📁');
createDirectoryStructure();

// Paso 2: Procesar todas las secciones y actividades
processAllSections();

// Paso 3: Generar XMLs de secciones
logMessage("Generando XMLs de secciones...", '📄');
foreach ($allSections as $section) {
    generateSectionXML($section);
}

// Paso 4: Generar XMLs globales
logMessage("Generando XMLs globales...", '📄');
generateCourseXML();
generateFilesXML();
generateQuestionsXMLGlobal();
generateScalesXML();
generateOutcomesXML();
generateGradebookXML();
generateGroupsXML();
generateRolesXML();
generateMoodleBackupXML();

// Paso 5: Crear archivo MBZ
$mbzPath = createMBZ();

echo "\n=======================================================\n";
echo " PROCESO COMPLETADO\n";
echo "=======================================================\n";
echo " Archivos: " . count($allFiles) . "\n";
echo " Actividades: " . count($allActivities) . "\n";
echo " Secciones: " . count($allSections) . "\n";
echo " Preguntas: " . count($allQuestions) . "\n";
echo " MBZ: " . ($mbzPath ? $mbzPath : 'No generado') . "\n";
echo "=======================================================\n";
