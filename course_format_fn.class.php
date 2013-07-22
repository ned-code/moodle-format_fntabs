<?php

/**
 * course_format is the base class for all course formats
 *
 * This class provides all the functionality for a course format
 */
define('COMPLETION_WAITFORGRADE_FN', 5);
define('COMPLETION_SAVED_FN', 4);

require_once($CFG->libdir . '/filelib.php');
require_once($CFG->libdir . '/completionlib.php');

/**
 * Standard base class for all course formats
 */
class course_format_fn extends course_format {

    /**
     * Contructor
     *
     * @param $course object The pre-defined course object. Passed by reference, so that extended info can be added.
     *
     */
    public $tdselectedclass;

    function course_format_fn(&$course) {
        global $mods, $modnames, $modnamesplural, $modnamesused, $sections, $DB;

        parent::course_format($course);

        $this->mods = &$mods;
        $this->modnames = &$modnames;
        $this->modnamesplural = &$modnamesplural;
        $this->modnamesused = &$modnamesused;
        $this->sections = &$sections;
    }

    /*
     * Add extra config options in course object
     * 
     * * */

    function get_course($course=null) {
        global $DB;

        if (!empty($course->id)) {
            $extradata = $DB->get_records('course_config_fn', array('courseid' => $course->id));
        } else if (!empty($this->course->id)) {
            $extradata = $DB->get_records('course_config_fn', array('courseid' => $this->course->id));
        } else {
            $extradata = false;
        }

        if (is_null($course)) {
            $course = new Object();
        }

        if ($extradata) {
            foreach ($extradata as $extra) {
                $this->course->{$extra->variable} = $extra->value;
                $course->{$extra->variable} = $extra->value;
            }
        }

        return $course;
    }

    /** Custom functions * */
    function handle_extra_actions() {
        global $DB;

        if (isset($_POST['sec0title'])) {
            if (!$course = $DB->get_record('course', array('id' => $_POST['id']))) {
                print_error('This course doesn\'t exist.');
            }
            FN_get_course($course);
            $course->sec0title = $_POST['sec0title'];
            FN_update_course($course);
            $cm->course = $course->id;
        }
    }

    function get_week_info($tabrange, $week) {
        global $SESSION, $DB;

        $fnmaxtab = $DB->get_field('course_config_fn', 'value', array('courseid' => $this->course->id, 'variable' => 'maxtabs'));

        if ($fnmaxtab) {
            $maximumtabs = $fnmaxtab;
        } else {
            $maximumtabs = 12;
        }
        if ($this->course->numsections == $maximumtabs) {
            $tablow = 1;
            $tabhigh = $maximumtabs;
        } else if ($tabrange > 1000) {
            $tablow = (int) ($tabrange / 1000);
            $tabhigh = (int) ($tablow + $maximumtabs - 1);
        } else if (($tabrange == 0) && ($week == 0)) {
            $tablow = ((int) ((int) ($this->course->numsections - 1) / (int) $maximumtabs) * $maximumtabs) + 1;
            $tabhigh = $tablow + $maximumtabs - 1;
        } else if ($tabrange == 0) {
            $tablow = ((int) ((int) $week / (int) $maximumtabs) * $maximumtabs) + 1;
            $tabhigh = $tablow + $maximumtabs - 1;
        } else {
            $tablow = 1;
            $tabhigh = $maximumtabs;
        }
        $tabhigh = MIN($tabhigh, $this->course->numsections);


        /// Normalize the tabs to always display FNMAXTABS...
        if (($tabhigh - $tablow + 1) < $maximumtabs) {
            $tablow = $tabhigh - $maximumtabs + 1;
        }


        /// Save the low and high week in SESSION variables... If they already exist, and the selected
        /// week is in their range, leave them as is.
        if (($tabrange >= 1000) || !isset($SESSION->FN_tablow[$this->course->id]) || !isset($SESSION->FN_tabhigh[$this->course->id]) ||
                ($week < $SESSION->FN_tablow[$this->course->id]) || ($week > $SESSION->FN_tabhigh[$this->course->id])) {
            $SESSION->FN_tablow[$this->course->id] = $tablow;
            $SESSION->FN_tabhigh[$this->course->id] = $tabhigh;
        } else {
            $tablow = $SESSION->FN_tablow[$this->course->id];
            $tabhigh = $SESSION->FN_tabhigh[$this->course->id];
        }
        $tablow = MAX($tablow, 1);
        $tabhigh = MIN($tabhigh, $this->course->numsections);

        /// If selected week in a different set of tabs, move it to the current set...
        if (($week != 0) && ($week < $tablow)) {
            $week = $SESSION->G8_selected_week[$this->course->id] = $tablow;
        } else if ($week > $tabhigh) {
            $week = $SESSION->G8_selected_week[$this->course->id] = $tabhigh;
        }
        unset($maximumtabs);
        return array($tablow, $tabhigh, $week);
    }

