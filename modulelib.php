<?php

// This function is to know the assignment state draft or not 
function assignment_is_completed($mod, $userid) {
    global $CFG, $DB, $USER;
    require_once ($CFG->dirroot.'/mod/assignment/lib.php');

    if  (!($assignment = $DB->get_record('assignment', array('id' => $mod->instance)))) {
        
        return false;   // Doesn't exist... wtf?
    }
//print_object($assignment);
    require_once ($CFG->dirroot.'/mod/assignment/type/'.$assignment->assignmenttype.'/assignment.class.php');
    $assignmentclass = "assignment_$assignment->assignmenttype";
    $assignmentinstance = new $assignmentclass($mod->id, $assignment, $mod);

    if (!($submission = $assignmentinstance->get_submission($userid)) || empty($submission->timemodified)) {
        return false;
    }
    
    if($assignment->var4 =='1' && $submission->data2==''){
   
       $completionobj=$DB->get_record('course_modules_completion',array('coursemoduleid'=>$mod->id,'userid'=>$USER->id));
        
         if($completionobj->completionstate !='4'){
         $update= new stdClass();
         $update->id=$completionobj->id;
         $update->completionstate='4';
         $DB->update_record('course_modules_completion',$update);
         }
       return 'saved'; 
        
    }
 //   print_object($submission);
    

    if (empty($submission->timemarked)) {       
         $completionobj=$DB->get_record('course_modules_completion',array('coursemoduleid'=>$mod->id,'userid'=>$USER->id));
        
         if($completionobj->completionstate !='5'){
         $update= new stdClass();
         $update->id=$completionobj->id;
         $update->completionstate='5';
         $DB->update_record('course_modules_completion',$update);
         }
        // print_object($completionobj);
        return 'submitted';
    }
}

//function assignment_is_completed($mod, $userid) {
//    global $CFG, $DB;
//    require_once ($CFG->dirroot . '/mod/assignment/lib.php');
//
//    if (!($assignment = $DB->get_record('assignment', array('id' => $mod->instance)))) {
//        return false;   // Doesn't exist... wtf?
//    }
//
//    require_once ($CFG->dirroot . '/mod/assignment/type/' . $assignment->assignmenttype . '/assignment.class.php');
//    $assignmentclass = "assignment_$assignment->assignmenttype";
//    $assignmentinstance = new $assignmentclass($mod->id, $assignment, $mod);
//
//    if (!($submission = $assignmentinstance->get_submission($userid)) || empty($submission->timemodified)) {
//        return false;
//    }
//
//    if (empty($submission->timemarked) && !empty($submission->data2)) {
//        return 'submitted';
//    }
//    if (empty($submission->timemarked) && empty($submission->data2) && empty($submission->data1)) {
//        return 'saved';
//    } else {
//        return ((int) $assignment->grade > 0) ? (int) ($submission->grade / $assignment->grade * 100) : true;
//    }
//}
//function assignment_is_completed($mod, $userid) {
//    global $CFG, $DB;
//    require_once ($CFG->dirroot . '/mod/assignment/lib.php');
//
//    if (!($assignment = $DB->get_record('assignment', array('id' => $mod->instance)))) {
//        return false;   // Doesn't exist... wtf?
//    }
//
//    require_once ($CFG->dirroot . '/mod/assignment/type/' . $assignment->assignmenttype . '/assignment.class.php');
//    $assignmentclass = "assignment_$assignment->assignmenttype";
//    $assignmentinstance = new $assignmentclass($mod->id, $assignment, $mod);
//
//    if (!($submission = $assignmentinstance->get_submission($userid)) || empty($submission->timemodified)) {
//        return false;
//    }
//
//    if (empty($submission->timemarked) && !empty($submission->data2)) {
//        return 'submitted';
//    }
//    if (empty($submission->timemarked) && empty($submission->data2) && empty($submission->data1)) {
//        return 'saved';
//    } else {
//        return ((int) $assignment->grade > 0) ? (int) ($submission->grade / $assignment->grade * 100) : true;
//    }
//}

//function assignment_is_completed($mod, $userid) {
//    global $CFG, $DB;
//    require_once ($CFG->dirroot . '/mod/assignment/lib.php');
//
//    if (!($assignment = $DB->get_record('assignment', array('id' => $mod->instance)))) {
//        return false;   // Doesn't exist... wtf?
//    }    
//print_object($assignment->assignmenttype);
//    require_once ($CFG->dirroot . '/mod/assignment/type/' . $assignment->assignmenttype . '/assignment.class.php');
//    $assignmentclass = "assignment_$assignment->assignmenttype";
//    $assignmentinstance = new $assignmentclass($mod->id, $assignment, $mod);    
//
//    if (!($submission = $assignmentinstance->get_submission($userid)) || empty($submission->timemodified)) {
//        return false;
//    }
//    if(($assignment->assignmenttype='upload')){    
//        if(($assignment->assignmenttype='upload') && empty($submission->timemarked) && $submission->timemarked<=0 && empty($submission->data1) && empty($submission->data1)){
//            echo "Sudhanshu";
//            return 'saved';            
//        }
//        else{
//            return 'submitted';
//        }        
//    }
//    else if((($assignment->assignmenttype='online')|| ($assignment->assignmenttype='uploadsingle')) && empty($submission->timemarked) && ($submission->timemarked >0)){
//        return 'submitted';
//    }
//    else {
//        
//    }
//}
//
// working function