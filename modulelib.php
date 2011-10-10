<?php

// This function is to know the assignment state draft or not 
function assignment_is_completed($mod, $userid) {
    global $CFG, $DB, $USER;
    require_once ($CFG->dirroot.'/mod/assignment/lib.php');

    if  (!($assignment = $DB->get_record('assignment', array('id' => $mod->instance)))) {
        
        return false;   // Doesn't exist... wtf?
    }
    require_once ($CFG->dirroot.'/mod/assignment/type/'.$assignment->assignmenttype.'/assignment.class.php');
    $assignmentclass = "assignment_$assignment->assignmenttype";
    $assignmentinstance = new $assignmentclass($mod->id, $assignment, $mod);

    if (!($submission = $assignmentinstance->get_submission($userid)) || empty($submission->timemodified)) {
        return false;
    }
    
    if($assignment->var4 =='1' && $submission->data2==''){
   
       $completionobj=$DB->get_record('course_modules_completion',array('coursemoduleid'=>$mod->id,'userid'=>$USER->id));

         if(isset($completionobj->completionstate) && $completionobj->completionstate !='4'){
         $update= new stdClass();
         $update->id=$completionobj->id;
         $update->completionstate='4';
         $DB->update_record('course_modules_completion',$update);
         }
       return 'saved'; 
        
    }    

    if (empty($submission->timemarked)) {     
         $completionobj=$DB->get_record('course_modules_completion',array('coursemoduleid'=>$mod->id,'userid'=>$USER->id));       
         if($completionobj->completionstate !='5'){
         $update= new stdClass();
         $update->id=$completionobj->id;
         $update->completionstate='5';
         $DB->update_record('course_modules_completion',$update);
         }
        return 'submitted';
    }
}