    function print_weekly_activities_bar($week=0, $tabrange=0, $resubmission=false) {
        global $FULLME, $CFG, $course, $DB, $USER, $PAGE;

        $selectedcolour   = $DB->get_field('course_config_fn', 'value', array('courseid' => $this->course->id, 'variable' => 'selectedcolour'));
        $activelinkcolour = $DB->get_field('course_config_fn', 'value', array('courseid' => $this->course->id, 'variable' => 'activelinkcolour'));
        $activecolour     = $DB->get_field('course_config_fn', 'value', array('courseid' => $this->course->id, 'variable' => 'activecolour'));

        $inactivelinkcolour = $DB->get_field('course_config_fn', 'value', array('courseid' => $this->course->id, 'variable' => 'inactivelinkcolour'));
        $inactivecolour     = $DB->get_field('course_config_fn', 'value', array('courseid' => $this->course->id, 'variable' => 'inactivecolour'));

        $selectedcolour = $selectedcolour ? $selectedcolour : 'FFFF33';
        $activelinkcolour   = $activelinkcolour ? $activelinkcolour : '000000';
        $inactivelinkcolour   = $inactivelinkcolour ? $inactivelinkcolour : '000000';
        $activecolour   = $activecolour ? $activecolour : 'DBE6C4';
        $inactivecolour = $inactivecolour ? $inactivecolour : 'BDBBBB';

        $fnmaxtab       = $DB->get_field('course_config_fn', 'value', array('courseid' => $this->course->id, 'variable' => 'maxtabs'));

        if ($fnmaxtab) {
            $maximumtabs = $fnmaxtab;
        } else {
            $maximumtabs = 12;
        }

        echo "
        <style>
        .fnweeklynavselected {
            background-color: #$selectedcolour;
        }
        .fnweeklynavnorm,
        .fnweeklynavnorm a:active {
            background-color: #$activecolour;
        }
        .fnweeklynavdisabled {
            color: #$inactivelinkcolour;
            background-color: #$inactivecolour;
        }
        .fnweeklynav .tooltip {
            color: #$activelinkcolour;
        }
        </style>";


        $completioninfo = new completion_info($course);

        list($tablow, $tabhigh, $week) = $this->get_week_info($tabrange, $week);

        $timenow = time();
        $weekdate = $this->course->startdate;    // this should be 0:00 Monday of that week
        $weekdate += 7200;                 // Add two hours to avoid possible DST problems
        $weekofseconds = 604800;

        if ($this->course->numsections > 20) {
            $extraclassfortab = "tab-greaterthan5";
        } else {
            $extraclassfortab = "tab-lessthan5";
        }

        if (isset($this->course->topicheading) && !empty($this->course->topicheading)) {
            $strtopicheading = $this->course->topicheading;
        } else {
            $strtopicheading = 'Week';
        }
        $context = get_context_instance(CONTEXT_COURSE, $this->course->id);
        $isteacher = has_capability('moodle/course:update', $this->context);
        $iseditingteacher = has_capability('gradereport/grader:view', $this->context);
        $url = preg_replace('/(^.*)(&selected_week\=\d+)(.*)/', '$1$3', $FULLME);

        $actbar = '';
        $actbar .= '<table cellpadding="0" cellspacing="0"><tr><td>';
        $actbar .= '<table cellpadding="0" cellspacing="0"  class="fnweeklynav"><tr>';
        $width = (int) (100 / ($tabhigh - $tablow + 3));
        $actbar .= '<td width="4" align="center" height="25"></td>';

        if ($tablow <= 1) {
            $actbar .= '<td height="25" class="tab-heading"><strong>' . $strtopicheading . ':&nbsp;</strong></td>';
        } else {
            $prv = ($tablow - $maximumtabs) * 1000;
            if ($prv < 0) {
                $prv = 1000;
            }
            $actbar .= '<td id="fn_tab_previous" height="25"><a href="' . $url . '&selected_week=' . $prv . '">Previous</a></td>';
        }

        $tdselectedclass = array();

        $currentweek = ($timenow > $course->startdate) ?
                (int) ((($timenow - $course->startdate) / $weekofseconds) + 1) : 0;

        $currentweek = min($currentweek, $course->numsections);


        for ($i = $tablow; $i <= $tabhigh; $i++) {
            //if (empty($this->sections[$i]->visible) || ($timenow < $weekdate)) {
            if (empty($this->sections[$i]->visible) || $i > $currentweek) {
                if ($i == $week) {
                    $css = 'fnweeklynavdisabledselected';
                } else {
                    $css = 'fnweeklynavdisabled';
                }
                $tdselectedclass[$i] = $css;
                if ($isteacher) {
                    $f = '<a href="' . $url . '&selected_week=' . $i . '" ><span class="' . $css . '">&nbsp;' .
                            $i . '&nbsp;</span></a>';
                } else {
                    $f = ' ' . $i . ' ';
                }
                $actbar .= '<td class="' . $css . ' ' . $extraclassfortab . '" height="25" width="" alt="Upcoming sections" title="Upcoming sections">' . $f . '</td>';
            } else if ($i == $week) {
                if (!$isteacher && !is_siteadmin() && !empty($completioninfo) && !$iseditingteacher) {
                    if ($completioninfo->is_enabled() && $CFG->enablecompletion) {
                        $f = $this->is_section_finished($this->sections[$i], $this->mods) ? 'green-tab' : 'red-tab';
                    } else {
                        $f = '';
                    }
                } else {
                    $f = '';
                }
                $tdselectedclass[$i] = 'fnweeklynavselected';
                $actbar .= '<td class="fnweeklynavselected ' . $f . ' ' . $extraclassfortab . '" id=fnweeklynav' . $i . ' width="" height="25"> ' . $i . ' </td>';
            } else {
                if (!$isteacher && !is_siteadmin() && !$iseditingteacher) {
                    if ($completioninfo->is_enabled() && $CFG->enablecompletion) {
                        $f = $this->is_section_finished($this->sections[$i], $this->mods) ? 'green-tab' : 'red-tab';
                        $w = $i;
                        $sectionid = $i;
                        $section = $DB->get_record("course_sections", array("section" => $sectionid, "course" => $course->id));
                        $activitiesstatusarray = get_activities_status($course, $section, $resubmission);
                        $compl = $activitiesstatusarray['complete'];
                        $incompl = $activitiesstatusarray['incomplete'];
                        $svd = $activitiesstatusarray['saved'];
                        $notattemptd = $activitiesstatusarray['notattempted'];
                        $waitforgrade = $activitiesstatusarray['waitngforgrade'];
                    } else {
                        $f = '';
                    }
                } else {
                    $f = '';
                }
                $tdselectedclass[$i] = 'fnweeklynavnorm';
                $tooltipclass = ($i >= ($tabhigh / 2)) ? '-right' : '';
                $actbar .= '<td class="fnweeklynavnorm ' . $f . ' ' . $extraclassfortab . '" id=fnweeklynav' . $i . ' width="" height="25"><a class="tooltip'.$tooltipclass.'" href="' . $url . '&selected_week=' . $i . '">&nbsp;' . $i . '&nbsp;';
                if (!$isteacher && !is_siteadmin() && !is_primary_admin($USER->id) && !$iseditingteacher && $CFG->enablecompletion && $completioninfo->is_enabled()) {
                    /*
                    $actbar .= '<span class="custom info">
                            <div class="head">Week ' . $i . '</div>
                            <ul>
                                <li class="complete"><img src="' . $CFG->wwwroot . '/course/format/' . $this->course->format . '/pix/completed.gif" /> ' . $compl . ' Complete</li>
                                <li class="not-attp"><img src="' . $CFG->wwwroot . '/course/format/' . $this->course->format . '/pix/completion-auto-n.gif" /> ' . $notattemptd . ' Not Attempted</li>
                                <li class="in-complete"><img src="' . $CFG->wwwroot . '/course/format/' . $this->course->format . '/pix/incomplete.gif" /> ' . $incompl . ' Incomplete</li>
                                <li class="grade-wait"><img src="' . $CFG->wwwroot . '/course/format/' . $this->course->format . '/pix/unmarked.gif" /> ' . $waitforgrade . ' Waiting for Grade</li>
                                <li class="saved"><img src="' . $CFG->wwwroot . '/course/format/' . $this->course->format . '/pix/saved.gif" /> ' . $svd . ' Draft</li>
                                
                            </ul>
                            <img class="arrows" src="' . $CFG->wwwroot . '/course/format/' . $this->course->format . '/pix/t-arrow.gif" alt="Information" height="20" width="24" />
                        </span>'; */
                        
                    $actbar .= '<span class="custom info">
                            <ul>
                                <li class="complete"><img src="' . $CFG->wwwroot . '/course/format/' . $this->course->format . '/pix/completed.gif" /> ' . $compl . '</li>
                                <li class="not-attp"><img src="' . $CFG->wwwroot . '/course/format/' . $this->course->format . '/pix/completion-auto-n.gif" /> ' . $notattemptd . '</li>
                                <li class="in-complete"><img src="' . $CFG->wwwroot . '/course/format/' . $this->course->format . '/pix/incomplete.gif" /> ' . $incompl . '</li>
                                <li class="grade-wait"><img src="' . $CFG->wwwroot . '/course/format/' . $this->course->format . '/pix/unmarked.gif" /> ' . $waitforgrade . '</li>
                                <li class="saved"><img src="' . $CFG->wwwroot . '/course/format/' . $this->course->format . '/pix/saved.gif" /> ' . $svd . '</li>
                                
                            </ul>
                            <img class="arrows" src="' . $CFG->wwwroot . '/course/format/' . $this->course->format . '/pix/t-arrow-grey.gif" alt="Information" height="20" width="24" />
                        </span>';
                }
                $actbar .= '</a>' . '</td>';
            }
           // $weekdate += ( $weekofseconds);
            $actbar .= '<td align="center" height="25" style="width: 2px;">' .
                    '<img src="' . $CFG->wwwroot . '/pix/spacer.gif" height="1" width="1" alt="" /></td>';
        }

        if (($week == 0) && ($tabhigh >= $this->course->numsections)) {
            $actbar .= '<td class="fnweeklynavselected ' . $extraclassfortab . '"  width="" height="25">All</td>';
        } else if ($tabhigh >= $this->course->numsections) {
            $actbar .= '<td class="fnweeklynavnorm ' . $extraclassfortab . '" width="" height="25">' .
                    '<a href="' . $url . '&selected_week=0">All</a></td>';
        } else {
            $nxt = ($tabhigh + 1) * 1000;
            $actbar .= '<td id="fn_tab_next" height="25"><a href="' . $url . '&selected_week=' . $nxt . '">Next</a></td>';
        }
        $settingIcon='';
        if ($PAGE->user_is_editing() && has_capability('moodle/course:update', $context)) {
            $settingIcon = '<a href="' . $CFG->wwwroot . '/course/format/' . $this->course->format . '/tabsettings.php?id='.$this->course->id.'" > 
                                <img style="margin: 3px 1px 1px 5px;" src="' . $CFG->wwwroot . '/course/format/' . $this->course->format . '/pix/cog.png" /></a>';
        }
        $actbar .= '<td width="1" align="center" height="25">'.$settingIcon.'</td>';
        $actbar .= '</tr>';
        $actbar .= '<tr>';
        $actbar .= '<td height="3" colspan="2"></td>';


        $this->tdselectedclass = $tdselectedclass;

        for ($i = $tablow; $i <= $tabhigh; $i++) {
            if ($i == $week) {
                $actbar .= '<td height="3" class="' . $tdselectedclass[$i] . '"></td>';
            } else {
                $actbar .= '<td height="3"></td>';
            }
            $actbar .= '<td height="3"></td>';
        }
        $actbar .= '<td height="3" colspan="2"></td>';
        $actbar .= '</tr>';
        $actbar .= '</table>';
        $actbar .= '</td></tr></table>';
        rebuild_course_cache($this->course->id);
        unset($maximumtabs);

        return $actbar;
    }

