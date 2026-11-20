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
 * Tests for the feedback loop.
 *
 * @package    local_afirst_loop
 * @copyright  2026 Africa CDC
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \local_afirst_loop\local\notifier
 */
final class notifier_test extends \advanced_testcase {

    public function test_references_walk_nested_trees(): void {
        $tree = ['op' => '&', 'c' => [
            ['type' => 'grade', 'id' => 12, 'max' => 70],
            ['op' => '|', 'c' => [['type' => 'completion', 'cm' => 42, 'e' => 1], ['type' => 'completion', 'cm' => 9, 'e' => 1]]],
        ]];
        $refs = notifier::references($tree);
        $this->assertSame([12], $refs['gradeitems']);
        $this->assertSame([42, 9], $refs['cms']);
        $this->assertSame(['cms' => [], 'gradeitems' => []], notifier::references([]));
    }

    public function test_kind_from_idnumbers(): void {
        $this->assertSame(notifier::KIND_REVIEW, notifier::kind('AFIRST-ML-1.1', ''));
        $this->assertSame(notifier::KIND_RETAKE, notifier::kind('AFIRST-RETAKE-1.1', 'AFIRST-ML-1.1'));
        $this->assertSame(notifier::KIND_FASTTRACK, notifier::kind('', 'AFIRST-DIAG-M2'));
        $this->assertSame(notifier::KIND_OPENED, notifier::kind('', ''));
    }

    public function test_opening_after_completion_is_announced_once(): void {
        global $DB;
        $this->resetAfterTest();
        $this->preventResetByRollback(); // The observer runs after the transaction.
        set_config('enableavailability', 1);
        $course = $this->getDataGenerator()->create_course(['enablecompletion' => 1]);
        $student = $this->getDataGenerator()->create_and_enrol($course, 'student');
        $review = $this->getDataGenerator()->create_module('page', ['course' => $course->id, 'idnumber' => 'AFIRST-ML-1.1',
            'completion' => COMPLETION_TRACKING_MANUAL]);
        $retake = $this->getDataGenerator()->create_module('page', ['course' => $course->id, 'idnumber' => 'AFIRST-RETAKE-1.1',
            'availability' => json_encode(['op' => '&', 'showc' => [true],
                'c' => [['type' => 'completion', 'cm' => (int)$review->cmid, 'e' => COMPLETION_COMPLETE]]])]);

        // Switched off for the course: nothing happens.
        $this->assertSame([], notifier::process($course->id, $student->id, (int)$review->cmid, false));

        set_config('courses', (string)$course->id, 'local_afirst_loop');
        $sink = $this->redirectMessages();
        // The review is not complete yet, so the retake is still locked and nothing is announced.
        $this->assertSame([], notifier::process($course->id, $student->id, (int)$review->cmid, false));
        $this->assertSame(0, $sink->count());

        // Completing the review fires the observer: one message about the retake.
        $this->setAdminUser(); // Overriding completion needs the capability.
        $cm = get_coursemodule_from_id('page', $review->cmid);
        (new \completion_info($course))->update_state($cm, COMPLETION_COMPLETE, $student->id, true);
        $messages = $sink->get_messages();
        $this->assertCount(1, $messages);
        $this->assertSame('opened', $messages[0]->eventtype);
        $this->assertSame((int)$student->id, (int)$messages[0]->useridto);
        $this->assertStringContainsString($retake->name, $messages[0]->fullmessage);
        $this->assertTrue($DB->record_exists('local_afirst_loop_sent',
            ['userid' => $student->id, 'cmid' => $retake->cmid, 'kind' => notifier::KIND_RETAKE]));

        // Announced once only.
        $this->assertSame([], notifier::process($course->id, $student->id, (int)$review->cmid, false));
        $this->assertCount(1, $sink->get_messages());
    }

    public function test_module_of_question(): void {
        $this->assertSame('2.1', \local_afirst_loop\local\assistant_signals::module_of('2.1', ['Module 4.2 X']));
        $this->assertSame('4.2', \local_afirst_loop\local\assistant_signals::module_of(null,
            ['Course overview', 'Module 4.2 Applied Analytics: Machine Learning']));
        $this->assertSame('3.1', \local_afirst_loop\local\assistant_signals::module_of('', ['module 3.1: Visualisation']));
        $this->assertNull(\local_afirst_loop\local\assistant_signals::module_of('home', ['Course overview']));
    }

    public function test_early_review_thresholds(): void {
        $signals = \local_afirst_loop\local\assistant_signals::class;
        $this->assertFalse($signals::warrants_early_review(2, 1, 3, 2));
        $this->assertTrue($signals::warrants_early_review(3, 0, 3, 2));
        $this->assertTrue($signals::warrants_early_review(2, 2, 3, 2));
        $this->assertFalse($signals::warrants_early_review(5, 5, 0, 0), 'both signals switched off');
    }
}
