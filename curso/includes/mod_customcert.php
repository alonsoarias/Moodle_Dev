<?php
/**
 * Generador de actividades mod_customcert
 * Estructura XML compatible con Moodle 4.4
 * El template del certificado se crea vacío - debe configurarse manualmente en Moodle
 */

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

    // module.xml
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

    // customcert.xml - Estructura básica sin template embebido
    // El template debe configurarse manualmente después de restaurar
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
