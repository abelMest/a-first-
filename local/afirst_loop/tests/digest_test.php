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

use local_afirst_loop\local\digest;

/**
 * Tests for the inactivity nudge (R8) and the weekly digest.
 *
 * @package    local_afirst_loop
 * @copyright  2026 Africa CDC
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \local_afirst_loop\local\digest
 */
final class digest_test extends \advanced_testcase {

    public function test_stalled_learner_is_nudged_once_and_again_after_returning(): void {
        global $DB;
        $this->resetAfterTest();
        $this->preventResetByRollback();
        $course = $this->getDataGenerator()->create_course(['enablecompletion' => 1]);
        $active = $this->getDataGenerator()->create_and_enrol($course, 'student');
        $neverstarted = $this->getDataGenerator()->create_and_enrol($course, 'student');
        $this->getDataGenerator()->create_and_enrol($course, 'editingteacher');
        set_config('courses', (string)$course->id, 'local_afirst_loop');
        set_config('stalldays', 14, 'local_afirst_loop');

        $now = time();
        $DB->insert_record('user_lastaccess', (object)['userid' => $active->id, 'courseid' => $course->id,
            'timeaccess' => $now - 20 * DAYSECS]);
        $sink = $this->redirectMessages();

        $this->assertSame([(int)$active->id], digest::nudge_stalled($course->id, $now));
        $this->assertSame(1, $sink->count());
        $this->assertStringContainsString('20 days', $sink->get_messages()[0]->fullmessage);
        // Not twice for the same stall, and never for someone who has not started.
        $this->assertSame([], digest::nudge_stalled($course->id, $now + DAYSECS));

        // Back on the course, then stalled again: a new nudge.
        $DB->set_field('user_lastaccess', 'timeaccess', $now + 2 * DAYSECS, ['userid' => $active->id]);
        $this->assertSame([], digest::nudge_stalled($course->id, $now + 3 * DAYSECS));
        $this->assertSame([(int)$active->id], digest::nudge_stalled($course->id, $now + 17 * DAYSECS));
        $this->assertNotContains((int)$neverstarted->id, array_keys(digest::last_activity($course->id,
            [(int)$active->id, (int)$neverstarted->id])));
    }

    public function test_digest_lists_and_sends_to_teachers(): void {
        global $DB;
        $this->resetAfterTest();
        $this->preventResetByRollback();
        $course = $this->getDataGenerator()->create_course();
        $student = $this->getDataGenerator()->create_and_enrol($course, 'student', ['firstname' => 'Amina',
            'lastname' => 'Test']);
        $teacher = $this->getDataGenerator()->create_and_enrol($course, 'editingteacher');
        set_config('courses', (string)$course->id, 'local_afirst_loop');
        set_config('digest', 1, 'local_afirst_loop');
        $DB->insert_record('local_afirst_loop_sent', (object)['courseid' => $course->id, 'userid' => $student->id,
            'cmid' => 7, 'kind' => 'fasttrack', 'timecreated' => time()]);

        $data = digest::build($course->id, time() - WEEKSECS);
        $this->assertSame(['Amina Test'], $data['fasttracked']);
        $this->assertSame([], $data['reviewed']);
        $text = digest::render($data);
        $this->assertStringContainsString('Fast-tracked', $text);
        $this->assertStringContainsString('1: Amina Test', $text);

        $sink = $this->redirectMessages();
        $this->assertSame(1, digest::send_weekly($course->id));
        $this->assertSame((int)$teacher->id, (int)$sink->get_messages()[0]->useridto);
    }
}
