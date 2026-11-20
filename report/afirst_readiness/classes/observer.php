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

namespace report_afirst_readiness;

use report_afirst_readiness\local\readiness_report;

/**
 * Tell the learner their readiness result the moment they finish the scenario.
 *
 * @package    report_afirst_readiness
 * @copyright  2026 Africa CDC
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class observer {

    /**
     * A Lesson attempt ended: if the Lesson is a readiness scenario, send the result.
     *
     * @param \mod_lesson\event\lesson_ended $event
     */
    public static function lesson_ended(\mod_lesson\event\lesson_ended $event): void {
        try {
            self::send_result((int)$event->contextinstanceid, (int)$event->relateduserid ?: (int)$event->userid);
        } catch (\Throwable $e) {
            debugging('report_afirst_readiness: ' . $e->getMessage(), DEBUG_DEVELOPER);
        }
    }

    /**
     * Send one learner their result for a scenario Lesson.
     *
     * @param int $lessoncmid
     * @param int $userid
     * @return string|null The status sent, or null when the Lesson is not a readiness scenario.
     */
    public static function send_result(int $lessoncmid, int $userid): ?string {
        global $DB;
        $record = $DB->get_record('report_afirst_readiness', ['lessoncmid' => $lessoncmid], '*', IGNORE_MULTIPLE);
        if (!$record) {
            return null;
        }
        $row = (new readiness_report($record))->row($userid);
        $course = get_course($record->courseid);
        $a = (object)[
            'course' => format_string($course->fullname, true, ['escape' => false]),
            'status' => get_string('status:' . $row['status'], 'report_afirst_readiness'),
        ];
        $url = new \moodle_url('/report/afirst_readiness/mine.php', ['id' => $course->id]);
        $body = get_string('msg:result:body', 'report_afirst_readiness', $a);

        $message = new \core\message\message();
        $message->component = 'report_afirst_readiness';
        $message->name = 'result';
        $message->userfrom = \core_user::get_noreply_user();
        $message->userto = \core_user::get_user($userid);
        $message->subject = get_string('msg:result:subject', 'report_afirst_readiness', $a);
        $message->fullmessage = $body;
        $message->fullmessageformat = FORMAT_PLAIN;
        $message->fullmessagehtml = '<p>' . s($body) . '</p>';
        $message->smallmessage = $body;
        $message->notification = 1;
        $message->courseid = $course->id;
        $message->contexturl = $url->out(false);
        $message->contexturlname = get_string('mine', 'report_afirst_readiness');
        message_send($message);
        return $row['status'];
    }
}
