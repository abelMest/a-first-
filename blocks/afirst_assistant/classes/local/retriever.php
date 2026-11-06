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
 * Finds the approved course passages that best match a learner question.
 *
 * Keyword ranking (BM25) always runs. When hybrid retrieval is configured, meaning-based ranking (embeddings) runs too
 * and the two rankings are merged by reciprocal rank fusion. Passages from the module the learner is currently in can
 * be boosted.
 *
 * @package    block_afirst_assistant
 * @copyright  2026 Africa CDC
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class retriever {

    /** @var float Default multiplier for passages from the learner's current module. */
    public const DEFAULT_CONTEXT_BOOST = 1.5;

    /** @var int Candidates taken from each ranking before fusion. */
    private const CANDIDATES = 20;

    /** @var int Reciprocal rank fusion constant. */
    private const RRF_K = 60;

    /**
     * Search the approved sources of a course.
     *
     * @param int $courseid
     * @param string $question
     * @param int $limit Maximum passages returned.
     * @param string|null $prefermodule Module code (e.g. "2.2") of the learner's current page, boosted when set.
     * @return passage[] Best first. May be empty; the caller decides whether scores are high enough.
     */
    public static function search(int $courseid, string $question, int $limit, ?string $prefermodule = null): array {
        $index = self::get_index($courseid);
        if (!$index['rows']) {
            return [];
        }
        $terms = text_processor::tokenize($question);
        $bm25 = $terms ? $index['bm25']->rank($terms) : [];

        $similarities = [];
        if (embedder::enabled() && $index['hasvectors']) {
            $query = embedder::embed_query($question);
            if ($query !== null) {
                foreach ($index['rows'] as $id => $row) {
                    $vector = embedder::unpack($row->embedding);
                    if ($vector !== null) {
                        $similarities[$id] = embedder::similarity($query, $vector);
                    }
                }
                arsort($similarities);
            }
        }
        if (!$bm25 && !$similarities) {
            return [];
        }

        // Order candidates: by fused rank when both signals exist, otherwise by BM25 score alone.
        if ($similarities) {
            $fused = [];
            foreach ([array_slice($bm25, 0, self::CANDIDATES, true), array_slice($similarities, 0, self::CANDIDATES, true)]
                    as $ranking) {
                $rank = 0;
                foreach (array_keys($ranking) as $id) {
                    $fused[$id] = ($fused[$id] ?? 0) + 1 / (self::RRF_K + ++$rank);
                }
            }
        } else {
            $fused = $bm25;
        }

        $boost = (float)(get_config('block_afirst_assistant', 'contextboost') ?: self::DEFAULT_CONTEXT_BOOST);
        if ($prefermodule !== null && $prefermodule !== '' && $boost > 0) {
            foreach ($fused as $id => $value) {
                if (self::module_of($index['rows'][$id]->sourcetitle) === $prefermodule) {
                    $fused[$id] = $value * $boost;
                }
            }
        }
        arsort($fused);

        $passages = [];
        foreach (array_slice($fused, 0, $limit, true) as $id => $unused) {
            $row = $index['rows'][$id];
            $passages[] = new passage((int)$row->id, (int)$row->sourceid, $row->sourcetitle, (int)$row->chunkno,
                $row->content, round($bm25[$id] ?? 0.0, 4),
                isset($similarities[$id]) ? round($similarities[$id], 4) : null);
        }
        return $passages;
    }

    /**
     * Module code in a title such as "Module 2.2 Exploratory Data Analysis" or "Module 3.1: Visualisation".
     *
     * @param string $title
     * @return string|null
     */
    public static function module_of(string $title): ?string {
        return preg_match('/\b(\d{1,2}\.\d{1,2})\b/', $title, $m) ? $m[1] : null;
    }

    /**
     * Starter questions of the course's approved sources, by module code ('' for sources without one).
     *
     * @param int $courseid
     * @return array<string, string[]>
     */
    public static function suggestions(int $courseid): array {
        global $DB;
        $records = $DB->get_records_select('block_afirst_source', 'courseid = ? AND status = ? AND suggestions IS NOT NULL',
            [$courseid, source_manager::STATUS_APPROVED], 'title', 'id, title, suggestions');
        $bymodule = [];
        foreach ($records as $record) {
            $questions = array_values(array_filter(array_map('trim', explode("\n", $record->suggestions))));
            if ($questions) {
                $bymodule[self::module_of($record->title) ?? ''] = $questions;
            }
        }
        return $bymodule;
    }

    /**
     * Whether the course has at least one approved source with passages.
     *
     * @param int $courseid
     * @return bool
     */
    public static function has_sources(int $courseid): bool {
        return (bool)self::get_index($courseid)['rows'];
    }

    /**
     * The course's approved passages and BM25 statistics, from the application cache or rebuilt from the database.
     *
     * Without the cache every question re-reads and re-decodes every passage of the course, which is what limits
     * throughput once many courses and learners share one site.
     *
     * @param int $courseid
     * @return array{rows: array<int, \stdClass>, bm25: bm25|null, hasvectors: bool}
     */
    public static function get_index(int $courseid): array {
        global $DB;

        $cache = \cache::make('block_afirst_assistant', 'courseindex');
        $index = $cache->get($courseid);
        if ($index !== false && array_key_exists('hasvectors', $index)) {
            return $index;
        }

        // Only chunks of sources that are currently approved for this course take part.
        $sql = "SELECT c.id, c.sourceid, c.chunkno, c.content, c.termfreq, c.embedding, s.title AS sourcetitle
                  FROM {block_afirst_chunk} c
                  JOIN {block_afirst_source} s ON s.id = c.sourceid
                 WHERE s.courseid = :courseid AND s.status = :status";
        $rows = $DB->get_records_sql($sql, ['courseid' => $courseid, 'status' => source_manager::STATUS_APPROVED]);
        $docs = [];
        $hasvectors = false;
        foreach ($rows as $row) {
            $docs[$row->id] = json_decode($row->termfreq, true) ?: [];
            unset($row->termfreq);
            $hasvectors = $hasvectors || !empty($row->embedding);
        }
        $index = ['rows' => $rows, 'bm25' => $rows ? new bm25($docs) : null, 'hasvectors' => $hasvectors];
        $cache->set($courseid, $index);
        return $index;
    }

    /**
     * Drop a course's cached index after its sources change.
     *
     * @param int $courseid
     */
    public static function purge_index(int $courseid): void {
        \cache::make('block_afirst_assistant', 'courseindex')->delete($courseid);
    }
}
