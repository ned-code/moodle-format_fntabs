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

require_once($CFG->libdir.'/filelib.php');
require_once($CFG->libdir.'/completionlib.php');

// Horrible backwards compatible parameter aliasing..
if ($topic = optional_param('topic', 0, PARAM_INT)) {
    $url = $PAGE->url;
    $url->param('section', $topic);
    debugging('Outdated topic param passed to course/view.php', DEBUG_DEVELOPER);
    redirect($url);
}
// End backwards-compatible aliasing..

$context = context_course::instance($course->id);

if (($marker >= 0) && has_capability('moodle/course:setcurrentsection', $context) && confirm_sesskey()) {
    $course->marker = $marker;
    course_set_marker($course->id, $marker);
}

// Make sure all sections are created.
$course = course_get_format($course)->get_course();
course_create_sections_if_missing($course, range(0, $course->numsections));

$renderer = $PAGE->get_renderer('format_fntabs');

$showtabs = format_fntabs_get_setting($course->id, 'showtabs');

if (!isset($course->showsection0)) {
    $course->showsection0 = 0;
}

$section = optional_param('section', 0, PARAM_INT);
$selectedweek = optional_param('selected_week', -1, PARAM_INT);

if ($section) {
    $selectedweek = $section;
}

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

if ($selectedweek > 999) {
    $tabrange = $selectedweek;
    $selectedweek = $SESSION->G8_selected_week[$course->id];
    list($tablow, $tabhigh, $selectedweek) = $renderer->get_week_info($course, $tabrange, $selectedweek);
} else if ($selectedweek > -1) {
    $SESSION->G8_selected_week[$course->id] = $selectedweek;
    $SESSION->G8_selected_week_whenset[$course->id] = time();
} else if (isset($SESSION->G8_selected_week[$course->id])) {
    $tabrange = 1000;
    $selectedweek = $SESSION->G8_selected_week[$course->id];
    list($tablow, $tabhigh, $selectedweek) = $renderer->get_week_info($course, $tabrange, $selectedweek);
} else {
    $tabrange = 1000;
    $SESSION->G8_selected_week[$course->id] = $selectedweek;
    $SESSION->G8_selected_week_whenset[$course->id] = time();
}

$isteacher = has_capability('moodle/grade:viewall', context_course::instance($course->id));

// Add the selected_week to the course object (so it can be used elsewhere).
$course->selected_week = $selectedweek;

// Note, an ordered list would confuse - "1" could be the clipboard or summary.
echo "<ul class='fntabs'>\n";

// If currently moving a file then show the current clipboard.
if (ismoving($course->id)) {
    $stractivityclipboard = strip_tags(get_string('activityclipboard', '', $USER->activitycopyname));
    $strcancel = get_string('cancel');
    echo '<li class="clipboard">';
    echo $stractivityclipboard . '&nbsp;&nbsp;(<a href="mod.php?cancelcopy=true&amp;sesskey=' . sesskey() .
        '">' . $strcancel . '</a>)';
    echo "</li>\n";
}

// Print Section 0 with general activities.
$section = 0;
$thissection = $sections[$section];
unset($sections[0]);

if (!empty($course->showsection0) && ($thissection->summary or $thissection->sequence or $PAGE->user_is_editing())) {


    echo '<li id ="section-0" class ="section main clearfix" >';
    echo '<div class ="left side">&nbsp;</div>';
    echo '<div class ="right side" >&nbsp;</div>';
    echo '<div class ="content">';

    // Section-0 header.
    if (empty($course->sec0title)) {
        $course->sec0title = '';
    }

    if (!empty($thissection->name)) {
        echo $OUTPUT->heading($thissection->name, 3, 'sectionname');
    }

    echo '<div class="summary">';

    $coursecontext = context_course::instance($course->id);
    $summarytext = file_rewrite_pluginfile_urls($thissection->summary, 'pluginfile.php',
        $coursecontext->id, 'course', 'section', $thissection->id
    );
    $summaryformatoptions = new stdClass;
    $summaryformatoptions->noclean = true;
    $summaryformatoptions->overflowdiv = true;
    echo format_text($summarytext, FORMAT_HTML, $summaryformatoptions);

    if ($PAGE->user_is_editing() && has_capability('moodle/course:update', context_course::instance($course->id))) {
        echo '<p><a title="' . $streditsummary . '" ' .
            ' href="editsection.php?id=' . $thissection->id . '"><img src="' . $OUTPUT->pix_url('t/edit') . '" ' .
            ' class="iconsmall edit" alt="' . $streditsummary . '" /></a></p>';
    }

    echo '</div>';

    echo $renderer->print_section_fn($course, $thissection, $mods, $modnamesused);


    if ($PAGE->user_is_editing()) {
        $renderer->print_section_add_menus($course, $section, $modnames);
    }

    echo '</div>';
    echo "</li>\n";
}

