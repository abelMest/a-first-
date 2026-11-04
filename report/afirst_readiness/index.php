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
 * Course readiness report: one row per learner per scenario.
 *
 * @package    report_afirst_readiness
 * @copyright  2026 Africa CDC
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use report_afirst_readiness\local\readiness_report;
use report_afirst_readiness\local\rules;
use report_afirst_readiness\local\scenario;

require(__DIR__ . '/../../config.php');

$courseid = required_param('id', PARAM_INT);
$scenarioid = optional_param('scenario', 0, PARAM_INT);
$download = optional_param('download', 0, PARAM_BOOL);

$course = get_course($courseid);
require_login($course);
$context = context_course::instance($course->id);
require_capability('report/afirst_readiness:view', $context);

$url = new moodle_url('/report/afirst_readiness/index.php', ['id' => $course->id]);
$PAGE->set_url($url);
$PAGE->set_context($context);
$PAGE->set_pagelayout('report');
$PAGE->set_title(get_string('pluginname', 'report_afirst_readiness'));
$PAGE->set_heading($course->fullname);

$records = $DB->get_records('report_afirst_readiness', ['courseid' => $course->id], 'timecreated DESC');
$record = $scenarioid && isset($records[$scenarioid]) ? $records[$scenarioid] : reset($records);

if ($download && $record) {
    require_sesskey();
    $report = new readiness_report($record);
    $domains = scenario::domain_titles($report->scenario);
    $filename = clean_filename('readiness_' . $record->scenarioid . '_' . date('Ymd') . '.csv');
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    $out = fopen('php://output', 'w');
    // Pseudonymised: a salted hash replaces the user id, so the file can be analysed without identities (Prop 3.6).
    $salt = get_site_identifier() . ':' . $record->id;
    fputcsv($out, array_merge(['learner', 'status', 'completed', 'critical'], array_keys($domains),
        ['knowledge', 'supervisor', 'reasons']), ',', '"', '');
    foreach ($report->rows() as $id => $r) {
        fputcsv($out, array_merge([
            substr(hash('sha256', $salt . ':' . $id), 0, 12), $r['status'], (int)$r['completed'], (int)$r['critical'],
        ], array_map(fn($d) => $r['domains'][$d] ?? '', array_keys($domains)), [
            json_encode($r['knowledge']), $r['supervisor'] ?? '',
            implode('; ', array_map([$report, 'reason_text'], $r['reasons'])),
        ]), ',', '"', '');
    }
    fclose($out);
    die();
}

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('pluginname', 'report_afirst_readiness'));

if (!$record) {
    echo $OUTPUT->notification(get_string('noscenarios', 'report_afirst_readiness'), 'info', false);
    echo $OUTPUT->footer();
    die();
}

if (count($records) > 1) {
    $options = array_map(fn($r) => $r->scenarioid . ' v' . $r->version, $records);
    echo $OUTPUT->single_select($url, 'scenario', $options, $record->id, null);
}

$report = new readiness_report($record);
$domains = scenario::domain_titles($report->scenario);
$rows = $report->rows();
$cmurl = new moodle_url('/mod/lesson/view.php', ['id' => $record->lessoncmid]);
echo html_writer::tag('p', get_string('scenario', 'report_afirst_readiness') . ': ' .
    html_writer::link($cmurl, format_string($report->scenario['title'])) . ' (v' . s($record->version) . ')');
echo $OUTPUT->notification(get_string('disclaimer', 'report_afirst_readiness', s($report->scenario['status'])) . ' ' .
    get_string('thresholds', 'report_afirst_readiness', (object)$report->settings['thresholds']), 'info', false);

// Summary counts.
$counts = array_count_values(array_column($rows, 'status'));
echo html_writer::start_div('d-flex flex-wrap gap-3 mb-3');
foreach ([rules::INDEPENDENT, rules::SUPERVISED, rules::NOT_YET, rules::IN_PROGRESS, rules::NOT_STARTED] as $status) {
    echo html_writer::div(html_writer::div($counts[$status] ?? 0, 'h4 mb-0') .
        html_writer::div(get_string('status:' . $status, 'report_afirst_readiness'), 'small text-muted'), 'border rounded p-2 px-3');
}
echo html_writer::end_div();

$badge = [
    rules::INDEPENDENT => 'bg-success', rules::SUPERVISED => 'bg-info text-dark', rules::NOT_YET => 'bg-warning text-dark',
    rules::IN_PROGRESS => 'bg-secondary', rules::NOT_STARTED => 'bg-light text-dark',
];
$table = new html_table();
$table->head = array_merge([get_string('learner', 'report_afirst_readiness'), get_string('status', 'report_afirst_readiness')],
    array_values($domains), [get_string('knowledge', 'report_afirst_readiness'), get_string('supervisor', 'report_afirst_readiness'),
    get_string('reasons', 'report_afirst_readiness')]);
$fmt = fn($v) => $v === null ? '-' : format_float($v, 0) . '%';
foreach ($rows as $r) {
    $knowledge = $r['knowledge'] ? implode(', ', array_map(fn($k, $v) => s($k) . ' ' . $fmt($v), array_keys($r['knowledge']),
        $r['knowledge'])) : '-';
    $status = html_writer::span(get_string('status:' . $r['status'], 'report_afirst_readiness'), 'badge ' . $badge[$r['status']]);
    if ($r['critical']) {
        $status .= ' ' . html_writer::span(get_string('critical', 'report_afirst_readiness'), 'badge bg-danger');
    }
    $table->data[] = array_merge([fullname($r['user']), $status],
        array_map(fn($d) => $fmt($r['domains'][$d] ?? null), array_keys($domains)),
        [$knowledge, $r['supervisorconfigured'] ? $fmt($r['supervisor']) : '-',
            implode('<br>', array_map(fn($x) => s($report->reason_text($x)), $r['reasons']))]);
}
echo html_writer::table($table);

echo $OUTPUT->single_button(new moodle_url($url, ['scenario' => $record->id, 'download' => 1, 'sesskey' => sesskey()]),
    get_string('download', 'report_afirst_readiness'), 'post');
echo $OUTPUT->footer();
