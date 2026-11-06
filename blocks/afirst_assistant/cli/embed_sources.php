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
 * Compute meaning vectors (embeddings) for a course's sources, for hybrid retrieval.
 *
 * @package    block_afirst_assistant
 * @copyright  2026 Africa CDC
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use block_afirst_assistant\local\embedder;
use block_afirst_assistant\local\source_manager;

define('CLI_SCRIPT', true);

require(__DIR__ . '/../../../config.php');
require_once($CFG->libdir . '/clilib.php');

[$options, $unrecognised] = cli_get_params([
    'courseid' => 0,
    'all' => false,
    'help' => false,
], ['h' => 'help']);

$help = <<<EOT
Compute embeddings for the sources of a course, so the assistant can also search by meaning (hybrid retrieval).
Set the embedding endpoint first (Site administration > Plugins > Blocks > Course assistant), e.g. a local Ollama
server with: ollama pull nomic-embed-text

Options:
  --courseid=ID   Course whose sources are embedded (required).
  --all           Recompute every passage, not only those without a vector (after changing the embedding model).
  -h, --help      This help.

Example:
  sudo -u www-data php blocks/afirst_assistant/cli/embed_sources.php --courseid=2
EOT;

if ($unrecognised) {
    cli_error(get_string('cliunknowoption', 'admin', implode(PHP_EOL . '  ', $unrecognised)));
}
if ($options['help'] || !$options['courseid']) {
    echo $help;
    exit($options['help'] ? 0 : 2);
}
if (!embedder::enabled()) {
    cli_error('No embedding endpoint is configured (block_afirst_assistant | embedendpoint).');
}
$course = get_course((int)$options['courseid']);
$sources = $DB->get_records('block_afirst_source', ['courseid' => $course->id], 'title', 'id, title');
$total = 0;
foreach ($sources as $source) {
    $started = microtime(true);
    $done = source_manager::embed_source($source->id, !$options['all']);
    $total += $done;
    mtrace(sprintf('%-60s %4d passages embedded (%.1f s)', \core_text::substr($source->title, 0, 60), $done,
        microtime(true) - $started));
}
mtrace("Done: $total passages embedded.");
