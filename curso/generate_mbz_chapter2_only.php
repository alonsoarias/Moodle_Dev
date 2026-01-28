<?php
/**
 * Generador MBZ - Solo Capitulo 2
 * Genera un MBZ independiente con solo el contenido del Capitulo 2
 * para diagnosticar problemas de restauracion
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Configuracion
define('CONTENT_DIR', '/home/user/Moodle_Dev/mbz_generator/curso_content/curso/Huellas invisibles');
define('OUTPUT_DIR', __DIR__ . '/backup_output_chapter2');

echo "=======================================================\n";
echo " MBZ Generator - SOLO CAPITULO 2\n";
echo " " . date('Y-m-d H:i:s') . "\n";
echo "=======================================================\n\n";

// Cargar datos
require_once __DIR__ . '/data/course_structure.php';
require_once __DIR__ . '/data/glossary_data_1_5.php';
require_once __DIR__ . '/data/glossary_data_6_10.php';
require_once __DIR__ . '/data/quiz_data_1_5.php';
require_once __DIR__ . '/data/quiz_data_6_10.php';
require_once __DIR__ . '/data/chapter_descriptions.php';

// Combinar datos
$GLOSSARY_DATA = $GLOSSARY_DATA + $GLOSSARY_DATA_6_10;
$QUIZ_DATA = $QUIZ_DATA + $QUIZ_DATA_6_10;

// Variables globales
$allFiles = [];
$allActivities = [];
$allSections = [];
$allQuestions = [];
$generationTime = time();

// IDs - empezamos desde 1 para este curso independiente
$nextFileId = 1;
$nextContextId = 100;
$nextModuleId = 1;
$nextInstanceId = 1;
$nextSectionId = 1;
$nextQuestionId = 1;
$nextAnswerId = 1;
$nextEntryId = 1;

// ============================================================================
// FUNCIONES AUXILIARES
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

function escapeXml($str) {
    return htmlspecialchars($str, ENT_XML1 | ENT_QUOTES, 'UTF-8');
}

function logMessage($msg, $icon = '  ') {
    echo "[" . date('H:i:s') . "] {$icon} {$msg}\n";
}

// ============================================================================
// FUNCIONES DE GENERACION DE ACTIVIDADES
// ============================================================================

function generateActivityCommonXMLs($activityDir) {
    file_put_contents($activityDir . '/grades.xml',
        '<?xml version="1.0" encoding="UTF-8"?>' . "\n" .
        '<activity_gradebook><grade_items></grade_items><grade_letters></grade_letters></activity_gradebook>');

    file_put_contents($activityDir . '/roles.xml',
        '<?xml version="1.0" encoding="UTF-8"?>' . "\n" .
        '<roles><role_overrides></role_overrides><role_assignments></role_assignments></roles>');

    file_put_contents($activityDir . '/filters.xml',
        '<?xml version="1.0" encoding="UTF-8"?>' . "\n" .
        '<filters><filter_actives></filter_actives><filter_configs></filter_configs></filters>');

    file_put_contents($activityDir . '/comments.xml',
        '<?xml version="1.0" encoding="UTF-8"?><comments></comments>');

    file_put_contents($activityDir . '/calendar.xml',
        '<?xml version="1.0" encoding="UTF-8"?><events></events>');

    file_put_contents($activityDir . '/competencies.xml',
        '<?xml version="1.0" encoding="UTF-8"?><course_module_competencies></course_module_competencies>');

    file_put_contents($activityDir . '/inforef.xml',
        '<?xml version="1.0" encoding="UTF-8"?><inforef><fileref></fileref></inforef>');
}

function updateInforefWithFile($activityDir, $fileId) {
    $inforef = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<inforef>
  <fileref>
    <file><id>{$fileId}</id></file>
  </fileref>
</inforef>
XML;
    file_put_contents($activityDir . '/inforef.xml', $inforef);
}

/**
 * Genera un label de introduccion para un capitulo
 */
function generateChapterIntroLabel($activity, $activityDir, $time, $chapterNum, $chapterTitle, $chapterIntro) {
    $moduleId = $activity['moduleid'];
    $instanceId = $activity['instanceid'];
    $contextId = $activity['contextid'];
    $sectionId = $activity['sectionid'];
    $sectionNum = $activity['sectionnumber'];
    $name = escapeXml($activity['name']);

    $chapterTitleEsc = escapeXml($chapterTitle);
    $chapterIntroEsc = escapeXml($chapterIntro);

    $iconSvg = '<svg viewBox="0 0 50 50" width="40" height="40"><circle cx="25" cy="20" r="12" fill="none" stroke="white" stroke-width="2"/><path d="M25 35 L25 45 M20 40 L30 40" stroke="white" stroke-width="2" fill="none"/><circle cx="21" cy="18" r="2" fill="white"/><circle cx="29" cy="18" r="2" fill="white"/><path d="M20 24 Q25 28 30 24" stroke="white" stroke-width="1.5" fill="none"/></svg>';

    $content = <<<HTML
<div style="max-width: 900px; margin: 0 auto 30px auto; font-family: 'Segoe UI', Roboto, sans-serif;">
    <div style="background: linear-gradient(135deg, #0170B9 0%, #3a3a3a 100%); border-radius: 20px; overflow: hidden; box-shadow: 0 8px 32px rgba(0,0,0,0.15);">
        <div style="display: flex; align-items: center; gap: 20px; padding: 25px 30px; border-bottom: 1px solid rgba(255,255,255,0.1);">
            <div style="background: rgba(255,255,255,0.15); width: 70px; height: 70px; border-radius: 50%; display: flex; align-items: center; justify-content: center; flex-shrink: 0; border: 2px solid rgba(255,255,255,0.2);">
                {$iconSvg}
            </div>
            <div>
                <div style="color: rgba(255,255,255,0.7); font-size: 14px; text-transform: uppercase; letter-spacing: 2px; margin-bottom: 5px;">Capitulo {$chapterNum}</div>
                <h2 style="color: white; margin: 0; font-size: 28px; font-weight: 600;">{$chapterTitleEsc}</h2>
            </div>
        </div>
        <div style="padding: 25px 30px; color: rgba(255,255,255,0.9); font-size: 16px; line-height: 1.7;">
            <p style="margin: 0;">{$chapterIntroEsc}</p>
        </div>
    </div>
</div>
HTML;

    $contentEsc = escapeXml($content);

    $labelXml = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<activity id="{$instanceId}" moduleid="{$moduleId}" modulename="label" contextid="{$contextId}">
  <label id="{$instanceId}">
    <name>{$name}</name>
    <intro>{$contentEsc}</intro>
    <introformat>1</introformat>
    <timemodified>{$time}</timemodified>
  </label>
</activity>
XML;
    file_put_contents($activityDir . '/label.xml', $labelXml);

    $moduleXml = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<module id="{$moduleId}" version="2024042200">
  <modulename>label</modulename>
  <sectionid>{$sectionId}</sectionid>
  <sectionnumber>{$sectionNum}</sectionnumber>
  <idnumber></idnumber>
  <added>{$time}</added>
  <score>0</score>
  <indent>0</indent>
  <visible>1</visible>
  <visibleoncoursepage>1</visibleoncoursepage>
  <visibleold>1</visibleold>
  <groupmode>0</groupmode>
  <groupingid>0</groupingid>
  <completion>0</completion>
  <completiongradeitemnumber>\$@NULL@\$</completiongradeitemnumber>
  <completionview>0</completionview>
  <completionexpected>0</completionexpected>
  <completionpassgrade>0</completionpassgrade>
  <availability>\$@NULL@\$</availability>
  <showdescription>1</showdescription>
  <downloadcontent>1</downloadcontent>
  <lang></lang>
  <tags></tags>
</module>
XML;
    file_put_contents($activityDir . '/module.xml', $moduleXml);
    generateActivityCommonXMLs($activityDir);
}

