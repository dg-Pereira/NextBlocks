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
global $CFG;

/**
 * The main mod_nextblocks configuration form.
 *
 * @package     mod_nextblocks
 * @copyright   2023 Duarte Pereira<dg.pereira@campus.fct.unl.pt>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/course/moodleform_mod.php');

/**
 * Module instance settings form.
 *
 * @package     mod_nextblocks
 * @copyright   2023 Duarte Pereira<dg.pereira@campus.fct.unl.pt>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_nextblocks_mod_form extends moodleform_mod {

    /**
     * Defines forms elements
     *
     * @throws coding_exception
     */
    public function definition() {
        global $CFG;

        $mform = $this->_form;

        // Adding the "general" fieldset, where all the common settings are shown.
        $mform->addElement('header', 'general', get_string('general', 'form'));

        // Adding the standard "name" field.
        $mform->addElement('text', 'name', get_string('nextblocksname', 'mod_nextblocks'), array('size' => '64'));

        if (!empty($CFG->formatstringstriptags)) {
            $mform->setType('name', PARAM_TEXT);
        } else {
            $mform->setType('name', PARAM_CLEANHTML);
        }

        $mform->addRule('name', null, 'required', null, 'client');
        $mform->addRule('name', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');
        $mform->addHelpButton('name', 'nextblocksname', 'mod_nextblocks');

        // Adding the standard "intro" and "introformat" fields.
        if ($CFG->branch >= 29) {
            $this->standard_intro_elements();

        } else {
            $this->add_intro_editor();
        }

        // Adding the rest of mod_nextblocks settings, spreading all them into this fieldset
        // ... or adding more fieldsets ('header' elements) if needed for better logic.

        //<<------------------------------------------ General tab ------------------------------------------>>//

        //isEval checkbox
        //if second parameter of addElement is a colunm name of the table, the value of the checkbox will be saved in that column
        //$mform->addElement('advcheckbox', 'iseval', get_string('iseval', 'mod_nextblocks'));
        //$mform->addHelpButton('iseval', 'iseval', 'mod_nextblocks');

        //<<------------------------------------------ Tests tab ------------------------------------------>>//

        $mform->addElement('header', 'tests', get_string('nextblockscreatetests', 'mod_nextblocks'));

        $mform->addElement(
            'filemanager',
            'attachments',
            get_string('testsfilesubmit', 'mod_nextblocks'),
            null,
            [
                'subdirs' => 0,
                'areamaxbytes' => 10485760,
                'maxfiles' => 1,
                'accepted_types' => ['txt'],
                'return_types' => FILE_INTERNAL | FILE_EXTERNAL,
            ]
        );
        $mform->addHelpButton('attachments', 'testsfile', 'mod_nextblocks');
        $mform->setType('testsfile', PARAM_FILE);

        //<<------------------------------------------ Custom Blocks tab ------------------------------------------>>//

        function addCustomBlocksInputs($mform) {
            $mform->addElement('textarea', 'blockdefinition', get_string("blockdefinition", "mod_nextblocks"),
                'wrap="virtual" rows="8" cols="80"');
            $mform->addHelpButton('blockdefinition', 'blockdefinition', 'mod_nextblocks');
            $mform->setType('blockdefinition', PARAM_TEXT);
            $mform->addElement('textarea', 'blockgenerator', get_string("blockgenerator", "mod_nextblocks"),
                'wrap="virtual" rows="8" cols="80"');
            $mform->addHelpButton('blockgenerator', 'blockgenerator', 'mod_nextblocks');
            $mform->setType('blockgenerator', PARAM_TEXT);
        }

        $mform->addElement('header', 'customblocks', get_string('nextblockscreatecustomblocks', 'mod_nextblocks'));
        $mform->addElement('html', get_string('customblockstext', 'mod_nextblocks'));

        $repeatarray = [
            $mform->createElement('textarea', 'definition', get_string('blockdefinition', 'mod_nextblocks'), 'wrap="virtual" rows="8" cols="80"'),
            $mform->createElement('textarea', 'generator', get_string('blockgenerator', 'mod_nextblocks'), 'wrap="virtual" rows="8" cols="80"'),
            $mform->createElement('hidden', 'optionid', 0),
            $mform->createElement('submit', 'delete', get_string('deletestr', 'mod_nextblocks'), [], false),
        ];

        $repeatoptions = [
            'definition' => [
                'type' => PARAM_TEXT,

            ],
            'generator' => [
                'type' => PARAM_TEXT,
            ],
            'optionid' => [
                'type' => PARAM_INT,
            ],
        ];

        $this->repeat_elements(
            $repeatarray,
            1,
            $repeatoptions,
            'option_repeats',
            'option_add_fields',
            1,
            null,
            true,
            'delete',
        );

        //addCustomBlocksInputs($mform);
        //$mform->addElement('button', 'addanothercustomblock', get_string('addanothercustomblock', 'mod_nextblocks'));

        //<<------------------------------------------ Primitive Restricions tab ------------------------------------------>>//

        //$mform->addElement(
        //    'header', 'primitiverestrictions', get_string('nextblockscreateprimitiverestrictions', 'mod_nextblocks')
        //);

        //<<------------------------------------------ Timing tab ------------------------------------------>>//

        //$mform->addElement('header', 'timing', get_string('nextblockscreatetiming', 'mod_nextblocks'));

        //<<------------------------------------------ Submissions tab ------------------------------------------>>//

        $mform->addElement(
            'header', 'submissions', get_string('nextblockscreatesubmissions', 'mod_nextblocks')
        );

        $mform->addElement(
            'advcheckbox', 'multiplesubmissions', get_string('multiplesubmissions', 'mod_nextblocks'),
        );
        $mform->addElement('text', 'maxsubmissions', get_string('howmanysubmissions', 'mod_nextblocks'));
        $mform->setType('maxsubmissions', PARAM_INT);
        $mform->hideIf('maxsubmissions', 'multiplesubmissions', 'neq', 1);


        //<<------------------------------------------ Grading tab ------------------------------------------>>//

        $this->standard_grading_coursemodule_elements();

        $this->standard_coursemodule_elements();
        $this->apply_admin_defaults();

        // Add standard buttons.
        $this->add_action_buttons();
    }

    function validation($data, $files): array
    {
        global $USER;
        $errors = parent::validation($data, $files);
        $usercontext = context_user::instance($USER->id);
        $fs = get_file_storage();
        try {
            $files = $fs->get_area_files($usercontext->id, 'user', 'draft', $data['attachments'], 'id', false);
        } catch (coding_exception $e) {

        }
        if (count($files) === 1) {
            $file = reset($files);
            $fileString = $file->get_content();
            if (file_structure_is_valid($fileString)) {
                $errors['attachments'] = get_string('invalidfilestructure', 'mod_nextblocks');
            }
        }

        return $errors;
    }
}
