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
global $DB, $OUTPUT, $PAGE, $CFG, $page, $USER;

/**
 * Prints an instance of mod_nextblocks.
 *
 * @package     mod_nextblocks
 * @copyright   2023 Duarte Pereira<dg.pereira@campus.fct.unl.pt>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use event\course_module_viewed;

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
$record = $DB->get_record('nextblocks_userdata', array('userid' => $USER->id, 'nextblocksid' => $cmid));
$saved_workspace = $record ? $record->saved_workspace : null;

$fs = get_file_storage();
$filenamehash = get_filenamehash($instanceid);

$tests_file = $fs->get_file_by_hash($filenamehash);
$tests_file_contents = $tests_file ? $tests_file->get_content() : null;
$PAGE->requires->js_call_amd('mod_nextblocks/codeenv', 'init', [$tests_file_contents, $saved_workspace]);
//$PAGE->requires->js_call_amd('mod_nextblocks/test', 'init');
$PAGE->set_url('/mod/nextblocks/view.php', array('id' => $cm->id));
$PAGE->set_title(format_string($moduleinstance->name));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_context($modulecontext);

echo $OUTPUT->header();

$title = $DB->get_field('nextblocks', 'name', array('id' => $instanceid));
$description = $DB->get_field('nextblocks', 'intro', array('id' => $instanceid));

$runButton = '<input id="runButton" type="submit" class="btn btn-primary m-2" value="'.get_string("nextblocks_run", "nextblocks").'" />';
$runTestsButton = $tests_file ? '<input id="runTestsButton" type="submit" class="btn btn-primary m-2" value="'.get_string("nextblocks_runtests", "nextblocks").'" />' : '';


echo $OUTPUT->heading($title);
echo '<p>' . $description . '</p>';

echo '<hr>';

echo '<div id="nextblocks-container" class="container-fluid mt-6 mb-6">
    <div class="row h-100">
        <div id = "blocklyArea" class="col-md-9 h-100">
            <div id="blocklyDiv" class="mw-100 h-100"></div>
        </div>
        <div class="col-md-3 mh-100 h-100">
            <div class="row h-25 border">
                <!-- "reactions" on top, and a row of three buttons below" -->
                <div class="col-md-12 h-100">
                    <div class="row h-25">
                        <!-- centered text "reactions" -->
                        <div class="col-md-12 text-center">
                            '. $OUTPUT->heading("Reactions", $level=4) . '
                        </div>
                    </div>
                    <div class="row h-75">
                        <!-- three emoji reactions -->
                        <div class="col-md-4 h-100">
                            <div class="row h-75">
                                <img class = "emoji-img img-fluid border border-secondary mx-auto d-block p-1 bg-gray rounded bg-primary mh-100" src="pix/emoji-hard.png" alt="Dummy laugh image">
                            </div>
                            <div class="row h-25">
                                <div class="col-md-12 text-center">
                                    33%
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4 h-100">
                            <div class="row h-75">
                                <img class = "emoji-img img-fluid border border-secondary mx-auto d-block p-1 bg-gray rounded mh-100" src="pix/emoji-think.png" alt="Dummy laugh image">
                            </div>
                            <div class="col-md-12 text-center">
                                33%
                            </div>
                        </div>
                        <div class="col-md-4 h-100">
                            <div class="row h-75">
                                <img class = "emoji-img img-fluid border border-secondary mx-auto d-block p-1 bg-gray rounded mh-100" src="pix/emoji-easy.png" alt="Dummy laugh image">
                            </div>
                            <div class="col-md-12 text-center">
                                33%
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="row h-75 border">
                <div class="col-md-12 h-100">
                    <div class="row h-10">
                        <div class="col-md-12 text-center">
                            '. $OUTPUT->heading("Output", $level=4) . '
                        </div>
                    </div>
                    <div class="row h-75">
                        <div id="output-div" class="col-md-12 h-100 p-2">
                        </div>
                    </div>  
                    <div class="row h-15">
                        <!-- run and run tests buttons -->
                        <div class="col-md-12">
                            <div class="row">
                                <div class="col-md-12 text-center">
                                    <input id="runButton" type="submit" class="btn btn-primary m-2" value="'.get_string("nextblocks_run", "nextblocks").'" />' .
                                    $runTestsButton . '
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>';

//make div for displaying static code text
echo '<div id="codeDiv" class="container mt-6 mb-6"></div>';

//display tests file
/*
if($filenamehash != false){
    echo '<div id="testsDiv" class="container mt-6 mb-6">';
    echo '<h3>Tests</h3>';
    echo '<p>' . $tests_file_contents . '</p>';
    echo '</div>';
}
*/

//make buttons centered
echo '<div style="text-align: center;">';

echo '<input id="saveButton" type="submit" class="btn btn-primary m-2" value="'.get_string("nextblocks_save", "nextblocks").'" />';
echo '<input type="submit" class="btn btn-primary m-2" value="'.get_string("nextblocks_submit", "nextblocks").'" />';
echo '<input type="submit" class="btn btn-primary m-2" value="'.get_string("nextblocks_cancel", "nextblocks").'" />';

echo '</div>';

//make horizontal separator
echo '<hr>';

echo '<div style="text-align: center;">';
echo '<img src="pix/chat.png" alt="Dummy chat image">';
echo '</div>';

echo $OUTPUT->footer();
