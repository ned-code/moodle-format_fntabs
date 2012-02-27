<?php

defined('MOODLE_INTERNAL') || die;

require_once("$CFG->libdir/formslib.php");
require_once($CFG->libdir . '/completionlib.php');
require_once($CFG->dirroot . '/course/edit_form.php');

class course_fntabs_edit_form extends moodleform {

    function definition() {

        global $CFG, $COURSE, $DB;


        $mform = & $this->_form;
        /// form definition with new course defaults
        $course = $this->_customdata['course']; // this contains the data of this form 
        if (!empty($course->id)) {
            $coursecontext = get_context_instance(CONTEXT_COURSE, $course->id);
            $context = $coursecontext;
        }
        //get the max tabs set from database       
        $mform->addElement('hidden', 'id', $this->_customdata['course']->id);
        $mform->setType('id', PARAM_INT);

        $configvariabletabs = $DB->record_exists('course_config_fn', array('courseid' => $course->id, 'variable' => 'maxtabs'));
        if (isset($configvariabletabs)) {
            $maxtabsindb = $DB->get_field('course_config_fn', 'value', array('courseid' => $course->id, 'variable' => 'maxtabs'));
        }


        //get mainheading from database
        $configvariabeheading = $DB->record_exists('course_config_fn', array('courseid' => $course->id, 'variable' => 'mainheading'));
        if (isset($configvariabletabs)) {
            $mainheadingindb = $DB->get_field('course_config_fn', 'value', array('courseid' => $course->id, 'variable' => 'mainheading'));
        }


        //get topic heading from database
        $configvariabetopicheading = $DB->record_exists('course_config_fn', array('courseid' => $course->id, 'variable' => 'topicheading'));
        if (isset($configvariabetopicheading)) {
            $topicmainheadingindb = $DB->get_field('course_config_fn', 'value', array('courseid' => $course->id, 'variable' => 'topicheading'));
        }


        //get showsection0 heading from database
        $configvariabeshowsection0 = $DB->record_exists('course_config_fn', array('courseid' => $course->id, 'variable' => 'showsection0'));
        if (isset($configvariabeshowsection0)) {
            $showsection0indb = $DB->get_field('course_config_fn', 'value', array('courseid' => $course->id, 'variable' => 'showsection0'));
        }


        //get showsectiononly0 heading from database
        $configvariabeshowonlysection0 = $DB->record_exists('course_config_fn', array('courseid' => $course->id, 'variable' => 'showonlysection0'));
        if (isset($configvariabeshowonlysection0)) {
            $showonlysection0indb = $DB->get_field('course_config_fn', 'value', array('courseid' => $course->id, 'variable' => 'showonlysection0'));
        }

        $mform->addElement('header', 'FN Course Tabs', 'FN Course Tabs');
        // $mform->addElement('hidden', 'extraonly', $extraonly); 
        //For mainheading for the course
        $label = get_string('mainheading', 'format_fntabs');
        $mform->addElement('text', 'mainheading', $label, 'maxlength="254" size="50"');
        $mform->addRule('mainheading', get_string('missingmainheading', 'format_fntabs'), 'required', null, 'client');
        $mform->addHelpButton('mainheading', 'mainheading', 'format_fntabs');

        if (isset($mainheadingindb)) {
            $mform->setDefault('mainheading', $mainheadingindb);
        } else {
            $mform->setDefault('mainheading', get_string('defaultmainheading', 'format_fntabs'));
        }

        $mform->setType('mainheading', PARAM_MULTILANG);
        //For topic heading for example Week Section
        $label = get_string('topicheading', 'format_fntabs');
        $mform->addElement('text', 'topicheading', $label, 'maxlength="254" size="50"');
        $mform->addRule('topicheading', get_string('missingtopicheading', 'format_fntabs'), 'required', null, 'client');
        $mform->addHelpButton('topicheading', 'topicheading', 'format_fntabs');

        if (isset($topicmainheadingindb)) {
            $mform->setDefault('topicheading', $topicmainheadingindb);
        } else {
            $mform->setDefault('topicheading', get_string('defaulttopicheading', 'format_fntabs'));
        }

        $mform->setType('topicheading', PARAM_MULTILANG);

        //for changing the number of tab to show before next link
        $numberoftabs = array();
        for ($i = 12; $i <= 20; $i++) {
            $numberoftabs[$i] = $i;
        }

        $mform->addElement('select', 'maxtabs', get_string('setnumberoftabs', 'format_fntabs'), $numberoftabs);
        $mform->addHelpButton('maxtabs', 'setnumberoftabs', 'format_fntabs');

        if (isset($maxtabsindb)) {
            $mform->setDefault('maxtabs', $maxtabsindb);
        } else {
            $mform->setDefault('maxtabs', $numberoftabs[12]);
        }

        //header for FN other setting 
        $mform->addElement('header', 'FN Other', 'FN Other');
        //For shwosection 0 or not
        $choices["0"] = get_string("hide");
        $choices["1"] = get_string("show");
        $label = get_string('showsection0', 'format_fntabs');
        $mform->addElement('select', 'showsection0', $label, $choices);
        $mform->addHelpButton('showsection0', 'showsection0', 'format_fntabs');
        if (isset($showsection0indb)) {
            $mform->setDefault('showsection0', $showsection0indb);
        } else {
            $mform->setDefault('showsection0', '0');
        }

        unset($choices);
        //for shwo only section 0 setting
        $choices['0'] = get_string("no");
        $choices['1'] = get_string("yes");
        $label = get_string('showonlysection0', 'format_fntabs');
        $mform->addElement('select', 'showonlysection0', $label, $choices);
        $mform->addHelpButton('showonlysection0', 'showonlysection0', 'format_fntabs');
        if (isset($showonlysection0indb)) {
            $mform->setDefault('showonlysection0', $showonlysection0indb);
        } else {
            $mform->setDefault('showonlysection0', '0');
        }


        unset($choices);

        /// Remove the already in place submit buttons and put them back at the end.        
        $this->add_action_buttons();
    }

}
