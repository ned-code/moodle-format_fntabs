<?php
/**
 * course_format is the base class for all course formats
 *
 * This class provides all the functionality for a course format
 */

/**
 * Standard base class for all course formats
 */
class course_format {
    

    var $course;        // The course record, with all data fields.
    var $page;          // The page object.
    var $blocks;        // The pageblocks object.

/**
 * Contructor
 * @param $course object The pre-defined course object. 
 * Passed by reference, so that extended info can be added.
 *
 */
    function course_format(&$course) {
        if (empty($this->course) && is_object($course)) {
            $this->course = clone($course);
        }
        
        $courseformatoptions = course_get_format($course)->get_format_options();
        $this->course->numsections = $courseformatoptions['numsections']; 
        //$course->hiddensections = $courseformatoptions['hiddensections']; 
        //$course->coursedisplay = $courseformatoptions['coursedisplay'];
         
        /// Method should load any other course data into the course property.
        $this->get_course();
    }

/**
 * Get any additional course data and return it. Also add it to the course property, and the optional
 * course parameter.
 *
 * @param $course object Optional object to add the new data to.
 * @return object The extra course data.
 *
 */ 
    
    function get_course($course=null) {
        return $course;

    /// Sample Code:
//        if (!empty($course->id)) {
//            $extradata = get_records('course_config_[format]', 'courseid', $course->id);
//        } else if (!empty($this->course->id)) {
//            $extradata = get_records('course_config_[format]', 'courseid', $this->course->id);
//        } else {
//            $extradata = false;
//        }
//
//        if (is_null($course)) {
//            $course = new Object();
//        }
//
//        if ($extradata) {
//            foreach ($extradata as $extra) {
//                $this->course->{$extra->variable} = $extra->value;
//                $course->{$extra->variable} = $extra->value;
//            }
//        }
//
//        return $course;
    }
}