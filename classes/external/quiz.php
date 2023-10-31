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

namespace local_raisecli\external;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/externallib.php');
require_once($CFG->libdir . '/questionlib.php');

use external_api;
use external_function_parameters;
use external_multiple_structure;
use external_value;
use external_single_structure;
use question_engine;

/**
 * RAISE CLI Web Service Function - Quiz Data Access Functions
 *
 * @package    local_raisecli
 * @copyright  2023 OpenStax
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class quiz extends external_api {
    /**
     * Describes the parameters for get_quiz_attempt.
     *
     * @return external_function_parameters
     */
    public static function get_quiz_attempt_parameters() {
        return new external_function_parameters ([
            'attemptid' => new external_value(PARAM_INT, 'attempt id'),
        ]);
    }

    /**
     * Get quiz attempt details including question level responses
     *
     * @param int $attemptid attempt id
     * @throws moodle_exception
     */
    public static function get_quiz_attempt($attemptid) {
        global $DB;

        $params = self::validate_parameters(
            self::get_quiz_attempt_parameters(),
            ['attemptid' => $attemptid]
        );

        $conditions = ['id' => $params['attemptid'], 'state' => 'finished'];
        $fields = [
            'id',
            'attempt',
            'quiz',
            'userid',
            'uniqueid',
            'state',
            'sumgrades',
            'timestart',
            'timefinish',
            'timemodified',
            'gradednotificationsenttime',
        ];
        $attempt = $DB->get_record(
            'quiz_attempts',
            $conditions,
            implode(',', $fields),
            MUST_EXIST
        );
        $quba = question_engine::load_questions_usage_by_activity($attempt->uniqueid);

        $context = $quba->get_owning_context();
        self::validate_context($context);
        require_capability('mod/quiz:viewreports', $context);

        $questiondata = [];
        foreach ($quba->get_slots() as $slot) {
            $questionattempt = $quba->get_question_attempt($slot);
            $question = $questionattempt->get_question();
            $questiontype = $question->qtype->name();
            if ($questiontype == "multichoice") {
                // We are only generating details on multichoice questions.
                $choiceorder = $question->get_order($questionattempt);
                $response = $question->get_response($questionattempt);
                $responseanswers = [];

                if (is_array($response)) {
                    // The response is an array if the question was configured as
                    // multi-select. Parsing the correct answer content is a bit
                    // more tedious due to how the response is returned as either
                    // ["_order" => ""] if unanswered or ["choice0" => "1", "choice1" = "0"].
                    foreach ($choiceorder as $choicenum => $responseid) {
                        if ($question->is_choice_selected($response, $choicenum)) {
                            $responseanswers[] = $question->answers[$responseid]->answer;
                        }
                    }
                } else {
                    // Response is -1 if question was not answered and otherwise
                    // the index into the choiceorder.
                    if (array_key_exists($response, $choiceorder)) {
                        $responseid = $choiceorder[$response];
                        $responseanswers[] = $question->answers[$responseid]->answer;
                    }
                }

                $questiondata[] = [
                    'slot' => $slot,
                    'type' => $questiontype,
                    'idnumber' => $question->idnumber,
                    'answer' => $responseanswers,
                ];
            }
        }

        return [
            'attempt' => [
                'id' => $attempt->id,
                'quiz' => $attempt->quiz,
                'userid' => $attempt->userid,
                'attempt' => $attempt->attempt,
                'uniqueid' => $attempt->uniqueid,
                'state' => $attempt->state,
                'timestart' => $attempt->timestart,
                'timefinish' => $attempt->timefinish,
                'timemodified' => $attempt->timemodified,
                'sumgrades' => $attempt->sumgrades,
                'gradednotificationsenttime' => $attempt->gradednotificationsenttime,
            ],
            'questions' => $questiondata,
        ];
    }

    /**
     * Describes the get_quiz_attempt return value.
     *
     * @return external_single_structure
     * @since Moodle 3.1
     */
    public static function get_quiz_attempt_returns() {
        return new external_single_structure([
            'attempt' => new external_single_structure([
                'id' => new external_value(PARAM_INT, 'Attempt id.'),
                'quiz' => new external_value(PARAM_INT, 'Foreign key reference to the quiz that was attempted.'),
                'userid' => new external_value(PARAM_INT, 'Foreign key reference to the user whose attempt this is.'),
                'attempt' => new external_value(PARAM_INT, 'Sequentially numbers this students attempts at this quiz.'),
                'uniqueid' => new external_value(PARAM_INT, 'Foreign key reference to the question_usage that holds the
                                                    details of the the question_attempts that make up this quiz
                                                    attempt.'),
                'state' => new external_value(PARAM_ALPHA, 'The current state of the attempts.'),
                'timestart' => new external_value(PARAM_INT, 'Time when the attempt was started.'),
                'timefinish' => new external_value(PARAM_INT, 'Time when the attempt was submitted'),
                'timemodified' => new external_value(PARAM_INT, 'Last modified time.'),
                'sumgrades' => new external_value(PARAM_FLOAT, 'Total marks for this attempt.'),
                'gradednotificationsenttime' => new external_value(
                    PARAM_INT,
                    'Time when the student was notified that manual grading of their attempt was complete.'
                ),
            ]),
            'questions' => new external_multiple_structure(
                new external_single_structure([
                    'slot' => new external_value(PARAM_INT, 'slot number'),
                    'type' => new external_value(PARAM_ALPHANUMEXT, 'question type, i.e: multichoice'),
                    'idnumber' => new external_value(PARAM_TEXT, 'question idnumber'),
                    'answer' => new external_multiple_structure(
                        new external_value(PARAM_RAW, 'answer HTML as defined in the question content')
                    ),
                ])
            ),
        ]);
    }
}
