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
 * Runestone module main user interface
 *
 * @package    mod_runestone
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require('../../config.php');
require_once("$CFG->dirroot/mod/runestone/lib.php");
require_once("$CFG->dirroot/mod/runestone/locallib.php");

$id       = optional_param('id', 0, PARAM_INT);        // Course module ID
$u        = optional_param('u', 0, PARAM_INT);         // Lesson instance id
$redirect = optional_param('redirect', 0, PARAM_BOOL);

if ($u) {  // Two ways to specify the module
    $runes = $DB->get_record('runestone', array('id'=>$u), '*', MUST_EXIST);
    $cm = get_coursemodule_from_instance('runestone', $runes->id, $runes->course, false, MUST_EXIST);

} else {
    $cm = get_coursemodule_from_id('runestone', $id, 0, false, MUST_EXIST);
    $runes = $DB->get_record('runestone', array('id'=>$cm->instance), '*', MUST_EXIST);
}

$course = $DB->get_record('course', array('id'=>$cm->course), '*', MUST_EXIST);

require_course_login($course, true, $cm);
$context = context_module::instance($cm->id);
require_capability('mod/runestone:view', $context);

$PAGE->set_url('/mod/runestone/view.php', array('id' => $cm->id));

$currenttab = 'view';
$assignment_url = adjust_external_url($currenttab);
$runes->url = $assignment_url . $runes->runes_assign;

$exturl = trim($runes->url);
if (empty($exturl)) {
    runestone_print_header($runes, $cm, $course);
    runestone_print_heading($runes, $cm, '', $course);
    runestone_print_intro($runes, $cm, $course);
    notice(get_string('invalidstoredurl', 'mod_runestone'), new moodle_url('/course/view.php', array('id'=>$cm->course)));
    die;
}
unset($exturl);

runestone_display($runes, $cm, $currenttab, $course);

