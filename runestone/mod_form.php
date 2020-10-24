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
 * Runestone Lesson configuration form
 *
 * @package    mod_runestone
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

require_once ($CFG->dirroot.'/course/moodleform_mod.php');
require_once($CFG->dirroot.'/mod/runestone/locallib.php');
require_once($CFG->libdir.'/formslib.php');
require_once($CFG->libdir.'/completionlib.php');

// Get data dynamically based on the selection from the dropdown
$PAGE->requires->js(new moodle_url('/mod/runestone/js/onchange.js'));

class mod_runestone_mod_form extends moodleform_mod {
    function definition() {
        global $CFG, $DB;
        $mform = $this->_form;

        //-------------------------------------------------------
        $mform->addElement('header', 'general', get_string('general', 'form'));
        $mform->addElement('text', 'name', get_string('name'), array('size'=>'48'));
        if (!empty($CFG->formatstringstriptags)) {
            $mform->setType('name', PARAM_TEXT);
        } else {
            $mform->setType('name', PARAM_CLEANHTML);
        }

        $mform->addRule('name', null, 'required', null, 'client');
        $mform->addRule('name', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');

        $courses = get_runestone_courses();
        $assignments = array(get_string('assignment_default', 'mod_runestone'));
        if (empty($courses)) {
            $courses = array(get_string('empty_courses', 'mod_runestone'));
        } else {
            $assignments = get_runestone_assignments(key($courses));
            if (empty($assignments)) {
                $assignments = array(get_string('empty_assignments', 'mod_runestone'));
            }
        }

        $mform->addElement('select', 'runes_course', get_string('course', 'mod_runestone'), $courses);
        $mform->setDefault('runes_course', key($courses));
        $mform->addHelpButton('runes_course', 'select_course', 'mod_runestone');

        $mform->addElement('select', 'runes_assign', get_string('assignment', 'mod_runestone'), $assignments);
        $mform->addRule('runes_assign', null, 'required', null, 'client');
        $mform->addRule('runes_assign', get_string('assignment_error',  'mod_runestone'), 'nonzero', null, 'client');

        $this->standard_intro_elements();
        $element = $mform->getElement('introeditor');
        $attributes = $element->getAttributes();
        $attributes['rows'] = 5;
        $element->setAttributes($attributes);

        $mform->addElement('checkbox', 'printintro', get_string('printintro', 'mod_runestone'));
        $mform->setDefault('printintro', true);

        //-------------------------------------------------------
        $this->standard_coursemodule_elements();

        //-------------------------------------------------------
        $this->add_action_buttons();
    }

    function get_data(){
        global $DB;

        $data = parent::get_data();

        if (!empty($data)) {
            $mform =& $this->_form;

            // Add the assignmentid properly to the $data object.
            if(!empty($mform->_submitValues['runes_assign'])) {
                $data->runes_assign = $mform->_submitValues['runes_assign'];
            }

        }

        return $data;
    }

}
