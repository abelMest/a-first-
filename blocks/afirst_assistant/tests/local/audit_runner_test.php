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

use core_ai\aiactions\generate_text;
use core_ai\aiactions\responses\response_generate_text;
use core_ai\manager;

/**
 * Tests for the audit harness, the source approval guard and the CSV formats in evaluation/.
 *
 * @package    block_afirst_assistant
 * @category   test
 * @copyright  2026 Africa CDC
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[\PHPUnit\Framework\Attributes\CoversClass(\block_afirst_assistant\local\audit_runner::class)]
final class audit_runner_test extends \advanced_testcase {

    /**
     * A course with one approved Module 1.2 source.
     *
     * @return \stdClass
     */
    private function course_with_source(): \stdClass {
        global $DB;
        $course = $this->getDataGenerator()->create_course();
        $sourceid = $DB->insert_record('block_afirst_source', (object) [
            'courseid' => $course->id, 'title' => 'Module 1.2 Data Governance and Quality', 'filename' => 'm12.txt',
            'contenthash' => sha1('x'), 'status' => source_manager::STATUS_APPROVED, 'timecreated' => time(),
            'timemodified' => time(),
        ]);
        source_manager::index_text($sourceid,
            'Data quality has several dimensions. Completeness checks whether all expected reports were received. ' .
            'Timeliness measures the delay between an event and its report.');
        set_config('minscore', 0.1, 'block_afirst_assistant');
        return $course;
    }

    /**
     * Manager double that always answers with the given text.
     *
     * @param string $text
     * @return manager
     */
    private function manager_returning(string $text): manager {
        $manager = $this->createMock(manager::class);
        $manager->method('process_action')->willReturnCallback(function(generate_text $action) use ($text) {
            $response = new response_generate_text(true);
            $response->set_response_data(['generatedcontent' => $text, 'model' => 'test-model']);
            return $response;
        });
        return $manager;
    }

    public function test_parse_anchor_and_module(): void {
        $this->assertSame(['module' => '2.1', 'anchor' => 'Data wrangling – is'],
            audit_runner::parse_anchor('Module 2.1: "Data wrangling – is"'));
        $this->assertNull(audit_runner::parse_anchor('NONE'));
        $this->assertSame('4.3', audit_runner::module_of('Module 4.3 Text analysis'));
        $this->assertNull(audit_runner::module_of('Course handbook'));
    }

    public function test_include_row(): void {
        $this->assertFalse(audit_runner::include_row(['signoff_status' => 'rejected'], false));
        $this->assertTrue(audit_runner::include_row(['signoff_status' => 'pending'], false));
        $this->assertFalse(audit_runner::include_row(['signoff_status' => 'pending'], true));
        $this->assertTrue(audit_runner::include_row(['signoff_status' => 'approved'], true));
        // The bias sample has no sign-off column.
        $this->assertTrue(audit_runner::include_row(['id' => 'BIAS-001'], true));
    }

    public function test_run_item_fills_automatic_columns_and_detects_anchor(): void {
        $this->resetAfterTest();
        $course = $this->course_with_source();
        $runner = new audit_runner(new answerer($this->manager_returning('Completeness checks reports [1].')),
            $course->id, get_admin()->id, 'run1', 'test');

        $r = $runner->run_item([
            'id' => 'AUD-900', 'module' => '1.2', 'question' => 'What does completeness check?', 'type' => 'factual',
            'expected_source' => 'Module 1.2: "Completeness   checks whether all expected reports"',
        ], audit_runner::SET_AUDIT);

        $this->assertSame(audit_runner::RESULT_COLUMNS, array_keys($r));
        $this->assertSame('answered', $r['status']);
        $this->assertSame('1.2', $r['cited_modules']);
        $this->assertSame(1, $r['anchor_in_citations']);
        $this->assertSame('test-model', $r['model']);
        $this->assertSame('English', $r['language']);
        $this->assertCount(1, $runner->get_logids());
    }

    public function test_out_of_scope_refusal_and_summary(): void {
        $this->resetAfterTest();
        $course = $this->course_with_source();
        $runner = new audit_runner(new answerer($this->manager_returning('Completeness checks reports [1].')),
            $course->id, get_admin()->id, 'run1', 'test');

        $results = [
            $runner->run_item(['id' => 'A', 'question' => 'What does completeness check?', 'type' => 'factual',
                'expected_source' => 'Module 1.2: "a phrase that is not there"'], audit_runner::SET_AUDIT),
            $runner->run_item(['id' => 'B', 'question' => 'Who won the football final?', 'type' => 'out_of_scope',
                'expected_source' => 'NONE'], audit_runner::SET_AUDIT),
            $runner->run_item(['id' => 'C', 'prompt' => 'Que mesure la ponctualité ?', 'language' => 'French',
                'type' => 'factual', 'expected_source' => 'Module 1.2: "Timeliness measures the delay"'],
                audit_runner::SET_BIAS),
        ];
        $this->assertSame('refused', $results[1]['status']);
        $this->assertSame('', $results[1]['anchor_in_citations']);
        $this->assertSame(0, $results[0]['anchor_in_citations']);

        $s = audit_runner::summarise($results);
        $this->assertSame(3, $s['items']);
        $this->assertEquals(100.0, $s['refusal_recall_pct']);
        $this->assertSame(1, $s['false_refusals']);
        $this->assertEquals(100.0, $s['answered_with_citation_pct']);
        $this->assertEquals(0.0, $s['auto_traceability_pct']);
        $this->assertArrayHasKey('French', $s['by_language']);
        $this->assertEquals(0.0, $s['by_language']['French']['answer_rate_pct']);
    }

    public function test_percentile(): void {
        $this->assertNull(audit_runner::percentile([], 50));
        $this->assertSame(20, audit_runner::percentile([10, 20, 30, 40], 50));
        $this->assertSame(40, audit_runner::percentile([10, 20, 30, 40], 95));
    }

    public function test_repository_csv_sets_parse(): void {
        $dir = __DIR__ . '/../../../../courses/ds4h/evaluation';
        if (!is_dir($dir)) {
            $this->markTestSkipped('Evaluation sets are only present in the A-FIRST repository checkout.');
        }
        [$header, $rows] = audit_runner::read_csv("$dir/audit_set.csv");
        $this->assertSame(audit_runner::SET_AUDIT, audit_runner::detect_set($header));
        $this->assertCount(50, $rows);
        [$header, $rows] = audit_runner::read_csv("$dir/bias_audit_sample.csv");
        $this->assertSame(audit_runner::SET_BIAS, audit_runner::detect_set($header));
        $this->assertCount(21, $rows);
    }

    public function test_bulk_import_creates_searchable_sources_once(): void {
        global $DB;
        $this->resetAfterTest();
        $this->setAdminUser();
        $course = $this->getDataGenerator()->create_course();
        $text = file_get_contents(__DIR__ . '/../fixtures/module12.txt');

        $source = source_manager::create_from_content($course->id, 'Module 1.2 Data Governance', 'module12.txt', $text);
        $this->assertGreaterThan(0, $source->chunkcount);
        $this->assertSame(source_manager::STATUS_PENDING, $source->status);
        $this->assertNotNull(source_manager::get_file($source));
        $this->assertNull(source_manager::create_from_content($course->id, 'Duplicate', 'copy.txt', $text));
        $this->assertEquals(1, $DB->count_records('block_afirst_source', ['courseid' => $course->id]));

        // Pending sources are not searched until approved.
        $this->assertSame([], retriever::search($course->id, 'data quality', 3));
        source_manager::approve($source);
        $this->assertNotEmpty(retriever::search($course->id, 'data quality', 3));

        $this->expectException(\moodle_exception::class);
        source_manager::create_from_content($course->id, 'Binary', 'slides.pptx', 'x');
    }

    public function test_source_without_passages_cannot_be_approved(): void {
        global $DB;
        $this->resetAfterTest();
        $this->setAdminUser();
        $course = $this->getDataGenerator()->create_course();
        $id = $DB->insert_record('block_afirst_source', (object) [
            'courseid' => $course->id, 'title' => 'Scanned PDF text', 'filename' => 'x.txt', 'contenthash' => sha1('y'),
            'status' => source_manager::STATUS_PENDING, 'timecreated' => time(), 'timemodified' => time(),
        ]);
        $this->assertSame(0, source_manager::index_text($id, 'Too short.'));

        $this->expectException(\moodle_exception::class);
        $this->expectExceptionMessage(get_string('error:nochunks', 'block_afirst_assistant'));
        source_manager::approve($DB->get_record('block_afirst_source', ['id' => $id]));
    }
}
