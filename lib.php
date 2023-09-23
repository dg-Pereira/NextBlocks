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

    return $id;
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

function console_log($output, $with_script_tags = true) {
    $js_code = 'console.log(' . json_encode($output, JSON_HEX_TAG) .
        ');';
    if ($with_script_tags) {
        $js_code = '<script>' . $js_code . '</script>';
    }
    echo $js_code;
}
