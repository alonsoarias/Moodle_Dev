<?php
/**
 * Generador de actividades mod_quiz
 * Estructura XML compatible con Moodle 4.4
 * Usa formato de preguntas compatible con versiones antiguas y nuevas
 */

/**
 * Genera los XMLs para una actividad mod_quiz
 */
function generateQuizActivity($activity, $activityDir, $time, $quizData, &$nextQuestionId, &$nextAnswerId, &$allQuestions) {
    $moduleId = $activity['moduleid'];
    $instanceId = $activity['instanceid'];
    $contextId = $activity['contextid'];
    $sectionId = $activity['sectionid'];
    $sectionNum = $activity['sectionnumber'];
    $name = escapeXml($activity['name']);
    $chapter = $activity['chapter'];

    // Obtener datos del quiz para este capitulo
    $chapterQuiz = isset($quizData[$chapter]) ? $quizData[$chapter] : null;
    $introText = $chapterQuiz ? $chapterQuiz['intro'] : "Test de autoevaluacion del Capitulo {$chapter}";
    $intro = escapeXml('<p>' . $introText . '</p>');
    $questions = $chapterQuiz ? $chapterQuiz['questions'] : [];

    // Crear categoria de preguntas para este quiz
    $categoryId = 1000 + $chapter;
    $categoryName = "Preguntas Capitulo {$chapter}";
    $categoryContextId = $contextId;

    // module.xml
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

    // Generar preguntas para el banco de preguntas global
    $questionsForCategory = [];
    $questionSlots = '';
    $slot = 1;

    foreach ($questions as $qIndex => $question) {
        $questionId = $nextQuestionId++;
        $stamp = 'localhost+' . $time . '+' . bin2hex(random_bytes(8));
        $versionStamp = 'localhost+' . $time . '+' . bin2hex(random_bytes(8));

        // Generar XML de pregunta
        $questionXml = generateSimpleMultichoiceXml($question, $questionId, $categoryId, $time, $nextAnswerId, $stamp, $versionStamp);
        $questionsForCategory[] = $questionXml;

        // Slot para referenciar la pregunta
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

    // Agregar categoria y preguntas al banco global
    $allQuestions[] = [
        'categoryid' => $categoryId,
        'categoryname' => $categoryName,
        'contextid' => $categoryContextId,
        'questions' => $questionsForCategory
    ];

    // quiz.xml
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

    // grades.xml especifico para quiz
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

    // Otros XMLs comunes
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
        '<?xml version="1.0" encoding="UTF-8"?><inforef><fileref></fileref><question_categoryref></question_categoryref></inforef>');

    return count($questions);
}

/**
 * Genera XML para una pregunta multichoice (formato simple y compatible)
 */
function generateSimpleMultichoiceXml($question, $questionId, $categoryId, $time, &$nextAnswerId, $stamp, $versionStamp) {
    $questionText = escapeXml('<p>' . $question['text'] . '</p>');
    $questionName = escapeXml(substr($question['text'], 0, 50) . '...');

    // Generar respuestas
    $answersXml = '';
    $correctFraction = '1.0000000';
    $incorrectFraction = '0.0000000';

    foreach ($question['answers'] as $aIndex => $answer) {
        $answerId = $nextAnswerId++;
        $answerText = escapeXml('<p>' . $answer['text'] . '</p>');
        $fraction = $answer['correct'] ? $correctFraction : $incorrectFraction;
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

    // Formato pregunta simple Moodle (sin question_bank_entry wrapper complejo)
    $xml = <<<XML
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

    return $xml;
}

/**
 * Genera el archivo questions.xml global (formato simple compatible)
 */
function generateQuestionsXML($outputDir, $allQuestions, $time) {
    if (empty($allQuestions)) {
        file_put_contents($outputDir . '/questions.xml',
            '<?xml version="1.0" encoding="UTF-8"?><question_categories></question_categories>');
        return;
    }

    $categoriesXml = '';

    foreach ($allQuestions as $catData) {
        $catId = $catData['categoryid'];
        $catName = escapeXml($catData['categoryname']);
        $catContextId = $catData['contextid'];
        $questionsXml = implode("\n", $catData['questions']);
        $stamp = 'localhost+' . $time . '+cat' . $catId;

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

    file_put_contents($outputDir . '/questions.xml', $xml);
}
