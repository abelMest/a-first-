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

use local_afirst_loop\local\assistant_signals;

/**
 * Tests for the slow-connection (R9) and content-gap (R10) signals.
 *
 * @package    local_afirst_loop
 * @copyright  2026 Africa CDC
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \local_afirst_loop\local\assistant_signals
 */
final class signals_test extends \advanced_testcase {

    public function test_median_ignores_missing_readings(): void {
        $this->assertNull(assistant_signals::median_downlink([0, 0, 0.4, 0.6]), 'fewer than 3 real readings');
        $this->assertEqualsWithDelta(0.6, assistant_signals::median_downlink([0.4, 0.6, 2.0]), 0.001);
        $this->assertEqualsWithDelta(0.5, assistant_signals::median_downlink([0.4, 0, 0.6, 0.3, 9.0]), 0.001);
    }

    public function test_same_question_is_recognised(): void {
        $this->assertSame('what is an epidemic curve used for',
            assistant_signals::normalise_question("  What is an EPIDEMIC curve used for?? "));
        $this->assertSame(assistant_signals::normalise_question('Qu\'est-ce qu\'une courbe épidémique ?'),
            assistant_signals::normalise_question('qu est ce qu une courbe épidémique'));
        $this->assertSame('', assistant_signals::normalise_question('?!'));
    }

    public function test_gap_alerts_reviewers_once_after_enough_learners(): void {
        global $DB;
        $this->resetAfterTest();
        $this->preventResetByRollback();
        $course = $this->getDataGenerator()->create_course();
        $teacher = $this->getDataGenerator()->create_and_enrol($course, 'editingteacher');
        set_config('courses', (string)$course->id, 'local_afirst_loop');
        set_config('gaplearners', 3, 'local_afirst_loop');
        $sink = $this->redirectMessages();

        $logids = [];
        foreach (['What is an epidemic curve used for?', 'what is an epidemic curve used for',
                'What is an epidemic curve used for ?'] as $i => $question) {
            $learner = $this->getDataGenerator()->create_and_enrol($course, 'student');
            $logids[] = $DB->insert_record('block_afirst_log', (object)['courseid' => $course->id, 'userid' => $learner->id,
                'question' => $question, 'refused' => 1, 'refusalreason' => 'no_match', 'timecreated' => time()]);
            $told = assistant_signals::content_gap($course->id, end($logids));
            $this->assertSame($i === 2, $told, 'only the third learner triggers the alert');
        }
        $this->assertFalse(assistant_signals::content_gap($course->id, end($logids)), 'once per question');
        $messages = array_values(array_filter($sink->get_messages(), fn($m) => $m->component === 'local_afirst_loop'));
        $this->assertCount(1, $messages, json_encode(array_map(fn($m) => [$m->component, $m->eventtype, $m->useridto],
            $sink->get_messages())));
        $this->assertSame((int)$teacher->id, (int)$messages[0]->useridto);
        $this->assertStringContainsString('3 learners', $messages[0]->fullmessage);
    }
}
