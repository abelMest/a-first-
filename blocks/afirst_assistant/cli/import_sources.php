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
 * Bulk-import approved course texts as assistant sources, for onboarding a course without the web form.
 *
 * @package    block_afirst_assistant
 * @copyright  2026 Africa CDC
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use block_afirst_assistant\local\source_manager;

define('CLI_SCRIPT', true);

require(__DIR__ . '/../../../config.php');
require_once($CFG->libdir . '/clilib.php');

[$options, $unrecognised] = cli_get_params([
    'courseid' => 0,
    'dir' => '',
    'manifest' => '',
    'approve' => false,
    'userid' => 0,
    'dry-run' => false,
    'help' => false,
], ['h' => 'help', 'n' => 'dry-run']);

$help = <<<EOT
Import every .txt, .md, .html or .htm file in a folder as a course-assistant source.

Each file becomes one source. Its title (shown to learners in citations) comes from the manifest, or else from the
file name ("module_2_1_data_cleaning.txt" becomes "Module 2 1 Data Cleaning"). Start titles with "Module x.y" so the
audit harness can check which module a citation comes from. Files whose content is already a source of the course are
skipped, so re-running is safe.

Options:
  --courseid=ID     Target course (required).
  --dir=PATH        Folder with the approved texts (required).
  --manifest=PATH   Optional CSV with header "filename,title".
  --approve         Approve each imported source immediately. Only do this when the content owner has signed the
                    files off (Prop 3.9); otherwise approve them in the web page (Course assistant > Manage sources).
  --userid=ID       Recorded as uploader and approver. Defaults to the main admin.
  -n, --dry-run     List what would be imported.
  -h, --help        This help.

EOT;

if ($unrecognised) {
    cli_error(get_string('cliunknowoption', 'admin', implode(PHP_EOL . '  ', $unrecognised)));
}
if ($options['help'] || !$options['courseid'] || !$options['dir']) {
    echo $help;
    exit($options['help'] ? 0 : 2);
}
if (!is_dir($options['dir'])) {
    cli_error("Not a folder: {$options['dir']}");
}

$course = get_course((int)$options['courseid']);
$user = $options['userid'] ? core_user::get_user((int)$options['userid'], '*', MUST_EXIST) : get_admin();
\core\session\manager::set_user($user);

$titles = [];
if ($options['manifest']) {
    $handle = fopen($options['manifest'], 'r') ?: cli_error("Cannot read {$options['manifest']}");
    $header = array_map(fn($h) => trim(preg_replace('/^\xEF\xBB\xBF/', '', $h)), fgetcsv($handle, null, ',', '"', ''));
    while (($row = fgetcsv($handle, null, ',', '"', '')) !== false) {
        $r = array_combine($header, array_pad($row, count($header), ''));
        if (!empty($r['filename']) && !empty($r['title'])) {
            $titles[$r['filename']] = $r['title'];
        }
    }
    fclose($handle);
}

$files = [];
foreach (new DirectoryIterator($options['dir']) as $entry) {
    if ($entry->isFile() && in_array('.' . strtolower($entry->getExtension()), source_manager::ACCEPTED_TYPES, true)) {
        $files[] = $entry->getFilename();
    }
}
sort($files, SORT_NATURAL);
if (!$files) {
    cli_error('No .txt, .md, .html or .htm files found.');
}

$imported = $skipped = 0;
foreach ($files as $filename) {
    $title = $titles[$filename] ?? ucwords(str_replace(['_', '-'], ' ', pathinfo($filename, PATHINFO_FILENAME)));
    if ($options['dry-run']) {
        mtrace("would import: $filename as \"$title\"");
        continue;
    }
    $source = source_manager::create_from_content($course->id, $title, $filename,
        file_get_contents($options['dir'] . DIRECTORY_SEPARATOR . $filename));
    if (!$source) {
        mtrace("skipped (already a source): $filename");
        $skipped++;
        continue;
    }
    $status = 'pending';
    if ($options['approve']) {
        if ($source->chunkcount > 0) {
            source_manager::approve($source);
            $status = 'approved';
        } else {
            $status = 'pending: no passages, check the file';
        }
    }
    mtrace("imported: $filename as \"$title\" ({$source->chunkcount} passages, $status)");
    $imported++;
}
mtrace("Done: $imported imported, $skipped skipped.");
