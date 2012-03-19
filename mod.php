<?php

//  Handles custom updated for the FN course format.

require("../../../config.php");
require("lib.php");
require_login();
global $DB;

if (isset($_POST['sec0title'])) {
    if (!$course = $DB->get_record('course', array('id' => $_POST['id']))) {
        print_error('This course doesn\'t exist.');
    }
    FN_get_course($course);
    $course->sec0title = $_POST['sec0title'];
    FN_update_course($course);
    $cm->course = $course->id;
}

$site = get_site();
if ($site->id == $cm->course) {
    redirect($CFG->wwwroot);
} else {
    redirect($CFG->wwwroot . '/course/view.php?id=' . $cm->course);
}
exit;