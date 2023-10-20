<?php

global $CFG;
require_once("$CFG->libdir/externallib.php");

class mod_nextblocks_external extends external_api {

    //need to get the course module id because this does not run in the page
    public static function save_workspace($userid, $nextblocksid, $saved_workspace) {
        global $DB;
        $params = self::validate_parameters(self::save_workspace_parameters(),
                array('userid' => $userid, 'nextblocksid' => $nextblocksid, 'saved_workspace' => $saved_workspace));
        $DB->insert_record('nextblocks_userdata', array('userid' => $userid, 'nextblocksid' => $nextblocksid, 'saved_workspace' => $saved_workspace));
    }

    public static function save_workspace_parameters()
    {
        error_log('Log test', 3, 'C:\wamp64\logs\php_error.log');
        return new external_function_parameters(
            array(
                'userid' => new external_value(PARAM_INT, 'user id'),
                'nextblocksid' => new external_value(PARAM_INT, 'module id'),
                'saved_workspace' => new external_value(PARAM_TEXT, 'workspace'),
            )
        );
    }

    public static function save_workspace_returns() {
        return null;
    }
}