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
 * Checks that an answer's content actually comes from the passages it cites.
 *
 * A citation marker only says which passage the model claims to use. The Gate 1 rehearsal (DE A-12) showed a small
 * model inventing facts (a cholera ORS dose, a Director General's name) and still adding [1][2][3]. This check
 * withholds an answer when too few of its content words appear in the cited passages, or when it states a number
 * found neither in those passages nor in the question.
 *
 * @package    block_afirst_assistant
 * @copyright  2026 Africa CDC
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class support_checker {

    /** @var float Default minimum share of the answer's content words found in the cited passages. */
    public const DEFAULT_MIN_SUPPORT = 0.5;

    /** @var int Whole numbers up to this are ignored: models use them for list numbering and counting words. */
    private const SMALL_NUMBER = 10;

    /**
     * Assess an answer against its cited passages.
     *
     * @param string $answer Answer text, with or without [n] markers.
     * @param string $question The learner's question (numbers it contains may be repeated).
     * @param string[] $passages Texts of the cited passages.
     * @param float $minsupport
     * @param bool $checkwords False when the answer is in another language than the passages (cross-lingual
     *     retrieval): words cannot be compared, so only the number check applies. Coverage is still reported.
     * @return array{supported: bool, coverage: float, unsupportednumbers: string[]}
     */
    public static function check(string $answer, string $question, array $passages, float $minsupport,
            bool $checkwords = true): array {
        $answer = preg_replace('/\[\d+\]/', ' ', $answer);
        $source = implode(' ', $passages);

        $answerterms = self::terms($answer);
        $sourceterms = array_flip(self::terms($source));
        $found = count(array_filter($answerterms, fn(string $t) => isset($sourceterms[$t])));
        $coverage = $answerterms ? $found / count($answerterms) : 0.0;

        $allowed = array_flip(array_merge(self::numbers($source), self::numbers($question)));
        $unsupported = array_values(array_filter(self::numbers($answer),
            fn(string $n) => !isset($allowed[$n]) && !(ctype_digit($n) && (int)$n <= self::SMALL_NUMBER)));

        return [
            'supported' => (!$checkwords || $coverage >= $minsupport) && !$unsupported,
            'coverage' => round($coverage, 4),
            'unsupportednumbers' => $unsupported,
        ];
    }

    /**
     * Content words, lightly stemmed so that "checks" matches "check". Pure numbers are handled separately.
     *
     * @param string $text
     * @return string[]
     */
    /** @var string[] Very common English words, used to tell an English answer from one in another language. */
    private const ENGLISH_MARKERS = ['the', 'of', 'and', 'to', 'is', 'are', 'in', 'that', 'for', 'with', 'as', 'by',
        'it', 'this', 'be', 'or', 'from', 'an', 'which', 'what', 'how', 'can', 'you', 'when', 'its', 'not'];

    /** Share of English marker words above which a text counts as English (English prose is ~0.3, French ~0.03). */
    private const ENGLISH_MIN_SHARE = 0.12;

    /**
     * Whether a text is (mostly) English, the language of the course sources (D-17).
     *
     * The word check compares words, so it can only judge an answer written in the passages' language. An answer in
     * French or Portuguese is translated back before checking (see answerer). Short texts count as English.
     *
     * @param string $text
     * @return bool
     */
    public static function looks_english(string $text): bool {
        preg_match_all('/\p{L}+/u', \core_text::strtolower(preg_replace('/\[\d+\]/', ' ', $text)), $matches);
        $words = $matches[0];
        if (count($words) < 6) {
            return true;
        }
        $markers = array_flip(self::ENGLISH_MARKERS);
        $hits = count(array_filter($words, fn(string $w) => isset($markers[$w])));
        return $hits / count($words) >= self::ENGLISH_MIN_SHARE;
    }

    public static function terms(string $text): array {
        $terms = [];
        foreach (text_processor::tokenize($text) as $word) {
            if (!ctype_digit($word)) {
                $terms[] = self::stem($word);
            }
        }
        return $terms;
    }

    /**
     * Remove a common English inflection.
     *
     * @param string $word Lower-case word.
     * @return string
     */
    public static function stem(string $word): string {
        foreach (['ing' => 6, 'ed' => 5, 'es' => 5, 's' => 4] as $suffix => $minlength) {
            if (\core_text::strlen($word) >= $minlength && str_ends_with($word, $suffix)) {
                return \core_text::substr($word, 0, -\core_text::strlen($suffix));
            }
        }
        return $word;
    }

    /**
     * Numbers in a text, with thousands separators removed ("100,000" and "100000" match).
     *
     * @param string $text
     * @return string[] Unique.
     */
    public static function numbers(string $text): array {
        preg_match_all('/\d+(?:[.,]\d+)*/', $text, $matches);
        $numbers = [];
        foreach ($matches[0] as $number) {
            // A comma followed by exactly three digits is a thousands separator; otherwise treat it as a decimal mark.
            $number = preg_replace('/,(?=\d{3}(?!\d))/', '', $number);
            $numbers[str_replace(',', '.', $number)] = true;
        }
        return array_map('strval', array_keys($numbers));
    }
}
