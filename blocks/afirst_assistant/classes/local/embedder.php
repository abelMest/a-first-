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

use core\http_client;

/**
 * Text embeddings from a local Ollama server, for meaning-based (hybrid) retrieval.
 *
 * Moodle 5.0's AI subsystem has no embedding action, so this calls Ollama's /api/embed directly, through Moodle's
 * HTTP client (the site's outbound-request security rules apply). It is off until an endpoint is configured, and
 * every failure degrades to keyword-only retrieval rather than an error for the learner.
 *
 * @package    block_afirst_assistant
 * @copyright  2026 Africa CDC
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class embedder {

    /** @var float Default minimum cosine similarity for a passage to count as grounded by meaning alone. */
    public const DEFAULT_MIN_SIMILARITY = 0.7;

    /** @var int Texts sent per request. */
    private const BATCH = 16;

    /** @var int Characters of each text sent for embedding (the model's window is limited). */
    private const MAX_CHARS = 2000;

    /**
     * Whether hybrid retrieval is configured.
     *
     * @return bool
     */
    public static function enabled(): bool {
        return trim((string)get_config('block_afirst_assistant', 'embedendpoint')) !== '';
    }

    /**
     * Embed course passages.
     *
     * @param string[] $texts
     * @return array<int, float[]|null> Unit vectors in the same order, null where the call failed.
     */
    public static function embed_documents(array $texts): array {
        return self::embed($texts, 'search_document: ');
    }

    /**
     * Embed a learner question.
     *
     * @param string $text
     * @return float[]|null Unit vector, or null on failure.
     */
    public static function embed_query(string $text): ?array {
        return self::embed([$text], 'search_query: ')[0] ?? null;
    }

    /**
     * Call the embedding endpoint in batches.
     *
     * @param string[] $texts
     * @param string $prefix Task prefix used by nomic-embed models; other models get the plain text.
     * @return array<int, float[]|null>
     */
    private static function embed(array $texts, string $prefix): array {
        $endpoint = rtrim(trim((string)get_config('block_afirst_assistant', 'embedendpoint')), '/');
        $model = trim((string)get_config('block_afirst_assistant', 'embedmodel')) ?: 'nomic-embed-text';
        if ($endpoint === '' || !$texts) {
            return array_fill(0, count($texts), null);
        }
        $prefix = str_contains($model, 'nomic') ? $prefix : '';
        $body = ['model' => $model, 'keep_alive' => '1h'];
        if (get_config('block_afirst_assistant', 'embedcpu') !== '0') {
            // Keep the small embedding model off the GPU, so it does not evict the answering model on a shared GPU
            // (on the 2 GB laptop GPU they swapped on every question, doubling answer time).
            $body['options'] = ['num_gpu' => 0];
        }
        $client = \core\di::get(http_client::class);
        $vectors = [];
        foreach (array_chunk(array_values($texts), self::BATCH) as $batch) {
            $input = array_map(fn(string $t) => $prefix . \core_text::substr($t, 0, self::MAX_CHARS), $batch);
            try {
                $response = $client->post($endpoint . '/api/embed', [
                    'json' => $body + ['input' => $input],
                    'timeout' => 120,
                ]);
                $data = json_decode((string)$response->getBody(), true);
                $embeddings = $data['embeddings'] ?? [];
            } catch (\Throwable $e) {
                debugging('Course assistant embedding failed: ' . $e->getMessage(), DEBUG_DEVELOPER);
                $embeddings = [];
            }
            foreach (array_keys($batch) as $i) {
                $vectors[] = isset($embeddings[$i]) && is_array($embeddings[$i]) ? self::normalise($embeddings[$i]) : null;
            }
        }
        return $vectors;
    }

    /**
     * Scale a vector to unit length, so cosine similarity is a dot product.
     *
     * @param float[] $vector
     * @return float[]|null Null for a zero vector.
     */
    public static function normalise(array $vector): ?array {
        $norm = sqrt(array_sum(array_map(fn($v) => $v * $v, $vector)));
        return $norm > 0 ? array_map(fn($v) => (float)$v / $norm, $vector) : null;
    }

    /**
     * Cosine similarity of two unit vectors.
     *
     * @param float[] $a
     * @param float[] $b
     * @return float
     */
    public static function similarity(array $a, array $b): float {
        $sum = 0.0;
        foreach ($a as $i => $v) {
            $sum += $v * ($b[$i] ?? 0.0);
        }
        return $sum;
    }

    /**
     * Compact storage form: float32 little-endian, base64 (a 768-dimension vector is about 4 KB).
     *
     * @param float[] $vector
     * @return string
     */
    public static function pack(array $vector): string {
        return base64_encode(pack('g*', ...$vector));
    }

    /**
     * Inverse of pack().
     *
     * @param string|null $packed
     * @return float[]|null
     */
    public static function unpack(?string $packed): ?array {
        if ($packed === null || $packed === '') {
            return null;
        }
        $binary = base64_decode($packed, true);
        return $binary === false || strlen($binary) % 4 !== 0 ? null : array_values(unpack('g*', $binary));
    }
}
