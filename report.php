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
 * NextBlocks report page.
 *
 * @package    mod_nextblocks
 * @copyright  2024 Duarte Pereira
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
global $PAGE, $OUTPUT, $USER, $DB;

use mod_nextblocks\form\grade_submit;

require(__DIR__ . '/../../config.php');
require_once(__DIR__ . '/lib.php');

// Course module id.
$id = optional_param('id', 0, PARAM_INT);

// Activity instance id.
$n = optional_param('n', 0, PARAM_INT);

if ($id) {
    $cm = get_coursemodule_from_id('nextblocks', $id, 0, false, MUST_EXIST);
    $course = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
    $moduleinstance = $DB->get_record('nextblocks', array('id' => $cm->instance), '*', MUST_EXIST);
} else {
    $moduleinstance = $DB->get_record('nextblocks', array('id' => $n), '*', MUST_EXIST);
    $course = $DB->get_record('course', array('id' => $moduleinstance->course), '*', MUST_EXIST);
    $cm = get_coursemodule_from_instance('nextblocks', $moduleinstance->id, $course->id, false, MUST_EXIST);
}

require_login($course, true, $cm);

$modulecontext = context_module::instance($cm->id);

//import css
echo '<link rel="stylesheet" href="styles.css">';
//import icons
//echo '<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">';

//import blockly
echo '<script src="./blockly/blockly_compressed.js"></script>
    <script src="./blockly/blocks_compressed.js"></script>
    <script src="./blockly/msg/en.js"></script>
    <script src="./blockly/javascript_compressed.js"></script>';

$userid = required_param('userid', PARAM_INT);

$instanceid = $cm->instance;

$record = $DB->get_record('nextblocks_userdata', array('userid' => $userid, 'nextblocksid' => $instanceid));

$saved_workspace = $record->saved_workspace;

// get custom blocks
$custom_blocks = $DB->get_records('nextblocks_customblocks', array('nextblocksid' => $instanceid));
$custom_blocks_json = array();
foreach ($custom_blocks as $custom_block) {
    $custom_blocks_json[] = array(
        'definition' => $custom_block->blockdefinition,
        'generator' => $custom_block->blockgenerator
    );
}

$fs = get_file_storage();
$filenamehash = get_filenamehash($instanceid);

$tests_file = $fs->get_file_by_hash($filenamehash);
$tests_file_contents = $tests_file ? $tests_file->get_content() : null;

$reactions = [intval($moduleinstance->reactionseasy), intval($moduleinstance->reactionsmedium), intval($moduleinstance->reactionshard)];
$last_user_reaction = intval($record->reacted);

if (has_capability('mod/nextblocks:gradeitems', context_module::instance($cm->id))) {
    $reportType = 1;
} else {
    $reportType = 2;
}
$PAGE->requires->js_call_amd('mod_nextblocks/codeenv', 'init', [$tests_file_contents, $saved_workspace, $custom_blocks_json, 1, $reactions, $last_user_reaction, $reportType]);

$PAGE->set_url('/mod/nextblocks/report.php', array('id' => $cm->id));
$PAGE->set_title("Report " . format_string($moduleinstance->name));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_context($modulecontext);

$title = $DB->get_field('nextblocks', 'name', array('id' => $instanceid));
$description = $DB->get_field('nextblocks', 'intro', array('id' => $instanceid));

$runTestsButton = $tests_file ? '<input id="runTestsButton" type="submit" class="btn btn-primary m-2" value="'.get_string("nextblocks_runtests", "nextblocks").'" />' : '';

$mform = new grade_submit();

if($data = $mform->get_data()) {
    //This is where you can process the $data you get from the form
    error_log('test2', 3, "C:\wamp64\logs\php_error.log");

    //update grade

    //redirect(new moodle_url('/nextblocks/report.php', array('id' => $moduleinstance->course)), 'Cancelled');
    redirect(new moodle_url($PAGE->url, array('id' => $id, 'userid' => $userid)), 'Cancelled');
} else {
    $graderForm = $mform->render();

    $student = $DB->get_record('user', array('id' => $userid));

    $currentGrade = $record->grade;
    $maxGrade = $moduleinstance->grade;

    $showGrader = has_capability('mod/nextblocks:gradeitems', context_module::instance($cm->id));

    $data = [
        'title' => $OUTPUT->heading($title),
        'description' => $description,
        'outputHeading' => $OUTPUT->heading("Output", $level=4),
        'reactionsHeading' => $OUTPUT->heading("Reactions", $level=4),
        'runTestsButton' => $runTestsButton,
        'showSubmitButton' => false,
        'showGrader' => $showGrader,
        'graderForm' => $graderForm,
        'studentName' => $student->firstname . ' ' . $student->lastname,
        'currentGrade' => $currentGrade,
        'maxGrade' => $maxGrade,
    ];

    echo $OUTPUT->header();
    echo $OUTPUT->render_from_template('mod_nextblocks/nextblocks', $data);
    echo $OUTPUT->footer();
}
