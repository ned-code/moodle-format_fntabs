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
 * Keeps track of the version number
 *
 * @package    course/format
 * @subpackage fntabs
 * @author     Fernando Oliveira - MoodleFN {@link http://moodlefn.knet.ca}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


$plugin->version = 2013032201;
$plugin->requires = 2013051400; // 2.5
$plugin->maturity = MATURITY_RC;
$plugin->component = 'format_fntabs';
$plugin->release = '2.5';
// plugin dependency for block_fn_tabs was removed for Moodle 2.5
