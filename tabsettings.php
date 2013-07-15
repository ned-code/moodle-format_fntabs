<?php

// Edit course settings

require_once('../../../config.php');
//require_once($CFG->libdir . '/blocklib.php');
require_once('lib.php');
require_once('edit_form.php');
require_once('course_format.class.php');
require_once('course_format_fn.class.php');
global $DB, $OUTPUT, $PAGE;

$id = optional_param('id', 0, PARAM_INT);       // course id       
$categoryid = optional_param('category', 0, PARAM_INT); // course category - can be changed in edit form

$PAGE->set_pagelayout('admin');
$PAGE->set_url('/course/format/fntabs/tabsettings.php', array('id' => $id));

require_login();
/// basic access control checks
if ($id) { // editing course
    if ($id == SITEID) {
        // don't allow editing of  'site course' using this from
        print_error('You cannot edit the site course using this form');
    }

    if (!$course = $DB->get_record('course', array('id' => $id))) {
        print_error('Course ID was incorrect');
    }
    require_login($course);
    $category = $DB->get_record('course_categories', array('id' => $course->category), '*', MUST_EXIST);
    $coursecontext = get_context_instance(CONTEXT_COURSE, $course->id);
    require_capability('moodle/course:update', $coursecontext);
} else {
    require_login();
    print_error('Course id must be specified');
}

/// Need the bigger course object, including any extras.
$cobject = new course_format_fn($course);
$course = clone($cobject->course);

unset($cobject);

/// first create the form
$editform = new course_fntabs_edit_form(NULL, array('course' => $course), 'post', '', array('class' => 'fn_tabs_settings'));
                                  
$data = new stdClass();
$data->courseid = $course->id;
$mainheading = $DB->get_field('course_config_fn', 'value', array('courseid' =>$data->courseid, 'variable' => 'mainheading'));
$data->mainheading = ($mainheading) ? $mainheading: get_string('defaultmainheading', 'format_fntabs');

$topicheading = $DB->get_field('course_config_fn', 'value', array('courseid' =>$data->courseid, 'variable' => 'topicheading'));
$data->topicheading = ($topicheading) ? $topicheading: get_string('defaulttopicheading', 'format_fntabs');

$maxtabs = $DB->get_field('course_config_fn', 'value', array('courseid' =>$data->courseid, 'variable' => 'maxtabs'));
$data->maxtabs = ($maxtabs) ? $maxtabs: 12; 

$defaulttab = $DB->get_field('course_config_fn', 'value', array('courseid' =>$data->courseid, 'variable' => 'defaulttab'));
$completion = new completion_info($course);
if((!$completion->is_enabled()) && $defaulttab =='option2'){
    $data->defaulttab = 'option1';
}else{
    $data->defaulttab = ($defaulttab) ? $defaulttab: 'option1';
}

$bgcolour = $DB->get_field('course_config_fn', 'value', array('courseid' =>$data->courseid, 'variable' => 'bgcolour'));
$data->bgcolour = ($bgcolour) ? $bgcolour: '9DBB61';
$activecolour = $DB->get_field('course_config_fn', 'value', array('courseid' =>$data->courseid, 'variable' => 'activecolour'));
$data->activecolour = ($activecolour) ? $activecolour: 'DBE6C4';
$selectedcolour = $DB->get_field('course_config_fn', 'value', array('courseid' =>$data->courseid, 'variable' => 'selectedcolour'));
$data->selectedcolour = ($selectedcolour) ? $selectedcolour: 'FFFF33';
$inactivebgcolour = $DB->get_field('course_config_fn', 'value', array('courseid' =>$data->courseid, 'variable' => 'inactivebgcolour'));
$data->inactivebgcolour = ($inactivebgcolour) ? $inactivebgcolour: 'F5E49C';
$inactivecolour = $DB->get_field('course_config_fn', 'value', array('courseid' =>$data->courseid, 'variable' => 'inactivecolour'));
$data->inactivecolour = ($inactivecolour) ? $inactivecolour: 'BDBBBB';
$activelinkcolour = $DB->get_field('course_config_fn', 'value', array('courseid' =>$data->courseid, 'variable' => 'activelinkcolour'));
$data->activelinkcolour = ($activelinkcolour) ? $activelinkcolour: '000000';
$inactivelinkcolour = $DB->get_field('course_config_fn', 'value', array('courseid' =>$data->courseid, 'variable' => 'inactivelinkcolour'));
$data->inactivelinkcolour = ($inactivelinkcolour) ? $inactivelinkcolour: '000000';
$highlightcolour = $DB->get_field('course_config_fn', 'value', array('courseid' =>$data->courseid, 'variable' => 'highlightcolour'));
$data->highlightcolour = ($highlightcolour) ? $highlightcolour: '73C1E1';

