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

use core_text;

/**
 * Cleaning, sentence splitting, chunking and tokenising of approved course text.
 *
 * Ported from the Phase 2 retrieval service (setup_rag_new.py). Unlike the original it keeps
 * non-ASCII letters, so French and Portuguese questions can still match.
 *
 * @package    block_afirst_assistant
 * @copyright  2026 Africa CDC
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class text_processor {

    /** @var int Target words per chunk; chunks never cut a sentence. */
    public const TARGET_CHUNK_WORDS = 150;

    /** @var int Sentences repeated at the start of the next chunk. */
    public const OVERLAP_SENTENCES = 2;

    /** @var int Sentences shorter than this many words are dropped as fragments. */
    public const MIN_SENTENCE_WORDS = 4;

    /** @var string[] Words ignored for retrieval (English function words plus storyboard UI terms). */
    public const STOP_WORDS = [
        'a', 'an', 'the', 'is', 'it', 'in', 'on', 'at', 'to', 'for',
        'of', 'and', 'or', 'with', 'this', 'are', 'be', 'as', 'by',
        'from', 'not', 'each', 'their', 'than', 'into', 'about', 'used',
        'if', 'have', 'has', 'been', 'will', 'may', 'would', 'can',
        'also', 'such', 'these', 'which', 'more', 'some', 'they', 'you',
        'we', 'its', 'one', 'that', 'was', 'were', 'but', 'so', 'do',
        'does', 'did', 'when', 'where', 'how', 'what', 'who', 'all',
        'any', 'both', 'few', 'most', 'other', 'same', 'then',
        'too', 'very', 'just', 'because', 'while', 'although', 'however',
        'select', 'learn', 'image', 'chapter', 'module', 'click', 'screen',
        'card', 'cards', 'slide', 'tab', 'tabs', 'template', 'block',
    ];

    /**
     * Normalise storyboard text: typographic punctuation, markdown and authoring-tool artefacts, whitespace.
     *
     * @param string $text Raw text.
     * @return string
     */
    public static function clean(string $text): string {
        $text = str_replace(
            ["\u{2018}", "\u{2019}", "\u{201C}", "\u{201D}", "\u{2013}", "\u{2014}", "\u{2026}"],
            ["'", "'", '"', '"', '-', '-', '...'],
            $text
        );
        $text = preg_replace('/\*+/u', '', $text);
        $text = str_replace('|', ' ', $text);
        $text = preg_replace(
            '/(Plain text only|Template Image|Avatar image:?|Suiting|Suitable|Divider-Block: Continue|' .
                'Text-Block: Heading|Interactive-Block:[^:]+)/iu',
            '',
            $text
        );
        $text = preg_replace('/\s+/u', ' ', $text);
        return trim($text);
    }

    /**
     * End every line with punctuation, so headings and list items become sentences of their own.
     *
     * Extracted course content (e.g. Rise SCORM) has headings and bullets without full stops; once whitespace is
     * collapsed they would otherwise merge with the next sentence into one very long one.
     *
     * @param string $text Text with one block per line.
     * @return string
     */
    public static function terminate_lines(string $text): string {
        $lines = preg_split('/\R/u', $text);
        foreach ($lines as $i => $line) {
            $line = rtrim($line);
            if ($line !== '' && !preg_match('/[.!?:;]["\')\]]?$/u', $line)) {
                $lines[$i] = $line . '.';
            }
        }
        return implode("\n", $lines);
    }

    /**
     * Split cleaned text into sentences, dropping short fragments.
     *
     * @param string $text Cleaned text.
     * @return string[]
     */
    public static function split_sentences(string $text): array {
        $parts = preg_split('/(?<=[.!?])\s+(?=[\p{Lu}0-9])/u', $text);
        $sentences = [];
        foreach ($parts as $part) {
            $part = trim($part);
            if (count(preg_split('/\s+/u', $part, -1, PREG_SPLIT_NO_EMPTY)) >= self::MIN_SENTENCE_WORDS) {
                $sentences[] = $part;
            }
        }
        return $sentences;
    }

    /**
     * Group sentences into overlapping chunks of roughly TARGET_CHUNK_WORDS words.
     *
     * @param string[] $sentences
     * @param int $targetwords
     * @param int $overlap
     * @return string[] Chunk texts, in document order.
     */
    public static function chunk(array $sentences, int $targetwords = self::TARGET_CHUNK_WORDS,
            int $overlap = self::OVERLAP_SENTENCES): array {
        $chunks = [];
        $count = count($sentences);
        $i = 0;
        while ($i < $count) {
            $words = 0;
            $j = $i;
            $parts = [];
            while ($j < $count) {
                $parts[] = $sentences[$j];
                $words += count(preg_split('/\s+/u', $sentences[$j], -1, PREG_SPLIT_NO_EMPTY));
                $j++;
                if ($words >= $targetwords) {
                    break;
                }
            }
            $text = implode(' ', $parts);
            if (core_text::strlen(trim($text)) > 50) {
                $chunks[] = $text;
            }
            if ($j >= $count) {
                break;
            }
            // Step back by the overlap, but always make progress.
            $i = max($j - $overlap, $i + 1);
        }
        return $chunks;
    }

    /**
     * Lower-cased retrieval terms of three or more letters/digits, without stop words.
     *
     * @param string $text
     * @return string[]
     */
    public static function tokenize(string $text): array {
        preg_match_all('/[\p{L}\p{N}]{3,}/u', core_text::strtolower($text), $matches);
        $stop = array_flip(self::STOP_WORDS);
        return array_values(array_filter($matches[0], fn(string $word) => !isset($stop[$word])));
    }

    /**
     * Term frequencies for a text.
     *
     * @param string $text
     * @return array<string, int>
     */
    public static function term_frequencies(string $text): array {
        return array_count_values(self::tokenize($text));
    }
}
