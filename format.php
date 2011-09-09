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
require_once($CFG->libdir . '/filelib.php');
require_once($CFG->libdir . '/completionlib.php');
require_once($CFG->libdir . '/ajax/ajaxlib.php');
require_once($CFG->dirroot . '/mod/forum/lib.php');

require_once($CFG->dirroot . '/course/format/' . $course->format . '/course_format.class.php');
require_once($CFG->dirroot . '/course/format/' . $course->format . '/course_format_fn.class.php');
global $DB, $OUTPUT, $THEME, $PAGE;

$cobject = new course_format_fn($course);
$course = $cobject->course;

$cobject->handle_extra_actions();

/// Add any extra module information to our module structures.
//$cobject->add_extra_module_info();
//    $week = optional_param('week', -1, PARAM_INT);
$selected_week = optional_param('selected_week', -1, PARAM_INT);
if ($selected_week != -1) {
    $displaysection = course_set_display($course->id, $selected_week);
} else {
    $displaysection = course_get_display($course->id);
}

$streditsummary = get_string('editsummary');
$stradd = get_string('add');
$stractivities = get_string('activities');
$strshowallweeks = get_string('showallweeks');
$strweek = get_string('week');
$strgroups = get_string('groups');
$strgroupmy = get_string('groupmy');
$editing = $PAGE->user_is_editing();

if ($editing) {
    $strweekhide = get_string('hideweekfromothers');
    $strweekshow = get_string('showweekfromothers');
    $strmoveup = get_string('moveup');
    $strmovedown = get_string('movedown');
    $strmarkthistopic = get_string("markthistopic");
}
$tabrange = 0;
if ($selected_week > 999) {
    $tabrange = $selected_week;
    $selected_week = $SESSION->G8_selected_week[$course->id];
    list($tablow, $tabhigh, $selected_week) = $cobject->get_week_info($tabrange, $selected_week);
} else if ($selected_week > -1) {
    $SESSION->G8_selected_week[$course->id] = $selected_week;
} else if (isset($SESSION->G8_selected_week[$course->id])) {
    $selected_week = $SESSION->G8_selected_week[$course->id];
} else {
    $SESSION->G8_selected_week[$course->id] = $selected_week;
}
$context = get_context_instance(CONTEXT_COURSE, $course->id);
$cobject->context = $context;

if (($marker >= 0) && has_capability('moodle/course:setcurrentsection', $cobject->context) && confirm_sesskey()) {
    $course->marker = $marker;
    if (!$DB->set_field("course", "marker", $marker, array("id" => $course->id))) {
        print_error("Could not mark that topic for this course");
    }
}

/// Add the selected_week to the course object (so it can be used elsewhere).
$course->selected_week = $selected_week;

//Print the Your progress icon if the track completion is enabled
$completioninfo = new completion_info($course);
echo $completioninfo->display_help_icon();
echo $OUTPUT->heading(get_string('weeklyoutline'), 2, 'headingblock header outline');

// Note, an ordered list would confuse - "1" could be the clipboard or summary.
echo "<ul class='weeks'>\n";

/// If currently moving a file then show the current clipboard
if (ismoving($course->id)) {
    $stractivityclipboard = strip_tags(get_string('activityclipboard', '', addslashes($USER->activitycopyname)));
    $strcancel = get_string('cancel');
    echo '<tr class="clipboard">';
    echo '<td colspan="3">';
    echo $stractivityclipboard . '&nbsp;&nbsp;(<a href="mod.php?cancelcopy=true&amp;sesskey=' . $USER->sesskey . '">' . $strcancel . '</a>)';
    echo '</td>';
    echo '</tr>';
}

/// Print Section 0 with general activities

$section = 0;
$thissection = $sections[$section];
unset($sections[0]);
//    print_object($course->showsection0);

