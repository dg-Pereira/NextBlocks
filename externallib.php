<?php

global $CFG;
require_once("$CFG->libdir/externallib.php");
require_once(__DIR__ . '/lib.php');

class mod_nextblocks_external extends external_api {

    //need to get the course module id because this does not run in the page
    /**
     * Saves the workspace of a user.
     *
     * @param int $nextblocksid Id of the nextblocks activity
     * @param string $saved_workspace The workspace to be saved, in base64
     * @param int    $userid          The id of the user that is saving the workspace.
     *                                By default is not needed, and the current user is used.
     *                                Only used when teacher is adding comments to user's workspace.
     *
     * @return void
     * @throws coding_exception
     * @throws dml_exception
     * @throws invalid_parameter_exception
     */
    public static function save_workspace($nextblocksid, $saved_workspace, $userid=null) {
        global $DB, $USER;
        if (!$userid) {
            $userid = $USER->id;
        }
        $params = self::validate_parameters(self::save_workspace_parameters(),
            array('nextblocksid' => $nextblocksid, 'saved_workspace' => $saved_workspace));
        $cm = get_coursemodule_from_id('nextblocks', $nextblocksid, 0, false, MUST_EXIST);

        //check if record exists
        $record = $DB->get_record('nextblocks_userdata', array('userid' => $userid, 'nextblocksid' => $cm->instance));
        //if record exists with same userid and nextblocksid, update it, else insert new record
        if ($record) {
            $DB->update_record('nextblocks_userdata', array('id' => $record->id, 'userid' => $userid, 'nextblocksid' => $cm->instance, 'saved_workspace' => $saved_workspace));
        } else {
            $DB->insert_record('nextblocks_userdata', array('userid' => $userid, 'nextblocksid' => $cm->instance, 'saved_workspace' => $saved_workspace));
        }
    }

    public static function save_workspace_parameters()
    {
        return new external_function_parameters(
            array(
                'nextblocksid' => new external_value(PARAM_INT, 'module id'),
                'saved_workspace' => new external_value(PARAM_RAW, 'workspace'),
                'userid' => new external_value(PARAM_INT, 'user id', false)
            )
        );
    }

    public static function save_workspace_returns() {
        return null;
    }

    public static function submit_workspace($nextblocksid, $submitted_workspace, $codeString) {
        global $DB, $USER;

        $params = self::validate_parameters(self::submit_workspace_parameters(),
            array('nextblocksid' => $nextblocksid, 'submitted_workspace' => $submitted_workspace, 'codeString' => $codeString));

        $cm = get_coursemodule_from_id('nextblocks', $nextblocksid, 0, false, MUST_EXIST);

        //check if record exists
        $record = $DB->get_record('nextblocks_userdata', array('userid' => $USER->id, 'nextblocksid' => $cm->instance));
        //if record exists with same userid and nextblocksid, update it, else insert new record
        if ($record) {
            $DB->update_record('nextblocks_userdata', array('id' => $record->id, 'userid' => $USER->id, 'nextblocksid' => $cm->instance, 'saved_workspace' => $submitted_workspace, 'submitted_workspace' => $submitted_workspace, 'submissionnumber' => $record->submissionnumber + 1));
        } else {
            $DB->insert_record('nextblocks_userdata', array('userid' => $USER->id, 'nextblocksid' => $cm->instance, 'saved_workspace' => $submitted_workspace, 'submitted_workspace' => $submitted_workspace, 'submissionnumber' => 1));
        }

        $nextblocks = $DB->get_record('nextblocks', array('id' => $cm->instance));

        $fs = get_file_storage();
        $filenamehash = get_filenamehash($cm->instance);

        // if has point grade and tests, run auto grading
        if($nextblocks->grade > 0 && $filenamehash != false) {
            $tests_file = $fs->get_file_by_hash($filenamehash);
            self::auto_grade($cm, $codeString, $nextblocks, $tests_file);
        }
    }

    public static function auto_grade($cm, $codeString, $nextblocks, $tests_file) {
        global $USER, $DB;

        $tests_file_contents = $tests_file->get_content();

        $tests = json_decode($tests_file_contents, true);

        $testsCount = count($tests);
        $testsCorrectCount = self::run_tests_jobe($tests, $codeString);
        $newGrade = $testsCorrectCount / $testsCount * $nextblocks->grade; //$nextblocks->grade is the max grade

        $grades = new stdClass();
        $grades->userid = $USER->id;
        $grades->rawgrade = $newGrade;

        nextblocks_grade_item_update($nextblocks, $grades);

        // update userdata with new grade
        $userdata = $DB->get_record('nextblocks_userdata', array('userid' => $USER->id, 'nextblocksid' => $cm->instance));
        $DB->update_record('nextblocks_userdata', array('id' => $userdata->id, 'userid' => $USER->id, 'nextblocksid' => $cm->instance, 'grade' => $newGrade));
    }

