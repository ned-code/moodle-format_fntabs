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
require_once($CFG->dirroot.'/lib/excellib.class.php');

$courseid   = required_param('courseid', PARAM_INT);

// Paging options.
$page      = optional_param('page', 0, PARAM_INT);
$perpage   = optional_param('perpage', 20, PARAM_INT);
$sort      = optional_param('sort', 'name', PARAM_ALPHANUM);
$dir       = optional_param('dir', 'ASC', PARAM_ALPHA);
// Action.
$action    = optional_param('action', false, PARAM_ALPHA);
$search    = optional_param('search', '', PARAM_TEXT);

require_login(null, false);

// Permission.
$coursecontext = context_course::instance($courseid);
require_capability('moodle/course:update', $coursecontext);

$thispageurl = new moodle_url('/course/format/fntabs/colorschema.php', array('courseid' => $courseid));

$PAGE->set_url($thispageurl);
$PAGE->set_pagelayout('course');
$PAGE->set_context($coursecontext);

$course = $DB->get_record('course', array('id' => $courseid));
$name = get_string('colorschemas', 'format_fntabs');
$title = get_string('colorschemas', 'format_fntabs');
$heading = $SITE->fullname;

// Breadcrumb.
if ($course) {
    $PAGE->navbar->add($course->shortname,
        new moodle_url('/course/view.php', array('id' => $course->id))
    );
}
$PAGE->navbar->add(get_string('pluginname', 'format_fntabs'));
$PAGE->navbar->add(get_string('settings', 'format_fntabs'),
    new moodle_url('/course/format/fntabs/tabsettings.php', array('id' => $courseid))
);
$PAGE->navbar->add($name);

$PAGE->set_title($title);
if ($course) {
    $PAGE->set_heading($course->fullname);
} else {
    $PAGE->set_heading($heading);
}

$datacolumns = array(
    'id' => 'tc.id',
    'name' => 'tc.name',
    'predefined' => 'tc.predefined',
    'timecreated' => 'tc.timecreated',
    'timemodified' => 'tc.timemodified'
);

// Filter.
$where = '';
if ($search) {
    $where .= " AND ".$datacolumns['name']." LIKE '%$search%'";
}

// Sort.
$order = '';
if ($sort) {
    $order = " ORDER BY $datacolumns[$sort] $dir";
}

// Count records for paging.
$countsql = "SELECT COUNT(1) FROM {format_fntabs_color} tc WHERE 0 = 0 $where";
$totalcount = $DB->count_records_sql($countsql);

// Table columns.
$columns = array(
    'rowcount',
    'name',
    'predefined',
    'timecreated',
    'timemodified',
    'action'
);

$sql = "SELECT tc.*
          FROM {format_fntabs_color} tc
         WHERE 0=0
               $where
               $order";

foreach ($columns as $column) {
    $string[$column] = get_string($column, 'format_fntabs');
    if ($sort != $column) {
        $columnicon = "";
        if ($column == "name") {
            $columndir = "ASC";
        } else {
            $columndir = "ASC";
        }
    } else {
        $columndir = $dir == "ASC" ? "DESC" : "ASC";
        if ($column == "minpoint") {
            $columnicon = ($dir == "ASC") ? "sort_asc" : "sort_desc";
        } else {
            $columnicon = ($dir == "ASC") ? "sort_asc" : "sort_desc";
        }
        $columnicon = "<img class='iconsort' src=\"" . $OUTPUT->pix_url('t/' . $columnicon) . "\" alt=\"\" />";

    }
    if (($column == 'rowcount') || ($column == 'action')) {
        $$column = $string[$column];
    } else {
        $sorturl = $thispageurl;
        $sorturl->param('perpage', $perpage);
        $sorturl->param('sort', $column);
        $sorturl->param('dir', $columndir);
        $sorturl->param('search', $search);

        $$column = html_writer::link($sorturl->out(false), $string[$column]).$columnicon;
    }
}

$table = new html_table();

$table->head = array();
$table->wrap = array();
foreach ($columns as $column) {
    $table->head[$column] = $$column;
    $table->wrap[$column] = '';
}

// Override cell wrap.
$table->wrap['action'] = 'nowrap';

$tablerows = $DB->get_records_sql($sql, null, $page * $perpage, $perpage);

$counter = ($page * $perpage);

