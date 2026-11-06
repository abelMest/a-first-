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
 * @package    block_afirst_assistant
 * @copyright  2026 Africa CDC
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Upgrade the plugin.
 *
 * @param int $oldversion
 * @return bool
 */
function xmldb_block_afirst_assistant_upgrade($oldversion) {
    global $DB;
    $dbman = $DB->get_manager();

    if ($oldversion < 2026110402) {
        // Cross-lingual retrieval (D-17): keep the translated query for audit and review.
        $table = new xmldb_table('block_afirst_log');
        $field = new xmldb_field('retrievalquery', XMLDB_TYPE_TEXT, null, null, null, null, null, 'downlinkmbps');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        upgrade_block_savepoint(true, 2026110402, 'afirst_assistant');
    }

    if ($oldversion < 2026110403) {
        // Support check (DE A-12): how much of each answer is found in the passages it cites.
        $table = new xmldb_table('block_afirst_log');
        $field = new xmldb_field('supportscore', XMLDB_TYPE_NUMBER, '10, 4', null, null, null, null, 'retrievalquery');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        upgrade_block_savepoint(true, 2026110403, 'afirst_assistant');
    }

    if ($oldversion < 2026110600) {
        // Launcher, module context, follow-ups, hybrid retrieval and ratings (0.5.0).
        $fields = [
            'block_afirst_source' => [new xmldb_field('suggestions', XMLDB_TYPE_TEXT, null, null, null, null, null,
                'timemodified')],
            'block_afirst_chunk' => [new xmldb_field('embedding', XMLDB_TYPE_TEXT, null, null, null, null, null,
                'termfreq')],
            'block_afirst_log' => [
                new xmldb_field('topsimilarity', XMLDB_TYPE_NUMBER, '10, 4', null, null, null, null, 'supportscore'),
                new xmldb_field('contextmodule', XMLDB_TYPE_CHAR, '20', null, null, null, null, 'topsimilarity'),
                new xmldb_field('followup', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0', 'contextmodule'),
                new xmldb_field('rating', XMLDB_TYPE_INTEGER, '2', null, null, null, null, 'followup'),
            ],
        ];
        foreach ($fields as $tablename => $tablefields) {
            $table = new xmldb_table($tablename);
            foreach ($tablefields as $field) {
                if (!$dbman->field_exists($table, $field)) {
                    $dbman->add_field($table, $field);
                }
            }
        }
        upgrade_block_savepoint(true, 2026110600, 'afirst_assistant');
    }

    return true;
}
