<?php
// This file is part of Moodle - https://moodle.org/
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
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * Prints an instance of mod_nextblocks.
 *
 * @package     mod_nextblocks
 * @copyright   2023 Duarte Pereira<dg.pereira@campus.fct.unl.pt>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

global $DB, $OUTPUT, $PAGE, $CFG, $page, $USER;

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
echo '<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">';

//import blockly
echo '<script src="./blockly/blockly_compressed.js"></script>
    <script src="./blockly/blocks_compressed.js"></script>
    <script src="./blockly/msg/en.js"></script>
    <script src="./blockly/javascript_compressed.js"></script>';

//import custom category
//echo '<script src="./amd/src/custom_category.js"></script>';

$cmid = $PAGE->cm->id;
$cm = get_coursemodule_from_id('nextblocks', $cmid, 0, false, MUST_EXIST);
$instanceid = $cm->instance;

// call init, with saved workspace and tests file if they exist
$record = $DB->get_record('nextblocks_userdata', array('userid' => $USER->id, 'nextblocksid' => $cm->instance));
$saved_workspace = $record ? $record->saved_workspace : null;

// get custom blocks
$custom_blocks = $DB->get_records('nextblocks_customblocks', array('nextblocksid' => $instanceid));
nextblocks_console_log($custom_blocks);
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

if($record) {
    $remaining_submissions = $moduleinstance->maxsubmissions - $record->submissionnumber;
} else {
    $remaining_submissions = $moduleinstance->maxsubmissions;
}

$reactions = [intval($moduleinstance->reactionseasy), intval($moduleinstance->reactionsmedium), intval($moduleinstance->reactionshard)];
$last_user_reaction = $record ? intval($record->reacted) : 0;

$PAGE->requires->js_call_amd('mod_nextblocks/codeenv', 'init', [$tests_file_contents, $saved_workspace, $custom_blocks_json, $remaining_submissions, $reactions, $last_user_reaction]);

$PAGE->set_url('/mod/nextblocks/view.php', array('id' => $cm->id));
$PAGE->set_title(format_string($moduleinstance->name));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_context($modulecontext);

echo $OUTPUT->header();

$title = $DB->get_field('nextblocks', 'name', array('id' => $instanceid));
$description = $DB->get_field('nextblocks', 'intro', array('id' => $instanceid));

//$runButton = '<input id="runButton" type="submit" class="btn btn-primary m-2" value="'.get_string("nextblocks_run", "nextblocks").'" />';
$runTestsButton = $tests_file ? '<input id="runTestsButton" type="submit" class="btn btn-primary m-2" value="'.get_string("nextblocks_runtests", "nextblocks").'" />' : '';


//display tests file
/*
if($filenamehash != false){
    echo '<div id="testsDiv" class="container mt-6 mb-6">';
    echo '<h3>Tests</h3>';
    echo '<p>' . $tests_file_contents . '</p>';
    echo '</div>';
}
*/

$data = [
    'title' => $OUTPUT->heading($title),
    'description' => $description,
    'outputHeading' => $OUTPUT->heading("Output", $level=4),
    'reactionsHeading' => $OUTPUT->heading("Reactions", $level=4),
    'runTestsButton' => $runTestsButton
];

echo $OUTPUT->render_from_template('mod_nextblocks/nextblocks', $data);

echo $OUTPUT->footer();
