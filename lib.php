<?php

/**
 * This file contains general functions for the course format MoodleFN format   
 * 
 * 
 */
require_once ($CFG->dirroot . '/course/lib.php');
define('FN_EXTRASECTION', 9999);     // A non-existant section to hold hidden modules.
/// Format Specific Functions:

function FN_update_course($form, $oldformat = false, $resubmission=false) {
    global $CFG, $DB, $OUTPUT;
    $config_vars = array('showsection0', 'sec0title', 'mainheading', 'topicheading', 'maxtabs');

    foreach ($config_vars as $config_var) {
        if ($varrec = $DB->get_record('course_config_fn', array('courseid' => $form->id, 'variable' => $config_var))) {
            $varrec->value = $form->$config_var;
            $DB->update_record('course_config_fn', $varrec);
        } else {
            $varrec->courseid = $form->id;
            $varrec->variable = $config_var;
            $varrec->value = $form->$config_var;
            $DB->insert_record('course_config_fn', $varrec);
        }
    }

    /// We need to have the sections created ahead of time for the weekly nav to work,
    /// so check and create here.
    if (!($sections = get_all_sections($form->id))) {
        $sections = array();
    }

    for ($i = 0; $i <= $form->numsections; $i++) {
        if (empty($sections[$i])) {
            $section = new Object();
            $section->course = $form->id;   // Create a new section structure
            $section->section = $i;
            $section->summary = "";
            $section->visible = 1;
            if (!$section->id = $DB->insert_record("course_sections", $section)) {
                $OUTPUT->notification("Error inserting new section!");
            }
        }
    }

    /// Check for a change to an FN format. If so, set some defaults as well...
    if ($oldformat != 'FN') {
        /// Set the news (announcements) forum to no force subscribe, and no posts or discussions.
        require_once($CFG->dirroot . '/mod/forum/lib.php');
        $news = forum_get_course_forum($form->id, 'news');
        $news->open = 0;
        $news->forcesubscribe = 0;
        $DB->update_record('forum', $news);
    }
    rebuild_course_cache($form->id);
}

/* get the generic
 *  course object and 
 * them to course object
 * 
 */

function FN_get_course(&$course, $resubmission=false) {
    global $DB;
    /// Add course specific variable to the passed in parameter.
    if ($config_vars = $DB->get_records('course_config_fn', array('courseid' => $course->id))) {
        foreach ($config_vars as $config_var) {
            $course->{$config_var->variable} = $config_var->value;
        }
    }
}

/* get the get wwek info
 *  course object and 
 * them to course object
 * 
 */

function get_week_info($tabrange, $week, $resubmission=false) {
    global $SESSION;

    $fnmaxtab = $DB->get_field('course_config_fn', 'value', array('courseid' => $this->course->id, 'variable' => 'maxtabs'));
    if ($fnmaxtab) {
        $maximumtabs = $fnmaxtab;
    } else {
        $maximumtabs = 12;
    }

    if ($this->course->numsections == $maximumtabs) {
        $tablow = 1;
        $tabhigh = $maximumtabs;
    } else if ($tabrange > 1000) {
        $tablow = $tabrange / 1000;
        $tabhigh = $tablow + $maximumtabs - 1;
    } else if (($tabrange == 0) && ($week == 0)) {
        $tablow = ((int) ((int) ($this->course->numsections - 1) / (int) $maximumtabs) * $maximumtabs) + 1;
        $tabhigh = $tablow + $maximumtabs - 1;
    } else if ($tabrange == 0) {
        $tablow = ((int) ((int) $week / (int) $maximumtabs) * $maximumtabs) + 1;
        $tabhigh = $tablow + $maximumtabs - 1;
    } else {
        $tablow = 1;
        $tabhigh = $maximumtabs;
    }
    $tabhigh = MIN($tabhigh, $this->course->numsections);


    /// Normalize the tabs to always display FNMAXTABS...
    if (($tabhigh - $tablow + 1) < $maximumtabs) {
        $tablow = $tabhigh - $maximumtabs + 1;
    }


    /// Save the low and high week in SESSION variables... If they already exist, and the selected
    /// week is in their range, leave them as is.
    if (($tabrange >= 1000) || !isset($SESSION->FN_tablow[$this->course->id]) || !isset($SESSION->FN_tabhigh[$this->course->id]) ||
            ($week < $SESSION->FN_tablow[$this->course->id]) || ($week > $SESSION->FN_tabhigh[$this->course->id])) {
        $SESSION->FN_tablow[$this->course->id] = $tablow;
        $SESSION->FN_tabhigh[$this->course->id] = $tabhigh;
    } else {
        $tablow = $SESSION->FN_tablow[$this->course->id];
        $tabhigh = $SESSION->FN_tabhigh[$this->course->id];
    }


    $tablow = MAX($tablow, 1);
    $tabhigh = MIN($tabhigh, $this->course->numsections);

    /// If selected week in a different set of tabs, move it to the current set...
    if (($week != 0) && ($week < $tablow)) {
        $week = $SESSION->G8_selected_week[$this->course->id] = $tablow;
    } else if ($week > $tabhigh) {
        $week = $SESSION->G8_selected_week[$this->course->id] = $tabhigh;
    }

    return array($tablow, $tabhigh, $week);
}

