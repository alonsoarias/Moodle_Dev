<?php
/**
 * Generador de actividades mod_glossary
 * Estructura XML compatible con Moodle 4.4
 */

/**
 * Genera los XMLs para una actividad mod_glossary
 */
function generateGlossaryActivity($activity, $activityDir, $time, $glossaryData, &$nextEntryId) {
    $moduleId = $activity['moduleid'];
    $instanceId = $activity['instanceid'];
    $contextId = $activity['contextid'];
    $sectionId = $activity['sectionid'];
    $sectionNum = $activity['sectionnumber'];
    $name = escapeXml($activity['name']);
    $chapter = $activity['chapter'];

    // Obtener datos del glosario para este capitulo
    $chapterGlossary = isset($glossaryData[$chapter]) ? $glossaryData[$chapter] : null;
    $introText = $chapterGlossary ? $chapterGlossary['intro'] : "Glosario de terminos del Capitulo {$chapter}";
    // Usar texto plano escapado para evitar problemas de parsing
    $intro = escapeXml($introText);
    $terms = $chapterGlossary ? $chapterGlossary['terms'] : [];

    // module.xml
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
        // Usar texto plano para evitar problemas de parsing XML
        $defEsc = escapeXml($term['definition']);

        $entriesXml .= <<<ENTRY
      <entry id="{$entryId}">
        <userid>2</userid>
        <concept>{$termEsc}</concept>
        <definition>{$defEsc}</definition>
        <definitionformat>0</definitionformat>
        <definitiontrust>0</definitiontrust>
        <attachment></attachment>
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
        <tags>
        </tags>
      </entry>
ENTRY;
    }

    // glossary.xml - Estructura exacta Moodle 4.4 (campos en orden correcto, sin extras)
    $glossaryXml = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<activity id="{$instanceId}" moduleid="{$moduleId}" modulename="glossary" contextid="{$contextId}">
  <glossary id="{$instanceId}">
    <name>{$name}</name>
    <intro>{$intro}</intro>
    <introformat>0</introformat>
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
