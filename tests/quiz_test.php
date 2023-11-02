<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

namespace local_raisecli;

use local_raisecli\external\quiz;
use externallib_advanced_testcase;

defined('MOODLE_INTERNAL') || die();

global $CFG;

require_once($CFG->dirroot . '/webservice/tests/helpers.php');

/**
 * RAISE CLI Web Service tests
 *
 * @package     local_raisecli
 * @copyright   2023 OpenStax
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class quiz_test extends externallib_advanced_testcase {

    /**
     * Test set up.
     *
     * This is executed before running any test in this file.
     */
    public function setUp(): void {
        $this->resetAfterTest();
    }

    /**
     * Setup a course, a quiz, a question category and questions for testing.
     *
     * @return array The created data objects
     */
    public function setup_quiz_and_questions() {
        $category = $this->getDataGenerator()->create_category();
        $course = $this->getDataGenerator()->create_course([
            'category' => $category->id,
        ]);
        $quiz = $this->getDataGenerator()->create_module('quiz', ['course' => $course->id, 'sumgrades' => 2]);
        $qgen = $this->getDataGenerator()->get_plugin_generator('core_question');
        $context = \context_module::instance($quiz->cmid);
        $qcat = $qgen->create_question_category(['contextid' => $context->id]);

        $q1data = [
            'name' => 'MC question 1',
            'idnumber' => '4d26bafe-423a-4015-864c-71269243f80d',
            'category' => $qcat->id,
            'fraction' => ['1.0', '0.0', '0.0', '0.0'],
            'single' => '1',
            'answer' => [
                0 => [
                    'text' => '<p>Red</p>',
                    'format' => FORMAT_HTML,
                ],
                1 => [
                    'text' => '<p>Blue</p>',
                    'format' => FORMAT_HTML,
                ],
                2 => [
                    'text' => '<p>Green</p>',
                    'format' => FORMAT_HTML,
                ],
                3 => [
                    'text' => '<p>Orange</p>',
                    'format' => FORMAT_HTML,
                ],
            ],
        ];

        $q2data = [
            'name' => 'MC question 2',
            'category' => $qcat->id,
            'fraction' => ['0.5', '0.5', '0.0', '0.0'],
            'single' => '0',
            'answer' => [
                0 => [
                    'text' => '<p>1</p>',
                    'format' => FORMAT_HTML,
                ],
                1 => [
                    'text' => '<p>2</p>',
                    'format' => FORMAT_HTML,
                ],
                2 => [
                    'text' => '<p>3</p>',
                    'format' => FORMAT_HTML,
                ],
                3 => [
                    'text' => '<p>4</p>',
                    'format' => FORMAT_HTML,
                ],
            ],
        ];

        $q1 = $qgen->create_question('multichoice', null, $q1data);
        $q2 = $qgen->create_question('multichoice', null, $q2data);
        quiz_add_quiz_question($q1->id, $quiz);
        quiz_add_quiz_question($q2->id, $quiz);

        return [$category, $course, $quiz, $qcat, [$q1, $q2]];
    }

    /**
     * Test local_raisecli_get_quiz_attempt when user skips all questions
     *
     * @covers ::get_quiz_attempt
     */
    public function test_local_raisecli_get_quiz_attempt_noresponses() {
        global $DB;

        list($category, $course, $quiz, $qcat, $questions) = $this->setup_quiz_and_questions();
        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);
        $studentrole = $DB->get_record('role', ['shortname' => 'student']);
        $this->getDataGenerator()->enrol_user($user->id, $course->id, $studentrole->id, 'manual');

        $quizobj = \quiz::create($quiz->id, $user->id);
        $quba = \question_engine::make_questions_usage_by_activity('mod_quiz', $quizobj->get_context());
        $quba->set_preferred_behaviour($quizobj->get_quiz()->preferredbehaviour);

        $timenow = time();
        $attempt = quiz_create_attempt($quizobj, 1, false, $timenow);
        quiz_start_new_attempt($quizobj, $quba, $attempt, 1, $timenow);
        quiz_attempt_save_started($quizobj, $quba, $attempt);
        $attemptobj = \quiz_attempt::create($attempt->id);
        $attemptobj->process_finish($timenow, false);

        // Set required capabilities for user.
        $this->assignUserCapability('mod/quiz:viewreports', $quba->get_owning_context());

        $result = quiz::get_quiz_attempt($attempt->id);
        $result = \external_api::clean_returnvalue(quiz::get_quiz_attempt_returns(), $result);

        $this->assertEquals(
            $result['attempt'],
            [
                'id' => $attemptobj->get_attemptid(),
                'quiz' => $attemptobj->get_quizid(),
                'userid' => $attemptobj->get_userid(),
                'attempt' => 1,
                'uniqueid' => $attemptobj->get_uniqueid(),
                'state' => 'finished',
                'timestart' => $timenow,
                'timefinish' => $timenow,
                'timemodified' => $timenow,
                'sumgrades' => $attemptobj->get_sum_marks(),
                'gradednotificationsenttime' => $timenow,
            ]
        );
        $this->assertEquals(count($result['questions']), 2);
        $this->assertTrue(in_array(
            [
                'slot' => 1,
                'type' => 'multichoice',
                'idnumber' => $questions[0]->idnumber,
                'answer' => [],
            ],
            $result['questions']
        ));
        $this->assertTrue(in_array(
            [
                'slot' => 2,
                'type' => 'multichoice',
                'idnumber' => $questions[1]->idnumber,
                'answer' => [],
            ],
            $result['questions']
        ));
    }

    /**
     * Test local_raisecli_get_quiz_attempt when user attempts all questions
     *
     * @covers ::get_quiz_attempt
     */
    public function test_local_raisecli_get_quiz_attempt_withresponses() {
        global $DB;

        list($category, $course, $quiz, $qcat, $questions) = $this->setup_quiz_and_questions();
        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);
        $studentrole = $DB->get_record('role', ['shortname' => 'student']);
        $this->getDataGenerator()->enrol_user($user->id, $course->id, $studentrole->id, 'manual');

        // Start the attempt.
        $quizobj = \quiz::create($quiz->id, $user->id);
        $quba = \question_engine::make_questions_usage_by_activity('mod_quiz', $quizobj->get_context());
        $quba->set_preferred_behaviour($quizobj->get_quiz()->preferredbehaviour);

        $timenow = time();
        $attempt = quiz_create_attempt($quizobj, 1, false, $timenow);
        quiz_start_new_attempt($quizobj, $quba, $attempt, 1, $timenow);
        quiz_attempt_save_started($quizobj, $quba, $attempt);
        $attemptobj = \quiz_attempt::create($attempt->id);

        $attemptobj->process_submitted_actions(
            $timenow,
            false,
            [
                '1' => ['answer' => 'Green'],
                '2' => ['1' => '0', '2' => '1', '3' => '0', '4' => '1'],
            ]
        );

        $attemptobj->process_finish($timenow, false);

        // Set required capabilities for user.
        $this->assignUserCapability('mod/quiz:viewreports', $quba->get_owning_context());

        $result = quiz::get_quiz_attempt($attempt->id);
        $result = \external_api::clean_returnvalue(quiz::get_quiz_attempt_returns(), $result);
        $this->assertEquals(
            $result['attempt'],
            [
                'id' => $attemptobj->get_attemptid(),
                'quiz' => $attemptobj->get_quizid(),
                'userid' => $attemptobj->get_userid(),
                'attempt' => 1,
                'uniqueid' => $attemptobj->get_uniqueid(),
                'state' => 'finished',
                'timestart' => $timenow,
                'timefinish' => $timenow,
                'timemodified' => $timenow,
                'sumgrades' => $attemptobj->get_sum_marks(),
                'gradednotificationsenttime' => $timenow,
            ]
        );
        $this->assertEquals(count($result['questions']), 2);
        // Sort answer arrays prior to checking question data as the
        // ordering of responses is non-deterministic for multi-select.
        sort($result['questions'][0]['answer']);
        sort($result['questions'][1]['answer']);
        $this->assertTrue(in_array(
            [
                'slot' => 1,
                'type' => 'multichoice',
                'idnumber' => $questions[0]->idnumber,
                'answer' => ['<p>Green</p>'],
            ],
            $result['questions']
        ));
        $this->assertTrue(in_array(
            [
                'slot' => 2,
                'type' => 'multichoice',
                'idnumber' => $questions[1]->idnumber,
                'answer' => ['<p>2</p>', '<p>4</p>'],
            ],
            $result['questions']
        ));
    }
}
