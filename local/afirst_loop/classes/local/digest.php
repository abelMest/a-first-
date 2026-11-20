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

namespace local_afirst_loop\local;

/**
 * Step 3 of the OODA design note: rule R8 (nudge learners who have stalled) and the weekly teacher digest.
 *
 * Both read only what Moodle and the course assistant already record. "Last activity" is the latest of a course visit,
 * an activity completion, a quiz attempt or a question to the assistant.
 *
 * @package    local_afirst_loop
 * @copyright  2026 Africa CDC
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class digest {

    /** @var string Learner nudged after a stall (R8); cleared when they come back. */
    public const KIND_STALLED = 'stalled';

    /**
     * Learners in a course: enrolled users who appear in completion reports (students), excluding teachers.
     *
     * @param int $courseid
     * @return \stdClass[] Keyed by user id.
     */
    public static function learners(int $courseid): array {
        $context = \context_course::instance($courseid);
        $users = get_enrolled_users($context, 'moodle/course:isincompletionreports', 0, 'u.*', null, 0, 0, true);
        return array_filter($users, fn($u) => !has_capability('local/afirst_loop:receivealerts', $context, $u));
    }

    /**
     * Each learner's last activity in the course and the last activity module they worked on.
     *
     * @param int $courseid
     * @param int[] $userids
     * @return array<int, array{time: int, cmid: int|null}> Learners with any activity only.
     */
    public static function last_activity(int $courseid, array $userids): array {
        global $DB;
        if (!$userids) {
            return [];
        }
        [$in, $params] = $DB->get_in_or_equal($userids, SQL_PARAMS_NAMED);
        $out = [];
        $cmtime = [];
        // Only activities learners can see may name where they left off.
        $visible = [];
        foreach (get_fast_modinfo($courseid)->get_cms() as $cm) {
            if ($cm->visible && $cm->visibleoncoursepage) {
                $visible[(int)$cm->id] = true;
            }
        }
        // The latest time of any kind counts as activity; the latest activity-module event names where they left off.
        $bump = function(int $userid, int $time, ?int $cmid) use (&$out, &$cmtime, $visible) {
            $out[$userid] ??= ['time' => 0, 'cmid' => null];
            $out[$userid]['time'] = max($out[$userid]['time'], $time);
            if ($cmid !== null && isset($visible[$cmid]) && $time >= ($cmtime[$userid] ?? 0)) {
                $cmtime[$userid] = $time;
                $out[$userid]['cmid'] = $cmid;
            }
        };
        // Course visits (no module).
        foreach ($DB->get_records_select('user_lastaccess', "courseid = :courseid AND userid $in",
                $params + ['courseid' => $courseid], '', 'id, userid, timeaccess') as $r) {
            $bump((int)$r->userid, (int)$r->timeaccess, null);
        }
        // Completions and viewed activities: the latest one per learner names where they left off.
        foreach ($DB->get_records_sql("SELECT cmc.id, cmc.userid, cmc.coursemoduleid, cmc.timemodified
                                         FROM {course_modules_completion} cmc
                                         JOIN {course_modules} cm ON cm.id = cmc.coursemoduleid
                                        WHERE cm.course = :courseid AND cmc.userid $in AND cmc.completionstate > 0",
                $params + ['courseid' => $courseid]) as $r) {
            $bump((int)$r->userid, (int)$r->timemodified, (int)$r->coursemoduleid);
        }
        foreach ($DB->get_records_sql("SELECT qa.id, qa.userid, cm.id AS cmid, qa.timemodified
                                         FROM {quiz_attempts} qa
                                         JOIN {quiz} q ON q.id = qa.quiz
                                         JOIN {modules} m ON m.name = 'quiz'
                                         JOIN {course_modules} cm ON cm.instance = q.id AND cm.module = m.id
                                        WHERE q.course = :courseid AND qa.userid $in AND qa.preview = 0",
                $params + ['courseid' => $courseid]) as $r) {
            $bump((int)$r->userid, (int)$r->timemodified, (int)$r->cmid);
        }
        if ($DB->get_manager()->table_exists('block_afirst_log')) {
            foreach ($DB->get_records_sql("SELECT userid, MAX(timecreated) AS t FROM {block_afirst_log}
                                            WHERE courseid = :courseid AND userid $in GROUP BY userid",
                    $params + ['courseid' => $courseid]) as $r) {
                $bump((int)$r->userid, (int)$r->t, null);
            }
        }
        return $out;
    }

    /**
     * R8: nudge learners who started but have not been active for the configured number of days. Run daily.
     *
     * @param int $courseid
     * @param int|null $now Current time (tests and the dev CLI simulate a later day).
     * @return int[] Learners nudged.
     */
    public static function nudge_stalled(int $courseid, ?int $now = null): array {
        global $DB;
        $now = $now ?? time();
        $days = (int)(get_config('local_afirst_loop', 'stalldays') ?: 14);
        if (!notifier::enabled_for($courseid) || $days <= 0) {
            return [];
        }
        $learners = self::learners($courseid);
        $last = self::last_activity($courseid, array_keys($learners));
        $course = get_course($courseid);
        $modinfo = get_fast_modinfo($course);
        $nudged = [];
        foreach ($last as $userid => $info) {
            $sent = $DB->get_record('local_afirst_loop_sent',
                ['courseid' => $courseid, 'userid' => $userid, 'cmid' => 0, 'kind' => self::KIND_STALLED]);
            if ($sent && $info['time'] > (int)$sent->timecreated) {
                $DB->delete_records('local_afirst_loop_sent', ['id' => $sent->id]); // They came back: a later stall counts.
                $sent = null;
            }
            if ($sent || $info['time'] > $now - $days * DAYSECS) {
                continue;
            }
            $cm = null;
            if ($info['cmid']) {
                try {
                    $cm = $modinfo->get_cm($info['cmid']);
                } catch (\moodle_exception $e) {
                    $cm = null;
                }
                if ($cm && (!$cm->visible || !$cm->visibleoncoursepage)) {
                    $cm = null; // Never point a learner to an activity hidden from them.
                }
            }
            $a = (object)[
                'course' => format_string($course->fullname, true, ['escape' => false]),
                'days' => (int)floor(($now - $info['time']) / DAYSECS),
                'activity' => $cm ? format_string($cm->name, true, ['context' => $cm->context, 'escape' => false]) : '',
            ];
            $url = $cm ? $cm->url : new \moodle_url('/course/view.php', ['id' => $courseid]);
            notifier::send('opened', $learners[$userid], $course, get_string('msg:stalled:subject', 'local_afirst_loop', $a),
                get_string($cm ? 'msg:stalled:body' : 'msg:stalled:bodynoactivity', 'local_afirst_loop', $a), $url,
                $cm ? $a->activity : $a->course);
            $DB->insert_record('local_afirst_loop_sent', (object)['courseid' => $courseid, 'userid' => $userid, 'cmid' => 0,
                'kind' => self::KIND_STALLED, 'timecreated' => $now]);
            $nudged[] = $userid;
        }
        return $nudged;
    }

    /**
     * Everything a teacher's weekly digest reports, for one course and period.
     *
     * @param int $courseid
     * @param int $since Start of the period.
     * @param int|null $now End of the period.
     * @return array fasttracked, reviewed, early, needsupport, stalled (user id => name lists), reports (open, new),
     *     assistant (questions, declined, topmodules => [module => [questions, declined]]).
     */
    public static function build(int $courseid, int $since, ?int $now = null): array {
        global $DB;
        $now = $now ?? time();
        $learners = self::learners($courseid);
        $name = fn(int $userid) => isset($learners[$userid]) ? fullname($learners[$userid]) : null;
        $sentby = function(string $kind) use ($DB, $courseid, $since, $name): array {
            $ids = $DB->get_fieldset_select('local_afirst_loop_sent', 'DISTINCT userid',
                'courseid = ? AND kind = ? AND timecreated >= ?', [$courseid, $kind, $since]);
            return array_values(array_filter(array_map(fn($id) => $name((int)$id), $ids)));
        };
        $days = (int)(get_config('local_afirst_loop', 'stalldays') ?: 14);
        $stalled = [];
        foreach (self::last_activity($courseid, array_keys($learners)) as $userid => $info) {
            if ($days > 0 && $info['time'] <= $now - $days * DAYSECS) {
                $stalled[] = $name($userid) . ' (' . (int)floor(($now - $info['time']) / DAYSECS) . ' d)';
            }
        }

        $data = [
            'fasttracked' => $sentby(notifier::KIND_FASTTRACK),
            'reviewed' => $sentby(notifier::KIND_REVIEW),
            'early' => $sentby(assistant_signals::KIND_EARLY),
            'needsupport' => $sentby(notifier::KIND_SUPPORT),
            'stalled' => $stalled,
            'reports' => ['new' => 0, 'open' => 0],
            'assistant' => ['questions' => 0, 'declined' => 0, 'topmodules' => []],
        ];
        $dbman = $DB->get_manager();
        if ($dbman->table_exists('block_afirst_escalation')) {
            $data['reports']['new'] = $DB->count_records_select('block_afirst_escalation', 'courseid = ? AND timecreated >= ?',
                [$courseid, $since]);
            $data['reports']['open'] = $DB->count_records('block_afirst_escalation', ['courseid' => $courseid,
                'status' => 'open']);
        }
        if ($dbman->table_exists('block_afirst_log')) {
            $rows = $DB->get_records_select('block_afirst_log', 'courseid = ? AND timecreated >= ? AND timecreated <= ?',
                [$courseid, $since, $now], 'id', 'id, contextmodule, citations, refused, refusalreason');
            $modules = [];
            foreach ($rows as $row) {
                $data['assistant']['questions']++;
                $declined = (bool)$row->refused;
                $data['assistant']['declined'] += (int)$declined;
                $module = assistant_signals::module_of_log($row) ?? '-';
                $modules[$module] ??= [0, 0];
                $modules[$module][0]++;
                $modules[$module][1] += (int)$declined;
            }
            uasort($modules, fn($a, $b) => [$b[0], $b[1]] <=> [$a[0], $a[1]]);
            $data['assistant']['topmodules'] = array_slice($modules, 0, 3, true);
        }
        return $data;
    }

    /**
     * Plain-text digest body.
     *
     * @param array $data From build().
     * @return string
     */
    public static function render(array $data): string {
        $list = fn(array $names) => $names ? count($names) . ': ' . implode(', ', $names) : get_string('none');
        $lines = [
            get_string('digest:fasttracked', 'local_afirst_loop') . ' ' . $list($data['fasttracked']),
            get_string('digest:reviewed', 'local_afirst_loop') . ' ' . $list($data['reviewed']),
            get_string('digest:early', 'local_afirst_loop') . ' ' . $list($data['early']),
            get_string('digest:needsupport', 'local_afirst_loop') . ' ' . $list($data['needsupport']),
            get_string('digest:stalled', 'local_afirst_loop') . ' ' . $list($data['stalled']),
            get_string('digest:reports', 'local_afirst_loop', (object)$data['reports']),
            get_string('digest:assistant', 'local_afirst_loop', (object)[
                'questions' => $data['assistant']['questions'], 'declined' => $data['assistant']['declined']]),
        ];
        $top = [];
        foreach ($data['assistant']['topmodules'] as $module => [$q, $d]) {
            $top[] = get_string('digest:module', 'local_afirst_loop', (object)['module' => $module, 'questions' => $q,
                'declined' => $d]);
        }
        if ($top) {
            $lines[] = get_string('digest:topmodules', 'local_afirst_loop') . ' ' . implode('; ', $top);
        }
        return implode("\n", $lines);
    }

    /**
     * Send the weekly digest for one course to everyone who receives alerts there. Run weekly.
     *
     * @param int $courseid
     * @param int|null $now
     * @return int Teachers sent to.
     */
    public static function send_weekly(int $courseid, ?int $now = null): int {
        if (!notifier::enabled_for($courseid) || !get_config('local_afirst_loop', 'digest')) {
            return 0;
        }
        $now = $now ?? time();
        $course = get_course($courseid);
        $body = self::render(self::build($courseid, $now - WEEKSECS, $now));
        $a = (object)['course' => format_string($course->fullname, true, ['escape' => false])];
        $url = new \moodle_url('/blocks/afirst_assistant/report.php', ['courseid' => $courseid]);
        $sent = 0;
        foreach (get_enrolled_users(\context_course::instance($courseid), 'local/afirst_loop:receivealerts') as $teacher) {
            notifier::send('digest', $teacher, $course, get_string('digest:subject', 'local_afirst_loop', $a), $body, $url,
                get_string('reviewqueue', 'local_afirst_loop'));
            $sent++;
        }
        return $sent;
    }

    /**
     * Course ids the loop is switched on for.
     *
     * @return int[]
     */
    public static function courses(): array {
        return array_values(array_filter(array_map('intval',
            explode(',', (string)get_config('local_afirst_loop', 'courses')))));
    }
}
