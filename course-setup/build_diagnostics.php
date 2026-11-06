<?php
// A-FIRST course setup: builds the module diagnostics listed in diagnostics_map.json (D-23).
//
// Run from the Moodle root (or pass --moodle):
//   php /path/to/build_diagnostics.php --courseid=4 --map=/path/to/diagnostics_map.json --dry-run
//   php /path/to/build_diagnostics.php --courseid=4 --map=/path/to/diagnostics_map.json
//
// Idempotent. Diagnostics are found by ID number (AFIRST-DIAG-M<n>):
//  - an entry with existing_quiz_name only gets the ID number set on that quiz (its questions are kept);
//  - a missing diagnostic is made by duplicating the course's Placement Test (so it inherits its
//    settings), emptying it and adding the listed bank questions. Completion is switched off so the
//    diagnostic stays optional;
//  - an existing diagnostic whose questions differ from the map is reported, not changed.
// Refuses to run on a site that is not a local development host unless --allow-host is given.

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
require_once($CFG->dirroot . '/course/lib.php');
require_once($CFG->dirroot . '/mod/quiz/locallib.php');

[$options, $unrecognised] = cli_get_params(
    ['courseid' => null, 'map' => null, 'dry-run' => false, 'allow-host' => false, 'moodle' => null, 'help' => false],
    ['n' => 'dry-run', 'h' => 'help']
);
if ($unrecognised) {
    cli_error(get_string('cliunknowoption', 'admin', implode("\n  ", $unrecognised)));
}
if ($options['help'] || empty($options['courseid']) || empty($options['map'])) {
    echo "Build the A-FIRST module diagnostics.

Options:
  --courseid=ID   course to build in (required)
  --map=PATH      courses/<course>/diagnostics_map.json (required)
  -n, --dry-run   report what would change, change nothing
  --allow-host    allow a site that is not localhost / *.localhost / *.test
  --moodle=PATH   Moodle root, if not run from it
";
    exit($options['help'] ? 0 : 1);
}

$host = parse_url($CFG->wwwroot, PHP_URL_HOST);
$devhost = in_array($host, ['localhost', '127.0.0.1', '::1'], true)
    || str_ends_with($host, '.localhost') || str_ends_with($host, '.test');
if (!$devhost && !$options['allow-host']) {
    cli_error("$CFG->wwwroot is not a local development site. Pass --allow-host only with the site owner's go-ahead.");
}

$map = json_decode((string)@file_get_contents($options['map']), true);
if (empty($map['diagnostics']) || !is_array($map['diagnostics'])) {
    cli_error("{$options['map']} has no diagnostics list");
}
$dryrun = (bool)$options['dry-run'];

\core\cron::setup_user();
$course = get_course((int)$options['courseid']);
$modinfo = get_fast_modinfo($course);

/** Quiz cms in the course keyed by cmid. */
$quizcms = $modinfo->get_instances_of('quiz');
$byidnumber = [];
$byname = [];
foreach ($quizcms as $cm) {
    if ($cm->idnumber !== '') {
        $byidnumber[$cm->idnumber] = $cm;
    }
    $byname[$cm->name][] = $cm;
}

/**
 * Bank entry ids currently in a quiz, in slot order.
 */
