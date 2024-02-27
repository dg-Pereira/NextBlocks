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
global $PAGE, $OUTPUT, $USER;

/**
 * NextBlocks report overview page.
 *
 * @package    mod_nextblocks
 * @copyright  2024 Duarte Pereira
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

global $DB;
require(__DIR__ . '/../../config.php');
require_once(__DIR__ . '/lib.php');

// Course module id.
$id = optional_param('id', 0, PARAM_INT);

// Activity instance id.
$n = optional_param('n', 0, PARAM_INT);

$cm = get_coursemodule_from_id('nextblocks', $id, 0, false, MUST_EXIST);
$course = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
$moduleinstance = $DB->get_record('nextblocks', array('id' => $cm->instance), '*', MUST_EXIST);

require_login($course, true, $cm);

$modulecontext = context_module::instance($cm->id);

//import css
echo '<link rel="stylesheet" href="styles.css">';
//import icons
//echo '<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">';

$instanceid = $cm->instance;

$PAGE->set_url('/mod/nextblocks/overview.php', array('id' => $cm->id));
$PAGE->set_title(format_string($moduleinstance->name) . " Overview");
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_context($modulecontext);

$record = $DB->get_records('nextblocks_userdata', array('nextblocksid' => $instanceid));

//get username, grade, submission timestamp
$grades = array();
foreach ($record as $r) {
    $userid = $r->userid;

    if (has_capability('mod/nextblocks:isgraded', $modulecontext, $userid)) { //switch to this one after reinstalling the plugin
        $user = $DB->get_record('user', array('id' => $r->userid));
        $grade = $r->grade ?? "-";
        //$submission = $r->submission_timestamp;
        $reaction = $r->reacted == 0 ? "-" : $r->reacted;
        $grades[] = array('username' => $user->username, 'userId'=> $userid, 'grade' => $grade, /*'submission' => $submission, */'reaction' => $reaction);
    }
}

$avg_grade = avg_filter(array_column($grades, 'grade'), function($value) {
    return $value != '-';
});
$avg_reaction = avg_filter(array_column($grades, 'reaction'), function($value) {
    return $value != '-';
});

$data = [
    'activityId' => $cm->id,
    'activityName' => $moduleinstance->name,
    'grades' => $grades,
    'avgGrade' => $avg_grade,
    'avgReaction' => $avg_reaction,
    'totalSubmissions' => count($grades)
];

echo $OUTPUT->header();
echo $OUTPUT->render_from_template('mod_nextblocks/overview', $data);
echo $OUTPUT->footer();

/**
 * Filters an array of numbers and returns the average.
 *
 * @param array    $grades An array of numbers.
 * @param callable $filter A function that filters the array.
 *
 * @return float The average of the filtered array.
 */
function avg_filter(array $grades, callable $filter): float {
    $elements = array_filter($grades, $filter);
    //if there are no elements remaining, return 0 to avoid division by 0 error
    if(count($elements) == 0) {
        return 0;
    }
    $avg = array_sum($elements) / count($elements);
    return round($avg, 2);
}