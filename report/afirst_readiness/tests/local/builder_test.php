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
 * Building a scenario as a Lesson, and the report over learners' attempts.
 *
 * @package    report_afirst_readiness
 * @category   test
 * @copyright  2026 Africa CDC
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[\PHPUnit\Framework\Attributes\CoversClass(\report_afirst_readiness\local\scenario_builder::class)]
#[\PHPUnit\Framework\Attributes\CoversClass(\report_afirst_readiness\local\readiness_report::class)]
final class builder_test extends \advanced_testcase {

    /**
     * The shared mini scenario.
     *
     * @return array
     */
    private static function tiny(): array {
        return json_decode(file_get_contents(__DIR__ . '/../fixtures/tiny_scenario.json'), true);
    }

    /**
     * Record a learner's choices as Lesson attempts, the way mod_lesson stores them.
     *
     * @param \stdClass $record
     * @param int $userid
     * @param array $choices list of [node, option]
     */
    private function attempt(\stdClass $record, int $userid, array $choices): void {
        global $DB;
        $byoption = [];
        foreach (json_decode($record->answermap, true) as $answerid => $m) {
            $byoption[$m['node'] . '/' . $m['option']] = (int)$answerid;
        }
        $time = time();
        foreach ($choices as [$node, $option]) {
            $answer = $DB->get_record('lesson_answers', ['id' => $byoption["$node/$option"]]);
            $DB->insert_record('lesson_attempts', (object) [
                'lessonid' => $record->lessonid, 'pageid' => $answer->pageid, 'userid' => $userid,
                'answerid' => $answer->id, 'retry' => 0, 'correct' => $answer->score > 0 ? 1 : 0,
                'useranswer' => '', 'timeseen' => $time++,
            ]);
        }
    }

    public function test_build_and_report(): void {
        global $DB;
        $this->resetAfterTest();
        $this->setAdminUser();
        $gen = $this->getDataGenerator();
        $course = $gen->create_course(['enablecompletion' => 1]);
        $quiz = $gen->create_module('quiz', ['course' => $course->id, 'grade' => 10]);

        $record = scenario_builder::build($course->id, 1, self::tiny(), [
            'thresholds' => ['independent_domain_min' => 80],
            'knowledge' => ['1.1' => (int)$quiz->cmid],
        ]);

        // The Lesson: hidden, custom scoring, branching wired to the right pages.
        $cm = get_coursemodule_from_id('lesson', $record->lessoncmid, $course->id, false, MUST_EXIST);
        $this->assertEquals(0, $cm->visible);
        $this->assertSame('AFIRST-RDY-T-1', $cm->idnumber);
        $lesson = $DB->get_record('lesson', ['id' => $record->lessonid]);
        $this->assertEquals(1, $lesson->custom);
        $this->assertEquals(0, $lesson->retake);
        $this->assertEquals(3, $DB->count_records('lesson_pages', ['lessonid' => $lesson->id]));
        // One choice per decision (single-answer multichoice), never "select all that apply".
        $this->assertEquals(0, $DB->count_records_select('lesson_pages', 'lessonid = ? AND (qtype <> 3 OR qoption <> 0)',
            [$lesson->id]));
        $map = json_decode($record->answermap, true);
        $this->assertCount(6, $map);
        $pages = $DB->get_records_menu('lesson_pages', ['lessonid' => $lesson->id], '', 'title, id');
        foreach ($map as $answerid => $m) {
            $answer = $DB->get_record('lesson_answers', ['id' => $answerid]);
            $option = array_column(array_column(self::tiny()['nodes'], null, 'id')[$m['node']]['options'],
                null, 'id')[$m['option']];
            $expected = $option['next'] === 'end' ? LESSON_EOL : (int)$pages[$option['next']];
            $this->assertEquals($expected, $answer->jumpto, "jump for {$m['node']}/{$m['option']}");
            $this->assertEquals(array_sum($option['scores'] ?? []), $answer->score);
            $this->assertEquals($m['node'], $DB->get_field('lesson_pages', 'title', ['id' => $answer->pageid]));
        }

        // Four learners with different evidence.
        $best = $gen->create_and_enrol($course, 'student');
        $critical = $gen->create_and_enrol($course, 'student');
        $weak = $gen->create_and_enrol($course, 'student');
        $none = $gen->create_and_enrol($course, 'student');
        $gen->create_and_enrol($course, 'editingteacher');
        $this->attempt($record, $best->id, [['A', '1'], ['C', '1']]);
        $this->attempt($record, $critical->id, [['A', '1'], ['C', '2']]);
        $this->attempt($record, $weak->id, [['A', '2'], ['B', '2'], ['C', '1']]);
        foreach ([$best->id => 9, $critical->id => 9, $weak->id => 5] as $userid => $raw) {
            grade_update('mod/quiz', $course->id, 'mod', 'quiz', $quiz->id, 0, [$userid => (object)[
                'userid' => $userid, 'rawgrade' => $raw]]);
        }

        $rows = (new readiness_report($record))->rows();
        $this->assertCount(4, $rows, 'teachers are not learners');
        $this->assertSame(rules::INDEPENDENT, $rows[$best->id]['status']);
        $this->assertEquals(['1.1' => 90.0], $rows[$best->id]['knowledge']);
        // The critical option also scores 0 in its domain, so this learner is below even the supervised bar.
        $this->assertSame(rules::NOT_YET, $rows[$critical->id]['status']);
        $this->assertTrue($rows[$critical->id]['critical']);
        $this->assertEquals(0.0, $rows[$critical->id]['domains']['Y']);
        $this->assertSame(rules::NOT_YET, $rows[$weak->id]['status']);
        $this->assertEquals(0.0, $rows[$weak->id]['domains']['X']);
        $this->assertSame(rules::NOT_STARTED, $rows[$none->id]['status']);

        $text = (new readiness_report($record))->reason_text(['domain', ['domain' => 'X', 'value' => 0.0, 'min' => 60]]);
        $this->assertStringContainsString('Domain X', $text);
    }

    public function test_invalid_scenario_is_rejected_before_anything_is_created(): void {
        global $DB;
        $this->resetAfterTest();
        $this->setAdminUser();
        $course = $this->getDataGenerator()->create_course();
        $s = self::tiny();
        $s['start'] = 'nowhere';
        try {
            scenario_builder::build($course->id, 0, $s, []);
            $this->fail('An invalid scenario must not be built.');
        } catch (\invalid_parameter_exception $e) {
            $this->assertEquals(0, $DB->count_records('lesson', ['course' => $course->id]));
        }
    }
}