function get_course_section_mods($courseid, $sectionid, $resubmission=false) {
    global $DB;

    if (empty($courseid)) {
        return false; // avoid warnings
    }

    if (empty($sectionid)) {
        return false; // avoid warnings
    }

    return $DB->get_records_sql("SELECT cm.*, m.name as modname
                                   FROM {modules} m, {course_modules} cm
                                  WHERE cm.course = ? AND cm.section= ? AND cm.completion !=0 AND cm.module = m.id AND m.visible = 1", array($courseid, $sectionid)); // no disabled mods
}

/**
 * To get the assignment object from instance
 *
 * @param instance of the assignment
 * @return assignment object from assignment table
 * @todo Finish documenting this function
 */
function get_assignment_object_from_instance($module, $resubmission=false) {
    global $DB;

    if (!($assignment = $DB->get_record('assignment', array('id' => $module->instance)))) {

        return false;   // Doesn't exist... wtf?
    } else {
        return $assignment;
    }
}

/**
 * To get the assignment object and user submission
 *
 * @param module of the assignment
 * @return assignment object from assignment table
 * @todo Finish documenting this function
 */
function is_saved_or_submitted($mod, $userid, $resubmission=false) {
    global $CFG, $DB, $USER, $SESSION;
    require_once ($CFG->dirroot . '/mod/assignment/lib.php');
   

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
                            && (empty($submission->data2))
                            && (empty($submission->timemarked))){                  
                        return 'saved';
                        
                    }
                    else if(!empty($submission->timemodified)
                            && ($submission->data2='submitted')
                            && empty($submission->timemarked)){                
                        return 'submitted';                    
                    }
                    else if(!empty($submission->timemodified)
                            && ($submission->data2='submitted')
                            && ($submission->grade==-1)){
                        return 'submitted';
                        
                    }
                }
                else if(empty($submission->timemarked)){               
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
        if ($resubmission){
            if (!$submission = $DB->get_records('assign_submission', array('assignment'=>$assignment->id, 'userid'=>$USER->id), 'attemptnumber DESC', '*', 0, 1)) {
                return false;
            }else{
                $submission = reset($submission);            
            }            
        }else{
            if (!$submission = $DB->get_record('assign_submission', array('assignment'=>$assignment->id, 'userid'=>$USER->id))) {
                return false;
            }            
            
        }
        
        $attemptnumber = $submission->attemptnumber;
        
        if (($submission->status == 'reopened') && ($submission->attemptnumber > 0)){
            $attemptnumber = $submission->attemptnumber - 1;     
        }    

        if ($resubmission){
            if ($submissionisgraded = $DB->get_records('assign_grades', array('assignment'=>$assignment->id, 'userid'=>$USER->id, 'attemptnumber' => $attemptnumber), 'attemptnumber DESC', '*', 0, 1)) {
                $submissionisgraded = reset($submissionisgraded);
                if ($submissionisgraded->grade > -1){
                  if ($submission->timemodified > $submissionisgraded->timemodified) {
                        $graded = false;  
                    }else{
                        $graded = true;  
                    }
                }else{
                    $graded = false;
                }                
            }else{
                $graded = false;
            } 
        }else{
            if (!$submissionisgraded = $DB->get_record('assign_grades', array('assignment'=>$assignment->id, 'userid'=>$USER->id))) {
                $graded = false;
            }else if ($submissionisgraded->grade <> -1){
                if ($submission->timemodified > $submissionisgraded->timemodified) {
                    $graded = false;  
                }else{
                    $graded = true;  
                }  
            }else{
                $graded = false;
            }
        }        
        
        if ($submission->status == 'draft') {
            if($graded){
                return 'submitted';
            }else{
                return 'saved';
            }            
        }
        if ($submission->status == 'reopened') {
            if($graded){
                return 'submitted';
            }else{
                return 'waitinggrade';
            }            
        } 
        if ($submission->status == 'submitted') {
            if($graded){
                return 'submitted';
            }else{
                return 'waitinggrade';
            }  
        }
    } else {
        return ;
    }
}

