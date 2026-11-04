<?php
// A-FIRST course setup: applies the fast-track and remediation restrict-access
// rules described in course_config.json to a Moodle 5.0 course.
//
// Run from the Moodle root (or pass --moodle):
//   php /path/to/apply_course_config.php --config=/path/to/course_config.json --dry-run
//   php /path/to/apply_course_config.php --config=/path/to/course_config.json
//
// Idempotent: an activity whose availability already equals the plan is left alone;
// one this script configured before is updated in place; one with hand-made
// restrictions is reported as a conflict and kept unless --replace is given.

define('CLI_SCRIPT', true);

// Locate config.php before any Moodle code is loaded.
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
require_once($CFG->libdir . '/gradelib.php');
require_once($CFG->libdir . '/completionlib.php');
require_once($CFG->dirroot . '/course/lib.php');
require_once(__DIR__ . '/rules.php');

[$options, $unrecognised] = cli_get_params(
    ['config' => null, 'dry-run' => false, 'replace' => false,
        'moodle' => null, 'help' => false],
    ['n' => 'dry-run', 'h' => 'help']
);
if ($unrecognised) {
    cli_error(get_string('cliunknowoption', 'admin', implode("\n  ", $unrecognised)));
}
if ($options['help']) {
    echo "Apply A-FIRST fast-track and remediation restrict-access rules.

Options:
  --config=PATH   courses/<course>/course_config.json (required)
  -n, --dry-run   print the planned availability JSON per activity, change nothing
  --replace       overwrite restrictions that were not written by this script
  --moodle=PATH   Moodle root, if not run from it
  -h, --help      this help
";
    exit(0);
}

if (empty($options['config'])) {
    cli_error('--config=PATH is required, for example --config=/opt/afirst/courses/ds4h/course_config.json');
}
$dryrun = (bool)$options['dry-run'];
$config = json_decode((string)@file_get_contents($options['config']), true);
if ($errors = afirst_cs_validate_config($config)) {
    cli_error("Invalid config {$options['config']}:\n  " . implode("\n  ", $errors));
}

\core\cron::setup_user();
$course = get_course($config['courseid']);
$modinfo = get_fast_modinfo($course);

// Site and course switches the rules depend on.
$problems = [];
if (empty($CFG->enableavailability)) {
    $problems[] = 'Restrict access is disabled (Site administration > Advanced features > Enable restricted access).';
}
if (empty($CFG->enablecompletion)) {
    $problems[] = 'Completion tracking is disabled site-wide (Advanced features > Enable completion tracking).';
}
if (empty($course->enablecompletion)) {
    $problems[] = "Completion tracking is off in course {$course->id} settings.";
}
$enabledconditions = \core\plugininfo\availability::get_enabled_plugins() ?: [];
foreach (['grade', 'completion'] as $plugin) {
    if (!array_key_exists($plugin, $enabledconditions)) {
        $problems[] = "Availability condition plugin availability_$plugin is not enabled.";
    }
}
if ($problems) {
    if (!$dryrun) {
        cli_error("Cannot apply:\n  " . implode("\n  ", $problems));
    }
    cli_writeln("WARNING (dry run continues):\n  " . implode("\n  ", $problems));
}

/**
 * Returns the cm_info for $cmid or stops with an error naming the config key.
 */
$getcm = function(int $cmid, string $what) use ($modinfo, $course): cm_info {
    try {
        return $modinfo->get_cm($cmid);
    } catch (moodle_exception $e) {
        cli_error("$what: course module $cmid does not exist in course {$course->id}");
    }
};

