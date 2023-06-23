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

require_once $CFG->dirroot . '/blocks/courses_status/locallib.php';

/**
 * Courses status block.
 *
 * @since     3.1
 * @package   block_courses_status
 * @copyright 2017 David Herney Bernal - cirano
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

class block_courses_status extends block_base {
    function init() {
        $this->title = get_string('pluginname', 'block_courses_status');
    }

    function applicable_formats() {
        return array('all' => true);
    }

    /**
     * All multiple instances of this block
     * @return bool Returns false
     */
    function instance_allow_multiple() {
        return false;
    }

    function get_content() {
        if ($this->content !== NULL) {
            return $this->content;
        }
        $this->content = (object)['text' => '', 'footer' => ''];

        global $CFG, $USER, $DB;

        $text = '';

        if (!is_object($this->config)){
            $this->config = new stdClass();
        }

        $types = block_courses_status_gettypes();

        foreach($types as $type) {
            if (property_exists($this->config, $type) && $this->config->$type) {
                $url = $CFG->wwwroot . '/blocks/courses_status/view.php?t=' . $type;

                if ($type == 'others') {
                    $url .= '&id=' . $this->instance->id;
                }

                $text .= '<a href="' . $url . '" class="bcs_box bcs_' . $type . '">';
                $text .= '<div><div class="bcs_icon"></div><div class="bcs_label">' . get_string('label_' . $type, 'block_courses_status') . '</div></div>';
                $text .= '</a>';
            }
        }

        $this->content->text = $text;
        return $this->content;
    }

}
