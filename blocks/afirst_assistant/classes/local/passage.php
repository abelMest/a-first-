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
 * One retrieved passage of approved course text.
 *
 * @package    block_afirst_assistant
 * @copyright  2026 Africa CDC
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class passage {

    /**
     * Constructor.
     *
     * @param int $chunkid
     * @param int $sourceid
     * @param string $sourcetitle Human-readable source name shown in citations.
     * @param int $chunkno Position of the passage within its source.
     * @param string $text
     * @param float $score BM25 score (0 when the passage was found by meaning only).
     * @param float|null $similarity Cosine similarity to the question, when hybrid retrieval is on.
     */
    public function __construct(
        public readonly int $chunkid,
        public readonly int $sourceid,
        public readonly string $sourcetitle,
        public readonly int $chunkno,
        public readonly string $text,
        public readonly float $score,
        public readonly ?float $similarity = null,
    ) {
    }

    /**
     * Whether this passage is strong enough to ground an answer: by keywords or by meaning.
     *
     * @param float $minscore Minimum BM25 score.
     * @param float $minsimilarity Minimum cosine similarity.
     * @return bool
     */
    public function is_grounded(float $minscore, float $minsimilarity): bool {
        return $this->score >= $minscore || ($this->similarity !== null && $this->similarity >= $minsimilarity);
    }
}
