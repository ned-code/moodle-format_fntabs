<?php

/**
 * This file contains general functions for the course format MoodleFN format
 *
 */
require_once ($CFG->dirroot . '/course/lib.php');
define('FN_EXTRASECTION', 9999);     // A non-existant section to hold hidden modules.

/// Format Specific Functions:
function FN_update_course($form, $oldformat = false) {
    global $CFG, $DB, $OUTPUT;

    /// Updates course specific variables.
    /// Variables are: 'showsection0', 'showannouncements'.
//    $config_vars = array('showsection0', 'showannouncements', 'sec0title', 'showhelpdoc', 'showclassforum',
//                         'showclasschat', 'logo', 'mycourseblockdisplay','showgallery', 'gallerydefault', 'usesitegroups', 'mainheading', 'topicheading',
//                         'activitytracking', 'ttmarking', 'ttgradebook', 'ttdocuments', 'ttstaff',
//                         'defreadconfirmmess', 'usemandatory', 'expforumsec');


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

/**
 * Indicates this format uses sections.
 *
 * @return bool Returns true
 */
//function callback_weeks_uses_sections() {
//    return true;
//}

/**
 * Used to display the course structure for a course where format=weeks
 *
 * This is called automatically by {@link load_course()} if the current course
 * format = weeks.
 *
 * @param navigation_node $navigation The course node
 * @param array $path An array of keys to the course node
 * @param stdClass $course The course we are loading the section for
 */
//function callback_weeks_load_content(&$navigation, $course, $coursenode) {
//    return $navigation->load_generic_course_sections($course, $coursenode, 'weeks');
//}

/**
 * The string that is used to describe a section of the course
 * e.g. Topic, Week...
 *
 * @return string
 */
//function callback_weeks_definition() {
//    return get_string('week');
//}

function FN_get_course(&$course) {
    global $DB;
    /// Add course specific variable to the passed in parameter.

    if ($config_vars = $DB->get_records('course_config_fn', array('courseid' => $course->id))) {
        foreach ($config_vars as $config_var) {
            $course->{$config_var->variable} = $config_var->value;
        }
    }
}

/**
 * The GET argument variable that is used to identify the section being
 * viewed by the user (if there is one)
 *
 * @return string
 */
//function callback_weeks_request_key() {
//    return 'week';
//}

/**
 * Gets the name for the provided section.
 *
 * @param stdClass $course
 * @param stdClass $section
 * @return string
 */
//function callback_weeks_get_section_name($course, $section) {
//    // We can't add a node without text
//    if (!empty($section->name)) {
//        // Return the name the user set
//        return $section->name;
//    } else if ($section->section == 0) {
//        // Return the section0name
//        return get_string('section0name', 'format_weeks');
//    } else {
//        // Got to work out the date of the week so that we can show it
//        $sections = get_all_sections($course->id);
//        $weekdate = $course->startdate + 7200;
//        foreach ($sections as $sec) {
//            if ($sec->id == $section->id) {
//                break;
//            } else if ($sec->section != 0) {
//                $weekdate += 604800;
//            }
//        }
//        $strftimedateshort = ' ' . get_string('strftimedateshort');
//        $weekday = userdate($weekdate, $strftimedateshort);
//        $endweekday = userdate($weekdate + 518400, $strftimedateshort);
//        return $weekday . ' - ' . $endweekday;
//    }
//}

/**
 * Declares support for course AJAX features
 *
 * @see course_format_ajax_support()
 * @return stdClass
 */
//function callback_weeks_ajax_support() {
//    $ajaxsupport = new stdClass();
//    $ajaxsupport->capable = true;
//    $ajaxsupport->testedbrowsers = array('MSIE' => 6.0, 'Gecko' => 20061111, 'Safari' => 531, 'Chrome' => 6.0);
//    return $ajaxsupport;
//}

function get_week_info($tabrange, $week) {
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

function get_course_section_mods($courseid, $sectionid) {
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
function get_assignment_object_from_instance($module) {
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
function is_saved_or_submitted($mod, $userid) {
    global $CFG, $DB, $USER, $SESSION;
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

    switch ($assignment->assignmenttype) {
        case "upload":
            if ($assignment->var4) { //if var4 enable then assignment can be saved                
                if (!empty($submission->timemodified)
                        && (empty($submission->data2))
                        && (empty($submission->timemarked))) {                   
                    return 'saved';
                } else if (!empty($submission->timemodified)
                        && ($submission->data2 = 'submitted')
                        && empty($submission->timemarked)) {
                    return 'submitted';
                } else if (!empty($submission->timemodified)
                        && ($submission->data2 = 'submitted')
                        && ($submission->grade == -1)) {
                    return 'submitted';
                }
            } else if (empty($submission->timemarked)) {
                return 'submitted';
            }
            break;
        case "uploadsingle":
            if (empty($submission->timemarked)) {
                return 'submitted';
            }
            break;
        case "online":
            if (empty($submission->timemarked)) {
                return 'submitted';
            }
            break;
        case "offline":
            if (empty($submission->timemarked)) {
                return 'submitted';
            }
            break;
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
function get_activities_status($course, $section) {

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
                    if ($completionstate == 0) {  // if completion=0 then it may be saved or submitted                      
                        if (($module->module == 1)
                                && ($module->modname = 'assignment')
                                && ($module->completion == 2)
                                && is_saved_or_submitted($module, $USER->id)) {                          
                            //grab assignment status
                            $assignement_status = is_saved_or_submitted($module, $USER->id);
                            if (isset($assignement_status)) {
                                if ($assignement_status=='saved') {
                                    $saved++;
                                } else if ($assignement_status=='submitted') {
                                    $waitingforgrade++;
                                }
                            }
                        } else {
                            $notattempted++;
                        }
                    } elseif ($completionstate == 1 || $completionstate == 2) {
                        $complete++;
                    } elseif ($completionstate == 3) {
                             if (($module->module == 1)
                                    && ($module->modname = 'assignment')
                                    && ($module->completion == 2)
                                    && is_saved_or_submitted($module, $USER->id)) {
                                  //grab assignment status
                                    $assignement_status = is_saved_or_submitted($module, $USER->id);
                                    if (isset($assignement_status)) {
                                        if ($assignement_status=='saved') {
                                            $saved++;
                                        } else if ($assignement_status=='submitted') {
                                            $waitingforgrade++;
                                        }
                                    }
                                 }
                             else{
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