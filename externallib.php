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
            $DB->update_record('nextblocks_userdata', array('id' => $record->id, 'userid' => $USER->id, 'nextblocksid' => $cm->instance, 'submitted_workspace' => $submitted_workspace));
        } else {
            $DB->insert_record('nextblocks_userdata', array('userid' => $USER->id, 'nextblocksid' => $cm->instance, 'submitted_workspace' => $submitted_workspace));
        }
        $nextblocks = $DB->get_record('nextblocks', array('id' => $cm->instance));

        $grades = new stdClass();
        $grades->userid = $USER->id;
        $grades->rawgrade = 98;

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

        nextblocks_log($codeString . "\n" . $result . "\n");

        $result = json_decode($result, true);
        $stdout = $result['stdout'];

        nextblocks_grade_item_update($nextblocks, $grades);
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

}