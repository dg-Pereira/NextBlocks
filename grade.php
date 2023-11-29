<?php
global $PAGE, $DB, $CFG;
require_once("../../config.php");

$id = required_param('id', PARAM_INT);

$cm = get_coursemodule_from_id('nextblocks', $id, 0, false, MUST_EXIST);
$course = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
$lesson = $DB->get_record('nextblocks', array('id' => $cm->instance), '*', MUST_EXIST);

require_login($course, false, $cm);

$PAGE->set_url('/mod/nextblocks/grade.php', array('id'=>$cm->id))
if (has_capability('mod/nextblocks:viewreports', context_module::instance($cm->id))) {
    redirect('report.php?id='.$cm->id);
} else {
    redirect('view.php?id='.$cm->id);
}