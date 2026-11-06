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
 * Manage the approved sources of a course.
 *
 * @package    block_afirst_assistant
 * @copyright  2026 Africa CDC
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use block_afirst_assistant\form\source_form;
use block_afirst_assistant\local\source_manager;

require(__DIR__ . '/../../config.php');

$courseid = required_param('courseid', PARAM_INT);
$action = optional_param('action', '', PARAM_ALPHA);

$course = get_course($courseid);
require_login($course);
$context = context_course::instance($course->id);
require_capability('block/afirst_assistant:managesources', $context);

$url = new moodle_url('/blocks/afirst_assistant/sources.php', ['courseid' => $course->id]);
$PAGE->set_url($url);
$PAGE->set_context($context);
$PAGE->set_pagelayout('incourse');
$PAGE->set_title(get_string('managesources', 'block_afirst_assistant'));
$PAGE->set_heading($course->fullname);

if ($action === 'importscorm') {
    require_sesskey();
    $results = source_manager::import_scorm($course->id);
    $imported = count(array_filter($results, fn($r) => $r['status'] === 'imported'));
    redirect($url, get_string('scormimported', 'block_afirst_assistant', (object) [
        'imported' => $imported,
        'total' => count($results),
    ]), null, \core\output\notification::NOTIFY_SUCCESS);
} else if ($action !== '') {
    require_sesskey();
    $source = $DB->get_record('block_afirst_source', ['id' => required_param('id', PARAM_INT), 'courseid' => $course->id],
        '*', MUST_EXIST);
    if ($action === 'approve') {
        source_manager::approve($source);
    } else if ($action === 'retire') {
        source_manager::retire($source);
    } else if ($action === 'suggestions') {
        source_manager::set_suggestions($source, preg_split('/\R/u', optional_param('suggestions', '', PARAM_TEXT)));
    }
    redirect($url);
}

$form = new source_form($url);
$form->set_data(['courseid' => $course->id]);
if ($form->is_cancelled()) {
    redirect($url);
} else if ($data = $form->get_data()) {
    $source = source_manager::create_from_draft($course->id, $data->title, $data->sourcefile);
    redirect($url, get_string('chunks', 'block_afirst_assistant') . ': ' . $source->chunkcount, null,
        \core\output\notification::NOTIFY_SUCCESS);
}

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('managesources', 'block_afirst_assistant'));
echo html_writer::tag('p', get_string('sourcesintro', 'block_afirst_assistant'));
if (get_fast_modinfo($course)->get_instances_of('scorm')) {
    echo $OUTPUT->single_button(new moodle_url($url, ['action' => 'importscorm', 'sesskey' => sesskey()]),
        get_string('importscorm', 'block_afirst_assistant'), 'post');
    echo html_writer::tag('p', get_string('importscorm_help', 'block_afirst_assistant'), ['class' => 'small text-muted']);
}

$sources = $DB->get_records('block_afirst_source', ['courseid' => $course->id], 'status ASC, title ASC');
if (!$sources) {
    echo $OUTPUT->notification(get_string('nosources', 'block_afirst_assistant'), 'info', false);
} else {
    $table = new html_table();
    $table->head = [
        get_string('source', 'block_afirst_assistant'),
        get_string('status', 'block_afirst_assistant'),
        get_string('chunks', 'block_afirst_assistant'),
        '',
    ];
    foreach ($sources as $source) {
        $actions = [];
        if ($source->status !== source_manager::STATUS_APPROVED && (int)$source->chunkcount > 0) {
            $actions[] = $OUTPUT->single_button(new moodle_url($url,
                ['action' => 'approve', 'id' => $source->id, 'sesskey' => sesskey()]),
                get_string('approve', 'block_afirst_assistant'), 'post', ['type' => 'primary']);
        }
        if ($source->status === source_manager::STATUS_APPROVED) {
            $actions[] = $OUTPUT->single_button(new moodle_url($url,
                ['action' => 'retire', 'id' => $source->id, 'sesskey' => sesskey()]),
                get_string('retire', 'block_afirst_assistant'));
        }
        $file = source_manager::get_file($source);
        $title = $file ? html_writer::link(moodle_url::make_pluginfile_url($context->id, 'block_afirst_assistant',
            source_manager::FILEAREA, $source->id, '/', $file->get_filename()), format_string($source->title)) :
            format_string($source->title);
        $status = get_string($source->status, 'block_afirst_assistant');
        if ($source->status === source_manager::STATUS_APPROVED && $source->timeapproved) {
            $status .= html_writer::div(userdate($source->timeapproved, get_string('strftimedatetimeshort', 'langconfig')),
                'small text-muted');
        }
        // Starter questions shown in the assistant panel when learners are in this source's module.
        $editor = html_writer::start_tag('details', ['class' => 'mt-1 small']) .
            html_writer::tag('summary', get_string('starterquestions', 'block_afirst_assistant')) .
            html_writer::start_tag('form', ['method' => 'post', 'action' => $url->out(false)]) .
            html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()]) .
            html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'action', 'value' => 'suggestions']) .
            html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'id', 'value' => $source->id]) .
            html_writer::tag('textarea', s($source->suggestions ?? ''), ['name' => 'suggestions', 'rows' => 4,
                'class' => 'form-control form-control-sm my-1',
                'aria-label' => get_string('starterquestions', 'block_afirst_assistant')]) .
            html_writer::tag('button', get_string('savechanges'), ['type' => 'submit', 'class' => 'btn btn-secondary btn-sm']) .
            html_writer::end_tag('form') . html_writer::end_tag('details');
        $table->data[] = [$title . $editor, $status, $source->chunkcount, implode(' ', $actions)];
    }
    echo html_writer::table($table);
}

echo $OUTPUT->heading(get_string('addsource', 'block_afirst_assistant'), 3);
$form->display();
echo $OUTPUT->footer();