if (!empty($course->showsection0) && ($thissection->summary or $thissection->sequence or $PAGE->user_is_editing())) {

    // Note, 'right side' is BEFORE content.
    echo "<tr id=\"section-0\" class=\"section main\">";
    echo '<td colspan="3" align="center" width="100%" id="fnsection0" class="content">';
    if (empty($course->sec0title)) {
        $course->sec0title = '';
    }
    if ($PAGE->user_is_editing()) {
        if (empty($_GET['edittitle']) or ($_GET['edittitle'] != 'sec0')) {
            echo "<b>$course->sec0title</b>";
            $path = $CFG->wwwroot . '/course';
            if (empty($THEME->custompix)) {
                $pixpath = $path . '/../pix';
            } else {
                $pixpath = $path . '/../theme/' . $CFG->theme . '/pix';
            }
            echo ' <a title="' . get_string('edit') . '" href="' . $CFG->wwwroot . '/course/view.php?id=' .
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
            echo "<b>$course->sec0title</b>";
        }
    } else {
        echo "<b>$course->sec0title</b>";
    }
    echo '</td></tr>';
    echo '<li id="section-0" class="section main clearfix" >';
    echo '<div class="left side">&nbsp;</div>';
    echo '<div class="right side" >&nbsp;</div>';
    echo '<div class="content">';
    if (!empty($thissection->name)) {
        echo $OUTPUT->heading($thissection->name, 3, 'sectionname');
    }
    echo '<div class="summary">';

    $coursecontext = get_context_instance(CONTEXT_COURSE, $course->id);
    $summarytext = file_rewrite_pluginfile_urls($thissection->summary, 'pluginfile.php', $coursecontext->id, 'course', 'section', $thissection->id);
    $summaryformatoptions = new stdClass;
    $summaryformatoptions->noclean = true;
    $summaryformatoptions->overflowdiv = true;
    echo format_text($summarytext, $thissection->summaryformat, $summaryformatoptions);

    if ($PAGE->user_is_editing() && has_capability('moodle/course:update', get_context_instance(CONTEXT_COURSE, $course->id))) {
        echo '<p><a title="' . $streditsummary . '" ' .
        ' href="editsection.php?id=' . $thissection->id . '"><img src="' . $OUTPUT->pix_url('t/edit') . '" ' .
        ' class="icon edit" alt="' . $streditsummary . '" /></a></p>';
    }

    echo '</div>';
    $cobject->print_section_fn($course, $thissection, $mods, $modnamesused);

    if ($PAGE->user_is_editing()) {
        $cobject->print_section_add_menus($course, $section, $modnames);
    }

    echo '</div>';
    echo "</li>\n";
}


