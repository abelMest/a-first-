<?php
// A-FIRST course setup: builds the pilot study instruments listed in pilot_instruments.json (D-29).
//
// Run from the Moodle root (or pass --moodle):
//   php /path/to/build_pilot_instruments.php --courseid=4 --config=/path/to/courses/ds4h/pilot/pilot_instruments.json --dry-run
//   php /path/to/build_pilot_instruments.php --courseid=4 --config=/path/to/courses/ds4h/pilot/pilot_instruments.json
//
// Creates, in the configured section and in this order: the information sheet (Page), the consent
// (Choice), the starting questionnaire (Feedback), the baseline and endline tests (Quiz) and the mid and
// end questionnaires (Feedback). Everything is created HIDDEN: the advisory team and the ethics committee
// approve the instruments, then a teacher shows them (or run again with --show).
//
// Idempotent: activities are found by ID number and never rebuilt; a test whose questions differ from the
// configuration is reported, not changed. The tests and questionnaires require the consent answer first.
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
require_once($CFG->dirroot . '/course/modlib.php');
require_once($CFG->dirroot . '/mod/quiz/locallib.php');
require_once($CFG->libdir . '/resourcelib.php');
require_once($CFG->libdir . '/gradelib.php');

[$options, $unrecognised] = cli_get_params(
    ['courseid' => null, 'config' => null, 'dry-run' => false, 'show' => false, 'allow-host' => false, 'moodle' => null,
        'help' => false],
    ['n' => 'dry-run', 'h' => 'help']
);
if ($unrecognised) {
    cli_error(get_string('cliunknowoption', 'admin', implode("\n  ", $unrecognised)));
}
if ($options['help'] || empty($options['courseid']) || empty($options['config'])) {
    echo "Build the A-FIRST pilot study instruments (hidden).

Options:
  --courseid=ID   course to build in (required)
  --config=PATH   courses/<course>/pilot/pilot_instruments.json (required)
  -n, --dry-run   report what would change, change nothing
  --show          make every instrument visible (only after approval)
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

$config = json_decode((string)@file_get_contents($options['config']), true);
if (!is_array($config) || empty($config['assessment']['items']) || empty($config['surveys'])) {
    cli_error("{$options['config']} is not a pilot instruments file");
}
$configdir = dirname($options['config']);
$dryrun = (bool)$options['dry-run'];

\core\cron::setup_user();
$course = get_course((int)$options['courseid']);
$section = (int)($config['section'] ?? 0);

/** Course-modules keyed by ID number, fresh from the database. */
function afirst_pilot_cms(int $courseid): array {
    $out = [];
    get_fast_modinfo($courseid, 0, true); // Reset only; it returns nothing.
    foreach (get_fast_modinfo($courseid)->get_cms() as $cm) {
        if ($cm->idnumber !== '') {
            $out[$cm->idnumber] = $cm;
        }
    }
    return $out;
}

/** Bank entry ids of a quiz, in slot order (keyed by slot number). */
function afirst_pilot_entries(int $quizid): array {
    global $DB;
    $rows = $DB->get_records_sql(
        "SELECT s.slot, qr.questionbankentryid
           FROM {quiz_slots} s
           JOIN {question_references} qr ON qr.itemid = s.id AND qr.component = 'mod_quiz' AND qr.questionarea = 'slot'
          WHERE s.quizid = ?
       ORDER BY s.slot", [$quizid]);
    return array_map(fn($r) => (int)$r->questionbankentryid, $rows);
}

/** Latest ready question id of a bank entry. */
function afirst_pilot_latest_question(int $entryid): int {
    global $DB;
    $id = $DB->get_field_sql("SELECT questionid FROM {question_versions}
        WHERE questionbankentryid = ? AND status = 'ready' ORDER BY version DESC", [$entryid], IGNORE_MULTIPLE);
    if (!$id) {
        throw new moodle_exception('invalidrecordunknown', 'error', '', null, "no ready version for bank entry $entryid");
    }
    return (int)$id;
}

/** Moves a course-module to the end of the given section (order from the database, cache reset). */
function afirst_pilot_move_to_end(stdClass $course, int $cmid, int $sectionnum): void {
    global $DB;
    get_fast_modinfo($course->id, 0, true);
    $section = $DB->get_record('course_sections', ['course' => $course->id, 'section' => $sectionnum], '*', MUST_EXIST);
    moveto_module(get_coursemodule_from_id('', $cmid, $course->id, false, MUST_EXIST), $section);
    rebuild_course_cache($course->id, true);
}

/** Common fields for create_module(). */
function afirst_pilot_moduleinfo(stdClass $course, string $modname, int $section, string $name, string $intro): stdClass {
    global $DB;
    return (object)[
        'modulename' => $modname,
        'module' => $DB->get_field('modules', 'id', ['name' => $modname], MUST_EXIST),
        'course' => $course->id,
        'section' => $section,
        'visible' => 0,
        'visibleoncoursepage' => 1,
        'name' => $name,
        'introeditor' => ['text' => $intro, 'format' => FORMAT_HTML, 'itemid' => 0],
        'showdescription' => 0,
        'cmidnumber' => '',
        'groupmode' => 0,
        'groupingid' => 0,
        'completion' => COMPLETION_TRACKING_NONE,
    ];
}

/** Availability JSON: the consent activity must be completed (any answer). */
function afirst_pilot_after_consent(int $consentcmid): string {
    return json_encode(['op' => '&', 'c' => [['type' => 'completion', 'cm' => $consentcmid, 'e' => COMPLETION_COMPLETE]],
        'showc' => [true]]);
}

/** Feedback item presentation for the configured item. */
function afirst_pilot_feedback_item(int $feedbackid, string $key, array $item, int $position): stdClass {
    $base = ['feedback' => $feedbackid, 'template' => 0, 'label' => $key, 'position' => $position,
        'required' => empty($item['required']) ? 0 : 1, 'dependitem' => 0, 'dependvalue' => '', 'options' => ''];
    switch ($item['type']) {
        case 'label':
            return (object)($base + ['name' => '', 'typ' => 'label', 'presentation' => $item['text'], 'hasvalue' => 0,
                'required' => 0]);
        case 'scale': // Single choice laid out horizontally.
        case 'choice':
            $horizontal = $item['type'] === 'scale' ? '<<<<<1' : '';
            return (object)(['name' => $item['question'], 'typ' => 'multichoice', 'hasvalue' => 1,
                'presentation' => 'r>>>>>' . implode("\n|", $item['options']) . $horizontal] + $base);
        case 'text':
            return (object)(['name' => $item['question'], 'typ' => 'textarea', 'hasvalue' => 1,
                'presentation' => '60|4'] + $base);
    }
    throw new coding_exception("unknown survey item type {$item['type']}");
}

$cms = afirst_pilot_cms($course->id);
$exit = 0;
$created = [];
$label = fn(string $idnumber) => str_pad($idnumber, 30);

// 1. Information sheet (Page).
$info = $config['information_sheet'];
if (isset($cms[$info['idnumber']])) {
    echo $label($info['idnumber']) . "cm {$cms[$info['idnumber']]->id} unchanged\n";
} else {
    $html = (string)@file_get_contents($configdir . '/' . $info['content_file']);
    if ($html === '') {
        cli_error("Cannot read {$info['content_file']}");
    }
    echo $label($info['idnumber']) . "create Page \"{$info['name']}\"" . ($dryrun ? ' [dry run]' : '') . "\n";
    if (!$dryrun) {
        $mi = afirst_pilot_moduleinfo($course, 'page', $section, $info['name'], '');
        $mi->page = ['text' => $html, 'format' => FORMAT_HTML, 'itemid' => 0];
        $mi->display = RESOURCELIB_DISPLAY_OPEN;
        $mi->printintro = 0;
        $mi->printlastmodified = 1;
        $mi->cmidnumber = $info['idnumber'];
        $created[] = create_module($mi)->coursemodule;
    }
}

// 2. Consent (Choice): one answer, can be changed (withdrawal), results never shown to learners.
$consent = $config['consent'];
if (isset($cms[$consent['idnumber']])) {
    echo $label($consent['idnumber']) . "cm {$cms[$consent['idnumber']]->id} unchanged\n";
} else {
    echo $label($consent['idnumber']) . "create Choice \"{$consent['name']}\"" . ($dryrun ? ' [dry run]' : '') . "\n";
    if (!$dryrun) {
        $mi = afirst_pilot_moduleinfo($course, 'choice', $section, $consent['name'], $consent['intro']);
        $mi->showdescription = 1;
        $mi->option = $consent['options'];
        $mi->limit = array_fill(0, count($consent['options']), 0);
        $mi->limitanswers = 0;
        $mi->allowupdate = 1;
        $mi->allowmultiple = 0;
        $mi->display = 1; // Vertical.
        $mi->showresults = 0; // CHOICE_SHOWRESULTS_NOT.
        $mi->publish = 0;
        $mi->showunanswered = 0;
        $mi->includeinactive = 0;
        $mi->showpreview = 0;
        $mi->timeopen = 0;
        $mi->timeclose = 0;
        $mi->completion = COMPLETION_TRACKING_AUTOMATIC;
        $mi->completionsubmit = 1;
        $mi->cmidnumber = $consent['idnumber'];
        $created[] = create_module($mi)->coursemodule;
    }
}
$cms = $dryrun ? $cms : afirst_pilot_cms($course->id);
$consentcmid = isset($cms[$consent['idnumber']]) ? (int)$cms[$consent['idnumber']]->id : 0;

// Resolve the test questions: named Knowledge Check + slot -> bank entry.
$quizbyname = [];
foreach (get_fast_modinfo($course)->get_instances_of('quiz') as $cm) {
    $quizbyname[$cm->name][] = $cm;
}
$entries = [];
foreach ($config['assessment']['items'] as $item) {
    $matches = $quizbyname[$item['source_quiz']] ?? [];
    if (count($matches) !== 1) {
        cli_error("Expected exactly one quiz named \"{$item['source_quiz']}\", found " . count($matches));
    }
    $slots = afirst_pilot_entries((int)$matches[0]->instance);
    foreach ($item['slots'] as $slot) {
        if (!isset($slots[$slot])) {
            cli_error("\"{$item['source_quiz']}\" has no slot $slot");
        }
        $entries[] = $slots[$slot];
    }
}

// 3. Surveys and tests, in the order learners meet them.
$order = [$config['surveys'][0], ...array_map(fn($q) => $q + ['quiz' => true], $config['assessment']['quizzes']),
    ...array_slice($config['surveys'], 1)];
$template = null;
foreach ($order as $spec) {
    $idnumber = $spec['idnumber'];
    if (!empty($spec['quiz'])) {
        if (isset($cms[$idnumber])) {
            $have = array_values(afirst_pilot_entries((int)$cms[$idnumber]->instance));
            if ($have === $entries) {
                echo $label($idnumber) . "cm {$cms[$idnumber]->id} unchanged\n";
            } else {
                echo $label($idnumber) . "cm {$cms[$idnumber]->id} exists but its questions differ from the configuration. Not changed.\n";
                $exit = 2;
            }
            continue;
        }
        echo $label($idnumber) . "create Quiz \"{$spec['name']}\" with " . count($entries) . " questions"
            . ($dryrun ? ' [dry run]' : '') . "\n";
        if ($dryrun) {
            continue;
        }
        if ($template === null) {
            $templates = $quizbyname['Placement Test'] ?? [];
            if (count($templates) !== 1) {
                cli_error('Need exactly one quiz named "Placement Test" to copy settings from.');
            }
            $template = $templates[0];
        }
        ob_start(); // duplicate_module() logs its backup and restore steps.
        $newcm = duplicate_module($course, get_fast_modinfo($course)->get_cm($template->id));
        ob_end_clean();
        $quizid = (int)$newcm->instance;
        while (($count = \mod_quiz\quiz_settings::create($quizid)->get_structure()->get_question_count()) > 0) {
            \mod_quiz\quiz_settings::create($quizid)->get_structure()->remove_slot($count);
        }
        // One attempt, questions shuffled, no right answers shown afterwards (the endline reuses them).
        $DB->update_record('quiz', (object)['id' => $quizid, 'name' => $spec['name'],
            'intro' => $config['assessment']['intro'], 'introformat' => FORMAT_HTML,
            'attempts' => 1, 'shuffleanswers' => 1, 'timeopen' => 0, 'timeclose' => 0, 'timelimit' => 0,
            'reviewcorrectness' => 0, 'reviewmaxmarks' => 0, 'reviewmarks' => 0, 'reviewspecificfeedback' => 0,
            'reviewgeneralfeedback' => 0, 'reviewrightanswer' => 0, 'reviewoverallfeedback' => 0,
            'reviewattempt' => 0x10000, // Only "during the attempt".
            'completionpass' => 0, 'completionattemptsexhausted' => 0, 'completionminattempts' => 0, 'timemodified' => time()]);
        $DB->update_record('course_modules', (object)['id' => $newcm->id, 'idnumber' => $idnumber, 'visible' => 0,
            'visibleold' => 0, 'completion' => COMPLETION_TRACKING_AUTOMATIC, 'completiongradeitemnumber' => null,
            'completionpassgrade' => 0, 'completionview' => 0,
            'availability' => $consentcmid ? afirst_pilot_after_consent($consentcmid) : null]);
        $DB->set_field('quiz', 'completionminattempts', 1, ['id' => $quizid]);
        $DB->set_field('grade_items', 'itemname', $spec['name'],
            ['itemtype' => 'mod', 'itemmodule' => 'quiz', 'iteminstance' => $quizid, 'courseid' => $course->id]);
        // Study measure, not course assessment: weight 0 in the course total (natural aggregation).
        $gi = grade_item::fetch(['itemtype' => 'mod', 'itemmodule' => 'quiz', 'iteminstance' => $quizid,
            'courseid' => $course->id]);
        $gi->aggregationcoef = 0;
        $gi->aggregationcoef2 = 0;
        $gi->weightoverride = 1;
        $gi->update('afirst_pilot');
        $quiz = $DB->get_record('quiz', ['id' => $quizid], '*', MUST_EXIST);
        $quiz->cmid = $newcm->id;
        foreach ($entries as $entryid) {
            quiz_add_quiz_question(afirst_pilot_latest_question($entryid), $quiz, 0, 1.0);
        }
        $calculator = \mod_quiz\quiz_settings::create($quizid)->get_grade_calculator();
        $calculator->recompute_quiz_sumgrades();
        $calculator->update_quiz_maximum_grade(100); // Scores read as percentages.
        $created[] = (int)$newcm->id;
        continue;
    }

    // Feedback questionnaire: not anonymous (answers are linked to the study code at export), one submission.
    if (isset($cms[$idnumber])) {
        echo $label($idnumber) . "cm {$cms[$idnumber]->id} unchanged\n";
        continue;
    }
    echo $label($idnumber) . "create Feedback \"{$spec['name']}\" with " . count($spec['items']) . " items"
        . ($dryrun ? ' [dry run]' : '') . "\n";
    if ($dryrun) {
        continue;
    }
    $mi = afirst_pilot_moduleinfo($course, 'feedback', $section, $spec['name'], '');
    $mi->anonymous = 2; // FEEDBACK_ANONYMOUS_NO.
    $mi->email_notification = 0;
    $mi->multiple_submit = 0;
    $mi->autonumbering = 1;
    $mi->site_after_submit = '';
    $mi->page_after_submit_editor = ['text' => '<p>Thank you. Your answers have been saved.</p>', 'format' => FORMAT_HTML,
        'itemid' => 0];
    $mi->page_after_submit = $mi->page_after_submit_editor['text']; // feedback_add_instance() reads these directly.
    $mi->page_after_submitformat = FORMAT_HTML;
    $mi->publish_stats = 0;
    $mi->timeopen = 0;
    $mi->timeclose = 0;
    $mi->completion = COMPLETION_TRACKING_AUTOMATIC;
    $mi->completionsubmit = 1;
    $mi->cmidnumber = $idnumber;
    $newcm = create_module($mi);
    $position = 0;
    foreach ($spec['items'] as $key) {
        $DB->insert_record('feedback_item', afirst_pilot_feedback_item((int)$newcm->instance, $key,
            $config['survey_items'][$key], ++$position));
    }
    if ($consentcmid) {
        $DB->set_field('course_modules', 'availability', afirst_pilot_after_consent($consentcmid), ['id' => $newcm->coursemodule]);
    }
    $created[] = (int)$newcm->coursemodule;
}

if (!$dryrun) {
    // New activities go to the end of the section, in the order above.
    foreach ($created as $cmid) {
        afirst_pilot_move_to_end($course, $cmid, $section);
    }
    if ($options['show']) {
        foreach (afirst_pilot_cms($course->id) as $idnumber => $cm) {
            if (str_starts_with($idnumber, 'AFIRST-PILOT-') && !$cm->visible) {
                set_coursemodule_visible($cm->id, 1);
                echo $label($idnumber) . "shown\n";
            }
        }
    }
    rebuild_course_cache($course->id, true);
}
exit($exit);
