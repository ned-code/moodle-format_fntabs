<?php

// Display the whole course as "tab" made of of modules
// Included from "view.php"
/**
 * Evaluation Moodle FN tab course format  for course display - layout tables, for accessibility, etc.
 *
 * A duplicate course format to enable the Moodle development team to evaluate
 * CSS for the multi-column layout in place of layout tables.
 * Less risk for the Moodle 1.6 beta release.
 *   1. Straight copy of weeks/format.php
 *   2. Replace <table> and <td> with DIVs; inline styles.
 *   3. Reorder columns so that in linear view content is first then blocks;
 * styles to maintain original graphical (side by side) view.
 *
 * Target: 3-column graphical view using relative widths for pixel screen sizes
 * 800x600, 1024x768... on IE6, Firefox. Below 800 columns will shift downwards.
 *
 * http://www.maxdesign.com.au/presentation/em/ Ideal length for content.
 * http://www.svendtofte.com/code/max_width_in_ie/ Max width in IE.
 *
 * @copyright &copy; 2006 The Open University
 * @author N.D.Freear@open.ac.uk, and others.
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package
 */
defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/filelib.php');
require_once($CFG->libdir . '/completionlib.php');

require_once($CFG->dirroot . '/course/format/' . $course->format . '/course_format.class.php');
require_once($CFG->dirroot . '/course/format/' . $course->format . '/course_format_fn.class.php');
require_once($CFG->dirroot . '/course/format/' . $course->format . '/lib.php');
require_once($CFG->dirroot . '/course/format/' . $course->format . '/modulelib.php');

global $DB, $OUTPUT, $THEME, $PAGE;

//Check sesubmission plugin
if ($assignCheck = $DB->get_record_sql("SELECT * FROM {$CFG->prefix}assign LIMIT 0, 1")){
    if(isset($assignCheck->attemptreopenmethod)){
        $resubmission = true;
    }else{
        $resubmission = false;
    }
}else{
    $resubmission = false;
}

$cobject = new course_format_fn($course);
$course = $cobject->course;

if (!isset($course->showsection0)) {
    $course->showsection0 = 0;
}

$cobject->handle_extra_actions();

$selected_week = optional_param('selected_week', -1, PARAM_INT);

$streditsummary = get_string('editsummary');
$stradd = get_string('add');
$stractivities = get_string('activities');
$strshowallweeks = get_string('showallweeks', 'format_fntabs');
$strweek = get_string('week');
$strgroups = get_string('groups');
$strgroupmy = get_string('groupmy');
$editing = $PAGE->user_is_editing();

if ($editing) {
    $strweekhide = get_string('hideweekfromothers', 'format_fntabs');
    $strweekshow = get_string('showweekfromothers', 'format_fntabs');
    $strmoveup = get_string('moveup');
    $strmovedown = get_string('movedown');
    $strmarkedthistopic = get_string("markedthistopic");
    $strmarkthistopic = get_string("markthistopic");
}

$tabrange = 0;


if ($selected_week > 999) {
    $tabrange = $selected_week;
    $selected_week = $SESSION->G8_selected_week[$course->id];
    list($tablow, $tabhigh, $selected_week) = $cobject->get_week_info($tabrange, $selected_week);
} else if ($selected_week > -1) {
    $SESSION->G8_selected_week[$course->id] = $selected_week;
    $SESSION->G8_selected_week_whenset[$course->id] = time();
} else if (isset($SESSION->G8_selected_week[$course->id])) {
    $tabrange = 1000;
    $selected_week = $SESSION->G8_selected_week[$course->id];
    list($tablow, $tabhigh, $selected_week) = $cobject->get_week_info($tabrange, $selected_week);
} else {
    $tabrange = 1000;
    $SESSION->G8_selected_week[$course->id] = $selected_week;
    $SESSION->G8_selected_week_whenset[$course->id] = time();
}

$cobject->context = get_context_instance(CONTEXT_COURSE, $course->id);

$isteacher = has_capability('moodle/grade:viewall', $cobject->context);

if (($marker >= 0) && has_capability('moodle/course:setcurrentsection', $cobject->context) && confirm_sesskey()) {

    $course->marker = $marker;

    if (!$DB->set_field("course", "marker", $marker, array("id" => $course->id))) {
        print_error("Could not mark that topic for this course");
    }
}


// Add the selected_week to the course object (so it can be used elsewhere).
$course->selected_week = $selected_week;

// Note, an ordered list would confuse - "1" could be the clipboard or summary.
echo "<ul class='weeks'>\n";

