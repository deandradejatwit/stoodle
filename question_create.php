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

/**
 * TODO describe file question_create
 *
 * @package    local_stoodle
 * @copyright  2024 Jonathan Kong-Shi kongshij@wit.edu,
 *             Myles R. Sullivan sullivanm22@wit.edu,
 *             Jhonathan Deandrade deandradej@wit.edu
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require('../../config.php');

require_login();
global $SESSION;
global $error;

$url = new moodle_url('/local/stoodle/question_create.php', []);
$PAGE->set_url($url);
$PAGE->set_context(context_system::instance());
$PAGE->set_pagelayout('standard');
$PAGE->set_title(get_string('createquestion', 'local_stoodle'));
$PAGE->set_heading(get_string('createquestion', 'local_stoodle'));

// Instantiates the create_question constructor to create the create_question form.
$createquestionform = new \local_stoodle\form\create_question();
if ($createquestionform->is_cancelled()) {
    $SESSION->question_count -= 1;
    $url = new moodle_url('/local/stoodle/quiz_create.php');
    redirect($url);
} else if ($data = $createquestionform->get_data()) {
    $optradio = required_param_array('optradio', PARAM_TEXT);
    $question = required_param('question', PARAM_TEXT);
    $answer = required_param_array('answer', PARAM_TEXT);
    $numanswers = 0;

    $questionnum = $SESSION->question_count;
    $quizid = $SESSION->quiz_id;

    if (!empty($question) && check_not_empty($answer) && check_not_empty($optradio)) {

        $recordquestion = new stdClass;
        $recordanswers = new stdClass;

        $recordquestion->stoodle_quizid = $quizid;
        $recordquestion->question_number = $questionnum;
        $recordquestion->question_text = $question;
        $recordquestion->usermodified = $USER->id;
        $recordquestion->timecreated = time();
        $recordquestion->timemodified = time();

        for ($i = 0; $i <= count($answer) - 1; $i++) {
            if (!empty($answer[$i])) {
                $numanswers++;
            }
        }

        // If more that one answer exist set the question to multiple choice.
        if ($numanswers > 1) {
            $recordquestion->is_multiple_choice = 1;
        } else {
            $recordquestion->is_multiple_choice = 0;
        }

        // Checks if created quiz name does not exists in the flashcard set database, if it does go on with question creation.
        if ($DB->get_record_select('stoodle_quiz', 'id = ?', [$quizid]) &&
        !$DB->get_record_select('stoodle_quiz_questions', 'question_text = ?', [$question])) {
            $DB->insert_record('stoodle_quiz_questions', $recordquestion);
            $ques = $DB->get_record_select('stoodle_quiz_questions', 'question_text = ?', [$question]);

            for ($i = 0; $i <= count($answer) - 1; $i++) {
                if (!empty($answer[$i])) {
                    $recordanswers->is_correct = $optradio[$i];
                    $recordanswers->stoodle_quiz_questionsid = $ques->id;
                    $recordanswers->option_number = $i + 1;
                    $recordanswers->option_text = $answer[$i];
                    $recordanswers->usermodified = $USER->id;
                    $recordanswers->timecreated = time();
                    $recordanswers->timemodified = time();
                    $DB->insert_record('stoodle_quiz_question_options', $recordanswers);
                }
            }
            redirect(new moodle_url('/local/stoodle/quiz_create.php'));
        }
    } else {
        $error = true;
    }
}

/**
 * Checks if an array is empty
 *
 * @param array $arr1 First array
 */
function check_not_empty($arr1) {
    for ($i = 0; $i < count($arr1); $i++) {
        if (!(empty($arr1[$i]))) {
            return true;
        }
    }
    return false;
}

echo $OUTPUT->header();

if ($error) {
    echo $OUTPUT->notification(get_string('errquestioncreate', 'local_stoodle'), 'error');
}
$createquestionform->display();


echo $OUTPUT->footer();
