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
 * @package    block_afirst_assistant
 * @copyright  2026 Africa CDC
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class retriever {

    /**
     * Search the approved sources of a course.
     *
     * @param int $courseid
     * @param string $question
     * @param int $limit Maximum passages returned.
     * @return passage[] Best first. May be empty; the caller decides whether scores are high enough.
     */
    public static function search(int $courseid, string $question, int $limit): array {
        $terms = text_processor::tokenize($question);
        if (!$terms) {
            return [];
        }
        $index = self::get_index($courseid);
        if (!$index['rows']) {
            return [];
        }
        $ranked = $index['bm25']->rank($terms);

        $passages = [];
        foreach (array_slice($ranked, 0, $limit, true) as $id => $score) {
            $row = $index['rows'][$id];
            $passages[] = new passage((int)$row->id, (int)$row->sourceid, $row->sourcetitle,
                (int)$row->chunkno, $row->content, round($score, 4));
        }
        return $passages;
    }

    /**
     * The course's approved passages and BM25 statistics, from the application cache or rebuilt from the database.
     *
     * Without the cache every question re-reads and re-decodes every passage of the course, which is what limits
     * throughput once many courses and learners share one site.
     *
     * @param int $courseid
     * @return array{rows: array<int, \stdClass>, bm25: bm25|null}
     */
    public static function get_index(int $courseid): array {
        global $DB;

        $cache = \cache::make('block_afirst_assistant', 'courseindex');
        $index = $cache->get($courseid);
        if ($index !== false) {
            return $index;
        }

        // Only chunks of sources that are currently approved for this course take part.
        $sql = "SELECT c.id, c.sourceid, c.chunkno, c.content, c.termfreq, s.title AS sourcetitle
                  FROM {block_afirst_chunk} c
                  JOIN {block_afirst_source} s ON s.id = c.sourceid
                 WHERE s.courseid = :courseid AND s.status = :status";
        $rows = $DB->get_records_sql($sql, ['courseid' => $courseid, 'status' => source_manager::STATUS_APPROVED]);
        $docs = [];
        foreach ($rows as $row) {
            $docs[$row->id] = json_decode($row->termfreq, true) ?: [];
            unset($row->termfreq);
        }
        $index = ['rows' => $rows, 'bm25' => $rows ? new bm25($docs) : null];
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
