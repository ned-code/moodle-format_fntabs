<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * @package    format_fntabs
 * @copyright  Michael Gardener <mgardener@cissq.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir.'/formslib.php');

class colorschema_form extends moodleform {
    public function definition() {

        global $CFG, $OUTPUT;

        $mform = $this->_form;
        $mform->addElement('header', '', get_string('colourschema', 'format_fntabs'), '');

        $mform->addElement('html', '<table style="width:100%"><tr><td>');

        MoodleQuickForm::registerElementType('ftcolourpopup',
            "$CFG->dirroot/course/format/fntabs/js/ft_colourpopup.php", 'MoodleQuickForm_ftcolourpopup');

        $mform->addElement('text', 'name', get_string('name', 'format_fntabs'));
        $mform->setType('name', PARAM_RAW);
        $mform->addRule('name', null, 'required', null, 'client');

        $mform->addElement('ftcolourpopup', 'bgcolour',
            get_string('bgcolour', 'format_fntabs'), 'maxlength="6" size="6"');
        $mform->setType('bgcolour', PARAM_ALPHANUM);
        $mform->addRule('bgcolour', null, 'required', null, 'client');

        $mform->addElement('ftcolourpopup', 'activecolour',
            get_string('activeweek', 'format_fntabs'), 'maxlength="6" size="6"');
        $mform->setType('activecolour', PARAM_ALPHANUM);
        $mform->addRule('activecolour', null, 'required', null, 'client');

        $mform->addElement('ftcolourpopup', 'selectedcolour',
            get_string('selectedweek', 'format_fntabs'), 'maxlength="6" size="6"');
        $mform->setType('selectedcolour', PARAM_ALPHANUM);
        $mform->addRule('selectedcolour', null, 'required', null, 'client');

        $mform->addElement('ftcolourpopup', 'inactivecolour',
            get_string('inactiveweek', 'format_fntabs'), 'maxlength="6" size="6"');
        $mform->setType('inactivecolour', PARAM_ALPHANUM);
        $mform->addRule('inactivecolour', null, 'required', null, 'client');

        $mform->addElement('ftcolourpopup', 'inactivebgcolour',
            get_string('inactivebgcolour', 'format_fntabs'), 'maxlength="6" size="6"');
        $mform->setType('inactivebgcolour', PARAM_ALPHANUM);
        $mform->addRule('inactivebgcolour', null, 'required', null, 'client');

        $mform->addElement('ftcolourpopup', 'activelinkcolour',
            get_string('activelinkcolour', 'format_fntabs'), 'maxlength="6" size="6"');
        $mform->setType('activelinkcolour', PARAM_ALPHANUM);
        $mform->addRule('activelinkcolour', null, 'required', null, 'client');

        $mform->addElement('ftcolourpopup', 'selectedlinkcolour',
            get_string('selectedlinkcolour', 'format_fntabs'), 'maxlength="6" size="6"');
        $mform->setType('selectedlinkcolour', PARAM_ALPHANUM);
        $mform->addRule('selectedlinkcolour', null, 'required', null, 'client');

        $mform->addElement('ftcolourpopup', 'inactivelinkcolour',
            get_string('inactivelinkcolour', 'format_fntabs'), 'maxlength="6" size="6"');
        $mform->setType('inactivelinkcolour', PARAM_ALPHANUM);
        $mform->addRule('inactivelinkcolour', null, 'required', null, 'client');

        $mform->addElement('html', '</td><td width="320px">');

        $mform->addElement('html', '<img src="'.$OUTPUT->pix_url('ned_tabs_colourkey', 'format_fntabs').'" />');

        $mform->addElement('html', '</td></tr></table>');

        $mform->addElement('hidden', 'add');
        $mform->setType('add', PARAM_INT);

        $mform->addElement('hidden', 'edit');
        $mform->setType('edit', PARAM_INT);

        $mform->addElement('hidden', 'courseid');
        $mform->setType('courseid', PARAM_INT);

        $this->add_action_buttons(true, get_string('submit', 'format_fntabs'));
    }

    public function validation($data, $files) {
        $errors = array();
        return $errors;
    }
}