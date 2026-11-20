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
 * The Act step of the adaptive loop: tell the learner what a grade or a completion has just opened, and why.
 *
 * The rules themselves stay in Moodle's restrict-access conditions (course-setup/apply_course_config.php). This class
 * reads them: after a quiz is graded or an activity completed, any activity whose conditions refer to it and that has
 * just become available to the learner is announced once, in one message. A retake still below the support threshold
 * alerts the course's teachers (rule R7 in the design note).
 *
 * @package    local_afirst_loop
 * @copyright  2026 Africa CDC
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class notifier {

    /** @var string Activity opened by a module diagnostic score (R1). */
    public const KIND_FASTTRACK = 'fasttrack';
    /** @var string Review unit opened by a low Knowledge Check (R2). */
    public const KIND_REVIEW = 'review';
    /** @var string Retake opened by finishing the review (R3). */
    public const KIND_RETAKE = 'retake';
    /** @var string Anything else opened, e.g. a Knowledge Check after the module content (R4). */
    public const KIND_OPENED = 'opened';
    /** @var string Teacher alert: retake still below the threshold (R7). */
    public const KIND_SUPPORT = 'support';

    /**
     * Course-modules and grade items an availability tree depends on.
     *
     * @param array $tree Decoded availability JSON.
     * @return array{cms: int[], gradeitems: int[]}
     */
    public static function references(array $tree): array {
        $out = ['cms' => [], 'gradeitems' => []];
        if (($tree['type'] ?? '') === 'completion' && isset($tree['cm'])) {
            $out['cms'][] = (int)$tree['cm'];
        }
        if (($tree['type'] ?? '') === 'grade' && isset($tree['id'])) {
            $out['gradeitems'][] = (int)$tree['id'];
        }
        foreach ($tree['c'] ?? [] as $child) {
            $sub = self::references((array)$child);
            $out['cms'] = array_merge($out['cms'], $sub['cms']);
            $out['gradeitems'] = array_merge($out['gradeitems'], $sub['gradeitems']);
        }
        $out['cms'] = array_values(array_unique($out['cms']));
        $out['gradeitems'] = array_values(array_unique($out['gradeitems']));
        return $out;
    }

    /**
     * What kind of opening this is, from the A-FIRST ID numbers set by the course-setup scripts.
     *
     * @param string $openedidnumber ID number of the activity that opened.
     * @param string $triggeridnumber ID number of the graded or completed activity.
     * @return string One of the KIND_ constants.
     */
    public static function kind(string $openedidnumber, string $triggeridnumber): string {
        if (str_starts_with($openedidnumber, 'AFIRST-ML-')) {
            return self::KIND_REVIEW;
        }
        if (str_starts_with($openedidnumber, 'AFIRST-RETAKE-')) {
            return self::KIND_RETAKE;
        }
        if (str_starts_with($triggeridnumber, 'AFIRST-DIAG-')) {
            return self::KIND_FASTTRACK;
        }
        return self::KIND_OPENED;
    }

    /**
     * Whether the loop is switched on for a course (plugin setting: comma-separated course ids).
     *
     * @param int $courseid
     * @return bool
     */
    public static function enabled_for(int $courseid): bool {
        $ids = array_filter(array_map('intval', explode(',', (string)get_config('local_afirst_loop', 'courses'))));
        return in_array($courseid, $ids, true);
    }

    /**
     * React to a graded quiz or a completed activity.
     *
     * @param int $courseid
     * @param int $userid The learner.
     * @param int $triggercmid The graded or completed course-module.
     * @param bool $graded True for a graded quiz attempt (enables the R7 teacher alert).
     * @return array Sent feedback: list of [kind, opened cmids] (for tests and the CLI).
     */
    public static function process(int $courseid, int $userid, int $triggercmid, bool $graded): array {
        global $DB;
        if (!self::enabled_for($courseid)) {
            return [];
        }
        $course = get_course($courseid);
        get_fast_modinfo($courseid, 0, true); // Availability has just changed: reset the course cache.
        $modinfo = get_fast_modinfo($course, $userid);
        try {
            $trigger = $modinfo->get_cm($triggercmid);
        } catch (\moodle_exception $e) {
            return [];
        }
        $gradeitems = $DB->get_records_select('grade_items',
            "courseid = ? AND itemtype = 'mod' AND itemmodule = ? AND iteminstance = ?",
            [$courseid, $trigger->modname, $trigger->instance], 'itemnumber', 'id, grademin, grademax');
        $score = self::percent($gradeitems, $userid);

        // 1. What has this just opened for the learner?
        $bykind = [];
        foreach ($modinfo->get_cms() as $cm) {
            if ((int)$cm->id === $triggercmid || empty($cm->availability) || !$cm->visible || !$cm->uservisible
                    || !$cm->available) {
                continue;
            }
            $refs = self::references((array)json_decode($cm->availability, true));
            if (!in_array($triggercmid, $refs['cms'], true)
                    && !array_intersect(array_map('intval', array_keys($gradeitems)), $refs['gradeitems'])) {
                continue;
            }
            $kind = self::kind((string)$cm->idnumber, (string)$trigger->idnumber);
            if ($DB->record_exists('local_afirst_loop_sent',
                    ['courseid' => $courseid, 'userid' => $userid, 'cmid' => $cm->id, 'kind' => $kind])) {
                continue;
            }
            $bykind[$kind][] = $cm;
        }
        $sent = [];
        foreach ($bykind as $kind => $cms) {
            self::send_learner($course, $userid, $trigger, $cms, $kind, $score);
            foreach ($cms as $cm) {
                self::record($courseid, $userid, (int)$cm->id, $kind);
            }
            $sent[] = [$kind, array_map(fn($cm) => (int)$cm->id, $cms)];
        }

        // 2. R7: still below the threshold after the review and the retake: tell the teachers once.
        $below = (float)(get_config('local_afirst_loop', 'supportbelow') ?: 70);
        if ($graded && $score !== null && $score < $below && str_starts_with((string)$trigger->idnumber, 'AFIRST-RETAKE-')
                && !$DB->record_exists('local_afirst_loop_sent',
                    ['courseid' => $courseid, 'userid' => $userid, 'cmid' => $triggercmid, 'kind' => self::KIND_SUPPORT])) {
            self::send_teachers($course, $userid, $trigger, $score, $below);
            self::record($courseid, $userid, $triggercmid, self::KIND_SUPPORT);
            $sent[] = [self::KIND_SUPPORT, [$triggercmid]];
        }
        return $sent;
    }

    /**
     * The learner's final grade on the first grade item, as a percentage of its range.
     *
     * @param array $gradeitems Grade item records (id, grademin, grademax).
     * @param int $userid
     * @return float|null
     */
    private static function percent(array $gradeitems, int $userid): ?float {
        global $DB;
        $item = reset($gradeitems);
        if (!$item) {
            return null;
        }
        $final = $DB->get_field('grade_grades', 'finalgrade', ['itemid' => $item->id, 'userid' => $userid]);
        $range = (float)$item->grademax - (float)$item->grademin;
        if ($final === false || $final === null || $range <= 0) {
            return null;
        }
        return round(100 * ((float)$final - (float)$item->grademin) / $range);
    }

    /**
     * Remember that feedback was sent.
     *
     * @param int $courseid
     * @param int $userid
     * @param int $cmid
     * @param string $kind
     */
    private static function record(int $courseid, int $userid, int $cmid, string $kind): void {
        global $DB;
        $DB->insert_record('local_afirst_loop_sent', (object)['courseid' => $courseid, 'userid' => $userid, 'cmid' => $cmid,
            'kind' => $kind, 'timecreated' => time()]);
    }

    /**
     * One message to the learner for everything one event opened.
     *
     * @param \stdClass $course
     * @param int $userid
     * @param \cm_info $trigger
     * @param \cm_info[] $cms
     * @param string $kind
     * @param float|null $score
     */
    private static function send_learner(\stdClass $course, int $userid, \cm_info $trigger, array $cms, string $kind,
            ?float $score): void {
        $a = (object)[
            'trigger' => format_string($trigger->name, true, ['context' => $trigger->context, 'escape' => false]),
            'score' => $score === null ? '' : format_float($score, 0),
            'list' => implode(', ', array_map(fn($cm) => format_string($cm->name, true, ['context' => $cm->context, 'escape' => false]), $cms)),
            'course' => format_string($course->fullname, true, ['escape' => false]),
        ];
        $first = reset($cms);
        self::send('opened', \core_user::get_user($userid), $course,
            get_string("msg:{$kind}:subject", 'local_afirst_loop', $a),
            get_string("msg:{$kind}:body", 'local_afirst_loop', $a),
            $first->url, format_string($first->name, true, ['context' => $first->context, 'escape' => false]));
    }

    /**
     * R7 alert to everyone who can receive it in the course (teachers by default), except the learner.
     *
     * @param \stdClass $course
     * @param int $userid
     * @param \cm_info $trigger
     * @param float $score
     * @param float $below
     */
    private static function send_teachers(\stdClass $course, int $userid, \cm_info $trigger, float $score, float $below): void {
        $learner = \core_user::get_user($userid);
        $a = (object)[
            'learner' => fullname($learner),
            'trigger' => format_string($trigger->name, true, ['context' => $trigger->context, 'escape' => false]),
            'score' => format_float($score, 0),
            'below' => format_float($below, 0),
            'course' => format_string($course->fullname, true, ['escape' => false]),
        ];
        $url = new \moodle_url('/mod/quiz/report.php', ['id' => $trigger->id]);
        $teachers = get_enrolled_users(\context_course::instance($course->id), 'local/afirst_loop:receivealerts');
        foreach ($teachers as $teacher) {
            if ((int)$teacher->id === $userid) {
                continue;
            }
            self::send('needssupport', $teacher, $course,
                get_string('msg:support:subject', 'local_afirst_loop', $a),
                get_string('msg:support:body', 'local_afirst_loop', $a), $url, $a->trigger);
        }
    }

    /**
     * Send one notification.
     *
     * @param string $name Message provider.
     * @param \stdClass $to
     * @param \stdClass $course
     * @param string $subject
     * @param string $body
     * @param \moodle_url|null $url
     * @param string $urlname
     */
    public static function send(string $name, \stdClass $to, \stdClass $course, string $subject, string $body,
            ?\moodle_url $url, string $urlname): void {
        $message = new \core\message\message();
        $message->component = 'local_afirst_loop';
        $message->name = $name;
        $message->userfrom = \core_user::get_noreply_user();
        $message->userto = $to;
        $message->subject = $subject;
        $message->fullmessage = $body;
        $message->fullmessageformat = FORMAT_PLAIN;
        $message->fullmessagehtml = '<p>' . s($body) . '</p>';
        $message->smallmessage = $body;
        $message->notification = 1;
        $message->courseid = $course->id;
        if ($url) {
            $message->contexturl = $url->out(false);
            $message->contexturlname = $urlname;
        }
        message_send($message);
    }
}
