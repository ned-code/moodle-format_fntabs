<?php

//$id:$
/**
 * Library of functions to handle specific module operations, such as:
 *  - Completed checks,
 *
 * All functions should have all arguments they need passed to them.
 *
 */
/// Create functions to return true, false or 'na' for activity completion.
//function is_activity_complete($mod, $userid) {
//    /// Can't test hiiden activities...
//    if (!$mod->visible) {
//        return 'na';
//    }
//    $functionname = $mod->modname.'_is_completed';
//    if (function_exists($functionname)) {
//        return $functionname($mod, $userid);
//    }
//    else {
//        return 'na';
//    }
//}

function assignment_is_completed($mod, $userid) {
    global $CFG, $DB;
    require_once ($CFG->dirroot . '/mod/assignment/lib.php');

    if (!($assignment = $DB->get_record('assignment', array('id' => $mod->instance)))) {
        return false;   // Doesn't exist... wtf?
    }

    require_once ($CFG->dirroot . '/mod/assignment/type/' . $assignment->assignmenttype . '/assignment.class.php');
    $assignmentclass = "assignment_$assignment->assignmenttype";
    $assignmentinstance = new $assignmentclass($mod->id, $assignment, $mod);

    if (!($submission = $assignmentinstance->get_submission($userid)) || empty($submission->timemodified)) {
        return false;
    }

    if (empty($submission->timemarked) && !empty($submission->data2)) {
        return 'submitted';
    }
    if (empty($submission->timemarked) && empty($submission->data2) && empty($submission->data1)) {
        return 'saved';
    } else {
        return ((int) $assignment->grade > 0) ? (int) ($submission->grade / $assignment->grade * 100) : true;
    }
}

//function fnassignment_is_completed($mod, $userid) {
//    global $CFG,$DB;
//    require_once ($CFG->dirroot.'/mod/fnassignment/lib.php');
//
//    if (! ($assignment = $DB->get_record('fnassignment', array('id'=>$mod->instance)))) {
//        return false;   // Doesn't exist... wtf?
//    }
//
//    require_once ($CFG->dirroot.'/mod/fnassignment/type/'.$assignment->assignmenttype.'/fnassignment.class.php');
//    $assignmentclass = "fnassignment_$assignment->assignmenttype";
//    $assignmentinstance = new $assignmentclass($mod->id, $assignment, $mod);
//
//    if (!($submission = $assignmentinstance->get_submission($userid)) || empty($submission->timemodified)) {
//        return false;
//    }
//
//    if (empty($submission->timemarked)) {
//        return 'submitted';
//    } else {
//        return ((int)$assignment->grade > 0) ? (int)($submission->grade / $assignment->grade * 100) : true;
//    }
//}
//
//function choice_is_completed($mod, $userid) {
//    global $DB;
//    return $DB->get_record("choice_answers", array("choiceid"=>$mod->instance, "userid"=>$userid)) ? true : false;
//}
//
//function exercise_is_completed($mod, $userid) {
//    global $DB;
//    return $DB->get_record("exercise_submissions", array("exerciseid"=>$mod->instance, "userid"=>$userid)) ? true : false;
//}
//
//function feedback_is_completed($mod, $userid) {
//    global $DB;
//    return (isteacheredit($mod->course) || $DB->get_record("feedback_completed", array("feedback"=>$mod->instance, "userid"=>$userid))) ? true : false;
//}
//
//function quiz_is_completed($mod, $userid) {
//    global $DB;
//    return $DB->get_record("quiz_attempts", array("quiz"=>$mod->instance, "userid"=>$userid)) ? true : false;
//}
//
//function workshop_is_completed($mod, $userid) {
//    global $DB;
//    return $DB->get_record("workshop_submissions", array("workshopid"=>$mod->instance, "userid"=>$userid)) ? true : false;
//}
//
///// FN - 20060125 - Remove read resource requirement.
////function resource_is_completed($mod, $userid) {
////    return get_record("resource_completed", "resourceid", $mod->instance, "userid", $userid) ? true : false;
////}
//
//function forum_is_completed($mod, $userid) {
//    global $CFG,$DB;
//
//    $sql = 'SELECT * FROM '.$CFG->prefix.'forum_discussions fd, '.$CFG->prefix.'forum_posts fp '.
//           'WHERE fd.forum = '.$mod->instance.' AND fp.discussion = fd.id AND fp.userid = '.$userid;
//    return $DB->get_records_sql($sql) ? true : false;
//}
//
//function lesson_is_completed($mod, $userid) {
//    global $DB;
//    return $DB->get_record('lesson_grades', array('lessonid'=>$mod->instance, 'userid'=>$userid)) ? true : false;
//}
//
//function questionnaire_is_completed($mod, $userid) {
//    global $DB;
//    return $DB->get_record('questionnaire_attempts', array('qid'=>$mod->instance, 'userid'=>$userid)) ? true : false;
//}
//
//function journal_is_completed($mod, $userid) {
//    global $CFG,$DB;
//
//    $sql = 'SELECT j.assessed as maxgrade,e.timemarked,e.rating as grade '.
//           'FROM '.$CFG->prefix.'journal j, '.$CFG->prefix.'journal_entries e '.
//           'WHERE j.id = '.$mod->instance.' AND e.journal = j.id AND e.userid = '.$userid;
//    /// If a submission exists, return true, or the grade percent if its been numerically graded.
//    if ($recs = $DB->get_record_sql($sql)) {
//        if (!$recs->timemarked) {
//            return 'submitted';
//        } else {
//            return ((int)$recs->maxgrade > 0) ? (int)($recs->grade / $recs->maxgrade * 100) : true;
//        }
//    } else {
//        return false;
//    }
//}
//
//function practice_is_completed($mod, $userid) {
//  global $DB;
//    return $DB->get_record("practice_submissions", array("practiceid"=>$mod->instance, "userid"=>$userid)) ? true : false;
//}
