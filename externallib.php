<?php

global $CFG;
require_once("$CFG->libdir/externallib.php");

class mod_nextblocks_external extends external_api {

    //need to get the course module id because this does not run in the page
    public static function save_workspace($nextblocksid, $saved_workspace) {
        global $DB, $USER;
        $params = self::validate_parameters(self::save_workspace_parameters(),
                array('nextblocksid' => $nextblocksid, 'saved_workspace' => $saved_workspace));
        //check if record exists
        $record = $DB->get_record('nextblocks_userdata', array('userid' => $USER->id, 'nextblocksid' => $nextblocksid));
        //if record exists with same userid and nextblocksid, update it, else insert new record
        if ($record) {
            $DB->update_record('nextblocks_userdata', array('id' => $record->id, 'userid' => $USER->id, 'nextblocksid' => $nextblocksid, 'saved_workspace' => $saved_workspace));
        } else {
            $DB->insert_record('nextblocks_userdata', array('userid' => $USER->id, 'nextblocksid' => $nextblocksid, 'saved_workspace' => $saved_workspace));
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

    public static function submit_workspace($nextblocksid, $saved_workspace) {
        global $DB, $USER;
        $params = self::validate_parameters(self::submit_workspace_parameters(),
            array('nextblocksid' => $nextblocksid, 'saved_workspace' => $saved_workspace));
        //check if record exists
        $record = $DB->get_record('nextblocks_userdata', array('userid' => $USER->id, 'nextblocksid' => $nextblocksid));
        //if record exists with same userid and nextblocksid, update it, else insert new record
        if ($record) {
            $DB->update_record('nextblocks_userdata', array('id' => $record->id, 'userid' => $USER->id, 'nextblocksid' => $nextblocksid, 'submitted_workspace' => $saved_workspace));
        } else {
            $DB->insert_record('nextblocks_userdata', array('userid' => $USER->id, 'nextblocksid' => $nextblocksid, 'submitted_workspace' => $saved_workspace));
        }
    }

    public static function submit_workspace_parameters()
    {
        return new external_function_parameters(
            array(
                'nextblocksid' => new external_value(PARAM_INT, 'module id'),
                'saved_workspace' => new external_value(PARAM_RAW, 'workspace'),
            )
        );
    }

    public static function submit_workspace_returns() {
        return null;
    }


}