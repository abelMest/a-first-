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
 * Runs the reviewer audit set or the bias sample through the real answerer and computes the automatic metrics.
 *
 * Input and output formats are specified in evaluation/README.md of the A-FIRST repository. The harness fills
 * the automatic columns; accuracy (A1) and final traceability (A2) still come from two human graders.
 *
 * @package    block_afirst_assistant
 * @copyright  2026 Africa CDC
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class audit_runner {

    /** @var string Reviewer-audited question set (audit_set.csv). */
    public const SET_AUDIT = 'audit';

    /** @var string Multilingual bias sample (bias_audit_sample.csv). */
    public const SET_BIAS = 'bias';

    /** @var string[] Results columns, in order (evaluation/README.md, "Suggested results file"). */
    public const RESULT_COLUMNS = [
        'run_id', 'provider', 'model', 'item_id', 'set', 'language', 'topic_area', 'type', 'module', 'status',
        'refusal_reason', 'answer', 'cited_passages', 'cited_modules', 'anchor_in_citations', 'latency_ms', 'top_score',
        'retrieval_query', 'support_score', 'top_similarity',
        'A1_accuracy', 'A2_traceability', 'A3_refusal', 'A4_harm', 'A4_flags', 'A5_language', 'grader', 'grader_comment',
    ];

    /** @var int[] Log rows written by this run, so the caller can remove them from the study log. */
    private array $logids = [];

    /**
     * Constructor.
     *
     * @param answerer $answerer
     * @param int $courseid Course whose approved sources are searched.
     * @param int $userid User the questions are asked as (never a learner).
     * @param string $runid
     * @param string $provider Label of the single provider instance enabled for this run.
     */
    public function __construct(
        private answerer $answerer,
        private int $courseid,
        private int $userid,
        private string $runid,
        private string $provider,
    ) {
    }

    /**
     * Read a UTF-8 CSV into rows keyed by header name.
     *
     * @param string $path
     * @return array{0: string[], 1: array<int, array<string, string>>} Header and rows.
     */
    public static function read_csv(string $path): array {
        $handle = fopen($path, 'r');
        if ($handle === false) {
            throw new \moodle_exception('cannotopenfile', 'error', '', $path);
        }
        $header = fgetcsv($handle, null, ',', '"', '');
        if (!$header) {
            fclose($handle);
            throw new \moodle_exception('csvemptyfile', 'error');
        }
        $header[0] = preg_replace('/^\xEF\xBB\xBF/', '', $header[0]);
        $header = array_map('trim', $header);
        $rows = [];
        while (($values = fgetcsv($handle, null, ',', '"', '')) !== false) {
            if ($values === [null] || $values === ['']) {
                continue;
            }
            $values = array_pad($values, count($header), '');
            $rows[] = array_combine($header, array_slice($values, 0, count($header)));
        }
        fclose($handle);
        return [$header, $rows];
    }

    /**
     * Which set a header belongs to.
     *
     * @param string[] $header
     * @return string SET_AUDIT or SET_BIAS.
     */
    public static function detect_set(array $header): string {
        if (in_array('prompt', $header, true) && in_array('language', $header, true)) {
            return self::SET_BIAS;
        }
        if (in_array('question', $header, true)) {
            return self::SET_AUDIT;
        }
        throw new \moodle_exception('csvloaderror', 'error', '', 'Neither a question nor a prompt column was found');
    }

    /**
     * Whether a row takes part in the run.
     *
     * @param array<string, string> $row
     * @param bool $approvedonly Official baseline: only rows both reviewers approved.
     * @return bool
     */
    public static function include_row(array $row, bool $approvedonly): bool {
        $signoff = strtolower(trim($row['signoff_status'] ?? ''));
        if ($signoff === 'rejected') {
            return false;
        }
        return !$approvedonly || $signoff === 'approved' || !array_key_exists('signoff_status', $row);
    }

    /**
     * Split an expected_source value into module and anchor phrase.
     *
     * @param string $expected For example: Module 2.1: "Data wrangling is".
     * @return array{module: string, anchor: string}|null Null for NONE or an unparseable value.
     */
    public static function parse_anchor(string $expected): ?array {
        if (!preg_match('/^\s*Module\s+(\d+\.\d+)\s*:\s*"(.+)"\s*$/su', $expected, $m)) {
            return null;
        }
        return ['module' => $m[1], 'anchor' => $m[2]];
    }

    /**
     * Normalise text the same way indexed passages are, for anchor matching.
     *
     * @param string $text
     * @return string
     */
    public static function normalise(string $text): string {
        return \core_text::strtolower(text_processor::clean($text));
    }

    /**
     * The course module number a source title refers to, if any.
     *
     * @param string $title For example "Module 2.1 Data Cleaning".
     * @return string|null
     */
    public static function module_of(string $title): ?string {
        return preg_match('/\b(\d+\.\d+)\b/', $title, $m) ? $m[1] : null;
    }

    /**
     * Ask one item and fill the automatic result columns.
     *
     * @param array<string, string> $row
     * @param string $set
     * @return array<string, string|int|float|null> Keyed by RESULT_COLUMNS.
     */
    public function run_item(array $row, string $set): array {
        global $DB;

        $question = trim($set === self::SET_BIAS ? ($row['prompt'] ?? '') : ($row['question'] ?? ''));
        $type = trim($row['type'] ?? '');
        $result = $this->answerer->answer($this->courseid, $this->userid, $question);
        $this->logids[] = $result->logid;
        $log = $DB->get_record('block_afirst_log', ['id' => $result->logid], '*', MUST_EXIST);

        $citedchunks = [];
        if ($result->status === answer_result::STATUS_ANSWERED && $log->citations) {
            foreach (json_decode($log->citations, true) ?: [] as $c) {
                if (!empty($c['cited'])) {
                    $citedchunks[] = (int)$c['chunkid'];
                }
            }
        }
        $chunks = [];
        if ($citedchunks) {
            [$insql, $inparams] = $DB->get_in_or_equal($citedchunks);
            $chunks = $DB->get_records_sql(
                "SELECT c.id, c.content, s.title
                   FROM {block_afirst_chunk} c
                   JOIN {block_afirst_source} s ON s.id = c.sourceid
                  WHERE c.id $insql", $inparams);
        }

        $modules = [];
        foreach ($chunks as $chunk) {
            if ($module = self::module_of($chunk->title)) {
                $modules[$module] = $module;
            }
        }
        sort($modules);

        $anchorhit = '';
        if ($type !== 'out_of_scope' && ($anchor = self::parse_anchor($row['expected_source'] ?? ''))) {
            $needle = self::normalise($anchor['anchor']);
            $anchorhit = 0;
            foreach ($chunks as $chunk) {
                if ($needle !== '' && str_contains(self::normalise($chunk->content), $needle)) {
                    $anchorhit = 1;
                    break;
                }
            }
        }

        $out = array_fill_keys(self::RESULT_COLUMNS, '');
        return array_merge($out, [
            'run_id' => $this->runid,
            'provider' => $this->provider,
            'model' => $log->model ?? '',
            'item_id' => $row['id'] ?? '',
            'set' => $set,
            'language' => $row['language'] ?? 'English',
            'topic_area' => $row['topic_area'] ?? '',
            'type' => $type,
            'module' => $row['module'] ?? '',
            'status' => $result->status,
            'refusal_reason' => $result->reason ?? '',
            // Withheld (uncited) answers are kept for graders, who must see what the model said.
            'answer' => $log->answer ?? '',
            'cited_passages' => implode(';', $citedchunks),
            'cited_modules' => implode(';', $modules),
            'anchor_in_citations' => $anchorhit,
            'latency_ms' => (int)$log->latencyms,
            'top_score' => $log->topscore === null ? '' : round((float)$log->topscore, 4),
            'top_similarity' => ($log->topsimilarity ?? null) === null ? '' : round((float)$log->topsimilarity, 4),
            'support_score' => $log->supportscore === null ? '' : round((float)$log->supportscore, 4),
            'retrieval_query' => $log->retrievalquery ?? '',
        ]);
    }

    /**
     * Log rows written so far.
     *
     * @return int[]
     */
    public function get_logids(): array {
        return $this->logids;
    }

    /**
     * Automatic metrics over a run (evaluation/README.md): refusal precision and recall with out-of-scope as the
     * positive class, automatic traceability, citation rate of answers, latency percentiles, and per-language rates.
     *
     * @param array[] $results Rows from run_item().
     * @return array<string, mixed>
     */
    public static function summarise(array $results): array {
        $tp = $fp = $fn = $tn = $errors = 0;
        $inscope = $anchored = $answered = $answeredcited = 0;
        $latencies = [];
        $reasons = [];
        $bylanguage = [];
        foreach ($results as $r) {
            $latencies[] = (int)$r['latency_ms'];
            $lang = $r['language'] ?: 'English';
            $bylanguage[$lang] ??= ['items' => 0, 'answered' => 0, 'refused' => 0, 'error' => 0, 'anchored' => 0, 'inscope' => 0];
            $bylanguage[$lang]['items']++;
            $bylanguage[$lang][$r['status']] = ($bylanguage[$lang][$r['status']] ?? 0) + 1;
            if ($r['refusal_reason'] !== '') {
                $reasons[$r['refusal_reason']] = ($reasons[$r['refusal_reason']] ?? 0) + 1;
            }
            if ($r['status'] === answer_result::STATUS_ERROR) {
                $errors++;
                continue;
            }
            $oos = $r['type'] === 'out_of_scope';
            $refused = $r['status'] === answer_result::STATUS_REFUSED;
            if ($oos && $refused) {
                $tp++;
            } else if (!$oos && $refused) {
                $fp++;
            } else if ($oos) {
                $fn++;
            } else {
                $tn++;
            }
            if ($r['status'] === answer_result::STATUS_ANSWERED) {
                $answered++;
                $answeredcited += $r['cited_passages'] !== '' ? 1 : 0;
            }
            if (!$oos) {
                $inscope++;
                $bylanguage[$lang]['inscope']++;
                if ((string)$r['anchor_in_citations'] === '1') {
                    $anchored++;
                    $bylanguage[$lang]['anchored']++;
                }
            }
        }
        sort($latencies);
        $pct = fn(int $num, int $den) => $den ? round(100 * $num / $den, 1) : null;
        foreach ($bylanguage as &$l) {
            $l['answer_rate_pct'] = $pct($l['answered'], $l['items']);
            $l['auto_traceability_pct'] = $pct($l['anchored'], $l['inscope']);
        }
        unset($l);
        ksort($reasons);
        return [
            'items' => count($results),
            'errors' => $errors,
            'refusal_precision_pct' => $pct($tp, $tp + $fp),
            'refusal_recall_pct' => $pct($tp, $tp + $fn),
            'false_refusals' => $fp,
            'answered_out_of_scope' => $fn,
            'answered' => $answered,
            'answered_with_citation_pct' => $pct($answeredcited, $answered),
            'auto_traceability_pct' => $pct($anchored, $inscope),
            'latency_p50_ms' => self::percentile($latencies, 50),
            'latency_p95_ms' => self::percentile($latencies, 95),
            'refusal_reasons' => $reasons,
            'by_language' => $bylanguage,
        ];
    }

    /**
     * Nearest-rank percentile of a sorted list.
     *
     * @param int[] $sorted
     * @param int $p
     * @return int|null
     */
    public static function percentile(array $sorted, int $p): ?int {
        if (!$sorted) {
            return null;
        }
        $rank = (int)ceil($p / 100 * count($sorted));
        return $sorted[max(0, min(count($sorted) - 1, $rank - 1))];
    }
}
