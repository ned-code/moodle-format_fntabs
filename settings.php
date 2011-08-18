<?php // $Id: settings.php,v 1.2 2009/05/04 21:13:33 mchurch Exp $
      // Edit course settings

    require_once('../../../config.php');
//    require_once($CFG->dirroot.'/enrol/enrol.class.php');
    require_once($CFG->libdir.'/blocklib.php');
    require_once('lib.php');
    require_once('edit_form.php');
    require_once('course_format.class.php');
    require_once('course_format_fn.class.php');
    global $DB,$OUTPUT,$PAGE;

    $id         = optional_param('id', 0, PARAM_INT);       // course id       
    $categoryid = optional_param('category', 0, PARAM_INT); // course category - can be changed in edit form
    $PAGE->set_url('/course/format/mfntabs/settings.php', array('id'=>$id,'extraonly'=>'1'));
//    $PAGE->set_url('/blocks/fn_marking/fn_summaries.php', array('id'=>$id,'show'=>$show,'navlevel'=>'top'));
      

/// basic access control checks
    if ($id) { // editing course
        
        if($id == SITEID){
            
            // don't allow editing of  'site course' using this from
            print_error('You cannot edit the site course using this form');
        }

        if (!$course = $DB->get_record('course', array('id' => $id))) {
            print_error('Course ID was incorrect');
        }
        require_login($course->id);
        $category = $DB->get_record('course_categories', array('id' =>$course->category));
        require_capability('moodle/course:update', get_context_instance(CONTEXT_COURSE, $course->id));

    } else if ($categoryid) { // creating new course in this category
        
        $course = null;
        require_login($course->id);
        if (!$category = $DB->get_record('course_categories', array('id' => $categoryid))) {
            print_error('Category ID was incorrect');
        }
        require_capability('moodle/course:create', get_context_instance(CONTEXT_COURSECAT, $category->id));
    } else {
        require_login($course->id);
        print_error('Either course id or category must be specified');
    }

/// prepare course
    if (!empty($course)) {
        $allowedmods = array();
        if (!empty($course)) {
            
            if ($am = $DB->get_records('course_allowed_modules',array('course'=> $course->id))) {
                foreach ($am as $m) {
                    $allowedmods[] = $m->module;
                }
            } else {
                if (empty($course->restrictmodules)) {
                    $allowedmods = explode(',',$CFG->defaultallowedmodules);
                } // it'll be greyed out but we want these by default anyway.
            }
            $course->allowedmods = $allowedmods;

        //    if ($course->enrolstartdate){
        //       $course->enrolstartdisabled = 0;
        //    }

         //   if ($course->enrolenddate) {
         //       $course->enrolenddisabled = 0;
         //   }
        }
    }


    /// Need the bigger course object, including any extras.
    $cobject = new course_format_fn($course);
    $course = clone($cobject->course);
    unset($cobject);

/// first create the form
    $editform = new course_mfntabs_edit_form('settings.php', compact('course', 'category'));
    // now override defaults if course already exists
    if (!empty($course)) {
      #  $course->enrolpassword = $course->password; // we need some other name for password field MDL-9929
        $editform->set_data($course);
    }
    if ($editform->is_cancelled()){
        if (empty($course)) {
            redirect($CFG->wwwroot);
        } else {
            redirect($CFG->wwwroot.'/course/view.php?id='.$course->id);
        }

    } else if ($data = $editform->get_data()) {
        

        if (empty($data->extraonly)) {
            $data->password = $data->enrolpassword;  // we need some other name for password field MDL-9929
    /// process data if submitted

            //preprocess data
            if ($data->enrolstartdisabled){
                $data->enrolstartdate = 0;
            }

            if ($data->enrolenddisabled) {
                $data->enrolenddate = 0;
            }

            $data->timemodified = time();

            if (!update_course($data)) {
                print_error('coursenotupdated');
            }
        }

    /// Handle the extra settings:

        $variable = 'showsection0';
        update_course_fn_setting($variable, $data->$variable);

        $variable = 'showonlysection0';
        update_course_fn_setting($variable, $data->$variable);

//        $variable = 'expforumsec';
//        update_course_fn_setting($variable, $data->$variable);

        $variable = 'mainheading';
        update_course_fn_setting($variable, $data->$variable);

        $variable = 'topicheading';
        update_course_fn_setting($variable, $data->$variable);
       
//        $variable = 'defreadconfirmmess';
//        update_course_fn_setting($variable, $data->$variable);
       
        redirect($CFG->wwwroot."/course/view.php?id=$course->id");
         
    }

// $PAGE->set_url($CFG->wwwroot."/course/view.php?id=$course->id");
/// Print the form

    $site = get_site();
    $streditcoursesettings = get_string("editcoursesettings");
    $straddnewcourse = get_string("addnewcourse");
    $stradministration = get_string("administration");
    $strcategories = get_string("categories");
    $navlinks = array();

    if (!empty($course)) {
        $navlinks[] = array('name' => $streditcoursesettings,
                            'link' => null,
                            'type' => 'misc');
        $title = $streditcoursesettings;
        $fullname = $course->fullname;
    } else {
        $navlinks[] = array('name' => $stradministration,
                            'link' => "$CFG->wwwroot/$CFG->admin/index.php",
                            'type' => 'misc');
        $navlinks[] = array('name' => $strcategories,
                            'link' => 'index.php',
                            'type' => 'misc');
        $navlinks[] = array('name' => $straddnewcourse,
                            'link' => null,
                            'type' => 'misc');
        $title = "$site->shortname: $straddnewcourse";
        $fullname = $site->fullname;
    }

    $navigation = build_navigation($navlinks);
    print_header($title, $fullname, $navigation, $editform->focus()); 
    echo $OUTPUT->heading($streditcoursesettings);   

    $editform->display();
    echo $OUTPUT->footer($course);
    

//-------------------------------------------------------------------------------------------------------

    function update_course_fn_setting($variable, $data) {
        global $course,$DB;

        $rec = new Object();
        $rec->courseid = $course->id;
        $rec->variable = $variable;
        $rec->value = $data;
        if ($DB->get_field('course_config_fn', 'id', array('courseid'=>$course->id, 'variable'=>$variable))) {
            $id=$DB->get_field('course_config_fn', 'id', array('courseid'=>$course->id, 'variable'=>$variable));
            $rec->id = $id;
  
            $DB->update_record('course_config_fn', $rec);
        } else {
            $rec->id = $DB->insert_record('course_config_fn', $rec);
        }
    }
