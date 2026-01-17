<?php
/**
 * Generador MBZ Incremental - Huellas Invisibles
 * Construye el curso fase por fase para validacion
 *
 * Uso: php generate_mbz_incremental.php [fase]
 * Fase 0 = Presentacion
 * Fase 1 = Capitulo 1
 * Fase 2 = Capitulo 2
 * ... etc
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Configuracion
define('CONTENT_DIR', '/home/user/Moodle_Dev/mbz_generator/curso_content/curso/Huellas invisibles');
define('OUTPUT_DIR', __DIR__ . '/backup_output_incremental');

// Obtener fase desde argumentos o usar 1 por defecto (Presentacion + Cap1)
$maxFase = isset($argv[1]) ? intval($argv[1]) : 1;

echo "=======================================================\n";
echo " MBZ Generator INCREMENTAL - Huellas Invisibles\n";
echo " Generando hasta Fase {$maxFase}\n";
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

// Funciones de generacion estan definidas inline mas abajo
// para evitar conflictos y tener control completo

// Variables globales
$allFiles = [];
$allActivities = [];
$allSections = [];
$allQuestions = [];
$generationTime = time();

// IDs
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

/**
 * Genera XMLs comunes para cualquier actividad
 */
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

/**
 * Actualiza inforef.xml con referencias a archivos
 */
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
 * Genera los XMLs para un label de presentacion del curso CON imagen
 */
