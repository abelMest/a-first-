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

namespace block_afirst_assistant\privacy;

use core_privacy\local\metadata\collection;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\approved_userlist;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\transform;
use core_privacy\local\request\userlist;
use core_privacy\local\request\writer;

/**
 * Privacy provider: learners' questions, answers and reports, stored per course.
 *
 * @package    block_afirst_assistant
 * @copyright  2026 Africa CDC
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class provider implements
    \core_privacy\local\metadata\provider,
    \core_privacy\local\request\core_userlist_provider,
    \core_privacy\local\request\plugin\provider {

    #[\Override]
    public static function get_metadata(collection $collection): collection {
        $collection->add_database_table('block_afirst_log', [
            'userid' => 'privacy:metadata:log:userid',
            'question' => 'privacy:metadata:log:question',
            'answer' => 'privacy:metadata:log:answer',
            'connectiontype' => 'privacy:metadata:log:connectiontype',
            'contextmodule' => 'privacy:metadata:log:contextmodule',
            'rating' => 'privacy:metadata:log:rating',
            'retrievalquery' => 'privacy:metadata:log:retrievalquery',
            'timecreated' => 'privacy:metadata:log:timecreated',
        ], 'privacy:metadata:log');
        $collection->add_database_table('block_afirst_escalation', [
            'userid' => 'privacy:metadata:escalation:userid',
            'reason' => 'privacy:metadata:escalation:reason',
            'comment' => 'privacy:metadata:escalation:comment',
            'timecreated' => 'privacy:metadata:escalation:timecreated',
            'reviewerid' => 'privacy:metadata:escalation:reviewerid',
            'resolution' => 'privacy:metadata:escalation:resolution',
        ], 'privacy:metadata:escalation');
        $collection->add_database_table('block_afirst_source', [
            'usermodified' => 'privacy:metadata:source:usermodified',
            'approvedby' => 'privacy:metadata:source:approvedby',
            'timeapproved' => 'privacy:metadata:source:timeapproved',
        ], 'privacy:metadata:source');
        $collection->add_subsystem_link('core_ai', [], 'privacy:metadata:core_ai');
        return $collection;
    }

    #[\Override]
    public static function get_contexts_for_userid(int $userid): contextlist {
        $contextlist = new contextlist();
        foreach (self::user_field_queries() as [$table, $where, $userparams]) {
            $contextlist->add_from_sql("SELECT ctx.id
                                          FROM {context} ctx
                                          JOIN {{$table}} t ON t.courseid = ctx.instanceid AND ctx.contextlevel = :courselevel
                                         WHERE $where",
                ['courselevel' => CONTEXT_COURSE] + array_fill_keys($userparams, $userid));
        }
        return $contextlist;
    }

    #[\Override]
    public static function get_users_in_context(userlist $userlist): void {
        $context = $userlist->get_context();
        if ($context->contextlevel != CONTEXT_COURSE) {
            return;
        }
        $params = [$context->instanceid];
        $userlist->add_from_sql('userid', 'SELECT userid FROM {block_afirst_log} WHERE courseid = ?', $params);
        $userlist->add_from_sql('reviewerid', 'SELECT reviewerid FROM {block_afirst_escalation} WHERE courseid = ?', $params);
        $userlist->add_from_sql('approvedby', 'SELECT approvedby FROM {block_afirst_source} WHERE courseid = ?', $params);
        $userlist->add_from_sql('usermodified', 'SELECT usermodified FROM {block_afirst_source} WHERE courseid = ?', $params);
    }

    /**
     * Tables and conditions that link a user to a course.
     *
     * @return array[] [table, where clause, names of its user-id parameters]
     */
    private static function user_field_queries(): array {
        return [
            ['block_afirst_log', 't.userid = :userid', ['userid']],
            ['block_afirst_escalation', 't.reviewerid = :userid', ['userid']],
            ['block_afirst_source', '(t.approvedby = :userid OR t.usermodified = :userid2)', ['userid', 'userid2']],
        ];
    }

    #[\Override]
    public static function export_user_data(approved_contextlist $contextlist): void {
        global $DB;
        $userid = $contextlist->get_user()->id;
        foreach ($contextlist as $context) {
            if ($context->contextlevel != CONTEXT_COURSE) {
                continue;
            }
            $logs = $DB->get_records('block_afirst_log', ['courseid' => $context->instanceid, 'userid' => $userid], 'id');
            $reports = $DB->get_records('block_afirst_escalation', ['courseid' => $context->instanceid, 'userid' => $userid],
                'id');
            $data = (object) [
                'questions' => array_values(array_map(fn($l) => (object) [
                    'question' => $l->question,
                    'answer' => $l->answer,
                    'refused' => transform::yesno($l->refused),
                    'connectiontype' => $l->connectiontype,
                    'module' => $l->contextmodule,
                    'rating' => $l->rating === null ? null : ((int)$l->rating > 0 ?
                        get_string('helpful', 'block_afirst_assistant') : get_string('nothelpful', 'block_afirst_assistant')),
                    'retrievalquery' => $l->retrievalquery,
                    'timecreated' => transform::datetime($l->timecreated),
                ], $logs)),
                'reports' => array_values(array_map(fn($r) => (object) [
                    'reason' => $r->reason,
                    'comment' => $r->comment,
                    'status' => $r->status,
                    'timecreated' => transform::datetime($r->timecreated),
                ], $reports)),
                // Staff roles: sources this user uploaded or approved, and reports they reviewed.
                'sources' => array_values(array_map(fn($s) => (object) [
                    'title' => $s->title,
                    'uploadedbyyou' => transform::yesno($s->usermodified == $userid),
                    'approvedbyyou' => transform::yesno($s->approvedby == $userid),
                    'timeapproved' => $s->timeapproved ? transform::datetime($s->timeapproved) : null,
                ], $DB->get_records_select('block_afirst_source', 'courseid = ? AND (approvedby = ? OR usermodified = ?)',
                    [$context->instanceid, $userid, $userid], 'id'))),
                'reviewed' => array_values(array_map(fn($r) => (object) [
                    'resolution' => $r->resolution,
                    'timeresolved' => $r->timeresolved ? transform::datetime($r->timeresolved) : null,
                ], $DB->get_records('block_afirst_escalation', ['courseid' => $context->instanceid, 'reviewerid' => $userid],
                    'id'))),
            ];
            writer::with_context($context)->export_data([get_string('pluginname', 'block_afirst_assistant')], $data);
        }
    }

    #[\Override]
    public static function delete_data_for_all_users_in_context(\context $context): void {
        global $DB;
        if ($context->contextlevel != CONTEXT_COURSE) {
            return;
        }
        $DB->delete_records('block_afirst_escalation', ['courseid' => $context->instanceid]);
        $DB->delete_records('block_afirst_log', ['courseid' => $context->instanceid]);
        // Approved sources are course content, not personal data: keep them, drop who handled them.
        $DB->execute('UPDATE {block_afirst_source} SET approvedby = NULL, usermodified = 0 WHERE courseid = ?',
            [$context->instanceid]);
    }

    #[\Override]
    public static function delete_data_for_user(approved_contextlist $contextlist): void {
        $userid = $contextlist->get_user()->id;
        foreach ($contextlist as $context) {
            if ($context->contextlevel == CONTEXT_COURSE) {
                self::delete_users_in_course($context->instanceid, [$userid]);
            }
        }
    }

    #[\Override]
    public static function delete_data_for_users(approved_userlist $userlist): void {
        $context = $userlist->get_context();
        if ($context->contextlevel == CONTEXT_COURSE) {
            self::delete_users_in_course($context->instanceid, $userlist->get_userids());
        }
    }

    /**
     * Delete the logs and reports of some users in one course, including reports on their answers,
     * and anonymise their staff actions (source uploads and approvals, report reviews).
     *
     * @param int $courseid
     * @param int[] $userids
     */
    private static function delete_users_in_course(int $courseid, array $userids): void {
        global $DB;
        if (!$userids) {
            return;
        }
        [$insql, $params] = $DB->get_in_or_equal($userids, SQL_PARAMS_NAMED);
        $params['courseid'] = $courseid;
        $DB->delete_records_select('block_afirst_escalation',
            "courseid = :courseid AND logid IN (SELECT id FROM {block_afirst_log} WHERE userid $insql)", $params);
        $DB->delete_records_select('block_afirst_log', "courseid = :courseid AND userid $insql", $params);
        $DB->execute("UPDATE {block_afirst_escalation} SET reviewerid = NULL WHERE courseid = :courseid AND reviewerid $insql",
            $params);
        $DB->execute("UPDATE {block_afirst_source} SET approvedby = NULL WHERE courseid = :courseid AND approvedby $insql",
            $params);
        $DB->execute("UPDATE {block_afirst_source} SET usermodified = 0 WHERE courseid = :courseid AND usermodified $insql",
            $params);
    }
}
