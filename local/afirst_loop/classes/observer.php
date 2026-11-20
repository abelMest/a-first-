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

namespace local_afirst_loop;

use local_afirst_loop\local\notifier;

/**
 * Event observers. Feedback must never break a learner's attempt, so failures are only reported to developers.
 *
 * @package    local_afirst_loop
 * @copyright  2026 Africa CDC
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class observer {

    /**
     * A quiz attempt was graded.
     *
     * @param \mod_quiz\event\attempt_graded $event
     */
    public static function attempt_graded(\mod_quiz\event\attempt_graded $event): void {
        self::run((int)$event->courseid, (int)$event->relateduserid, (int)$event->contextinstanceid, true);
    }

    /**
     * An activity's completion changed; only completion (not failure or reset) can open something.
     *
     * @param \core\event\course_module_completion_updated $event
     */
    public static function completion_updated(\core\event\course_module_completion_updated $event): void {
        $state = (int)($event->other['completionstate'] ?? COMPLETION_INCOMPLETE);
        if (!in_array($state, [COMPLETION_COMPLETE, COMPLETION_COMPLETE_PASS], true)) {
            return;
        }
        self::run((int)$event->courseid, (int)$event->relateduserid, (int)$event->contextinstanceid, false);
    }

    /**
     * A learner asked the course assistant a question (R5 early review).
     *
     * @param \block_afirst_assistant\event\question_asked $event
     */
    public static function question_asked(\block_afirst_assistant\event\question_asked $event): void {
        try {
            local\assistant_signals::question_asked((int)$event->courseid, (int)$event->relateduserid, (int)$event->objectid);
        } catch (\Throwable $e) {
            debugging('local_afirst_loop: ' . $e->getMessage(), DEBUG_DEVELOPER);
        }
    }

    /**
     * A learner rated an assistant answer (R6 trust check).
     *
     * @param \block_afirst_assistant\event\answer_rated $event
     */
    public static function answer_rated(\block_afirst_assistant\event\answer_rated $event): void {
        try {
            local\assistant_signals::answer_rated((int)$event->courseid, (int)$event->relateduserid,
                (int)($event->other['rating'] ?? 0));
        } catch (\Throwable $e) {
            debugging('local_afirst_loop: ' . $e->getMessage(), DEBUG_DEVELOPER);
        }
    }

    /**
     * Run the notifier, never throwing.
     *
     * @param int $courseid
     * @param int $userid
     * @param int $cmid
     * @param bool $graded
     */
    private static function run(int $courseid, int $userid, int $cmid, bool $graded): void {
        try {
            notifier::process($courseid, $userid, $cmid, $graded);
        } catch (\Throwable $e) {
            debugging('local_afirst_loop: ' . $e->getMessage(), DEBUG_DEVELOPER);
        }
    }
}
