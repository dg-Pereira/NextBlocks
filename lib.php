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

    $id = $DB->insert_record('nextblocks', $moduleinstance);

    //fazer coisas de depois submissao do form aqui!!!

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

        //save the tests file
        save_tests_file($fromform, $id);
    } else {
        // This branch is executed if the form is submitted but the data doesn't
        // validate and the form should be redisplayed or on the first display of the form.

        //send log to C:\wamp64\logs\php_error.log
        nextblocks_alert('33');
        // Set anydefault data (if any).
        //$this->set_data($toform);

        // Display the form.
        //$mform->display();
    }

    return $id;
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

function nextblocks_alert($msg) {
    echo "<script type='text/javascript'>alert('$msg');</script>";
}

/**
 * Serve the files from the myplugin file areas.
 *
 * @param stdClass $course the course object
 * @param stdClass $cm the course module object
 * @param stdClass $context the context
 * @param string $filearea the name of the file area
 * @param array $args extra arguments (itemid, path)
 * @param bool $forcedownload whether or not force download
 * @param array $options additional options affecting the file serving
 * @return bool false if the file not found, just send the file otherwise and do not return anything
 */
function mod_nextblocks_pluginfile(
    $course,
    $cm,
    $context,
    string $filearea,
    array $args,
    bool $forcedownload,
    array $options = []
): bool
{
    global $DB;

    // Check the contextlevel is as expected - if your plugin is a block, this becomes CONTEXT_BLOCK, etc.
    if ($context->contextlevel != CONTEXT_MODULE) {
        nextblocks_console_log(0);
        return false;
    }

    // Make sure the filearea is one of those used by the plugin.
    if ($filearea !== 'attachment' && $filearea !== 'draft') {
        nextblocks_console_log(1);
        return false;
    }

    // Make sure the user is logged in and has access to the module (plugins that are not course modules should leave out the 'cm' part).
    require_login($course, true, $cm);

    // Check the relevant capabilities - these may vary depending on the filearea being accessed.
    if (!has_capability('mod/nextblocks:view', $context)) {
        nextblocks_console_log(2);
        return false;
    }

    // The args is an array containing [itemid, path].
    // Fetch the itemid from the path.
    $itemid = array_shift($args);

    /*
    // The itemid can be used to check access to a record, and ensure that the
    // record belongs to the specifeid context. For example:
    if ($filearea === 'attachment') {
        $post = $DB->get_record('nextblocks_posts', ['id' => $itemid]);
        if ($post->myplugin !== $context->instanceid) {
            // This post does not belong to the requested context.
            return false;
        }

        // You may want to perform additional checks here, for example:
        // - ensure that if the record relates to a grouped activity, that this
        //   user has access to it
        // - check whether the record is hidden
        // - check whether the user is allowed to see the record for some other
        //   reason.

        // If, for any reason, the user does not hve access, you can return
        // false here.
    }

   */

    // For a plugin which does not specify the itemid, you may want to use the following to keep your code consistent:
    // $itemid = null;

    // Extract the filename / filepath from the $args array.
    $filename = array_pop($args); // The last item in the $args array.
    if (empty($args)) {
        // $args is empty => the path is '/'.
        $filepath = '/';
    } else {
        // $args contains the remaining elements of the filepath.
        $filepath = '/' . implode('/', $args) . '/';
    }

    // Retrieve the file from the Files API.
    $fs = get_file_storage();
    $file = $fs->get_file($context->id, 'mod_nextblocks', $filearea, $itemid, $filepath, $filename);
    if (!$file) {
        nextblocks_console_log(3);
        // The file does not exist.
        return false;
    }

    // We can now send the file back to the browser - in this case with a cache lifetime of 1 day and no filtering.
    send_stored_file($file, DAY_SECS, 0, $forcedownload, $options);
}