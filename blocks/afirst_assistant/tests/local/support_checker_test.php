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
 * Tests for support_checker, using answers from the Gate 1 rehearsal (DE A-12).
 *
 * @package    block_afirst_assistant
 * @category   test
 * @copyright  2026 Africa CDC
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[\PHPUnit\Framework\Attributes\CoversClass(support_checker::class)]
final class support_checker_test extends \basic_testcase {

    /** @var string A course passage about data quality. */
    private const PASSAGE = 'Data quality has several dimensions. Completeness checks whether all expected reports were ' .
        'received. Timeliness measures the delay between an event and its report. In 2019, 1,250 facilities reported.';

    public function test_answer_drawn_from_the_passage_is_supported(): void {
        $result = support_checker::check('Completeness checks whether expected reports were received [1].',
            'What does completeness check?', [self::PASSAGE], 0.5);
        $this->assertTrue($result['supported']);
        $this->assertSame(1.0, $result['coverage']);
    }

    public function test_invented_content_with_citation_markers_is_not_supported(): void {
        // Shape of the rehearsal's out-of-scope answers: plausible, cited, and not from the passage.
        $result = support_checker::check('[1][2][3] Dr. John Madaka is the current Director General of Africa CDC.',
            'Who is the current Director General of Africa CDC?', [self::PASSAGE], 0.5);
        $this->assertFalse($result['supported']);
        $this->assertLessThan(0.5, $result['coverage']);
    }

    public function test_number_not_in_the_passage_is_not_supported_even_with_good_coverage(): void {
        $result = support_checker::check('Completeness checks whether 0.13 of expected reports were received [1].',
            'What does completeness check?', [self::PASSAGE], 0.5);
        $this->assertFalse($result['supported']);
        $this->assertSame(['0.13'], $result['unsupportednumbers']);
    }

    public function test_numbers_from_the_passage_or_question_and_small_counts_are_allowed(): void {
        $result = support_checker::check('In 2019, 1250 facilities reported; 2 dimensions matter for case P005 [1].',
            'What about patient P005?', [self::PASSAGE], 0.0);
        $this->assertSame([], $result['unsupportednumbers']);
        $this->assertTrue($result['supported']);
    }

    public function test_word_check_can_be_skipped_for_cross_lingual_answers_but_numbers_still_count(): void {
        $french = 'La complétude vérifie que tous les rapports attendus ont été reçus [1].';
        $this->assertTrue(support_checker::check($french, 'Que vérifie la complétude ?', [self::PASSAGE], 0.5,
            checkwords: false)['supported']);
        $this->assertFalse(support_checker::check($french, 'Que vérifie la complétude ?', [self::PASSAGE], 0.5)['supported']);
        $this->assertFalse(support_checker::check('Le taux est de 0,13 [1].', 'Quel taux ?', [self::PASSAGE], 0.5,
            checkwords: false)['supported']);
    }

    public function test_looks_english_tells_english_answers_from_french_and_portuguese(): void {
        $this->assertTrue(support_checker::looks_english(
            'Completeness checks whether all of the expected reports were received in time [1].'));
        $this->assertFalse(support_checker::looks_english(
            'La complétude vérifie que tous les rapports attendus ont été reçus à temps [1].'));
        $this->assertFalse(support_checker::looks_english(
            'A completude verifica se todos os relatórios esperados foram recebidos a tempo [1].'));
        $this->assertTrue(support_checker::looks_english('Data wrangling [1].'), 'short texts count as English');
    }

    public function test_stem_and_numbers_normalisation(): void {
        $this->assertSame('check', support_checker::stem('checks'));
        $this->assertSame('report', support_checker::stem('reporting'));
        $this->assertSame('data', support_checker::stem('data'));
        $this->assertEqualsCanonicalizing(['100000', '2.5', '0.13'],
            support_checker::numbers('100,000 cases; 2.5 grams; 0,13 percent'));
    }
}