    /*     * LIBRARY REPLACEMENT* */

    /**
     * Prints a section full of activity modules
     */
    function print_section_fn($course, $section, $mods, $modnamesused, $absolute=false, $width="100%", $hidecompletion=false, $resubmission=false) {
        global $CFG, $USER, $DB, $PAGE, $OUTPUT;

        static $initialised;
        static $groupbuttons;
        static $groupbuttonslink;
        static $isediting;
        static $ismoving;
        static $strmovehere;
        static $strmovefull;
        static $strunreadpostsone;
        static $groupings;
        static $modulenames;

        if (!isset($initialised)) {
            $groupbuttons = ($course->groupmode or (!$course->groupmodeforce));
            $groupbuttonslink = (!$course->groupmodeforce);
            $isediting = $PAGE->user_is_editing();
            $ismoving = $isediting && ismoving($course->id);
            if ($ismoving) {
                $strmovehere = get_string("movehere");
                $strmovefull = strip_tags(get_string("movefull", "", "'$USER->activitycopyname'"));
            }
            $modulenames = array();
            $initialised = true;
        }

        $modinfo = get_fast_modinfo($course);

        $completioninfo = new completion_info($course);

        //Accessibility: replace table with list <ul>, but don't output empty list.
        if (!empty($section->sequence)) {

            // Fix bug #5027, don't want style=\"width:$width\".
            echo "<ul class=\"section img-text\">\n";
            $sectionmods = explode(",", $section->sequence);

            foreach ($sectionmods as $modnumber) {
                if (empty($mods[$modnumber])) {
                    continue;
                }

                /**
                 * @var cm_info
                 */
                $mod = $mods[$modnumber];  

                if ($ismoving and $mod->id == $USER->activitycopy) {
                    // do not display moving mod
                    continue;
                }

                if (isset($modinfo->cms[$modnumber])) {
                    // We can continue (because it will not be displayed at all)
                    // if:
                    // 1) The activity is not visible to users
                    // and
                    // 2a) The 'showavailability' option is not set (if that is set,
                    //     we need to display the activity so we can show
                    //     availability info)
                    // or
                    // 2b) The 'availableinfo' is empty, i.e. the activity was
                    //     hidden in a way that leaves no info, such as using the
                    //     eye icon.
                    if (!$modinfo->cms[$modnumber]->uservisible &&
                            (empty($modinfo->cms[$modnumber]->showavailability) ||
                            empty($modinfo->cms[$modnumber]->availableinfo))) {
                        // visibility shortcut
                        continue;
                    }
                } else {
                    if (!file_exists("$CFG->dirroot/mod/$mod->modname/lib.php")) {
                        // module not installed
                        continue;
                    }
                    if (!coursemodule_visible_for_user($mod) &&
                            empty($mod->showavailability)) {
                        // full visibility check
                        continue;
                    }
                }

                if (!isset($modulenames[$mod->modname])) {
                    $modulenames[$mod->modname] = get_string('modulename', $mod->modname);
                }
                $modulename = $modulenames[$mod->modname];

                // In some cases the activity is visible to user, but it is
                // dimmed. This is done if viewhiddenactivities is true and if:
                // 1. the activity is not visible, or
                // 2. the activity has dates set which do not include current, or
                // 3. the activity has any other conditions set (regardless of whether
                //    current user meets them)
                $canviewhidden = has_capability(
                        'moodle/course:viewhiddenactivities', get_context_instance(CONTEXT_MODULE, $mod->id));
                $accessiblebutdim = false;
                if ($canviewhidden) {
                    $accessiblebutdim = !$mod->visible;
                    if (!empty($CFG->enableavailability)) {
                        $accessiblebutdim = $accessiblebutdim ||
                                $mod->availablefrom > time() ||
                                ($mod->availableuntil && $mod->availableuntil < time()) ||
                                count($mod->conditionsgrade) > 0 ||
                                count($mod->conditionscompletion) > 0;
                    }
                }

                $liclasses = array();
                $liclasses[] = 'activity';
                $liclasses[] = $mod->modname;
                $liclasses[] = 'modtype_' . $mod->modname;
                $extraclasses = $mod->get_extra_classes();
                if ($extraclasses) {
                    $liclasses = array_merge($liclasses, explode(' ', $extraclasses));
                }
                echo html_writer::start_tag('li', array('class' => join(' ', $liclasses), 'id' => 'module-' . $modnumber));
                if ($ismoving) {
                    echo '<a title="' . $strmovefull . '"' .
                    ' href="' . $CFG->wwwroot . '/course/mod.php?moveto=' . $mod->id . '&amp;sesskey=' . sesskey() . '">' .
                    '<img class="movetarget" src="' . $OUTPUT->pix_url('movehere') . '" ' .
                    ' alt="' . $strmovehere . '" /></a><br />
                     ';
                }

                $classes = array('mod-indent');
                if (!empty($mod->indent)) {
                    $classes[] = 'mod-indent-' . $mod->indent;
                    if ($mod->indent > 15) {
                        $classes[] = 'mod-indent-huge';
                    }
                }
                echo html_writer::start_tag('div', array('class' => join(' ', $classes)));

                // Get data about this course-module
                list($content, $instancename) = array($modinfo->cms[$modnumber]->get_formatted_content(array('overflowdiv' => true, 'noclean' => true)), $modinfo->cms[$modnumber]->get_formatted_name());
                        //=get_print_section_cm_text($modinfo->cms[$modnumber], $course);

                //Accessibility: for files get description via icon, this is very ugly hack!
                $altname = '';
                $altname = $mod->modfullname;
                if (!empty($customicon)) {
                    $archetype = plugin_supports('mod', $mod->modname, FEATURE_MOD_ARCHETYPE, MOD_ARCHETYPE_OTHER);
                    if ($archetype == MOD_ARCHETYPE_RESOURCE) {
                        $mimetype = mimeinfo_from_icon('type', $customicon);
                        $altname = get_mimetype_description($mimetype);
                    }
                }
                // Avoid unnecessary duplication: if e.g. a forum name already
                // includes the word forum (or Forum, etc) then it is unhelpful
                // to include that in the accessible description that is added.
                if (false !== strpos(textlib::strtolower($instancename), textlib::strtolower($altname))) {
                    $altname = '';
                }
                // File type after name, for alphabetic lists (screen reader).
                if ($altname) {
                    $altname = get_accesshide(' ' . $altname);
                }

                // We may be displaying this just in order to show information
                // about visibility, without the actual link
                $contentpart = '';
                if ($mod->uservisible) {
                    // Nope - in this case the link is fully working for user
                    $linkclasses = '';
                    $textclasses = '';
                    if ($accessiblebutdim) {
                        $linkclasses .= ' dimmed';
                        $textclasses .= ' dimmed_text';
                        $accesstext = '<span class="accesshide">' .
                                get_string('hiddenfromstudents') . ': </span>';
                    } else {
                        $accesstext = '';
                    }
                    if ($linkclasses) {
                        $linkcss = 'class="' . trim($linkclasses) . '" ';
                    } else {
                        $linkcss = '';
                    }
                    if ($textclasses) {
                        $textcss = 'class="' . trim($textclasses) . '" ';
                    } else {
                        $textcss = '';
                    }

                    // Get on-click attribute value if specified
                    $onclick = $mod->get_on_click();
                    if ($onclick) {
                        $onclick = ' onclick="' . $onclick . '"';
                    }

                    if ($url = $mod->get_url()) {
                        // Display link itself
                        echo '<a ' . $linkcss . $mod->extra . $onclick .
                        ' href="' . $url . '"><img src="' . $mod->get_icon_url() .
                        '" class="activityicon" alt="' .
                        $modulename . '" /> ' .
                        $accesstext . '<span class="instancename">' .
                        $instancename . $altname . '</span></a>';

                        // If specified, display extra content after link
                        if ($content) {
                            $contentpart = '<div class="contentafterlink' .
                                    trim($textclasses) . '">' . $content . '</div>';
                        }
                    } else {
                        // No link, so display only content
                        $contentpart = '<div ' . $textcss . $mod->extra . '>' .
                                $accesstext . $content . '</div>';
                    }

                    if (!empty($mod->groupingid) && has_capability('moodle/course:managegroups', get_context_instance(CONTEXT_COURSE, $course->id))) {
                        if (!isset($groupings)) {
                            $groupings = groups_get_all_groupings($course->id);
                        }
                        echo " <span class=\"groupinglabel\">(" . format_string($groupings[$mod->groupingid]->name) . ')</span>';
                    }
                } else {
                    $textclasses = $extraclasses;
                    $textclasses .= ' dimmed_text';
                    if ($textclasses) {
                        $textcss = 'class="' . trim($textclasses) . '" ';
                    } else {
                        $textcss = '';
                    }
                    $accesstext = '<span class="accesshide">' .
                            get_string('notavailableyet', 'condition') .
                            ': </span>';

                    if ($url = $mod->get_url()) {
                        // Display greyed-out text of link
                        echo '<div ' . $textcss . $mod->extra .
                        ' >' . '<img src="' . $mod->get_icon_url() .
                        '" class="activityicon" alt="' .
                        $modulename .
                        '" /> <span>' . $instancename . $altname .
                        '</span></div>';

                        // Do not display content after link when it is greyed out like this.
                    } else {
                        // No link, so display only content (also greyed)
                        $contentpart = '<div ' . $textcss . $mod->extra . '>' .
                                $accesstext . $content . '</div>';
                    }
                }

                // Module can put text after the link (e.g. forum unread)
                echo $mod->get_after_link();

                // If there is content but NO link (eg label), then display the
                // content here (BEFORE any icons). In this case cons must be
                // displayed after the content so that it makes more sense visually
                // and for accessibility reasons, e.g. if you have a one-line label
                // it should work similarly (at least in terms of ordering) to an
                // activity.
                if (empty($url)) {
                    echo $contentpart;
                }

                if ($isediting) {
                    if ($groupbuttons and plugin_supports('mod', $mod->modname, FEATURE_GROUPS, 0)) {
                        if (!$mod->groupmodelink = $groupbuttonslink) {
                            $mod->groupmode = $course->groupmode;
                        }
                    } else {
                        $mod->groupmode = false;
                    }
                    echo '&nbsp;&nbsp;';
                    //echo make_editing_buttons($mod, $absolute, true, $mod->indent, $section->section);
                    
                 
                        if (!($mod instanceof cm_info)) {
                            $modinfo = get_fast_modinfo($mod->course);
                            $mod = $modinfo->get_cm($mod->id);
                        }
                        $actions = course_get_cm_edit_actions($mod, $mod->indent, $section->section);

                        $courserenderer = $PAGE->get_renderer('core', 'course');
                        // The space added before the <span> is a ugly hack but required to set the CSS property white-space: nowrap
                        // and having it to work without attaching the preceding text along with it. Hopefully the refactoring of
                        // the course page HTML will allow this to be removed.
                        echo ' ' . $courserenderer->course_section_cm_edit_actions($actions);
                                       
                    
                    
                    echo $mod->get_after_edit_icons();
                }

                // Completion
                require_once('modulelib.php');

                $completion = $hidecompletion ? COMPLETION_TRACKING_NONE : $completioninfo->is_enabled($mod);
                if ($completion != COMPLETION_TRACKING_NONE && isloggedin() &&
                        !isguestuser() && $mod->uservisible) {
                    $completiondata = $completioninfo->get_data($mod, true);
                    $completionicon = '';
                    if ($isediting) {
                        switch ($completion) {
                            case COMPLETION_TRACKING_MANUAL :
                                $completionicon = 'manual-enabled';
                                break;
                            case COMPLETION_TRACKING_AUTOMATIC :
                                $completionicon = 'auto-enabled';
                                break;
                            default: // wtf
                        }
                    }
                    ///this condition is added by sudhanshu                    
                    else if (is_siteadmin() || !has_capability('mod/assignment:submit', get_context_instance(CONTEXT_COURSE, $course->id))) {
                        switch ($completion) {
                            case COMPLETION_TRACKING_MANUAL :
                                $completionicon = 'manual-enabled';
                                break;
                            case COMPLETION_TRACKING_AUTOMATIC :
                                $completionicon = 'auto-enabled';
                                break;
                            default: // wtf
                        }
                    } else if ($completion == COMPLETION_TRACKING_MANUAL) {
                        switch ($completiondata->completionstate) {
                            case COMPLETION_INCOMPLETE:
                                $completionicon = 'manual-n';
                                break;
                            case COMPLETION_COMPLETE:
                                $completionicon = 'manual-y';
                                break;
                        }
                    } else { // Automatic                      
                        if (($mod->modname == 'assignment' || $mod->modname == 'assign') && isset($mod->completiongradeitemnumber)) {                           
                            $act_compl = is_saved_or_submitted($mod, $USER->id, $resubmission);
                            if ($act_compl == 'submitted') {
                               // $completiondata->completionstate = COMPLETION_WAITFORGRADE_FN;
                            } else if ($act_compl == 'waitinggrade') {
                               $completiondata->completionstate = COMPLETION_WAITFORGRADE_FN;
                            } else if ($act_compl == 'saved') {
                                $completiondata->completionstate = COMPLETION_SAVED_FN;
                            }
                        }

                        switch ($completiondata->completionstate) {
                            case COMPLETION_INCOMPLETE:
                                $completionicon = 'auto-n';
                                break;
                            case COMPLETION_COMPLETE:
                                $completionicon = 'auto-y';
                                break;
                            case COMPLETION_COMPLETE_PASS:
                                $completionicon = 'auto-pass';
                                break;
                            case COMPLETION_COMPLETE_FAIL:
                                $completionicon = 'auto-fail';
                                break;
                            case COMPLETION_WAITFORGRADE_FN:
                                $completionicon = 'submitted';
                                break;
                            case COMPLETION_SAVED_FN:
                                $completionicon = 'saved';
                                break;
                        }
                    }
                    if ($completionicon) {
                        $imgsrc = '' . $CFG->wwwroot . '/course/format/' . $this->course->format . '/pix/completion-' . $completionicon . '.gif';
                        $imgalt = s(get_string('completion-alt-' . $completionicon, 'format_fntabs'));
                        if ($completion == COMPLETION_TRACKING_MANUAL && !$isediting && has_capability('mod/assignment:submit', get_context_instance(CONTEXT_COURSE, $course->id)) && !is_primary_admin($USER->id)) {
                            $imgtitle = s(get_string('completion-title-' . $completionicon, 'format_fntabs'));
                            $newstate =
                                    $completiondata->completionstate == COMPLETION_COMPLETE ? COMPLETION_INCOMPLETE : COMPLETION_COMPLETE;

                            // In manual mode the icon is a toggle form...
                            // If this completion state is used by the
                            // conditional activities system, we need to turn
                            // off the JS.i
                            if (!empty($CFG->enableavailability) &&
                                    condition_info::completion_value_used_as_condition($course, $mod)) {
                                $extraclass = ' preventjs';
                            } else {
                                $extraclass = '';
                            }
                            echo "
                                    <form class='togglecompletion$extraclass' method='post' action='" . $CFG->wwwroot . "/course/togglecompletion.php'><div>
                                    <input type='hidden' name='id' value='{$mod->id}' />
                                    <input type='hidden' name='sesskey' value='" . sesskey() . "' />
                                    <input type='hidden' name='completionstate' value='$newstate' />
                                    <input type='image' src='$imgsrc' alt='$imgalt' title='$imgtitle' />
                                    </div></form>";
                        } else {
                            // In auto mode, or when editing, the icon is just an image
                            echo "<span class='autocompletion'>";
                            echo "<img src='$imgsrc' alt='$imgalt' title='$imgalt' /></span>";
                        }
                    }
                }

                // If there is content AND a link, then display the content here
                // (AFTER any icons). Otherwise it was displayed before
                if (!empty($url)) {
                    echo $contentpart;
                }

                // Show availability information (for someone who isn't allowed to
                // see the activity itself, or for staff)
                if (!$mod->uservisible) {
                    echo '<div class="availabilityinfo">' . $mod->availableinfo . '</div>';
                } else if ($canviewhidden && !empty($CFG->enableavailability)) {
                    $ci = new condition_info($mod);
                    $fullinfo = $ci->get_full_information();
                    if ($fullinfo) {
                        echo '<div class="availabilityinfo">' . get_string($mod->showavailability ? 'userrestriction_visible' : 'userrestriction_hidden', 'condition', $fullinfo) . '</div>';
                    }
                }

                echo html_writer::end_tag('div');
                echo html_writer::end_tag('li') . "\n";
            }
        } elseif ($ismoving) {
            echo "<ul class=\"section\">\n";
        }

        if ($ismoving) {
            echo '<li><a title="' . $strmovefull . '"' .
            ' href="' . $CFG->wwwroot . '/course/mod.php?movetosection=' . $section->id . '&amp;sesskey=' . sesskey() . '">' .
            '<img class="movetarget" src="' . $OUTPUT->pix_url('movehere') . '" ' .
            ' alt="' . $strmovehere . '" /></a></li>
             ';
        }
        if (!empty($section->sequence) || $ismoving) {
            echo "</ul><!--class='section'-->\n\n";
        }
    }