/// If currently moving a file then show the current clipboard
if (ismoving($course->id)) {
    $stractivityclipboard = strip_tags(get_string('activityclipboard', '', $USER->activitycopyname));
    $strcancel = get_string('cancel');
    echo '<li class="clipboard">';
    echo $stractivityclipboard . '&nbsp;&nbsp;(<a href="mod.php?cancelcopy=true&amp;sesskey=' . sesskey() . '">' . $strcancel . '</a>)';
    echo "</li>\n";
}

/// Print Section 0 with general activities

$section = 0;
$thissection = $sections[$section];
unset($sections[0]);

if (!empty($course->showsection0) && ($thissection->summary or $thissection->sequence or $PAGE->user_is_editing())) {

    // Note, 'right side' is BEFORE content.
    echo '<li id="section-0" class="section main clearfix" style="border:none !important;" >';
    //  echo '<td colspan="3" align="center" width="100%" id="fnsection0" class="content">';

    if (empty($course->sec0title)) {
        $course->sec0title = '';
    }

    if ($PAGE->user_is_editing()) {

        if (empty($_GET['edittitle']) or ($_GET['edittitle'] != 'sec0')) {

            if ($course->sec0title) {
                echo $OUTPUT->heading($course->sec0title, 2, 'fnoutlineheadingblock1', 'sectionzerotextalignment');
            } else {
                echo $OUTPUT->heading(get_string('sectionzerodefaultheading', 'format_fntabs'), 2, 'fnoutlineheadingblock1');
            }

            $path = $CFG->wwwroot . '/course';

            if (empty($THEME->custompix)) {
                $pixpath = $path . '/../pix';
            } else {
                $pixpath = $path . '/../theme/' . $CFG->theme . '/pix';
            }

            echo ' <a title="' . get_string('edit_title_for_section0', 'format_fntabs') . '" href="' . $CFG->wwwroot . '/course/view.php?id=' .
            $course->id . '&amp;edittitle=sec0"><img src="' . $pixpath . '/t/edit.gif" /></a>';

        } else if ($_GET['edittitle'] == 'sec0') {
            echo '<form name="editsec0title" method="post" ' .
            'action="' . $CFG->wwwroot . '/course/format/fntabs/mod.php">' .
            '<input name="id" type="hidden" value="' . $course->id . '" />' .
            '<input name="sec0title" type="text" size="20" value="' . $course->sec0title . '" />' .
            '<input style="font-size: 8pt; margin: 0 0 0 2px; padding: 0 0 0 0;" type="submit" ' .
            'value="ok" title="Save">' .
            '</form>';
        } else {

            if ($course->sec0title) {
                echo $OUTPUT->heading($course->sec0title, 2, 'fnoutlineheadingblock1');
            } else {
                echo $OUTPUT->heading(get_string('sectionzerodefaultheading', 'format_fntabs'), 2, 'fnoutlineheadingblock1');
            }
        }
    } else {

        if ($course->sec0title) {
            echo $OUTPUT->heading($course->sec0title, 2, 'fnoutlineheadingblock1');
        } else {
            echo $OUTPUT->heading(get_string('sectionzerodefaultheading', 'format_fntabs'), 2, 'fnoutlineheadingblock1', 'sectionzerotextalignment');
        }
    }
    //echo '</td></tr>';

    echo '</li>';
    echo '<li id ="section-0" class ="section main clearfix" >';
    echo '<div class ="left side">&nbsp;</div>';
    echo '<div class ="right side" >&nbsp;</div>';
    echo '<div class ="content">';

    if (!empty($thissection->name)) {
        echo $OUTPUT->heading($thissection->name, 3, 'sectionname');
    }

    echo '<div class="summary">';

    $coursecontext = get_context_instance(CONTEXT_COURSE, $course->id);
    $summarytext = file_rewrite_pluginfile_urls($thissection->summary, 'pluginfile.php', $coursecontext->id, 'course', 'section', $thissection->id);
    $summaryformatoptions = new stdClass;
    $summaryformatoptions->noclean = true;
    $summaryformatoptions->overflowdiv = true;
    echo format_text($summarytext, FORMAT_HTML, $summaryformatoptions);

    if ($PAGE->user_is_editing() && has_capability('moodle/course:update', get_context_instance(CONTEXT_COURSE, $course->id))) {
        echo '<p><a title="' . $streditsummary . '" ' .
        ' href="editsection.php?id=' . $thissection->id . '"><img src="' . $OUTPUT->pix_url('t/edit') . '" ' .
        ' class="iconsmall edit" alt="' . $streditsummary . '" /></a></p>';
    }

    echo '</div>';

    $cobject->print_section_fn($course, $thissection, $mods, $modnamesused, $resubmission);

    if ($PAGE->user_is_editing()) {
        $cobject->print_section_add_menus($course, $section, $modnames);
    }

    echo '</div>';
    echo "</li>\n";
}