function afirst_diag_entries(int $quizid): array {
    global $DB;
    return array_map('intval', $DB->get_fieldset_sql(
        "SELECT qr.questionbankentryid
           FROM {quiz_slots} s
           JOIN {question_references} qr ON qr.itemid = s.id AND qr.component = 'mod_quiz' AND qr.questionarea = 'slot'
          WHERE s.quizid = ?
       ORDER BY s.slot", [$quizid]));
}

/**
 * Latest ready question id of a bank entry.
 */
function afirst_diag_latest_question(int $entryid): int {
    global $DB;
    $id = $DB->get_field_sql(
        "SELECT questionid FROM {question_versions}
          WHERE questionbankentryid = ? AND status = 'ready'
       ORDER BY version DESC", [$entryid], IGNORE_MULTIPLE);
    if (!$id) {
        throw new moodle_exception('invalidrecordunknown', 'error', '', null, "no ready version for bank entry $entryid");
    }
    return (int)$id;
}

/**
 * Moves a course-module to just after another one in the same section.
 */
function afirst_diag_place_after(stdClass $course, int $cmid, int $aftercmid): void {
    global $DB;
    $modinfo = get_fast_modinfo($course);
    $after = $modinfo->get_cm($aftercmid);
    $sequence = $modinfo->get_sections()[$after->sectionnum] ?? [];
    $next = $sequence[array_search($aftercmid, $sequence) + 1] ?? null;
    if ($next === $cmid) {
        return;
    }
    moveto_module(get_coursemodule_from_id('', $cmid, $course->id, false, MUST_EXIST),
        $DB->get_record('course_sections', ['id' => $after->section], '*', MUST_EXIST), $next);
    rebuild_course_cache($course->id, true);
}

$template = null;
$exit = 0;
$previous = null; // The diagnostic before this one, to keep them in module order.
foreach ($map['diagnostics'] as $diag) {
    $idnumber = $diag['idnumber'];
    $wanted = array_map('intval', $diag['question_bank_entry_ids']);
    $label = "Module {$diag['module']} ($idnumber)";

    // 1. Already built.
    if (isset($byidnumber[$idnumber])) {
        $cm = $byidnumber[$idnumber];
        $have = afirst_diag_entries($cm->instance);
        if (!$dryrun && $previous) {
            afirst_diag_place_after($course, $cm->id, $previous);
        }
        $previous = $cm->id;
        if ($have === $wanted) {
            echo "$label: cm {$cm->id} unchanged\n";
        } else {
            echo "$label: cm {$cm->id} exists but its questions differ from the map (have "
                . implode(',', $have) . "). Not changed; fix by hand or remove the ID number to rebuild.\n";
            $exit = 2;
        }
        continue;
    }

    // 2. Reuse an existing quiz (Module 1: the Placement Test).
    if (!empty($diag['existing_quiz_name'])) {
        $matches = $byname[$diag['existing_quiz_name']] ?? [];
        if (count($matches) !== 1) {
            echo "$label: expected exactly one quiz named \"{$diag['existing_quiz_name']}\", found " . count($matches) . "\n";
            $exit = 2;
            continue;
        }
        $cm = $matches[0];
        $have = afirst_diag_entries($cm->instance);
        if ($have !== $wanted) {
            echo "$label: \"{$cm->name}\" (cm {$cm->id}) has questions " . implode(',', $have)
                . ", not the mapped ones. Not changed.\n";
            $exit = 2;
            continue;
        }
        echo "$label: set ID number on existing \"{$cm->name}\" (cm {$cm->id})" . ($dryrun ? ' [dry run]' : '') . "\n";
        if (!$dryrun) {
            $DB->set_field('course_modules', 'idnumber', $idnumber, ['id' => $cm->id]);
        }
        $previous = $cm->id;
        continue;
    }

    // 3. Build a new one from the Placement Test template.
    if ($template === null) {
        $templates = $byname['Placement Test'] ?? [];
        if (count($templates) !== 1) {
            cli_error('Need exactly one quiz named "Placement Test" to copy settings from.');
        }
        $template = $templates[0];
    }
    echo "$label: create \"{$diag['name']}\" with " . count($wanted) . " questions" . ($dryrun ? ' [dry run]' : '') . "\n";
    if ($dryrun) {
        foreach ($wanted as $entryid) {
            afirst_diag_latest_question($entryid); // Fails early if an entry is missing.
        }
        continue;
    }

    ob_start(); // The backup/restore behind duplicate_module() logs every step to the CLI.
    $newcm = duplicate_module($course, get_fast_modinfo($course)->get_cm($template->id));
    ob_end_clean();
    $quizid = (int)$newcm->instance;

    // Empty the copy, last slot first. Reload the structure each time: it caches its sections.
    while (($count = \mod_quiz\quiz_settings::create($quizid)->get_structure()->get_question_count()) > 0) {
        \mod_quiz\quiz_settings::create($quizid)->get_structure()->remove_slot($count);
    }
    // Name, ID number, no completion (optional activity).
    $DB->update_record('quiz', (object)['id' => $quizid, 'name' => $diag['name'],
        'completionpass' => 0, 'completionattemptsexhausted' => 0, 'completionminattempts' => 0,
        'timemodified' => time()]);
    $DB->update_record('course_modules', (object)['id' => $newcm->id, 'idnumber' => $idnumber,
        'completion' => COMPLETION_TRACKING_NONE, 'completiongradeitemnumber' => null,
        'completionpassgrade' => 0, 'completionview' => 0]);
    $DB->set_field('grade_items', 'itemname', $diag['name'],
        ['itemtype' => 'mod', 'itemmodule' => 'quiz', 'iteminstance' => $quizid, 'courseid' => $course->id]);

    $quiz = $DB->get_record('quiz', ['id' => $quizid], '*', MUST_EXIST);
    $quiz->cmid = $newcm->id;
    foreach ($wanted as $entryid) {
        quiz_add_quiz_question(afirst_diag_latest_question($entryid), $quiz, 0, 1.0);
    }
    \mod_quiz\quiz_settings::create($quizid)->get_grade_calculator()->recompute_quiz_sumgrades();
    rebuild_course_cache($course->id, true);
    if ($previous) {
        afirst_diag_place_after($course, $newcm->id, $previous);
    }
    $previous = $newcm->id;
    echo "  -> cm {$newcm->id}, quiz $quizid, entries " . implode(',', afirst_diag_entries($quizid)) . "\n";
}

rebuild_course_cache($course->id, true);
exit($exit);
