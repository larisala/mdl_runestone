<?php
  require_once("../../config.php");
  global $DB, $CFG;

  require_once($CFG->dirroot.'/mod/runestone/locallib.php');

  $courseid = $_GET['courseid'];

  if($courseid) {
      $assign_arr = get_runestone_assignments($courseid);

      if (empty($assign_arr)) {
        echo "<option value='0'>". get_string('empty_assignments', 'mod_runestone') ."</option>";
      } else {
        foreach ($assign_arr as $index=>$assignment) {
          echo "<option value=".$index.">" . $assignment . "</option>";
        }
      }
  }
?>
