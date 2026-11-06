<?php
// A-FIRST course setup: builds the remediation route's review units and retakes (D-24).
//
// For each sub-module code (e.g. 1.1), found from the course's SCORM activity whose name contains it:
//  1. extracts a review unit from the SCORM's own text (microlearning.php: verbatim key points and
//     key terms, nothing generated) and writes <outdir>/afirst-review-<code>.h5p plus a section of
//     <outdir>/REVIEW.md for the content owner;
//  2. creates an H5P activity "Review: Module <code>" (ID number AFIRST-ML-<code>), hidden, with
//     completion on view and attempt tracking, right after the module's Knowledge Check;
//  3. creates "Module <code> Knowledge Check (retake)" (ID number AFIRST-RETAKE-<code>) by duplicating
//     the Knowledge Check, hidden, right after the review unit.
// Units stay hidden until the content owner approves them; then run again with --show.
//
// Run from the Moodle root (or pass --moodle):
//   php build_microlearning.php --courseid=4 --outdir=/path/to/courses/ds4h/microlearning [--codes=1.1,1.2] [--dry-run]
//   php build_microlearning.php --courseid=4 --show [--codes=...]      (after owner sign-off)
//   php build_microlearning.php --courseid=4 --hide [--codes=...]
//
// Idempotent: existing activities (found by ID number) are kept; --update-package replaces a review
// unit's package with the newly built one. Refuses a non-development site unless --allow-host is given.

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
require_once($CFG->libdir . '/filelib.php');
require_once($CFG->dirroot . '/course/lib.php');
require_once($CFG->dirroot . '/course/modlib.php');
require_once(__DIR__ . '/microlearning.php');

[$options, $unrecognised] = cli_get_params(
    ['courseid' => null, 'outdir' => null, 'codes' => '', 'dry-run' => false, 'show' => false, 'hide' => false,
        'update-package' => false, 'allow-host' => false, 'moodle' => null, 'help' => false],
    ['n' => 'dry-run', 'h' => 'help']
);
if ($unrecognised) {
    cli_error(get_string('cliunknowoption', 'admin', implode("\n  ", $unrecognised)));
}
$toggle = $options['show'] ? 1 : ($options['hide'] ? 0 : null);
if ($options['help'] || empty($options['courseid']) || ($toggle === null && empty($options['outdir']))) {
    echo "Build A-FIRST review units and retakes.

Options:
  --courseid=ID       course (required)
  --outdir=PATH       where to write the .h5p packages and REVIEW.md (required unless --show/--hide)
  --codes=1.1,1.2     limit to these sub-modules (default: every SCORM whose name has a code)
  -n, --dry-run       build the packages and REVIEW.md, change nothing in Moodle
  --update-package    replace the package of review units that already exist
  --show / --hide     make the units and retakes visible / hidden (owner sign-off), nothing else
  --allow-host        allow a site that is not localhost / *.localhost / *.test
";
    exit($options['help'] ? 0 : 1);
}

$host = parse_url($CFG->wwwroot, PHP_URL_HOST);
$devhost = in_array($host, ['localhost', '127.0.0.1', '::1'], true)
    || str_ends_with($host, '.localhost') || str_ends_with($host, '.test');
if (!$devhost && !$options['allow-host']) {
    cli_error("$CFG->wwwroot is not a local development site. Pass --allow-host only with the site owner's go-ahead.");
}

\core\cron::setup_user();
$course = get_course((int)$options['courseid']);
$dryrun = (bool)$options['dry-run'];
$only = array_filter(array_map('trim', explode(',', (string)$options['codes'])));

/** Course-module of the course by ID number, or null. */
function afirst_ml_cm_by_idnumber(stdClass $course, string $idnumber): ?cm_info {
    foreach (get_fast_modinfo($course)->get_cms() as $cm) {
        if ($cm->idnumber === $idnumber && !$cm->deletioninprogress) {
            return $cm;
        }
    }
    return null;
}

