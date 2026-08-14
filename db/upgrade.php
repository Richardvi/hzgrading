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
 * Upgrade script for local_hzgrading.
 *
 * @package    local_hzgrading
 * @copyright  internetlab.nl
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Voert de database-upgrades uit voor local_hzgrading.
 *
 * Nodig omdat de plugin al eerder geïnstalleerd kan zijn zonder de
 * local_hzgrading_override tabel; install.xml wordt alleen gebruikt
 * bij een verse installatie.
 *
 * @param int $oldversion
 * @return bool
 */
function xmldb_local_hzgrading_upgrade($oldversion) {
    global $DB;
    $dbman = $DB->get_manager();

    if ($oldversion < 2026081300) {

        $table = new xmldb_table('local_hzgrading_override');
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE);
        $table->add_field('assignid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null);
        $table->add_field('userid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null);
        $table->add_field('graderid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null);
        $table->add_field('type', XMLDB_TYPE_CHAR, '10', null, XMLDB_NOTNULL, null);
        $table->add_field('value', XMLDB_TYPE_NUMBER, '10, 5', null, null, null);
        $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null);

        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table->add_key('assignid', XMLDB_KEY_FOREIGN, ['assignid'], 'assign', ['id']);
        $table->add_key('userid', XMLDB_KEY_FOREIGN, ['userid'], 'user', ['id']);
        $table->add_key('graderid', XMLDB_KEY_FOREIGN, ['graderid'], 'user', ['id']);

        $table->add_index('assignid_userid', XMLDB_INDEX_UNIQUE, ['assignid', 'userid']);

        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        upgrade_plugin_savepoint(true, 2026081300, 'local', 'hzgrading');
    }

    return true;
}