/**
 * To Know status of the activity
 *
 * @param mod object
 * @param userid
 * @return saved or submitted
 * @todo Finish documenting this function
 */
function get_activities_status($course, $section, $resubmission=false) {

    global $CFG, $USER;
    require_once($CFG->libdir . '/completionlib.php');
    require_once($CFG->dirroot . '/course/lib.php');
    $complete = 0;
    $incomplete = 0;
    $saved = 0;
    $notattempted = 0;
    $waitingforgrade = 0;

    if ($section->visible) {
        $modules = get_course_section_mods($course->id, $section->id);
        $completion = new completion_info($course);
        if ((isset($CFG->enablecompletion)) && !empty($completion)) {
            foreach ($modules as $module) {
                if (!$module->visible) {
                    continue;
                }
                if ($completion->is_enabled($course = null, $module)) {
                    $data = $completion->get_data($module, false, $USER->id, null);
                    $completionstate = $data->completionstate;
                    //grab assignment status
                    $assignement_status = is_saved_or_submitted($module, $USER->id, $resubmission); 
                    if ($completionstate == 0) {  // if completion=0 then it may be saved or submitted                         
                        if (($module->module == '1')
                                && ($module->modname == 'assignment' || $module->modname == 'assign')
                                && ($module->completion == '2')
                                && $assignement_status) {

                            if (isset($assignement_status)) {
                                if ($assignement_status == 'saved') {
                                    $saved++;
                                } else if ($assignement_status == 'submitted') {
                                    $notattempted++;
                                } else if ($assignement_status == 'waitinggrade') {
                                    $waitingforgrade++;
                                }
                            }else{
                                $notattempted++;
                            }
                        } else {
                            $notattempted++;
                        }
                    } elseif ($completionstate == 1 || $completionstate == 2) {
                      if (($module->module == 1)
                                && ($module->modname == 'assignment' || $module->modname == 'assign')
                                && ($module->completion == 2)
                                && $assignement_status) {
                            if (isset($assignement_status)) {
                                if ($assignement_status == 'saved') {
                                    $saved++;
                                } else if ($assignement_status == 'submitted') {
                                    $complete++;
                                } else if ($assignement_status == 'waitinggrade') {
                                    $waitingforgrade++;
                                }
                            }else{
                                $complete++;
                            }
                        } else {
                            $complete++;
                        }                    
                                 
                    } elseif ($completionstate == 3) {
                        if (($module->module == 1)
                                && ($module->modname == 'assignment' || $module->modname == 'assign')
                                && ($module->completion == 2)
                                && $assignement_status) {
                            if (isset($assignement_status)) {
                                if ($assignement_status == 'saved') {
                                    $saved++;
                                } else if ($assignement_status == 'submitted') {
                                    $incomplete++;
                                } else if ($assignement_status == 'waitinggrade') {
                                    $waitingforgrade++;
                                }
                            }else{
                                $incomplete++;
                            }  
                        } else {
                            $incomplete++;
                        }
                    }
                }
            }
            $array["complete"] = "$complete";
            $array["incomplete"] = "$incomplete";
            $array["saved"] = "$saved";
            $array["notattempted"] = "$notattempted";
            $array["waitngforgrade"] = "$waitingforgrade";
            return $array;
        }
    }
}