/** Moves a course-module to just after another one in the same section. */
function afirst_ml_place_after(stdClass $course, int $cmid, int $aftercmid): void {
    global $DB;
    $modinfo = get_fast_modinfo($course);
    $after = $modinfo->get_cm($aftercmid);
    $sequence = $modinfo->get_sections()[$after->sectionnum] ?? [];
    $pos = array_search($aftercmid, $sequence);
    $next = $sequence[$pos + 1] ?? null;
    if ($next === $cmid) {
        return;
    }
    $section = $DB->get_record('course_sections', ['id' => $after->section], '*', MUST_EXIST);
    $mod = get_coursemodule_from_id('', $cmid, $course->id, false, MUST_EXIST); // Includes modname.
    moveto_module($mod, $section, $next); // course_add_cm_to_section() takes the cmid to insert before.
    rebuild_course_cache($course->id, true);
}

// Sub-modules: SCORM activities whose name carries a code like 1.1, and their Knowledge Check.
$units = [];
$modinfo = get_fast_modinfo($course);
foreach ($modinfo->get_instances_of('scorm') as $scormcm) {
    if (!preg_match('/\b(\d{1,2}\.\d{1,2})\b/', $scormcm->name, $m) || ($only && !in_array($m[1], $only, true))) {
        continue;
    }
    $code = $m[1];
    $kc = null;
    foreach ($modinfo->get_instances_of('quiz') as $quizcm) {
        if (strcasecmp(trim($quizcm->name), "Module $code Knowledge Check") === 0) {
            $kc = $quizcm;
        }
    }
    if (!$kc) {
        echo "$code: no quiz named \"Module $code Knowledge Check\", skipped\n";
        continue;
    }
    $units[$code] = ['scorm' => $scormcm, 'kc' => $kc];
}
uksort($units, 'version_compare');
if (!$units) {
    cli_error('No sub-modules found.');
}

// --show / --hide only.
if ($toggle !== null) {
    foreach (array_keys($units) as $code) {
        foreach (["AFIRST-ML-$code", "AFIRST-RETAKE-$code"] as $idnumber) {
            if ($cm = afirst_ml_cm_by_idnumber($course, $idnumber)) {
                set_coursemodule_visible($cm->id, $toggle);
                echo "$idnumber: cm {$cm->id} " . ($toggle ? 'shown' : 'hidden') . "\n";
            } else {
                echo "$idnumber: not built yet\n";
            }
        }
    }
    exit(0);
}

// Installed H5P library versions (latest of each).
// No download, embed, copyright or frame buttons on the review units (the content is the course's own).
$displayoptions = \core_h5p\helper::get_display_options((new \core_h5p\factory())->get_core(),
    (object)['frame' => 0, 'export' => 0, 'embed' => 0, 'copyright' => 0]);

$versions = [];
foreach (['H5P.Column', 'H5P.AdvancedText', 'H5P.Accordion', 'H5P.Dialogcards'] as $name) {
    $lib = $DB->get_records('h5p_libraries', ['machinename' => $name], 'majorversion DESC, minorversion DESC',
        'id, majorversion, minorversion', 0, 1);
    if (!$lib) {
        cli_error("H5P library $name is not installed. Run the scheduled task \\core\\task\\h5p_get_content_types_task first.");
    }
    $lib = reset($lib);
    $versions[$name] = [(int)$lib->majorversion, (int)$lib->minorversion];
}

$outdir = rtrim($options['outdir'], '/');
check_dir_exists($outdir);
$review = "# Microlearning review sheet: {$course->fullname}\n\n"
    . "Built " . date("Y-m-d") . " by `course-setup/build_microlearning.php` (D-24). Each unit is a short review "
    . "shown to a learner whose Knowledge Check score is below the remediation threshold. Every line is copied word for "
    . "word from the module's SCORM text; nothing was written by a model. Units stay hidden from learners until the "
    . "content owner approves them (Gate 2.3).\n\nFor each unit: check that the key points and terms are correct, "
    . "useful and complete enough for a review, and mark a decision.\n\n";

