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
 * Form for editing courses_status block instances.
 *
 * @package   block_courses_status
 * @copyright 2017 David Herney Bernal - cirano
 * @license   http://www.gnu.org/copyleft/gpl.courses_status GNU GPL v3 or later
 */

/**
 * Form for editing courses_status block instances.
 *
 * @copyright 2017 David Herney Bernal - cirano
 * @license   http://www.gnu.org/copyleft/gpl.courses_status GNU GPL v3 or later
 */
class block_courses_status_edit_form extends block_edit_form {

    protected function specific_definition($mform) {
        global $CFG;

        // Fields for editing courses_status block title and contents.
        $mform->addElement('header', 'configheader', get_string('blocksettings', 'block'));

        $types = block_courses_status_gettypes();

        foreach($types as $type) {
            if ($type != 'others') {
                $mform->addElement('checkbox', 'config_' . $type, get_string('config_' . $type, 'block_courses_status'));
                $mform->addHelpButton('config_' . $type, 'config_' . $type, 'block_courses_status');
            }
        }

        $mform->addElement('textarea', 'config_others', get_string('config_others', 'block_courses_status'), array('cols' => 40, 'rows' => 15));
        $mform->setType('config_others', PARAM_NOTAGS);
        $mform->addHelpButton('config_others', 'config_others', 'block_courses_status');
    }

}