/**
 * Genera un video placeholder
 */
function generateVideoPlaceholder($activity, $activityDir, $time) {
    $moduleId = $activity['moduleid'];
    $instanceId = $activity['instanceid'];
    $contextId = $activity['contextid'];
    $sectionId = $activity['sectionid'];
    $sectionNum = $activity['sectionnumber'];
    $name = escapeXml($activity['name']);

    $content = <<<HTML
<div style="max-width: 800px; margin: 20px auto; font-family: 'Segoe UI', Roboto, sans-serif;">
    <div style="background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%); border-radius: 16px; overflow: hidden; box-shadow: 0 4px 20px rgba(0,0,0,0.3);">
        <div style="padding: 60px 40px; text-align: center;">
            <div style="width: 80px; height: 80px; margin: 0 auto 20px; background: rgba(255,255,255,0.1); border-radius: 50%; display: flex; align-items: center; justify-content: center;">
                <svg viewBox="0 0 24 24" width="40" height="40" fill="none" stroke="#e94560" stroke-width="2">
                    <polygon points="5 3 19 12 5 21 5 3"></polygon>
                </svg>
            </div>
            <h3 style="color: white; margin: 0 0 10px 0; font-size: 20px;">{$name}</h3>
            <p style="color: rgba(255,255,255,0.6); margin: 0; font-size: 14px;">Video educativo - Contenido multimedia</p>
        </div>
    </div>
</div>
HTML;

    $contentEsc = escapeXml($content);

    $labelXml = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<activity id="{$instanceId}" moduleid="{$moduleId}" modulename="label" contextid="{$contextId}">
  <label id="{$instanceId}">
    <name>{$name}</name>
    <intro>{$contentEsc}</intro>
    <introformat>1</introformat>
    <timemodified>{$time}</timemodified>
  </label>
</activity>
XML;
    file_put_contents($activityDir . '/label.xml', $labelXml);

    $moduleXml = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<module id="{$moduleId}" version="2024042200">
  <modulename>label</modulename>
  <sectionid>{$sectionId}</sectionid>
  <sectionnumber>{$sectionNum}</sectionnumber>
  <idnumber></idnumber>
  <added>{$time}</added>
  <score>0</score>
  <indent>0</indent>
  <visible>1</visible>
  <visibleoncoursepage>1</visibleoncoursepage>
  <visibleold>1</visibleold>
  <groupmode>0</groupmode>
  <groupingid>0</groupingid>
  <completion>0</completion>
  <completiongradeitemnumber>\$@NULL@\$</completiongradeitemnumber>
  <completionview>0</completionview>
  <completionexpected>0</completionexpected>
  <completionpassgrade>0</completionpassgrade>
  <availability>\$@NULL@\$</availability>
  <showdescription>1</showdescription>
  <downloadcontent>1</downloadcontent>
  <lang></lang>
  <tags></tags>
</module>
XML;
    file_put_contents($activityDir . '/module.xml', $moduleXml);
    generateActivityCommonXMLs($activityDir);
}

/**
 * Genera un recurso (archivo)
 */
