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
 * Assistant signals in the adaptive loop (OODA design note, step 2).
 *
 * R5 early review: when a learner has asked the course assistant several questions about one sub-module, or several
 * of them could not be supported by the course text, before taking its Knowledge Check, the learner joins the
 * module's early-review group. The review unit's access rule (course-setup, "early_review": true) opens for that
 * group, and the learner is told. It only ever offers help; assessment access still depends on quiz results.
 *
 * R6 trust check: a learner who marks several answers as not helpful is flagged to the course's teachers.
 *
 * Only counts, reasons and module codes are used, never the text of a question.
 *
 * @package    local_afirst_loop
 * @copyright  2026 Africa CDC
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class assistant_signals {

    /** @var string Review unit offered early (R5). */
    public const KIND_EARLY = 'early';
    /** @var string Teacher told about repeated "not helpful" ratings (R6). */
    public const KIND_TRUST = 'trust';

    /** @var string[] Refusals meaning the course text did not support an answer (as opposed to off-topic questions). */
    public const UNSUPPORTED_REASONS = ['unsupported', 'model_declined', 'uncited'];

    /**
     * The sub-module code a question was about: the module the learner was in, else the first module the answer cited.
     *
     * @param string|null $contextmodule Module code recorded with the question, e.g. "2.1".
     * @param string[] $citedtitles Titles of the cited sources, e.g. "Module 2.1 Data Wrangling ...".
     * @return string|null
     */
    public static function module_of(?string $contextmodule, array $citedtitles): ?string {
        if ($contextmodule !== null && preg_match('/^\d+\.\d+$/', trim($contextmodule))) {
            return trim($contextmodule);
        }
        foreach ($citedtitles as $title) {
            if (preg_match('/\bModule\s+(\d+\.\d+)/i', (string)$title, $m)) {
                return $m[1];
            }
        }
        return null;
    }

    /**
     * Whether recent assistant use on a module is enough to offer its review early.
     *
     * @param int $questions Questions about the module in the window.
     * @param int $unsupported Of those, how many the course text could not support.
     * @param int $minquestions Threshold for questions.
     * @param int $minunsupported Threshold for unsupported ones.
     * @return bool
     */
    public static function warrants_early_review(int $questions, int $unsupported, int $minquestions,
            int $minunsupported): bool {
        return ($minquestions > 0 && $questions >= $minquestions) || ($minunsupported > 0 && $unsupported >= $minunsupported);
    }

    /**
     * Module code of one assistant log row.
     *
     * @param \stdClass $log Row of block_afirst_log (contextmodule, citations).
     * @return string|null
     */
    private static function module_of_log(\stdClass $log): ?string {
        global $DB;
        $titles = [];
        $citations = json_decode((string)$log->citations, true) ?: [];
        $ids = array_map(fn($c) => (int)$c['sourceid'], array_filter($citations, fn($c) => !empty($c['cited'])))
            ?: array_map(fn($c) => (int)$c['sourceid'], $citations);
        if ($ids && ($log->contextmodule === null || $log->contextmodule === '')) {
            [$insql, $params] = $DB->get_in_or_equal(array_values(array_unique($ids)));
            $titles = $DB->get_fieldset_select('block_afirst_source', 'title', "id $insql", $params);
        }
        return self::module_of($log->contextmodule, $titles);
    }

    /**
     * R5, run after every question to the assistant.
     *
     * @param int $courseid
     * @param int $userid
     * @param int $logid
     * @return string|null Module code whose review was offered, or null.
     */
    public static function question_asked(int $courseid, int $userid, int $logid): ?string {
        global $DB, $CFG;
        if (!notifier::enabled_for($courseid)) {
            return null;
        }
        $log = $DB->get_record('block_afirst_log', ['id' => $logid, 'courseid' => $courseid, 'userid' => $userid]);
        if (!$log || !($module = self::module_of_log($log))) {
            return null;
        }
        $group = $DB->get_record('groups', ['courseid' => $courseid, 'idnumber' => "AFIRST-EARLY-$module"]);
        if (!$group) {
            return null; // Early review is not configured for this course or module.
        }
        get_fast_modinfo($courseid, 0, true);
        $modinfo = get_fast_modinfo($courseid, $userid);
        $review = null;
        foreach ($modinfo->get_cms() as $cm) {
            if ($cm->idnumber === "AFIRST-ML-$module") {
                $review = $cm;
                break;
            }
        }
        if (!$review || !$review->visible || $review->available
                || $DB->record_exists('local_afirst_loop_sent', ['courseid' => $courseid, 'userid' => $userid,
                    'cmid' => $review->id, 'kind' => self::KIND_EARLY])) {
            return null; // No review unit, not yet approved, already open, or already offered.
        }
        // Only before the Knowledge Check: once it is graded, R2 decides.
        $refs = notifier::references((array)json_decode((string)$review->availability, true));
        if ($refs['gradeitems']) {
            [$insql, $params] = $DB->get_in_or_equal($refs['gradeitems']);
            $params[] = $userid;
            if ($DB->record_exists_select('grade_grades', "itemid $insql AND userid = ? AND finalgrade IS NOT NULL", $params)) {
                return null;
            }
        }

        $days = max(1, (int)(get_config('local_afirst_loop', 'earlydays') ?: 7));
        $rows = $DB->get_records_select('block_afirst_log', 'courseid = ? AND userid = ? AND timecreated >= ?',
            [$courseid, $userid, time() - $days * DAYSECS], 'id', 'id, contextmodule, citations, refused, refusalreason');
        $questions = 0;
        $unsupported = 0;
        foreach ($rows as $row) {
            if (self::module_of_log($row) !== $module) {
                continue;
            }
            $questions++;
            if ($row->refused && in_array($row->refusalreason, self::UNSUPPORTED_REASONS, true)) {
                $unsupported++;
            }
        }
        $minq = (int)(get_config('local_afirst_loop', 'earlyquestions') ?: 3);
        $minu = (int)(get_config('local_afirst_loop', 'earlyunsupported') ?: 2);
        if (!self::warrants_early_review($questions, $unsupported, $minq, $minu)) {
            return null;
        }

        require_once($CFG->dirroot . '/group/lib.php');
        groups_add_member($group->id, $userid, 'local_afirst_loop');
        $DB->insert_record('local_afirst_loop_sent', (object)['courseid' => $courseid, 'userid' => $userid,
            'cmid' => $review->id, 'kind' => self::KIND_EARLY, 'timecreated' => time()]);
        $course = get_course($courseid);
        $a = (object)[
            'module' => $module,
            'list' => format_string($review->name, true, ['context' => $review->context, 'escape' => false]),
            'course' => format_string($course->fullname, true, ['escape' => false]),
        ];
        notifier::send('opened', \core_user::get_user($userid), $course,
            get_string('msg:early:subject', 'local_afirst_loop', $a), get_string('msg:early:body', 'local_afirst_loop', $a),
            $review->url, $a->list);
        return $module;
    }

    /**
     * R6, run after a learner rates an answer.
     *
     * @param int $courseid
     * @param int $userid The learner who rated.
     * @param int $rating 1, -1 or 0.
     * @return bool Whether the teachers were told.
     */
    public static function answer_rated(int $courseid, int $userid, int $rating): bool {
        global $DB;
        if ($rating !== -1 || !notifier::enabled_for($courseid)) {
            return false;
        }
        $min = (int)(get_config('local_afirst_loop', 'trustdown') ?: 2);
        $down = $DB->count_records('block_afirst_log', ['courseid' => $courseid, 'userid' => $userid, 'rating' => -1]);
        if ($down < $min || $DB->record_exists('local_afirst_loop_sent',
                ['courseid' => $courseid, 'userid' => $userid, 'cmid' => 0, 'kind' => self::KIND_TRUST])) {
            return false;
        }
        $DB->insert_record('local_afirst_loop_sent', (object)['courseid' => $courseid, 'userid' => $userid, 'cmid' => 0,
            'kind' => self::KIND_TRUST, 'timecreated' => time()]);
        $course = get_course($courseid);
        $a = (object)[
            'learner' => fullname(\core_user::get_user($userid)),
            'count' => $down,
            'course' => format_string($course->fullname, true, ['escape' => false]),
        ];
        $url = new \moodle_url('/blocks/afirst_assistant/report.php', ['courseid' => $courseid]);
        foreach (get_enrolled_users(\context_course::instance($courseid), 'local/afirst_loop:receivealerts') as $teacher) {
            if ((int)$teacher->id !== $userid) {
                notifier::send('needssupport', $teacher, $course, get_string('msg:trust:subject', 'local_afirst_loop', $a),
                    get_string('msg:trust:body', 'local_afirst_loop', $a), $url,
                    get_string('reviewqueue', 'local_afirst_loop'));
            }
        }
        return true;
    }
}
