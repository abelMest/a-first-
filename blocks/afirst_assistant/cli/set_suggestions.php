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
 * Load the starter questions shown in the assistant panel, per module, from a JSON file.
 *
 * @package    block_afirst_assistant
 * @copyright  2026 Africa CDC
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use block_afirst_assistant\local\retriever;
use block_afirst_assistant\local\source_manager;

define('CLI_SCRIPT', true);

require(__DIR__ . '/../../../config.php');
require_once($CFG->libdir . '/clilib.php');

[$options, $unrecognised] = cli_get_params([
    'courseid' => 0,
    'file' => '',
    'help' => false,
], ['h' => 'help']);

$help = <<<EOT
Set starter questions on a course's sources from a JSON file keyed by module code, for example
courses/ds4h/assistant_suggestions.json: {"1.1": ["What is data literacy?", ...], "1.2": [...]}.
Each source whose title contains that module code (e.g. "Module 1.1 ...") receives the questions.

Options:
  --courseid=ID   Course (required).
  --file=PATH     JSON file (required).
  -h, --help      This help.
EOT;

if ($unrecognised) {
    cli_error(get_string('cliunknowoption', 'admin', implode(PHP_EOL . '  ', $unrecognised)));
}
if ($options['help'] || !$options['courseid'] || !$options['file']) {
    echo $help;
    exit($options['help'] ? 0 : 2);
}
$bymodule = json_decode((string)file_get_contents($options['file']), true);
if (!is_array($bymodule)) {
    cli_error('Not a JSON object: ' . $options['file']);
}
$course = get_course((int)$options['courseid']);
$count = 0;
foreach ($DB->get_records('block_afirst_source', ['courseid' => $course->id]) as $source) {
    $code = retriever::module_of($source->title);
    if ($code !== null && isset($bymodule[$code]) && is_array($bymodule[$code])) {
        source_manager::set_suggestions($source, $bymodule[$code]);
        mtrace("Module $code: " . count($bymodule[$code]) . " starter questions on \"{$source->title}\"");
        $count++;
    }
}
mtrace("Done: $count sources updated.");