function generateResourceActivity($activity, $activityDir, $time, $filePath) {
    global $allFiles, $nextFileId;

    $moduleId = $activity['moduleid'];
    $instanceId = $activity['instanceid'];
    $contextId = $activity['contextid'];
    $sectionId = $activity['sectionid'];
    $sectionNum = $activity['sectionnumber'];
    $name = escapeXml($activity['name']);

    // Procesar archivo
    $filename = basename($filePath);
    $filesize = file_exists($filePath) ? filesize($filePath) : 0;
    $contenthash = file_exists($filePath) ? sha1_file($filePath) : sha1($filename . time());
    $mimetype = getMimeType($filename);

    $fileId = $nextFileId++;
    $allFiles[] = [
        'id' => $fileId,
        'contenthash' => $contenthash,
        'contextid' => $contextId,
        'component' => 'mod_resource',
        'filearea' => 'content',
        'itemid' => 0,
        'filepath' => '/',
        'filename' => $filename,
        'mimetype' => $mimetype,
        'filesize' => $filesize,
        'timecreated' => $time,
        'timemodified' => $time,
        'source' => $filePath
    ];

    $resourceXml = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<activity id="{$instanceId}" moduleid="{$moduleId}" modulename="resource" contextid="{$contextId}">
  <resource id="{$instanceId}">
    <name>{$name}</name>
    <intro></intro>
    <introformat>1</introformat>
    <tobemigrated>0</tobemigrated>
    <legacyfiles>0</legacyfiles>
    <legacyfileslast>\$@NULL@\$</legacyfileslast>
    <display>0</display>
    <displayoptions>a:1:{s:10:"printintro";i:1;}</displayoptions>
    <filterfiles>0</filterfiles>
    <revision>1</revision>
    <timemodified>{$time}</timemodified>
  </resource>
</activity>
XML;
    file_put_contents($activityDir . '/resource.xml', $resourceXml);

    $moduleXml = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<module id="{$moduleId}" version="2024042200">
  <modulename>resource</modulename>
  <sectionid>{$sectionId}</sectionid>
  <sectionnumber>{$sectionNum}</sectionnumber>
  <idnumber></idnumber>
  <added>{$time}</added>
  <score>0</score>
  <indent>0</indent>
  <visible>1</visible>
  <visibleoncoursepage>1</visibleoncoursepage>
  <visibleold>1</visibleold>
  <groupmode>0</groupmode>
  <groupingid>0</groupingid>
  <completion>1</completion>
  <completiongradeitemnumber>\$@NULL@\$</completiongradeitemnumber>
  <completionview>1</completionview>
  <completionexpected>0</completionexpected>
  <completionpassgrade>0</completionpassgrade>
  <availability>\$@NULL@\$</availability>
  <showdescription>0</showdescription>
  <downloadcontent>1</downloadcontent>
  <lang></lang>
  <tags></tags>
</module>
XML;
    file_put_contents($activityDir . '/module.xml', $moduleXml);
    generateActivityCommonXMLs($activityDir);
    updateInforefWithFile($activityDir, $fileId);

    return $fileId;
}

/**
 * Genera un glosario
 */
function generateGlossaryActivity($activity, $activityDir, $time, $entries) {
    global $nextEntryId;

    $moduleId = $activity['moduleid'];
    $instanceId = $activity['instanceid'];
    $contextId = $activity['contextid'];
    $sectionId = $activity['sectionid'];
    $sectionNum = $activity['sectionnumber'];
    $name = escapeXml($activity['name']);

    $entriesXml = '';
    foreach ($entries as $entry) {
        $entryId = $nextEntryId++;
        // Support both 'concept' and 'term' keys
        $concept = escapeXml(isset($entry['concept']) ? $entry['concept'] : $entry['term']);
        $definition = escapeXml($entry['definition']);

        $entriesXml .= <<<ENTRY

      <entry id="{$entryId}">
        <userid>2</userid>
        <concept>{$concept}</concept>
        <definition>{$definition}</definition>
        <definitionformat>1</definitionformat>
        <definitiontrust>0</definitiontrust>
        <attachment></attachment>
        <timecreated>{$time}</timecreated>
        <timemodified>{$time}</timemodified>
        <teacherentry>0</teacherentry>
        <sourceglossaryid>0</sourceglossaryid>
        <usedynalink>1</usedynalink>
        <casesensitive>0</casesensitive>
        <fullmatch>0</fullmatch>
        <approved>1</approved>
        <aliases></aliases>
        <ratings></ratings>
        <tags></tags>
      </entry>
ENTRY;
    }

    $glossaryXml = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<activity id="{$instanceId}" moduleid="{$moduleId}" modulename="glossary" contextid="{$contextId}">
  <glossary id="{$instanceId}">
    <name>{$name}</name>
    <intro>&lt;p&gt;Glosario de terminos del capitulo&lt;/p&gt;</intro>
    <introformat>1</introformat>
    <allowduplicatedentries>0</allowduplicatedentries>
    <displayformat>dictionary</displayformat>
    <mainglossary>0</mainglossary>
    <showspecial>1</showspecial>
    <showalphabet>1</showalphabet>
    <showall>1</showall>
    <allowcomments>0</allowcomments>
    <allowprintview>1</allowprintview>
    <usedynalink>1</usedynalink>
    <defaultapproval>1</defaultapproval>
    <approvaldisplayformat>default</approvaldisplayformat>
    <globalglossary>0</globalglossary>
    <entbypage>10</entbypage>
    <editalways>0</editalways>
    <rsstype>0</rsstype>
    <rssarticles>0</rssarticles>
    <assessed>0</assessed>
    <assesstimestart>0</assesstimestart>
    <assesstimefinish>0</assesstimefinish>
    <scale>0</scale>
    <timecreated>{$time}</timecreated>
    <timemodified>{$time}</timemodified>
    <completionentries>0</completionentries>
    <entries>{$entriesXml}
    </entries>
    <oneperpage>0</oneperpage>
    <categories></categories>
  </glossary>
</activity>
XML;
    file_put_contents($activityDir . '/glossary.xml', $glossaryXml);

    $moduleXml = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<module id="{$moduleId}" version="2024042200">
  <modulename>glossary</modulename>
  <sectionid>{$sectionId}</sectionid>
  <sectionnumber>{$sectionNum}</sectionnumber>
  <idnumber></idnumber>
  <added>{$time}</added>
  <score>0</score>
  <indent>0</indent>
  <visible>1</visible>
  <visibleoncoursepage>1</visibleoncoursepage>
  <visibleold>1</visibleold>
  <groupmode>0</groupmode>
  <groupingid>0</groupingid>
  <completion>0</completion>
  <completiongradeitemnumber>\$@NULL@\$</completiongradeitemnumber>
  <completionview>0</completionview>
  <completionexpected>0</completionexpected>
  <completionpassgrade>0</completionpassgrade>
  <availability>\$@NULL@\$</availability>
  <showdescription>0</showdescription>
  <downloadcontent>1</downloadcontent>
  <lang></lang>
  <tags></tags>
</module>
XML;
    file_put_contents($activityDir . '/module.xml', $moduleXml);
    generateActivityCommonXMLs($activityDir);

    return count($entries);
}

