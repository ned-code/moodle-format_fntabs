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
$PAGE->set_url('/course/format/fntabs/settings.php', array('id' => $id, 'extraonly' => '1'));


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
    
} else if ($categoryid) { // creating new course in this category
    $course = null;
    require_login();
    if (!$category = $DB->get_record('course_categories', array('id' => $categoryid))) {
        print_error('Category ID was incorrect');
    }
    require_capability('moodle/course:create', get_context_instance(CONTEXT_COURSECAT, $category->id));
    
} else {
    require_login();
    print_error('Either course id or category must be specified');
}

/// prepare course
$editoroptions = array('maxfiles' => EDITOR_UNLIMITED_FILES, 'maxbytes'=>$CFG->maxbytes, 'trusttext'=>false, 'noclean'=>true);
if (!empty($course)) {
    $allowedmods = array();
    if ($am = $DB->get_records('course_allowed_modules', array('course'=>$course->id))) {
        foreach ($am as $m) {
            $allowedmods[] = $m->module;
        }
    } else {
        // this happens in case we edit course created before enabling module restrictions or somebody disabled everything :-(
        if (empty($course->restrictmodules) and !empty($CFG->defaultallowedmodules)) {
            $allowedmods = explode(',', $CFG->defaultallowedmodules);
        }
    }
    $course->allowedmods = $allowedmods;
    $course = file_prepare_standard_editor($course, 'summary', $editoroptions, $coursecontext, 'course', 'summary', 0);

} else {
    $course = file_prepare_standard_editor($course, 'summary', $editoroptions, null, 'course', 'summary', null);
}


/// Need the bigger course object, including any extras.
$cobject = new course_format_fn($course);
$course = clone($cobject->course);
unset($cobject);

/// first create the form
$editform = new course_fntabs_edit_form('settings.php', compact('course', 'category'));

if ($editform->is_cancelled()) {
    if (empty($course)) {
        redirect($CFG->wwwroot);
    } else {
        redirect($CFG->wwwroot . '/course/view.php?id=' . $course->id);        
    }
} else if ($data = $editform->get_data()) {
    
    
    if (empty($data->extraonly)) {
            if (empty($course->id)) {
            // In creating the course
            $course = create_course($data, $editoroptions);

            // Get the context of the newly created course
            $context = get_context_instance(CONTEXT_COURSE, $course->id, MUST_EXIST);

            if (!empty($CFG->creatornewroleid) and !is_viewing($context, NULL, 'moodle/role:assign') and !is_enrolled($context, NULL, 'moodle/role:assign')) {
                // deal with course creators - enrol them internally with default role
                enrol_try_internal_enrol($course->id, $USER->id, $CFG->creatornewroleid);

            }
            if (!is_enrolled($context)) {
                // Redirect to manual enrolment page if possible
                $instances = enrol_get_instances($course->id, true);
                foreach($instances as $instance) {
                    if ($plugin = enrol_get_plugin($instance->enrol)) {
                        if ($plugin->get_manual_enrol_link($instance)) {
                            // we know that the ajax enrol UI will have an option to enrol
                            redirect(new moodle_url('/enrol/users.php', array('id'=>$course->id)));
                        }
                    }
                }
            }
        } else {
            // Save any changes to the files used in the editor
            update_course($data, $editoroptions);
        }
    }

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
    redirect($CFG->wwwroot . "/course/view.php?id=$course->id");
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
