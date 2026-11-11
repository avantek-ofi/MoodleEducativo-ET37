<?php
// This file is part of Aula ET37 - https://moodle.org/
//
// Aula ET37 is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * Upgrade steps.
 *
 * @package    local_aulaet37
 * @copyright  2026 Aula ET37
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Runs upgrade steps.
 *
 * @param int $oldversion
 * @return bool
 */
function xmldb_local_aulaet37_upgrade($oldversion): bool {
    global $DB;

    if ($oldversion < 2026111100) {
        // DNI and birth date stop being "required" in Moodle so site administrators and
        // manual accounts are not forced to fill them; the plugin requires them on signup
        // and in its own step for self-registered and Google accounts.
        foreach (['dni', 'fechanacimiento'] as $shortname) {
            $DB->set_field('user_info_field', 'required', 0, ['shortname' => $shortname]);
        }
        upgrade_plugin_savepoint(true, 2026111100, 'local', 'aulaet37');
    }

    if ($oldversion < 2026111200) {
        // Weekly class timetable.
        $dbman = $DB->get_manager();
        $table = new xmldb_table('local_aulaet37_slots');
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('courseid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('groupid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('weekday', XMLDB_TYPE_INTEGER, '2', null, XMLDB_NOTNULL, null, null);
        $table->add_field('starttime', XMLDB_TYPE_INTEGER, '4', null, XMLDB_NOTNULL, null, null);
        $table->add_field('endtime', XMLDB_TYPE_INTEGER, '4', null, XMLDB_NOTNULL, null, null);
        $table->add_field('room', XMLDB_TYPE_CHAR, '100', null, XMLDB_NOTNULL, null, '');
        $table->add_field('notes', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, '');
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table->add_key('courseid', XMLDB_KEY_FOREIGN, ['courseid'], 'course', ['id']);
        $table->add_index('weekday', XMLDB_INDEX_NOTUNIQUE, ['weekday', 'starttime']);
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }
        upgrade_plugin_savepoint(true, 2026111200, 'local', 'aulaet37');
    }

    return true;
}
