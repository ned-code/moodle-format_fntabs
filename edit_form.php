<?php

//$Id: edit_form.php,v 1.3 2009/05/04 21:13:33 mchurch Exp $

defined('MOODLE_INTERNAL') || die;
require_once($CFG->libdir . '/formslib.php');
require_once($CFG->libdir . '/completionlib.php');

require_once($CFG->dirroot . '/course/edit_form.php');

class course_fntabs_edit_form extends course_edit_form {

    function definition() {
        $extraonly = optional_param('extraonly', 0, PARAM_INT);    // full settings or only the extra ones.

        if (!$extraonly) {
            parent::definition();
        }
        $this->definition2($extraonly);
    }

    function definition2($extraonly) {

        global $USER, $CFG, $COURSE;

        $mform = & $this->_form;


/// form definition with new course defaults
//--------------------------------------------------------------------------------
        if (!empty($this->params)) {
            foreach ($this->params as $param => $value) {
                $mform->addElement('hidden', $param, $value);
            }
        }

        // the upload manager is used directly in post precessing, moodleform::save_files() is not used yet
        #  $this->set_upload_manager(new upload_manager('logo', true, false, $this->_customdata['course'], false, $CFG->maxbytes, true, trset_upload_managerue));

        $mform->addElement('header', 'FN Course Tabs', 'FN Course Tabs');
        $mform->addElement('hidden', 'extraonly', $extraonly);
        $label = get_string('mainheading', 'format_fntabs');
        $mform->addElement('text', 'mainheading', $label, 'maxlength="254" size="50"');
        $mform->setDefault('mainheading', get_string('defaultmainheading', 'format_fntabs'));
        $mform->setType('mainheading', PARAM_MULTILANG);
        $label = get_string('topicheading', 'format_fntabs');
        $mform->addElement('text', 'topicheading', $label, 'maxlength="254" size="50"');
        $mform->setDefault('topicheading', get_string('defaulttopicheading', 'format_fntabs'));
        $mform->setType('topicheading', PARAM_MULTILANG);
        $choices["0"] = get_string("hide");
        $choices["1"] = get_string("show");        
        $mform->addElement('header', 'FN Other', 'FN Other');
        unset($choices);
        $choices["0"] = get_string("hide");
        $choices["1"] = get_string("show");
        $label = get_string('showsection0', 'format_fntabs');
        $mform->addElement('select', 'showsection0', $label, $choices);
        $mform->setDefault('showsection0', '0');
        unset($choices);
        $choices['0'] = get_string("no");
        $choices['1'] = get_string("yes");
        $label = get_string('showonlysection0', 'format_fntabs');
        $mform->addElement('select', 'showonlysection0', $label, $choices);

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
            if (0 == count($errors)) {
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
                $link = get_string('notusinglogo', 'format_fn');
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
