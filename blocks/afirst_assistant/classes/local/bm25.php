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
 * Okapi BM25 ranking over a small in-memory corpus of term-frequency vectors.
 *
 * @package    block_afirst_assistant
 * @copyright  2026 Africa CDC
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class bm25 {

    /** @var array<int, array<string, int>> Document id => term frequencies. */
    private array $docs;

    /** @var array<int, int> Document id => number of terms. */
    private array $lengths;

    /** @var array<string, float> Term => inverse document frequency. */
    private array $idf = [];

    /** @var float Average document length. */
    private float $avglength;

    /**
     * Build the index statistics.
     *
     * @param array<int, array<string, int>> $docs Document id => term frequencies.
     * @param float $k1 Term-frequency saturation.
     * @param float $b Length normalisation.
     */
    public function __construct(array $docs, private float $k1 = 1.5, private float $b = 0.75) {
        $this->docs = $docs;
        $this->lengths = array_map('array_sum', $docs);
        $n = count($docs);
        $this->avglength = $n ? max(1.0, array_sum($this->lengths) / $n) : 1.0;

        $df = [];
        foreach ($docs as $freqs) {
            foreach (array_keys($freqs) as $term) {
                $df[$term] = ($df[$term] ?? 0) + 1;
            }
        }
        foreach ($df as $term => $count) {
            $this->idf[$term] = log(($n - $count + 0.5) / ($count + 0.5) + 1.0);
        }
    }

    /**
     * Score every document against the query terms.
     *
     * @param string[] $queryterms Tokenised query (duplicates count once).
     * @return array<int, float> Document id => score, best first, zero scores omitted.
     */
    public function rank(array $queryterms): array {
        $terms = array_unique($queryterms);
        $scores = [];
        foreach ($this->docs as $id => $freqs) {
            $score = 0.0;
            $norm = $this->k1 * (1.0 - $this->b + $this->b * ($this->lengths[$id] / $this->avglength));
            foreach ($terms as $term) {
                if (!isset($freqs[$term])) {
                    continue;
                }
                $tf = $freqs[$term];
                $score += $this->idf[$term] * ($tf * ($this->k1 + 1.0)) / ($tf + $norm);
            }
            if ($score > 0) {
                $scores[$id] = $score;
            }
        }
        arsort($scores);
        return $scores;
    }
}