/**
 * Genera un quiz con preguntas
 */
function generateQuizActivity($activity, $activityDir, $time, $questions, $categoryId, $categoryName) {
    global $allQuestions, $nextQuestionId, $nextAnswerId;

    $moduleId = $activity['moduleid'];
    $instanceId = $activity['instanceid'];
    $contextId = $activity['contextid'];
    $sectionId = $activity['sectionid'];
    $sectionNum = $activity['sectionnumber'];
    $name = escapeXml($activity['name']);
    $intro = escapeXml($activity['intro'] ?? 'Autoevaluacion del capitulo');

    $questionSlots = '';
    $questionsForCategory = [];
    $slot = 1;

    foreach ($questions as $q) {
        $questionId = $nextQuestionId++;
        $questionName = escapeXml($q['question']);
        $questionText = escapeXml($q['question']);
        $stamp = 'localhost+' . time() . '+' . bin2hex(random_bytes(6));
        $versionStamp = 'localhost+' . time() . '+' . bin2hex(random_bytes(6));

        $answersXml = '';
        $answerNum = 0;
        foreach ($q['options'] as $optText) {
            $answerId = $nextAnswerId++;
            $answerText = escapeXml($optText);
            $fraction = ($answerNum == $q['correct']) ? '1.0000000' : '0.0000000';
            $feedback = ($answerNum == $q['correct']) ? 'Correcto' : 'Incorrecto';

            $answersXml .= <<<ANS
            <answer id="{$answerId}">
              <answertext>{$answerText}</answertext>
              <answerformat>1</answerformat>
              <fraction>{$fraction}</fraction>
              <feedback>{$feedback}</feedback>
              <feedbackformat>1</feedbackformat>
            </answer>

ANS;
            $answerNum++;
        }

        $questionXml = <<<XML
      <question id="{$questionId}">
        <parent>0</parent>
        <name>{$questionName}</name>
        <questiontext>{$questionText}</questiontext>
        <questiontextformat>1</questiontextformat>
        <generalfeedback></generalfeedback>
        <generalfeedbackformat>1</generalfeedbackformat>
        <defaultmark>1.0000000</defaultmark>
        <penalty>0.3333333</penalty>
        <qtype>multichoice</qtype>
        <length>1</length>
        <stamp>{$stamp}</stamp>
        <version>{$versionStamp}</version>
        <hidden>0</hidden>
        <timecreated>{$time}</timecreated>
        <timemodified>{$time}</timemodified>
        <createdby>2</createdby>
        <modifiedby>2</modifiedby>
        <plugin_qtype_multichoice_question>
          <answers>
{$answersXml}
          </answers>
          <multichoice id="{$questionId}">
            <layout>0</layout>
            <single>1</single>
            <shuffleanswers>1</shuffleanswers>
            <correctfeedback>Respuesta correcta</correctfeedback>
            <correctfeedbackformat>1</correctfeedbackformat>
            <partiallycorrectfeedback>Parcialmente correcta</partiallycorrectfeedback>
            <partiallycorrectfeedbackformat>1</partiallycorrectfeedbackformat>
            <incorrectfeedback>Respuesta incorrecta</incorrectfeedback>
            <incorrectfeedbackformat>1</incorrectfeedbackformat>
            <answernumbering>abc</answernumbering>
            <shownumcorrect>1</shownumcorrect>
            <showstandardinstruction>0</showstandardinstruction>
          </multichoice>
        </plugin_qtype_multichoice_question>
        <question_hints>
        </question_hints>
        <tags>
        </tags>
      </question>
XML;

        $bankEntryId = $questionId;
        $versionId = $questionId;

        $questionsForCategory[] = [
            'bankentryid' => $bankEntryId,
            'versionid' => $versionId,
            'questionxml' => $questionXml
        ];

        $questionSlots .= <<<SLOT
      <question_instance id="{$slot}">
        <quizid>{$instanceId}</quizid>
        <slot>{$slot}</slot>
        <page>1</page>
        <displaynumber>\$@NULL@\$</displaynumber>
        <requireprevious>0</requireprevious>
        <maxmark>1.0000000</maxmark>
        <quizgradeitemid>\$@NULL@\$</quizgradeitemid>
        <question_reference>
          <usingcontextid>{$contextId}</usingcontextid>
          <component>mod_quiz</component>
          <questionarea>slot</questionarea>
          <questionbankentryid>{$bankEntryId}</questionbankentryid>
          <version>\$@NULL@\$</version>
        </question_reference>
      </question_instance>
SLOT;
        $slot++;
    }

    $courseContextId = 50;

    $allQuestions[] = [
        'categoryid' => $categoryId,
        'categoryname' => $categoryName,
        'contextid' => $courseContextId,
        'questions' => $questionsForCategory
    ];

    $numQuestions = count($questions);
    $sumgrades = $numQuestions . '.00000';
    $grade = $numQuestions . '.00000';

    $quizXml = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<activity id="{$instanceId}" moduleid="{$moduleId}" modulename="quiz" contextid="{$contextId}">
  <quiz id="{$instanceId}">
    <name>{$name}</name>
    <intro>{$intro}</intro>
    <introformat>1</introformat>
    <timeopen>0</timeopen>
    <timeclose>0</timeclose>
    <timelimit>0</timelimit>
    <overduehandling>autosubmit</overduehandling>
    <graceperiod>0</graceperiod>
    <preferredbehaviour>deferredfeedback</preferredbehaviour>
    <canredoquestions>0</canredoquestions>
    <attempts_number>0</attempts_number>
    <attemptonlast>0</attemptonlast>
    <grademethod>1</grademethod>
    <decimalpoints>2</decimalpoints>
    <questiondecimalpoints>-1</questiondecimalpoints>
    <reviewattempt>69888</reviewattempt>
    <reviewcorrectness>4352</reviewcorrectness>
    <reviewmaxmarks>69888</reviewmaxmarks>
    <reviewmarks>4352</reviewmarks>
    <reviewspecificfeedback>4352</reviewspecificfeedback>
    <reviewgeneralfeedback>4352</reviewgeneralfeedback>
    <reviewrightanswer>4352</reviewrightanswer>
    <reviewoverallfeedback>4352</reviewoverallfeedback>
    <questionsperpage>1</questionsperpage>
    <navmethod>free</navmethod>
    <shuffleanswers>1</shuffleanswers>
    <sumgrades>{$sumgrades}</sumgrades>
    <grade>{$grade}</grade>
    <timecreated>{$time}</timecreated>
    <timemodified>{$time}</timemodified>
    <password></password>
    <subnet></subnet>
    <browsersecurity>-</browsersecurity>
    <delay1>0</delay1>
    <delay2>0</delay2>
    <showuserpicture>0</showuserpicture>
    <showblocks>0</showblocks>
    <completionattemptsexhausted>0</completionattemptsexhausted>
    <completionminattempts>0</completionminattempts>
    <allowofflineattempts>0</allowofflineattempts>
    <subtype></subtype>
    <hasfeedback>0</hasfeedback>
    <hasquestions>1</hasquestions>
    <question_instances>
{$questionSlots}
    </question_instances>
    <sections>
      <section id="1">
        <firstslot>1</firstslot>
        <heading></heading>
        <shufflequestions>0</shufflequestions>
      </section>
    </sections>
    <feedbacks></feedbacks>
    <overrides></overrides>
    <grades></grades>
    <attempts></attempts>
  </quiz>
</activity>
XML;
    file_put_contents($activityDir . '/quiz.xml', $quizXml);

    $gradesXml = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<activity_gradebook>
  <grade_items>
    <grade_item id="{$instanceId}">
      <categoryid>\$@NULL@\$</categoryid>
      <itemname>{$name}</itemname>
      <itemtype>mod</itemtype>
      <itemmodule>quiz</itemmodule>
      <iteminstance>{$instanceId}</iteminstance>
      <itemnumber>0</itemnumber>
      <iteminfo>\$@NULL@\$</iteminfo>
      <idnumber></idnumber>
      <calculation>\$@NULL@\$</calculation>
      <gradetype>1</gradetype>
      <grademax>{$grade}</grademax>
      <grademin>0.00000</grademin>
      <scaleid>\$@NULL@\$</scaleid>
      <outcomeid>\$@NULL@\$</outcomeid>
      <gradepass>6.00000</gradepass>
      <multfactor>1.00000</multfactor>
      <plusfactor>0.00000</plusfactor>
      <aggregationcoef>0.00000</aggregationcoef>
      <aggregationcoef2>0.00000</aggregationcoef2>
      <weightoverride>0</weightoverride>
      <sortorder>1</sortorder>
      <display>0</display>
      <decimals>\$@NULL@\$</decimals>
      <hidden>0</hidden>
      <locked>0</locked>
      <locktime>0</locktime>
      <needsupdate>0</needsupdate>
      <timecreated>{$time}</timecreated>
      <timemodified>{$time}</timemodified>
      <grade_grades></grade_grades>
    </grade_item>
  </grade_items>
  <grade_letters></grade_letters>
</activity_gradebook>
XML;
    file_put_contents($activityDir . '/grades.xml', $gradesXml);

    file_put_contents($activityDir . '/roles.xml', '<?xml version="1.0" encoding="UTF-8"?>' . "\n" . '<roles><role_overrides></role_overrides><role_assignments></role_assignments></roles>');
    file_put_contents($activityDir . '/filters.xml', '<?xml version="1.0" encoding="UTF-8"?>' . "\n" . '<filters><filter_actives></filter_actives><filter_configs></filter_configs></filters>');
    file_put_contents($activityDir . '/comments.xml', '<?xml version="1.0" encoding="UTF-8"?><comments></comments>');
    file_put_contents($activityDir . '/calendar.xml', '<?xml version="1.0" encoding="UTF-8"?><events></events>');
    file_put_contents($activityDir . '/competencies.xml', '<?xml version="1.0" encoding="UTF-8"?><course_module_competencies></course_module_competencies>');

    // CRITICAL: Include question_categoryref
    $inforefXml = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<inforef>
  <fileref></fileref>
  <question_categoryref>
    <question_category id="{$categoryId}"/>
  </question_categoryref>
</inforef>
XML;
    file_put_contents($activityDir . '/inforef.xml', $inforefXml);

    return count($questions);
}