    public static function run_tests_jobe($tests, $codeString): int {
        $testsCorrectCount = 0;
        for ($i = 0; $i < count($tests); $i++) {
            $test = $tests[$i];
            $inputs = $test['inputs'];
            $expected_output = $test['output'];
            //json has arrays where there shouldn't be, as there is only one element, so the foreaches are necessary even though they look redundant
            foreach($inputs as $key => $val) {
                $inputName = "";
                $input = "";
                foreach($val as $inputName_ => $val1) {
                    $inputName = $inputName_;
                    $input = "";
                    foreach($val1 as $key2 => $inputValue_) {
                        $input = $inputValue_;
                    }
                }
                // Get the indices of the first and second parentheses of the last occurrence of the input function call
                $firstParenIndex = strrpos($codeString, "input" . $inputName . "(");
                $secondParenIndex = strpos($codeString, ")", $firstParenIndex);

                //replace everything between the parentheses with the input
                $codeString = substr_replace($codeString, $input, $firstParenIndex + strlen("input" . $inputName . "("), $secondParenIndex - $firstParenIndex - strlen("input" . $inputName . "("));
            }

            $test_output = self::run_test_jobe($codeString);
            if ($test_output == $expected_output) {
                $testsCorrectCount++;
            }
        }


        return $testsCorrectCount;
    }

    /*
         * Make http request to localhost:4000/jobe/index.php/restapi/runs/ with the following json:
         * {
         *   "run_spec": {
         *     "language_id": "c",
         *     "sourcefilename": "test.c",
         *     "sourcecode": "\n#include <stdio.h>\n\nint main() {\n    printf(\"Hello world\\n\");\n}\n"
         *    }
         * }
         *
         * and the headers:
         * Content-type: application/json; charset-utf-8
         *
         * Run docker container first
         */
    public static function run_test_jobe($codeString){
        $url = 'http://localhost:4000/jobe/index.php/restapi/runs/';
        $data = [
            "run_spec" => [
                'language_id' => 'nodejs',
                'sourcefilename' => 'test.js',
                'sourcecode' => $codeString
            ]
        ];

        // use key 'http' even if you send the request to https://...
        $options = [
            'http' => [
                'header' => "Content-type: application/json\r\n",
                'method' => 'POST',
                'content' => json_encode($data),
            ],
        ];

        $context = stream_context_create($options);
        $result = file_get_contents($url, false, $context);
        if ($result === false) {
            /* Handle error */

        }

        $result = json_decode($result, true);
        return $result['stdout'];
    }

    public static function submit_workspace_parameters()
    {
        return new external_function_parameters(
            array(
                'nextblocksid' => new external_value(PARAM_INT, 'module id'),
                'submitted_workspace' => new external_value(PARAM_RAW, 'workspace'),
                'codeString' => new external_value(PARAM_RAW, 'codeString'),
            )
        );
    }

    public static function submit_workspace_returns() {
        return null;
    }

