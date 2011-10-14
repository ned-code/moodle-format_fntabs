<?php

defined('MOODLE_INTERNAL') || die;

require_once($CFG->libdir . '/formslib.php');
require_once($CFG->libdir . '/completionlib.php');
require_once($CFG->dirroot . '/course/edit_form.php');

class course_fntabs_edit_form extends course_edit_form {
    

    function definition() {
        
        $extraonly = optional_param('extraonly', 0, PARAM_INT);

        if (!$extraonly) {
            parent::definition();
        }
        $this->definition2($extraonly);
    }

    function definition2($extraonly) {
        global $USER, $CFG, $COURSE;

        $mform = & $this->_form;

        /// form definition with new course defaults
        if (!empty($this->params)) {
            
            foreach ($this->params as $param => $value) {
                $mform->addElement('hidden', $param, $value);
            }
        }

        // the upload manager is used directly in post precessing, moodleform::save_files() is not used yet
        //  $this->set_upload_manager(new upload_manager('logo', true, false, $this->_customdata['course'], false, $CFG->maxbytes, true, trset_upload_managerue));
        
        // Set the header FN course tabs
        $mform->addElement('header', 'FN Course Tabs', 'FN Course Tabs');
        $mform->addElement('hidden', 'extraonly', $extraonly);

        //For mainheading for the course
        $label = get_string('mainheading', 'format_fntabs');
        $mform->addElement('text', 'mainheading', $label, 'maxlength="254" size="50"');
        $mform->addRule('mainheading', get_string('missingmainheading','format_fntabs'), 'required', null, 'client');
        $mform->addHelpButton('mainheading', 'mainheading', 'format_fntabs');
        $mform->setDefault('mainheading', get_string('defaultmainheading', 'format_fntabs'));
        $mform->setType('mainheading', PARAM_MULTILANG);
        

        //For topic heading for example Week Section
        $label = get_string('topicheading', 'format_fntabs');
        $mform->addElement('text', 'topicheading', $label, 'maxlength="254" size="50"');
        $mform->addRule('topicheading', get_string('missingtopicheading','format_fntabs'), 'required', null, 'client');
        $mform->addHelpButton('topicheading', 'topicheading', 'format_fntabs');
        $mform->setDefault('topicheading', get_string('defaulttopicheading', 'format_fntabs'));
        $mform->setType('topicheading', PARAM_MULTILANG);
        

        //for changing the number of tab to show before next link
        $numberoftabs = array();
        for ($i = 12; $i <= 20; $i++) {
            $numberoftabs[$i] = $i;
        }

        $mform->addElement('select', 'maxtabs', get_string('setnumberoftabs', 'format_fntabs'), $numberoftabs);
        $mform->addHelpButton('maxtabs', 'setnumberoftabs', 'format_fntabs');
        $mform->setDefault('maxtabs', $numberoftabs[12]);

        //header for FN other setting 
        $mform->addElement('header', 'FN Other', 'FN Other');        

        //For shwosection 0 or not
        $choices["0"] = get_string("hide");
        $choices["1"] = get_string("show");
        $label = get_string('showsection0', 'format_fntabs');
        $mform->addElement('select', 'showsection0', $label, $choices);
        $mform->addHelpButton('showsection0', 'showsection0', 'format_fntabs');
        $mform->setDefault('showsection0', '0');
        unset($choices);

        //for shwo only section 0 setting
        $choices['0'] = get_string("no");
        $choices['1'] = get_string("yes");
        $label = get_string('showonlysection0', 'format_fntabs');
        $mform->addElement('select', 'showonlysection0', $label, $choices);
        $mform->addHelpButton('showonlysection0', 'showonlysection0', 'format_fntabs');
        $mform->setDefault('showonlysection0', '0');

        unset($choices);
        $choices["-1"] = 'none';
        for ($i = 0; $i <= $this->_customdata['course']->numsections; $i++) {
            $choices["$i"] = 'Section ' . $i;
        }
        

        $mform->addElement('hidden', 'shortname', get_string('shortnamecourse'), 'maxlength="100" size="20"');
        $mform->addHelpButton('shortname', 'shortnamecourse');
        $mform->addRule('shortname', get_string('missingshortname'), 'required', null, 'client');
        $mform->setType('shortname', PARAM_MULTILANG);
        if (!empty($course->id)) {
            $mform->hardFreeze('shortname');
            $mform->setConstant('shortname', $course->shortname);
        }
        

        $mform->addElement('hidden', 'id', $this->_customdata['course']->id);
        $mform->setType('id', PARAM_INT);
        $mform->addElement('hidden', 'formatsettings', '1');
        $mform->setType('formatsettings', PARAM_INT);

        /// Remove the already in place submit buttons and put them back at the end.
        if (!$extraonly) {
            $mform->removeElement('buttonar');
        }
        $this->add_action_buttons();
    }

/// perform some extra moodle validation
    function validation($data, $files) {        
        if (empty($data->extraonly)) {
            $errors = parent::validation($data, $files);           
            if (count($errors) ==0 ) {
                return true;
            } else {
                return $errors;
            }
        }
        return true;
    }

/// Handle any specific No Submit Buttons
    function no_submit_button_pressed() {
        global $CFG;

        $data = $this->_form->exportValues();

        if (isset($data['deletelogo']) && !empty($data['id']) && !empty($data['logofile'])) {
            /// If delete logo was pressed...
            $logo = $CFG->dataroot . '/' . $data['id'] . '/' . $data['logofile'];
            if (unlink($logo)) {
                global $DB;
                $DB->set_field('course_config_fn', 'value', '', array('courseid' => $data['id'], 'variable' => 'logo'));
                $this->_customdata['course']->logo = '';
                $link = get_string('notusinglogo', 'format_fntabs');
                $dbgrp = $this->_form->getElement('dbgrp');
                $elements = $dbgrp->getElements();
                foreach (array_keys($elements) as $key) {
                    if ('dbuttont' == $dbgrp->getElementName($key)) {
                        $element = & $elements[$key];
                        $element->setValue($link);
                        break;
                    }
                }
            }
        }

        return parent::no_submit_button_pressed();
    }

}