// ============================================================================
// GENERAR XMLs GLOBALES
// ============================================================================

function generateGlobalXMLs() {
    global $allSections, $allActivities, $allFiles, $allQuestions, $generationTime;

    logMessage("Generando XMLs globales...", '📄');

    generateMoodleBackupXML();
    generateCourseXML();

    foreach ($allSections as $section) {
        generateSectionXML($section);
    }

    generateFilesXML();
    generateQuestionsXMLFile();

    file_put_contents(OUTPUT_DIR . '/scales.xml', '<?xml version="1.0" encoding="UTF-8"?><scales_definition></scales_definition>');
    file_put_contents(OUTPUT_DIR . '/outcomes.xml', '<?xml version="1.0" encoding="UTF-8"?><outcomes_definition></outcomes_definition>');
    file_put_contents(OUTPUT_DIR . '/groups.xml', '<?xml version="1.0" encoding="UTF-8"?><groups></groups>');
    file_put_contents(OUTPUT_DIR . '/roles.xml', '<?xml version="1.0" encoding="UTF-8"?><roles></roles>');
    file_put_contents(OUTPUT_DIR . '/grade_history.xml', '<?xml version="1.0" encoding="UTF-8"?><grade_history></grade_history>');

    generateUsersXML();
}

function generateMoodleBackupXML() {
    global $allActivities, $allSections, $generationTime;

    $activitiesXml = '';
    foreach ($allActivities as $act) {
        $activitiesXml .= <<<ACT
      <activity>
        <moduleid>{$act['moduleid']}</moduleid>
        <sectionid>{$act['sectionid']}</sectionid>
        <modulename>{$act['modulename']}</modulename>
        <title>{$act['title']}</title>
        <directory>activities/{$act['modulename']}_{$act['moduleid']}</directory>
      </activity>

ACT;
    }

    $sectionsXml = '';
    foreach ($allSections as $sec) {
        $sectionsXml .= <<<SEC
      <section>
        <sectionid>{$sec['id']}</sectionid>
        <title>{$sec['name']}</title>
        <directory>sections/section_{$sec['id']}</directory>
      </section>

SEC;
    }

    $activitySettings = '';
    foreach ($allActivities as $act) {
        $actDir = "{$act['modulename']}_{$act['moduleid']}";
        $activitySettings .= <<<SET
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
        <value>1</value>
      </setting>
SET;
    }

    $sectionSettings = '';
    foreach ($allSections as $sec) {
        $sectionSettings .= <<<SET
      <setting>
        <level>section</level>
        <section>section_{$sec['id']}</section>
        <name>section_{$sec['id']}_included</name>
        <value>1</value>
      </setting>
      <setting>
        <level>section</level>
        <section>section_{$sec['id']}</section>
        <name>section_{$sec['id']}_userinfo</name>
        <value>1</value>
      </setting>
SET;
    }

    $xml = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<moodle_backup>
  <information>
    <name>backup-capitulo2-only.mbz</name>
    <moodle_version>2024042200</moodle_version>
    <moodle_release>4.4</moodle_release>
    <backup_version>2024042200</backup_version>
    <backup_release>4.4</backup_release>
    <backup_date>{$generationTime}</backup_date>
    <mnet_remoteusers>0</mnet_remoteusers>
    <include_files>1</include_files>
    <include_file_references_to_external_content>0</include_file_references_to_external_content>
    <original_wwwroot>https://campus.example.com</original_wwwroot>
    <original_site_identifier_hash>mbzgen_chapter2</original_site_identifier_hash>
    <original_course_id>2</original_course_id>
    <original_course_format>topics</original_course_format>
    <original_course_fullname>Capitulo 2: Funciones Emocionales</original_course_fullname>
    <original_course_shortname>cap2-emociones</original_course_shortname>
    <original_course_startdate>{$generationTime}</original_course_startdate>
    <original_course_enddate>0</original_course_enddate>
    <original_course_contextid>50</original_course_contextid>
    <original_system_contextid>1</original_system_contextid>
    <details>
      <detail backup_id="mbzgen_chapter2">
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
        <title>Capitulo 2: Funciones Emocionales</title>
        <directory>course</directory>
      </course>
    </contents>
    <settings>
      <setting>
        <level>root</level>
        <name>filename</name>
        <value>backup-capitulo2-only.mbz</value>
      </setting>
      <setting>
        <level>root</level>
        <name>imscc11</name>
        <value>0</value>
      </setting>
      <setting>
        <level>root</level>
        <name>users</name>
        <value>1</value>
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

    $xml = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<course id="2" contextid="50">
  <shortname>cap2-emociones</shortname>
  <fullname>Capitulo 2: Funciones Emocionales</fullname>
  <idnumber></idnumber>
  <summary>&lt;p&gt;Contenido del Capitulo 2 sobre Funciones Emocionales del curso Huellas Invisibles.&lt;/p&gt;</summary>
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
  <category id="1">
    <name>Miscellaneous</name>
    <description></description>
  </category>
  <tags></tags>
  <customfields></customfields>
  <courseformatoptions></courseformatoptions>
</course>
XML;
    file_put_contents(OUTPUT_DIR . '/course/course.xml', $xml);

    file_put_contents(OUTPUT_DIR . '/course/enrolments.xml', '<?xml version="1.0" encoding="UTF-8"?><enrolments><enrols></enrols></enrolments>');
    file_put_contents(OUTPUT_DIR . '/course/roles.xml', '<?xml version="1.0" encoding="UTF-8"?><roles><role_overrides></role_overrides><role_assignments></role_assignments></roles>');
    file_put_contents(OUTPUT_DIR . '/course/completiondefaults.xml', '<?xml version="1.0" encoding="UTF-8"?><course_completion_defaults></course_completion_defaults>');
    file_put_contents(OUTPUT_DIR . '/course/inforef.xml', '<?xml version="1.0" encoding="UTF-8"?><inforef></inforef>');
}

