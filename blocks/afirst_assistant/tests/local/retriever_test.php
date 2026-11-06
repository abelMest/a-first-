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

use GuzzleHttp\Psr7\Response;

/**
 * Tests for module boost, hybrid (embedding) retrieval, starter questions and the embedder helpers.
 *
 * @package    block_afirst_assistant
 * @category   test
 * @copyright  2026 Africa CDC
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[\PHPUnit\Framework\Attributes\CoversClass(retriever::class)]
#[\PHPUnit\Framework\Attributes\CoversClass(embedder::class)]
#[\PHPUnit\Framework\Attributes\CoversClass(passage::class)]
final class retriever_test extends \advanced_testcase {

    /**
     * An approved source with the given text.
     *
     * @param int $courseid
     * @param string $title
     * @param string $text
     * @return \stdClass
     */
    private function source(int $courseid, string $title, string $text): \stdClass {
        global $DB;
        $source = (object) ['courseid' => $courseid, 'title' => $title, 'filename' => sha1($title) . '.txt',
            'contenthash' => sha1($title), 'status' => source_manager::STATUS_APPROVED, 'timecreated' => time(),
            'timemodified' => time()];
        $source->id = $DB->insert_record('block_afirst_source', $source);
        source_manager::index_text($source->id, $text);
        return $source;
    }

    public function test_module_of(): void {
        $this->assertSame('2.2', retriever::module_of('Module 2.2 Exploratory Data Analysis'));
        $this->assertSame('3.1', retriever::module_of('Module 3.1: Foundations of Data Visualisation'));
        $this->assertNull(retriever::module_of('Course handbook'));
    }

    public function test_current_module_is_boosted(): void {
        $this->resetAfterTest();
        $course = $this->getDataGenerator()->create_course();
        $this->source($course->id, 'Module 1.2 Data quality',
            'Missing values reduce completeness of surveillance reports. Teams check missing values every week.');
        $this->source($course->id, 'Module 2.1 Data wrangling',
            'Wrangling handles missing values by imputation or by flagging the records for follow up.');

        $plain = retriever::search($course->id, 'missing values', 2);
        $boosted = retriever::search($course->id, 'missing values', 2, '2.1');
        $this->assertSame('Module 1.2 Data quality', $plain[0]->sourcetitle);
        $this->assertSame('Module 2.1 Data wrangling', $boosted[0]->sourcetitle);
    }

    public function test_hybrid_retrieval_finds_a_passage_with_no_shared_keywords(): void {
        $this->resetAfterTest();
        set_config('embedendpoint', 'http://ollama.example:11434', 'block_afirst_assistant');
        $course = $this->getDataGenerator()->create_course();
        ['mock' => $mock] = $this->get_mocked_http_client();
        // Indexing: the two passages get orthogonal vectors.
        $mock->append(new Response(200, [], json_encode(['embeddings' => [[1, 0, 0]]])));
        $this->source($course->id, 'Module 3.2 Dashboards', 'An outbreak timeline plots daily case counts by onset date.');
        $mock->append(new Response(200, [], json_encode(['embeddings' => [[0, 1, 0]]])));
        $this->source($course->id, 'Module 4.2 Machine learning', 'Models trained on noise memorise rather than learn.');

        // The query shares no keyword with the dashboards passage but is closest to it in meaning.
        $mock->append(new Response(200, [], json_encode(['embeddings' => [[0.9, 0.1, 0]]])));
        $found = retriever::search($course->id, 'What is an epi curve for?', 2);

        $this->assertSame('Module 3.2 Dashboards', $found[0]->sourcetitle);
        $this->assertSame(0.0, $found[0]->score);
        $this->assertGreaterThan(0.9, $found[0]->similarity);
        $this->assertTrue($found[0]->is_grounded(3.0, 0.6));
        $this->assertFalse($found[1]->is_grounded(3.0, 0.6));
    }

    public function test_embedding_failure_falls_back_to_keywords(): void {
        $this->resetAfterTest();
        set_config('embedendpoint', 'http://ollama.example:11434', 'block_afirst_assistant');
        $course = $this->getDataGenerator()->create_course();
        ['mock' => $mock] = $this->get_mocked_http_client();
        $mock->append(new Response(500, [], 'model not found'));
        $this->source($course->id, 'Module 1.2 Data quality', 'Completeness checks whether expected reports arrived.');
        $this->assertDebuggingCalled();

        $found = retriever::search($course->id, 'completeness reports', 1);
        $this->assertCount(1, $found);
        $this->assertNull($found[0]->similarity);
    }

    public function test_suggestions_and_has_sources(): void {
        $this->resetAfterTest();
        $course = $this->getDataGenerator()->create_course();
        $this->assertFalse(retriever::has_sources($course->id));
        $source = $this->source($course->id, 'Module 1.1 Data literacy',
            'Data literacy is the ability to read, understand and communicate health data.');
        source_manager::set_suggestions($source, ['What is data literacy?', '', '  Why does it matter?  ']);

        $this->assertTrue(retriever::has_sources($course->id));
        $this->assertSame(['1.1' => ['What is data literacy?', 'Why does it matter?']], retriever::suggestions($course->id));
    }

    public function test_embedder_vectors_round_trip(): void {
        $vector = embedder::normalise([3, 4]);
        $this->assertEqualsWithDelta([0.6, 0.8], $vector, 1e-9);
        $this->assertEqualsWithDelta($vector, embedder::unpack(embedder::pack($vector)), 1e-6);
        $this->assertEqualsWithDelta(1.0, embedder::similarity($vector, $vector), 1e-9);
        $this->assertNull(embedder::normalise([0, 0]));
        $this->assertNull(embedder::unpack('not base64!'));
    }
}
