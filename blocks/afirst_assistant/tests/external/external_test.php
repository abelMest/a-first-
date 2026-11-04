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

namespace block_afirst_assistant\external;

use core_ai\manager;
use core_external\external_api;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/webservice/tests/helpers.php');

/**
 * Tests for the ask and escalate web services: access rules and reviewer notification.
 *
 * @package    block_afirst_assistant
 * @category   test
 * @copyright  2026 Africa CDC
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[\PHPUnit\Framework\Attributes\CoversClass(\block_afirst_assistant\external\ask::class)]
#[\PHPUnit\Framework\Attributes\CoversClass(\block_afirst_assistant\external\escalate::class)]
#[\PHPUnit\Framework\Attributes\CoversClass(\block_afirst_assistant\local\escalation_manager::class)]
final class external_test extends \externallib_advanced_testcase {

    public function test_ask_requires_enrolment(): void {
        $this->resetAfterTest();
        $course = $this->getDataGenerator()->create_course();
        $outsider = $this->getDataGenerator()->create_user();
        $this->setUser($outsider);
        manager::user_policy_accepted($outsider->id, \context_system::instance()->id);

        $this->expectException(\require_login_exception::class);
        ask::execute($course->id, 'What is completeness?');
    }

    public function test_ask_requires_ai_policy_acceptance(): void {
        $this->resetAfterTest();
        $course = $this->getDataGenerator()->create_course();
        $student = $this->getDataGenerator()->create_and_enrol($course, 'student');
        $this->setUser($student);

        $this->expectExceptionMessage(get_string('error:policynotaccepted', 'block_afirst_assistant'));
        ask::execute($course->id, 'What is completeness?');
    }

    public function test_ask_without_sources_refuses(): void {
        $this->resetAfterTest();
        $course = $this->getDataGenerator()->create_course();
        $student = $this->getDataGenerator()->create_and_enrol($course, 'student');
        $this->setUser($student);
        manager::user_policy_accepted($student->id, \context_course::instance($course->id)->id);

        $result = external_api::clean_returnvalue(ask::execute_returns(), ask::execute($course->id, 'What is completeness?'));

        $this->assertSame('refused', $result['status']);
        $this->assertSame('no_match', $result['reason']);
        $this->assertGreaterThan(0, $result['logid']);
    }

    public function test_escalate_only_own_answers_and_notifies_reviewers(): void {
        global $DB;
        $this->resetAfterTest();
        $this->preventResetByRollback();
        $course = $this->getDataGenerator()->create_course();
        $student = $this->getDataGenerator()->create_and_enrol($course, 'student');
        $other = $this->getDataGenerator()->create_and_enrol($course, 'student');
        $teacher = $this->getDataGenerator()->create_and_enrol($course, 'editingteacher');
        $logid = $DB->insert_record('block_afirst_log', (object) [
            'courseid' => $course->id, 'userid' => $student->id, 'question' => 'Q', 'answer' => 'A', 'refused' => 0,
            'timecreated' => time(),
        ]);

        $this->setUser($other);
        try {
            escalate::execute($logid, 'inaccurate');
            $this->fail('Another learner must not report this answer.');
        } catch (\dml_missing_record_exception $e) {
            $this->assertEquals(0, $DB->count_records('block_afirst_escalation'));
        }

        $this->setUser($student);
        $sink = $this->redirectMessages();
        $result = escalate::execute($logid, 'unsafe', 'Wrong threshold');
        $messages = $sink->get_messages();
        $sink->close();

        $escalation = $DB->get_record('block_afirst_escalation', ['id' => $result['escalationid']]);
        $this->assertSame('unsafe', $escalation->reason);
        $this->assertSame('open', $escalation->status);
        $this->assertCount(1, $messages);
        $this->assertEquals($teacher->id, $messages[0]->useridto);
        $this->assertStringNotContainsString(fullname($student), $messages[0]->fullmessage);

        // Reporting the same answer again while the first report is open does not create or notify twice.
        $sink = $this->redirectMessages();
        $again = escalate::execute($logid, 'inaccurate');
        $this->assertSame(0, $sink->count());
        $sink->close();
        $this->assertSame($result['escalationid'], $again['escalationid']);
        $this->assertEquals(1, $DB->count_records('block_afirst_escalation'));
    }

    public function test_escalate_rejects_unknown_reason(): void {
        global $DB;
        $this->resetAfterTest();
        $course = $this->getDataGenerator()->create_course();
        $student = $this->getDataGenerator()->create_and_enrol($course, 'student');
        $logid = $DB->insert_record('block_afirst_log', (object) [
            'courseid' => $course->id, 'userid' => $student->id, 'question' => 'Q', 'refused' => 1, 'timecreated' => time(),
        ]);
        $this->setUser($student);

        $this->expectException(\invalid_parameter_exception::class);
        escalate::execute($logid, 'boring');
    }
}
