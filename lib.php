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
define('FN_EXTRASECTION', 9999); // A non-existant section to hold hidden modules.
require_once($CFG->dirroot.'/course/format/lib.php');
require_once($CFG->dirroot.'/course/lib.php');

/**
 * Main class for the Topics course format
 *
 * @package    format_fntabs
 * @copyright  2012 Marina Glancy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class format_fntabs extends format_base {

    /**
     * Returns true if this course format uses sections
     *
     * @return bool
     */
    public function uses_sections() {
        return true;
    }

    /**
     * Returns the display name of the given section that the course prefers.
     *
     * Use section name is specified by user. Otherwise use default ("Topic #")
     *
     * @param int|stdClass $section Section object from database or just field section.section
     * @return string Display name that the course format prefers, e.g. "Topic 2"
     */
    public function get_section_name($section) {
        $section = $this->get_section($section);
        if ((string)$section->name !== '') {
            return format_string($section->name, true,
                array('context' => context_course::instance($this->courseid)));
        } else {
            return parent::get_section_name($section);
        }
    }

    /**
     * Returns the default section name for the topics course format.
     *
     * If the section number is 0, it will use the string with key = section0name from the course format's lang file.
     * If the section number is not 0, the base implementation of format_base::get_default_section_name which uses
     * the string with the key = 'sectionname' from the course format's lang file + the section number will be used.
     *
     * @param stdClass $section Section object from database or just field course_sections section
     * @return string The default value for the section name.
     */
    public function get_default_section_name($section) {
        if ($section->section == 0) {
            // Return the general section.
            return get_string('section0name', 'format_fntabs');
        } else {
            // Use format_base::get_default_section_name implementation which
            // will display the section name in "Topic n" format.
            return parent::get_default_section_name($section);
        }
    }

    /**
     * The URL to use for the specified course (with section)
     *
     * @param int|stdClass $section Section object from database or just field course_sections.section
     *     if omitted the course view page is returned
     * @param array $options options for view URL. At the moment core uses:
     *     'navigation' (bool) if true and section has no separate page, the function returns null
     *     'sr' (int) used by multipage formats to specify to which section to return
     * @return null|moodle_url
     */
    public function get_view_url($section, $options = array()) {
        global $CFG;
        $course = $this->get_course();
        $url = new moodle_url('/course/view.php', array('id' => $course->id));

        $sr = null;
        if (array_key_exists('sr', $options)) {
            $sr = $options['sr'];
        }
        if (is_object($section)) {
            $sectionno = $section->section;
        } else {
            $sectionno = $section;
        }
        if ($sectionno !== null) {
            if ($sr !== null) {
                if ($sr) {
                    $usercoursedisplay = COURSE_DISPLAY_MULTIPAGE;
                    $sectionno = $sr;
                } else {
                    $usercoursedisplay = COURSE_DISPLAY_SINGLEPAGE;
                }
            } else {
                $usercoursedisplay = COURSE_DISPLAY_MULTIPAGE;
            }
            if ($sectionno != 0 && $usercoursedisplay == COURSE_DISPLAY_MULTIPAGE) {
                $url->param('section', $sectionno);
            } else {
                if (empty($CFG->linkcoursesections) && !empty($options['navigation'])) {
                    return null;
                }
                $url->set_anchor('section-'.$sectionno);
            }
        }
        return $url;
    }

    /**
     * Returns the information about the ajax support in the given source format
     *
     * The returned object's property (boolean)capable indicates that
     * the course format supports Moodle course ajax features.
     *
     * @return stdClass
     */
    public function supports_ajax() {
        $ajaxsupport = new stdClass();
        $ajaxsupport->capable = true;
        return $ajaxsupport;
    }

    /**
     * Loads all of the course sections into the navigation
     *
     * @param global_navigation $navigation
     * @param navigation_node $node The course node within the navigation
     */
    public function extend_course_navigation($navigation, navigation_node $node) {
        global $PAGE;
        // If section is specified in course/view.php, make sure it is expanded in navigation.
        if ($navigation->includesectionnum === false) {
            $selectedsection = optional_param('section', null, PARAM_INT);
            if ($selectedsection !== null && (!defined('AJAX_SCRIPT') || AJAX_SCRIPT == '0') &&
                $PAGE->url->compare(new moodle_url('/course/view.php'), URL_MATCH_BASE)) {
                $navigation->includesectionnum = $selectedsection;
            }
        }

        // Check if there are callbacks to extend course navigation.
        parent::extend_course_navigation($navigation, $node);

        // We want to remove the general section if it is empty.
        $modinfo = get_fast_modinfo($this->get_course());
        $sections = $modinfo->get_sections();
        if (!isset($sections[0])) {
            // The general section is empty to find the navigation node for it we need to get its ID.
            $section = $modinfo->get_section_info(0);
            $generalsection = $node->get($section->id, navigation_node::TYPE_SECTION);
            if ($generalsection) {
                // We found the node - now remove it.
                $generalsection->remove();
            }
        }
    }

    /**
     * Custom action after section has been moved in AJAX mode
     *
     * Used in course/rest.php
     *
     * @return array This will be passed in ajax respose
     */
    public function ajax_section_move() {
        global $PAGE;
        $titles = array();
        $course = $this->get_course();
        $modinfo = get_fast_modinfo($course);
        $renderer = $this->get_renderer($PAGE);
        if ($renderer && ($sections = $modinfo->get_section_info_all())) {
            foreach ($sections as $number => $section) {
                $titles[$number] = $renderer->section_title($section, $course);
            }
        }
        return array('sectiontitles' => $titles, 'action' => 'move');
    }

    /**
     * Returns the list of blocks to be automatically added for the newly created course
     *
     * @return array of default blocks, must contain two keys BLOCK_POS_LEFT and BLOCK_POS_RIGHT
     *     each of values is an array of block names (for left and right side columns)
     */
    public function get_default_blocks() {
        return array(
            BLOCK_POS_LEFT => array(),
            BLOCK_POS_RIGHT => array('search_forums', 'news_items', 'calendar_upcoming', 'recent_activity')
        );
    }

    /**
     * Definitions of the additional options that this course format uses for course
     *
     * Topics format uses the following options:
     * - coursedisplay
     * - numsections
     * - hiddensections
     *
     * @param bool $foreditform
     * @return array of options
     */
    public function course_format_options($foreditform = false) {
        static $courseformatoptions = false;
        if ($courseformatoptions === false) {
            $courseconfig = get_config('moodlecourse');
            $courseformatoptions = array(
                'numsections' => array(
                    'default' => $courseconfig->numsections,
                    'type' => PARAM_INT,
                ),
                'hiddensections' => array(
                    'default' => $courseconfig->hiddensections,
                    'type' => PARAM_INT,
                ),
            );
        }
        if ($foreditform && !isset($courseformatoptions['coursedisplay']['label'])) {
            $courseconfig = get_config('moodlecourse');
            $max = $courseconfig->maxsections;
            if (!isset($max) || !is_numeric($max)) {
                $max = 52;
            }
            $sectionmenu = array();
            for ($i = 0; $i <= $max; $i++) {
                $sectionmenu[$i] = "$i";
            }
            $courseformatoptionsedit = array(
                'numsections' => array(
                    'label' => new lang_string('numberweeks'),
                    'element_type' => 'select',
                    'element_attributes' => array($sectionmenu),
                ),
                'hiddensections' => array(
                    'label' => new lang_string('hiddensections'),
                    'help' => 'hiddensections',
                    'help_component' => 'moodle',
                    'element_type' => 'select',
                    'element_attributes' => array(
                        array(
                            0 => new lang_string('hiddensectionscollapsed'),
                            1 => new lang_string('hiddensectionsinvisible')
                        )
                    ),
                )
            );
            $courseformatoptions = array_merge_recursive($courseformatoptions, $courseformatoptionsedit);
        }
        return $courseformatoptions;
    }

    /**
     * Adds format options elements to the course/section edit form.
     *
     * This function is called from {@link course_edit_form::definition_after_data()}.
     *
     * @param MoodleQuickForm $mform form the elements are added to.
     * @param bool $forsection 'true' if this is a section edit form, 'false' if this is course edit form.
     * @return array array of references to the added form elements.
     */
    public function create_edit_form_elements(&$mform, $forsection = false) {
        $elements = parent::create_edit_form_elements($mform, $forsection);

        // Increase the number of sections combo box values if the user has increased the number of sections
        // using the icon on the course page beyond course 'maxsections' or course 'maxsections' has been
        // reduced below the number of sections already set for the course on the site administration course
        // defaults page.  This is so that the number of sections is not reduced leaving unintended orphaned
        // activities / resources.
        if (!$forsection) {
            $maxsections = get_config('moodlecourse', 'maxsections');
            $numsections = $mform->getElementValue('numsections');
            $numsections = $numsections[0];
            if ($numsections > $maxsections) {
                $element = $mform->getElement('numsections');
                for ($i = $maxsections + 1; $i <= $numsections; $i++) {
                    $element->addOption("$i", $i);
                }
            }
        }
        return $elements;
    }

    /**
     * Updates format options for a course
     *
     * In case if course format was changed to 'topics', we try to copy options
     * 'coursedisplay', 'numsections' and 'hiddensections' from the previous format.
     * If previous course format did not have 'numsections' option, we populate it with the
     * current number of sections
     *
     * @param stdClass|array $data return value from {@link moodleform::get_data()} or array with data
     * @param stdClass $oldcourse if this function is called from {@link update_course()}
     *     this object contains information about the course before update
     * @return bool whether there were any changes to the options values
     */
    public function update_course_format_options($data, $oldcourse = null) {
        global $DB;
        $data = (array)$data;
        if ($oldcourse !== null) {
            $oldcourse = (array)$oldcourse;
            $options = $this->course_format_options();
            foreach ($options as $key => $unused) {
                if (!array_key_exists($key, $data)) {
                    if (array_key_exists($key, $oldcourse)) {
                        $data[$key] = $oldcourse[$key];
                    } else if ($key === 'numsections') {
                        // If previous format does not have the field 'numsections'
                        // and data['numsections'] is not set,
                        // we fill it with the maximum section number from the DB.
                        $maxsection = $DB->get_field_sql('SELECT max(section) from {course_sections}
                            WHERE course = ?', array($this->courseid));
                        if ($maxsection) {
                            // If there are no sections, or just default 0-section, 'numsections' will be set to default.
                            $data['numsections'] = $maxsection;
                        }
                    }
                }
            }
        }
        $changed = $this->update_format_options($data);
        if ($changed && array_key_exists('numsections', $data)) {
            // If the numsections was decreased, try to completely delete the orphaned sections (unless they are not empty).
            $numsections = (int)$data['numsections'];
            $maxsection = $DB->get_field_sql('SELECT max(section) from {course_sections}
                        WHERE course = ?', array($this->courseid));
            for ($sectionnum = $maxsection; $sectionnum > $numsections; $sectionnum--) {
                if (!$this->delete_section($sectionnum, false)) {
                    break;
                }
            }
        }
        return $changed;
    }

    /**
     * Whether this format allows to delete sections
     *
     * Do not call this function directly, instead use {@link course_can_delete_section()}
     *
     * @param int|stdClass|section_info $section
     * @return bool
     */
    public function can_delete_section($section) {
        return true;
    }

    public function get_course() {
        global $DB;

        parent::get_course();

        if (!empty($this->course->id)) {
            $extradata = $DB->get_records('format_fntabs_config', array('courseid' => $this->course->id));
        } else {
            $extradata = false;
        }

        if ($extradata) {
            foreach ($extradata as $extra) {
                $this->course->{$extra->variable} = $extra->value;
                $this->course->{$extra->variable} = $extra->value;
            }
        }

        $settings = array(
            'showtabs',
            'tabcontent',
            'completiontracking',
            'tabwidth',
            'locationoftrackingicons',
            'activitytrackingbackground',
            'completiontracking',
            'mainheading',
            'topicheading',
            'maxtabs',
            'colorschema',
            'bgcolour',
            'activecolour',
            'selectedcolour',
            'inactivebgcolour',
            'inactivecolour',
            'activelinkcolour',
            'inactivelinkcolour',
            'selectedlinkcolour',
            'topictoshow',
            'showsection0',
            'showonlysection0',
            'defaulttab'
        );

        foreach ($settings as $index => $setting) {
            if (!isset($this->course->$setting)) {
                $this->course->$setting = format_fntabs_get_setting($this->course->id, $setting, true);
            }
        }
        return $this->course;
    }
}



function format_fntabs_update_course($form, $oldformat = false) {
    global $CFG, $DB, $OUTPUT;
    $configvars = array('showsection0', 'sec0title', 'mainheading', 'topicheading', 'maxtabs');

    foreach ($configvars as $configvar) {
        if ($varrec = $DB->get_record('format_fntabs_config', array('courseid' => $form->id, 'variable' => $configvar))) {
            $varrec->value = $form->$configvar;
            $DB->update_record('format_fntabs_config', $varrec);
        } else {
            $varrec->courseid = $form->id;
            $varrec->variable = $configvar;
            $varrec->value = $form->$configvar;
            $DB->insert_record('format_fntabs_config', $varrec);
        }
    }

    // We need to have the sections created ahead of time for the weekly nav to work,
    // so check and create here.
    if (!($sections = get_fast_modinfo($form->id)->get_section_info_all())) {
        $sections = array();
    }

    for ($i = 0; $i <= $form->numsections; $i++) {
        if (empty($sections[$i])) {
            $section = new stdClass();
            $section->course = $form->id;   // Create a new section structure.
            $section->section = $i;
            $section->summary = "";
            $section->visible = 1;
            if (!$section->id = $DB->insert_record("course_sections", $section)) {
                $OUTPUT->notification("Error inserting new section!");
            }
        }
    }

    // Check for a change to an FN format. If so, set some defaults as well...
    if ($oldformat != 'FN') {
        // Set the news (announcements) forum to no force subscribe, and no posts or discussions.
        require_once($CFG->dirroot . '/mod/forum/lib.php');
        $news = forum_get_course_forum($form->id, 'news');
        $news->open = 0;
        $news->forcesubscribe = 0;
        $DB->update_record('forum', $news);
    }
    rebuild_course_cache($form->id);
}

/* get the generic
 *  course object and
 * them to course object
 *
 */
function format_fntabs_get_course(&$course) {
    global $DB;
    // Add course specific variable to the passed in parameter.
    if ($configvars = $DB->get_records('format_fntabs_config', array('courseid' => $course->id))) {
        foreach ($configvars as $configvar) {
            $course->{$configvar->variable} = $configvar->value;
        }
    }
}

/* get the get week info
 *  course object and
 * them to course object
 *
 */
function format_fntabs_get_week_info($tabrange, $week) {
    global $DB, $SESSION;

    $fnmaxtab = $DB->get_field('format_fntabs_config', 'value', array('courseid' => $this->course->id, 'variable' => 'maxtabs'));
    if ($fnmaxtab) {
        $maximumtabs = $fnmaxtab;
    } else {
        $maximumtabs = 12;
    }

    if ($this->course->numsections == $maximumtabs) {
        $tablow = 1;
        $tabhigh = $maximumtabs;
    } else if ($tabrange > 1000) {
        $tablow = $tabrange / 1000;
        $tabhigh = $tablow + $maximumtabs - 1;
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
    $tabhigh = min($tabhigh, $this->course->numsections);

    // Normalize the tabs to always display FNMAXTABS...
    if (($tabhigh - $tablow + 1) < $maximumtabs) {
        $tablow = $tabhigh - $maximumtabs + 1;
    }

    // Save the low and high week in SESSION variables... If they already exist, and the selected
    // week is in their range, leave them as is.
    if (($tabrange >= 1000) || !isset($SESSION->FN_tablow[$this->course->id]) || !isset($SESSION->FN_tabhigh[$this->course->id]) ||
        ($week < $SESSION->FN_tablow[$this->course->id]) || ($week > $SESSION->FN_tabhigh[$this->course->id])) {
        $SESSION->FN_tablow[$this->course->id] = $tablow;
        $SESSION->FN_tabhigh[$this->course->id] = $tabhigh;
    } else {
        $tablow = $SESSION->FN_tablow[$this->course->id];
        $tabhigh = $SESSION->FN_tabhigh[$this->course->id];
    }

    $tablow = max($tablow, 1);
    $tabhigh = min($tabhigh, $this->course->numsections);

    // If selected week in a different set of tabs, move it to the current set...
    if (($week != 0) && ($week < $tablow)) {
        $week = $SESSION->G8_selected_week[$this->course->id] = $tablow;
    } else if ($week > $tabhigh) {
        $week = $SESSION->G8_selected_week[$this->course->id] = $tabhigh;
    }

    return array($tablow, $tabhigh, $week);
}

function format_fntabs_get_course_section_mods($courseid, $sectionid) {
    global $DB;

    if (empty($courseid)) {
        return false;
    }

    if (empty($sectionid)) {
        return false;
    }

    return $DB->get_records_sql("SELECT cm.*, m.name modname
                                   FROM {modules} m, {course_modules} cm
                                  WHERE cm.course = ?
                                    AND cm.section= ?
                                    AND cm.completion !=0
                                    AND cm.module = m.id
                                    AND m.visible = 1", array($courseid, $sectionid));
}

/**
 * To get the assignment object from instance
 *
 * @param instance of the assignment
 * @return assignment object from assignment table
 * @todo Finish documenting this function
 */
function format_fntabs_get_assignment_object_from_instance($module) {
    global $DB;

    if (!($assignment = $DB->get_record('assignment', array('id' => $module->instance)))) {

        return false;   // Doesn't exist... wtf?
    } else {
        return $assignment;
    }
}

/**
 * To get the assignment object and user submission
 *
 * @param module of the assignment
 * @return assignment object from assignment table
 * @todo Finish documenting this function
 */
function format_fntabs_is_saved_or_submitted($mod, $userid) {
    global $CFG, $DB, $USER, $SESSION;
    require_once($CFG->dirroot . '/mod/assignment/lib.php');

    if (isset($SESSION->completioncache)) {
        unset($SESSION->completioncache);
    }

    if ($mod->modname == 'assignment') {
        if (!($assignment = $DB->get_record('assignment', array('id' => $mod->instance)))) {
            return false;   // Doesn't exist... wtf?
        }
        require_once($CFG->dirroot.'/mod/assignment/type/'.$assignment->assignmenttype.'/assignment.class.php');
        $assignmentclass = "assignment_$assignment->assignmenttype";
        $assignmentinstance = new $assignmentclass($mod->id, $assignment, $mod);

        if (!($submission = $assignmentinstance->get_submission($userid)) || empty($submission->timemodified)) {
            return false;
        }

        switch ($assignment->assignmenttype) {
            case "upload":
                if ($assignment->var4) { // If var4 enable then assignment can be saved.
                    if (!empty($submission->timemodified)
                        && (empty($submission->data2))
                        && (empty($submission->timemarked))) {
                        return 'saved';
                    } else if (!empty($submission->timemodified)
                        && ($submission->data2 = 'submitted')
                        && empty($submission->timemarked)) {
                        return 'submitted';
                    } else if (!empty($submission->timemodified)
                        && ($submission->data2 = 'submitted')
                        && ($submission->grade == -1)) {
                        return 'submitted';
                    }
                } else if (empty($submission->timemarked)) {
                    return 'submitted';
                }
                break;
            case "uploadsingle":
                if (empty($submission->timemarked)) {
                    return 'submitted';
                }
                break;
            case "online":
                if (empty($submission->timemarked)) {
                    return 'submitted';
                }
                break;
            case "offline":
                if (empty($submission->timemarked)) {
                    return 'submitted';
                }
                break;
        }
    } else if ($mod->modname == 'assign') {
        if (!($assignment = $DB->get_record('assign', array('id' => $mod->instance)))) {
            return false; // Doesn't exist.
        }

        if (!$submission = $DB->get_records('assign_submission',
            array('assignment' => $assignment->id, 'userid' => $USER->id), 'attemptnumber DESC', '*', 0, 1)) {
            return false;
        } else {
            $submission = reset($submission);
        }

        $attemptnumber = $submission->attemptnumber;

        if (($submission->status == 'reopened') && ($submission->attemptnumber > 0)) {
            $attemptnumber = $submission->attemptnumber - 1;
        }

        if ($submissionisgraded = $DB->get_records('assign_grades',
            array('assignment' => $assignment->id, 'userid' => $USER->id, 'attemptnumber' => $attemptnumber),
            'attemptnumber DESC', '*', 0, 1)) {

            $submissionisgraded = reset($submissionisgraded);
            if ($submissionisgraded->grade > -1) {
                if (($submission->timemodified > $submissionisgraded->timemodified)
                    || ($submission->attemptnumber > $submissionisgraded->attemptnumber)) {
                    $graded = false;
                } else {
                    $graded = true;
                }
            } else {
                $graded = false;
            }
        } else {
            $graded = false;
        }

        if ($submission->status == 'draft') {
            if ($graded) {
                return 'submitted';
            } else {
                return 'saved';
            }
        }
        if ($submission->status == 'reopened') {
            return 'submitted';
        }
        if ($submission->status == 'submitted') {
            if ($graded) {
                return 'submitted';
            } else {
                return 'waitinggrade';
            }
        }
    } else {
        return;
    }
}

/**
 * To Know status of the activity
 *
 * @param mod object
 * @param userid
 * @return saved or submitted
 * @todo Finish documenting this function
 */
function format_fntabs_get_activities_status($course, $section) {

    global $CFG, $USER;
    require_once($CFG->libdir . '/completionlib.php');
    require_once($CFG->dirroot . '/course/lib.php');
    $complete = 0;
    $incomplete = 0;
    $saved = 0;
    $notattempted = 0;
    $waitingforgrade = 0;
    $sectionmodule = array();

    if ($section->visible) {
        $modules = format_fntabs_get_course_section_mods($course->id, $section->id);
        $completion = new completion_info($course);
        if ((isset($CFG->enablecompletion)) && !empty($completion)) {
            foreach ($modules as $module) {
                if (!$module->visible) {
                    continue;
                }
                if ($completion->is_enabled($course = null, $module)) {
                    $data = $completion->get_data($module, false, $USER->id, null);
                    $completionstate = $data->completionstate;
                    // Grab assignment status.
                    $assignementstatus = format_fntabs_is_saved_or_submitted($module, $USER->id);

                    if ($completionstate == COMPLETION_INCOMPLETE) {  // If completion=0 then it may be saved or submitted.
                        if (($module->modname == 'assignment' || $module->modname == 'assign')
                            && ($module->completion == '2')
                            && $assignementstatus) {

                            if (isset($assignementstatus)) {
                                if ($assignementstatus == 'saved') {
                                    $sectionmodule[$module->id] = 'saved';
                                    $saved++;
                                } else if ($assignementstatus == 'submitted') {
                                    $sectionmodule[$module->id] = 'notattemted';
                                    $notattempted++;
                                } else if ($assignementstatus == 'waitinggrade') {
                                    $sectionmodule[$module->id] = 'waitingforgrade';
                                    $waitingforgrade++;
                                }
                            } else {
                                $sectionmodule[$module->id] = 'notattemted';
                                $notattempted++;
                            }
                        } else {
                            if (($module->modname == 'quiz')
                                && format_fntabs_quiz_waitingforgrade($module->instance, $USER->id)) {
                                $sectionmodule[$module->id] = 'waitingforgrade';
                                $waitingforgrade++;
                            } else {
                                $sectionmodule[$module->id] = 'notattemted';
                                $notattempted++;
                            }
                        }
                    } else if ($completionstate == COMPLETION_COMPLETE || $completionstate == COMPLETION_COMPLETE_PASS) {
                        if (($module->modname == 'assignment' || $module->modname == 'assign')
                            && ($module->completion == 2)
                            && $assignementstatus) {
                            if (isset($assignementstatus)) {
                                if ($assignementstatus == 'saved') {
                                    $sectionmodule[$module->id] = 'saved';
                                    $saved++;
                                } else if ($assignementstatus == 'submitted') {
                                    $sectionmodule[$module->id] = 'complete';
                                    $complete++;
                                } else if ($assignementstatus == 'waitinggrade') {
                                    $sectionmodule[$module->id] = 'waitingforgrade';
                                    $waitingforgrade++;
                                }
                            } else {
                                $sectionmodule[$module->id] = 'complete';
                                $complete++;
                            }
                        } else {
                            $sectionmodule[$module->id] = 'complete';
                            $complete++;
                        }

                    } else if ($completionstate == COMPLETION_COMPLETE_FAIL) {
                        if (($module->modname == 'assignment' || $module->modname == 'assign')
                            && ($module->completion == 2)
                            && $assignementstatus) {
                            if (isset($assignementstatus)) {
                                if ($assignementstatus == 'saved') {
                                    $sectionmodule[$module->id] = 'saved';
                                    $saved++;
                                } else if ($assignementstatus == 'submitted') {
                                    $sectionmodule[$module->id] = 'incomplete';
                                    $incomplete++;
                                } else if ($assignementstatus == 'waitinggrade') {
                                    $sectionmodule[$module->id] = 'waitingforgrade';
                                    $waitingforgrade++;
                                }
                            } else {
                                $sectionmodule[$module->id] = 'incomplete';
                                $incomplete++;
                            }
                        } else {
                            $sectionmodule[$module->id] = 'incomplete';
                            $incomplete++;
                        }
                    }
                }
            }
            $array["complete"] = "$complete";
            $array["incomplete"] = "$incomplete";
            $array["saved"] = "$saved";
            $array["notattempted"] = "$notattempted";
            $array["waitngforgrade"] = "$waitingforgrade";
            $array["modules"] = $sectionmodule;
            return $array;
        }
    }
}

function format_fntabs_quiz_waitingforgrade ($quizid, $userid) {
    global $DB;
    $sql = "SELECT qs.id,
                   q.qtype
              FROM {quiz_slots} qs
              JOIN {question} q
                ON qs.questionid = q.id
             WHERE qs.quizid = ?
               AND q.qtype = 'essay'";

    if ($DB->record_exists_sql($sql, array($quizid))) {
        $sql = "SELECT qa.id,
                       qa.sumgrades
                  FROM {quiz_attempts} qa
                 WHERE qa.quiz = ?
                   AND qa.userid = ?
                   AND qa.state = 'finished'
              ORDER BY qa.attempt DESC";

        if ($attempts = $DB->get_records_sql($sql, array($quizid, $userid))) {
            $attempt = reset($attempts);
            if (is_null($attempt->sumgrades)) {
                return true;
            }
        }
    }
    return false;

}

function format_fntabs_update_course_setting($variable, $data) {
    global $course, $DB;

    $rec = new stdClass();
    $rec->courseid = $course->id;
    $rec->variable = $variable;
    $rec->value = $data;

    if ($DB->get_field('format_fntabs_config', 'id', array('courseid' => $course->id, 'variable' => $variable))) {
        $id = $DB->get_field('format_fntabs_config', 'id', array('courseid' => $course->id, 'variable' => $variable));
        $rec->id = $id;
        $DB->update_record('format_fntabs_config', $rec);
    } else {
        $rec->id = $DB->insert_record('format_fntabs_config', $rec);
    }
}

function format_fntabs_get_setting($courseid, $name, $getdefaultvalue = false) {
    global $DB;

    // Default values.
    $showtabs = 1;
    $tabcontent = 'usesectionnumbers';
    $completiontracking = 1;
    $tabwidth = 'equalspacing';
    $locationoftrackingicons = 'nediconsleft';
    $showorphaned = 0;
    $activitytrackingbackground = 1;
    $completiontracking = 1;
    $mainheading = '';
    $topicheading = get_string('defaulttopicheading', 'format_fntabs');
    $maxtabs = 12;
    $colorschema = 0;
    $bgcolour = '9DBB61';
    $activecolour = 'DBE6C4';
    $selectedcolour = 'FFFF33';
    $inactivebgcolour = 'F5E49C';
    $inactivecolour = 'BDBBBB';
    $activelinkcolour = '000000';
    $inactivelinkcolour = '000000';
    $selectedlinkcolour = '000000';
    $topictoshow = 1;
    $showsection0 = 0;
    $showonlysection0 = 0;
    $defaulttab = 'option1';

    if ($getdefaultvalue) {
        return $$name;
    }

    $setting = $DB->get_field('format_fntabs_config', 'value',
        array('courseid' => $courseid, 'variable' => $name)
    );

    if ($setting === false) {
        return $$name;
    } else {
        return $setting;
    }
}
function format_fntabs_course_get_cm_rename_action(cm_info $mod, $sr = null) {
    global $COURSE, $OUTPUT;

    static $str;
    static $baseurl;

    $modcontext = context_module::instance($mod->id);
    $hasmanageactivities = has_capability('moodle/course:manageactivities', $modcontext);

    if (!isset($str)) {
        $str = get_strings(array('edittitle'));
    }

    if (!isset($baseurl)) {
        $baseurl = new moodle_url('/course/mod.php', array('sesskey' => sesskey()));
    }

    if ($sr !== null) {
        $baseurl->param('sr', $sr);
    }

    // AJAX edit title.
    if ($mod->has_view() && $hasmanageactivities && course_ajax_enabled($COURSE) &&
        (($mod->course == $COURSE->id) || ($mod->course == SITEID))) {
        // We will not display link if we are on some other-course page (where we should not see this module anyway).
        return html_writer::span(
            html_writer::link(
                new moodle_url($baseurl, array('update' => $mod->id)),
                $OUTPUT->pix_icon('t/editstring', '', 'moodle', array('class' => 'iconsmall visibleifjs', 'title' => '')),
                array(
                    'class' => 'editing_title',
                    'data-action' => 'edittitle',
                    'title' => $str->edittitle,
                )
            )
        );
    }
    return '';
}