function generateSectionXML($section) {
    global $generationTime;

    $sectionDir = OUTPUT_DIR . '/sections/section_' . $section['id'];
    if (!is_dir($sectionDir)) {
        mkdir($sectionDir, 0755, true);
    }

    $name = escapeXml($section['name']);
    $summary = isset($section['summary']) ? escapeXml($section['summary']) : '';
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
    file_put_contents($sectionDir . '/inforef.xml', '<?xml version="1.0" encoding="UTF-8"?><inforef></inforef>');
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
    <userid>2</userid>
    <filesize>{$file['filesize']}</filesize>
    <mimetype>{$file['mimetype']}</mimetype>
    <status>0</status>
    <timecreated>{$file['timecreated']}</timecreated>
    <timemodified>{$file['timemodified']}</timemodified>
    <source>{$file['filename']}</source>
    <author>Docente</author>
    <license>allrightsreserved</license>
    <sortorder>0</sortorder>
    <repositorytype>\$@NULL@\$</repositorytype>
    <repositoryid>\$@NULL@\$</repositoryid>
    <reference>\$@NULL@\$</reference>
  </file>

FILE;
    }

    $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n<files>\n" . $filesXml . "</files>";
    file_put_contents(OUTPUT_DIR . '/files.xml', $xml);
}

