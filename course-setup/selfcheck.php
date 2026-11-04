<?php
// Self-check for rules.php: runs with plain PHP, no Moodle needed.
//   php selfcheck.php            run the assertions
//   php selfcheck.php --print    also print the planned JSON for a fully filled example
//
// Grade item ids are faked as 1000 + cmid; on a real site apply_course_config.php
// resolves them from the gradebook.

require_once(__DIR__ . '/rules.php');

$failures = 0;
$checks = 0;
function check(bool $ok, string $label): void {
    global $failures, $checks;
    $checks++;
    if (!$ok) {
        $failures++;
        echo "FAIL $label\n";
    }
}

$fakegrade = fn(int $cmid): ?int => 1000 + $cmid;

// 1. The shipped config is valid and, with nothing created yet, plans nothing.
$real = json_decode(file_get_contents(__DIR__ . '/../courses/ds4h/course_config.json'), true);
check(afirst_cs_validate_config($real) === [], 'courses/ds4h/course_config.json validates');
[$plan, $notices] = afirst_cs_build_plan($real, $fakegrade);
check($plan === [], 'no diagnostics or microlearning yet: no activity is changed');
check(count($notices) === 18, 'two notices per module (diagnostic, microlearning) = 18, got ' . count($notices));

// 2. Invalid configs are rejected.
$bad = $real;
$bad['thresholds']['fasttrack_min_percent'] = 120;
$bad['modules']['1.2']['knowledgecheck_cmid'] = 12;
$errors = afirst_cs_validate_config($bad);
check(count($errors) === 2, 'threshold range and duplicate cmid both reported');
check(afirst_cs_validate_config('x') !== [], 'non-object config rejected');

// 3. A fully filled module produces the three rules.
$full = $real;
$full['modules'] = [
    '1.1' => ['diagnostic_cmid' => 101, 'knowledgecheck_cmid' => 12, 'content_cmids' => [11],
        'microlearning_cmids' => [201], 'retake_cmid' => 301],
    '1.2' => ['diagnostic_cmid' => 102, 'knowledgecheck_cmid' => 14, 'content_cmids' => [13, 15],
        'microlearning_cmids' => [202, 203], 'retake_cmid' => null],
];
check(afirst_cs_validate_config($full) === [], 'example config validates');
[$plan, $notices] = afirst_cs_build_plan($full, $fakegrade);
check(array_keys($plan) === [12, 14, 201, 202, 203, 301], 'planned cms: ' . implode(',', array_keys($plan)));
check($notices === [], 'no notices for a complete config');

$kc = $plan[12]['tree'];
check($kc['op'] === '|' && $kc['show'] === true && !isset($kc['showc']), 'knowledge check: OR root with show');
check($kc['c'][0] === ['type' => 'completion', 'cm' => 11, 'e' => 1], 'knowledge check: content completion');
check($kc['c'][1] === ['type' => 'grade', 'id' => 1101, 'min' => 90.0], 'knowledge check: diagnostic >= 90%');

$kc2 = $plan[14]['tree'];
check($kc2['c'][0]['op'] === '&' && count($kc2['c'][0]['c']) === 2, 'two content cms are ANDed in a sub-tree');

$ml = $plan[201]['tree'];
check($ml === ['op' => '&', 'c' => [['type' => 'grade', 'id' => 1012, 'max' => 80.0]], 'showc' => [false]],
    'microlearning: knowledge check < 80%, hidden until unlocked');
check($plan[202]['tree'] == $plan[203]['tree'], 'each microlearning unit gets the same rule');

$retake = $plan[301]['tree'];
check($retake['c'][0]['max'] === 80.0 && $retake['c'][1] === ['type' => 'completion', 'cm' => 201, 'e' => 1],
    'retake: knowledge check < 80% AND microlearning complete');
check(count($retake['showc']) === count($retake['c']), 'retake: one showc flag per condition');

$visible = $full;
$visible['hide_microlearning_until_unlocked'] = false;
check(afirst_cs_build_plan($visible, $fakegrade)[0][201]['tree']['showc'] === [true], 'microlearning can be shown greyed out');

// 4. Missing grade items skip rules instead of writing broken conditions.
[$p, $n] = afirst_cs_build_plan($full, fn(int $cmid) => $cmid === 101 ? null : 1000 + $cmid);
check(!isset($p[12]) && isset($p[201]), 'diagnostic without grade item: only the knowledge check rule is skipped');

