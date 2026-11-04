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
 * Reviewer page: reported answers, unanswered questions, headline metrics and log export.
 *
 * @package    block_afirst_assistant
 * @copyright  2026 Africa CDC
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use block_afirst_assistant\local\escalation_manager;
use block_afirst_assistant\local\log_export;

require(__DIR__ . '/../../config.php');

$courseid = required_param('courseid', PARAM_INT);
$tab = optional_param('tab', 'escalations', PARAM_ALPHA);

$course = get_course($courseid);
require_login($course);
$context = context_course::instance($course->id);
require_capability('block/afirst_assistant:review', $context);

$url = new moodle_url('/blocks/afirst_assistant/report.php', ['courseid' => $course->id, 'tab' => $tab]);
$PAGE->set_url($url);
$PAGE->set_context($context);
$PAGE->set_pagelayout('incourse');
$PAGE->set_title(get_string('reviewqueue', 'block_afirst_assistant'));
$PAGE->set_heading($course->fullname);

if (optional_param('export', 0, PARAM_BOOL)) {
    require_sesskey();
    log_export::download($course->id);
    die();
}

if ($resolveid = optional_param('resolve', 0, PARAM_INT)) {
    require_sesskey();
    $DB->get_record('block_afirst_escalation', ['id' => $resolveid, 'courseid' => $course->id], 'id', MUST_EXIST);
    escalation_manager::resolve($resolveid, required_param('resolution', PARAM_TEXT));
    redirect($url);
}

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('reviewqueue', 'block_afirst_assistant'));

// Headline metrics for the BASIC evaluation (Capstone Proposal 3.7).
$citedlike = $DB->sql_like('citations', ':citedpattern');
$stats = $DB->get_record_sql("
    SELECT COUNT(1) AS total,
           SUM(refused) AS refused,
           SUM(CASE WHEN refused = 0 AND $citedlike THEN 1 ELSE 0 END) AS cited
      FROM {block_afirst_log}
     WHERE courseid = :courseid", ['courseid' => $course->id, 'citedpattern' => '%"cited":true%']);
$open = $DB->count_records('block_afirst_escalation', ['courseid' => $course->id, 'status' => escalation_manager::STATUS_OPEN]);
$answered = max(1, (int)$stats->total - (int)$stats->refused);
echo html_writer::start_div('d-flex flex-wrap gap-3 mb-3');
foreach ([
    get_string('interactions', 'block_afirst_assistant') => (int)$stats->total,
    get_string('refusalrate', 'block_afirst_assistant') => $stats->total ? round(100 * $stats->refused / $stats->total) . '%' : '-',
    get_string('citedanswers', 'block_afirst_assistant') => $stats->total ? round(100 * $stats->cited / $answered) . '%' : '-',
    get_string('escalations', 'block_afirst_assistant') . ' (' . get_string('open', 'block_afirst_assistant') . ')' => $open,
] as $label => $value) {
    echo html_writer::div(html_writer::div($value, 'h4 mb-0') . html_writer::div($label, 'small text-muted'),
        'border rounded p-2 px-3');
}
echo html_writer::end_div();

$tabs = [
    new tabobject('escalations', new moodle_url($url, ['tab' => 'escalations']),
        get_string('escalations', 'block_afirst_assistant')),
    new tabobject('unanswered', new moodle_url($url, ['tab' => 'unanswered']),
        get_string('unanswered', 'block_afirst_assistant')),
];
echo $OUTPUT->tabtree($tabs, $tab);

if ($tab === 'unanswered') {
    // The unanswered-question log doubles as a content-gap inventory; learner identity is not shown.
    $rows = $DB->get_records('block_afirst_log', ['courseid' => $course->id, 'refused' => 1], 'timecreated DESC',
        'id, question, refusalreason, topscore, timecreated', 0, 200);
    $table = new html_table();
    $table->head = [get_string('questionheading', 'block_afirst_assistant'), get_string('reportreason', 'block_afirst_assistant'),
        get_string('topscore', 'block_afirst_assistant'), get_string('date')];
    foreach ($rows as $row) {
        $table->data[] = [
            s($row->question),
            get_string('refusalreason:' . $row->refusalreason, 'block_afirst_assistant'),
            $row->topscore === null ? '-' : format_float($row->topscore, 2),
            userdate($row->timecreated, get_string('strftimedatetimeshort', 'langconfig')),
        ];
    }
    echo html_writer::table($table);
} else {
    $sql = "SELECT e.*, l.question, l.answer
              FROM {block_afirst_escalation} e
              JOIN {block_afirst_log} l ON l.id = e.logid
             WHERE e.courseid = ?
          ORDER BY CASE WHEN e.status = 'open' THEN 0 ELSE 1 END, e.timecreated ASC";
    foreach ($DB->get_records_sql($sql, [$course->id], 0, 200) as $esc) {
        $badge = $esc->status === escalation_manager::STATUS_RESOLVED
            ? html_writer::span(get_string('resolved', 'block_afirst_assistant'), 'badge bg-success')
            : (escalation_manager::is_overdue($esc)
                ? html_writer::span(get_string('overdue', 'block_afirst_assistant'), 'badge bg-danger')
                : html_writer::span(get_string('open', 'block_afirst_assistant'), 'badge bg-warning text-dark'));
        echo html_writer::start_div('card mb-3');
        echo html_writer::start_div('card-body');
        echo html_writer::div($badge . ' ' . html_writer::span(get_string('reason:' . $esc->reason, 'block_afirst_assistant'),
            'fw-bold') . ' · ' . get_string('deadline', 'block_afirst_assistant') . ': ' .
            userdate($esc->timecreated + escalation_manager::REVIEW_WINDOW, get_string('strftimedatetimeshort', 'langconfig')),
            'mb-2');
        echo html_writer::tag('p', html_writer::tag('strong', get_string('questionheading', 'block_afirst_assistant') . ': ') .
            s($esc->question));
        echo html_writer::tag('p', html_writer::tag('strong', 'AI: ') . s($esc->answer ?? '-'));
        if ($esc->comment) {
            echo html_writer::tag('p', html_writer::tag('em', s($esc->comment)));
        }
        if ($esc->status === escalation_manager::STATUS_OPEN) {
            echo html_writer::start_tag('form', ['method' => 'post', 'action' => $url->out(false)]);
            echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()]);
            echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'resolve', 'value' => $esc->id]);
            echo html_writer::label(get_string('resolution', 'block_afirst_assistant'), 'resolution' . $esc->id, false,
                ['class' => 'form-label']);
            echo html_writer::tag('textarea', '', ['name' => 'resolution', 'id' => 'resolution' . $esc->id,
                'class' => 'form-control mb-2', 'rows' => 2, 'required' => 'required']);
            echo html_writer::tag('button', get_string('resolve', 'block_afirst_assistant'),
                ['type' => 'submit', 'class' => 'btn btn-primary btn-sm']);
            echo html_writer::end_tag('form');
        } else {
            echo html_writer::tag('p', html_writer::tag('strong', get_string('resolution', 'block_afirst_assistant') . ': ') .
                s($esc->resolution), ['class' => 'mb-0']);
        }
        echo html_writer::end_div();
        echo html_writer::end_div();
    }
}

echo $OUTPUT->single_button(new moodle_url($url, ['export' => 1, 'sesskey' => sesskey()]),
    get_string('exportlog', 'block_afirst_assistant'), 'post');
echo $OUTPUT->footer();
