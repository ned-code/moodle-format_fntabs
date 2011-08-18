<?php  //$Id: upgrade.php,v 1.1 2009/04/17 20:45:22 mchurch Exp $

// This file keeps track of upgrades to 
// the fn course format
//
// Sometimes, changes between versions involve
// alterations to database structures and other
// major things that may break installations.
//
// The upgrade function in this file will attempt
// to perform all the necessary actions to upgrade
// your older installtion to the current version.
//
// If there's something it cannot do itself, it
// will tell you what you need to do.
//
// The commands in here will all be database-neutral,
// using the functions defined in lib/ddllib.php

function xmldb_format_fn_upgrade($oldversion=0) {

    global $CFG, $THEME, $DB;
    $dbman = $DB->get_manager(); /// loads ddl manager and xmldb classes 

    if ($oldversion < 2011071301) { //New version in version.php
        $table = new xmldb_table('fn_coursemodule_extra');
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('courseid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '0', 'id');
        $table->add_field('cmid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '0', 'courseid');
        $table->add_field('hideingradebook', XMLDB_TYPE_INTEGER, '2', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '0', 'cmid');
        $table->add_field('mandatory', XMLDB_TYPE_INTEGER, '2', XMLDB_UNSIGNED, XMLDB_NOTNULL, null,'0', 'hideingradebook');

         /// Adding keys to table fn_coursemodule_extra
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));

         /// Adding index to table fn_coursemodule_extra
        $table->add_index('courseid', XMLDB_INDEX_NOTUNIQUE, array('courseid'));
        $table->add_index('cmid', XMLDB_INDEX_NOTUNIQUE, array('cmid'));

          if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }
          ///format savepoint reached
        upgrade_format_savepoint(true, 2011071301, 'community');
       
    }
    return true;
}



