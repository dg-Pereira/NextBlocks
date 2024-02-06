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

namespace mod_nextblocks\form;

global $CFG;

use moodleform;

require_once("$CFG->libdir/formslib.php");

class grade_submit extends moodleform {
    public function definition() {
        $mform = $this->_form;

        $mform->addElement('float', 'newgrade', get_string('newgrade', 'mod_nextblocks'));

        $this->add_action_buttons(false);
    }

    // Custom validation should be added here.
    function validation($data, $files) {
        error_log("validation", 3, "C:\wamp64\logs\php_error.log");
        return [];
    }
}