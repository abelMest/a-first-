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

/**
 * Tests for text_processor and bm25.
 *
 * @package    block_afirst_assistant
 * @category   test
 * @copyright  2026 Africa CDC
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[\PHPUnit\Framework\Attributes\CoversClass(\block_afirst_assistant\local\text_processor::class)]
#[\PHPUnit\Framework\Attributes\CoversClass(\block_afirst_assistant\local\bm25::class)]
final class text_processor_test extends \basic_testcase {

    public function test_clean_removes_storyboard_artefacts_and_normalises_punctuation(): void {
        $raw = "**Data quality** \u{2014} Text-Block: Heading the  \u{201C}five\u{201D} dimensions | Plain text only";
        $this->assertSame('Data quality - the "five" dimensions', text_processor::clean($raw));
    }

    public function test_terminate_lines_turns_headings_into_sentences(): void {
        $text = "Data quality dimensions\nCompleteness checks reports.\nIs it timely?\n\nKey terms:\n- Validity";
        $this->assertSame("Data quality dimensions.\nCompleteness checks reports.\nIs it timely?\n\nKey terms:\n- Validity.",
            text_processor::terminate_lines($text));
    }

    public function test_split_sentences_drops_fragments(): void {
        $text = 'Completeness checks expected reports arrived. Too short. Timeliness measures delay from event to report.';
        $this->assertSame([
            'Completeness checks expected reports arrived.',
            'Timeliness measures delay from event to report.',
        ], text_processor::split_sentences($text));
    }

    public function test_chunk_overlaps_and_terminates_without_duplicate_tail(): void {
        $sentences = [];
        for ($i = 1; $i <= 10; $i++) {
            $sentences[] = "Sentence number $i has exactly seven words.";
        }
        // 7 words per sentence, target 20 words: 3 sentences per chunk, 2 overlapping.
        $chunks = text_processor::chunk($sentences, 20, 2);
        $this->assertStringStartsWith('Sentence number 1 ', $chunks[0]);
        $this->assertStringStartsWith('Sentence number 2 ', $chunks[1]);
        $this->assertStringEndsWith('Sentence number 10 has exactly seven words.', end($chunks));
        // The last chunk is the only one reaching the end.
        $this->assertCount(1, array_filter($chunks, fn($c) => str_contains($c, 'number 10 ')));
    }

    public function test_tokenize_keeps_accented_words_and_drops_stop_words(): void {
        $this->assertSame(['qualité', 'données', 'completeness'],
            text_processor::tokenize('Qualité données and the Completeness of it'));
    }

    public function test_bm25_ranks_matching_document_first(): void {
        $docs = [
            1 => text_processor::term_frequencies('Epidemic curves show cases over time by onset date.'),
            2 => text_processor::term_frequencies('Data quality dimensions include completeness and timeliness.'),
            3 => text_processor::term_frequencies('Logistic regression models binary outcomes.'),
        ];
        $ranked = (new bm25($docs))->rank(text_processor::tokenize('What are the dimensions of data quality?'));
        $this->assertSame(2, array_key_first($ranked));
        $this->assertArrayNotHasKey(3, $ranked);
    }
}