    public static function submit_reaction($nextblocksid, $reaction) {
        global $DB, $USER;
        $params = self::validate_parameters(self::submit_reaction_parameters(),
            array('nextblocksid' => $nextblocksid, 'reaction' => $reaction));

        $cm = get_coursemodule_from_id('nextblocks', $nextblocksid, 0, false, MUST_EXIST);
        $nextblocks = $DB->get_record('nextblocks', array('id' => $cm->instance));
        $userdata = $DB->get_record('nextblocks_userdata', array('userid' => $USER->id, 'nextblocksid' => $cm->instance));
        //if userdata does not exist, insert new record
        if (!$userdata) {
            $new_id = $DB->insert_record('nextblocks_userdata', array('userid' => $USER->id, 'nextblocksid' => $cm->instance));
            $userdata = $DB->get_record('nextblocks_userdata', array('id' => $new_id));
        }

        //get reaction database column name
        $newReactionColumnName = "reactions".$reaction;
        //reaction to number, for database (easy-1, medium-2, hard-3)
        $newReactionNumber = array_search($reaction, array('easy', 'medium', 'hard')) + 1;

        $new_reactions = [
            'reactionseasy' => $nextblocks->reactionseasy,
            'reactionsmedium' => $nextblocks->reactionsmedium,
            'reactionshard' => $nextblocks->reactionshard,];

        // if new reaction is same as previous reaction, decrement reaction
        if ($userdata->reacted == $newReactionNumber) {
            $DB->update_record('nextblocks', array('id' => $nextblocks->id, $newReactionColumnName => $nextblocks->$newReactionColumnName - 1));
            //user unreacted, update userdata
            $DB->update_record('nextblocks_userdata', array('id' => $userdata->id, 'userid' => $USER->id, 'nextblocksid' => $cm->instance, 'reacted' => 0));
            $new_reactions[$newReactionColumnName] = $nextblocks->$newReactionColumnName - 1;
        } else { // else, decrement previous reaction (if it exists) and increment new reaction.
            if ($userdata->reacted == 0) {
                $DB->update_record('nextblocks', array('id' => $nextblocks->id, $newReactionColumnName => $nextblocks->$newReactionColumnName + 1));
                $new_reactions[$newReactionColumnName] = $nextblocks->$newReactionColumnName + 1;
            } else {
                $oldReactionColumnName = "reactions" . array('easy', 'medium', 'hard')[$userdata->reacted - 1];

                $DB->update_record(
                    'nextblocks', array(
                        'id' => $nextblocks->id,
                        $newReactionColumnName => $nextblocks->$newReactionColumnName + 1,
                        $oldReactionColumnName => $nextblocks->$oldReactionColumnName - 1
                    )
                );
                $new_reactions[$newReactionColumnName] = $nextblocks->$newReactionColumnName + 1;
                $new_reactions[$oldReactionColumnName] = $nextblocks->$oldReactionColumnName - 1;
            }
            //update userdata with new reaction
            $DB->update_record('nextblocks_userdata', array('id' => $userdata->id, 'userid' => $USER->id, 'nextblocksid' => $cm->instance, 'reacted' => $newReactionNumber));
        }

        return $new_reactions;
    }

    public static function submit_reaction_parameters()
    {
        return new external_function_parameters(
            array(
                'nextblocksid' => new external_value(PARAM_INT, 'module id'),
                'reaction' => new external_value(PARAM_ALPHA, 'workspace'),
            )
        );
    }

    public static function submit_reaction_returns() {
        return new external_single_structure(
            array(
                'reactionseasy' => new external_value(PARAM_INT, 'number of easy reactions'),
                'reactionsmedium' => new external_value(PARAM_INT, 'number of medium reactions'),
                'reactionshard' => new external_value(PARAM_INT, 'number of hard reactions'),
            )
        );
    }

    public static function save_message($message, $userName, $nextblocksId, $timestamp) {
        global $DB;
        $params = self::validate_parameters(self::save_message_parameters(),
            array('message' => $message, 'userName' => $userName, 'nextblocksId' => $nextblocksId, 'timestamp' => $timestamp));
        $DB->insert_record('nextblocks_messages', array('message' => $message, 'username' => $userName, 'nextblocksid' => $nextblocksId, 'timestamp' => $timestamp));
    }

    public static function save_message_parameters() {
        return new external_function_parameters(
            array(
                'message' => new external_value(PARAM_TEXT, 'message sent'),
                'userName' => new external_value(PARAM_TEXT, 'name of the user who sent the message'),
                'nextblocksId' => new external_value(PARAM_INT, 'id of the activity where the message was sent'),
                'timestamp' => new external_value(PARAM_INT, 'when the message was sent (UNIX time)'),
            )
        );
    }

    public static function save_message_returns() {
        return null;
    }

    public static function get_messages($messageCount, $nextblocksId){
        global $DB;
        $params = self::validate_parameters(self::get_messages_parameters(),
            array('messageCount' => $messageCount, 'nextblocksId' => $nextblocksId));
        $messages = $DB->get_records('nextblocks_messages', array('nextblocksid' => $nextblocksId), 'timestamp ASC', '*', 0, $messageCount);
        $messagesArray = array();
        foreach ($messages as $message) {
            $messageArray = array(
                'message' => $message->message,
                'username' => $message->username,
                'timestamp' => $message->timestamp
            );
            $messagesArray[] = $messageArray;
        }
        return $messagesArray;
    }

    public static function get_messages_parameters() {
        return new external_function_parameters(
            array(
                'messageCount' => new external_value(PARAM_INT, 'number of messages to get'),
                'nextblocksId' => new external_value(PARAM_INT, 'id of the activity where the messages were sent'),
            )
        );
    }

    public static function get_messages_returns() {
        return new external_multiple_structure(
            new external_single_structure(
                array(
                    'message' => new external_value(PARAM_TEXT, 'message sent'),
                    'username' => new external_value(PARAM_TEXT, 'name of the user who sent the message'),
                    'timestamp' => new external_value(PARAM_INT, 'when the message was sent (UNIX time)'),
                )
            )
        );
    }
}