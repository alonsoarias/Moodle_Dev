<?php
/**
 * Generador de actividades mod_resource
 * Incluye resource normal y video_placeholder
 */

/**
 * Genera los XMLs para una actividad mod_resource
 */
function generateResourceActivity($activity, $activityDir, $time) {
    $moduleId = $activity['moduleid'];
    $instanceId = $activity['instanceid'];
    $contextId = $activity['contextid'];
    $sectionId = $activity['sectionid'];
    $sectionNum = $activity['sectionnumber'];
    $name = escapeXml($activity['name']);

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
  <showdescription>0</showdescription>
  <downloadcontent>1</downloadcontent>
  <lang></lang>
  <tags></tags>
</module>
XML;
    file_put_contents($activityDir . '/module.xml', $moduleXml);

    // resource.xml
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

    // Archivos comunes
    generateActivityCommonXMLs($activityDir);
}

/**
 * Genera los XMLs para un video_placeholder (resource con descripcion especial)
 */
function generateVideoPlaceholderActivity($activity, $activityDir, $time) {
    $moduleId = $activity['moduleid'];
    $instanceId = $activity['instanceid'];
    $contextId = $activity['contextid'];
    $sectionId = $activity['sectionid'];
    $sectionNum = $activity['sectionnumber'];
    $name = escapeXml($activity['name']);
    $videoName = isset($activity['video']) ? escapeXml($activity['video']) : 'video.mp4';

    // SVG del icono de video
    $videoIconSvg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 200 120" width="200" height="120">
      <defs>
        <linearGradient id="bgGrad" x1="0%" y1="0%" x2="100%" y2="100%">
          <stop offset="0%" style="stop-color:#1a237e;stop-opacity:1"/>
          <stop offset="100%" style="stop-color:#3949ab;stop-opacity:1"/>
        </linearGradient>
      </defs>
      <rect x="5" y="5" width="190" height="110" rx="10" fill="url(#bgGrad)"/>
      <circle cx="100" cy="60" r="35" fill="rgba(255,255,255,0.15)" stroke="white" stroke-width="3"/>
      <polygon points="90,45 90,75 120,60" fill="white"/>
    </svg>';

    // Intro HTML con placeholder visual
    $intro = '<div style="text-align: center; padding: 20px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border-radius: 10px; margin: 10px 0;">';
    $intro .= '<div style="display: inline-block;">' . $videoIconSvg . '</div>';
    $intro .= '<p style="color: white; font-size: 14px; margin-top: 15px;"><strong>Video pendiente de subir:</strong></p>';
    $intro .= '<p style="color: #e0e0e0; font-size: 12px;">' . $videoName . '</p>';
    $intro .= '<p style="color: #b0b0b0; font-size: 11px; margin-top: 10px;"><em>Edite este recurso para subir el archivo .mp4</em></p>';
    $intro .= '</div>';
    $introEsc = escapeXml($intro);

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

    // resource.xml
    $resourceXml = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<activity id="{$instanceId}" moduleid="{$moduleId}" modulename="resource" contextid="{$contextId}">
  <resource id="{$instanceId}">
    <name>{$name}</name>
    <intro>{$introEsc}</intro>
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
