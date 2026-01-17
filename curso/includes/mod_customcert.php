<?php
/**
 * Generador de actividades mod_customcert
 * Incluye estructura XML para certificados personalizados
 * Nota: El template del certificado debe configurarse manualmente en Moodle
 * usando la imagen "huellas invisibles.png" como fondo
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

    // customcert.xml - Estructura basica del certificado
    // Nota: Los elementos del template (imagen de fondo, textos) deben configurarse
    // manualmente en Moodle despues de restaurar el backup
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
    <templates>
      <template id="1">
        <name>Certificado Huellas Invisibles</name>
        <contextid>{$contextId}</contextid>
        <timecreated>{$time}</timecreated>
        <timemodified>{$time}</timemodified>
        <pages>
          <page id="1">
            <templateid>1</templateid>
            <width>297</width>
            <height>210</height>
            <leftmargin>0</leftmargin>
            <rightmargin>0</rightmargin>
            <sequence>1</sequence>
            <elements>
              <element id="1">
                <pageid>1</pageid>
                <name>Imagen de fondo</name>
                <element>image</element>
                <data></data>
                <font>\$@NULL@\$</font>
                <fontsize>\$@NULL@\$</fontsize>
                <colour>\$@NULL@\$</colour>
                <posx>0</posx>
                <posy>0</posy>
                <width>297</width>
                <height>210</height>
                <refpoint>0</refpoint>
                <sequence>1</sequence>
              </element>
              <element id="2">
                <pageid>1</pageid>
                <name>Nombre del estudiante</name>
                <element>studentname</element>
                <data></data>
                <font>Rubik</font>
                <fontsize>24</fontsize>
                <colour>#0170B9</colour>
                <posx>148</posx>
                <posy>85</posy>
                <width>200</width>
                <height>0</height>
                <refpoint>1</refpoint>
                <sequence>2</sequence>
              </element>
              <element id="3">
                <pageid>1</pageid>
                <name>Nombre del curso</name>
                <element>coursename</element>
                <data></data>
                <font>Rubik</font>
                <fontsize>18</fontsize>
                <colour>#3a3a3a</colour>
                <posx>148</posx>
                <posy>110</posy>
                <width>200</width>
                <height>0</height>
                <refpoint>1</refpoint>
                <sequence>3</sequence>
              </element>
              <element id="4">
                <pageid>1</pageid>
                <name>Fecha de emision</name>
                <element>date</element>
                <data>1</data>
                <font>Rubik</font>
                <fontsize>12</fontsize>
                <colour>#3a3a3a</colour>
                <posx>148</posx>
                <posy>145</posy>
                <width>100</width>
                <height>0</height>
                <refpoint>1</refpoint>
                <sequence>4</sequence>
              </element>
              <element id="5">
                <pageid>1</pageid>
                <name>Codigo de verificacion</name>
                <element>code</element>
                <data></data>
                <font>Rubik</font>
                <fontsize>8</fontsize>
                <colour>#666666</colour>
                <posx>148</posx>
                <posy>195</posy>
                <width>100</width>
                <height>0</height>
                <refpoint>1</refpoint>
                <sequence>5</sequence>
              </element>
            </elements>
          </page>
        </pages>
      </template>
    </templates>
    <issues></issues>
  </customcert>
</activity>
XML;
    file_put_contents($activityDir . '/customcert.xml', $customcertXml);

    generateActivityCommonXMLs($activityDir);
}