$topictoshow = $DB->get_field('course_config_fn', 'value', array('courseid' =>$data->courseid, 'variable' => 'topictoshow'));
$data->topictoshow = ($defaulttab == 'option3') ? $topictoshow: 1;
$showsection0 = $DB->get_field('course_config_fn', 'value', array('courseid' =>$data->courseid, 'variable' => 'showsection0'));
$data->showsection0 = ($showsection0 ) ? $showsection0 : 0;

$showonlysection0 = $DB->get_field('course_config_fn', 'value', array('courseid' => $data->courseid, 'variable' => 'showonlysection0'));
$data->showonlysection0 = ($showonlysection0) ? $showonlysection0 : 0;
$data->defaulttabwhenset = time();
$editform->set_data($data);

if ($editform->is_cancelled()) {
    if (empty($course)) {
        redirect($CFG->wwwroot);
    } else {
        redirect($CFG->wwwroot . '/course/view.php?id=' . $course->id);
    }
} else if ($data = $editform->get_data()) {
    // process data if submitted   
    /// Handle the extra settings:    
    $variable = 'showsection0';
    update_course_fn_setting($variable, $data->$variable);

    $variable = 'showonlysection0';
    update_course_fn_setting($variable, $data->$variable);

    $variable = 'mainheading';
    update_course_fn_setting($variable, $data->$variable);

    $variable = 'topicheading';
    update_course_fn_setting($variable, $data->$variable);

    $variable = 'maxtabs';
    update_course_fn_setting($variable, $data->$variable);
    
    $variable = 'defaulttab';
    update_course_fn_setting($variable, $data->$variable);
    
    $variable = 'topictoshow';
    update_course_fn_setting($variable, $data->$variable);
    
    $variable = 'defaulttabwhenset';
    update_course_fn_setting($variable, $data->$variable);

    $variable = 'bgcolour';
    update_course_fn_setting($variable, $data->$variable);

    $variable = 'activelinkcolour';
    update_course_fn_setting($variable, $data->$variable);

    $variable = 'inactivelinkcolour';
    update_course_fn_setting($variable, $data->$variable);

    $variable = 'highlightcolour';
    update_course_fn_setting($variable, $data->$variable);

    $variable = 'inactivebgcolour';
    update_course_fn_setting($variable, $data->$variable);

    $variable = 'selectedcolour';
    update_course_fn_setting($variable, $data->$variable);

    $variable = 'activecolour';
    update_course_fn_setting($variable, $data->$variable);

    $variable = 'inactivecolour';
    update_course_fn_setting($variable, $data->$variable);

    unset($SESSION->G8_selected_week[$course->id]);
    redirect($CFG->wwwroot . "/course/view.php?id=$course->id" );
}

/// Print the form
$site = get_site();
$streditcoursesettings = get_string("editcoursesettings");
if (!empty($course)) {
    $PAGE->navbar->add($streditcoursesettings);
    $title = $streditcoursesettings;
    $fullname = $course->fullname;
} else {
    $title = "";
    $fullname = $site->fullname;
}

$PAGE->set_title($title);
$PAGE->set_heading($fullname);

echo $OUTPUT->header();
echo $OUTPUT->heading($streditcoursesettings);

$editform->display();
echo $OUTPUT->footer();

////////////////////////////////////////////////////////////////////

function update_course_fn_setting($variable, $data) {
    global $course, $DB;

    $rec = new Object();
    $rec->courseid = $course->id;
    $rec->variable = $variable;
    $rec->value = $data;
    
    if ($DB->get_field('course_config_fn', 'id', array('courseid' => $course->id, 'variable' => $variable))) {
        $id = $DB->get_field('course_config_fn', 'id', array('courseid' => $course->id, 'variable' => $variable));
        $rec->id = $id;
        $DB->update_record('course_config_fn', $rec);
    } else {
        $rec->id = $DB->insert_record('course_config_fn', $rec);
    }
}
