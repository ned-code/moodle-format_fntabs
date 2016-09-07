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

require_once('../../../config.php');
require_once('colorschema_form.php');

$edit = optional_param('edit', 0, PARAM_INT);
$add = optional_param('add', 0, PARAM_INT);
$duplicate = optional_param('duplicate', 0, PARAM_INT);
$courseid = optional_param('courseid', 0, PARAM_INT);

require_login(null, false);

// Permission.
$coursecontext = context_course::instance($courseid);
require_capability('moodle/course:update', $coursecontext);

if ($duplicate) {
    if (!$schema = $DB->get_record('format_fntabs_color', array('id' => $duplicate))) {
        redirect(new moodle_url('/course/format/fntabs/colorschema.php', array('courseid' => $courseid)));
    }
    $schema->name = $schema->name.' [duplicate]';
    $schema->predefined = 0;
    unset($schema->id);
    unset($schema->timemodified);
    $schema->timecreated = time();
    $schemaid = $DB->insert_record('format_fntabs_color', $schema);
    redirect(new moodle_url('/course/format/fntabs/colorschema_edit.php', array('courseid' => $courseid, 'edit' => $schemaid)));
}

$PAGE->https_required();

$thispageurl = new moodle_url('/course/format/fntabs/colorschema_edit.php',
    array('courseid' => $courseid, 'edit' => $edit, 'add' => $add)
);

$PAGE->set_url($thispageurl);
$PAGE->set_pagelayout('course');
$PAGE->set_context($coursecontext);
$PAGE->verify_https_required();

$name = get_string('addedit', 'format_fntabs');
$title = get_string('addedit', 'format_fntabs');
$heading = $SITE->fullname;

// Breadcrumb.
$PAGE->navbar->add(get_string('pluginname', 'format_fntabs'));
$PAGE->navbar->add(get_string('settings', 'format_fntabs'),
    new moodle_url('/course/format/fntabs/tabsettings.php', array('id' => $courseid))
);
$PAGE->navbar->add(get_string('colorschemas', 'format_fntabs'),
    new moodle_url('/course/format/fntabs/colorschema.php', array('courseid' => $courseid))
);
$PAGE->navbar->add($name);

$PAGE->set_title($title);
$PAGE->set_heading($heading);

$mform = new colorschema_form();

if ($edit) {
    if (!$toform = $DB->get_record('format_fntabs_color', array('id' => $edit, 'predefined' => 0))) {
        redirect(new moodle_url('/course/format/fntabs/colorschema.php', array('courseid' => $courseid)));
    }
}

if ($mform->is_cancelled()) {
    redirect(new moodle_url('/course/format/fntabs/colorschema.php', array('courseid' => $courseid)));
} else if ($fromform = $mform->get_data()) {
    $rec = new stdClass();
    $rec->name = $fromform->name;
    $rec->courseid = $fromform->courseid;
    $rec->bgcolour = $fromform->bgcolour;
    $rec->activecolour = $fromform->activecolour;
    $rec->selectedcolour = $fromform->selectedcolour;
    $rec->inactivecolour = $fromform->inactivecolour;
    $rec->inactivebgcolour = $fromform->inactivebgcolour;
    $rec->activelinkcolour = $fromform->activelinkcolour;
    $rec->selectedlinkcolour = $fromform->selectedlinkcolour;
    $rec->inactivelinkcolour = $fromform->inactivelinkcolour;

    if ($add) {
        $rec->timecreated = time();
        $rec->id = $DB->insert_record('format_fntabs_color', $rec);
        redirect(new moodle_url('/course/format/fntabs/colorschema.php', array('courseid' => $courseid)),
            get_string('successful', 'format_fntabs'), 0);
    } else {
        $rec->id = $fromform->edit;
        $rec->timemodified = time();
        $DB->update_record('format_fntabs_color', $rec);
        redirect(new moodle_url('/course/format/fntabs/colorschema.php', array('courseid' => $courseid)),
            get_string('successful', 'format_fntabs'), 0);
    }
    exit;
}

echo $OUTPUT->header();
if ($edit) {
    $toform->edit = $edit;
    $toform->courseid = $courseid;
    $mform->set_data($toform);
} else {
    $toform = new stdClass();
    $toform->add = $add;
    $toform->courseid = $courseid;
    $mform->set_data($toform);
}

$mform->display();

echo $OUTPUT->footer();