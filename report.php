<?php

global $DB;
require_once("../../config.php");

echo '<h1>Report</h1>';

$id = required_param('id', PARAM_INT);
$userid = required_param('userid', PARAM_INT);

$cm = get_coursemodule_from_id('nextblocks', $id, 0, false, MUST_EXIST);
$instanceid = $cm->instance;

$record = $DB->get_record('nextblocks_userdata', array('userid' => $userid, 'nextblocksid' => $instanceid));

if (!$record) {
    echo '<p>No workspace found for this user.</p>';
    return;
}

echo base64_decode($record->submitted_workspace);
