<?php
// This file is part of Moodle - https://moodle.org/
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
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * Upgrade steps.
 *
 * @package    local_afirst_loop
 * @copyright  2026 Africa CDC
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Upgrade the plugin.
 *
 * @param int $oldversion
 * @return bool
 */
function xmldb_local_afirst_loop_upgrade($oldversion) {
    global $DB;
    $dbman = $DB->get_manager();

    if ($oldversion < 2026112001) {
        // Records become per course (course-level alerts use cmid 0).
        $table = new xmldb_table('local_afirst_loop_sent');
        foreach ([new xmldb_index('usercmkind', XMLDB_INDEX_UNIQUE, ['userid', 'cmid', 'kind']),
                new xmldb_index('courseid', XMLDB_INDEX_NOTUNIQUE, ['courseid'])] as $old) {
            if ($dbman->index_exists($table, $old)) {
                $dbman->drop_index($table, $old);
            }
        }
        $new = new xmldb_index('courseusercmkind', XMLDB_INDEX_UNIQUE, ['courseid', 'userid', 'cmid', 'kind']);
        if (!$dbman->index_exists($table, $new)) {
            $dbman->add_index($table, $new);
        }
        upgrade_plugin_savepoint(true, 2026112001, 'local', 'afirst_loop');
    }
    return true;
}