foreach ($units as $code => $u) {
    $text = \block_afirst_assistant\local\scorm_text_extractor::from_cm($u['scorm']);
    $unit = afirst_ml_extract($text);
    $title = trim(preg_replace('/^(module\s+)?m?' . preg_quote($code, '/') . '[:\s]*/i', '', $u['scorm']->name)) ?: $u['scorm']->name;
    $name = "Review: Module $code";

    // 1. Package and review sheet.
    $zipfile = "$outdir/afirst-review-$code.h5p";
    $zip = new ZipArchive();
    if ($zip->open($zipfile, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
        cli_error("Cannot write $zipfile");
    }
    $flags = JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE;
    $zip->addFromString('h5p.json', json_encode(afirst_ml_h5p_json("$name – $title", $versions), $flags));
    $zip->addFromString('content/content.json', json_encode(afirst_ml_content($code, "$name – $title", $unit, $versions), $flags));
    $zip->close();
    $review .= afirst_ml_review_md($code, $title, $u['scorm']->name, $unit);
    printf("%s: %d objectives, %d key points, %d key terms -> %s\n", $code, count($unit['objectives']),
        count($unit['panels']), count($unit['cards']), basename($zipfile));
    if ($dryrun) {
        continue;
    }

    // 2. Review unit.
    $draftid = file_get_unused_draft_itemid();
    get_file_storage()->create_file_from_pathname(['contextid' => context_user::instance($USER->id)->id,
        'component' => 'user', 'filearea' => 'draft', 'itemid' => $draftid, 'filepath' => '/',
        'filename' => basename($zipfile)], $zipfile);
    $ml = afirst_ml_cm_by_idnumber($course, "AFIRST-ML-$code");
    if (!$ml) {
        $info = (object)[
            'modulename' => 'h5pactivity', 'module' => $DB->get_field('modules', 'id', ['name' => 'h5pactivity']),
            'course' => $course->id, 'section' => $u['kc']->sectionnum, 'visible' => 0, 'visibleoncoursepage' => 1,
            'name' => $name, 'introeditor' => ['text' => '<p>A short review of Module ' . s($code) . '. It opens when '
                . 'your Knowledge Check score shows this module needs another look. Then retake the Knowledge Check.</p>',
                'format' => FORMAT_HTML, 'itemid' => 0], 'cmidnumber' => "AFIRST-ML-$code", 'packagefile' => $draftid,
            'enabletracking' => 1, 'grademethod' => 1, 'reviewmode' => 1, 'grade' => 0,
            'displayoptions' => $displayoptions,
            'completion' => COMPLETION_TRACKING_AUTOMATIC, 'completionview' => 1, 'completionexpected' => 0,
        ];
        $created = create_module($info);
        $ml = get_fast_modinfo($course)->get_cm($created->coursemodule);
        echo "  created $name (cm {$ml->id}, hidden)\n";
    } else if ($options['update-package']) {
        $context = context_module::instance($ml->id);
        file_save_draft_area_files($draftid, $context->id, 'mod_h5pactivity', 'package', 0, ['subdirs' => 0, 'maxfiles' => 1]);
        echo "  $name (cm {$ml->id}) package replaced\n";
    } else {
        echo "  $name exists (cm {$ml->id})\n";
    }
    $DB->set_field('h5pactivity', 'displayoptions', $displayoptions, ['id' => $ml->instance]);
    afirst_ml_place_after($course, $ml->id, $u['kc']->id);

    // 3. Retake.
    $retake = afirst_ml_cm_by_idnumber($course, "AFIRST-RETAKE-$code");
    if (!$retake) {
        ob_start(); // duplicate_module() runs a backup and restore that logs every step.
        $newcm = duplicate_module($course, get_fast_modinfo($course)->get_cm($u['kc']->id));
        ob_end_clean();
        $retakename = "Module $code Knowledge Check (retake)";
        $DB->set_field('quiz', 'name', $retakename, ['id' => $newcm->instance]);
        $DB->set_field('grade_items', 'itemname', $retakename,
            ['itemtype' => 'mod', 'itemmodule' => 'quiz', 'iteminstance' => $newcm->instance, 'courseid' => $course->id]);
        $DB->update_record('course_modules', (object)['id' => $newcm->id, 'idnumber' => "AFIRST-RETAKE-$code",
            'availability' => null]);
        set_coursemodule_visible($newcm->id, 0);
        rebuild_course_cache($course->id, true);
        $retake = get_fast_modinfo($course)->get_cm($newcm->id);
        echo "  created $retakename (cm {$retake->id}, hidden)\n";
    } else {
        echo "  retake exists (cm {$retake->id})\n";
    }
    afirst_ml_place_after($course, $retake->id, $ml->id);
}

file_put_contents("$outdir/REVIEW.md", $review);
echo "Review sheet: $outdir/REVIEW.md\n";