function generateQuestionsXMLFile() {
    global $allQuestions, $generationTime;

    if (empty($allQuestions)) {
        file_put_contents(OUTPUT_DIR . '/questions.xml', '<?xml version="1.0" encoding="UTF-8"?>' . "\n<question_categories>\n</question_categories>");
        return;
    }

    $categoriesXml = '';
    foreach ($allQuestions as $category) {
        $catId = $category['categoryid'];
        $catName = escapeXml($category['categoryname']);
        $catContextId = $category['contextid'];

        $bankEntriesXml = '';
        foreach ($category['questions'] as $qData) {
            $bankEntryId = $qData['bankentryid'];
            $versionId = $qData['versionid'];
            $questionXml = $qData['questionxml'];

            $bankEntriesXml .= <<<ENTRY
    <question_bank_entry id="{$bankEntryId}">
      <questioncategoryid>{$catId}</questioncategoryid>
      <idnumber></idnumber>
      <ownerid>2</ownerid>
      <question_version id="{$versionId}">
        <version>1</version>
        <status>ready</status>
        <question_versions>
{$questionXml}
        </question_versions>
      </question_version>
    </question_bank_entry>

ENTRY;
        }

        $stamp = 'localhost+' . time() . '+' . bin2hex(random_bytes(6));

        $categoriesXml .= <<<CAT
  <question_category id="{$catId}">
    <name>{$catName}</name>
    <contextid>{$catContextId}</contextid>
    <contextlevel>50</contextlevel>
    <contextinstanceid>1</contextinstanceid>
    <info></info>
    <infoformat>0</infoformat>
    <stamp>{$stamp}</stamp>
    <parent>0</parent>
    <sortorder>999</sortorder>
    <idnumber></idnumber>
    <question_bank_entries>
{$bankEntriesXml}
    </question_bank_entries>
  </question_category>

CAT;
    }

    $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n<question_categories>\n" . $categoriesXml . "</question_categories>";
    file_put_contents(OUTPUT_DIR . '/questions.xml', $xml);
}

function generateUsersXML() {
    global $generationTime;

    $xml = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<users>
  <user id="2" contextid="5">
    <username>admin</username>
    <idnumber></idnumber>
    <email>admin@example.com</email>
    <firstname>Admin</firstname>
    <lastname>User</lastname>
    <auth>manual</auth>
    <confirmed>1</confirmed>
    <suspended>0</suspended>
    <deleted>0</deleted>
    <timecreated>{$generationTime}</timecreated>
    <timemodified>{$generationTime}</timemodified>
  </user>
</users>
XML;
    file_put_contents(OUTPUT_DIR . '/users.xml', $xml);
}

// ============================================================================
// CREAR ESTRUCTURA Y GENERAR CONTENIDO - SOLO CAPITULO 2
// ============================================================================

logMessage("Creando estructura de directorios...", '📁');

if (is_dir(OUTPUT_DIR)) {
    exec('rm -rf ' . escapeshellarg(OUTPUT_DIR));
}
mkdir(OUTPUT_DIR, 0755, true);
mkdir(OUTPUT_DIR . '/activities', 0755, true);
mkdir(OUTPUT_DIR . '/sections', 0755, true);
mkdir(OUTPUT_DIR . '/course', 0755, true);
mkdir(OUTPUT_DIR . '/files', 0755, true);

logMessage("Procesando solo Capitulo 2...", '📚');

// Crear seccion unica para el capitulo 2
$sectionId = $nextSectionId++;
$sectionSequence = [];

$allSections[] = [
    'id' => $sectionId,
    'number' => 1,
    'name' => 'Capitulo 2: Funciones Emocionales',
    'sequence' => &$sectionSequence
];

logMessage("Seccion 1: Capitulo 2: Funciones Emocionales", '📁');

// Intro del capitulo
$chapterNum = 2;
$chapterTitle = 'Funciones Emocionales';
$chapterIntro = isset($CHAPTER_DESCRIPTIONS[$chapterNum]['intro'])
    ? $CHAPTER_DESCRIPTIONS[$chapterNum]['intro']
    : 'Estudio de las funciones emocionales en el desarrollo infantil.';

$moduleId = $nextModuleId++;
$instanceId = $nextInstanceId++;
$contextId = $nextContextId++;

$activity = [
    'moduleid' => $moduleId,
    'instanceid' => $instanceId,
    'contextid' => $contextId,
    'sectionid' => $sectionId,
    'sectionnumber' => 1,
    'name' => "Introduccion - Capitulo 2"
];

$activityDir = OUTPUT_DIR . "/activities/label_{$moduleId}";
mkdir($activityDir, 0755, true);

generateChapterIntroLabel($activity, $activityDir, $generationTime, $chapterNum, $chapterTitle, $chapterIntro);
$sectionSequence[] = $moduleId;

$allActivities[] = [
    'moduleid' => $moduleId,
    'sectionid' => $sectionId,
    'modulename' => 'label',
    'title' => escapeXml($activity['name'])
];

logMessage("  + Intro capitulo: Funciones Emocionales", '🎯');

// Video placeholder
$moduleId = $nextModuleId++;
$instanceId = $nextInstanceId++;
$contextId = $nextContextId++;

$activity = [
    'moduleid' => $moduleId,
    'instanceid' => $instanceId,
    'contextid' => $contextId,
    'sectionid' => $sectionId,
    'sectionnumber' => 1,
    'name' => "Video: Funciones Emocionales"
];

$activityDir = OUTPUT_DIR . "/activities/label_{$moduleId}";
mkdir($activityDir, 0755, true);

generateVideoPlaceholder($activity, $activityDir, $generationTime);
$sectionSequence[] = $moduleId;

$allActivities[] = [
    'moduleid' => $moduleId,
    'sectionid' => $sectionId,
    'modulename' => 'label',
    'title' => escapeXml($activity['name'])
];

logMessage("  + Video placeholder: Video: Funciones Emocionales", '🎬');

// Recursos del capitulo 2
$chapter2Dir = CONTENT_DIR . '/Capitulo 2- Funciones emocionales_';
$resources = [
    '2-Neurociencia del desarrollo Libro Cerebro en construccion.pdf' => 'Libro: Cerebro en Construccion',
    '4-Paper Documento complementario.pdf' => 'Documento Complementario',
    '7-REFERENCIAS (BIBLIOGRAFIA).docx' => 'Bibliografia'
];

