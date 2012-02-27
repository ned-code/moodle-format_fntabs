<?php
// Edit course settings

require_once('../../../config.php');
require_once($CFG->libdir . '/blocklib.php');
require_once('lib.php');
require_once('edit_form.php');
require_once('course_format.class.php');
require_once('course_format_fn.class.php');
global $DB, $OUTPUT, $PAGE;

$id = optional_param('id', 0, PARAM_INT);       // course id       
$categoryid = optional_param('category', 0, PARAM_INT); // course category - can be changed in edit form

$PAGE->set_pagelayout('admin');
$PAGE->set_url('/course/format/fntabs/settings.php', array('id' => $id));

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
    $category = $DB->get_record('course_categories', array('id'=>$course->category), '*', MUST_EXIST);
    $coursecontext = get_context_instance(CONTEXT_COURSE, $course->id);
    require_capability('moodle/course:update', $coursecontext);
    
}else{
    require_login();
    print_error('Course id must be specified');
}

/// Need the bigger course object, including any extras.
$cobject = new course_format_fn($course);
$course = clone($cobject->course);

unset($cobject);

/// first create the form
$editform = new course_fntabs_edit_form(NULL, array('course'=>$course));

if ($editform->is_cancelled()) {
    if (empty($course)) {
        redirect($CFG->wwwroot);
    } else {
        redirect($CFG->wwwroot . '/course/view.php?id=' . $course->id);        
    }
} else if ($data = $editform->get_data()) {
    // process data if submitted   

    /// Handle the extra settings:
    print_object($data);
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
    redirect($CFG->wwwroot . "/course/view.php?id=$course->id".'&selected_week=1000');
}

/// Print the form
$site = get_site();

$streditcoursesettings = get_string("editcoursesettings");
$straddnewcourse = get_string("addnewcourse");
$stradministration = get_string("administration");
$strcategories = get_string("categories");
$navlinks = array();

if (!empty($course)) {    
    $PAGE->navbar->add($streditcoursesettings);
    $title = $streditcoursesettings;    
    $fullname = $course->fullname;    
} else {
    $PAGE->navbar->add($stradministration, new moodle_url('/admin/index.php'));
    $PAGE->navbar->add($strcategories, new moodle_url('/course/index.php'));
    $PAGE->navbar->add($straddnewcourse);
    $title = "$site->shortname: $straddnewcourse";
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