// Now all the normal modules by week.
// Everything below uses "section" terminology - each "section" is a week.
$courseformatoptions = course_get_format($course)->get_format_options();
$course->numsections = $courseformatoptions['numsections'];

if (empty($course->showonlysection0)) {
    // Now all the weekly sections.
    $timenow = time();
    $weekdate = $course->startdate; // This should be 0:00 Monday of that week.
    $weekdate += 7200; // Add two hours to avoid possible DST problems.
    $section = 1;
    $sectionmenu = array();
    $weekofseconds = 604800;
    $course->enddate = $course->startdate + ($weekofseconds * $course->numsections);

    $completion = new completion_info($course);

    $showoption = $DB->get_field('format_fntabs_config', 'value',
        array('courseid' => $course->id, 'variable' => 'defaulttab')
    );
    $defaulttabwhensetindb = $DB->get_field('format_fntabs_config', 'value',
        array('courseid' => $course->id, 'variable' => 'defaulttabwhenset')
    );
    $selectedweekindb = $DB->get_field('format_fntabs_config', 'value',
        array('courseid' => $course->id, 'variable' => 'topictoshow')
    );
    // Calculate the current week based on today's date and the starting date of the course.
    $currentweek = ($timenow > $course->startdate) ? (int)((($timenow - $course->startdate) / $weekofseconds) + 1) : 0;

    $currentweek = min($currentweek, $course->numsections);

    $allsections = get_fast_modinfo($course->id)->get_section_info_all();

    $modinfo = get_fast_modinfo($COURSE);
    $strftimedateshort = " " . get_string("strftimedateshort");

    // If the selected_week variable is 0, all weeks are selected.
    if ($selectedweek == -1 && $currentweek == 0) {
        $selectedweek = 0;
        $section = $selectedweek;
        $numsections = $course->numsections;
    } else if ($selectedweek == -1) {
        // Show the selected week based on the start date of the course.
        if ($showoption == 'option1') {
            $selectedweek = $currentweek;
        } else if (($showoption == 'option2') && ($completion->is_enabled())) {
            // Show the selected week based on the section that have not attempted activity first.
            foreach ($allsections as $k => $sect) {
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
                        $activityinfoarr = format_fntabs_get_activities_status($course, $sect);
                        if (($activityinfoarr['saved'] > 0)
                            || ($activityinfoarr['notattempted'] > 0)
                            || ($activityinfoarr['waitngforgrade'] > 0)) {
                            $selectedweek = $k;
                            break;
                        }
                    }
                }
            }
        } else if (($showoption == 'option3') && ($selectedweekindb <= $currentweek)) {
            $selectedweek = $selectedweekindb;
        } else {
            $selectedweek = $selectedweek;
        }
        if ($PAGE->user_is_editing()) {
            $selectedweek = $currentweek;
        }

        $selectedweek = ($selectedweek > $currentweek) ? $currentweek : $selectedweek;
        $section = $selectedweek;
        $numsections = max($section, 1);
    } else if ($selectedweek != 0) {
        // Teachers can select a future week; students can't.
        $isteacher = has_capability('moodle/grade:viewall', context_course::instance($course->id));
        if (($selectedweek > $currentweek) && !$isteacher) {
            $section = $currentweek;
        } else {
            $section = $selectedweek;
        }
        $numsections = $section;
    } else {
        $numsections = $course->numsections;
    }

    $selectedweek = ($selectedweek < 0) ? 1 : $selectedweek;

    // If the course has been set to more than zero sections, display normal.
    if ($course->numsections > 0) {
        if ($course->showsection0) {
            $headerextraclass = '';
        } else {
            $headerextraclass = 'fnoutlineheadingblockone';
        }

        echo '<li id ="section-'.$section.'" class ="section main clearfix" >';

        if (!empty($course->mainheading)) {
            if (($PAGE->user_is_editing()) || !$completion->is_enabled()) {
                echo $OUTPUT->heading($course->mainheading, 2, 'fnoutlineheadingblock1');
            } else if ($completion->is_enabled() && !$isteacher) {
                echo $OUTPUT->heading($course->mainheading, 2, 'fnoutlineheadingblock ' . $headerextraclass . '');
            } else {
                echo $OUTPUT->heading($course->mainheading, 2, 'fnoutlineheadingblock1');
            }
        }

        $bgcolour = format_fntabs_get_setting($course->id, 'bgcolour');
        $selectedlinkcolour = format_fntabs_get_setting($course->id, 'selectedlinkcolour');
        $inactivebgcolour = format_fntabs_get_setting($course->id, 'inactivebgcolour');

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
        .fnweeklynavselected {
              color: #$selectedlinkcolour;
         }
        .fnweeklynavdisabledselected,
        .fnweeklynavdisabledselected1 {
            background-color: #$inactivebgcolour;
        }
        </style>";
        $class = '';
        if ($selectedweek > 0 && !$PAGE->user_is_editing()) {
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
                if ($showtabs) {
                    echo $renderer->print_weekly_activities_bar($course, $selectedweek, $tabrange);
                    if ($renderer->tdselectedclass[$selectedweek] == 'fnweeklynavdisabledselected') {
                        $class = 'fnweeklynavdisabledselected1';
                    } else {
                        $class = 'fnweeklynavselected';
                    }
                } else {
                    $class = '';
                }
                echo '</td></tr>
                    <!-- Tabs -->';
            }

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
            echo '<td valign="top" class="fntopicsoutlinecontent fnsectionouter" width="100%" align="center">'.
                '<div class="number-select">';
            echo $renderer->print_weekly_activities_bar($course, $selectedweek, $tabrange);
            echo '</div></td>';
            echo '</tr>';
            echo '</table>';
        }

        if (isset($course->topicheading) && !empty($course->topicheading)) {
            $headingprefix = $course->topicheading;
        } else {
            $headingprefix = '';
        }
    } else {
        $section = 1;
        $numsections = 1;
        $weekdate = 0;
        $headingprefix = 'Section ';
    }
    // Now all the normal modules by topic
    // Everything below uses "section" terminology - each "section" is a topic.

    if ($section <= 0) {
        $section = 1;
    }
    while (($course->numsections > 0) && ($section <= $numsections)) {
        echo '<table class="topicsoutline" border="0" cellpadding="0" cellspacing="0" width="100%">';
        if (!empty($sections[$section])) {
            $thissection = $sections[$section];
        } else {
            unset($thissection);
            if (!$thissection = $DB->get_record('course_sections', array('course' => $course->id, 'section' => $section))) {
                $thissection = new stdClass();
                $thissection->course = $course->id;
                $thissection->section = $section;
                $thissection->name = null;
                $thissection->summary = '';
                $thissection->summaryformat = FORMAT_HTML;
                $thissection->visible = 1;
                $thissection->id = $DB->insert_record('course_sections', $thissection);
            }
        }

        $showsection = (has_capability('moodle/course:viewhiddensections', $context)
            || ($thissection->visible && ($timenow > $weekdate)));
        $weekdate += ( $weekofseconds);
        if ($showsection) {
            $currenttopic = ($course->marker == $section);

            if (!$thissection->visible || ($selectedweek > $currentweek)) {
                $colorsides = "class=\"fntopicsoutlinesidehidden \"";
                $colormain = "class=\"fntopicsoutlinecontenthidden fntopicsoutlinecontent fntopicsoutlineinner\"";
            } else if ($currenttopic) {
                $colorsides = "class=\"fntopicsoutlinesidehighlight\"";
                $colormain = "class=\"fntopicsoutlinecontenthighlight fntopicsoutlinecontent fntopicsoutlineinner\"";
            } else {
                $colorsides = "class=\"fntopicsoutlineside\"";
                $colormain = "class=\"fntopicsoutlinecontent fntopicsoutlineinner\"";
            }

            if ($selectedweek <= 0 || $PAGE->user_is_editing()) {
                echo '<tr><td colspan="3" ' . $colorsides . ' align="center">';
                echo "$headingprefix  $section";
                echo '</td></tr>';
                echo "<tr>";
                echo '<td nowrap ' . $colorsides . ' valign="top" width="20">&nbsp;</td>';
            } else {
                echo "<tr>";
            }

            if (!has_capability('moodle/course:viewhiddensections', $context) && !$thissection->visible) { // Hidden for students.
                echo "<td valign=top align=center $colormain width=\"100%\">";
                echo get_string("notavailable");
                echo "</td>";
            } else {
                echo "<td valign=top $colormain width=\"100%\">";

                if (isset($renderer->course->expforumsec) && ($renderer->course->expforumsec == $thissection->section)) {
                    echo '<table cellspacing="0" cellpadding="0" border="0" align="center" width="100%">'
                        . '<tr><td>';
                } else {
                    echo '<table width="100%" cellspacing="0" cellpadding="0" border="0">'
                        . '<tr><td align="left">';
                }

                echo '<ul class="fntabs">';
                echo '<li id="section-'.$section.'" class="section">';
                echo '<div class="content">';
                echo '<div class="summary">';

                $coursecontext = context_course::instance($course->id);
                $summarytext = file_rewrite_pluginfile_urls($thissection->summary, 'pluginfile.php',
                    $coursecontext->id, 'course', 'section', $thissection->id
                );
                $summaryformatoptions = new stdClass;
                $summaryformatoptions->noclean = true;
                $summaryformatoptions->overflowdiv = true;
                echo format_text($summarytext, $thissection->summaryformat, $summaryformatoptions);

                if ($PAGE->user_is_editing() && has_capability('moodle/course:update', context_course::instance($course->id))) {

                    echo ' <a title="' . $streditsummary . '" href="editsection.php?id=' . $thissection->id . '">' .
                        '<img src="' . $OUTPUT->pix_url('t/edit') . '" class="iconsmall edit" alt="' . $streditsummary .
                        '" /></a><br /><br />';
                }

                echo '</div>';

                echo '<div class="section-boxs">';
                echo $renderer->print_section_fn($course, $thissection, $mods, $modnamesused, false, "100%", false);
                echo '</div>';

                if ($PAGE->user_is_editing()) {
                    $renderer->print_section_add_menus($course, $section, $modnames);
                }
                echo '</div>';
                echo '</il>';
                echo '</ul>';

                echo '</td></tr></table>';

                echo "</td>";
            }

            if ($selectedweek <= 0 || $PAGE->user_is_editing()) {
                echo '<td nowrap ' . $colorsides . ' valign="top" align="center" width="20">';
                echo "<font size=1>";
            }


            if ($PAGE->user_is_editing() && has_capability('moodle/course:update', context_course::instance($course->id))) {
                if ($course->marker == $section) {  // Show the "light globe" on/off.
                    echo '<a href="view.php?id=' . $course->id . '&amp;marker=0&amp;sesskey=' . sesskey() .
                        '#section-' . $section . '" title="' . $strmarkedthistopic . '">' .
                        '<img src="' . $OUTPUT->pix_url('i/marked') . '" alt="' . $strmarkedthistopic .
                        '" class="icon"/></a><br />';
                } else {
                    echo '<a href="view.php?id=' . $course->id . '&amp;marker=' . $section . '&amp;sesskey=' .
                        sesskey() . '#section-' . $section . '" title="' . $strmarkthistopic . '">' .
                        '<img src="' . $OUTPUT->pix_url('i/marker') . '" alt="' . $strmarkthistopic .
                        '" class="icon"/></a><br />';
                }

                if ($thissection->visible) {        // Show the hide/show eye.
                    echo '<a href="view.php?id='.$course->id.'&amp;hide='.$section.'&amp;sesskey='.sesskey().
                        '#section-'.$section.'" title="'.$strweekhide.'">'.
                        '<img src="'.$OUTPUT->pix_url('i/hide') . '" class="iconsmall iconhide" alt="'.
                        $strweekhide.'" /></a><br />';
                } else {
                    echo '<a href="view.php?id='.$course->id.'&amp;show='.$section.'&amp;sesskey='.sesskey().
                        '#section-'.$section.'" title="'.$strweekshow.'">'.
                        '<img src="'.$OUTPUT->pix_url('i/show') . '" class="iconsmall iconhide" alt="'.
                        $strweekshow.'" /></a><br />';
                }

                if ($section > 1) {                       // Add a arrow to move section up.
                    echo '<a href="view.php?id=' . $course->id . '&amp;random=' . rand(1, 10000) .
                        '&amp;section=' . $section . '&amp;move=-1&amp;sesskey=' . sesskey() .
                        '#section-' . ($section - 1) . '" title="' . $strmoveup . '">' .
                        '<img src="' . $OUTPUT->pix_url('t/up') . '" class="iconsmall up" alt="' .
                        $strmoveup . '" /></a><br />';
                }

                if ($section < $course->numsections) {    // Add a arrow to move section down.
                    echo '<a href="view.php?id=' . $course->id . '&amp;random=' . rand(1, 10000) .
                        '&amp;section=' . $section . '&amp;move=1&amp;sesskey=' . sesskey() .
                        '#section-' . ($section + 1) . '" title="' . $strmovedown . '">' .
                        '<img src="' . $OUTPUT->pix_url('t/down') . '" class="iconsmall down" alt="' .
                        $strmovedown . '" /></a><br />';
                }
            }

            if ($selectedweek <= 0 || $PAGE->user_is_editing()) {
                echo "</td>";
            }
            echo "</tr>";

            if ($selectedweek <= 0 || $PAGE->user_is_editing()) {
                echo '<tr><td colspan="3" ' . $colorsides . ' align="center">';
                echo '&nbsp;';
                echo '</td></tr>';
                echo "<tr><td colspan=3><img src=\"../pix/spacer.gif\" width=1 height=1></td></tr>";
            }
        }

        echo '</table>';
        unset($sections[$section]);
        $section++;
    }

    if ($selectedweek > 0 && !$PAGE->user_is_editing()) {
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

echo "</li>\n";
echo "</ul>\n";

// Include course format js module.
$PAGE->requires->js('/course/format/fntabs/format.js');