function generateIntroLabelActivity($activity, $activityDir, $time) {
    global $allFiles, $nextFileId, $generationTime;

    $moduleId = $activity['moduleid'];
    $instanceId = $activity['instanceid'];
    $contextId = $activity['contextid'];
    $sectionId = $activity['sectionid'];
    $sectionNum = $activity['sectionnumber'];
    $name = escapeXml($activity['name']);

    // Copiar imagen del curso
    $imagePath = CONTENT_DIR . '/huellas invisibles.png';
    $imageUrl = '';
    $fileId = null;

    if (file_exists($imagePath)) {
        $content = file_get_contents($imagePath);
        $hash = sha1($content);
        $filename = 'huellas_invisibles.png';
        $filesize = filesize($imagePath);

        $hashDir = OUTPUT_DIR . '/files/' . substr($hash, 0, 2);
        if (!is_dir($hashDir)) {
            mkdir($hashDir, 0755, true);
        }
        $destPath = $hashDir . '/' . $hash;
        if (!file_exists($destPath)) {
            copy($imagePath, $destPath);
        }

        $fileId = $nextFileId++;
        $allFiles[] = [
            'id' => $fileId,
            'contenthash' => $hash,
            'contextid' => $contextId,
            'component' => 'mod_label',
            'filearea' => 'intro',
            'itemid' => 0,
            'filepath' => '/',
            'filename' => $filename,
            'userid' => 2,
            'filesize' => $filesize,
            'mimetype' => 'image/png',
            'status' => 0,
            'timecreated' => $generationTime,
            'timemodified' => $generationTime,
            'source' => $filename,
            'author' => 'Huellas Invisibles',
            'license' => 'allrightsreserved',
            'sortorder' => 0
        ];

        $imageUrl = '@@PLUGINFILE@@/' . $filename;
    }

    $content = <<<HTML
<div style="max-width: 900px; margin: 0 auto; font-family: 'Segoe UI', Roboto, sans-serif;">
    <!-- Banner con imagen -->
    <div style="position: relative; border-radius: 20px; overflow: hidden; box-shadow: 0 10px 40px rgba(0,0,0,0.2); margin-bottom: 30px;">
        <img src="{$imageUrl}" alt="Huellas Invisibles" style="width: 100%; height: auto; display: block;" />
    </div>

    <!-- Contenido principal -->
    <div style="background: linear-gradient(135deg, #0170B9 0%, #1a5a8a 100%); border-radius: 20px; padding: 40px; color: white; box-shadow: 0 8px 32px rgba(1,112,185,0.3);">
        <h1 style="font-size: 2.2em; margin: 0 0 10px 0; font-weight: 700; letter-spacing: -0.5px;">
            Huellas Invisibles
        </h1>
        <h2 style="font-size: 1.3em; font-weight: 300; margin: 0 0 25px 0; opacity: 0.9;">
            Neurociencia del Desarrollo en la Primera Infancia
        </h2>

        <p style="font-size: 1.05em; line-height: 1.8; margin: 0 0 30px 0; opacity: 0.95;">
            Bienvenido a este viaje por el fascinante mundo del neurodesarrollo infantil.
            Exploraremos como se construye el cerebro en los primeros anos de vida,
            la plasticidad neuronal, las emociones, el apego y el papel del medio acuatico
            en la estimulacion temprana.
        </p>

        <!-- Stats cards -->
        <div style="display: flex; justify-content: center; gap: 20px; flex-wrap: wrap;">
            <div style="background: rgba(255,255,255,0.15); backdrop-filter: blur(10px); padding: 20px 30px; border-radius: 15px; text-align: center; min-width: 100px;">
                <div style="font-size: 2.5em; font-weight: 700; line-height: 1;">10</div>
                <div style="font-size: 0.85em; opacity: 0.8; margin-top: 5px;">Capitulos</div>
            </div>
            <div style="background: rgba(255,255,255,0.15); backdrop-filter: blur(10px); padding: 20px 30px; border-radius: 15px; text-align: center; min-width: 100px;">
                <div style="font-size: 2.5em; font-weight: 700; line-height: 1;">100+</div>
                <div style="font-size: 0.85em; opacity: 0.8; margin-top: 5px;">Preguntas</div>
            </div>
            <div style="background: rgba(255,255,255,0.15); backdrop-filter: blur(10px); padding: 20px 30px; border-radius: 15px; text-align: center; min-width: 100px;">
                <div style="font-size: 2.5em; font-weight: 700; line-height: 1;">190+</div>
                <div style="font-size: 0.85em; opacity: 0.8; margin-top: 5px;">Terminos</div>
            </div>
        </div>
    </div>
</div>
HTML;
    $contentEsc = escapeXml($content);

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
  <showdescription>0</showdescription>
  <downloadcontent>1</downloadcontent>
  <lang></lang>
  <tags></tags>
</module>
XML;
    file_put_contents($activityDir . '/module.xml', $moduleXml);

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

    // Actualizar inforef con archivo de imagen
    if ($fileId) {
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

    generateActivityCommonXMLs($activityDir);
}

/**
 * Genera un label de introduccion para un capitulo
 */
function generateChapterIntroLabel($activity, $activityDir, $time, $chapterNum, $chapterTitle, $chapterIntro) {
    global $allFiles, $nextFileId, $generationTime;

    $moduleId = $activity['moduleid'];
    $instanceId = $activity['instanceid'];
    $contextId = $activity['contextid'];
    $sectionId = $activity['sectionid'];
    $sectionNum = $activity['sectionnumber'];
    $name = escapeXml($activity['name']);

    // Copiar imagen del curso para el capitulo
    $imagePath = CONTENT_DIR . '/huellas invisibles.png';
    $imageUrl = '';
    $fileId = null;

    if (file_exists($imagePath)) {
        $content = file_get_contents($imagePath);
        $hash = sha1($content);
        $filename = 'huellas_cap' . $chapterNum . '.png';
        $filesize = filesize($imagePath);

        $hashDir = OUTPUT_DIR . '/files/' . substr($hash, 0, 2);
        if (!is_dir($hashDir)) {
            mkdir($hashDir, 0755, true);
        }
        $destPath = $hashDir . '/' . $hash;
        if (!file_exists($destPath)) {
            copy($imagePath, $destPath);
        }

        $fileId = $nextFileId++;
        $allFiles[] = [
            'id' => $fileId,
            'contenthash' => $hash,
            'contextid' => $contextId,
            'component' => 'mod_label',
            'filearea' => 'intro',
            'itemid' => 0,
            'filepath' => '/',
            'filename' => $filename,
            'userid' => 2,
            'filesize' => $filesize,
            'mimetype' => 'image/png',
            'status' => 0,
            'timecreated' => $generationTime,
            'timemodified' => $generationTime,
            'source' => $filename,
            'author' => 'Huellas Invisibles',
            'license' => 'allrightsreserved',
            'sortorder' => 0
        ];

        $imageUrl = '@@PLUGINFILE@@/' . $filename;
    }

    $chapterTitleEsc = escapeXml($chapterTitle);
    $chapterIntroEsc = escapeXml($chapterIntro);

    $content = <<<HTML
<div style="max-width: 900px; margin: 0 auto 30px auto; font-family: 'Segoe UI', Roboto, sans-serif;">
    <div style="background: linear-gradient(135deg, #0170B9 0%, #3a3a3a 100%); border-radius: 20px; overflow: hidden; box-shadow: 0 8px 32px rgba(0,0,0,0.15);">
        <!-- Header con numero de capitulo -->
        <div style="display: flex; align-items: center; gap: 20px; padding: 25px 30px; border-bottom: 1px solid rgba(255,255,255,0.1);">
            <div style="background: rgba(255,255,255,0.2); width: 60px; height: 60px; border-radius: 50%; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                <span style="color: white; font-size: 1.8em; font-weight: 700;">{$chapterNum}</span>
            </div>
            <div style="flex: 1;">
                <div style="color: rgba(255,255,255,0.7); font-size: 0.85em; text-transform: uppercase; letter-spacing: 1px;">Capitulo {$chapterNum}</div>
                <h2 style="color: white; margin: 5px 0 0 0; font-size: 1.5em; font-weight: 600;">{$chapterTitleEsc}</h2>
            </div>
        </div>

        <!-- Contenido con imagen -->
        <div style="display: flex; flex-wrap: wrap;">
            <div style="flex: 1; min-width: 300px; padding: 30px;">
                <p style="color: rgba(255,255,255,0.9); font-size: 1.05em; line-height: 1.8; margin: 0;">
                    {$chapterIntroEsc}
                </p>
            </div>
            <div style="flex: 0 0 250px; padding: 20px;">
                <img src="{$imageUrl}" alt="Capitulo {$chapterNum}" style="width: 100%; border-radius: 12px; box-shadow: 0 4px 20px rgba(0,0,0,0.3);" />
            </div>
        </div>
    </div>
</div>
HTML;
    $contentEsc = escapeXml($content);

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
  <showdescription>0</showdescription>
  <downloadcontent>1</downloadcontent>
  <lang></lang>
  <tags></tags>
</module>
XML;
    file_put_contents($activityDir . '/module.xml', $moduleXml);

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

    // Actualizar inforef con archivo de imagen
    if ($fileId) {
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

    generateActivityCommonXMLs($activityDir);
}

/**
 * Genera los XMLs para una actividad mod_feedback (encuesta)
 */
function generateFeedbackActivity($activity, $activityDir, $time) {
    $moduleId = $activity['moduleid'];
    $instanceId = $activity['instanceid'];
    $contextId = $activity['contextid'];
    $sectionId = $activity['sectionid'];
    $sectionNum = $activity['sectionnumber'];
    $name = escapeXml($activity['name']);

    $moduleXml = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<module id="{$moduleId}" version="2024042200">
  <modulename>feedback</modulename>
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
  <completion>2</completion>
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

    $feedbackItems = getFeedbackItems($time);
    $itemsXml = '';
    foreach ($feedbackItems as $item) {
        $itemsXml .= $item['xml'];
    }

    $intro = escapeXml('<p>Por favor, complete esta breve encuesta para ayudarnos a mejorar el curso. Sus respuestas son anonimas.</p>');

    $feedbackXml = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<activity id="{$instanceId}" moduleid="{$moduleId}" modulename="feedback" contextid="{$contextId}">
  <feedback id="{$instanceId}">
    <name>{$name}</name>
    <intro>{$intro}</intro>
    <introformat>1</introformat>
    <anonymous>1</anonymous>
    <email_notification>0</email_notification>
    <multiple_submit>0</multiple_submit>
    <autonumbering>1</autonumbering>
    <site_after_submit></site_after_submit>
    <page_after_submit></page_after_submit>
    <page_after_submitformat>1</page_after_submitformat>
    <publish_stats>0</publish_stats>
    <timeopen>0</timeopen>
    <timeclose>0</timeclose>
    <timemodified>{$time}</timemodified>
    <completionsubmit>1</completionsubmit>
    <items>
{$itemsXml}    </items>
    <completeds></completeds>
  </feedback>
</activity>
XML;
    file_put_contents($activityDir . '/feedback.xml', $feedbackXml);

    generateActivityCommonXMLs($activityDir);
}

/**
 * Genera los items de la encuesta de satisfaccion
 */
function getFeedbackItems($time) {
    $items = [];
    $itemId = 1;

    $items[] = [
        'id' => $itemId,
        'xml' => <<<ITEM
      <item id="{$itemId}">
        <template>0</template>
        <name>satisfaccion_general</name>
        <label>En general, que tan satisfecho/a esta con el curso?</label>
        <presentation>r>>>>>1|Muy insatisfecho/a####2|Insatisfecho/a####3|Neutral####4|Satisfecho/a####5|Muy satisfecho/a</presentation>
        <typ>multichoicerated</typ>
        <hasvalue>1</hasvalue>
        <position>{$itemId}</position>
        <required>1</required>
        <dependitem>0</dependitem>
        <dependvalue></dependvalue>
        <options></options>
      </item>

ITEM
    ];
    $itemId++;

    $items[] = [
        'id' => $itemId,
        'xml' => <<<ITEM
      <item id="{$itemId}">
        <template>0</template>
        <name>calidad_contenido</name>
        <label>La calidad del contenido del curso fue:</label>
        <presentation>r>>>>>1|Muy baja####2|Baja####3|Aceptable####4|Alta####5|Muy alta</presentation>
        <typ>multichoicerated</typ>
        <hasvalue>1</hasvalue>
        <position>{$itemId}</position>
        <required>1</required>
        <dependitem>0</dependitem>
        <dependvalue></dependvalue>
        <options></options>
      </item>

ITEM
    ];
    $itemId++;

    $items[] = [
        'id' => $itemId,
        'xml' => <<<ITEM
      <item id="{$itemId}">
        <template>0</template>
        <name>recomendacion</name>
        <label>Recomendaria este curso a otros profesionales:</label>
        <presentation>r>>>>>1|Definitivamente no####2|Probablemente no####3|No estoy seguro/a####4|Probablemente si####5|Definitivamente si</presentation>
        <typ>multichoicerated</typ>
        <hasvalue>1</hasvalue>
        <position>{$itemId}</position>
        <required>1</required>
        <dependitem>0</dependitem>
        <dependvalue></dependvalue>
        <options></options>
      </item>

ITEM
    ];
    $itemId++;

    $items[] = [
        'id' => $itemId,
        'xml' => <<<ITEM
      <item id="{$itemId}">
        <template>0</template>
        <name>comentarios</name>
        <label>Comentarios o sugerencias para mejorar el curso:</label>
        <presentation>60|5</presentation>
        <typ>textarea</typ>
        <hasvalue>1</hasvalue>
        <position>{$itemId}</position>
        <required>0</required>
        <dependitem>0</dependitem>
        <dependvalue></dependvalue>
        <options></options>
      </item>

ITEM
    ];

    return $items;
}

/**
 * Genera los XMLs para una actividad mod_customcert (certificado)
 */
function generateCustomcertActivity($activity, $activityDir, $time) {
    $moduleId = $activity['moduleid'];
    $instanceId = $activity['instanceid'];
    $contextId = $activity['contextid'];
    $sectionId = $activity['sectionid'];
    $sectionNum = $activity['sectionnumber'];
    $name = escapeXml($activity['name']);

    $moduleXml = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<module id="{$moduleId}" version="2024042200">
  <modulename>customcert</modulename>
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
  <completion>2</completion>
  <completiongradeitemnumber>\$@NULL@\$</completiongradeitemnumber>
  <completionview>1</completionview>
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

    $intro = escapeXml('<p>Felicitaciones por completar el curso "Huellas Invisibles: Neurodesarrollo en la Primera Infancia". Descargue su certificado de participacion.</p>');

    $customcertXml = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<activity id="{$instanceId}" moduleid="{$moduleId}" modulename="customcert" contextid="{$contextId}">
  <customcert id="{$instanceId}">
    <name>{$name}</name>
    <intro>{$intro}</intro>
    <introformat>1</introformat>
    <requiredtime>0</requiredtime>
    <verifyany>0</verifyany>
    <deliveryoption>0</deliveryoption>
    <emailstudents>0</emailstudents>
    <emailteachers>0</emailteachers>
    <emailothers></emailothers>
    <protection></protection>
    <language></language>
    <timecreated>{$time}</timecreated>
    <timemodified>{$time}</timemodified>
    <templateid>0</templateid>
    <template>
      <pages>
      </pages>
    </template>
    <issues>
    </issues>
  </customcert>
</activity>
XML;
    file_put_contents($activityDir . '/customcert.xml', $customcertXml);

    generateActivityCommonXMLs($activityDir);
}

// ============================================================================
// CREAR ESTRUCTURA DE DIRECTORIOS
// ============================================================================

function createDirectoryStructure() {
    logMessage("Creando estructura de directorios...", '📁');

    // Limpiar y crear directorio de salida
    if (is_dir(OUTPUT_DIR)) {
        exec('rm -rf ' . escapeshellarg(OUTPUT_DIR));
    }

    $dirs = [
        OUTPUT_DIR,
        OUTPUT_DIR . '/course',
        OUTPUT_DIR . '/files',
        OUTPUT_DIR . '/sections',
        OUTPUT_DIR . '/activities'
    ];

    foreach ($dirs as $dir) {
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
    }
}

// ============================================================================
// COPIAR ARCHIVO AL BACKUP
// ============================================================================

function copyFileToBackup($sourcePath, $contextId, $component, $filearea, $itemid = 0) {
    global $allFiles, $nextFileId, $generationTime;

    if (!file_exists($sourcePath)) {
        logMessage("Archivo no encontrado: $sourcePath", '⚠️');
        return null;
    }

    $content = file_get_contents($sourcePath);
    $hash = sha1($content);
    $filename = basename($sourcePath);
    $filesize = filesize($sourcePath);

    // Crear estructura de directorios para el archivo
    $hashDir = OUTPUT_DIR . '/files/' . substr($hash, 0, 2);
    if (!is_dir($hashDir)) {
        mkdir($hashDir, 0755, true);
    }

    // Copiar archivo con nombre de hash
    $destPath = $hashDir . '/' . $hash;
    if (!file_exists($destPath)) {
        copy($sourcePath, $destPath);
    }

    $fileId = $nextFileId++;

    $allFiles[] = [
        'id' => $fileId,
        'contenthash' => $hash,
        'contextid' => $contextId,
        'component' => $component,
        'filearea' => $filearea,
        'itemid' => $itemid,
        'filepath' => '/',
        'filename' => $filename,
        'userid' => 2,
        'filesize' => $filesize,
        'mimetype' => getMimeType($filename),
        'status' => 0,
        'timecreated' => $generationTime,
        'timemodified' => $generationTime,
        'source' => $filename,
        'author' => 'Huellas Invisibles',
        'license' => 'allrightsreserved',
        'sortorder' => 0
    ];

    return $fileId;
}

// ============================================================================
// GENERAR VIDEO PLACEHOLDER (recurso de texto)
// ============================================================================

function generateVideoPlaceholderActivity($activity, $activityDir, $time, $chapterDesc = null) {
    $moduleId = $activity['moduleid'];
    $instanceId = $activity['instanceid'];
    $contextId = $activity['contextid'];
    $sectionId = $activity['sectionid'];
    $sectionNum = $activity['sectionnumber'];
    $name = escapeXml($activity['name']);

    // Usar descripcion del capitulo si esta disponible
    $intro = '';
    if ($chapterDesc && isset($chapterDesc['video_desc'])) {
        $intro = escapeXml('<p>' . $chapterDesc['video_desc'] . '</p>');
    } else {
        $intro = escapeXml('<p>Video del capitulo - Contenido multimedia pendiente de configurar.</p>');
    }

    // module.xml
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
  <completion>2</completion>
  <completiongradeitemnumber>\$@NULL@\$</completiongradeitemnumber>
  <completionview>1</completionview>
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

    // resource.xml - placeholder
    $resourceXml = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<activity id="{$instanceId}" moduleid="{$moduleId}" modulename="resource" contextid="{$contextId}">
  <resource id="{$instanceId}">
    <name>{$name}</name>
    <intro>{$intro}</intro>
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

    generateActivityCommonXMLs($activityDir);
}

// ============================================================================
// PROCESAR SECCIONES (FASES)
// ============================================================================

function processSections($maxFase) {
    global $COURSE_STRUCTURE, $GLOSSARY_DATA, $QUIZ_DATA, $CHAPTER_DESCRIPTIONS;
    global $allActivities, $allSections, $allQuestions;
    global $nextModuleId, $nextInstanceId, $nextContextId, $nextSectionId;
    global $nextQuestionId, $nextAnswerId, $nextEntryId;
    global $generationTime;

    logMessage("Procesando secciones hasta Fase {$maxFase}...", '📚');

    // Filtrar solo las secciones hasta la fase indicada
    // Fase 0 = Seccion 0 (Presentacion)
    // Fase 1 = Seccion 0 + Seccion 1 (Presentacion + Cap1)
    // etc.

    $sectionsToProcess = [];
    foreach ($COURSE_STRUCTURE as $secNum => $secData) {
        if ($secNum <= $maxFase) {
            $sectionsToProcess[$secNum] = $secData;
        }
    }

    foreach ($sectionsToProcess as $sectionNum => $sectionData) {
        $sectionId = $nextSectionId++;
        $sectionName = $sectionData['name'];
        $sectionSummary = $sectionData['summary'];
        $sectionDir = $sectionData['dir'];
        $activities = $sectionData['activities'];
        $sequence = [];

        // Obtener descripcion del capitulo si existe
        $chapterDesc = null;
        if ($sectionNum >= 1 && $sectionNum <= 10 && isset($CHAPTER_DESCRIPTIONS[$sectionNum])) {
            $chapterDesc = $CHAPTER_DESCRIPTIONS[$sectionNum];
            // Usar la introduccion del capitulo como summary de la seccion
            $sectionSummary = $chapterDesc['intro'];
        }

        logMessage("Seccion {$sectionNum}: {$sectionName}", '📁');

        // Agregar intro de capitulo al inicio de cada capitulo (secciones 1-10)
        if ($sectionNum >= 1 && $sectionNum <= 10 && $chapterDesc) {
            $moduleId = $nextModuleId++;
            $instanceId = $nextInstanceId++;
            $contextId = $nextContextId++;

            $chapterTitle = $chapterDesc['title'];
            $chapterIntro = $chapterDesc['intro'];

            $introActivity = [
                'moduleid' => $moduleId,
                'instanceid' => $instanceId,
                'contextid' => $contextId,
                'sectionid' => $sectionId,
                'sectionnumber' => $sectionNum,
                'name' => "Introduccion - Capitulo {$sectionNum}",
                'modulename' => 'label'
            ];

            $activityDir = OUTPUT_DIR . "/activities/label_{$moduleId}";
            mkdir($activityDir, 0755, true);
            generateChapterIntroLabel($introActivity, $activityDir, $generationTime, $sectionNum, $chapterTitle, $chapterIntro);

            $allActivities[] = $introActivity;
            $sequence[] = $moduleId;
            logMessage("  + Intro capitulo: {$chapterTitle}", '🎯');
        }

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

            $type = $actData['type'];

            switch ($type) {
                case 'intro_label':
                    $moduleName = 'label';
                    $activity['modulename'] = 'label';
                    $activityDir = OUTPUT_DIR . "/activities/label_{$moduleId}";
                    mkdir($activityDir, 0755, true);
                    generateIntroLabelActivity($activity, $activityDir, $generationTime);
                    logMessage("  + Presentacion: {$actData['name']}", '📋');
                    break;

                case 'resource':
                    $moduleName = 'resource';
                    $activity['modulename'] = 'resource';
                    $activityDir = OUTPUT_DIR . "/activities/resource_{$moduleId}";
                    mkdir($activityDir, 0755, true);

                    // Descripcion basada en el tipo de recurso
                    $resourceDesc = '';
                    if ($chapterDesc) {
                        if (stripos($actData['name'], 'Libro') !== false) {
                            $resourceDesc = $chapterDesc['book_desc'];
                        } elseif (stripos($actData['name'], 'Documento') !== false) {
                            $resourceDesc = $chapterDesc['doc_desc'];
                        } elseif (stripos($actData['name'], 'Bibliografia') !== false) {
                            $resourceDesc = $chapterDesc['biblio_desc'];
                        }
                    }

                    $filePath = null;
                    if ($sectionDir && isset($actData['file'])) {
                        $filePath = CONTENT_DIR . '/' . $sectionDir . '/' . $actData['file'];
                    } elseif (isset($actData['file'])) {
                        $filePath = CONTENT_DIR . '/' . $actData['file'];
                    }

                    generateResourceActivityWithDesc($activity, $activityDir, $generationTime, $filePath, $resourceDesc);

                    if ($filePath && file_exists($filePath)) {
                        $fileId = copyFileToBackup($filePath, $contextId, 'mod_resource', 'content');
                        if ($fileId) {
                            updateInforefWithFile($activityDir, $fileId);
                        }
                    }

                    logMessage("  + Resource: {$actData['name']}", '📄');
                    break;

                case 'video_placeholder':
                    $moduleName = 'resource';
                    $activity['modulename'] = 'resource';
                    $activityDir = OUTPUT_DIR . "/activities/resource_{$moduleId}";
                    mkdir($activityDir, 0755, true);
                    generateVideoPlaceholderActivity($activity, $activityDir, $generationTime, $chapterDesc);
                    logMessage("  + Video placeholder: {$actData['name']}", '🎬');
                    break;

                case 'glossary':
                    $moduleName = 'glossary';
                    $activity['modulename'] = 'glossary';
                    $activity['chapter'] = $actData['chapter'];
                    $activityDir = OUTPUT_DIR . "/activities/glossary_{$moduleId}";
                    mkdir($activityDir, 0755, true);

                    // Agregar descripcion
                    $glossaryDesc = $chapterDesc ? $chapterDesc['glossary_desc'] : null;
                    $termCount = generateGlossaryActivityWithDesc($activity, $activityDir, $generationTime, $GLOSSARY_DATA, $nextEntryId, $glossaryDesc);
                    logMessage("  + Glossary: {$actData['name']} ({$termCount} terminos)", '📖');
                    break;

                case 'quiz':
                    $moduleName = 'quiz';
                    $activity['modulename'] = 'quiz';
                    $activity['chapter'] = $actData['chapter'];
                    $activityDir = OUTPUT_DIR . "/activities/quiz_{$moduleId}";
                    mkdir($activityDir, 0755, true);

                    // Agregar descripcion
                    $quizDesc = $chapterDesc ? $chapterDesc['quiz_desc'] : null;
                    $qCount = generateQuizActivityWithDesc($activity, $activityDir, $generationTime, $QUIZ_DATA, $nextQuestionId, $nextAnswerId, $allQuestions, $quizDesc);
                    logMessage("  + Quiz: {$actData['name']} ({$qCount} preguntas)", '❓');
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

            if ($moduleName) {
                $sequence[] = $moduleId;
                $allActivities[] = [
                    'moduleid' => $moduleId,
                    'sectionid' => $sectionId,
                    'modulename' => $moduleName,
                    'name' => $actData['name']
                ];
            }
        }

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
// FUNCIONES DE GENERACION CON DESCRIPCION
// ============================================================================

function generateResourceActivityWithDesc($activity, $activityDir, $time, $filePath, $description = '') {
    $moduleId = $activity['moduleid'];
    $instanceId = $activity['instanceid'];
    $contextId = $activity['contextid'];
    $sectionId = $activity['sectionid'];
    $sectionNum = $activity['sectionnumber'];
    $name = escapeXml($activity['name']);

    $intro = $description ? escapeXml('<p>' . $description . '</p>') : '';

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
  <completion>2</completion>
  <completiongradeitemnumber>\$@NULL@\$</completiongradeitemnumber>
  <completionview>1</completionview>
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

    $resourceXml = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<activity id="{$instanceId}" moduleid="{$moduleId}" modulename="resource" contextid="{$contextId}">
  <resource id="{$instanceId}">
    <name>{$name}</name>
    <intro>{$intro}</intro>
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

    generateActivityCommonXMLs($activityDir);
}

function generateGlossaryActivityWithDesc($activity, $activityDir, $time, $glossaryData, &$nextEntryId, $description = null) {
    $moduleId = $activity['moduleid'];
    $instanceId = $activity['instanceid'];
    $contextId = $activity['contextid'];
    $sectionId = $activity['sectionid'];
    $sectionNum = $activity['sectionnumber'];
    $name = escapeXml($activity['name']);
    $chapter = $activity['chapter'];

    $chapterGlossary = isset($glossaryData[$chapter]) ? $glossaryData[$chapter] : null;

    // Usar descripcion proporcionada o la del archivo de datos
    $introText = $description ? $description : ($chapterGlossary ? $chapterGlossary['intro'] : "Glosario de terminos del Capitulo {$chapter}");
    $intro = escapeXml('<p>' . $introText . '</p>');
    $terms = $chapterGlossary ? $chapterGlossary['terms'] : [];

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
  <completion>2</completion>
  <completiongradeitemnumber>\$@NULL@\$</completiongradeitemnumber>
  <completionview>1</completionview>
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

    // Generar entries XML
    $entriesXml = '';
    foreach ($terms as $term) {
        $entryId = $nextEntryId++;
        $termEsc = escapeXml($term['term']);
        $defEsc = escapeXml('<p>' . $term['definition'] . '</p>');

        $entriesXml .= <<<ENTRY
      <entry id="{$entryId}">
        <userid>2</userid>
        <concept>{$termEsc}</concept>
        <definition>{$defEsc}</definition>
        <definitionformat>1</definitionformat>
        <definitiontrust>0</definitiontrust>
        <attachment>0</attachment>
        <timecreated>{$time}</timecreated>
        <timemodified>{$time}</timemodified>
        <teacherentry>1</teacherentry>
        <sourceglossaryid>0</sourceglossaryid>
        <usedynalink>1</usedynalink>
        <casesensitive>0</casesensitive>
        <fullmatch>0</fullmatch>
        <approved>1</approved>
        <aliases>
        </aliases>
        <ratings>
        </ratings>
      </entry>

ENTRY;
    }

    $glossaryXml = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<activity id="{$instanceId}" moduleid="{$moduleId}" modulename="glossary" contextid="{$contextId}">
  <glossary id="{$instanceId}">
    <name>{$name}</name>
    <intro>{$intro}</intro>
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
    <entries>
{$entriesXml}
    </entries>
    <categories>
    </categories>
  </glossary>
</activity>
XML;
    file_put_contents($activityDir . '/glossary.xml', $glossaryXml);

    generateActivityCommonXMLs($activityDir);

    return count($terms);
}

function generateQuizActivityWithDesc($activity, $activityDir, $time, $quizData, &$nextQuestionId, &$nextAnswerId, &$allQuestions, $description = null) {
    $moduleId = $activity['moduleid'];
    $instanceId = $activity['instanceid'];
    $contextId = $activity['contextid'];
    $sectionId = $activity['sectionid'];
    $sectionNum = $activity['sectionnumber'];
    $name = escapeXml($activity['name']);
    $chapter = $activity['chapter'];

    $chapterQuiz = isset($quizData[$chapter]) ? $quizData[$chapter] : null;

    // Usar descripcion proporcionada o la del archivo de datos
    $introText = $description ? $description : ($chapterQuiz ? $chapterQuiz['intro'] : "Test de autoevaluacion del Capitulo {$chapter}");
    $intro = escapeXml('<p>' . $introText . '</p>');
    $questions = $chapterQuiz ? $chapterQuiz['questions'] : [];

    $categoryId = 1000 + $chapter;
    $categoryName = "Preguntas Capitulo {$chapter}";

    $moduleXml = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<module id="{$moduleId}" version="2024042200">
  <modulename>quiz</modulename>
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
  <completion>2</completion>
  <completiongradeitemnumber>0</completiongradeitemnumber>
  <completionview>0</completionview>
  <completionexpected>0</completionexpected>
  <completionpassgrade>1</completionpassgrade>
  <availability>\$@NULL@\$</availability>
  <showdescription>1</showdescription>
  <downloadcontent>1</downloadcontent>
  <lang></lang>
  <tags></tags>
</module>
XML;
    file_put_contents($activityDir . '/module.xml', $moduleXml);

    // Generar preguntas
    $questionsForCategory = [];
    $questionSlots = '';
    $slot = 1;

    foreach ($questions as $question) {
        $questionId = $nextQuestionId++;
        $stamp = 'localhost+' . $time . '+' . bin2hex(random_bytes(8));
        $versionStamp = 'localhost+' . $time . '+' . bin2hex(random_bytes(8));

        $questionText = escapeXml('<p>' . $question['text'] . '</p>');
        $questionName = escapeXml(mb_substr($question['text'], 0, 50) . '...');

        $answersXml = '';
        foreach ($question['answers'] as $answer) {
            $answerId = $nextAnswerId++;
            $answerText = escapeXml('<p>' . $answer['text'] . '</p>');
            $fraction = $answer['correct'] ? '1.0000000' : '0.0000000';
            $feedback = $answer['correct'] ? 'Respuesta correcta' : 'Respuesta incorrecta';

            $answersXml .= <<<ANSWER
            <answer id="{$answerId}">
              <answertext>{$answerText}</answertext>
              <answerformat>1</answerformat>
              <fraction>{$fraction}</fraction>
              <feedback>{$feedback}</feedback>
              <feedbackformat>1</feedbackformat>
            </answer>
ANSWER;
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

        $questionsForCategory[] = $questionXml;

        $questionSlots .= <<<SLOT
      <question_instance id="{$slot}">
        <slot>{$slot}</slot>
        <page>1</page>
        <displaynumber>\$@NULL@\$</displaynumber>
        <requireprevious>0</requireprevious>
        <maxmark>1.0000000</maxmark>
        <question_reference>
          <usingcontextid>{$contextId}</usingcontextid>
          <component>mod_quiz</component>
          <questionarea>slot</questionarea>
          <questionbankentryid>{$questionId}</questionbankentryid>
          <version>\$@NULL@\$</version>
        </question_reference>
      </question_instance>
SLOT;
        $slot++;
    }

    $allQuestions[] = [
        'categoryid' => $categoryId,
        'categoryname' => $categoryName,
        'contextid' => $contextId,
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

    // grades.xml
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

    // Otros XMLs
    file_put_contents($activityDir . '/roles.xml', '<?xml version="1.0" encoding="UTF-8"?>' . "\n" . '<roles><role_overrides></role_overrides><role_assignments></role_assignments></roles>');
    file_put_contents($activityDir . '/filters.xml', '<?xml version="1.0" encoding="UTF-8"?>' . "\n" . '<filters><filter_actives></filter_actives><filter_configs></filter_configs></filters>');
    file_put_contents($activityDir . '/comments.xml', '<?xml version="1.0" encoding="UTF-8"?><comments></comments>');
    file_put_contents($activityDir . '/calendar.xml', '<?xml version="1.0" encoding="UTF-8"?><events></events>');
    file_put_contents($activityDir . '/competencies.xml', '<?xml version="1.0" encoding="UTF-8"?><course_module_competencies></course_module_competencies>');
    file_put_contents($activityDir . '/inforef.xml', '<?xml version="1.0" encoding="UTF-8"?><inforef><fileref></fileref><question_categoryref></question_categoryref></inforef>');

    return count($questions);
}

// ============================================================================
// GENERAR XMLs GLOBALES
// ============================================================================

function generateGlobalXMLs() {
    global $allSections, $allActivities, $allFiles, $allQuestions, $generationTime;

    logMessage("Generando XMLs globales...", '📄');

    // moodle_backup.xml
    generateMoodleBackupXML();

    // course/course.xml
    generateCourseXML();

    // sections
    foreach ($allSections as $section) {
        generateSectionXML($section);
    }

    // files.xml
    generateFilesXML();

    // questions.xml
    generateQuestionsXMLFile();

    // Otros XMLs globales
    generateScalesXML();
    generateOutcomesXML();
    generateGradeHistoryXML();
    generateGroupsXML();
    generateRolesXML();
    generateUsersXML();
}

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

    // Settings
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
        <value>1</value>
      </setting>
SETTING;
    }

    $activitySettings = '';
    foreach ($allActivities as $act) {
        $activitySettings .= <<<SETTING
      <setting>
        <level>activity</level>
        <activity>{$act['modulename']}_{$act['moduleid']}</activity>
        <name>{$act['modulename']}_{$act['moduleid']}_included</name>
        <value>1</value>
      </setting>
      <setting>
        <level>activity</level>
        <activity>{$act['modulename']}_{$act['moduleid']}</activity>
        <name>{$act['modulename']}_{$act['moduleid']}_userinfo</name>
        <value>1</value>
      </setting>
SETTING;
    }

    $xml = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<moodle_backup>
  <information>
    <name>backup-huellas-invisibles-incremental.mbz</name>
    <moodle_version>2024042200</moodle_version>
    <moodle_release>4.4</moodle_release>
    <backup_version>2024042200</backup_version>
    <backup_release>4.4</backup_release>
    <backup_date>{$generationTime}</backup_date>
    <mnet_remoteusers>0</mnet_remoteusers>
    <include_files>1</include_files>
    <include_file_references_to_external_content>0</include_file_references_to_external_content>
    <original_wwwroot>https://campus.example.com</original_wwwroot>
    <original_site_identifier_hash>mbzgen_incremental</original_site_identifier_hash>
    <original_course_id>2</original_course_id>
    <original_course_format>topics</original_course_format>
    <original_course_fullname>Huellas Invisibles: Neurociencia del Desarrollo Infantil</original_course_fullname>
    <original_course_shortname>huellas-invisibles</original_course_shortname>
    <original_course_startdate>{$generationTime}</original_course_startdate>
    <original_course_enddate>0</original_course_enddate>
    <original_course_contextid>50</original_course_contextid>
    <original_system_contextid>1</original_system_contextid>
    <details>
      <detail backup_id="mbzgen_incremental">
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
        <title>Huellas Invisibles: Neurociencia del Desarrollo Infantil</title>
        <directory>course</directory>
      </course>
    </contents>
    <settings>
      <setting>
        <level>root</level>
        <name>filename</name>
        <value>backup-huellas-invisibles-incremental.mbz</value>
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
  <shortname>huellas-invisibles</shortname>
  <fullname>Huellas Invisibles: Neurociencia del Desarrollo Infantil</fullname>
  <idnumber></idnumber>
  <summary>&lt;p&gt;Curso completo sobre neurodesarrollo y neuroaprendizaje acuatico en la primera infancia. Explora los fundamentos de la neurociencia aplicada al desarrollo infantil a traves de 10 capitulos tematicos.&lt;/p&gt;</summary>
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
    <name>Cursos</name>
    <description></description>
  </category>
  <tags></tags>
  <customfields></customfields>
  <courseformatoptions></courseformatoptions>
</course>
XML;
    file_put_contents(OUTPUT_DIR . '/course/course.xml', $xml);

    // Otros archivos del curso
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
    $summary = escapeXml('<p>' . $section['summary'] . '</p>');
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

function generateQuestionsXMLFile() {
    global $allQuestions, $generationTime;

    if (empty($allQuestions)) {
        file_put_contents(OUTPUT_DIR . '/questions.xml', '<?xml version="1.0" encoding="UTF-8"?><question_categories></question_categories>');
        return;
    }

    $categoriesXml = '';
    foreach ($allQuestions as $catData) {
        $catId = $catData['categoryid'];
        $catName = escapeXml($catData['categoryname']);
        $catContextId = $catData['contextid'];
        $questionsXml = implode("\n", $catData['questions']);
        $stamp = 'localhost+' . $generationTime . '+cat' . $catId;

        $categoriesXml .= <<<CAT
  <question_category id="{$catId}">
    <name>{$catName}</name>
    <contextid>{$catContextId}</contextid>
    <contextlevel>70</contextlevel>
    <contextinstanceid>{$catId}</contextinstanceid>
    <info>Preguntas del capitulo</info>
    <infoformat>0</infoformat>
    <stamp>{$stamp}</stamp>
    <parent>0</parent>
    <sortorder>999</sortorder>
    <questions>
{$questionsXml}
    </questions>
  </question_category>
CAT;
    }

    $xml = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<question_categories>
{$categoriesXml}
</question_categories>
XML;
    file_put_contents(OUTPUT_DIR . '/questions.xml', $xml);
}

function generateScalesXML() {
    file_put_contents(OUTPUT_DIR . '/scales.xml', '<?xml version="1.0" encoding="UTF-8"?><scales_definition></scales_definition>');
}

function generateOutcomesXML() {
    file_put_contents(OUTPUT_DIR . '/outcomes.xml', '<?xml version="1.0" encoding="UTF-8"?><outcomes_definition></outcomes_definition>');
}

function generateGradeHistoryXML() {
    file_put_contents(OUTPUT_DIR . '/grade_history.xml', '<?xml version="1.0" encoding="UTF-8"?><grade_history></grade_history>');
}

function generateGroupsXML() {
    file_put_contents(OUTPUT_DIR . '/groups.xml', '<?xml version="1.0" encoding="UTF-8"?><groups><groupings></groupings></groups>');
}

function generateRolesXML() {
    file_put_contents(OUTPUT_DIR . '/roles.xml', '<?xml version="1.0" encoding="UTF-8"?><roles_definition></roles_definition>');
}

function generateUsersXML() {
    global $generationTime;

    // Usuario 2 es usado en las entradas del glosario
    $xml = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<users>
  <user id="2" contextid="1">
    <username>admin</username>
    <idnumber></idnumber>
    <email>admin@example.com</email>
    <firstname>Admin</firstname>
    <lastname>Usuario</lastname>
    <auth>manual</auth>
    <confirmed>1</confirmed>
    <policyagreed>0</policyagreed>
    <deleted>0</deleted>
    <suspended>0</suspended>
    <mnethostid>1</mnethostid>
    <password></password>
    <lang>es</lang>
    <calendartype>gregorian</calendartype>
    <theme></theme>
    <timezone>99</timezone>
    <firstaccess>{$generationTime}</firstaccess>
    <lastaccess>{$generationTime}</lastaccess>
    <lastlogin>{$generationTime}</lastlogin>
    <currentlogin>{$generationTime}</currentlogin>
    <lastip>127.0.0.1</lastip>
    <picture>0</picture>
    <description></description>
    <descriptionformat>1</descriptionformat>
    <mailformat>1</mailformat>
    <maildigest>0</maildigest>
    <maildisplay>2</maildisplay>
    <autosubscribe>1</autosubscribe>
    <trackforums>0</trackforums>
    <timecreated>{$generationTime}</timecreated>
    <timemodified>{$generationTime}</timemodified>
    <trustbitmask>0</trustbitmask>
    <imagealt></imagealt>
    <lastnamephonetic></lastnamephonetic>
    <firstnamephonetic></firstnamephonetic>
    <middlename></middlename>
    <alternatename></alternatename>
    <moodlenetprofile></moodlenetprofile>
    <custom_fields>
    </custom_fields>
    <tags>
    </tags>
    <preferences>
    </preferences>
    <roles>
    </roles>
  </user>
</users>
XML;
    file_put_contents(OUTPUT_DIR . '/users.xml', $xml);
}

// ============================================================================
// CREAR ARCHIVO MBZ
// ============================================================================

function createMBZ($maxFase) {
    global $generationTime;

    logMessage("Creando archivo MBZ...", '📦');

    $mbzFilename = "backup-huellas-fase{$maxFase}-" . date('Ymd-His', $generationTime) . ".mbz";
    $mbzPath = OUTPUT_DIR . '/' . $mbzFilename;

    $currentDir = getcwd();
    chdir(OUTPUT_DIR);
    exec('zip -r "' . $mbzFilename . '" . -x "*.mbz"', $output, $returnCode);
    chdir($currentDir);

    if ($returnCode === 0 && file_exists($mbzPath)) {
        $size = filesize($mbzPath);
        $sizeFormatted = round($size / 1024 / 1024, 2) . ' MB';
        logMessage("MBZ creado: {$mbzFilename} ({$sizeFormatted})", '✅');
        return $mbzPath;
    } else {
        logMessage("Error al crear MBZ", '❌');
        return null;
    }
}

// ============================================================================
// EJECUCION PRINCIPAL
// ============================================================================

createDirectoryStructure();
processSections($maxFase);
generateGlobalXMLs();
$mbzPath = createMBZ($maxFase);

echo "\n=======================================================\n";
echo " PROCESO COMPLETADO - FASE {$maxFase}\n";
echo "=======================================================\n";
echo " Secciones: " . count($allSections) . "\n";
echo " Actividades: " . count($allActivities) . "\n";
echo " Archivos: " . count($allFiles) . "\n";
if ($mbzPath) {
    echo " MBZ: {$mbzPath}\n";
}
echo "=======================================================\n";