    /**
     * If used, this will just call the library function (for now). Replace this with your own to make it
     * do what you want.
     *
     */
    function print_section_add_menus($course, $section, $modnames, $vertical=false, $return=false) {
        //return print_section_add_menus($course, $section, $modnames, $vertical, $return);
        global $PAGE;
        
        $output = '';
        $courserenderer = $PAGE->get_renderer('core', 'course');
        $output = $courserenderer->course_section_add_cm_control($course, $section, null,
                array('inblock' => $vertical));
        if ($return) {
            return $output;
        } else {
            echo $output;
            return !empty($output);
        }        
    }

    function is_section_finished(&$section, $mods) {
        global $USER, $course;
        $completioninfo = new completion_info($course);
        $modules = get_course_section_mods($course->id, $section->id);
        $count = 0;
        if (count($modules) >= 1) {
            foreach ($modules as $modu) {
                $completiondata = $completioninfo->get_data($modu, true);
                if ($completiondata->completionstate == 1 || $completiondata->completionstate == 2) {
                    $count++;
                }
            }
            if ($count == count($modules)) {
                return true;
            } else {
                return false;
            }
        }
    }

    function first_unfinished_section() {
        if (is_array($this->sections) && is_array($this->mods)) {
            foreach ($this->sections as $section) {
                if ($section->section > 0) {
                    if (!$this->is_section_finished($section, $this->mods)) {
                        return $section->section;
                    }
                }
            }
        }
        return false;
    } 

}