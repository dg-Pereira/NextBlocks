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
function nextblocks_supports(string $feature): ?bool {
    switch ($feature) {
    case FEATURE_MOD_INTRO:
        return true;
    case FEATURE_GRADE_HAS_GRADE:
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
 * @param object                       $moduleinstance An object from the form.
 * @param mod_nextblocks_mod_form|null $mform          The form.
 *
 * @return int The id of the newly inserted record.
 * @throws dml_exception
 * @throws moodle_exception
 */
function nextblocks_add_instance(object $moduleinstance, mod_nextblocks_mod_form $mform = null): int {
    global $DB;

    $moduleinstance->timecreated = time();

    $id = (int)$DB->insert_record('nextblocks', $moduleinstance);

    //post form submission stuff here!

    // Form processing and displaying is done here.
    if ($mform->is_cancelled()) {
        // If there is a cancel element on the form, and it was pressed,
        // then the `is_cancelled()` function will return true.
        // You can handle the cancel operation here.

        //redirect to course page
        redirect(new moodle_url('/course/view.php', array('id' => $moduleinstance->course)), 'Cancelled');

    } else if ($fromform = $mform->get_data()) {
        // When the form is submitted, and the data is successfully validated,
        // the `get_data()` function will return the data posted in the form.

        // Save custom blocks
        save_custom_blocks($fromform, $id);

        if(hasTestsFile($fromform)) {
            //save the tests file in File API
            save_tests_file($fromform, $id);

            // In-place replace tests file with json file. better to do this here than in the client, because all clients
            // will have the same tests file, so we don't need to do this for every client.
            // I don't think it can be done before, in save_tests_file, because we don't have access to the file's contents
            // until its saved
            convert_tests_file_to_json($id);

            //save hash of the file in the database for later file retrieval
            save_tests_file_hash($id);
        }

        $record = $DB->get_record('nextblocks', array('id' => $id));
        nextblocks_grade_item_update($record);
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

function nextblocks_update_grades($nextblocks, $userid=0, $nullifnone=true) {
    global $CFG, $DB;
    require_once($CFG->libdir.'/gradelib.php');

    nextblocks_grade_item_update($nextblocks);
    // Updating user's grades is not supported at this time in the logic module.
    return;
}

function nextblocks_grade_item_update($nextblocks, $grades=null): int {
    global $CFG;
    if (!function_exists('grade_update')) { //workaround for buggy PHP versions
        require_once($CFG->libdir.'/gradelib.php');
    }

    if (property_exists($nextblocks, 'cm_id')) { //it may not be always present
        $params = array('itemname'=>$nextblocks->name, 'idnumber'=>$nextblocks->cm_id);
    } else {
        $params = array('itemname'=>$nextblocks->name);
    }

    //from assign/lib.php
    if ($nextblocks->grade > 0) {
        $params['gradetype'] = GRADE_TYPE_VALUE;
        $params['grademax']  = $nextblocks->grade;
        $params['grademin']  = 0;
    } else if ($nextblocks->grade < 0) {
        $params['gradetype'] = GRADE_TYPE_SCALE;
        $params['scaleid']   = -$nextblocks->grade;
    } else {
        $params['gradetype'] = GRADE_TYPE_NONE;
    }

    if ($grades === 'reset') {
        $params['reset'] = true;
        $grades = null;
    } else if (!empty($grades)) {
        // Need to calculate raw grade (Note: $grades has many forms)
        if (is_object($grades)) {
            $grades = array($grades->userid => $grades);
        } else if (array_key_exists('userid', $grades)){
            $grades = array($grades['userid'] => $grades);
        }
        foreach ($grades as $key => $grade) {
            if (!is_array($grade)) {
                $grades[$key] = (array) $grade;
            }
        }
    }

    return grade_update('mod/nextblocks', $nextblocks->course, 'mod', 'nextblocks', $nextblocks->id, 0, $grades, $params);
}

/**
 * @throws dml_exception
 */
function save_custom_blocks(object $fromform, int $id)
{
    global $DB;

    //get block definitions and generators from form
    $blockdefinitions = $fromform->definition;
    $blockgenerators = $fromform->generator;

    if((count($blockdefinitions) == 1 && $blockdefinitions[0] == '') || (count($blockgenerators) == 1 && $blockgenerators[0] == '')){
        return;
    }

    if(count($blockdefinitions) !== count($blockgenerators)){
        throw new dml_exception('Block definitions and generators do not match');
    }

    //save each block definition and generator in the mdl_nextblocks_customblocks table
    foreach ($blockdefinitions as $key => $blockdefinition) {
        $blockgenerator = $blockgenerators[$key];
        $DB->insert_record('nextblocks_customblocks', ['blockdefinition' => $blockdefinition, 'blockgenerator' => $blockgenerator, 'nextblocksid' => $id]);
    }
}

function hasTestsFile(object $fromform): bool
{
    $files = file_get_all_files_in_draftarea($fromform->attachments);
    return count($files) > 0;
}

function file_structure_is_valid(string $file_string): bool {
    // Validate file structure with regular expression
    $exp = "/(\|\s+(_\s+\w+\s*:\s*(\w+\s+)+)*-\s+(\w+\s+)+)+/";
    return preg_match_all($exp, $file_string) !== 1;
}

/**
 * @throws coding_exception
 * @throws dml_exception
 * @throws stored_file_creation_exception
 * @throws file_exception
 * @throws Exception
 */
function convert_tests_file_to_json(int $id)
{
    global $PAGE;
    $fileinfo = array(
        'contextid' => $PAGE->context->id,
        'component' => 'mod_nextblocks',
        'filearea' => 'attachment',
        'itemid' => $id,
        'filepath' => '/',
        'filename' => 'tests.json'
    );

    //create get tests file
    $fs = get_file_storage();
    $hash = get_filenamehash($id);
    $file = $fs->get_file_by_hash($hash);
    $fileString = $file->get_content();

    //convert contents of tests file to json
    $json = parse_tests_file($fileString);
    $new_file = $fs->create_file_from_string($fileinfo, json_encode($json));

    //in-place replace tests file with json file
    $file->replace_file_with($new_file);

    // $file->replace_content_with($fileString); is deprecated :(
    $new_file->delete();
}

/**
 * @throws dml_exception
 */
function save_tests_file_hash(int $id)
{
    global $DB;
    $pathnamehash = $DB->get_field('files', 'pathnamehash', ['component' => 'mod_nextblocks', 'filearea' => 'attachment', 'itemid' => $id]);
    //if file exists, i.e., a tests file was uploaded, save the hash of the file in the database, else it stays null
    if($pathnamehash){
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
    return $DB->get_field('files', 'pathnamehash', ['component' => 'mod_nextblocks', 'filearea' => 'attachment', 'itemid' => $id]);
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

// Maybe in the future write regular expression to validate the tests file
// Consider doing parsing on the server side, when the file is submitted
// TODO a more formal file format description
/**
 * @param String $fileString The contents of the tests file
 *
 * @return array [{}] An array of test cases, each test case containing a list of inputs and an output, in JSON format
 * @throws Exception If the file is not in the correct format
 */
function parse_tests_file(String $fileString): array
{
    try {
        // The returned object has a list of test cases
        $jsonReturn = [];

        // Different test cases are separated by |
        $testCases = explode("|", $fileString);

        // File starts with a |, so the first element of the array is empty
        array_shift($testCases);

        foreach ($testCases as $testCase) {
            // Each test case contains a list of inputs (and an output)
            $thisTestCaseJson = [];
            $thisTestCaseJson['inputs'] = [];

            // The input and output of the test are separated by -
            $inputOutput = explode("-", $testCase);
            $inputs = $inputOutput[0];
            $thisTestCaseJson['output'] = trim($inputOutput[1]); // Remove newlines and add output of test to JSON

            $inputLines = explode("_", $inputs);

            foreach ($inputLines as $input) {
                if (strlen($input) < 3) { // Skip junk elements
                    continue;
                }
                // Each input has multiple lines. The first line is the input name and type, and the rest are
                // the input values for that input
                $inputLines = array_map('trim', explode("\n", $input)); // Remove junk line breaks from every line
                array_shift($inputLines); // Remove the first line (junk)
                array_pop($inputLines); // Remove the last line (junk)

                $inputName = explode(":", $inputLines[0])[0]; // Get the name of the input
                $inputType = trim(explode(":", $inputLines[0])[1]); // Get the type of the input

                $inputValue = [];
                $inputValue[$inputType] = array_slice($inputLines, 1); // Get the input values, skipping the first line

                // Contains the input prompt and a list of input values
                $thisInputJson = [$inputName => $inputValue];
                $thisTestCaseJson['inputs'][] = $thisInputJson; // Add this input to the list of inputs of this test case
            }
            $jsonReturn[] = $thisTestCaseJson; // Add this test case to the list of test cases
        }
        return $jsonReturn;
    } catch (Exception $e) {
        throw new Exception("Error parsing tests file: " . $e->getMessage());
    }
}

/**
 * Updates an instance of the mod_nextblocks in the database.
 *
 * Given an object containing all the necessary data (defined in mod_form.php),
 * this function will update an existing instance with new data.
 *
 * @param object                       $moduleinstance An object from the form in mod_form.php.
 * @param mod_nextblocks_mod_form|null $mform          The form.
 *
 * @return bool True if successful, false otherwise.
 * @throws dml_exception
 */
function nextblocks_update_instance(object $moduleinstance, mod_nextblocks_mod_form $mform = null): bool {
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
 * @throws dml_exception
 */
function nextblocks_delete_instance(int $id): bool {
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

function nextblocks_log($message) {
    error_log($message, 3, "C:\wamp64\logs\php_error.log");
}