// 5. Idempotency and conflict handling.
$json = afirst_cs_encode($kc);
check(afirst_cs_decide(null, $kc, false, false) === 'create', 'empty availability: create');
check(afirst_cs_decide('', $kc, false, false) === 'create', 'blank availability: create');
check(afirst_cs_decide($json, $kc, true, false) === 'unchanged', 'second run: unchanged');
$reordered = '{"show":true,"c":[{"e":1,"cm":11,"type":"completion"},{"id":1101,"type":"grade","min":"90.0"}],"op":"|"}';
check(afirst_cs_decide($reordered, $kc, false, false) === 'unchanged', 'key order and numeric form do not matter');

$moduleids = [101, 12, 301, 11, 201];
$modulegrades = array_map($fakegrade, $moduleids);
$old = $kc;
$old['c'][1]['min'] = 85.0;
check(afirst_cs_is_managed(json_decode(afirst_cs_encode($old), true), $modulegrades, $moduleids),
    'a tree this script wrote is recognised');
check(afirst_cs_decide(afirst_cs_encode($old), $kc, true, false) === 'update', 'threshold change: update in place');

$manual = '{"op":"&","c":[{"type":"date","d":">=","t":1767225600}],"showc":[true]}';
$isman = afirst_cs_is_managed(json_decode($manual, true), $modulegrades, $moduleids);
check(!$isman, 'a hand-made date restriction is not ours');
check(afirst_cs_decide($manual, $kc, $isman, false) === 'conflict', 'hand-made restriction kept by default');
check(afirst_cs_decide($manual, $kc, $isman, true) === 'replace', 'hand-made restriction replaced with --replace');
$othermodule = '{"op":"&","c":[{"type":"grade","id":1999,"max":80}],"showc":[false]}';
check(!afirst_cs_is_managed(json_decode($othermodule, true), $modulegrades, $moduleids),
    'a grade condition on another module\'s activity is not ours');


// Drafting a config from course sections (generate_course_config.php).
[$draft, $warnings] = afirst_cs_draft_modules([
    ['number' => 0, 'name' => 'General', 'cms' => [['id' => 1, 'modname' => 'forum', 'name' => 'News', 'idnumber' => '']]],
    ['number' => 1, 'name' => 'Module 1.1: Data literacy', 'cms' => [
        ['id' => 10, 'modname' => 'quiz', 'name' => 'Module 1.1 Diagnostic', 'idnumber' => ''],
        ['id' => 11, 'modname' => 'scorm', 'name' => 'Storyboard', 'idnumber' => ''],
        ['id' => 12, 'modname' => 'quiz', 'name' => 'Knowledge Check 1.1', 'idnumber' => ''],
        ['id' => 13, 'modname' => 'h5pactivity', 'name' => 'Microlearning', 'idnumber' => ''],
        ['id' => 14, 'modname' => 'quiz', 'name' => 'Knowledge check 1.1 (retake)', 'idnumber' => ''],
    ]],
    ['number' => 2, 'name' => 'Week two', 'cms' => [
        ['id' => 20, 'modname' => 'page', 'name' => 'Reading', 'idnumber' => ''],
        ['id' => 21, 'modname' => 'quiz', 'name' => 'Quiz A', 'idnumber' => ''],
        ['id' => 22, 'modname' => 'quiz', 'name' => 'Quiz B', 'idnumber' => ''],
    ]],
]);
check(array_map('strval', array_keys($draft)) === ['1.1', '2'], 'draft: module codes from names, section number as fallback, empty sections skipped');
check($draft['1.1'] === ['diagnostic_cmid' => 10, 'knowledgecheck_cmid' => 12, 'content_cmids' => [11],
    'microlearning_cmids' => [13], 'retake_cmid' => 14], 'draft: roles detected in a full module');
check($draft['2']['knowledgecheck_cmid'] === 22 && count($warnings) === 1, 'draft: last quiz taken as knowledge check, with a warning');
$draftconfig = ['courseid' => 5, 'thresholds' => ['fasttrack_min_percent' => 90, 'remediation_below_percent' => 80],
    'modules' => $draft];
check(afirst_cs_validate_config($draftconfig) === [], 'draft config validates');

if (in_array('--print', $argv)) {
    echo "\nPlanned availability for the example config (grade item id = 1000 + cmid):\n";
    foreach ($plan as $cmid => $entry) {
        printf("  module %s %-14s cm %-4d %s\n", $entry['module'], $entry['role'], $cmid, afirst_cs_encode($entry['tree']));
    }
    echo "\n";
}

echo ($failures ? "FAILED $failures of $checks checks\n" : "OK: $checks checks passed\n");
exit($failures ? 1 : 0);
