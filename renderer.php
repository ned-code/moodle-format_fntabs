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
require_once($CFG->dirroot.'/course/format/renderer.php');
require_once($CFG->dirroot.'/course/format/fntabs/lib.php');

/**
 * Basic renderer for topics format.
 *
 * @copyright 2012 Dan Poltawski
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class format_fntabs_renderer extends format_section_renderer_base {

    /**
     * Constructor method, calls the parent constructor
     *
     * @param moodle_page $page
     * @param string $target one of rendering target constants
     */
    public function __construct(moodle_page $page, $target) {
        global $mods, $modnames, $modnamesplural, $modnamesused, $sections;

        parent::__construct($page, $target);

        $this->mods = &$mods;
        $this->modnames = &$modnames;
        $this->modnamesplural = &$modnamesplural;
        $this->modnamesused = &$modnamesused;
        $this->sections = &$sections;

        // Since format_topics_renderer::section_edit_controls() only displays the 'Set current section'
        // control when editing mode is on
        // we need to be sure that the link 'Turn editing mode on' is available for a user
        // who does not have any other managing capability.
        $page->set_other_editing_capability('moodle/course:setcurrentsection');
    }

    /**
     * Generate the starting container html for a list of sections
     * @return string HTML to output.
     */
    protected function start_section_list() {
        return html_writer::start_tag('ul', array('class' => 'topics'));
    }

    /**
     * Generate the closing container html for a list of sections
     * @return string HTML to output.
     */
    protected function end_section_list() {
        return html_writer::end_tag('ul');
    }

    /**
     * Generate the title for this section page
     * @return string the page title
     */
    protected function page_title() {
        return get_string('topicoutline');
    }

    /**
     * Generate the edit control items of a section
     *
     * @param stdClass $course The course entry from DB
     * @param stdClass $section The course_section entry from DB
     * @param bool $onsectionpage true if being printed on a section page
     * @return array of edit control items
     */
    protected function section_edit_control_items($course, $section, $onsectionpage = false) {
        global $PAGE;

        if (!$PAGE->user_is_editing()) {
            return array();
        }

        $coursecontext = context_course::instance($course->id);

        if ($onsectionpage) {
            $url = course_get_url($course, $section->section);
        } else {
            $url = course_get_url($course);
        }
        $url->param('sesskey', sesskey());

        $isstealth = $section->section > $course->numsections;
        $controls = array();
        if (!$isstealth && $section->section && has_capability('moodle/course:setcurrentsection', $coursecontext)) {
            if ($course->marker == $section->section) {  // Show the "light globe" on/off.
                $url->param('marker', 0);
                $markedthistopic = get_string('markedthistopic');
                $highlightoff = get_string('highlightoff');
                $controls['highlight'] = array('url' => $url, "icon" => 'i/marked',
                                               'name' => $highlightoff,
                                               'pixattr' => array('class' => '', 'alt' => $markedthistopic),
                                               'attr' => array('class' => 'editing_highlight', 'title' => $markedthistopic));
            } else {
                $url->param('marker', $section->section);
                $markthistopic = get_string('markthistopic');
                $highlight = get_string('highlight');
                $controls['highlight'] = array('url' => $url, "icon" => 'i/marker',
                                               'name' => $highlight,
                                               'pixattr' => array('class' => '', 'alt' => $markthistopic),
                                               'attr' => array('class' => 'editing_highlight', 'title' => $markthistopic));
            }
        }

        $parentcontrols = parent::section_edit_control_items($course, $section, $onsectionpage);

        // If the edit key exists, we are going to insert our controls after it.
        if (array_key_exists("edit", $parentcontrols)) {
            $merged = array();
            // We can't use splice because we are using associative arrays.
            // Step through the array and merge the arrays.
            foreach ($parentcontrols as $key => $action) {
                $merged[$key] = $action;
                if ($key == "edit") {
                    // If we have come to the edit key, merge these controls here.
                    $merged = array_merge($merged, $controls);
                }
            }

            return $merged;
        } else {
            return array_merge($controls, $parentcontrols);
        }
    }

    public function get_week_info(&$course, $tabrange, $week) {
        global $SESSION, $DB;

        $fnmaxtab = $DB->get_field('format_fntabs_config', 'value', array('courseid' => $course->id, 'variable' => 'maxtabs'));

        if ($fnmaxtab) {
            $maximumtabs = $fnmaxtab;
        } else {
            $maximumtabs = 12;
        }
        if ($course->numsections == $maximumtabs) {
            $tablow = 1;
            $tabhigh = $maximumtabs;
        } else if ($tabrange > 1000) {
            $tablow = (int) ($tabrange / 1000);
            $tabhigh = (int) ($tablow + $maximumtabs - 1);
        } else if (($tabrange == 0) && ($week == 0)) {
            $tablow = ((int) ((int) ($course->numsections - 1) / (int) $maximumtabs) * $maximumtabs) + 1;
            $tabhigh = $tablow + $maximumtabs - 1;
        } else if ($tabrange == 0) {
            $tablow = ((int) ((int) $week / (int) $maximumtabs) * $maximumtabs) + 1;
            $tabhigh = $tablow + $maximumtabs - 1;
        } else {
            $tablow = 1;
            $tabhigh = $maximumtabs;
        }
        $tabhigh = min($tabhigh, $course->numsections);

        // Normalize the tabs to always display FNMAXTABS...
        if (($tabhigh - $tablow + 1) < $maximumtabs) {
            $tablow = $tabhigh - $maximumtabs + 1;
        }

        // Save the low and high week in SESSION variables... If they already exist, and the selected
        // week is in their range, leave them as is.
        if (($tabrange >= 1000) || !isset($SESSION->FN_tablow[$course->id]) || !isset($SESSION->FN_tabhigh[$course->id]) ||
            ($week < $SESSION->FN_tablow[$course->id]) || ($week > $SESSION->FN_tabhigh[$course->id])) {
            $SESSION->FN_tablow[$course->id] = $tablow;
            $SESSION->FN_tabhigh[$course->id] = $tabhigh;
        } else {
            $tablow = $SESSION->FN_tablow[$course->id];
            $tabhigh = $SESSION->FN_tabhigh[$course->id];
        }
        $tablow = max($tablow, 1);
        $tabhigh = min($tabhigh, $course->numsections);

        // If selected week in a different set of tabs, move it to the current set...
        if (($week != 0) && ($week < $tablow)) {
            $week = $SESSION->G8_selected_week[$course->id] = $tablow;
        } else if ($week > $tabhigh) {
            $week = $SESSION->G8_selected_week[$course->id] = $tabhigh;
        }
        unset($maximumtabs);
        return array($tablow, $tabhigh, $week);
    }

    /**
     * Prints a section full of activity modules
     */
    public function print_section_fn($course, $section, $mods, $modnamesused, $absolute=false,
                                     $width="100%", $hidecompletion=false) {
        global $USER;

        $sectionreturn = null;
        $displayoptions = array();
        $activitiesstatusarray = format_fntabs_get_activities_status($course, $section);

        $output = '';
        $modinfo = get_fast_modinfo($course);
        if (is_object($section)) {
            $section = $modinfo->get_section_info($section->section);
        } else {
            $section = $modinfo->get_section_info($section);
        }
        $completioninfo = new completion_info($course);

        // Check if we are currently in the process of moving a module with JavaScript disabled.
        $ismoving = $this->page->user_is_editing() && ismoving($course->id);
        if ($ismoving) {
            $movingpix = new pix_icon('movehere', get_string('movehere'), 'moodle', array('class' => 'movetarget'));
            $strmovefull = strip_tags(get_string("movefull", "", "'$USER->activitycopyname'"));
        }

        // Get the list of modules visible to user (excluding the module being moved if there is one).
        $moduleshtml = array();
        if (!empty($modinfo->sections[$section->section])) {
            foreach ($modinfo->sections[$section->section] as $modnumber) {
                $mod = $modinfo->cms[$modnumber];

                if ($ismoving and $mod->id == $USER->activitycopy) {
                    // Do not display moving mod.
                    continue;
                }

                if ($modulehtml = $this->course_section_cm_list_item($course,
                    $completioninfo, $mod, $sectionreturn, $displayoptions, $activitiesstatusarray)) {
                    $moduleshtml[$modnumber] = $modulehtml;
                }
            }
        }

        $sectionoutput = '';
        if (!empty($moduleshtml) || $ismoving) {
            foreach ($moduleshtml as $modnumber => $modulehtml) {
                if ($ismoving) {
                    $movingurl = new moodle_url('/course/mod.php', array('moveto' => $modnumber, 'sesskey' => sesskey()));
                    $sectionoutput .= html_writer::tag('li',
                        html_writer::link($movingurl, $this->output->render($movingpix), array('title' => $strmovefull)),
                        array('class' => 'movehere'));
                }

                $sectionoutput .= $modulehtml;
            }

            if ($ismoving) {
                $movingurl = new moodle_url('/course/mod.php', array('movetosection' => $section->id, 'sesskey' => sesskey()));
                $sectionoutput .= html_writer::tag('li',
                    html_writer::link($movingurl, $this->output->render($movingpix), array('title' => $strmovefull)),
                    array('class' => 'movehere'));
            }
        }

        // Always output the section module list.
        $output .= html_writer::tag('ul', $sectionoutput, array('class' => 'section img-text'));

        return $output;
    }

    /**
     * Renders HTML to display one course module for display within a section.
     *
     * This function calls:
     * {@link core_course_renderer::course_section_cm()}
     *
     * @param stdClass $course
     * @param completion_info $completioninfo
     * @param cm_info $mod
     * @param int|null $sectionreturn
     * @param array $displayoptions
     * @return String
     */
    public function course_section_cm_list_item($course, &$completioninfo, cm_info $mod, $sectionreturn,
                                                $displayoptions = array(), $activitiesstatusarray = null) {
        $output = '';
        if ($modulehtml = $this->course_section_cm($course, $completioninfo, $mod, $sectionreturn, $displayoptions,
            $activitiesstatusarray)) {
            $modclasses = 'activity ' . $mod->modname . ' modtype_' . $mod->modname . ' ' . $mod->extraclasses;
            $output .= html_writer::tag('li', $modulehtml, array('class' => $modclasses, 'id' => 'module-' . $mod->id));
        }
        return $output;
    }

    /**
     * Renders HTML to display one course module in a course section
     *
     * This includes link, content, availability, completion info and additional information
     * that module type wants to display (i.e. number of unread forum posts)
     *
     * This function calls:
     * {@link core_course_renderer::course_section_cm_name()}
     * {@link core_course_renderer::course_section_cm_text()}
     * {@link core_course_renderer::course_section_cm_availability()}
     * {@link core_course_renderer::course_section_cm_completion()}
     * {@link course_get_cm_edit_actions()}
     * {@link core_course_renderer::course_section_cm_edit_actions()}
     *
     * @param stdClass $course
     * @param completion_info $completioninfo
     * @param cm_info $mod
     * @param int|null $sectionreturn
     * @param array $displayoptions
     * @return string
     */
    public function course_section_cm($course, &$completioninfo, cm_info $mod, $sectionreturn,
                                      $displayoptions = array(), $activitiesstatusarray = null) {
        global $DB, $CFG;
        $output = '';

        if (!$mod->uservisible && empty($mod->availableinfo)) {
            return $output;
        }

        $locationoftrackingicons = format_fntabs_get_setting($course->id, 'locationoftrackingicons');
        $activitytrackingbackground = format_fntabs_get_setting($course->id, 'activitytrackingbackground');

        $indentclasses = 'mod-indent';
        if (!empty($mod->indent)) {
            $indentclasses .= ' mod-indent-'.$mod->indent;
            if ($mod->indent > 15) {
                $indentclasses .= ' mod-indent-huge';
            }
        }

        $output .= html_writer::start_tag('div');

        if ($this->page->user_is_editing()) {
            $output .= course_get_cm_move($mod, $sectionreturn);
        }

        $output .= html_writer::start_tag('div', array('class' => 'mod-indent-outer'));

        // This div is used to indent the content.
        $output .= html_writer::div('', $indentclasses);

        // Start a wrapper for the actual content to keep the indentation consistent.
        $output .= html_writer::start_tag('div');

        // Display the link to the module (or do nothing if module has no url).
        $cmname = $this->course_section_cm_name($mod, $displayoptions);

        $modicons = '';
        if ($this->page->user_is_editing()) {
            $editactions = course_get_cm_edit_actions($mod, $mod->indent, $sectionreturn);
            $modicons .= ' '. $this->course_section_cm_edit_actions($editactions, $mod, $displayoptions);
            $modicons .= $mod->afterediticons;
        }

        $modicons .= $this->course_section_cm_completion($course, $completioninfo, $mod, $displayoptions, $activitiesstatusarray);
        $completionstatus = '';

        if ($activitytrackingbackground) {
            $completionstatus = 'completion-notset';
            if (!empty($modicons)) {
                $doc = new DOMDocument();
                @$doc->loadHTML($modicons);
                $tags = $doc->getElementsByTagName('img');
                if ($tags->length == 0) {
                    $tags = $doc->getElementsByTagName('input');
                }
                foreach ($tags as $tag) {
                    if ($iconurl = $tag->getAttribute('src')) {
                        $spliturl = explode('/', $iconurl);
                        $spliturl = end($spliturl);
                        if (substr($spliturl, 0, 11) !== "completion-") {
                            $spliturl = explode('=', $iconurl);
                            $spliturl = end($spliturl);
                        }
                        $completionstatus = $spliturl;
                        break;
                    }
                }
            }
        }

        if (($locationoftrackingicons == 'nediconsleft') && (!$this->page->user_is_editing())) {
            if (!empty($modicons)) {
                if ($activitytrackingbackground) {
                    $output .= html_writer::span($modicons, 'actionslefttrackingbackground');
                } else {
                    $output .= html_writer::span($modicons, 'actionsleft');
                }
            }
        }

        if (!empty($cmname)) {
            // Start the div for the activity title, excluding the edit icons.
            $output .= html_writer::start_tag('div', array('class' => 'activityinstance '.$completionstatus));
            $output .= $cmname;

            if ($this->page->user_is_editing()) {
                $version = explode('.', $CFG->version);
                $version = reset($version);
                if ($version >= 2016051300) { // Moodle 3.1.
                    $output .= ' ' . format_fntabs_course_get_cm_rename_action($mod, $sectionreturn);
                } else {
                    $output .= ' ' . course_get_cm_rename_action($mod, $sectionreturn);
                }

            }

            // Module can put text after the link (e.g. forum unread).
            $output .= $mod->afterlink;

            // Closing the tag which contains everything but edit icons. Content part of the module should not be part of this.
            $output .= html_writer::end_tag('div');
        }

        // If there is content but NO link (eg label), then display the
        // content here (BEFORE any icons). In this case cons must be
        // displayed after the content so that it makes more sense visually
        // and for accessibility reasons, e.g. if you have a one-line label
        // it should work similarly (at least in terms of ordering) to an
        // activity.
        $contentpart = $this->course_section_cm_text($mod, $displayoptions);
        $url = $mod->url;
        if (empty($url)) {
            $output .= $contentpart;
        }
        if (($locationoftrackingicons == 'moodleicons') || ($locationoftrackingicons == 'nediconsright')
            || ($this->page->user_is_editing())) {
            if (!empty($modicons)) {
                $output .= html_writer::span($modicons, 'actions');
            }
        }

        // If there is content AND a link, then display the content here
        // (AFTER any icons). Otherwise it was displayed before.
        if (!empty($url)) {
            $output .= $contentpart;
        }

        // Show availability info (if module is not available).
        $output .= $this->course_section_cm_availability($mod, $displayoptions);

        $output .= html_writer::end_tag('div');

        // End of indentation div.
        $output .= html_writer::end_tag('div');

        $output .= html_writer::end_tag('div');
        return $output;
    }

    /**
     * Renders html to display a name with the link to the course module on a course page
     *
     * If module is unavailable for user but still needs to be displayed
     * in the list, just the name is returned without a link
     *
     * Note, that for course modules that never have separate pages (i.e. labels)
     * this function return an empty string
     *
     * @param cm_info $mod
     * @param array $displayoptions
     * @return string
     */
    public function course_section_cm_name(cm_info $mod, $displayoptions = array()) {
        global $CFG;
        $output = '';
        if (!$mod->uservisible && empty($mod->availableinfo)) {
            // Nothing to be displayed to the user.
            return $output;
        }
        $url = $mod->url;
        if (!$url) {
            return $output;
        }

        // Accessibility: for files get description via icon, this is very ugly hack!
        $instancename = $mod->get_formatted_name();
        $altname = $mod->modfullname;
        // Avoid unnecessary duplication: if e.g. a forum name already
        // includes the word forum (or Forum, etc) then it is unhelpful
        // to include that in the accessible description that is added.
        if (false !== strpos(core_text::strtolower($instancename),
                core_text::strtolower($altname))) {
            $altname = '';
        }
        // File type after name, for alphabetic lists (screen reader).
        if ($altname) {
            $altname = get_accesshide(' '.$altname);
        }

        // For items which are hidden but available to current user
        // ($mod->uservisible), we show those as dimmed only if the user has
        // viewhiddenactivities, so that teachers see 'items which might not
        // be available to some students' dimmed but students do not see 'item
        // which is actually available to current student' dimmed.
        $linkclasses = '';
        $accesstext = '';
        $textclasses = '';
        if ($mod->uservisible) {
            $conditionalhidden = $this->is_cm_conditionally_hidden($mod);
            $accessiblebutdim = (!$mod->visible || $conditionalhidden) &&
                has_capability('moodle/course:viewhiddenactivities', $mod->context);
            if ($accessiblebutdim) {
                $linkclasses .= ' dimmed';
                $textclasses .= ' dimmed_text';
                if ($conditionalhidden) {
                    $linkclasses .= ' conditionalhidden';
                    $textclasses .= ' conditionalhidden';
                }
                // Show accessibility note only if user can access the module himself.
                $accesstext = get_accesshide(get_string('hiddenfromstudents').':'. $mod->modfullname);
            }
        } else {
            $linkclasses .= ' dimmed';
            $textclasses .= ' dimmed_text';
        }

        // Get on-click attribute value if specified and decode the onclick - it
        // has already been encoded for display (puke).
        $onclick = htmlspecialchars_decode($mod->onclick, ENT_QUOTES);

        $groupinglabel = $mod->get_grouping_label($textclasses);

        // Display link itself.
        $activitylink = html_writer::empty_tag('img', array('src' => $mod->get_icon_url(),
                'class' => 'iconlarge activityicon', 'alt' => ' ', 'role' => 'presentation')) . $accesstext .
            html_writer::tag('span', $instancename . $altname, array('class' => 'instancename'));
        if ($mod->uservisible) {
            $output .= html_writer::link($url, $activitylink, array('class' => $linkclasses, 'onclick' => $onclick)) .
                $groupinglabel;
        } else {
            // We may be displaying this just in order to show information
            // about visibility, without the actual link ($mod->uservisible).
            $output .= html_writer::tag('div', $activitylink, array('class' => $textclasses)) .
                $groupinglabel;
        }
        return $output;
    }

    /**
     * Checks if course module has any conditions that may make it unavailable for
     * all or some of the students
     *
     * This function is internal and is only used to create CSS classes for the module name/text
     *
     * @param cm_info $mod
     * @return bool
     */
    protected function is_cm_conditionally_hidden(cm_info $mod) {
        global $CFG;
        $conditionalhidden = false;
        if (!empty($CFG->enableavailability)) {
            $info = new \core_availability\info_module($mod);
            $conditionalhidden = !$info->is_available_for_all();
        }
        return $conditionalhidden;
    }

    /**
     * Renders html to display the module content on the course page (i.e. text of the labels)
     *
     * @param cm_info $mod
     * @param array $displayoptions
     * @return string
     */
    public function course_section_cm_text(cm_info $mod, $displayoptions = array()) {
        $output = '';
        if (!$mod->uservisible && empty($mod->availableinfo)) {
            // Nothing to be displayed to the user.
            return $output;
        }
        $content = $mod->get_formatted_content(array('overflowdiv' => true, 'noclean' => true));
        $accesstext = '';
        $textclasses = '';
        if ($mod->uservisible) {
            $conditionalhidden = $this->is_cm_conditionally_hidden($mod);
            $accessiblebutdim = (!$mod->visible || $conditionalhidden) &&
                has_capability('moodle/course:viewhiddenactivities', $mod->context);
            if ($accessiblebutdim) {
                $textclasses .= ' dimmed_text';
                if ($conditionalhidden) {
                    $textclasses .= ' conditionalhidden';
                }
                // Show accessibility note only if user can access the module himself.
                $accesstext = get_accesshide(get_string('hiddenfromstudents').':'. $mod->modfullname);
            }
        } else {
            $textclasses .= ' dimmed_text';
        }
        if ($mod->url) {
            if ($content) {
                // If specified, display extra content after link.
                $output = html_writer::tag('div', $content, array('class' => trim('contentafterlink ' . $textclasses)));
            }
        } else {
            $groupinglabel = $mod->get_grouping_label($textclasses);

            // No link, so display only content.
            $output = html_writer::tag('div', $accesstext . $content . $groupinglabel,
                array('class' => 'contentwithoutlink ' . $textclasses));
        }
        return $output;
    }

    /**
     * Renders HTML for displaying the sequence of course module editing buttons
     *
     * @see course_get_cm_edit_actions()
     *
     * @param action_link[] $actions Array of action_link objects
     * @param cm_info $mod The module we are displaying actions for.
     * @param array $displayoptions additional display options:
     *     ownerselector => A JS/CSS selector that can be used to find an cm node.
     *         If specified the owning node will be given the class 'action-menu-shown' when the action
     *         menu is being displayed.
     *     constraintselector => A JS/CSS selector that can be used to find the parent node for which to constrain
     *         the action menu to when it is being displayed.
     *     donotenhance => If set to true the action menu that gets displayed won't be enhanced by JS.
     * @return string
     */
    public function course_section_cm_edit_actions($actions, cm_info $mod = null, $displayoptions = array()) {
        global $CFG;

        if (empty($actions)) {
            return '';
        }

        if (isset($displayoptions['ownerselector'])) {
            $ownerselector = $displayoptions['ownerselector'];
        } else if ($mod) {
            $ownerselector = '#module-'.$mod->id;
        } else {
            debugging('You should upgrade your call to '.__FUNCTION__.' and provide $mod', DEBUG_DEVELOPER);
            $ownerselector = 'li.activity';
        }

        if (isset($displayoptions['constraintselector'])) {
            $constraint = $displayoptions['constraintselector'];
        } else {
            $constraint = '.course-content';
        }

        $menu = new action_menu();
        $menu->set_owner_selector($ownerselector);
        $menu->set_constraint($constraint);
        $menu->set_alignment(action_menu::TR, action_menu::BR);
        $menu->set_menu_trigger(get_string('edit'));
        if (isset($CFG->modeditingmenu) && !$CFG->modeditingmenu || !empty($displayoptions['donotenhance'])) {
            $menu->do_not_enhance();

            // Swap the left/right icons.
            // Normally we have have right, then left but this does not
            // make sense when modactionmenu is disabled.
            $moveright = null;
            $tempactions = array();
            foreach ($actions as $key => $value) {
                if ($key === 'moveright') {

                    // Save moveright for later.
                    $moveright = $value;
                } else if ($moveright) {

                    // This assumes that the order was moveright, moveleft.
                    // If we have a moveright, then we should place it immediately after the current value.
                    $tempactions[$key] = $value;
                    $tempactions['moveright'] = $moveright;

                    // Clear the value to prevent it being used multiple times.
                    $moveright = null;
                } else {

                    $tempactions[$key] = $value;
                }
            }
            $actions = $tempactions;
            unset($tempactions);
        }
        foreach ($actions as $action) {
            if ($action instanceof action_menu_link) {
                $action->add_class('cm-edit-action');
            }
            $menu->add($action);
        }
        $menu->attributes['class'] .= ' section-cm-edit-actions commands';

        // Prioritise the menu ahead of all other actions.
        $menu->prioritise = true;

        return $this->render($menu);
    }

    /**
     * Renders html for completion box on course page
     *
     * If completion is disabled, returns empty string
     * If completion is automatic, returns an icon of the current completion state
     * If completion is manual, returns a form (with an icon inside) that allows user to
     * toggle completion
     *
     * @param stdClass $course course object
     * @param completion_info $completioninfo completion info for the course, it is recommended
     *     to fetch once for all modules in course/section for performance
     * @param cm_info $mod module to show completion for
     * @param array $displayoptions display options, not used in core
     * @return string
     */
    public function course_section_cm_completion($course, &$completioninfo, cm_info $mod, $displayoptions = array(),
                                                 $activitiesstatusarray = null) {
        global $CFG, $DB;

        $locationoftrackingicons = format_fntabs_get_setting($course->id, 'locationoftrackingicons');

        $output = '';
        if (!empty($displayoptions['hidecompletion']) || !isloggedin() || isguestuser() || !$mod->uservisible) {
            return $output;
        }
        if ($completioninfo === null) {
            $completioninfo = new completion_info($course);
        }
        $completion = $completioninfo->is_enabled($mod);
        if ($completion == COMPLETION_TRACKING_NONE) {
            if ($this->page->user_is_editing()) {
                $output .= html_writer::span('&nbsp;', 'filler');
            }
            return $output;
        }

        $completiondata = $completioninfo->get_data($mod, true);
        $completionicon = '';
        $usenedicons = false;

        if (($locationoftrackingicons == 'nediconsleft') || ($locationoftrackingicons == 'nediconsright')) {
            $usenedicons = true;
        }

        if ($this->page->user_is_editing()) {
            switch ($completion) {
                case COMPLETION_TRACKING_MANUAL :
                    $completionicon = 'manual-enabled';
                    break;
                case COMPLETION_TRACKING_AUTOMATIC :
                    $completionicon = 'auto-enabled';
                    break;
            }
        } else if ($completion == COMPLETION_TRACKING_MANUAL) {
            switch($completiondata->completionstate) {
                case COMPLETION_INCOMPLETE:
                    $completionicon = 'manual-n';
                    break;
                case COMPLETION_COMPLETE:
                    $completionicon = 'manual-y';
                    break;
            }
        } else { // Automatic.
            switch($completiondata->completionstate) {
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
            }
        }

        if (isset($activitiesstatusarray['modules'][$mod->id]) && $usenedicons) {
            if ($activitiesstatusarray['modules'][$mod->id] == 'waitingforgrade') {
                $completionicon = 'submitted';
            } else if ($activitiesstatusarray['modules'][$mod->id] == 'saved') {
                $completionicon = 'saved';
            }
        }

        if ($completionicon) {
            $formattedname = $mod->get_formatted_name();
            if ($completionicon == 'saved' || $completionicon == 'submitted') {
                $imgalt = get_string('completion-alt-' . $completionicon, 'format_fntabs', $formattedname);
            } else {
                $imgalt = get_string('completion-alt-' . $completionicon, 'completion', $formattedname);
            }

            if ($this->page->user_is_editing()) {
                // When editing, the icon is just an image.
                $completionpixicon = new pix_icon('i/completion-'.$completionicon, $imgalt, '',
                    array('title' => $imgalt, 'class' => 'iconsmall'));
                $output .= html_writer::tag('span', $this->output->render($completionpixicon),
                    array('class' => 'autocompletion'));
            } else if ($completion == COMPLETION_TRACKING_MANUAL) {
                $imgtitle = get_string('completion-title-' . $completionicon, 'completion', $formattedname);
                $newstate = $completiondata->completionstate == COMPLETION_COMPLETE ? COMPLETION_INCOMPLETE : COMPLETION_COMPLETE;
                // In manual mode the icon is a toggle form...

                // If this completion state is used by the
                // conditional activities system, we need to turn
                // off the JS.
                $extraclass = '';
                if (!empty($CFG->enableavailability) &&
                    core_availability\info::completion_value_used($course, $mod->id)) {
                    $extraclass = ' preventjs';
                }
                $output .= html_writer::start_tag('form', array('method' => 'post',
                    'action' => new moodle_url('/course/togglecompletion.php'),
                    'class' => 'togglecompletion'. $extraclass));
                $output .= html_writer::start_tag('div');
                $output .= html_writer::empty_tag('input', array(
                    'type' => 'hidden', 'name' => 'id', 'value' => $mod->id));
                $output .= html_writer::empty_tag('input', array(
                    'type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()));
                $output .= html_writer::empty_tag('input', array(
                    'type' => 'hidden', 'name' => 'modulename', 'value' => $mod->name));
                $output .= html_writer::empty_tag('input', array(
                    'type' => 'hidden', 'name' => 'completionstate', 'value' => $newstate));
                if ($usenedicons) {
                    $output .= html_writer::empty_tag('input', array(
                        'type' => 'image',
                        'src' => $this->output->pix_url('completion-' . $completionicon, 'format_fntabs'),
                        'alt' => $imgalt, 'title' => $imgtitle,
                        'aria-live' => 'polite'));
                } else {
                    $output .= html_writer::empty_tag('input', array(
                        'type' => 'image',
                        'src' => $this->output->pix_url('i/completion-' . $completionicon),
                        'alt' => $imgalt, 'title' => $imgtitle,
                        'aria-live' => 'polite'));
                }
                $output .= html_writer::end_tag('div');
                $output .= html_writer::end_tag('form');
            } else {
                // In auto mode, the icon is just an image.
                if ($usenedicons) {
                    $completionpixicon = new pix_icon('completion-'.$completionicon, $imgalt, 'format_fntabs',
                        array('title' => $imgalt));
                } else {
                    $completionpixicon = new pix_icon('i/completion-'.$completionicon, $imgalt, '',
                        array('title' => $imgalt));
                }
                $output .= html_writer::tag('span', $this->output->render($completionpixicon),
                    array('class' => 'autocompletion'));
            }
        }
        return $output;
    }

    /**
     * Renders HTML to show course module availability information (for someone who isn't allowed
     * to see the activity itself, or for staff)
     *
     * @param cm_info $mod
     * @param array $displayoptions
     * @return string
     */
    public function course_section_cm_availability(cm_info $mod, $displayoptions = array()) {
        global $CFG;
        if (!$mod->uservisible) {
            // This is a student who is not allowed to see the module but might be allowed
            // to see availability info (i.e. "Available from ...").
            if (!empty($mod->availableinfo)) {
                $formattedinfo = \core_availability\info::format_info(
                    $mod->availableinfo, $mod->get_course());
                $output = html_writer::tag('div', $formattedinfo, array('class' => 'availabilityinfo'));
            }
            return $output;
        }
        // This is a teacher who is allowed to see module but still should see the
        // information that module is not available to all/some students.
        $modcontext = context_module::instance($mod->id);
        $canviewhidden = has_capability('moodle/course:viewhiddenactivities', $modcontext);
        if ($canviewhidden && !empty($CFG->enableavailability)) {
            // Don't add availability information if user is not editing and activity is hidden.
            if ($mod->visible || $this->page->user_is_editing()) {
                $hidinfoclass = '';
                if (!$mod->visible) {
                    $hidinfoclass = 'hide';
                }
                $ci = new \core_availability\info_module($mod);
                $fullinfo = $ci->get_full_information();
                if ($fullinfo) {
                    $formattedinfo = \core_availability\info::format_info(
                        $fullinfo, $mod->get_course());
                    return html_writer::div($formattedinfo, 'availabilityinfo ' . $hidinfoclass);
                }
            }
        }
        return '';
    }

    /**
     * If used, this will just call the library function (for now). Replace this with your own to make it
     * do what you want.
     *
     */
    public function print_section_add_menus($course, $section, $modnames, $vertical=false, $return=false) {
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


    /** Custom functions * */
    public function handle_extra_actions() {
        global $DB;

        if (isset($_POST['sec0title'])) {
            if (!$course = $DB->get_record('course', array('id' => $_POST['id']))) {
                print_error('This course doesn\'t exist.');
            }
            format_fntabs_get_course($course);
            $course->sec0title = $_POST['sec0title'];
            format_fntabs_update_course($course);
            $cm->course = $course->id;
        }
    }


    public function print_weekly_activities_bar($course, $week=0, $tabrange=0) {
        global $FULLME, $CFG, $course, $DB, $USER, $PAGE, $OUTPUT;

        $selectedcolour = $DB->get_field('format_fntabs_config', 'value',
            array('courseid' => $course->id, 'variable' => 'selectedcolour')
        );
        $activelinkcolour = $DB->get_field('format_fntabs_config', 'value',
            array('courseid' => $course->id, 'variable' => 'activelinkcolour')
        );
        $activecolour = $DB->get_field('format_fntabs_config', 'value',
            array('courseid' => $course->id, 'variable' => 'activecolour')
        );

        $inactivelinkcolour = $DB->get_field('format_fntabs_config', 'value',
            array('courseid' => $course->id, 'variable' => 'inactivelinkcolour')
        );
        $inactivecolour = $DB->get_field('format_fntabs_config', 'value',
            array('courseid' => $course->id, 'variable' => 'inactivecolour')
        );

        $tabcontent = format_fntabs_get_setting($course->id, 'tabcontent');
        $completiontracking = format_fntabs_get_setting($course->id, 'completiontracking');
        $tabwidth = format_fntabs_get_setting($course->id, 'tabwidth');

        $selectedcolour     = $selectedcolour ? $selectedcolour : 'FFFF33';
        $activelinkcolour   = $activelinkcolour ? $activelinkcolour : '000000';
        $inactivelinkcolour = $inactivelinkcolour ? $inactivelinkcolour : '000000';
        $activecolour       = $activecolour ? $activecolour : 'DBE6C4';
        $inactivecolour     = $inactivecolour ? $inactivecolour : 'BDBBBB';

        $fnmaxtab = $DB->get_field('format_fntabs_config', 'value',
            array('courseid' => $course->id, 'variable' => 'maxtabs'));

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
        .fnweeklynavnorm a {
          color: #$activelinkcolour;
        }
        </style>";

        $completioninfo = new completion_info($course);

        list($tablow, $tabhigh, $week) = $this->get_week_info($course, $tabrange, $week);

        $section = optional_param('section', 0, PARAM_INT);
        if ($section > $tabhigh) {
            $tabhigh = $section;
            $week = $section;
        }

        $timenow = time();
        $weekdate = $course->startdate;    // This should be 0:00 Monday of that week.
        $weekdate += 7200;                 // Add two hours to avoid possible DST problems.
        $weekofseconds = 604800;

        if ($tabwidth == 'equalspacing') {
            if ($course->numsections > 20) {
                $extraclassfortab = "tab-greaterthan5";
            } else {
                $extraclassfortab = "tab-lessthan5";
            }
        } else {
            $extraclassfortab = '';
        }

        if (isset($course->topicheading) && !empty($course->topicheading)) {
            $strtopicheading = $course->topicheading;
        } else {
            $strtopicheading = '';
        }
        $context = context_course::instance($course->id);
        $isteacher = has_capability('moodle/course:update', $context);
        $iseditingteacher = has_capability('gradereport/grader:view', $context);
        $url = preg_replace('/(^.*)(&selected_week\=\d+)(.*)/', '$1$3', $FULLME);
        $url = preg_replace('/(^.*)(&section\=\d+)(.*)/', '$1$3', $url);

        $actbar = '';
        $actbar .= '<table cellpadding="0" cellspacing="0" class="fntabwrapper"><tr><td>';
        $actbar .= '<table cellpadding="0" cellspacing="0"  class="fnweeklynav"><tr class="tabs">';
        $width = (int) (100 / ($tabhigh - $tablow + 3));
        $actbar .= '<td width="4" align="center" height="25"></td>';

        if ($tablow <= 1) {
            if ($strtopicheading) {
                $actbar .= '<td height="25" class="tab-heading"><strong>' . $strtopicheading . ':&nbsp;</strong></td>';
            }
        } else {
            $prv = ($tablow - $maximumtabs) * 1000;
            if ($prv < 0) {
                $prv = 1000;
            }
            $actbar .= '<td id="fn_tab_previous" height="25"><a href="' . $url . '&selected_week=' . $prv . '">Previous</a></td>';
        }

        $tdselectedclass = array();

        $currentweek = ($timenow > $course->startdate) ? (int) ((($timenow - $course->startdate) / $weekofseconds) + 1) : 0;

        $currentweek = min($currentweek, $course->numsections);

        if ($numofsections = count($this->sections)) {
            if ($numofsections < $tabhigh) {
                $tabhigh = $numofsections;
            }
            for ($i = $tablow; $i <= $tabhigh; $i++) {
                if ($this->sections[$i]->name) {
                    $sectionname = $this->sections[$i]->name;
                } else {
                    if ($course->topicheading) {
                        $sectionname = $course->topicheading . ' ' . $this->sections[$i]->section;
                    } else {
                        $sectionname = $this->sections[$i]->section;
                    }
                }

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
                    if ($tabcontent == 'usesectionnumbers') {
                        $actbar .= '<td class="' . $css . ' ' . $extraclassfortab .
                            '" height="25" width="" alt="Upcoming sections" title="Upcoming sections">' . $f . '</td>';
                    } else if ($tabcontent == 'usesectiontitles') {
                        $actbar .= '<td class="' . $css . ' ' . $extraclassfortab .
                            '" height="25" width="" alt="Upcoming sections" title="Upcoming sections">' .
                            $sectionname . '</td>';
                    }
                } else if ($i == $week) {
                    if (!$isteacher && !is_siteadmin() && !empty($completioninfo) && !$iseditingteacher) {
                        if ($completioninfo->is_enabled() && $CFG->enablecompletion && $completiontracking) {
                            $f = $this->is_section_finished($this->sections[$i], $this->mods) ? 'green-tab' : 'red-tab';
                        } else {
                            $f = '';
                        }
                    } else {
                        $f = '';
                    }
                    $tdselectedclass[$i] = 'fnweeklynavselected';
                    if ($tabcontent == 'usesectionnumbers') {
                        $actbar .= '<td class="fnweeklynavselected ' . $f . ' ' . $extraclassfortab .
                            '" id=fnweeklynav' . $i . ' width="" height="25"> ' . $i . ' </td>';
                    } else if ($tabcontent == 'usesectiontitles') {
                        $actbar .= '<td class="fnweeklynavselected ' . $f . ' ' . $extraclassfortab .
                            '" id=fnweeklynav' . $i . ' width="" height="25"> ' . $sectionname . ' </td>';
                    }
                } else {
                    if (!$isteacher && !is_siteadmin() && !$iseditingteacher) {
                        if ($completioninfo->is_enabled() && $CFG->enablecompletion && $completiontracking) {
                            $f = $this->is_section_finished($this->sections[$i], $this->mods) ? 'green-tab' : 'red-tab';
                            $w = $i;
                            $sectionid = $i;
                            $section = $DB->get_record("course_sections", array("section" => $sectionid, "course" => $course->id));
                            $activitiesstatusarray = format_fntabs_get_activities_status($course, $section);
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
                    if ($tabcontent == 'usesectionnumbers') {
                        $actbar .= '<td class="fnweeklynavnorm ' . $f . ' ' . $extraclassfortab .
                            '" id=fnweeklynav' . $i . ' width="" height="25"><a class="tooltip' . $tooltipclass .
                            '" href="' . $url . '&selected_week=' . $i . '"><div>' . $i . '</div>';
                    } else if ($tabcontent == 'usesectiontitles') {
                        $actbar .= '<td class="fnweeklynavnorm ' . $f . ' ' . $extraclassfortab .
                            '" id=fnweeklynav' . $i . ' width="" height="25"><a class="tooltip' . $tooltipclass .
                            '" href="' . $url . '&selected_week=' . $i . '"><div>' . $sectionname . '</div>';
                    }
                    if (!$isteacher && !is_siteadmin()
                        && !is_primary_admin($USER->id)
                        && !$iseditingteacher
                        && $CFG->enablecompletion
                        && $completioninfo->is_enabled()
                        && $completiontracking
                    ) {
                        $actbar .= '<span class="custom info">
                            <ul>
                                <li class="not-attp"><img src="' . $CFG->wwwroot . '/course/format/' .
                            $course->format . '/pix/completion-auto-n.gif" /> ' . $notattemptd . '</li>
                                <li class="grade-wait"><img src="' . $CFG->wwwroot . '/course/format/' .
                            $course->format . '/pix/unmarked.gif" /> ' . $waitforgrade . '</li>
                                <li class="complete"><img src="' . $CFG->wwwroot . '/course/format/' .
                            $course->format . '/pix/completed.gif" /> ' . $compl . '</li>
                                <li class="in-complete"><img src="' . $CFG->wwwroot . '/course/format/' .
                            $course->format . '/pix/incomplete.gif" /> ' . $incompl . '</li>
                                <li class="saved"><img src="' . $CFG->wwwroot . '/course/format/' .
                            $course->format . '/pix/saved.gif" /> ' . $svd . '</li>
                            </ul>
                            <img class="arrows" src="' . $CFG->wwwroot . '/course/format/' .
                            $course->format . '/pix/t-arrow-grey.gif" alt="Information" height="20" width="24" />
                        </span>';
                    }
                    $actbar .= '</a>' . '</td>';
                }

                $actbar .= '<td align="center" height="25" style="width: 2px;">' .
                    '<img src="' . $CFG->wwwroot . '/pix/spacer.gif" height="1" width="1" alt="" /></td>';
            }
        }

        if (($week == 0) && ($tabhigh >= $course->numsections)) {
            $actbar .= '<td class="fnweeklynavselected ' . $extraclassfortab . '"  width="" height="25">All</td>';
        } else if ($tabhigh >= $course->numsections) {
            $actbar .= '<td class="fnweeklynavnorm ' . $extraclassfortab . '" width="" height="25">' .
                '<a href="' . $url . '&selected_week=0">All</a></td>';
        } else {
            $nxt = ($tabhigh + 1) * 1000;
            $actbar .= '<td id="fn_tab_next" height="25"><a href="' . $url . '&selected_week=' . $nxt . '">Next</a></td>';
        }
        $settingicon = '';
        if ($PAGE->user_is_editing() && has_capability('moodle/course:update', $context)) {
            $settingicon = '<a href="' . $CFG->wwwroot . '/course/format/' . $course->format .
                '/tabsettings.php?id='.$course->id.'" ><img style="margin: 3px 1px 1px 5px;" src="'.
                $OUTPUT->pix_url('t/edit').'" width="16" /></a>';
        }
        $actbar .= '<td width="1" align="center" height="25">'.$settingicon.'</td>';
        $actbar .= '</tr>';
        $actbar .= '<tr>';
        if ($strtopicheading) {
            $actbar .= '<td height="3" colspan="2"></td>';
        } else {
            $actbar .= '<td height="3"></td>';
        }

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
        rebuild_course_cache($course->id);
        unset($maximumtabs);

        return $actbar;
    }

    public function print_section_fn_($course, $section, $mods, $modnamesused, $absolute=false, $width="100%",
                                      $hidecompletion=false, $sectionreturn=null) {
        global $PAGE;
        $displayoptions = array('hidecompletion' => $hidecompletion);
        $courserenderer = $PAGE->get_renderer('core', 'course');
        echo $courserenderer->course_section_cm_list($course, $section, $sectionreturn, $displayoptions);
    }

    public function is_section_finished(&$section, $mods) {
        global $USER, $course;
        $completioninfo = new completion_info($course);
        $modules = format_fntabs_get_course_section_mods($course->id, $section->id);
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

    public function first_unfinished_section() {
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
