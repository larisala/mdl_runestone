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
 * Standard library of functions and constants for runestone lessons
 *
 * @package mod_runestone
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 **/

/**
 * List of features supported in Runestone module
 * @param string $feature FEATURE_xx constant for requested feature
 * @return mixed True if module supports feature, false if not, null if doesn't know
 */
function runestone_supports($feature) {
    switch($feature) {
        case FEATURE_GROUPS:                  return false;
        case FEATURE_GROUPINGS:               return false;
        case FEATURE_MOD_INTRO:               return true;
        case FEATURE_COMPLETION_TRACKS_VIEWS: return true;
        case FEATURE_GRADE_HAS_GRADE:         return true;
        case FEATURE_GRADE_OUTCOMES:          return false;
        case FEATURE_SHOW_DESCRIPTION:        return true;

        default: return null;
    }
}

/**
 * Given an object containing all the necessary data,
 * (defined by the form in mod_form.php) this function
 * will create a new instance and return the id number
 * of the new instance.
 *
 * @global object
 * @global object
 * @param object $runestone post data from the form
 * @return int
 **/
function runestone_add_instance($data, $mform) {
    global $CFG, $DB;

    require_once($CFG->dirroot.'/mod/runestone/locallib.php');

    $data->printintro = (int)!empty($data->printintro);

    $data->id = $DB->insert_record('runestone', $data);

    $completiontimeexpected = !empty($data->completionexpected) ? $data->completionexpected : null;
    \core_completion\api::update_completion_date_event($data->coursemodule, 'runestone', $data->id, $completiontimeexpected);

    runestone_grade_item_update($data);

    return $data->id;
}

/**
 * Given an object containing all the necessary data,
 * (defined by the form in mod_form.php) this function
 * will update an existing instance with new data.
 *
 * @global object
 * @param object $runestone post data from the form
 * @return boolean
 **/
function runestone_update_instance($data, $mform) {
    global $DB;

    $data->id = $data->instance;
    $cmid = $data->coursemodule;

    $context = context_module::instance($cmid);

    $DB->update_record("runestone", $data);

    runestone_grade_item_update($data);

    return true;
}

/**
 * Given an ID of an instance of this module,
 * this function will permanently delete the instance
 * and any data that depends on it.
 *
 * @global object
 * @param int $id
 * @return bool
 */
function runestone_delete_instance($id) {
    global $DB, $CFG;
    $DB->set_debug(true);
    require_once($CFG->dirroot . '/mod/runestone/locallib.php');

    $DB->delete_records("runestone", array("id" => $id));

    $DB->set_debug(false);
    return true;
}

/**
 * Return grade for given user or all users.
 *
 * @global stdClass
 * @global object
 * @param int $runestoneid id of runestone
 * @param int $userid optional user id, 0 means all users
 * @return array array of grades, false if none
 */
function runestone_get_user_grades($runestoneid, $userid=0) {
    global $CFG, $DB;

    if (empty($userid)) {
        return $DB->get_records('runestone_grades', array('lessonid'=>$runestoneid));
    }

    return $DB->get_records('runestone_grades', array('lessonid'=>$runestoneid, 'userid'=>$userid));
}

/**
 * Update grades in central gradebook
 *
 * @category grade
 */
function runestone_update_grades() {
    global $CFG, $DB;
    require_once($CFG->libdir.'/gradelib.php');

    $lessons = $DB->get_records('runestone', null, '');
    foreach ($lessons as $lesson) {
        $grades = runestone_get_user_grades($lesson->id);
        foreach ($grades as $grade) {
            $usr_grade = new stdClass();
            $usr_grade->userid   = $grade->userid;
            $usr_grade->rawgrade = $grade->grade;
            runestone_grade_item_update($lesson, $usr_grade);
        }
    }
}

/**
 * Create grade item for given runestone
 *
 * @category grade
 * @param object $runestone object
 * @param array|object $grades optional array/object of grade(s); 'reset' means reset grades in gradebook
 * @return int 0 if ok, error code otherwise
 */
function runestone_grade_item_update($runestone, $grades=null) {
    global $CFG;
    if (!function_exists('grade_update')) { //workaround for buggy PHP versions
        require_once($CFG->libdir.'/gradelib.php');
    }

    $params = array('itemname'=>$runestone->name);

    if ($grades  === 'reset') {
        $params['reset'] = true;
        $grades = NULL;
    }

    return grade_update('mod/runestone', $runestone->course, 'mod', 'runestone', $runestone->id, 0, $grades, $params);
}

/**
 * Mark the activity completed (if required) and trigger the course_module_viewed event.
 *
 * @param  stdClass $url        url object
 * @param  stdClass $course     course object
 * @param  stdClass $cm         course module object
 * @param  stdClass $context    context object
 * @since Moodle 3.0
 */
