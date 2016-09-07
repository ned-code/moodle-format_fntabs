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

function xmldb_format_fntabs_install() {
    global $DB;

    $rec = new stdClass();
    $rec->name = 'Embassy Green';
    $rec->courseid = 1;
    $rec->bgcolour = '9DBB61';
    $rec->activecolour = 'DBE6C4';
    $rec->selectedcolour = 'FFFF33';
    $rec->inactivecolour = 'BDBBBB';
    $rec->inactivebgcolour = 'F5E49C';
    $rec->activelinkcolour = '000000';
    $rec->selectedlinkcolour = '000000';
    $rec->inactivelinkcolour = '000000';
    $rec->predefined = 1;
    $rec->timecreated = time();
    $DB->insert_record('format_fntabs_color', $rec);

    $rec = new stdClass();
    $rec->name = 'Blues on Whyte';
    $rec->courseid = 1;
    $rec->bgcolour = 'FFFFFF';
    $rec->activecolour = 'E1E1E1';
    $rec->selectedcolour = '7CAAFE';
    $rec->inactivecolour = 'BDBBBB';
    $rec->inactivebgcolour = 'F5E49C';
    $rec->activelinkcolour = '929292';
    $rec->selectedlinkcolour = 'FFFFFF';
    $rec->inactivelinkcolour = '929292';
    $rec->predefined = 1;
    $rec->timecreated = time();
    $DB->insert_record('format_fntabs_color', $rec);

    return true;
}