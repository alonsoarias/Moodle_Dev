<?php
/**
 * Generador de actividades mod_label
 * Usado para mostrar la imagen del curso o contenido de presentacion
 */

/**
 * Genera los XMLs para un label de presentacion del curso (sin imagen)
 */
function generateIntroLabelActivity($activity, $activityDir, $time) {
    $moduleId = $activity['moduleid'];
    $instanceId = $activity['instanceid'];
    $contextId = $activity['contextid'];
    $sectionId = $activity['sectionid'];
    $sectionNum = $activity['sectionnumber'];
    $name = escapeXml($activity['name']);

    // Contenido de presentacion del curso - HTML formateado
    $content = <<<HTML
<div style="text-align: center; padding: 30px; background: linear-gradient(135deg, #0170B9 0%, #3a3a3a 100%); border-radius: 15px; color: white; margin: 20px 0;">
    <h1 style="font-family: 'Rubik', sans-serif; font-size: 2.5em; margin-bottom: 15px; text-shadow: 2px 2px 4px rgba(0,0,0,0.3);">
        Huellas Invisibles
    </h1>
    <h2 style="font-family: 'Rubik', sans-serif; font-size: 1.5em; font-weight: 300; margin-bottom: 25px;">
        Neurociencia del Desarrollo Infantil
    </h2>
    <p style="font-size: 1.1em; line-height: 1.8; max-width: 800px; margin: 0 auto 20px auto;">
        Bienvenido a este viaje por el fascinante mundo del neurodesarrollo en la primera infancia.
        A traves de 10 capitulos exploraremos como se construye el cerebro infantil,
        la plasticidad neuronal, las emociones, el apego y el papel fundamental del medio acuatico
        en la estimulacion temprana.
    </p>
    <div style="display: flex; justify-content: center; gap: 30px; flex-wrap: wrap; margin-top: 25px;">
        <div style="background: rgba(255,255,255,0.1); padding: 15px 25px; border-radius: 10px;">
            <strong style="font-size: 2em; display: block;">10</strong>
            <span style="font-size: 0.9em;">Capitulos</span>
        </div>
        <div style="background: rgba(255,255,255,0.1); padding: 15px 25px; border-radius: 10px;">
            <strong style="font-size: 2em; display: block;">100+</strong>
            <span style="font-size: 0.9em;">Preguntas</span>
        </div>
        <div style="background: rgba(255,255,255,0.1); padding: 15px 25px; border-radius: 10px;">
            <strong style="font-size: 2em; display: block;">190+</strong>
            <span style="font-size: 0.9em;">Terminos</span>
        </div>
    </div>
</div>
HTML;
    $contentEsc = escapeXml($content);

    // module.xml
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

    // label.xml
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

    generateActivityCommonXMLs($activityDir);
}

/**
 * Genera los XMLs para una actividad mod_label con imagen
 */
function generateLabelActivity($activity, $activityDir, $time) {
    $moduleId = $activity['moduleid'];
    $instanceId = $activity['instanceid'];
    $contextId = $activity['contextid'];
    $sectionId = $activity['sectionid'];
    $sectionNum = $activity['sectionnumber'];
    $name = escapeXml($activity['name']);
    $filename = $activity['filename'];
    $filenameUrl = rawurlencode($filename);

    // Contenido del label: mostrar la imagen
    $content = '<p style="text-align: center;"><img src="@@PLUGINFILE@@/' . $filenameUrl . '" alt="' . $name . '" style="max-width: 100%; border-radius: 10px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);" /></p>';
    $contentEsc = escapeXml($content);

    // module.xml
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

    // label.xml
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

    generateActivityCommonXMLs($activityDir);
}
