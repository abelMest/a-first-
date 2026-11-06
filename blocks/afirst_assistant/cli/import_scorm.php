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
 * Import the learner-visible text of a course's SCORM packages as assistant sources.
 *
 * @package    block_afirst_assistant
 * @copyright  2026 Africa CDC
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use block_afirst_assistant\local\scorm_text_extractor;
use block_afirst_assistant\local\source_manager;
use block_afirst_assistant\local\text_processor;

define('CLI_SCRIPT', true);

require(__DIR__ . '/../../../config.php');
require_once($CFG->libdir . '/clilib.php');

[$options, $unrecognised] = cli_get_params([
    'courseid' => 0,
    'approve' => false,
    'userid' => 0,
    'dry-run' => false,
    'help' => false,
], ['h' => 'help', 'n' => 'dry-run']);

$help = <<<EOT
Import the text of every visible SCORM activity of a course as a course-assistant source.

Each SCORM package becomes one source, titled with the activity name and stored as "scorm-<cmid>.txt". The text is
what learners see in the package: for Articulate Rise 360 exports the lesson titles, text blocks, lists, tabs,
accordions, flashcards, scenarios and knowledge checks; for other packages the text of the HTML pages listed in
imsmanifest.xml. Media, scripts and tracking code are left out. Activities that already have a source are skipped, so
re-running is safe; to refresh a changed package, retire and delete its old source first.

Options:
  --courseid=ID     Target course (required).
  --approve         Approve each imported source immediately. Only do this when the content owner has signed the
                    packages off (Prop 3.9); otherwise approve them in the web page (Course assistant > Manage sources).
  --userid=ID       Recorded as uploader and approver. Defaults to the main admin.
  -n, --dry-run     Show the title and word count each activity would give, without importing.
  -h, --help        This help.

EOT;

if ($unrecognised) {
    cli_error(get_string('cliunknowoption', 'admin', implode(PHP_EOL . '  ', $unrecognised)));
}
if ($options['help'] || !$options['courseid']) {
    echo $help;
    exit($options['help'] ? 0 : 2);
}

$course = get_course((int)$options['courseid']);
$user = $options['userid'] ? core_user::get_user((int)$options['userid'], '*', MUST_EXIST) : get_admin();
\core\session\manager::set_user($user);

$cms = array_filter(get_fast_modinfo($course)->get_instances_of('scorm'), fn(cm_info $cm) => $cm->visible
    && !$cm->deletioninprogress);
if (!$cms) {
    cli_error('The course has no visible SCORM activities.');
}
// Course order, not instance id order.
$sequence = array_flip(array_keys(get_fast_modinfo($course)->get_cms()));
uasort($cms, fn(cm_info $a, cm_info $b) => $sequence[$a->id] <=> $sequence[$b->id]);

$imported = $skipped = $failed = 0;
foreach ($cms as $cm) {
    $title = $cm->get_formatted_name();
    $filename = "scorm-{$cm->id}.txt";
    if (!$options['dry-run']
            && $DB->record_exists('block_afirst_source', ['courseid' => $course->id, 'filename' => $filename])) {
        mtrace("skipped (already a source): $filename \"$title\"");
        $skipped++;
        continue;
    }
    try {
        $text = text_processor::terminate_lines(scorm_text_extractor::from_cm($cm));
    } catch (Throwable $e) {
        mtrace("failed: $filename \"$title\": " . $e->getMessage());
        $failed++;
        continue;
    }
    $words = count(preg_split('/\s+/u', $text, -1, PREG_SPLIT_NO_EMPTY));
    if ($options['dry-run']) {
        mtrace("would import: $filename as \"$title\" ($words words)");
        continue;
    }
    if ($words === 0) {
        mtrace("skipped (no text found): $filename \"$title\"");
        $skipped++;
        continue;
    }
    $source = source_manager::create_from_content($course->id, $title, $filename, $text);
    if (!$source) {
        mtrace("skipped (same text already a source): $filename \"$title\"");
        $skipped++;
        continue;
    }
    $status = 'pending';
    if ($options['approve']) {
        if ($source->chunkcount > 0) {
            source_manager::approve($source);
            $status = 'approved';
        } else {
            $status = 'pending: no passages, check the package';
        }
    }
    mtrace("imported: $filename as \"$title\" ($words words, {$source->chunkcount} passages, $status)");
    $imported++;
}
mtrace("Done: $imported imported, $skipped skipped, $failed failed.");
