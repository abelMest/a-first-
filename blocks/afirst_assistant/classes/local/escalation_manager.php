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

namespace block_afirst_assistant\local;

use core\message\message;
use core\url;

/**
 * Learner reports of problematic answers and their human review.
 *
 * @package    block_afirst_assistant
 * @copyright  2026 Africa CDC
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class escalation_manager {

    /** @var string[] Reasons a learner can give. */
    public const REASONS = ['inaccurate', 'incomplete', 'misleading', 'unsafe', 'other'];

    /** @var int Review deadline promised to learners (Capstone Proposal 3.9). */
    public const REVIEW_WINDOW = 48 * HOURSECS;

    /** @var string Awaiting review. */
    public const STATUS_OPEN = 'open';

    /** @var string Reviewed. */
    public const STATUS_RESOLVED = 'resolved';

    /**
     * File a report and notify the course reviewers.
     *
     * @param \stdClass $log The block_afirst_log row being reported.
     * @param string $reason One of REASONS.
     * @param string $comment
     * @return int Escalation id.
     */
    public static function create(\stdClass $log, string $reason, string $comment): int {
        global $DB, $USER;

        // A repeated click or resubmission must not flood reviewers: one open report per answer and learner.
        $existing = $DB->get_field('block_afirst_escalation', 'id',
            ['logid' => $log->id, 'userid' => $USER->id, 'status' => self::STATUS_OPEN], IGNORE_MULTIPLE);
        if ($existing) {
            return (int)$existing;
        }

        $id = $DB->insert_record('block_afirst_escalation', (object) [
            'logid' => $log->id,
            'courseid' => $log->courseid,
            'userid' => $USER->id,
            'reason' => $reason,
            'comment' => $comment !== '' ? $comment : null,
            'status' => self::STATUS_OPEN,
            'timecreated' => time(),
        ]);
        self::notify_reviewers($id, $log, $reason);
        return $id;
    }

    /**
     * Close a report with the reviewer's resolution.
     *
     * @param int $id
     * @param string $resolution What was found and done (logged in the project's decision trail).
     */
    public static function resolve(int $id, string $resolution): void {
        global $DB, $USER;
        $DB->update_record('block_afirst_escalation', (object) [
            'id' => $id,
            'status' => self::STATUS_RESOLVED,
            'reviewerid' => $USER->id,
            'resolution' => $resolution,
            'timeresolved' => time(),
        ]);
    }

    /**
     * Whether an open report has passed its review deadline.
     *
     * @param \stdClass $escalation
     * @param int|null $now
     * @return bool
     */
    public static function is_overdue(\stdClass $escalation, ?int $now = null): bool {
        return $escalation->status === self::STATUS_OPEN
            && ($now ?? time()) > $escalation->timecreated + self::REVIEW_WINDOW;
    }

    /**
     * Message everyone who can review the course. Learner identity is not included.
     *
     * @param int $escalationid
     * @param \stdClass $log
     * @param string $reason
     */
    private static function notify_reviewers(int $escalationid, \stdClass $log, string $reason): void {
        $context = \context_course::instance($log->courseid);
        $reviewers = get_enrolled_users($context, 'block/afirst_assistant:review', 0, 'u.*', null, 0, 0, true);
        if (!$reviewers) {
            $reviewers = get_admins();
        }
        $url = new url('/blocks/afirst_assistant/report.php', ['courseid' => $log->courseid, 'tab' => 'escalations']);
        $a = (object) [
            'reason' => get_string('reason:' . $reason, 'block_afirst_assistant'),
            'question' => shorten_text($log->question, 200),
            'deadline' => userdate(time() + self::REVIEW_WINDOW),
            'url' => $url->out(false),
        ];
        foreach ($reviewers as $reviewer) {
            $message = new message();
            $message->component = 'block_afirst_assistant';
            $message->name = 'escalation';
            $message->userfrom = \core_user::get_noreply_user();
            $message->userto = $reviewer;
            $message->courseid = $log->courseid;
            $message->subject = get_string('escalation:subject', 'block_afirst_assistant', $a);
            $message->fullmessage = get_string('escalation:body', 'block_afirst_assistant', $a);
            $message->fullmessageformat = FORMAT_PLAIN;
            $message->fullmessagehtml = '';
            $message->smallmessage = $message->subject;
            $message->notification = 1;
            $message->contexturl = $a->url;
            $message->contexturlname = get_string('reviewqueue', 'block_afirst_assistant');
            message_send($message);
        }
    }
}