function runestone_view($url, $course, $cm, $context) {

    // Trigger course_module_viewed event.
    $params = array(
        'context' => $context,
        'objectid' => $url->id
    );

    $event = \mod_runestone\event\course_module_viewed::create($params);
    $event->add_record_snapshot('course_modules', $cm);
    $event->add_record_snapshot('course', $course);
    $event->add_record_snapshot('runestone', $url);
    $event->trigger();

    // Completion.
    $completion = new completion_info($course);
    $completion->set_module_viewed($cm);
}

/**
 * Tries to make connection to the external database.
 *
 * @return null|ADONewConnection
 */
function db_init() {
    global $CFG;

    require_once($CFG->libdir.'/adodb/adodb.inc.php');

    // Connect to the external database (forcing new connection).
    $extdb = ADONewConnection(get_config('mod_runestone', 'dbtype'));

    // The dbtype my contain the new connection URL, so make sure we are not connected yet.
    if (!$extdb->IsConnected()) {
        $result = $extdb->Connect(get_config('mod_runestone', 'dbhost'), get_config('mod_runestone', 'dbuser'), get_config('mod_runestone', 'dbpass'), get_config('mod_runestone', 'dbname'), true);
        if (!$result) {
            return null;
        }
    }

    $extdb->SetFetchMode(ADODB_FETCH_ASSOC);

    return $extdb;
}

function db_encode($text) {
    $dbenc = get_config('mod_runestone','dbencoding');
    if (empty($dbenc) or $dbenc == 'utf-8') {
        return $text;
    }
    if (is_array($text)) {
        foreach($text as $k=>$value) {
            $text[$k] = db_encode($value);
        }
        return $text;
    } else {
        return core_text::convert($text, 'utf-8', $dbenc);
    }
}

function db_decode($text) {
    $dbenc = get_config('mod_runestone','dbencoding');
    if (empty($dbenc) or $dbenc == 'utf-8') {
        return $text;
    }
    if (is_array($text)) {
        foreach($text as $k=>$value) {
            $text[$k] = db_decode($value);
        }
        return $text;
    } else {
        return core_text::convert($text, $dbenc, 'utf-8');
    }
}

/**
 * Forces synchronisation of all grades with runestone database.
 *
 * @param progress_trace $trace
 * @return int 0 means success, 1 db connect failure, 2 db read failure
 */
function sync_grades(progress_trace $trace = null) {
    global $CFG, $DB;

    if (empty($trace)) {
        $trace = new null_progress_trace();
    }

    if (!get_config('mod_runestone', 'dbtype')) {
        $trace->output('Runestone grades synchronisation skipped.');
        $trace->finished();
        return 0;
    }

    $trace->output('Starting runestone grades synchronisation...');

    if (!$extdb = db_init()) {
        $trace->output('Error while communicating with external enrolment database');
        $trace->finished();
        return 1;
    }

    // We may need a lot of memory here.
    core_php_time_limit::raise();
    raise_memory_limit(MEMORY_HUGE);

    $trace->output('Getting runestone lessons...');
    $lessons = $DB->get_records('runestone', null, '', 'id, course, runes_assign');

    foreach ($lessons as $lesson) {
        $context = context_course::instance($lesson->course);
        $sql = "SELECT g.score, u.email FROM grades AS g
            INNER JOIN assignments AS a
            ON a.id = g.assignment
            INNER JOIN auth_user as u
            ON u.id = g.auth_user
            WHERE a.id = $lesson->runes_assign";
        $users = $extdb->getAll($sql);

        foreach ($users as $user) {
            // Get moodle user with the same email from runestone user
            $mdl_user = $DB->get_record('user', array('email' => db_decode($user['email'])));
            // Check if user is enrolled in the course of the lesson
            if (!empty($mdl_user) && is_enrolled($context, $mdl_user->id, '', true)) {
                $score = $user['score'];
                $grade = array('lessonid'=>$lesson->id, 'userid'=>$mdl_user->id, 'grade'=>$score);
                // Check if there is recorded grade of this lesson for this user
                if ($DB->record_exists('runestone_grades', array('lessonid' => $lesson->id, 'userid' => $mdl_user->id))) {
                    $obj = $DB->get_record('runestone_grades', array('lessonid' => $lesson->id, 'userid' => $mdl_user->id), 'id');
                    $grade['id'] = $obj->id;
                    $DB->update_record('runestone_grades', $grade);
                } else {
                    $DB->insert_record('runestone_grades', $grade);
                }
            }

        }
     }

     // Update all grades
    runestone_update_grades();

    // Close db connection.
    $extdb->Close();

    $trace->output('...synchronisation finished.');
    $trace->finished();

    return 0;
}

