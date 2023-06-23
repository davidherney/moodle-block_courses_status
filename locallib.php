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
 * Common functions.
 *
 * @package   block_courses_status
 * @copyright 2017 David Herney Bernal - cirano
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

function block_courses_status_getcourses($type) {
    global $CFG, $DB, $USER, $SITE;

    $courses = array();

    if ($type == 'showall') {
        $courses = $DB->get_records('course', null, 'sortorder ASC', 'id, shortname, fullname, visible, showgrades, startdate');
    }
    else if ($type == 'others') {
        $id = optional_param('id', 0, PARAM_INT);
        if ($id) {
            $instance = $DB->get_record('block_instances', array('id' => $id));
            $instance = block_instance('courses_status', $instance);

            if (property_exists($instance->config, 'others')) {
                $shortnames = explode("\n", $instance->config->others);
                foreach($shortnames as $key => $one) {
                    $shortnames[$key] = trim($one);
                }

                $courses = $DB->get_records_list('course', 'shortname', $shortnames, 'sortorder ASC', 'id, shortname, fullname, visible, showgrades, startdate');
            }
        }
    }
    else if ($type == 'open') {
        $sql = "SELECT c.id, c.shortname, c.fullname, c.visible, c.showgrades, c.startdate
                    FROM {course} AS c
                    INNER JOIN {enrol} AS e ON e.enrol = 'guest' AND e.status = '0' AND e.courseid = c.id
                    WHERE c.enablecompletion = 1
                    ORDER BY c.sortorder ASC";
        $courses = $DB->get_records_sql($sql);
    }
    else if ($type == 'ended') {
        $sql = "SELECT c.id, c.shortname, c.fullname, c.visible, c.showgrades, c.startdate
                    FROM {course} AS c
                    INNER JOIN {course_completions} AS cc ON cc.userid = ? AND cc.timecompleted IS NOT NULL AND cc.course = c.id
                    WHERE c.enablecompletion = 1
                    ORDER BY c.sortorder ASC";
        $courses = $DB->get_records_sql($sql, array($USER->id));
    }
    else if ($type == 'inprogress') {
        $rolestudent = 5;
        $sql = "SELECT DISTINCT c.id, c.shortname, c.fullname, c.visible, c.showgrades, c.startdate
                    FROM {course} AS c
                    INNER JOIN {context} AS cx ON cx.instanceid = c.id AND cx.contextlevel = ?
                    INNER JOIN {role_assignments} AS ra ON ra.roleid = ? AND ra.contextid = cx.id AND ra.userid = ?
                    LEFT JOIN {course_completions} AS cc ON cc.userid = ? AND cc.course = c.id
                    LEFT JOIN {user_lastaccess} AS ul ON ul.userid = ? AND ul.courseid = c.id
                    WHERE ul.id IS NOT NULL AND cc.timecompleted IS NULL AND c.enablecompletion = 1
                    ORDER BY c.sortorder ASC";
        $courses = $DB->get_records_sql($sql, array(CONTEXT_COURSE, $rolestudent, $USER->id, $USER->id, $USER->id));
    }
    else {
        // Enrolled.
        $courses = enrol_get_users_courses($USER->id, false,
                                    'id, shortname, fullname, visible, showgrades, startdate, enablecompletion', 'sortorder ASC');

        if ($type == 'coming') {
            $coming = array();
            foreach ($courses as $course) {
                if ($course->startdate > time() && $course->enablecompletion == 1) {
                    $coming[] = $course;
                }
            }

            $courses = $coming;
        }
        else if ($type == 'pending') {
            $pending = array();
            foreach ($courses as $course) {
                if ($course->startdate <= time() && $course->enablecompletion == 1) {
                    $count = $DB->count_records('user_lastaccess', array('courseid' => $course->id, 'userid' => $USER->id));
                    if ($count == 0) {
                        $pending[] = $course;
                    }
                }
            }

            $courses = $pending;
        }
    }

    foreach($courses as $course) {
        $criteria = $DB->get_record('course_completion_criteria', array('course' => $course->id, 'criteriatype' => COMPLETION_CRITERIA_TYPE_DATE));
        if ($criteria && $criteria->timeend) {
            $course->enddate = $criteria->timeend;
        }
    }

    return $courses;
}

function block_courses_status_gettypes() {
    return array('showall', 'enrolled', 'ended', 'inprogress', 'pending', 'coming', 'open', 'others');
}

class block_courses_status_datapanel {

    /**
     * @var string Value to use for the id attribute of the panel
     */
    public $id = null;

    /**
     * @var array Attributes of HTML attributes for the main <div> element
     */
    public $attributes = array();

    /**
     * @var string Panel title
     *
     * Example of usage:
     * $t->head = 'Course 1';
     */
    public $head;

    /**
     * @var array|string Array of items to print like a <ul> list or string with content
     *
     * Example of usage with array:
     * $t->data = array('Course'=>'Course 1', 'Grade'=>98);
     *
     * Example with string
     * $t->data = 'The grade for Course 1 is 98';
     */
    public $data;

    /**
     * @var string Panel footer
     */
    public $foot;

    /**
     * Constructor
     */
    public function __construct() {
        $this->attributes['class'] = 'block_courses_status_datapanel';
    }

    public function print_content () {

        if (!isset($this->attributes['id']) && !empty($this->id)) {
            $this->attributes['id'] = $this->id;
        }

        echo html_writer::start_div('panel panel-default', $this->attributes);

        if (!empty($this->head)) {
            echo html_writer::div($this->head, 'panel-heading');
        }

        if (is_array($this->data)) {
            echo html_writer::start_tag('ul', array('class'=>'list-group'));

            foreach($this->data as $key=>$content) {
                echo html_writer::start_tag('li', array('class'=>'list-group-item'));

                if (!is_numeric($key)) {
                    echo html_writer::tag('label', $key, array('class'=>'label label-primary'));
                }

                echo html_writer::tag('span', $content);

                echo html_writer::end_tag('li');
            }

            echo html_writer::end_tag('ul');
        }
        else {
            echo html_writer::div($this->data, 'panel-body');
        }

        if (!empty($this->foot)) {
            echo html_writer::div($this->foot, 'panel-footer');
        }

        echo html_writer::end_div();
    }
}