foreach ($resources as $filename => $title) {
    $filePath = $chapter2Dir . '/' . $filename;
    if (file_exists($filePath)) {
        $moduleId = $nextModuleId++;
        $instanceId = $nextInstanceId++;
        $contextId = $nextContextId++;

        $activity = [
            'moduleid' => $moduleId,
            'instanceid' => $instanceId,
            'contextid' => $contextId,
            'sectionid' => $sectionId,
            'sectionnumber' => 1,
            'name' => $title
        ];

        $activityDir = OUTPUT_DIR . "/activities/resource_{$moduleId}";
        mkdir($activityDir, 0755, true);

        generateResourceActivity($activity, $activityDir, $generationTime, $filePath);
        $sectionSequence[] = $moduleId;

        $allActivities[] = [
            'moduleid' => $moduleId,
            'sectionid' => $sectionId,
            'modulename' => 'resource',
            'title' => escapeXml($title)
        ];

        logMessage("  + Resource: {$title}", '📄');
    }
}

// Glosario del capitulo 2
if (isset($GLOSSARY_DATA[2]['terms'])) {
    $moduleId = $nextModuleId++;
    $instanceId = $nextInstanceId++;
    $contextId = $nextContextId++;

    $activity = [
        'moduleid' => $moduleId,
        'instanceid' => $instanceId,
        'contextid' => $contextId,
        'sectionid' => $sectionId,
        'sectionnumber' => 1,
        'name' => "Glosario de Terminos - Capitulo 2"
    ];

    $activityDir = OUTPUT_DIR . "/activities/glossary_{$moduleId}";
    mkdir($activityDir, 0755, true);

    $numEntries = generateGlossaryActivity($activity, $activityDir, $generationTime, $GLOSSARY_DATA[2]['terms']);
    $sectionSequence[] = $moduleId;

    $allActivities[] = [
        'moduleid' => $moduleId,
        'sectionid' => $sectionId,
        'modulename' => 'glossary',
        'title' => escapeXml($activity['name'])
    ];

    logMessage("  + Glossary: Glosario de Terminos - Capitulo 2 ({$numEntries} terminos)", '📖');
}

// Quiz del capitulo 2
if (isset($QUIZ_DATA[2]['questions'])) {
    $moduleId = $nextModuleId++;
    $instanceId = $nextInstanceId++;
    $contextId = $nextContextId++;

    // ID de categoria unico para este quiz
    $categoryId = 1001;  // Usamos 1001 como ID de categoria

    // Convert quiz data to the expected format
    $convertedQuestions = [];
    foreach ($QUIZ_DATA[2]['questions'] as $q) {
        $options = [];
        $correctIndex = 0;
        $i = 0;
        foreach ($q['answers'] as $answer) {
            $options[] = $answer['text'];
            if ($answer['correct']) {
                $correctIndex = $i;
            }
            $i++;
        }
        $convertedQuestions[] = [
            'question' => $q['text'],
            'options' => $options,
            'correct' => $correctIndex
        ];
    }

    $activity = [
        'moduleid' => $moduleId,
        'instanceid' => $instanceId,
        'contextid' => $contextId,
        'sectionid' => $sectionId,
        'sectionnumber' => 1,
        'name' => "Autoevaluacion - Capitulo 2",
        'intro' => "<p>Autoevaluacion sobre funciones emocionales, lateralizacion cerebral y procesamiento emocional.</p>"
    ];

    $activityDir = OUTPUT_DIR . "/activities/quiz_{$moduleId}";
    mkdir($activityDir, 0755, true);

    $numQuestions = generateQuizActivity(
        $activity,
        $activityDir,
        $generationTime,
        $convertedQuestions,
        $categoryId,
        'Preguntas Capitulo 2'
    );
    $sectionSequence[] = $moduleId;

    $allActivities[] = [
        'moduleid' => $moduleId,
        'sectionid' => $sectionId,
        'modulename' => 'quiz',
        'title' => escapeXml($activity['name'])
    ];

    logMessage("  + Quiz: Autoevaluacion - Capitulo 2 ({$numQuestions} preguntas)", '❓');
}

$totalActivities = count($allActivities);
$totalSections = count($allSections);

logMessage("Total: {$totalActivities} actividades en {$totalSections} secciones", '✅');

// Generar XMLs globales
generateGlobalXMLs();

// Copiar archivos al directorio files/
logMessage("Copiando archivos...", '📦');
foreach ($allFiles as $file) {
    if (file_exists($file['source'])) {
        $hashDir = OUTPUT_DIR . '/files/' . substr($file['contenthash'], 0, 2);
        if (!is_dir($hashDir)) {
            mkdir($hashDir, 0755, true);
        }
        copy($file['source'], $hashDir . '/' . $file['contenthash']);
    }
}

// Crear archivo MBZ
$mbzFilename = 'backup-capitulo2-only-' . date('Ymd-His') . '.mbz';
$mbzPath = OUTPUT_DIR . '/' . $mbzFilename;

logMessage("Creando archivo MBZ...", '📦');

$zip = new ZipArchive();
if ($zip->open($mbzPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) === TRUE) {
    $files = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator(OUTPUT_DIR, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::LEAVES_ONLY
    );

    foreach ($files as $file) {
        if (!$file->isDir() && strpos($file->getPathname(), '.mbz') === false) {
            $filePath = $file->getRealPath();
            $relativePath = substr($filePath, strlen(OUTPUT_DIR) + 1);
            $zip->addFile($filePath, $relativePath);
        }
    }

    $zip->close();

    $fileSize = round(filesize($mbzPath) / (1024 * 1024), 2);
    logMessage("MBZ creado: {$mbzFilename} ({$fileSize} MB)", '✅');
} else {
    logMessage("Error al crear el archivo MBZ", '❌');
}

echo "\n=======================================================\n";
echo " PROCESO COMPLETADO - SOLO CAPITULO 2\n";
echo "=======================================================\n";
echo " Secciones: {$totalSections}\n";
echo " Actividades: {$totalActivities}\n";
echo " Archivos: " . count($allFiles) . "\n";
echo " MBZ: {$mbzPath}\n";
echo "=======================================================\n";
