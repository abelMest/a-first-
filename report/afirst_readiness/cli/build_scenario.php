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
 * Validate a readiness scenario file and build it into a course as a hidden Lesson.
 *
 * @package    report_afirst_readiness
 * @copyright  2026 Africa CDC
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use report_afirst_readiness\local\scenario;
use report_afirst_readiness\local\scenario_builder;

define('CLI_SCRIPT', true);

require(__DIR__ . '/../../../config.php');
require_once($CFG->libdir . '/clilib.php');

[$options, $unrecognised] = cli_get_params([
    'courseid' => 0,
    'section' => 0,
    'scenario' => '',
    'settings' => '',
    'course-config' => '',
    'taxonomy' => '',
    'validate-only' => false,
    'allow-draft' => false,
    'help' => false,
], ['h' => 'help']);

$help = <<<EOT
Validate a readiness scenario and build it into a course as a Lesson (docs/READINESS_SPEC.md).

Options:
  --scenario=PATH       Scenario JSON (required).
  --courseid=ID         Target course (required unless --validate-only).
  --section=N           Course section to add the Lesson to (default 0).
  --settings=PATH       readiness_settings.json: thresholds and supervisor_cmid.
  --course-config=PATH  course_config.json: its knowledge-check cmids become the knowledge evidence.
  --taxonomy=PATH       taxonomy.json: also check that every domain competency exists.
  --validate-only       Check the file and stop.
  --allow-draft         Build a scenario whose status is still "draft" (dev and beta only).
  -h, --help            This help.

The Lesson is created hidden. Review it, then make it visible to learners.

EOT;

if ($unrecognised) {
    cli_error(get_string('cliunknowoption', 'admin', implode(PHP_EOL . '  ', $unrecognised)));
}
if ($options['help'] || !$options['scenario'] || (!$options['courseid'] && !$options['validate-only'])) {
    echo $help;
    exit($options['help'] ? 0 : 2);
}

$read = function(string $path, string $what): array {
    $data = json_decode((string)@file_get_contents($path), true);
    if (!is_array($data)) {
        cli_error("Cannot read $what JSON from $path");
    }
    return $data;
};

$s = $read($options['scenario'], 'scenario');
$competencies = null;
if ($options['taxonomy']) {
    $tax = $read($options['taxonomy'], 'taxonomy');
    $competencies = [];
    foreach ($tax['modules'] ?? [] as $m) {
        foreach ($m['competencies'] ?? [] as $c) {
            $competencies[] = $c['id'];
        }
    }
}
if ($errors = scenario::validate($s, $competencies)) {
    cli_error("Scenario is invalid:\n  " . implode("\n  ", $errors));
}
mtrace(sprintf('Scenario %s v%s (%s): %d decision points, %d domains. Valid.', $s['id'], $s['version'], $s['status'],
    count($s['nodes']), count($s['domains'])));
if ($options['validate-only']) {
    exit(0);
}
if ($s['status'] !== 'approved' && !$options['allow-draft']) {
    cli_error("Scenario status is {$s['status']}. Have it approved by the content owner, or pass --allow-draft for dev or beta.");
}

$settings = $options['settings'] ? $read($options['settings'], 'settings') : [];
if ($options['course-config']) {
    $config = $read($options['course-config'], 'course config');
    if ((int)($config['courseid'] ?? 0) !== (int)$options['courseid']) {
        cli_error("course_config.json is for course {$config['courseid']}, not {$options['courseid']}.");
    }
    foreach ($config['modules'] ?? [] as $code => $m) {
        if (!empty($m['knowledgecheck_cmid'])) {
            $settings['knowledge'][(string)$code] = (int)$m['knowledgecheck_cmid'];
        }
    }
}

\core\cron::setup_user();
$record = scenario_builder::build((int)$options['courseid'], (int)$options['section'], $s, $settings);
mtrace("Built Lesson cm {$record->lessoncmid} (hidden) in course {$record->courseid}, section {$options['section']}.");
mtrace('Knowledge checks linked: ' . count($settings['knowledge'] ?? []) . '. Supervisor activity: ' .
    (!empty($settings['supervisor_cmid']) ? 'cm ' . $settings['supervisor_cmid'] : 'none') . '.');
mtrace("Report: {$CFG->wwwroot}/report/afirst_readiness/index.php?id={$record->courseid}");
