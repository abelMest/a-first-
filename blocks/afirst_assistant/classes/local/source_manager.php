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

use context_course;
use stored_file;

/**
 * Stores, indexes, approves and retires the course documents the assistant answers from.
 *
 * A source only becomes searchable once someone with managesources approves it, so the
 * assistant never answers from text nobody signed off.
 *
 * @package    block_afirst_assistant
 * @copyright  2026 Africa CDC
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class source_manager {

    /** @var string Uploaded and indexed, not yet searchable. */
    public const STATUS_PENDING = 'pending';

    /** @var string Searchable by the assistant. */
    public const STATUS_APPROVED = 'approved';

    /** @var string Withdrawn; kept so past answers still resolve their citations. */
    public const STATUS_RETIRED = 'retired';

    /** @var string File area holding the original documents. */
    public const FILEAREA = 'sources';

    /** @var string[] Accepted file extensions. */
    public const ACCEPTED_TYPES = ['.txt', '.md', '.html', '.htm'];

    /**
     * Create a pending source from a file in the user's draft area and index it.
     *
     * @param int $courseid
     * @param string $title Name learners see in citations.
     * @param int $draftitemid Draft area holding exactly one file.
     * @return \stdClass The source record.
     */
    public static function create_from_draft(int $courseid, string $title, int $draftitemid): \stdClass {
        global $DB, $USER;

        $now = time();
        $source = (object) [
            'courseid' => $courseid,
            'title' => $title,
            'filename' => '',
            'contenthash' => '',
            'status' => self::STATUS_PENDING,
            'chunkcount' => 0,
            'usermodified' => $USER->id,
            'timecreated' => $now,
            'timemodified' => $now,
        ];
        $source->id = $DB->insert_record('block_afirst_source', $source);

        $context = context_course::instance($courseid);
        file_save_draft_area_files($draftitemid, $context->id, 'block_afirst_assistant', self::FILEAREA, $source->id,
            ['subdirs' => 0, 'maxfiles' => 1, 'accepted_types' => self::ACCEPTED_TYPES]);

        $file = self::get_file($source);
        if (!$file) {
            $DB->delete_records('block_afirst_source', ['id' => $source->id]);
            throw new \moodle_exception('error:nofile', 'block_afirst_assistant');
        }
        $source->filename = $file->get_filename();
        $source->contenthash = $file->get_contenthash();
        $source->chunkcount = self::index_text($source->id, self::extract_text($file));
        $DB->update_record('block_afirst_source', $source);
        return $source;
    }

    /**
     * Create a pending source from file content (bulk import from the command line) and index it.
     *
     * @param int $courseid
     * @param string $title Name learners see in citations.
     * @param string $filename Original file name; its extension must be one of ACCEPTED_TYPES.
     * @param string $content File content.
     * @return \stdClass|null The source record, or null when the course already has a source with identical content.
     */
    public static function create_from_content(int $courseid, string $title, string $filename, string $content): ?\stdClass {
        global $DB, $USER;

        $extension = '.' . strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        if (!in_array($extension, self::ACCEPTED_TYPES, true)) {
            throw new \moodle_exception('error:nofile', 'block_afirst_assistant');
        }
        // get_file_storage() loads lib/filestorage, which the class autoloader does not cover.
        $fs = get_file_storage();
        $contenthash = $fs::hash_from_string($content);
        if ($DB->record_exists('block_afirst_source', ['courseid' => $courseid, 'contenthash' => $contenthash])) {
            return null;
        }

        $transaction = $DB->start_delegated_transaction();
        $now = time();
        $source = (object) [
            'courseid' => $courseid,
            'title' => \core_text::substr($title, 0, 255),
            'filename' => clean_param($filename, PARAM_FILE),
            'contenthash' => $contenthash,
            'status' => self::STATUS_PENDING,
            'chunkcount' => 0,
            'usermodified' => $USER->id,
            'timecreated' => $now,
            'timemodified' => $now,
        ];
        $source->id = $DB->insert_record('block_afirst_source', $source);
        $file = $fs->create_file_from_string([
            'contextid' => context_course::instance($courseid)->id,
            'component' => 'block_afirst_assistant',
            'filearea' => self::FILEAREA,
            'itemid' => $source->id,
            'filepath' => '/',
            'filename' => $source->filename,
        ], $content);
        $source->chunkcount = self::index_text($source->id, self::extract_text($file));
        $DB->update_record('block_afirst_source', $source);
        $transaction->allow_commit();
        return $source;
    }

    /**
     * Replace the passages of a source with freshly cut ones.
     *
     * @param int $sourceid
     * @param string $text Plain text.
     * @return int Number of passages stored.
     */
    public static function index_text(int $sourceid, string $text): int {
        global $DB;

        $transaction = $DB->start_delegated_transaction();
        $DB->delete_records('block_afirst_chunk', ['sourceid' => $sourceid]);
        $chunks = text_processor::chunk(text_processor::split_sentences(text_processor::clean($text)));
        $records = [];
        foreach ($chunks as $i => $content) {
            $records[] = (object) [
                'sourceid' => $sourceid,
                'chunkno' => $i + 1,
                'content' => $content,
                'termfreq' => json_encode(text_processor::term_frequencies($content)),
            ];
        }
        $DB->insert_records('block_afirst_chunk', $records);
        $transaction->allow_commit();
        retriever::purge_index((int)$DB->get_field('block_afirst_source', 'courseid', ['id' => $sourceid], MUST_EXIST));
        return count($records);
    }

    /**
     * Make a source searchable.
     *
     * @param \stdClass $source
     */
    public static function approve(\stdClass $source): void {
        global $DB, $USER;
        // A source that yielded no passages (empty, scanned or wrongly encoded file) would be approved but never answer.
        if ((int)$DB->count_records('block_afirst_chunk', ['sourceid' => $source->id]) === 0) {
            throw new \moodle_exception('error:nochunks', 'block_afirst_assistant');
        }
        $DB->update_record('block_afirst_source', (object) [
            'id' => $source->id,
            'status' => self::STATUS_APPROVED,
            'approvedby' => $USER->id,
            'timeapproved' => time(),
            'usermodified' => $USER->id,
            'timemodified' => time(),
        ]);
        retriever::purge_index((int)$source->courseid);
    }

    /**
     * Withdraw a source from search. Its passages stay so logged citations remain verifiable.
     *
     * @param \stdClass $source
     */
    public static function retire(\stdClass $source): void {
        global $DB, $USER;
        $DB->update_record('block_afirst_source', (object) [
            'id' => $source->id,
            'status' => self::STATUS_RETIRED,
            'usermodified' => $USER->id,
            'timemodified' => time(),
        ]);
        retriever::purge_index((int)$source->courseid);
    }

    /**
     * The stored original document of a source.
     *
     * @param \stdClass $source
     * @return stored_file|null
     */
    public static function get_file(\stdClass $source): ?stored_file {
        $context = context_course::instance($source->courseid);
        $files = get_file_storage()->get_area_files($context->id, 'block_afirst_assistant', self::FILEAREA,
            $source->id, 'id', false);
        return $files ? reset($files) : null;
    }

    /**
     * Plain text of a stored document; HTML is reduced to its text.
     *
     * @param stored_file $file
     * @return string
     */
    public static function extract_text(stored_file $file): string {
        $content = $file->get_content();
        if (preg_match('/\.html?$/i', $file->get_filename())) {
            $content = html_to_text($content, 0, false);
        }
        return \core_text::convert($content, mb_detect_encoding($content, 'UTF-8, ISO-8859-1', true) ?: 'UTF-8', 'UTF-8');
    }
}
