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
 * Library of interface functions and constants.
 *
 * @package     mod_nextblocks
 * @copyright   2023 Duarte Pereira<dg.pereira@campus.fct.unl.pt>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Return if the plugin supports $feature.
 *
 * @param string $feature Constant representing the feature.
 *
 * @return true | null True if the feature is supported, null otherwise.
 */
function nextblocks_supports($feature) {
    switch ($feature) {
    case FEATURE_MOD_INTRO:
        return true;
    default:
        return null;
    }
}

/**
 * Saves a new instance of the mod_nextblocks into the database.
 *
 * Given an object containing all the necessary data, (defined by the form
 * in mod_form.php) this function will create a new instance and return the id
 * number of the instance.
 *
 * @param object                  $moduleinstance An object from the form.
 * @param mod_nextblocks_mod_form $mform          The form.
 *
 * @return int The id of the newly inserted record.
 */
function nextblocks_add_instance($moduleinstance, $mform = null) {
    global $DB;

    $moduleinstance->timecreated = time();

    $id = (int)$DB->insert_record('nextblocks', $moduleinstance);

    //post form submission stuff here!

    // Form processing and displaying is done here.
    if ($mform->is_cancelled()) {
        //send log to C:\wamp64\logs\php_error.log
        error_log('11', 3, 'C:\wamp64\logs\php_error.log');
        // If there is a cancel element on the form, and it was pressed,
        // then the `is_cancelled()` function will return true.
        // You can handle the cancel operation here.
    } else if ($fromform = $mform->get_data()) {
        //send log to C:\wamp64\logs\php_error.log
        error_log('22', 3, 'C:\wamp64\logs\php_error.log');
        // When the form is submitted, and the data is successfully validated,
        // the `get_data()` function will return the data posted in the form.

        //save the tests file in File API
        save_tests_file($fromform, $id);

        //save hash of the file in the database for later file retrieval
        save_tests_file_hash($id);

        //if tests file does not exist, hash is 0 in the database
    } else {
        // This branch is executed if the form is submitted but the data doesn't
        // validate and the form should be redisplayed or on the first display of the form.

        //send log to C:\wamp64\logs\php_error.log
        // Set anydefault data (if any).
        //$this->set_data($toform);

        // Display the form.
        //$mform->display();
    }

    return $id;
}

function save_tests_file_hash(int $id)
{
    global $DB;
    $pathnamehash = $DB->get_field('files', 'pathnamehash', ['component' => 'mod_nextblocks', 'filearea' => 'attachment', 'itemid' => $id]);
    //if file exists, i.e., a tests file was uploaded, save the hash of the file in the database, else it stays null
    if($pathnamehash != false){
        $DB->set_field('nextblocks', 'testsfilehash', $pathnamehash, ['id' => $id]);
    }
}

/**
 * @param int $id The id of the instance.
 * @return false|mixed The pathnamehash of the file or false if it does not exist.
 * @throws dml_exception
 */
function get_filenamehash(int $id)
{
    global $DB;
    $pathnamehash = $DB->get_field('files', 'pathnamehash', ['component' => 'mod_nextblocks', 'filearea' => 'attachment', 'itemid' => $id]);
    return $pathnamehash;
}

function save_tests_file(object $fromform, int $id)
{
    // Save the tests file with File API.
    // Will need a check for whether the exercise creator selected the file option or not.
    global $PAGE;
    file_save_draft_area_files(
        // The $fromform->attachments property contains the itemid of the draft file area.
        $fromform->attachments,

        // The combination of contextid / component / filearea / itemid
        // form the virtual bucket that file are stored in.
        $PAGE->context->id,
        'mod_nextblocks',
        'attachment',
        $id,
        [
            'subdirs' => 0,
            'maxfiles' => 1,
        ]
    );
}

/**
 * Updates an instance of the mod_nextblocks in the database.
 *
 * Given an object containing all the necessary data (defined in mod_form.php),
 * this function will update an existing instance with new data.
 *
 * @param object                  $moduleinstance An object from the form in mod_form.php.
 * @param mod_nextblocks_mod_form $mform          The form.
 *
 * @return bool True if successful, false otherwise.
 */
function nextblocks_update_instance($moduleinstance, $mform = null) {
    global $DB;

    $moduleinstance->timemodified = time();
    $moduleinstance->id = $moduleinstance->instance;

    return $DB->update_record('nextblocks', $moduleinstance);
}

/**
 * Removes an instance of the mod_nextblocks from the database.
 *
 * @param int $id Id of the module instance.
 *
 * @return bool True if successful, false on failure.
 */
function nextblocks_delete_instance($id) {
    global $DB;

    $exists = $DB->get_record('nextblocks', array('id' => $id));
    if (!$exists) {
        return false;
    }

    $DB->delete_records('nextblocks', array('id' => $id));

    return true;
}

function nextblocks_console_log($output, $with_script_tags = true) {
    $js_code = 'console.log(' . json_encode($output, JSON_HEX_TAG) .
        ');';
    if ($with_script_tags) {
        $js_code = '<script>' . $js_code . '</script>';
    }
    echo $js_code;
}
