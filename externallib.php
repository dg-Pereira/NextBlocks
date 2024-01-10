<?php

global $CFG;
require_once("$CFG->libdir/externallib.php");
require_once(__DIR__ . '/lib.php');

class mod_nextblocks_external extends external_api {

    //need to get the course module id because this does not run in the page
    public static function save_workspace($nextblocksid, $saved_workspace) {
        global $DB, $USER;
        $params = self::validate_parameters(self::save_workspace_parameters(),
            array('nextblocksid' => $nextblocksid, 'saved_workspace' => $saved_workspace));
        $cm = get_coursemodule_from_id('nextblocks', $nextblocksid, 0, false, MUST_EXIST);

        //check if record exists
        $record = $DB->get_record('nextblocks_userdata', array('userid' => $USER->id, 'nextblocksid' => $cm->instance));
        //if record exists with same userid and nextblocksid, update it, else insert new record
        if ($record) {
            $DB->update_record('nextblocks_userdata', array('id' => $record->id, 'userid' => $USER->id, 'nextblocksid' => $cm->instance, 'saved_workspace' => $saved_workspace));
        } else {
            $DB->insert_record('nextblocks_userdata', array('userid' => $USER->id, 'nextblocksid' => $cm->instance, 'saved_workspace' => $saved_workspace));
        }
    }

    public static function save_workspace_parameters()
    {
        return new external_function_parameters(
            array(
                'nextblocksid' => new external_value(PARAM_INT, 'module id'),
                'saved_workspace' => new external_value(PARAM_RAW, 'workspace'),
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
        global $USER;

        $tests_file_contents = $tests_file->get_content();

        $tests = json_decode($tests_file_contents, true);

        $testsCount = count($tests);
        $testsCorrectCount = self::run_tests_jobe($tests, $codeString);
        $newGrade = $testsCorrectCount / $testsCount * $nextblocks->grade; //$nextblocks->grade is the max grade

        $grades = new stdClass();
        $grades->userid = $USER->id;
        $grades->rawgrade = $newGrade;

        nextblocks_grade_item_update($nextblocks, $grades);
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
            nextblocks_log("test output: " . $test_output . " expected output: " . $expected_output);
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
        $params = self::validate_parameters(self::submit_workspace_parameters(),
            array('nextblocksid' => $nextblocksid, 'reaction' => $reaction));

        $cm = get_coursemodule_from_id('nextblocks', $nextblocksid, 0, false, MUST_EXIST);

        //get column name from reaction
        $reactionColumnName = "reactions".$reaction;

        //get record
        $record = $DB->get_record('nextblocks', array('nextblocksid' => $cm->instance));

        //increment reaction
        $DB->update_record('nextblocks', array('id' => $record->id, $reactionColumnName => $record->$reactionColumnName + 1));
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
        return null;
    }
}