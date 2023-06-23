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
 * Display the course list
 *
 * @package   block_courses_status
 * @copyright 2017 David Herney Bernal - cirano
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL
 */

require_once '../../config.php';
require_once $CFG->libdir.'/adminlib.php';
require_once 'locallib.php';

$sort         = optional_param('sort', 'c.startdate', PARAM_ALPHA);
$dir          = optional_param('dir', 'DESC', PARAM_ALPHA);
$page         = optional_param('spage', 0, PARAM_INT);
$perpage      = optional_param('perpage', 10, PARAM_INT);        // how many per page
$type         = optional_param('t', 'enrolled', PARAM_ALPHA);

$systemcontext = context_system::instance();

require_login();

// Only the available types
$types = block_courses_status_gettypes();

if (!in_array($type, $types)) {
    $type = 'enrolled';
}

$PAGE->set_url('/blocks/courses_status/view.php');
$PAGE->set_context($systemcontext);

$s_title = get_string('label_' . $type, 'block_courses_status');
$PAGE->set_title($s_title);
$PAGE->set_heading(get_string('pluginname', 'block_courses_status'));
$PAGE->set_pagelayout('mydashboard');

echo $OUTPUT->header();

$courses = block_courses_status_getcourses($type);

echo $OUTPUT->heading($s_title);

$printsomeone = false;

if($courses && is_array($courses) && count($courses) > 0) {

    $dateformat = get_string('strftimedate', 'langconfig');

    $t_detail       = get_string('details', 'block_courses_status');
    $t_startdate    = get_string('startdate');
    $t_enddate      = get_string('enddate', 'block_courses_status');
    $t_closed       = get_string('closed', 'block_courses_status');

    echo html_writer::start_tag('div', array('class'=>'courselist'));
    foreach($courses as $course){

        if ($course->id == $SITE->id) {
            continue;
        }

        $url = new moodle_url('/course/view.php', array('id' => $course->id));
        //$details = html_writer::start_tag('div', array('class'=>'details'));

        $context = context_course::instance($course->id, MUST_EXIST);
        if (!$course->visible) {
            //$details .= html_writer::tag('strong', $t_closed);

            if(has_capability('moodle/course:viewhiddencourses', $context)) {
                $fullname = html_writer::link($url, $course->fullname, array('class' => 'dimmed'));
            }
            else {
                if ($type == 'showall' || $type == 'open' || $type == 'others') {
                    continue;
                }
                else {
                    $fullname = $course->fullname;
                }
            }
        }
        else {
            $fullname = html_writer::link($url, $course->fullname);
        }


        // Display course overview files.
        $contentimages = $contentfiles = '';
        $courseinlist = new core_course_list_element($course);
        foreach ($courseinlist->get_course_overviewfiles() as $file) {
            $isimage = $file->is_valid_image();
            $url = file_encode_url("$CFG->wwwroot/pluginfile.php",
                    '/'. $file->get_contextid(). '/'. $file->get_component(). '/'.
                    $file->get_filearea(). $file->get_filepath(). $file->get_filename(), !$isimage);
            if ($isimage) {
                $contentimages .= html_writer::tag('div',
                        html_writer::empty_tag('img', array('src' => $url)),
                        array('class' => 'courseimage'));
            } else {
                $image = $OUTPUT->pix_icon(file_file_icon($file, 24), $file->get_filename(), 'moodle');
                $filename = html_writer::tag('span', $image, array('class' => 'fp-icon')).
                        html_writer::tag('span', $file->get_filename(), array('class' => 'fp-filename'));
                $contentfiles .= html_writer::tag('span',
                        html_writer::link($url, $filename),
                        array('class' => 'coursefile fp-filename-icon'));
            }
        }

        $data = array ();
        $data[] = $contentimages. $contentfiles;
        $data[] = $fullname;

//         if (property_exists($course, 'startdate')) {
//             $details .= html_writer::tag('label', $t_startdate);
//             $details .= html_writer::tag('span', userdate($course->startdate, $dateformat));
//         }
//
//         if (property_exists($course, 'enddate')) {
//             $details .= html_writer::tag('label', $t_enddate);
//             $details .= html_writer::tag('span', userdate($course->enddate, $dateformat));
//         }
//
//         $details .= html_writer::end_tag('div');


        //$data[] = print_collapsible_region($details, '', 'details_' . $course->id, $t_detail, '', true, true);

        $panel = new block_courses_status_datapanel();
        $panel->data = $data;

        $panel->print_content();
        $printsomeone = true;
    }

    echo html_writer::end_tag('div');
}

if (!$printsomeone) {
    echo $OUTPUT->notification(get_string('not_courses_' . $type, 'block_courses_status'));
}

echo $OUTPUT->footer();
