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

        //<<------------------------------------------ Timing tab ------------------------------------------>>//

        $mform->addElement('header', 'timing', get_string('nextblockscreatetiming', 'mod_nextblocks'));

        //<<------------------------------------------ Grading tab ------------------------------------------>>//

        $mform->addElement('header', 'grading', get_string('nextblockscreategrading', 'mod_nextblocks'));

        //<<------------------------------------------ Tests tab ------------------------------------------>>//

        $mform->addElement('header', 'tests', get_string('nextblockscreatetests', 'mod_nextblocks'));
        //$mform->setExpanded('tests', true);

        $radioarray=array();
        $radioarray[] = $mform->createElement('radio', 'testsradio', '', get_string('testsradiofile', 'mod_nextblocks'), 1, '');
        $radioarray[] = $mform->createElement('radio', 'testsradio', '', get_string('testsradiotextbox', 'mod_nextblocks'), 2, '');
        $mform->addGroup($radioarray, 'testsradio', get_string('testsradiolabel', 'mod_nextblocks'), '<br>', false);
        $mform->addHelpButton('testsradio', 'testsradio', 'mod_nextblocks');
        $mform->setDefault('testsradio', 1); //both unselected

        // File option

        //$mform->addElement('filepicker', 'testsfile', get_string('testsfilesubmit', 'mod_nextblocks'));
        $mform->addElement(
            'filemanager',
            'testsfile',
            get_string('testsfilesubmit', 'mod_nextblocks'),
            null,
            [
                'subdirs' => 0,
                'areamaxbytes' => 10485760,
                'maxfiles' => 1,
                'accepted_types' => ['text'],
                'return_types' => FILE_INTERNAL | FILE_EXTERNAL,
            ]
        );
        $mform->addHelpButton('testsfile', 'testsfile', 'mod_nextblocks');
        $mform->setType('testsfile', PARAM_FILE);
        $mform->hideIf('testsfile', 'testsradio', 'neq', 1);

        // Text boxes option
        $mform->addElement('text', 'testsinput', get_string('testsinput', 'mod_nextblocks'));
        $mform->addHelpButton('testsinput', 'testsinput', 'mod_nextblocks');
        $mform->setType('testsinput', PARAM_TEXT);
        $mform->hideIf('testsinput', 'testsradio', 'neq', 2);

        $mform->addElement('text', 'testsoutput', get_string('testsoutput', 'mod_nextblocks'));
        $mform->addHelpButton('testsoutput', 'testsoutput', 'mod_nextblocks');
        $mform->setType('testsoutput', PARAM_TEXT);
        $mform->hideIf('testsoutput', 'testsradio', 'neq', 2);

        //<<------------------------------------------ Custom Blocks tab ------------------------------------------>>//

        $mform->addElement('header', 'customblocks', get_string('nextblockscreatecustomblocks', 'mod_nextblocks'));
        $mform->addElement('text', 'customblocksinput', get_string('customblocksinput', 'mod_nextblocks'));
        $mform->addHelpButton('customblocksinput', 'customblocksinput', 'mod_nextblocks');
        $mform->setType('customblocksinput', PARAM_TEXT);

        //<<------------------------------------------ Primitive Restricions tab ------------------------------------------>>//

        $mform->addElement(
            'header', 'primitiverestrictions', get_string('nextblockscreateprimitiverestrictions', 'mod_nextblocks')
        );

        // Add standard elements.
        $this->standard_coursemodule_elements();

        // Add standard buttons.
        $this->add_action_buttons();
    }
}