// Check every referenced activity and resolve grade item ids.
$gradeitems = [];
$expect = [
    'diagnostic_cmid' => ['quiz'], 'knowledgecheck_cmid' => ['quiz'], 'retake_cmid' => ['quiz'],
    'microlearning_cmids' => ['h5pactivity'], 'content_cmids' => null,
];
foreach ($config['modules'] as $code => $m) {
    foreach ($expect as $key => $modnames) {
        $ids = (array)($m[$key] ?? []);
        foreach ($ids as $cmid) {
            $cm = $getcm($cmid, "$code.$key");
            if ($modnames && !in_array($cm->modname, $modnames)) {
                cli_writeln("NOTICE $code.$key: cm $cmid is a {$cm->modname}, expected " . implode('/', $modnames));
            }
            $needscompletion = in_array($key, ['content_cmids', 'microlearning_cmids']);
            if ($needscompletion && $cm->completion == COMPLETION_TRACKING_NONE) {
                $problems[] = "$code.$key: cm $cmid ({$cm->name}) has no completion tracking; "
                    . 'a completion condition on it can never be met.';
                cli_writeln("WARNING {$problems[array_key_last($problems)]}");
            }
            $item = grade_item::fetch(['courseid' => $course->id, 'itemtype' => 'mod',
                'itemmodule' => $cm->modname, 'iteminstance' => $cm->instance, 'itemnumber' => 0]);
            $gradeitems[$cmid] = $item ? (int)$item->id : null;
        }
    }
}
if ($problems && !$dryrun) {
    cli_error('Fix the warnings above, then run again.');
}

[$plan, $notices] = afirst_cs_build_plan($config, fn(int $cmid) => $gradeitems[$cmid] ?? null);
foreach ($notices as $notice) {
    cli_writeln("NOTICE $notice");
}

// Per module, the activity and grade item ids a managed tree may reference.
$modulecmids = [];
foreach ($config['modules'] as $code => $m) {
    $modulecmids[(string)$code] = array_values(array_filter(array_merge(
        [$m['diagnostic_cmid'] ?? null, $m['knowledgecheck_cmid'] ?? null, $m['retake_cmid'] ?? null],
        $m['content_cmids'] ?? [], $m['microlearning_cmids'] ?? [])));
}

$counts = [];
$changed = [];
foreach ($plan as $cmid => $entry) {
    $cm = $getcm($cmid, "{$entry['module']}.{$entry['role']}");
    $json = afirst_cs_encode($entry['tree']);

    // Let core validate the structure and every condition before anything is written.
    try {
        new \core_availability\tree(json_decode($json));
    } catch (coding_exception $e) {
        cli_error("cm $cmid: planned availability rejected by core_availability\\tree: " . $e->getMessage());
    }

    $ownids = $modulecmids[$entry['module']];
    $owngrades = array_values(array_filter(array_map(fn($id) => $gradeitems[$id] ?? null, $ownids)));
    $current = $cm->availability;
    $managed = $current !== null && $current !== ''
        && afirst_cs_is_managed(json_decode($current, true), $owngrades, $ownids);
    $action = afirst_cs_decide($current, $entry['tree'], $managed, (bool)$options['replace']);
    $counts[$action] = ($counts[$action] ?? 0) + 1;

    cli_writeln(sprintf('[%s] module %s %s: cm %d "%s" (%s)', strtoupper($action), $entry['module'],
        $entry['role'], $cmid, $cm->name, $cm->modname));
    if ($action !== 'unchanged') {
        if ($current) {
            cli_writeln("    current: $current");
        }
        cli_writeln("    planned: $json");
    }
    if ($action === 'conflict') {
        cli_writeln('    kept: existing restrictions were not written by this script (use --replace to overwrite)');
    }

    if (!$dryrun && in_array($action, ['create', 'update', 'replace'])) {
        // Same write core uses in \core_availability\info::update_dependency_id_across_course().
        $DB->set_field('course_modules', 'availability', $json, ['id' => $cmid]);
        $changed[] = $cm;
    }
}

if ($changed) {
    rebuild_course_cache($course->id, true);
    foreach ($changed as $cm) {
        \core\event\course_module_updated::create_from_cm($cm)->trigger();
    }
}

ksort($counts);
$summary = [];
foreach ($counts as $action => $n) {
    $summary[] = "$action=$n";
}
cli_writeln(($dryrun ? 'DRY RUN, nothing changed. ' : '') . 'Activities: ' . (implode(', ', $summary) ?: 'none planned')
    . ($dryrun ? '' : '; written: ' . count($changed)));
exit(0);
