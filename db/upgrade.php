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

function xmldb_format_fntabs_upgrade($version) {
    global $DB;

    if ($version < 2016090600) {
        if ($schema = $DB->get_record('format_fntabs_color', array('name' => 'Green Meadow', 'predefined' => 1))) {
            $schema->name = 'Embassy Green';
            $DB->update_record('format_fntabs_color', $schema);
        }

        if ($schema = $DB->get_record('format_fntabs_color', array('name' => 'Grey on White', 'predefined' => 1))) {
            $schema->name = 'Blues on Whyte';
            $schema->selectedcolour = '7CAAFE';
            $DB->update_record('format_fntabs_color', $schema);
        }
    }
    return true;
}