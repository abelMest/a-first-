<?php
// A-FIRST course setup: imports the competency framework and links it to the course (core competencies).
//
// Run from the Moodle root (or pass --moodle):
//   php import_competencies.php --csv=/path/to/competency_framework_A-FIRST-DS4H-V1.csv \
//       --config=/path/to/course_config.json [--dry-run]
//
// 1. Imports the framework CSV with core tool_lpimportcsv (skipped when a framework with the same ID
//    number exists).
// 2. Links each module competency MOD-<code> to the course, and to that module's Knowledge Check and
//    retake from course_config.json, with the rule "activity completed -> attach evidence". Completing
//    a Knowledge Check (it needs a pass grade) then records evidence for the learner's competency.
// Idempotent. Refuses a non-development site unless --allow-host is given.

define('CLI_SCRIPT', true);

$moodleroot = null;
foreach ($argv as $arg) {
    if (strpos($arg, '--moodle=') === 0) {
        $moodleroot = substr($arg, strlen('--moodle='));
    }
}
$moodleroot = $moodleroot ?? getcwd();
if (!is_file($moodleroot . '/config.php')) {
    fwrite(STDERR, "Cannot find config.php in $moodleroot. Run from the Moodle root or pass --moodle=/path/to/moodle\n");
    exit(1);
}
require($moodleroot . '/config.php');
require_once($CFG->libdir . '/clilib.php');
require_once(__DIR__ . '/rules.php');

use core_competency\api;
use core_competency\competency;
use core_competency\competency_framework;
use core_competency\course_module_competency;

[$options, $unrecognised] = cli_get_params(
    ['csv' => null, 'config' => null, 'dry-run' => false, 'allow-host' => false, 'moodle' => null, 'help' => false],
    ['n' => 'dry-run', 'h' => 'help']
);
if ($unrecognised) {
    cli_error(get_string('cliunknowoption', 'admin', implode("\n  ", $unrecognised)));
}
if ($options['help'] || empty($options['csv']) || empty($options['config'])) {
    echo "Import the A-FIRST competency framework and link it to the course.

Options:
  --csv=PATH      courses/<course>/competency_framework_*.csv (required)
  --config=PATH   courses/<course>/course_config.json (required)
  -n, --dry-run   report what would change, change nothing
  --allow-host    allow a site that is not localhost / *.localhost / *.test
";
    exit($options['help'] ? 0 : 1);
}

$host = parse_url($CFG->wwwroot, PHP_URL_HOST);
$devhost = in_array($host, ['localhost', '127.0.0.1', '::1'], true)
    || str_ends_with($host, '.localhost') || str_ends_with($host, '.test');
if (!$devhost && !$options['allow-host']) {
    cli_error("$CFG->wwwroot is not a local development site. Pass --allow-host only with the site owner's go-ahead.");
}
if (!get_config('core_competency', 'enabled')) {
    cli_error('Competencies are disabled (Site administration > Advanced features).');
}
$config = json_decode((string)@file_get_contents($options['config']), true);
if ($errors = afirst_cs_validate_config($config)) {
    cli_error("Invalid config {$options['config']}:\n  " . implode("\n  ", $errors));
}
$dryrun = (bool)$options['dry-run'];
\core\cron::setup_user();

// 1. Framework.
$text = (string)file_get_contents($options['csv']);
$rows = array_map('str_getcsv', preg_split('/\R/', trim($text)));
$frameworkidnumber = null;
foreach ($rows as $row) {
    if (($row[12] ?? '') === '1') { // "Is framework" column.
        $frameworkidnumber = $row[1];
    }
}
if (!$frameworkidnumber) {
    cli_error('The CSV has no framework row (column "Is framework" = 1).');
}
$framework = competency_framework::get_record(['idnumber' => $frameworkidnumber]);
if ($framework) {
    echo "Framework $frameworkidnumber exists (id {$framework->get('id')}), not re-imported\n";
} else if ($dryrun) {
    echo "Framework $frameworkidnumber: would import " . (count($rows) - 2) . " competencies [dry run]\n";
} else {
    $importer = new \tool_lpimportcsv\framework_importer($text, 'UTF-8', 'comma', 0, null, false);
    if ($error = $importer->get_error()) {
        cli_error("Import failed: $error");
    }
    $framework = $importer->import();
    echo "Framework $frameworkidnumber imported (id {$framework->get('id')}, "
        . competency::count_records(['competencyframeworkid' => $framework->get('id')]) . " competencies)\n";
}
if (!$framework) {
    exit(0);
}

// 2. Course and activity links.
$courseid = $config['courseid'];
$linked = ['course' => 0, 'activity' => 0, 'unchanged' => 0];
foreach ($config['modules'] as $code => $m) {
    $comp = competency::get_record(['competencyframeworkid' => $framework->get('id'), 'idnumber' => "MOD-$code"]);
    if (!$comp) {
        echo "MOD-$code: not in the framework, skipped\n";
        continue;
    }
    $compid = $comp->get('id');
    if ($dryrun) {
        echo "MOD-$code: would link to course $courseid and cms "
            . implode(',', array_filter([$m['knowledgecheck_cmid'], $m['retake_cmid'] ?? null])) . " [dry run]\n";
        continue;
    }
    if (api::add_competency_to_course($courseid, $compid)) {
        $linked['course']++;
    } else {
        $linked['unchanged']++;
    }
    foreach (array_filter([$m['knowledgecheck_cmid'], $m['retake_cmid'] ?? null]) as $cmid) {
        if (api::add_competency_to_course_module($cmid, $compid)) {
            $linked['activity']++;
        } else {
            $linked['unchanged']++;
        }
        $cmc = course_module_competency::get_record(['cmid' => $cmid, 'competencyid' => $compid]);
        if ((int)$cmc->get('ruleoutcome') !== course_module_competency::OUTCOME_EVIDENCE) {
            api::set_course_module_competency_ruleoutcome($cmc, course_module_competency::OUTCOME_EVIDENCE);
        }
    }
}
if (!$dryrun) {
    echo "Linked: {$linked['course']} to the course, {$linked['activity']} to activities; {$linked['unchanged']} already linked\n";
}
