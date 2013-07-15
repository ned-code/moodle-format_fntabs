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

        $mform->addElement('hidden', 'id', $this->_customdata['course']->id);
        $mform->setType('id', PARAM_INT);
        $mform->addElement('hidden', 'defaulttabwhenset', time());
        $mform->setType('defaulttabwhenset', PARAM_INT);

        //get showsection0 heading from database         

        $mform->addElement('header', 'fncoursetabs', 'Tabs');

        //For mainheading for the course
        $label = get_string('mainheading', 'format_fntabs');
        $mform->addElement('text', 'mainheading', $label, 'maxlength="24" size="25"');
        $mform->addRule('mainheading', get_string('missingmainheading', 'format_fntabs'), 'required', null, 'client');
        $mform->setDefault('mainheading', get_string('defaultmainheading', 'format_fntabs'));
        $mform->setType('mainheading', PARAM_MULTILANG);
        //For topic heading for example Week Section
        $label = get_string('topicheading', 'format_fntabs');
        $mform->addElement('text', 'topicheading', $label, 'maxlength="24" size="25"');
        $mform->addRule('topicheading', get_string('missingtopicheading', 'format_fntabs'), 'required', null, 'client');
        $mform->setDefault('topicheading', get_string('defaulttopicheading', 'format_fntabs'));
        $mform->setType('topicheading', PARAM_MULTILANG);

        //for changing the number of tab to show before next link
        $numberoftabs = array();
        for ($i = 12; $i <= 20; $i++) {
            $numberoftabs[$i] = $i;
        }

        $mform->addElement('select', 'maxtabs', get_string('setnumberoftabs', 'format_fntabs'), $numberoftabs);
        $mform->setDefault('maxtabs', $numberoftabs[12]);

        //////work to be done for default tab
        $radioarray = array();
        $attributes = array();
        $radioarray[] = $mform->createElement('radio', 'defaulttab', '', get_string('default_tab_text', 'format_fntabs'), 'option1', array('checked' => true, 'class' => 'padding_before_radio', 'style' => 'padding-left:10px;'));
        // add second option if the course completion is enabled
        $completion = new completion_info($course);
        if ($completion->is_enabled()) {
            $radioarray[] = $mform->createElement('radio', 'defaulttab', '', get_string('default_tab_notattempted_text', 'format_fntabs'), 'option2');
        }

        $radioarray[] = $mform->createElement('radio', 'defaulttab', '', get_string('default_tab_specifyweek_text', 'format_fntabs'), 'option3');
        $mform->addGroup($radioarray, 'radioar', get_string('label_deafulttab_text', 'format_fntabs'), array('<br />'), false);
        $mform->setDefault('defaulttab', 'option1');


        //dropdown will contain week upto current section
        $timenow = time();
        $weekdate = $course->startdate;    // this should be 0:00 Monday of that week
        $weekdate += 7200;              // Add two hours to avoid possible DST problems        
        $weekofseconds = 604800;
        $course->enddate = $course->startdate + ($weekofseconds * $course->numsections);


        //  Calculate the current week based on today's date and the starting date of the course.
        $currentweek = ($timenow > $course->startdate) ?
                (int) ((($timenow - $course->startdate) / $weekofseconds) + 1) : 0;

        $currentweek = min($currentweek, $course->numsections);
        $topiclist = array();
        if ($currentweek > 0) {
            for ($i = 1; $i <= $currentweek; $i++) {
                $topiclist[$i] = $i;
            }
        } else {
            $topiclist[1] = 1;
        }

        ////////////
        $mform->addElement('select', 'topictoshow', '', $topiclist, array('class' => 'ddl_padding'));
        $mform->setDefault('topictoshow', $topiclist[1]);
        ///default tab end


        $mform->addElement('header', 'fncoursecolours', 'Colors');

        $mform->addElement('html', '<table style="width:100%"><tr><td>');
        
        
        
        

        MoodleQuickForm::registerElementType('tccolourpopup', "$CFG->dirroot/course/format/fntabs/js/tc_colourpopup.php", 'MoodleQuickForm_tccolourpopup');



        

        $mform->addElement('tccolourpopup', 'bgcolour', get_string('bgcolour', 'format_fntabs'), 'maxlength="6" size="6"');
        $mform->setType('bgcolour', PARAM_ALPHANUM);

        $mform->addElement('tccolourpopup', 'activecolour', get_string('activeweek', 'format_fntabs'), 'maxlength="6" size="6"');
        $mform->setType('activecolour', PARAM_ALPHANUM);

        $mform->addElement('tccolourpopup', 'selectedcolour', get_string('selectedweek', 'format_fntabs'), 'maxlength="6" size="6"');
        $mform->setType('selectedcolour', PARAM_ALPHANUM);

        $mform->addElement('tccolourpopup', 'inactivecolour', get_string('inactiveweek', 'format_fntabs'), 'maxlength="6" size="6"');
        $mform->setType('inactivecolour', PARAM_ALPHANUM);

        $mform->addElement('tccolourpopup', 'inactivebgcolour', get_string('inactivebgcolour', 'format_fntabs'), 'maxlength="6" size="6"');
        $mform->setType('inactivebgcolour', PARAM_ALPHANUM);

        $mform->addElement('tccolourpopup', 'activelinkcolour', get_string('activelinkcolour', 'format_fntabs'), 'maxlength="6" size="6"');
        $mform->setType('activelinkcolour', PARAM_ALPHANUM);

        $mform->addElement('tccolourpopup', 'inactivelinkcolour', get_string('inactivelinkcolour', 'format_fntabs'), 'maxlength="6" size="6"');
        $mform->setType('inactivelinkcolour', PARAM_ALPHANUM);

        $mform->addElement('tccolourpopup', 'highlightcolour', get_string('highlightcolour', 'format_fntabs'), 'maxlength="6" size="6"');
        $mform->setType('highlightcolour', PARAM_ALPHANUM);


        $mform->addElement('html', '</td><td width="320px">');

        $mform->addElement('html', '<img src="pix/fntabs_colourkey.png" />');

        $mform->addElement('html', '</td></tr></table>');

        //header for FN other setting 
        $mform->addElement('header', 'Section0', 'Section 0');
        //For shwosection 0 or not
        $choices['0'] = get_string("hide");
        $choices['1'] = get_string("show");
        $label = get_string('showsection0', 'format_fntabs');
        $mform->addElement('select', 'showsection0', $label, $choices);
        $mform->setDefault('showsection0', $choices['0']);
        unset($choices);

        //for shwo only section 0 setting
        $choices['0'] = get_string("no");
        $choices['1'] = get_string("yes");
        $label = get_string('showonlysection0', 'format_fntabs');
        $mform->addElement('select', 'showonlysection0', $label, $choices);
        $mform->setDefault('showonlysection0', $choices['0']);
        unset($choices);
        $this->add_action_buttons();
    }

}