if (empty($course->showonlysection0)) {
    /// Now all the weekly sections
    $timenow = time();
    $weekdate = $course->startdate;    // this should be 0:00 Monday of that week
    $weekdate += 7200;                 // Add two hours to avoid possible DST problems
    $section = 1;
    $weekofseconds = 604800;
    $course->enddate = $course->startdate + ($weekofseconds * $course->numsections);
    $sectionmenu = array();


    //  Calculate the current week based on today's date and the starting date of the course.
    $currentweek = ($timenow > $course->startdate) ?
            (int) ((($timenow - $course->startdate) / $weekofseconds) + 1) : 0;
    $currentweek = min($currentweek, $course->numsections);

    $strftimedateshort = " " . get_string("strftimedateshort");

    /// If the selected_week variable is 0, all weeks are selected.
    if ($selected_week == -1 && $currentweek == 0) {
        $selected_week = 0;
        $section = $selected_week;
        $numsections = $course->numsections;
    } else if ($selected_week == -1) {
        if ($PAGE->user_is_editing() ||
                (!empty($course->activitytracking) && ($selected_week = $cobject->first_unfinished_section()) === false)) {
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
        echo $OUTPUT->heading($strmainheading, 3, 'fnoutlineheadingblock');

        if ($selected_week > 0 && !$PAGE->user_is_editing()) {
            echo '<table class="topicsoutline" border="0" cellpadding="0" cellspacing="0" width="100%">
            <tr><td valign=top class="fntopicsoutlinecontent fnsectionouter" width="100%">
            <!-- Tabbed section container -->
            <table border="0" cellpadding="0" cellspacing="0" width="100%" align="center">
                    ';
            if ($course->numsections > 1) {
                echo '
                <!-- Tabs -->
                <tr>
                    <td width="100%">
                        ';
                echo $cobject->print_weekly_activities_bar($selected_week, $tabrange);
                echo '
                    </td>
                </tr>
                <!-- Tabs -->
                        ';
            }
            echo '
                <!-- Selected Tab Content -->
                <tr>
                    <!-- This cell holds the same colour as the selected tab. -->
                    <td width="100%" class="fnweeklynavselected">
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
					echo '<td valign="top" class="fntopicsoutlinecontent fnsectionouter" width="100%">';
						echo $cobject->print_weekly_activities_bar($selected_week, $tabrange);
					echo '</td>';
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
            $thissection->course = $course->id;   // Create a new week structure
            $thissection->section = $section;
            $thissection->name = null;
            $thissection->summary = '';
            $thissection->summaryformat = FORMAT_HTML;
            $thissection->visible = 1;
            $thissection->id = $DB->insert_record('course_sections', $thissection);
        }

        $showsection = (has_capability('moodle/course:viewhiddensections', $context) || ($thissection->visible && ($timenow > $weekdate)));

        if ($showsection) {
            $currenttopic = ($course->marker == $section);
            //            if (!$cobjectsection->visible || ($timenow < $weekdate) || ($selected_week > $currentweek)) {
            if (!$thissection->visible || ($selected_week > $currentweek)) {
                $colorsides = "class=\"fntopicsoutlinesidehidden\"";
                $colormain = "class=\"fntopicsoutlinecontenthidden\"";
            } else if ($currenttopic) {
                $colorsides = "class=\"fntopicsoutlinesidehighlight\"";
                $colormain = "class=\"fntopicsoutlinecontenthighlight\"";
            } else {
                $colorsides = "class=\"fntopicsoutlineside\"";
                $colormain = "class=\"fntopicsoutlinecontent fntopicsoutlineinner\"";
            }

            if ($selected_week <= 0 || $PAGE->user_is_editing()) {
                echo '<tr><td colspan="3" ' . $colorsides . ' align="center">';
                echo $heading_prefix . $section;
                echo '</td></tr>';
                echo "<tr>";
                echo '<td nowrap ' . $colorsides . ' valign="top" width="20">&nbsp;</td>';
            } else {
                echo "<tr>";
            }

            if (!has_capability('moodle/course:viewhiddensections', $context) and !$thissection->visible) {   // Hidden for students
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

                echo format_text($thissection->summary, FORMAT_HTML);

                if ($PAGE->user_is_editing() && has_capability('moodle/course:update', get_context_instance(CONTEXT_COURSE, $course->id))) {

                    echo ' <a title="' . $streditsummary . '" href="editsection.php?id=' . $thissection->id . '">' .
                    '<img src="' . $OUTPUT->pix_url('t/edit') . '" class="icon edit" alt="' . $streditsummary . '" /></a>';
					echo '<br clear="all">';
                }

               // echo '<br clear="all">';

                //   $mandatorypopup = print_section_local($course, $cobjectsection, $mods, $modnamesused);                
                $cobject->print_section_fn($course, $thissection, $mods, $modnamesused);

                if ($PAGE->user_is_editing()) {
//                    $cobject->print_section_add_menus($section);
//                    print_section_add_menus($course, $section, $modnames);
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
                    echo '<a href="view.php?id=' . $course->id . '&amp;week=0#section-' . $section . '" title="' . $strshowallweeks . '">' .
                    '<img src="' . $OUTPUT->pix_url('i/all') . '" class="icon wkall" alt="' . $strshowallweeks . '" /></a><br />';
                } else {
                    $strshowonlyweek = get_string("showonlyweek", "", $section);
                    echo '<a href="view.php?id=' . $course->id . '&amp;week=' . $section . '" title="' . $strshowonlyweek . '">' .
                    '<img src="' . $OUTPUT->pix_url('i/one') . '" class="icon wkone" alt="' . $strshowonlyweek . '" /></a><br />';
                }

                if ($thissection->visible) {      // Show the hide/show eye                          
                    echo '<a href="view.php?id=' . $course->id . '&amp;hide=' . $section . '&amp;sesskey=' . sesskey() . '#section-' . $section . '" title="' . $strweekhide . '">' .
                    '<img src="' . $OUTPUT->pix_url('i/hide') . '" class="icon hide" alt="' . $strweekhide . '" /></a><br />';
                } else {
                    echo '<a href="view.php?id=' . $course->id . '&amp;show=' . $section . '&amp;sesskey=' . sesskey() . '#section-' . $section . '" title="' . $strweekshow . '">' .
                    '<img src="' . $OUTPUT->pix_url('i/show') . '" class="icon hide" alt="' . $strweekshow . '" /></a><br />';
                }

                if ($section > 1) {                       // Add a arrow to move section up
                    echo '<a href="view.php?id=' . $course->id . '&amp;random=' . rand(1, 10000) . '&amp;section=' . $section . '&amp;move=-1&amp;sesskey=' . sesskey() . '#section-' . ($section - 1) . '" title="' . $strmoveup . '">' .
                    '<img src="' . $OUTPUT->pix_url('t/up') . '" class="icon up" alt="' . $strmoveup . '" /></a><br />';
                    echo "<br />";
                }

                if ($section < $course->numsections) {    // Add a arrow to move section down
                    echo '<a href="view.php?id=' . $course->id . '&amp;random=' . rand(1, 10000) . '&amp;section=' . $section . '&amp;move=1&amp;sesskey=' . sesskey() . '#section-' . ($section + 1) . '" title="' . $strmovedown . '">' .
                    '<img src="' . $OUTPUT->pix_url('t/down') . '" class="icon down" alt="' . $strmovedown . '" /></a><br />';
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
//                echo '<li id="section-' . $section . '" class="section main clearfix stealth hidden">';
            }

            $weekdate += ( $weekofseconds);
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
            </td></tr></table>
            <br><br>
            <!-- Tabbed section container -->
                    ';
    }
}

echo "</ul>\n";

//if (empty($sectionmenu)) {
//    $select = new single_select(new moodle_url('/course/view.php', array('id' => $course->id)), 'week', $sectionmenu);
//    $select->label = get_string('jumpto');
//    $select->class = 'jumpmenu';
//    $select->formid = 'sectionmenu';
////    echo $OUTPUT->render($select);
//}
