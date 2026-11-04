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

/**
 * Run the A-FIRST reviewer audit set or bias sample against the course assistant.
 *
 * @package    block_afirst_assistant
 * @copyright  2026 Africa CDC
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use block_afirst_assistant\local\answerer;
use block_afirst_assistant\local\audit_runner;
use core_ai\manager;

define('CLI_SCRIPT', true);

require(__DIR__ . '/../../../config.php');
require_once($CFG->libdir . '/clilib.php');

[$options, $unrecognised] = cli_get_params([
    'courseid' => 0,
    'set' => '',
    'out' => '',
    'provider' => '',
    'userid' => 0,
    'approved-only' => false,
    'keep-logs' => false,
    'sleep' => 0,
    'limit' => 0,
    'help' => false,
], ['h' => 'help']);

$help = <<<EOT
Run the reviewer audit set or the bias sample through the course assistant and write a results CSV for grading.

Options:
  --courseid=ID       Course whose approved sources are searched (required).
  --set=PATH          audit_set.csv or bias_audit_sample.csv (required). The set is detected from the header.
  --out=PATH          Results CSV to write (required). A summary JSON is written next to it.
  --provider=LABEL    Name of the provider instance under test, for the results (required).
                      Core AI tries every enabled provider in turn, so enable ONLY the one under test.
  --userid=ID         User to ask as. Defaults to the main admin. Never use a learner account.
  --approved-only     Only rows with signoff_status = approved (the official baseline run).
  --keep-logs         Keep the run's rows in block_afirst_log. By default they are deleted after the run so
                      audit traffic never mixes with learner data (core AI's own action log is not touched).
  --sleep=MS          Pause between items, to stay under provider rate limits.
  --limit=N           Run only the first N included rows (smoke test).
  -h, --help          This help.

Example:
  sudo -u www-data php blocks/afirst_assistant/cli/run_audit.php --courseid=2 \\
      --set=/opt/afirst/courses/ds4h/evaluation/audit_set.csv --out=/opt/afirst/results/audit-openai.csv --provider=openai-gpt

EOT;

if ($unrecognised) {
    cli_error(get_string('cliunknowoption', 'admin', implode(PHP_EOL . '  ', $unrecognised)));
}
if ($options['help'] || !$options['courseid'] || !$options['set'] || !$options['out'] || $options['provider'] === '') {
    echo $help;
    exit($options['help'] ? 0 : 2);
}

$course = get_course((int)$options['courseid']);
$user = $options['userid'] ? core_user::get_user((int)$options['userid'], '*', MUST_EXIST) : get_admin();
\core\session\manager::set_user($user);

[$header, $rows] = audit_runner::read_csv($options['set']);
$set = audit_runner::detect_set($header);
$rows = array_values(array_filter($rows, fn(array $r) => audit_runner::include_row($r, (bool)$options['approved-only'])));
if ($options['limit'] > 0) {
    $rows = array_slice($rows, 0, (int)$options['limit']);
}
if (!$rows) {
    cli_error('No rows to run (check --approved-only and signoff_status).');
}

$provider = clean_param($options['provider'], PARAM_ALPHANUMEXT);
$runid = date('Ymd-His') . '-' . $provider;
$runner = new audit_runner(new answerer(\core\di::get(manager::class)), $course->id, $user->id, $runid, $provider);

$outdir = dirname($options['out']);
if (!is_dir($outdir) || !is_writable($outdir)) {
    cli_error("Cannot write to $outdir");
}
$handle = fopen($options['out'], 'w');
fputcsv($handle, audit_runner::RESULT_COLUMNS, ',', '"', '');

$results = [];
$total = count($rows);
try {
    foreach ($rows as $i => $row) {
        $r = $runner->run_item($row, $set);
        $results[] = $r;
        fputcsv($handle, array_values($r), ',', '"', '');
        fflush($handle);
        mtrace(sprintf('[%d/%d] %s %s%s (%d ms)', $i + 1, $total, $r['item_id'], $r['status'],
            $r['refusal_reason'] !== '' ? ':' . $r['refusal_reason'] : '', $r['latency_ms']));
        if ($options['sleep'] > 0 && $i + 1 < $total) {
            usleep((int)$options['sleep'] * 1000);
        }
    }
} finally {
    fclose($handle);
    if (!$options['keep-logs'] && $runner->get_logids()) {
        $DB->delete_records_list('block_afirst_log', 'id', $runner->get_logids());
    }
}

$summary = ['run_id' => $runid, 'set' => $set, 'course' => (int)$course->id, 'source_file' => basename($options['set']),
    'approved_only' => (bool)$options['approved-only'], 'minscore' => get_config('block_afirst_assistant', 'minscore')]
    + audit_runner::summarise($results);
$summarypath = preg_replace('/\.csv$/i', '', $options['out']) . '.summary.json';
file_put_contents($summarypath, json_encode($summary, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL);

mtrace('');
mtrace(json_encode($summary, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
mtrace("Results: {$options['out']}");
mtrace("Summary: $summarypath");
mtrace('Accuracy (A1) and final traceability (A2) still need the two graders (evaluation/fairness_checklist.md).');
