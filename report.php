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
 * Plugin capabilities
 *
 * @package    mod_nextblocks
 * @copyright  2024 Duarte Pereira
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

global $DB;
require_once("../../config.php");

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
