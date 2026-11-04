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

use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\writer;
use core_privacy\tests\provider_testcase;

/**
 * Tests for the privacy provider.
 *
 * @package    block_afirst_assistant
 * @category   test
 * @copyright  2026 Africa CDC
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[\PHPUnit\Framework\Attributes\CoversClass(\block_afirst_assistant\privacy\provider::class)]
final class provider_test extends provider_testcase {

    /**
     * Log one question (and optionally a report) for a user.
     *
     * @param int $courseid
     * @param int $userid
     * @param bool $report
     * @return int Log id.
     */
    private function add_log(int $courseid, int $userid, bool $report = false): int {
        global $DB;
        $logid = $DB->insert_record('block_afirst_log', (object) [
            'courseid' => $courseid, 'userid' => $userid, 'question' => "Question from $userid", 'answer' => 'A',
            'refused' => 0, 'timecreated' => time(),
        ]);
        if ($report) {
            $DB->insert_record('block_afirst_escalation', (object) [
                'logid' => $logid, 'courseid' => $courseid, 'userid' => $userid, 'reason' => 'other', 'status' => 'open',
                'timecreated' => time(),
            ]);
        }
        return $logid;
    }

    public function test_export_and_delete_for_one_user(): void {
        global $DB;
        $this->resetAfterTest();
        $course = $this->getDataGenerator()->create_course();
        $context = \context_course::instance($course->id);
        $alice = $this->getDataGenerator()->create_user();
        $bob = $this->getDataGenerator()->create_user();
        $this->add_log($course->id, $alice->id, true);
        $this->add_log($course->id, $bob->id, true);

        $contextlist = provider::get_contexts_for_userid($alice->id);
        $this->assertEquals([$context->id], $contextlist->get_contextids());

        $approved = new approved_contextlist($alice, 'block_afirst_assistant', [$context->id]);
        provider::export_user_data($approved);
        $data = writer::with_context($context)->get_data([get_string('pluginname', 'block_afirst_assistant')]);
        $this->assertSame("Question from {$alice->id}", $data->questions[0]->question);
        $this->assertCount(1, $data->reports);

        provider::delete_data_for_user($approved);
        $this->assertEquals(0, $DB->count_records('block_afirst_log', ['userid' => $alice->id]));
        $this->assertEquals(0, $DB->count_records('block_afirst_escalation', ['userid' => $alice->id]));
        $this->assertEquals(1, $DB->count_records('block_afirst_log', ['userid' => $bob->id]));
        $this->assertEquals(1, $DB->count_records('block_afirst_escalation', ['userid' => $bob->id]));
    }

    public function test_teacher_deletion_anonymises_sources_but_keeps_them(): void {
        global $DB;
        $this->resetAfterTest();
        $course = $this->getDataGenerator()->create_course();
        $context = \context_course::instance($course->id);
        $teacher = $this->getDataGenerator()->create_user();
        $sourceid = $DB->insert_record('block_afirst_source', (object) [
            'courseid' => $course->id, 'title' => 'Module 1.1', 'filename' => 'm.txt', 'contenthash' => sha1('m'),
            'status' => 'approved', 'approvedby' => $teacher->id, 'timeapproved' => time(),
            'usermodified' => $teacher->id, 'timecreated' => time(), 'timemodified' => time(),
        ]);

        $this->assertEquals([$context->id], provider::get_contexts_for_userid($teacher->id)->get_contextids());
        provider::delete_data_for_user(new approved_contextlist($teacher, 'block_afirst_assistant', [$context->id]));

        $source = $DB->get_record('block_afirst_source', ['id' => $sourceid]);
        $this->assertNull($source->approvedby);
        $this->assertEquals(0, $source->usermodified);
        $this->assertSame('approved', $source->status);
    }

    public function test_delete_all_in_course(): void {
        global $DB;
        $this->resetAfterTest();
        $course = $this->getDataGenerator()->create_course();
        $other = $this->getDataGenerator()->create_course();
        $user = $this->getDataGenerator()->create_user();
        $this->add_log($course->id, $user->id, true);
        $this->add_log($other->id, $user->id);

        provider::delete_data_for_all_users_in_context(\context_course::instance($course->id));

        $this->assertEquals(0, $DB->count_records('block_afirst_log', ['courseid' => $course->id]));
        $this->assertEquals(1, $DB->count_records('block_afirst_log', ['courseid' => $other->id]));
    }
}
