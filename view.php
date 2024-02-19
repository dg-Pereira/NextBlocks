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

$PAGE->requires->js_call_amd('mod_nextblocks/codeenv', 'init', [$tests_file_contents, $saved_workspace, $custom_blocks_json, $remaining_submissions, $reactions, $last_user_reaction, 0]);

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
    'runTestsButton' => $runTestsButton,
    'showSubmitButton' => true,
    'showGrader' => false,
];

echo $OUTPUT->render_from_template('mod_nextblocks/nextblocks', $data);

echo $OUTPUT->footer();

error_log("loaded", 3, "C:\wamp64\logs\php_error.log");

$address = "0.0.0.0";
$port = 8060;
$null = NULL;

$sock = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
socket_bind($sock, $address, $port);
socket_listen($sock);

$members = [];
$connections = [$sock];

while(true) {
    $reads = $connections;
    $writes = $exceptions = $null;

    socket_select($reads, $writes, $exceptions, 0);

    if(in_array($sock, $reads)) {

        $new_connection = socket_accept($sock);
        $header = socket_read($new_connection, 1024);
        handshake($header, $new_connection, $address, $port);

        $connections[] = $new_connection;

        $reply = "new connection\n";
        $reply = pack_data($reply);
        socket_write($new_connection, $reply, strlen($reply));

        $sock_index = array_search($sock, $reads);
        unset($reads[$sock_index]);
    }

    foreach ($reads as $key => $value) {
        $data = socket_read($value, 1024);

        if(!empty($data)) {
            $message = unmask($data);
            $packed_message = pack_data($message);

            foreach($connections as $ckey => $cvalue) {
                if($ckey === 0){
                    continue;
                }
                socket_write($cvalue, $packed_message, strlen($packed_message));
            }
        } elseif ($data === '') {
            echo "disconnecting client $key\n";
            unset($connections[$key]);
            socket_close($value);
        }
    }
}

socket_close($sock);

function unmask($text) {
    $length = ord($text[1]) & 127;
    if($length == 126) {
        $masks = substr($text, 4, 4);
        $data = substr($text, 8);
    } elseif($length == 127) {
        $masks = substr($text, 10, 4);
        $data = substr($text, 14);
    } else {
        $masks = substr($text, 2, 4);
        $data = substr($text, 6);
    }
    $text = "";

    for ($i = 0; $i < strlen($data); ++$i) {
        $text .= $data[$i] ^ $masks[$i % 4];
    }

    return $text;
}

function pack_data($text) {
    $b1 = 0x80 | (0x1 & 0x0f);
    $length = strlen($text);

    if ($length <= 125) {
        $header = pack('CC', $b1, $length);
    } elseif ($length < 65536) {
        $header = pack('CCn', $b1, 126, $length);
    } else {
        $header = pack('CCNN', $b1, 127, $length);
    }

    return $header.$text;
}

function handshake($request_header, $sock, $address, $port) {
    $headers = [];
    $lines = preg_split("/\r\n/", $request_header);

    foreach ($lines as $line) {
        $line = chop($line);
        if(preg_match('/\A(\S+): (.*)\z/', $line, $matches)) {
            $headers[$matches[1]] = $matches[2];
        }
    }

    $secKey = $headers['Sec-WebSocket-Key'];
    $secAccept = base64_encode(pack('H*', sha1($secKey.'258EAFA5-E914-47DA-95CA-C5AB0DC85B11')));

    $response_header = "HTTP/1.1 101 Switching Protocols\r\n" .
        "Upgrade: websocket\r\n" .
        "Connection: Upgrade\r\n" .
        "Sec-WebSocket-Accept: $secAccept\r\n\r\n";

    socket_write($sock, $response_header, strlen($response_header));
}