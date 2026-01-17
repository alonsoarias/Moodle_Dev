<?php
/**
 * Generador de actividades mod_feedback
 * Incluye estructura XML para encuestas de satisfaccion
 */

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

    // module.xml
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

    // Preguntas de la encuesta de satisfaccion
    $feedbackItems = getFeedbackItems($time);
    $itemsXml = '';
    foreach ($feedbackItems as $item) {
        $itemsXml .= $item['xml'];
    }

    // feedback.xml
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

    // Pregunta 1: Satisfaccion general (escala 1-5)
    $items[] = [
        'id' => $itemId,
        'xml' => <<<ITEM
      <item id="{$itemId}">
        <template>0</template>
        <name>satisfaccion_general</name>
        <label></label>
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

    // Pregunta 2: Calidad del contenido
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

    // Pregunta 3: Claridad de explicaciones
    $items[] = [
        'id' => $itemId,
        'xml' => <<<ITEM
      <item id="{$itemId}">
        <template>0</template>
        <name>claridad_explicaciones</name>
        <label>Las explicaciones fueron claras y comprensibles:</label>
        <presentation>r>>>>>1|Totalmente en desacuerdo####2|En desacuerdo####3|Neutral####4|De acuerdo####5|Totalmente de acuerdo</presentation>
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

    // Pregunta 4: Utilidad practica
    $items[] = [
        'id' => $itemId,
        'xml' => <<<ITEM
      <item id="{$itemId}">
        <template>0</template>
        <name>utilidad_practica</name>
        <label>Los conocimientos adquiridos son aplicables a mi practica profesional:</label>
        <presentation>r>>>>>1|Totalmente en desacuerdo####2|En desacuerdo####3|Neutral####4|De acuerdo####5|Totalmente de acuerdo</presentation>
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

    // Pregunta 5: Organizacion del curso
    $items[] = [
        'id' => $itemId,
        'xml' => <<<ITEM
      <item id="{$itemId}">
        <template>0</template>
        <name>organizacion_curso</name>
        <label>La organizacion y estructura del curso fue:</label>
        <presentation>r>>>>>1|Muy deficiente####2|Deficiente####3|Aceptable####4|Buena####5|Excelente</presentation>
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

    // Pregunta 6: Recomendaria el curso
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

    // Pregunta 7: Comentarios abiertos
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
