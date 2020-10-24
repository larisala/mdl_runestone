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
* Sets up the tabs used by the runestone lesson pages for teachers.
*
* This file was adapted from the mod/quiz/tabs.php
*
 * @package mod_runestone
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or late
*/

defined('MOODLE_INTERNAL') || die();

global $DB;

if (!isset($currenttab)) {
    $currenttab = '';
}

$id = optional_param('id', 0, PARAM_INT);        // Course module ID
$u  = optional_param('u', 0, PARAM_INT);         // Lesson instance id

if ($u) {  // Two ways to specify the module
    $runes = $DB->get_record('runestone', array('id'=>$u), '*', MUST_EXIST);
    $cm = get_coursemodule_from_instance('runestone', $runes->id, $runes->course, false, MUST_EXIST);

} else {
    $cm = get_coursemodule_from_id('runestone', $id, 0, false, MUST_EXIST);
    $runes = $DB->get_record('runestone', array('id'=>$cm->instance), '*', MUST_EXIST);
}

$course = $DB->get_record('course', array('id'=>$cm->course), '*', MUST_EXIST);
$tabs = $row = $inactive = $activated = array();
$context = context_module::instance($cm->id);

$row[] = new tabobject('view', "$CFG->wwwroot/mod/runestone/view.php?id=$cm->id", get_string('preview', 'mod_runestone'), get_string('previewlesson', 'mod_runestone', format_string($runes->name)));
if (has_capability('mod/runestone:manage', $context)) {
    $row[] = new tabobject('edit', "$CFG->wwwroot/mod/runestone/edit.php?id=$cm->id", get_string('edit', 'mod_runestone'), get_string('edita', 'moodle', format_string($runes->name)));
    $row[] = new tabobject('reports', "$CFG->wwwroot/mod/runestone/report.php?id=$cm->id", get_string('reports', 'mod_runestone'),
            get_string('viewreports', 'mod_runestone', format_string($runes->name)));
    $row[] = new tabobject('grade', "$CFG->wwwroot/mod/runestone/grade.php?id=$cm->id", get_string('grade', 'mod_runestone'), get_string('grade2', 'mod_runestone', format_string($runes->name)));
}

$tabs[] = $row;
print_tabs($tabs, $currenttab, $inactive, $activated);
