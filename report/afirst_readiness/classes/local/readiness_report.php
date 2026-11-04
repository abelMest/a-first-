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

namespace report_afirst_readiness\local;

/**
 * Gathers each learner's evidence for one built scenario and applies the rules.
 *
 * @package    report_afirst_readiness
 * @copyright  2026 Africa CDC
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class readiness_report {

    /** @var array Decoded scenario. */
    public readonly array $scenario;

    /** @var array Decoded settings. */
    public readonly array $settings;

    /**
     * Constructor.
     *
     * @param \stdClass $record report_afirst_readiness row.
     */
    public function __construct(public readonly \stdClass $record) {
        $this->scenario = json_decode($record->scenario, true);
        $this->settings = json_decode($record->settings, true);
    }

    /**
     * Results for every learner who can attempt the Lesson (students by default), keyed by user id.
     *
     * @return array<int, array> user, started, completed, critical, domains, knowledge, supervisor, status, reasons.
     */
    public function rows(): array {
        $context = \context_course::instance($this->record->courseid);
        $learners = get_enrolled_users($context, 'mod/lesson:view', 0, 'u.id, u.firstname, u.lastname, u.firstnamephonetic, ' .
            'u.lastnamephonetic, u.middlename, u.alternatename, u.email', 'u.lastname, u.firstname', 0, 0, true);
        // Teachers can also view; only users without grading rights are learners.
        $learners = array_filter($learners, fn($u) => !has_capability('mod/lesson:grade', $context, $u));
        if (!$learners) {
            return [];
        }
        $userids = array_keys($learners);
        $choices = $this->choices($userids);
        $knowledge = $this->knowledge_grades($userids);
        $supervisorcmid = $this->settings['supervisor_cmid'] ?? null;
        $supervisor = $supervisorcmid ? $this->grades_for_cm((int)$supervisorcmid, $userids) : [];

        $rows = [];
        foreach ($learners as $id => $user) {
            $score = scenario::score($this->scenario, $choices[$id] ?? []);
            $input = [
                'started' => !empty($choices[$id]),
                'completed' => $score['completed'],
                'critical' => $score['critical'],
                'domains' => $score['domains'],
                'knowledge' => $knowledge[$id] ?? [],
                'supervisorconfigured' => (bool)$supervisorcmid,
                'supervisor' => $supervisor[$id] ?? null,
            ];
            $rows[$id] = ['user' => $user] + $input + rules::classify($input, $this->settings['thresholds'] ?? []);
        }
        return $rows;
    }

    /**
     * Each learner's choices in their latest Lesson attempt, in order.
     *
     * @param int[] $userids
     * @return array<int, array> user id => list of [node id, option id].
     */
    public function choices(array $userids): array {
        global $DB;
        $map = json_decode($this->record->answermap, true);
        [$insql, $params] = $DB->get_in_or_equal($userids, SQL_PARAMS_NAMED);
        $params['lessonid'] = $this->record->lessonid;
        $attempts = $DB->get_records_select('lesson_attempts', "lessonid = :lessonid AND userid $insql", $params,
            'userid, retry, timeseen, id', 'id, userid, answerid, retry');
        $latest = [];
        foreach ($attempts as $a) {
            $latest[$a->userid] = max($latest[$a->userid] ?? 0, (int)$a->retry);
        }
        $out = [];
        foreach ($attempts as $a) {
            if ((int)$a->retry !== $latest[$a->userid] || !isset($map[$a->answerid])) {
                continue;
            }
            $out[$a->userid][] = [$map[$a->answerid]['node'], $map[$a->answerid]['option']];
        }
        return $out;
    }

    /**
     * Knowledge-check percentages: user id => label => percent|null.
     *
     * @param int[] $userids
     * @return array
     */
    private function knowledge_grades(array $userids): array {
        $out = [];
        foreach ($this->settings['knowledge'] ?? [] as $label => $cmid) {
            $grades = $this->grades_for_cm((int)$cmid, $userids);
            foreach ($userids as $id) {
                $out[$id][(string)$label] = $grades[$id] ?? null;
            }
        }
        return $out;
    }

    /**
     * Final grade as a percentage of the grade range, for one activity.
     *
     * @param int $cmid
     * @param int[] $userids
     * @return array<int, float> user id => percent (users without a grade are absent).
     */
    private function grades_for_cm(int $cmid, array $userids): array {
        global $DB;
        $cm = get_coursemodule_from_id('', $cmid, $this->record->courseid);
        if (!$cm) {
            return [];
        }
        [$insql, $params] = $DB->get_in_or_equal($userids, SQL_PARAMS_NAMED);
        $params += ['courseid' => $this->record->courseid, 'modname' => $cm->modname, 'instance' => $cm->instance];
        $rows = $DB->get_records_sql("
            SELECT gg.userid, gg.finalgrade, gi.grademin, gi.grademax
              FROM {grade_items} gi
              JOIN {grade_grades} gg ON gg.itemid = gi.id
             WHERE gi.courseid = :courseid AND gi.itemtype = 'mod' AND gi.itemmodule = :modname
               AND gi.iteminstance = :instance AND gi.itemnumber = 0 AND gg.finalgrade IS NOT NULL
               AND gg.userid $insql", $params);
        $out = [];
        foreach ($rows as $r) {
            $range = (float)$r->grademax - (float)$r->grademin;
            $out[(int)$r->userid] = $range > 0 ? round(100 * ((float)$r->finalgrade - (float)$r->grademin) / $range, 1) : 0.0;
        }
        return $out;
    }

    /**
     * Human-readable reason.
     *
     * @param array $reason [id, a] from rules::classify().
     * @return string
     */
    public function reason_text(array $reason): string {
        [$id, $a] = $reason;
        $titles = scenario::domain_titles($this->scenario);
        if ($id === 'domainmissing') {
            $a = $titles[$a] ?? $a;
        } else if ($id === 'domain') {
            $a = (object)(['domain' => $titles[$a['domain']] ?? $a['domain']] + $a);
        } else if (is_array($a)) {
            $a = (object)$a;
        }
        return get_string('reason:' . $id, 'report_afirst_readiness', $a);
    }
}
