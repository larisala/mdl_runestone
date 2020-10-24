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
 * Displays the Runestone assignment grading module.
 *
 * @package mod_runestone
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or late
 **/

require_once('../../config.php');
require_once($CFG->dirroot.'/mod/runestone/locallib.php');

$id = required_param('id', PARAM_INT);    // Course Module ID

$cm = get_coursemodule_from_id('runestone', $id, 0, false, MUST_EXIST);
$runes = $DB->get_record('runestone', array('id'=>$cm->instance), '*', MUST_EXIST);
$course = $DB->get_record('course', array('id'=>$cm->course), '*', MUST_EXIST);

require_login($course, false, $cm);

$context = context_module::instance($cm->id);
require_capability('mod/lesson:manage', $context);

$url = new moodle_url('/mod/runestone/grade.php', array('id'=>$id));

$PAGE->set_url($url);
$currenttab = 'grade';

$assignment_url = adjust_external_url($currenttab);
$runes->url = $assignment_url;
runestone_display($runes, $cm, $currenttab, $course);

/// Finish the page
echo $OUTPUT->footer();
