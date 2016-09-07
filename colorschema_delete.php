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

$delete = optional_param('delete', 0, PARAM_INT);
$courseid = optional_param('courseid', 0, PARAM_INT);
$process = optional_param('process', 0, PARAM_INT);

require_login(null, false);

// Permission.
$coursecontext = context_course::instance($courseid);
require_capability('moodle/course:update', $coursecontext);

$PAGE->set_url('/course/format/fntabs/colorschema_delete.php',
    array('delete' => $delete, 'courseid' => $courseid)
);
$PAGE->set_context(context_system::instance());
$PAGE->set_pagelayout('course');
$title = get_string('delete', 'format_fntabs');
$heading = $SITE->fullname;
$PAGE->set_title($heading);
$PAGE->set_heading($heading);

// Breadcrumb.
$PAGE->navbar->add(get_string('pluginname', 'format_fntabs'));
$PAGE->navbar->add(get_string('settings', 'format_fntabs'),
    new moodle_url('/course/format/fntabs/tabsettings.php', array('id' => $courseid))
);
$PAGE->navbar->add(get_string('colorschemas', 'format_fntabs'),
    new moodle_url('/course/format/fntabs/colorschema.php', array('courseid' => $courseid))
);
$PAGE->navbar->add($title);

if (!$toform = $DB->get_record('format_fntabs_color', array('id' => $delete, 'predefined' => 0))) {
    redirect(new moodle_url('/course/format/fntabs/colorschema.php', array('courseid' => $courseid)));
}

$colorschema = $DB->get_record('format_fntabs_color', array('id' => $delete, 'predefined' => 0), '*', MUST_EXIST);

if ($process) {
    require_sesskey();
    $DB->delete_records('format_fntabs_color', array('id' => $delete, 'predefined' => 0));

    redirect(new moodle_url('/course/format/fntabs/colorschema.php', array('courseid' => $courseid)),
        get_string('successful', 'format_fntabs'), 1
    );
    die;
} else {
    echo $OUTPUT->header();
    echo html_writer::tag('h1', $title, array('class' => 'page-title'));
    echo $OUTPUT->confirm('<div><strong>'.
        get_string('colorschema', 'format_fntabs').': </strong>'.$colorschema->name.
        '<br><br>'.
        '</div>'.
        get_string('deleteconfirmmsg', 'format_fntabs').'<br><br>',
        new moodle_url('/course/format/fntabs/colorschema_delete.php',
            array('courseid' => $courseid, 'delete' => $delete, 'process' => 1)
        ),
        new moodle_url('/course/format/fntabs/colorschema.php', array('courseid' => $courseid))
    );
    echo $OUTPUT->footer();
}