foreach ($tablerows as $tablerow) {
    $row = new html_table_row();
    $actionlinks = '';
    foreach ($columns as $column) {
        $varname = 'cell'.$column;

        switch ($column) {
            case 'rowcount':
                $$varname = ++$counter;
                break;
            case 'timecreated':
            case 'timemodified':
                $$varname = '-';
                if ($tablerow->$column > 0) {
                    $$varname = new html_table_cell(date("m/d/Y g:i A", $tablerow->$column));
                }
                break;
            case 'predefined':
                if ($tablerow->$column > 0) {
                    $$varname = new html_table_cell(get_string('yes', 'format_fntabs'));
                } else {
                    $$varname = new html_table_cell('-');
                }
                break;
            case 'action':
                // Duplicate.
                if (has_capability('moodle/course:update', $coursecontext)) {
                    $actionurl = new moodle_url('/course/format/fntabs/colorschema_edit.php',
                        array('courseid' => $courseid, 'duplicate' => $tablerow->id )
                    );
                    $actioniconurl = $OUTPUT->pix_url('t/copy', '');
                    $actionicontext = get_string('duplicate', 'format_fntabs');
                    $actionicon = html_writer::img($actioniconurl, $actionicontext,
                        array('width' => '16', 'height' => '16')
                    );
                    $actionlinks .= html_writer::link($actionurl->out(false), $actionicon, array(
                            'class' => 'actionlink',
                            'title' => $actionicontext)).' ';
                }
                // Edit.
                if (has_capability('moodle/course:update', $coursecontext) && !$tablerow->predefined) {
                    $actionurl = new moodle_url('/course/format/fntabs/colorschema_edit.php',
                        array('courseid' => $courseid, 'edit' => $tablerow->id )
                    );
                    $actioniconurl = $OUTPUT->pix_url('t/edit', '');
                    $actionicontext = get_string('edit', 'format_fntabs');
                    $actionicon = html_writer::img($actioniconurl, $actionicontext, array('width' => '16', 'height' => '16'));
                    $actionlinks .= html_writer::link($actionurl->out(false), $actionicon, array(
                            'class' => 'actionlink',
                            'title' => $actionicontext)).' ';
                }
                // Delete.
                if (has_capability('moodle/course:update', $coursecontext) && !$tablerow->predefined) {
                    $actionurl = new moodle_url('/course/format/fntabs/colorschema_delete.php',
                        array('courseid' => $courseid, 'delete' => $tablerow->id )
                    );
                    $actioniconurl = $OUTPUT->pix_url('t/delete', '');
                    $actionicontext = get_string('delete', 'format_fntabs');
                    $actionicon = html_writer::img($actioniconurl, $actionicontext, array('width' => '16', 'height' => '16'));
                    $actionlinks .= html_writer::link($actionurl->out(false), $actionicon, array(
                            'class' => 'actionlink',
                            'title' => $actionicontext)).' ';
                }

                $$varname = new html_table_cell($actionlinks);
                break;
            default:
                $$varname = new html_table_cell($tablerow->$column);
        }
    }

    $row->cells = array();
    foreach ($columns as $column) {
        $varname = 'cell' . $column;
        $row->cells[$column] = $$varname;
    }
    $table->data[] = $row;

}

echo $OUTPUT->header();
echo html_writer::start_div('page-content-wrapper', array('id' => 'page-content'));
echo html_writer::tag('h1', $title, array('class' => 'page-title'));

// The view options.
$searchformurl = new moodle_url('/course/format/fntabs/colorschema.php');

$searchform = html_writer::tag('form',
    html_writer::empty_tag('input', array(
        'type' => 'hidden',
        'name' => 'sesskey',
        'value' => sesskey(),
    )).
    html_writer::empty_tag('input', array(
        'type' => 'hidden',
        'name' => 'perpage',
        'value' => $perpage,
    )).
    html_writer::empty_tag('input', array(
        'type' => 'hidden',
        'name' => 'sort',
        'value' => $sort,
    )).
    html_writer::empty_tag('input', array(
        'type' => 'hidden',
        'name' => 'dir',
        'value' => $dir,
    )).
    html_writer::empty_tag('input', array(
        'type' => 'hidden',
        'name' => 'courseid',
        'value' => $courseid,
    )).
    html_writer::empty_tag('input', array(
        'type' => 'text',
        'name' => 'search',
        'value' => $search,
        'class' => 'search-textbox',
    )).
    html_writer::empty_tag('input', array(
        'type' => 'submit',
        'value' => 'Search',
        'class' => 'search-submit-btn',
    )),
    array(
        'action' => $searchformurl->out(),
        'method' => 'post',
        'autocomplete' => 'off'
    )
);
echo html_writer::div($searchform, 'search-form-wrapper', array('id' => 'search-form'));

$pagingurl = new moodle_url('/course/format/fntabs/colorschema.php?',
    array(
        'perpage' => $perpage,
        'sort' => $sort,
        'dir' => $dir,
        'courseid' => $courseid,
        'search' => $search
    )
);

$pagingbar = new paging_bar($totalcount, $page, $perpage, $pagingurl, 'page');

echo $OUTPUT->render($pagingbar);
echo html_writer::table($table);
echo $OUTPUT->render($pagingbar);

// Add record form.
if (has_capability('moodle/course:update', $coursecontext)) {
    $formurl = new moodle_url('/course/format/fntabs/colorschema_edit.php',
        array('courseid' => $courseid, 'add' => '1')
    );
    $submitbutton  = html_writer::tag('button', get_string('add', 'format_fntabs'), array(
        'class' => 'spark-add-record-btn',
        'type' => 'submit',
        'value' => 'submit',
    ));
    $form = html_writer::tag('form', $submitbutton, array(
        'action' => $formurl->out(false),
        'method' => 'post',
        'style' => 'float: left;',
        'autocomplete' => 'off'
    ));

    $formurlclose = new moodle_url('/course/format/fntabs/tabsettings.php',
        array('id' => $courseid)
    );
    $submitbuttonclose  = html_writer::tag('button', get_string('close', 'format_fntabs'), array(
        'class' => 'spark-close-record-btn',
        'type' => 'submit',
        'value' => 'submit',
    ));
    $formclose = html_writer::tag('form', $submitbuttonclose, array(
        'action' => $formurlclose->out(false),
        'method' => 'post',
        'style' => 'float: left;',
        'autocomplete' => 'off'
    ));
    echo html_writer::div($form.' '.$formclose, 'add-record-btn-wrapper', array('id' => 'add-record-btn'));
}

echo html_writer::end_div(); // Main wrapper.
echo $OUTPUT->footer();