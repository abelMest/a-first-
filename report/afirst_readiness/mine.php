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
 * "My readiness": a learner's own readiness result, what it means, and what to work on next.
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
$course = get_course($courseid);
require_login($course);
$context = context_course::instance($course->id);
require_capability('report/afirst_readiness:viewown', $context);

$url = new moodle_url('/report/afirst_readiness/mine.php', ['id' => $course->id]);
$PAGE->set_url($url);
$PAGE->set_context($context);
$PAGE->set_pagelayout('incourse');
$PAGE->set_title(get_string('mine', 'report_afirst_readiness'));
$PAGE->set_heading($course->fullname);

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('mine', 'report_afirst_readiness'));

$modinfo = get_fast_modinfo($course);
$record = null;
foreach ($DB->get_records('report_afirst_readiness', ['courseid' => $course->id], 'timecreated DESC') as $r) {
    try {
        if ($modinfo->get_cm($r->lessoncmid)->uservisible) {
            $record = $r;
            break;
        }
    } catch (moodle_exception $e) {
        continue;
    }
}
if (!$record) {
    echo $OUTPUT->notification(get_string('mine:none', 'report_afirst_readiness'), 'info');
    echo $OUTPUT->footer();
    exit;
}

$report = new readiness_report($record);
$row = $report->row((int)$USER->id);
$lesson = $modinfo->get_cm($record->lessoncmid);
$titles = scenario::domain_titles($report->scenario);
$t = ($report->settings['thresholds'] ?? []) + rules::DEFAULT_THRESHOLDS;
$badge = [rules::INDEPENDENT => 'bg-success', rules::SUPERVISED => 'bg-info text-dark', rules::NOT_YET => 'bg-warning text-dark',
    rules::IN_PROGRESS => 'bg-secondary', rules::NOT_STARTED => 'bg-light text-dark border'][$row['status']];

echo html_writer::tag('p', get_string('mine:scenario', 'report_afirst_readiness',
    html_writer::link($lesson->url, format_string($lesson->name))));
echo html_writer::div(
    html_writer::tag('span', get_string('status:' . $row['status'], 'report_afirst_readiness'),
        ['class' => "badge $badge fs-5 me-2"]) .
    html_writer::tag('span', get_string('mine:status:' . $row['status'], 'report_afirst_readiness')),
    'my-3');

if (in_array($row['status'], [rules::NOT_STARTED, rules::IN_PROGRESS], true)) {
    echo $OUTPUT->single_button($lesson->url, get_string($row['status'] === rules::NOT_STARTED ? 'mine:start' : 'mine:continue',
        'report_afirst_readiness'), 'get');
} else {
    if ($row['critical']) {
        echo $OUTPUT->notification(get_string('mine:critical', 'report_afirst_readiness'), 'warning', false);
    }

    // Domains: the learner's score against the two bars.
    $table = new html_table();
    $table->head = [get_string('mine:domain', 'report_afirst_readiness'), get_string('mine:score', 'report_afirst_readiness'),
        get_string('mine:revisit', 'report_afirst_readiness')];
    $table->attributes['class'] = 'generaltable';
    $revisit = $report->revisit($row, get_fast_modinfo($course, $USER->id));
    foreach ($titles as $id => $title) {
        $value = $row['domains'][$id] ?? null;
        $text = $value === null ? get_string('mine:notassessed', 'report_afirst_readiness') : format_float($value, 0) . '%';
        $class = $value === null ? '' : ($value >= $t['independent_domain_min'] ? 'text-success'
            : ($value >= $t['supervised_domain_min'] ? 'text-info' : 'text-danger'));
        $links = array_map(fn($cm) => html_writer::link($cm->url, format_string($cm->name)), $revisit[$id] ?? []);
        $table->data[] = [s($title), html_writer::span($text, "fw-bold $class"), implode('<br>', $links)];
    }
    echo html_writer::table($table);
    echo html_writer::tag('p', get_string('mine:bars', 'report_afirst_readiness', (object)[
        'independent' => $t['independent_domain_min'], 'supervised' => $t['supervised_domain_min']]),
        ['class' => 'small text-muted']);

    // Knowledge Checks below the bar, with links.
    $kc = [];
    foreach ($row['knowledge'] as $label => $value) {
        if ($value === null || $value < $t['knowledge_each_min']) {
            $cmid = (int)($report->settings['knowledge'][$label] ?? 0);
            $name = $label;
            try {
                $cm = $modinfo->get_cm($cmid);
                $name = $cm->uservisible ? html_writer::link($cm->url, format_string($cm->name)) : format_string($cm->name);
            } catch (moodle_exception $e) {
                $name = s($label);
            }
            $kc[] = $name . ': ' . ($value === null ? get_string('mine:nottaken', 'report_afirst_readiness')
                : format_float($value, 0) . '%');
        }
    }
    if ($kc) {
        echo $OUTPUT->heading(get_string('mine:knowledge', 'report_afirst_readiness', $t['knowledge_each_min']), 4);
        echo html_writer::alist($kc);
    }
    if (!empty($row['supervisorconfigured']) && $row['supervisor'] === null) {
        echo html_writer::tag('p', get_string('mine:supervisorpending', 'report_afirst_readiness'));
    }
}

echo html_writer::tag('p', get_string('mine:disclaimer', 'report_afirst_readiness') .
    (($report->scenario['status'] ?? '') !== 'approved' ? ' ' . get_string('mine:draft', 'report_afirst_readiness') : ''),
    ['class' => 'small text-muted mt-4']);
echo $OUTPUT->footer();
