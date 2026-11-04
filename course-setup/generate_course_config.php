<?php
// Draft a course_config.json for any Moodle course, so its cmids don't have to be copied by hand.
//
//   sudo -u www-data php /opt/afirst/course-setup/generate_course_config.php --courseid=7 \
//       --out=/opt/afirst/courses/<course>/course_config.json
//
// Detection rules are in afirst_cs_draft_modules() (rules.php). The draft is a starting point: review every
// module, then run apply_course_config.php --dry-run before applying it.

define('CLI_SCRIPT', true);

// Locate config.php before any Moodle code is loaded.
$moodleroot = getcwd();
foreach ($argv as $arg) {
    if (str_starts_with($arg, '--moodle=')) {
        $moodleroot = substr($arg, 9);
    }
}
if (!is_file($moodleroot . '/config.php')) {
    $moodleroot = '/var/www/html';
}
if (!is_file($moodleroot . '/config.php')) {
    fwrite(STDERR, "Cannot find config.php. Run from the Moodle root or pass --moodle=/path/to/moodle\n");
    exit(1);
}
require($moodleroot . '/config.php');
require_once($CFG->libdir . '/clilib.php');
require_once(__DIR__ . '/rules.php');

[$options, $unrecognised] = cli_get_params(
    ['courseid' => 0, 'out' => '', 'fasttrack' => 90, 'remediation' => 80, 'force' => false, 'moodle' => null,
        'help' => false],
    ['h' => 'help']
);
if ($unrecognised) {
    cli_error(get_string('cliunknowoption', 'admin', implode("\n  ", $unrecognised)));
}
if ($options['help'] || !$options['courseid']) {
    echo "Draft course_config.json from a live course.

Options:
  --courseid=ID      Course to read (required).
  --out=PATH         Write the draft here (default: print it). Refuses to overwrite unless --force.
  --fasttrack=N      Diagnostic score that fast-tracks a module (default 90).
  --remediation=N    Knowledge-check score below which microlearning opens (default 80).
  --moodle=PATH      Moodle root, if not run from it.
  -h, --help         This help.
";
    exit($options['help'] ? 0 : 2);
}

$course = get_course((int)$options['courseid']);
$modinfo = get_fast_modinfo($course);
$sections = [];
foreach ($modinfo->get_section_info_all() as $section) {
    $cms = [];
    foreach ($modinfo->sections[$section->section] ?? [] as $cmid) {
        $cm = $modinfo->get_cm($cmid);
        if ($cm->deletioninprogress) {
            continue;
        }
        $cms[] = ['id' => (int)$cm->id, 'modname' => $cm->modname, 'name' => $cm->name, 'idnumber' => (string)$cm->idnumber];
    }
    $sections[] = ['number' => (int)$section->section, 'name' => get_section_name($course, $section), 'cms' => $cms];
}

[$modules, $warnings] = afirst_cs_draft_modules($sections);
$config = [
    '_comment' => sprintf('Draft generated %s from course %d (%s). Review every module before applying. ' .
        'null or [] means "not created yet" and the matching rule is skipped.', date('Y-m-d'), $course->id,
        $course->shortname),
    'courseid' => (int)$course->id,
    'thresholds' => [
        'fasttrack_min_percent' => (int)$options['fasttrack'],
        'remediation_below_percent' => (int)$options['remediation'],
    ],
    'hide_microlearning_until_unlocked' => true,
    // An object even when module codes look like list indexes.
    'modules' => (object)$modules,
];
$errors = afirst_cs_validate_config(json_decode(json_encode($config), true));
$json = json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n";

foreach ($warnings as $w) {
    fwrite(STDERR, "WARNING: $w\n");
}
foreach ($errors as $e) {
    fwrite(STDERR, "INVALID: $e\n");
}
fwrite(STDERR, count($modules) . " module(s) detected in course {$course->id}.\n");

if ($options['out']) {
    if (file_exists($options['out']) && !$options['force']) {
        cli_error("{$options['out']} exists; pass --force to overwrite it.");
    }
    file_put_contents($options['out'], $json);
    fwrite(STDERR, "Wrote {$options['out']}\n");
} else {
    echo $json;
}
exit($errors ? 1 : 0);
