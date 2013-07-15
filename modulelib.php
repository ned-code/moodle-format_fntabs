<?php

/**
 *To Know the assignement state saved or submiited
 *
 * @param mod object
 * @param userid
 * @return saved or submitted
 * @todo Finish documenting this function
 */ 

function assignment_is_completed($mod, $userid) {
    global $CFG, $DB, $USER, $SESSION;
    require_once ($CFG->dirroot.'/mod/assignment/lib.php');    

    if(isset($SESSION->completioncache)){
        unset($SESSION->completioncache);
    }

    if ($mod->modname == 'assignment') {
        if  (!($assignment = $DB->get_record('assignment', array('id' => $mod->instance)))) {
            return false;   // Doesn't exist... wtf?
        }

        require_once ($CFG->dirroot.'/mod/assignment/type/'.$assignment->assignmenttype.'/assignment.class.php');
        $assignmentclass = "assignment_$assignment->assignmenttype";
        $assignmentinstance = new $assignmentclass($mod->id, $assignment, $mod);
    
        if (!($submission = $assignmentinstance->get_submission($userid)) || empty($submission->timemodified)) {
            return false;
        }

        switch ($assignment->assignmenttype) {      
            case "upload":          
                if($assignment->var4){ //if var4 enable then assignment can be saved                
                    if(!empty($submission->timemodified)
                            && ($submission->data2=="")){                  
                        return 'saved';
                    } else if(!empty($submission->timemodified)
                            && ($submission->data2=='submitted')
                            && empty($submission->timemarked)){                
                        return 'submitted';                    
                    } else if(!empty($submission->timemodified)
                            && ($submission->data2=='submitted')
                            && ($submission->grade==-1)){
                        return 'submitted';
                    }
                } else if(empty($submission->timemarked)){               
                    return 'submitted';                
                }
                break;
            case "uploadsingle":            
                if(empty($submission->timemarked)){           
                     return 'submitted';                
                }            
                break;
            case "online":
                if(empty($submission->timemarked)){       
                     return 'submitted';                
                }             
                break;
            case "offline":           
                if(empty($submission->timemarked)){     
                     return 'submitted';                
                }
                break;
        }
    } else if ($mod->modname == 'assign') {
        if  (!($assignment = $DB->get_record('assign', array('id' => $mod->instance)))) {
            return false; // Doesn't exist
        }
        if (!$submission = $DB->get_record('assign_submission', array('assignment'=>$assignment->id, 'userid'=>$USER->id))) {
            return false;
        }

        $submissionisgraded = $DB->record_exists('assign_grades', array('assignment'=>$assignment->id, 'userid'=>$USER->id));

        if ($submission->status == 'draft') {
            return 'saved';
        } else if ($submission->status == 'submitted' && !$submissionisgraded) {
            return 'submitted';
        }
    } else {
        return false;
    }


}
//old versuion on date 10 nov
// This function is to know the assignment state draft or not 
//function assignment_is_completed($mod, $userid) {
//    global $CFG, $DB, $USER;
//    require_once ($CFG->dirroot.'/mod/assignment/lib.php');
//
//    if  (!($assignment = $DB->get_record('assignment', array('id' => $mod->instance)))) {
//        
//        return false;   // Doesn't exist... wtf?
//    }
//    require_once ($CFG->dirroot.'/mod/assignment/type/'.$assignment->assignmenttype.'/assignment.class.php');
//    $assignmentclass = "assignment_$assignment->assignmenttype";
//    $assignmentinstance = new $assignmentclass($mod->id, $assignment, $mod);
//
//    if (!($submission = $assignmentinstance->get_submission($userid)) || empty($submission->timemodified)) {
//        return false;
//    }
//    
//    if($assignment->var4 =='1' && $submission->data2==''){
//   
//       $completionobj=$DB->get_record('course_modules_completion',array('coursemoduleid'=>$mod->id,'userid'=>$USER->id));
//
//         if(isset($completionobj->completionstate) && $completionobj->completionstate !='4'){
//             $update= new stdClass();
//             $update->id=$completionobj->id;
//             $update->completionstate='4';
//             $DB->update_record('course_modules_completion',$update);
//         }
//       return 'saved'; 
//        
//    }    
//
//    if (empty($submission->timemarked)) {      
//        
//         $completionobj=$DB->get_record('course_modules_completion',array('coursemoduleid'=>$mod->id,'userid'=>$USER->id));
//         if($completionobj){
//            if($completionobj->completionstate !='5'){
//                 $update= new stdClass();
//                 $update->id=$completionobj->id;
//                 $update->completionstate='5';
//                 $DB->update_record('course_modules_completion',$update);
//             }
//             return 'submitted';             
//         }
//         else{
//             return 'submitted';
//         }
//        
//    }
//}
//
