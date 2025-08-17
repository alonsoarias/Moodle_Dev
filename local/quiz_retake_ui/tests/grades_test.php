<?php
// PHPUnit tests for grades class.
namespace local_quiz_retake_ui\tests;

use advanced_testcase;
use local_quiz_retake_ui\grades;
use mod_quiz\quiz_attempt;

class grades_test extends advanced_testcase {
    public function test_risk_free_grade(): void {
        $this->resetAfterTest();
        $quizgenerator = $this->getDataGenerator()->get_plugin_generator('mod_quiz');
        $course = $this->getDataGenerator()->create_course();
        $quiz = $quizgenerator->create_instance(['course' => $course->id, 'sumgrades' => 10]);
        $questiongenerator = $this->getDataGenerator()->get_plugin_generator('core_question');
        $question = $questiongenerator->create_question('truefalse', null, ['name' => 'Q1']);
        quiz_add_quiz_question($question->id, $quiz, 0);

        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);
        $attemptobj = $quizgenerator->create_attempt($quiz, $user);

        $attempt = quiz_attempt::create($attemptobj->id);
        $grade = grades::risk_free_grade($attempt, []);
        $this->assertEquals(0, $grade, 'No marks awarded initially');
    }
}