/// Now all the normal modules by week
/// Everything below uses "section" terminology - each "section" is a week.

$courseformatoptions = course_get_format($course)->get_format_options();
$course->numsections = $courseformatoptions['numsections'];
//$course->hiddensections = $courseformatoptions['hiddensections'];
//$course->coursedisplay = $courseformatoptions['coursedisplay'];



if (empty($course->showonlysection0)) {
    /// Now all the weekly sections

    $timenow = time();
    $weekdate = $course->startdate;    // this should be 0:00 Monday of that week
    $weekdate += 7200;                 // Add two hours to avoid possible DST problems
    $section = 1;
    $sectionmenu = array();
    $weekofseconds = 604800;
    $course->enddate = $course->startdate + ($weekofseconds * $course->numsections);

    $completion = new completion_info($course);

    $show_option = $DB->get_field('course_config_fn', 'value', array('courseid' => $course->id, 'variable' => 'defaulttab'));
    $default_tab_when_setindb = $DB->get_field('course_config_fn', 'value', array('courseid' => $course->id, 'variable' => 'defaulttabwhenset'));
    $selected_week_indb = $DB->get_field('course_config_fn', 'value', array('courseid' => $course->id, 'variable' => 'topictoshow'));
    //  Calculate the current week based on today's date and the starting date of the course.
    $currentweek = ($timenow > $course->startdate) ?
            (int) ((($timenow - $course->startdate) / $weekofseconds) + 1) : 0;

    $currentweek = min($currentweek, $course->numsections);
    //$allSections = get_all_sections($course->id);
    $allSections = get_fast_modinfo($course->id)->get_section_info_all();
    $modinfo = get_fast_modinfo($COURSE);
    $strftimedateshort = " " . get_string("strftimedateshort");
    /// If the selected_week variable is 0, all weeks are selected.
    if ($selected_week == -1 && $currentweek == 0) {
        $selected_week = 0;
        $section = $selected_week;
        $numsections = $course->numsections;
    } else if ($selected_week == -1) {

        // show the selected week based on the start date of the course
        if ($show_option == 'option1') {
            $selected_week = $currentweek;
        } elseif (($show_option == 'option2') && ($completion->is_enabled())) {
            // show the selected week based on the section that have not attempted activity first
            foreach ($allSections as $k => $sect) {
                if ($k == 0) {
                    continue;
                }

                if (!$sect->visible) {
                    continue;
                }

                if ($k > $currentweek) {
                    continue;
                }

                if ($k <= $COURSE->numsections) {
                    if (!empty($sect)) {
                        $activityinfoarr = get_activities_status($course, $sect, $resubmission);
                        if (($activityinfoarr['saved'] > 0)
                                || ($activityinfoarr['notattempted'] > 0)
                                || ($activityinfoarr['waitngforgrade'] > 0)) {
                            $selected_week = $k;
                            break;
                        }
                    }
                }
            }
        } elseif (($show_option == 'option3') && ($selected_week_indb <= $currentweek)) {
            $selected_week = $selected_week_indb;
        } else {
            $selected_week = $selected_week;
        }
        if ($PAGE->user_is_editing()) {
            $selected_week = $currentweek;
        }

        $selected_week = ($selected_week > $currentweek) ? $currentweek : $selected_week;
        $section = $selected_week;
        $numsections = MAX($section, 1);
    } else if ($selected_week != 0) {
        /// Teachers can select a future week; students can't.
        $isteacher = has_capability('moodle/grade:viewall', $cobject->context);
        if (($selected_week > $currentweek) && !$isteacher) {
            $section = $currentweek;
        } else {
            $section = $selected_week;
        }
        $numsections = $section;
    } else {
        $numsections = $course->numsections;
    }

    $selected_week = ($selected_week < 0) ? 1 : $selected_week;

    // If the course has been set to more than zero sections, display normal.
    if ($course->numsections > 0) {
        /// Forcing a style here, seems to be the only way to force a zero bottom margin...
        if (!empty($course->mainheading)) {
            $strmainheading = $course->mainheading;
        } else {
            $strmainheading = get_string('defaultmainheading', 'format_fntabs');
        }
        if ($course->showsection0) {
            $headerextraclass = '';
        } else {
            $headerextraclass = 'fnoutlineheadingblockone';
        }


        if (($PAGE->user_is_editing()) || !$completion->is_enabled()) {
            echo $OUTPUT->heading($strmainheading, 2, 'fnoutlineheadingblock1');
        } elseif ($completion->is_enabled() && !$isteacher) {
            echo $OUTPUT->heading($strmainheading, 2, 'fnoutlineheadingblock ' . $headerextraclass . '');
        } else {
            echo $OUTPUT->heading($strmainheading, 2, 'fnoutlineheadingblock1');
        }

        $bgcolour         = $DB->get_field('course_config_fn', 'value', array('courseid' => $course->id, 'variable' => 'bgcolour'));
        $highlightcolour  = $DB->get_field('course_config_fn', 'value', array('courseid' => $course->id, 'variable' => 'highlightcolour'));
        $inactivebgcolour = $DB->get_field('course_config_fn', 'value', array('courseid' => $course->id, 'variable' => 'inactivebgcolour'));

        $bgcolour         = $bgcolour ? $bgcolour : '9DBB61';
        $highlightcolour  = $highlightcolour ? $highlightcolour : '73C1E1';
        $inactivebgcolour = $inactivebgcolour ? $inactivebgcolour : 'F5E49C';

        echo "
        <style>
        .fnsectionouter,
        .courseedit-format,
        .courseedit-fn-main,
        headergeneral,
        fnmarkedfilearea {
            background-color:#$bgcolour;
        }
        .fntopicsoutlinecontent {
            border-color:#$bgcolour;
        }
        .fnweeklynavnorm {
            border-right: solid 1px #$bgcolour;
            border-right: solid 1px #$bgcolour;
        }
        .fnweeklynavselected {
            border-right: solid 1px #$bgcolour;
        }
        .fntopicsoutlineside {
            background-color:#$bgcolour;
        }
        .fntopicsoutlinesidehighlight,
        .courseedit-fn-format,
        .courseedit-fn-sidebar,
        .markingcontainer td.generalbox3 {
            background-color: #$highlightcolour;
        }
        .fntopicsoutlinecontenthighlight,
        .courseedit-fn-section,
        .fnmarkingblock thead,
        .fnmarkingblock tbody td,
        .fncoursegroup {
            border-color: #$highlightcolour;
        }
        .fnweeklynavdisabledselected,
        .fnweeklynavdisabledselected1 {
            background-color: #$inactivebgcolour;
        }
        </style>";

        if ($selected_week > 0 && !$PAGE->user_is_editing()) {
            echo '<table class="topicsoutline" border="0" cellpadding="0" cellspacing="0" width="100%">
                <tr><td valign=top class="fntopicsoutlinecontent fnsectionouter" width="100%">
                            <div class="number-select">
                <!-- Tabbed section container -->
                <table border="0" cellpadding="0" cellspacing="0" width="100%" align="center">
                        ';
            if ($course->numsections > 1) {
                echo '
                    <!-- Tabs -->
                    <tr>
                        <td width="100%" align="center">';
                echo $cobject->print_weekly_activities_bar($selected_week, $tabrange, $resubmission);

                echo '
                        </td>
                    </tr>
                    <!-- Tabs -->
                            ';
            }

            $class = $cobject->tdselectedclass[$selected_week] == 'fnweeklynavdisabledselected' ? 'fnweeklynavdisabledselected1' : 'fnweeklynavselected';

            echo '
                    <!-- Selected Tab Content -->
                    <tr>
                        <!-- This cell holds the same colour as the selected tab. -->
                        <td width="100%" class="' . $class . '">
                            <!-- This table creates a selected colour box around the content -->
                            <table width="100%" cellpadding="0" cellspacing="0" border="0">
                                <tr>
                                    <td class="content-section">
                            <table width="100%" cellpadding="0" cellspacing="0" border="0">
                                <tr>
                                    <td>
                        ';
        } else if ($course->numsections > 1) {

            echo '<table class="topicsoutline" border="0" cellpadding="8" cellspacing="0" width="100%">';
            echo '<tr>';
            echo '<td valign="top" class="fntopicsoutlinecontent fnsectionouter" width="100%" align="center"><div class="number-select">';
            echo $cobject->print_weekly_activities_bar($selected_week, $tabrange, $resubmission);
            echo '</div></td>';
            echo '</tr>';
            echo '</table>';
        }

        if (isset($course->topicheading) && !empty($course->topicheading)) {
            $heading_prefix = $course->topicheading;
        } else {
            $heading_prefix = 'Week ';
        }
    } else {
        $section = 1;
        $numsections = 1;
        $weekdate = 0;
        $heading_prefix = 'Section ';
    }
    // Now all the normal modules by topic
    // Everything below uses "section" terminology - each "section" is a topic.

    if ($section <= 0)
        $section = 1;
    while (($course->numsections > 0) && ($section <= $numsections)) {
        echo '<table class="topicsoutline" border="0" cellpadding="0" cellspacing="0" width="100%">';
        if (!empty($sections[$section])) {
            $thissection = $sections[$section];
        } else {
            unset($thissection);
            if (! $thissection = $DB->get_record('course_sections', array('course'=>$course->id, 'section'=>$section))){
                $thissection = new object();
                $thissection->course = $course->id;   // Create a new week structure
                $thissection->section = $section;
                $thissection->name = null;
                $thissection->summary = '';
                $thissection->summaryformat = FORMAT_HTML;
                $thissection->visible = 1;
                $thissection->id = $DB->insert_record('course_sections', $thissection);
            }
        }

        $showsection = (has_capability('moodle/course:viewhiddensections', $context) || ($thissection->visible && ($timenow > $weekdate)));
            $weekdate += ( $weekofseconds);
        if ($showsection) {
            $currenttopic = ($course->marker == $section);

            //            if (!$cobjectsection->visible || ($timenow < $weekdate) || ($selected_week > $currentweek)) {
            if (!$thissection->visible || ($selected_week > $currentweek)) {
                $colorsides = "class=\"fntopicsoutlinesidehidden \"";
                $colormain = "class=\"fntopicsoutlinecontenthidden fntopicsoutlinecontent fntopicsoutlineinner\"";
            } else if ($currenttopic) {
                $colorsides = "class=\"fntopicsoutlinesidehighlight\"";
                $colormain = "class=\"fntopicsoutlinecontenthighlight fntopicsoutlinecontent fntopicsoutlineinner\"";
            } else {
                $colorsides = "class=\"fntopicsoutlineside\"";
                $colormain = "class=\"fntopicsoutlinecontent fntopicsoutlineinner\"";
            }

            if ($selected_week <= 0 || $PAGE->user_is_editing()) {
                echo '<tr><td colspan="3" ' . $colorsides . ' align="center">';
                echo "$heading_prefix  $section";
                echo '</td></tr>';
                echo "<tr>";
                echo '<td nowrap ' . $colorsides . ' valign="top" width="20">&nbsp;</td>';
            } else {
                echo "<tr>";
            }

            if (!has_capability('moodle/course:viewhiddensections', $context) && !$thissection->visible) {   // Hidden for students
                echo "<td valign=top align=center $colormain width=\"100%\">";
                echo get_string("notavailable");
                echo "</td>";
            } else {
                echo "<td valign=top $colormain width=\"100%\">";

                if (isset($cobject->course->expforumsec) && ($cobject->course->expforumsec == $thissection->section)) {
                    echo '<table cellspacing="0" cellpadding="0" border="0" align="center" width="100%">'
                    . '<tr><td>';
                } else {
                    echo '<table width="100%" cellspacing="0" cellpadding="0" border="0">'
                    . '<tr><td align="left">';
                }
                echo '<div class="summary">';

                $coursecontext = get_context_instance(CONTEXT_COURSE, $course->id);
                $summarytext = file_rewrite_pluginfile_urls($thissection->summary, 'pluginfile.php', $coursecontext->id, 'course', 'section', $thissection->id);
                $summaryformatoptions = new stdClass;
                $summaryformatoptions->noclean = true;
                $summaryformatoptions->overflowdiv = true;
                echo format_text($summarytext, $thissection->summaryformat, $summaryformatoptions);

                if ($PAGE->user_is_editing() && has_capability('moodle/course:update', get_context_instance(CONTEXT_COURSE, $course->id))) {

                    echo ' <a title="' . $streditsummary . '" href="editsection.php?id=' . $thissection->id . '">' .
                    '<img src="' . $OUTPUT->pix_url('t/edit') . '" class="iconsmall edit" alt="' . $streditsummary . '" /></a><br /><br />';
                }
                //echo format_text($thissection->summary, FORMAT_HTML);
                echo '</div>';

                echo '<div class="section-boxs">';
                $cobject->print_section_fn($course, $thissection, $mods, $modnamesused, false, "100%", false, $resubmission);//PRINT SECTION
                echo '</div>';

                if ($PAGE->user_is_editing()) {
                    $cobject->print_section_add_menus($course, $section, $modnames);
                }

                echo '</td></tr></table>';

                echo "</td>";
            }

            if ($selected_week <= 0 || $PAGE->user_is_editing()) {
                echo '<td nowrap ' . $colorsides . ' valign="top" align="center" width="20">';
                echo "<font size=1>";
            }


            if ($PAGE->user_is_editing() && has_capability('moodle/course:update', get_context_instance(CONTEXT_COURSE, $course->id))) {
                if ($course->marker == $section) {  // Show the "light globe" on/off
                    echo '<a href="view.php?id=' . $course->id . '&amp;marker=0&amp;sesskey=' . sesskey() . '#section-' . $section . '" title="' . $strmarkedthistopic . '">' . '<img src="' . $OUTPUT->pix_url('i/marked') . '" alt="' . $strmarkedthistopic . '" class="icon"/></a><br />';
                } else {
                    echo '<a href="view.php?id=' . $course->id . '&amp;marker=' . $section . '&amp;sesskey=' . sesskey() . '#section-' . $section . '" title="' . $strmarkthistopic . '">' . '<img src="' . $OUTPUT->pix_url('i/marker') . '" alt="' . $strmarkthistopic . '" class="icon"/></a><br />';
                }

                if ($thissection->visible) {        // Show the hide/show eye
                    echo '<a href="view.php?id='.$course->id.'&amp;hide='.$section.'&amp;sesskey='.sesskey().'#section-'.$section.'" title="'.$strweekhide.'">'.
                         '<img src="'.$OUTPUT->pix_url('i/hide') . '" class="iconsmall iconhide" alt="'.$strweekhide.'" /></a><br />';
                } else {
                    echo '<a href="view.php?id='.$course->id.'&amp;show='.$section.'&amp;sesskey='.sesskey().'#section-'.$section.'" title="'.$strweekshow.'">'.
                         '<img src="'.$OUTPUT->pix_url('i/show') . '" class="iconsmall iconhide" alt="'.$strweekshow.'" /></a><br />';
                }

                if ($section > 1) {                       // Add a arrow to move section up
                    echo '<a href="view.php?id=' . $course->id . '&amp;random=' . rand(1, 10000) . '&amp;section=' . $section . '&amp;move=-1&amp;sesskey=' . sesskey() . '#section-' . ($section - 1) . '" title="' . $strmoveup . '">' .
                    '<img src="' . $OUTPUT->pix_url('t/up') . '" class="iconsmall up" alt="' . $strmoveup . '" /></a><br />';
                }

                if ($section < $course->numsections) {    // Add a arrow to move section down
                    echo '<a href="view.php?id=' . $course->id . '&amp;random=' . rand(1, 10000) . '&amp;section=' . $section . '&amp;move=1&amp;sesskey=' . sesskey() . '#section-' . ($section + 1) . '" title="' . $strmovedown . '">' .
                    '<img src="' . $OUTPUT->pix_url('t/down') . '" class="iconsmall down" alt="' . $strmovedown . '" /></a><br />';
                }
            }


            if ($selected_week <= 0 || $PAGE->user_is_editing()) {
                echo "</td>";
            }
            echo "</tr>";

            if ($selected_week <= 0 || $PAGE->user_is_editing()) {
                echo '<tr><td colspan="3" ' . $colorsides . ' align="center">';
                echo '&nbsp;';
                echo '</td></tr>';
                echo "<tr><td colspan=3><img src=\"../pix/spacer.gif\" width=1 height=1></td></tr>";
            }

           // $weekdate += ( $weekofseconds);
        }

        echo '</table>';
        unset($sections[$section]);
        $section++;
    }

    if ($selected_week > 0 && !$PAGE->user_is_editing()) {
        echo '
                                    </td>
                                </tr>
                            </table>
                            <!-- This table creates a selected colour box around the content -->
                        </td>
                        <!-- This cell holds the same colour as the selected tab. -->
                    </tr>
                    <!-- Selected Tab Content -->
                </table>
                        </td>
                        <!-- This cell holds the same colour as the selected tab. -->
                    </tr>
                    <!-- Selected Tab Content -->
                </table>
                <!-- Tabbed section container -->
                </div></td></tr></table>
                <br><br>
                <!-- Tabbed section container -->
                        ';
    }
}

echo "</ul>\n";
