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
 * Private runestone module utility functions
 *
 * @package    mod_runestone
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

require_once("$CFG->libdir/filelib.php");
require_once("$CFG->libdir/resourcelib.php");
require_once("$CFG->dirroot/mod/runestone/lib.php");

/**
 * Print page header.
 * @param object $runes
 * @param object $cm
 * @param object $course
 * @return void
 */
function runestone_print_header($runes, $cm, $course) {
    global $PAGE, $OUTPUT;

    $PAGE->set_title($course->shortname.': '.$runes->name);
    $PAGE->set_heading($course->fullname);

    echo $OUTPUT->header();
}

/**
 * Print page heading.
 * @param object $runes
 * @param object $cm
 * @param object $course
 * @param bool $notused This variable is no longer used.
 * @return void
 */
function runestone_print_heading($runes, $cm, $currenttab, $course, $notused = false) {
    global $OUTPUT, $CFG;

    $output = $OUTPUT->heading(format_string($runes->name), 2);

    $context = context_module::instance($cm->id);

    if (has_capability('mod/runestone:manage', $context)) {
        if (!empty($currenttab)) {
            ob_start();
            include($CFG->dirroot.'/mod/runestone/tabs.php');
            $output .= ob_get_contents();
            ob_end_clean();
        }
    }

    echo $output;
}

/**
 * Print runestone lesson introduction.
 * @param object $runes
 * @param object $cm
 * @param object $course
 * @param bool $ignoresettings print even if not specified in modedit
 * @return void
 */
function runestone_print_intro($runes, $cm, $course, $ignoresettings=false) {
    global $OUTPUT;

    if ($ignoresettings or $runes->printintro) {
        if (trim(strip_tags($runes->intro))) {
            echo $OUTPUT->box_start('mod_introbox', 'urlintro');
            echo format_module_intro('url', $runes, $cm->id);
            echo $OUTPUT->box_end();
        }
    }
}

/**
 * Display embedded runestone url.
 * @param object $runes
 * @param object $cm
 * @param object $course
 * @return does not return
 */
function runestone_display($runes, $cm, $currenttab, $course) {
    global $CFG, $PAGE, $OUTPUT;

    $fullurl  = $runes->url;

    $mimetype = resourcelib_guess_url_mimetype($fullurl);
    $title    = $runes->name;

    $link = html_writer::tag('a', $fullurl, array('href'=>str_replace('&amp;', '&', $fullurl)));
    $clicktoopen = get_string('clicktoopen', 'mod_runestone', $link);
    $moodleurl = new moodle_url($fullurl);

    $extension = resourcelib_get_extension($fullurl);

    $mediamanager = core_media_manager::instance($PAGE);
    $embedoptions = array(
        core_media_manager::OPTION_TRUSTED => true,
        core_media_manager::OPTION_BLOCK => true
    );

    if (in_array($mimetype, array('image/gif','image/jpeg','image/png'))) {  // It's an image
        $code = resourcelib_embed_image($fullurl, $title);

    } else if ($mediamanager->can_embed_url($moodleurl, $embedoptions)) {
        // Media (audio/video) file.
        $code = $mediamanager->embed_url($moodleurl, $title, 0, 0, $embedoptions);

    } else {
        // anything else - just try object tag enlarged as much as possible
        $code = resourcelib_embed_general($fullurl, $title, $clicktoopen, $mimetype);
    }

    runestone_print_header($runes, $cm, $course);
    runestone_print_heading($runes, $cm, $currenttab, $course);

    echo $code;

    runestone_print_intro($runes, $cm, $course);

    echo $OUTPUT->footer();
    die;
}

/**
 * Deletes a runestone lesson from the database as well as any associated records.
 * @final
 * @return bool
 */
function delete() {
    global $DB;

    $DB->delete_records("runestone", array("id" => $this->runestone->id));

    return true;
}

/**
 * Returns runestone courses
 * @final
 * @return array
 */
function get_runestone_courses() {
    global $CFG;

    // We do not create courses here intentionally because it requires full sync and is slow.
    if (!get_config('mod_runestone', 'dbtype')) {
        return null;
    }

    if (!$extdb = db_init()) {
        return null;
    }

    $sql = "SELECT id, course_name FROM courses";
    $courses = $extdb->getAssoc($sql);
    $extdb->Close();

    return $courses;
}

/**
 * Returns runestone assignments of a course
 * @param int $course_id
 * @return array
 */
function get_runestone_assignments($course_id) {
    global $CFG;

    // We do not create courses here intentionally because it requires full sync and is slow.
    if (!get_config('mod_runestone', 'dbtype')) {
        return NULL;
    }

    if (!$extdb = db_init()) {
        return NULL;
    }

    $sql = "SELECT id, name FROM assignments WHERE course = $course_id";
    $assignments = $extdb->getAssoc($sql);
    print_object($assignments);

    $extdb->Close();

    return $assignments;
}

/**
 * Get the runestone assignment id given url
 * @param object $url
 * @return int
 */
function get_external_id($url) {
    $exp = '/[?&]assignment_id=/';
    if (preg_match($exp, $url)) {
        $result = preg_split($exp, $url);
        return (int)$result[1];
    }
    return null;
}

/**
 * Adjust the form of external runestone url
 * @param object $url
 * @return string
 */
function adjust_external_url($type) {
    $url = get_config('mod_runestone', 'externalurl');
    if (strpos($url, '/runestone') === false) {
        $url .= '/runestone/';
    } else if (substr($url, -1) !== '/') {
        $url .= '/';
    } else if (substr($url, -2) === '//') {
        $url = substr($url, 0, -1);
    }

    switch ($type) {
        case 'view':
            $url .= 'assignments/doAssignment?assignment_id=';
            break;
        case 'edit':
            $url .= 'admin/assignments';
            break;
        case 'reports':
            $url .= 'dashboard/index';
            break;
        case 'grade':
            $url .= 'admin/grading';
            break;
    }

    return $url;
}
