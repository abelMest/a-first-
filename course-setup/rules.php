<?php
// A-FIRST course setup: restrict-access rules for fast-track and remediation.
//
// Pure functions with no Moodle dependency, so selfcheck.php can test them with
// plain PHP. apply_course_config.php supplies the Moodle look-ups (grade item ids)
// and does the validation and writing through core APIs.
//
// Availability JSON follows core_availability\tree and the availability_grade /
// availability_completion condition plugins:
//   grade:      {"type":"grade","id":<grade_items.id>,"min":90}  score >= min (percent)
//               {"type":"grade","id":<grade_items.id>,"max":80}  score <  max (percent)
//   completion: {"type":"completion","cm":<cmid>,"e":1}          COMPLETION_COMPLETE
// A root "&" tree carries "showc" (one flag per child); a root "|" tree carries "show".

/** Completion state "must be marked complete" (COMPLETION_COMPLETE in completionlib). */
const AFIRST_CS_COMPLETION_COMPLETE = 1;

/**
 * Checks the decoded course_config.json and returns a list of error strings.
 *
 * @param mixed $config
 * @return string[]
 */
function afirst_cs_validate_config($config): array {
    $errors = [];
    if (!is_array($config)) {
        return ['config is not a JSON object'];
    }
    if (empty($config['courseid']) || !is_int($config['courseid'])) {
        $errors[] = 'courseid must be a positive integer';
    }
    $t = $config['thresholds'] ?? [];
    foreach (['fasttrack_min_percent', 'remediation_below_percent'] as $key) {
        if (!isset($t[$key]) || !is_numeric($t[$key]) || $t[$key] <= 0 || $t[$key] > 100) {
            $errors[] = "thresholds.$key must be a number in (0, 100]";
        }
    }
    if (empty($config['modules']) || !is_array($config['modules'])) {
        $errors[] = 'modules must be a non-empty object keyed by module code';
        return $errors;
    }
    $seen = [];
    $diagnostics = [];
    foreach ($config['modules'] as $code => $m) {
        $code = (string)$code;
        if (!is_array($m)) {
            $errors[] = "modules.$code must be an object";
            continue;
        }
        $single = ['diagnostic_cmid', 'knowledgecheck_cmid', 'retake_cmid'];
        $lists = ['content_cmids', 'microlearning_cmids'];
        foreach ($single as $key) {
            $v = $m[$key] ?? null;
            if ($v !== null && (!is_int($v) || $v <= 0)) {
                $errors[] = "modules.$code.$key must be a positive integer or null";
            } else if ($v !== null && $key === 'diagnostic_cmid') {
                $diagnostics[$v][] = "$code.$key"; // One diagnostic may cover several sub-modules (D-23).
            } else if ($v !== null) {
                $seen[$v][] = "$code.$key";
            }
        }
        foreach ($lists as $key) {
            $v = $m[$key] ?? [];
            if (!is_array($v)) {
                $errors[] = "modules.$code.$key must be a list of cmids";
                continue;
            }
            foreach ($v as $id) {
                if (!is_int($id) || $id <= 0) {
                    $errors[] = "modules.$code.$key contains a non-positive-integer cmid";
                } else {
                    $seen[$id][] = "$code.$key";
                }
            }
        }
    }
    foreach ($seen as $cmid => $uses) {
        if (count($uses) > 1) {
            $errors[] = "cmid $cmid is used more than once: " . implode(', ', $uses);
        }
    }
    foreach ($diagnostics as $cmid => $uses) {
        if (isset($seen[$cmid])) {
            $errors[] = "cmid $cmid is a diagnostic and also: " . implode(', ', $seen[$cmid]);
        }
    }
    return $errors;
}

/**
 * Grade condition on a whole activity's grade item.
 */
function afirst_cs_grade_condition(int $gradeitemid, ?float $min, ?float $max): array {
    $c = ['type' => 'grade', 'id' => $gradeitemid];
    if ($min !== null) {
        $c['min'] = $min;
    }
    if ($max !== null) {
        $c['max'] = $max;
    }
    return $c;
}

/**
 * Completion condition: the given activity must be marked complete.
 */
function afirst_cs_completion_condition(int $cmid): array {
    return ['type' => 'completion', 'cm' => $cmid, 'e' => AFIRST_CS_COMPLETION_COMPLETE];
}

/**
 * Builds the planned availability tree for every activity the config manages.
 *
 * Rules per module:
 *  - knowledge check: open when all content activities are complete OR the module
 *    diagnostic is at least fasttrack_min_percent (fast-track route).
 *  - each microlearning unit: open when the knowledge check is below
 *    remediation_below_percent (remediation route); hidden until then unless
 *    hide_microlearning_until_unlocked is false.
 *  - retake (optional): open when the knowledge check is below the threshold AND
 *    every microlearning unit is complete.
 * A rule whose inputs are missing (null cmid, empty list) is skipped with a notice.
 *
 * @param array $config Decoded and validated course_config.json.
 * @param callable $gradeitemid fn(int $cmid): ?int returning the activity's grade item id.
 * @return array{0: array<int, array{module: string, role: string, tree: array}>, 1: string[]}
 *         [plan keyed by target cmid, notices]
 */
function afirst_cs_build_plan(array $config, callable $gradeitemid): array {
    $plan = [];
    $notices = [];
    $min = (float)$config['thresholds']['fasttrack_min_percent'];
    $max = (float)$config['thresholds']['remediation_below_percent'];
    $hideml = $config['hide_microlearning_until_unlocked'] ?? true;

    foreach ($config['modules'] as $code => $m) {
        $code = (string)$code;
        $diag = $m['diagnostic_cmid'] ?? null;
        $kc = $m['knowledgecheck_cmid'] ?? null;
        $content = array_values($m['content_cmids'] ?? []);
        $ml = array_values($m['microlearning_cmids'] ?? []);
        $retake = $m['retake_cmid'] ?? null;

        if ($kc === null) {
            $notices[] = "$code: no knowledgecheck_cmid, nothing to configure";
            continue;
        }
        $kcgrade = $gradeitemid($kc);
        if ($kcgrade === null) {
            $notices[] = "$code: knowledge check cm $kc has no grade item, skipping module";
            continue;
        }

        // Fast-track route.
        if ($diag === null) {
            $notices[] = "$code: diagnostic not created yet, knowledge check rule skipped";
        } else if (!$content) {
            $notices[] = "$code: no content_cmids, knowledge check rule skipped";
        } else if (($diaggrade = $gradeitemid($diag)) === null) {
            $notices[] = "$code: diagnostic cm $diag has no grade item, knowledge check rule skipped";
        } else {
            $completions = array_map('afirst_cs_completion_condition', $content);
            $contentdone = count($completions) === 1 ? $completions[0]
                : ['op' => '&', 'c' => $completions];
            $plan[$kc] = [
                'module' => $code,
                'role' => 'knowledgecheck',
                'tree' => [
                    'op' => '|',
                    'c' => [$contentdone, afirst_cs_grade_condition($diaggrade, $min, null)],
                    'show' => true,
                ],
            ];
        }

        // Remediation route.
        if (!$ml) {
            $notices[] = "$code: no microlearning_cmids yet, remediation rule skipped";
        }
        foreach ($ml as $mlcmid) {
            $plan[$mlcmid] = [
                'module' => $code,
                'role' => 'microlearning',
                'tree' => [
                    'op' => '&',
                    'c' => [afirst_cs_grade_condition($kcgrade, null, $max)],
                    'showc' => [!$hideml],
                ],
            ];
        }
        if ($retake !== null) {
            if (!$ml) {
                $notices[] = "$code: retake_cmid set but no microlearning, retake rule skipped";
            } else {
                $conds = [afirst_cs_grade_condition($kcgrade, null, $max)];
                foreach ($ml as $mlcmid) {
                    $conds[] = afirst_cs_completion_condition($mlcmid);
                }
                $plan[$retake] = [
                    'module' => $code,
                    'role' => 'retake',
                    'tree' => ['op' => '&', 'c' => $conds, 'showc' => array_fill(0, count($conds), true)],
                ];
            }
        }
    }
    ksort($plan);
    return [$plan, $notices];
}

/**
 * Collects every leaf condition of a decoded availability tree.
 *
 * @param mixed $tree Decoded JSON (assoc arrays).
 * @return array[]|null Leaf conditions, or null when the structure is not a tree.
 */
function afirst_cs_leaves($tree): ?array {
    if (!is_array($tree) || !isset($tree['c']) || !is_array($tree['c'])) {
        return null;
    }
    $leaves = [];
    foreach ($tree['c'] as $child) {
        if (is_array($child) && isset($child['c'])) {
            $sub = afirst_cs_leaves($child);
            if ($sub === null) {
                return null;
            }
            $leaves = array_merge($leaves, $sub);
        } else {
            $leaves[] = $child;
        }
    }
    return $leaves;
}

/**
 * True when an existing availability tree looks like one this script wrote for the
 * module: only grade/completion leaves that point at that module's own activities.
 * Such a tree is updated in place (e.g. after a threshold change) without --replace.
 *
 * @param mixed $current Decoded existing availability.
 * @param int[] $gradeitemids Grade item ids of the module's activities.
 * @param int[] $cmids Course-module ids of the module's activities.
 */
function afirst_cs_is_managed($current, array $gradeitemids, array $cmids): bool {
    $leaves = afirst_cs_leaves($current);
    if (!$leaves) {
        return false;
    }
    foreach ($leaves as $leaf) {
        $type = $leaf['type'] ?? '';
        if ($type === 'grade' && in_array($leaf['id'] ?? null, $gradeitemids, true)) {
            continue;
        }
        if ($type === 'completion' && in_array($leaf['cm'] ?? null, $cmids, true)) {
            continue;
        }
        return false;
    }
    return true;
}

/**
 * Canonical JSON for comparison and storage (numbers normalised, no escaping noise).
 */
function afirst_cs_encode(array $tree): string {
    return json_encode(afirst_cs_normalise($tree), JSON_UNESCAPED_SLASHES | JSON_PRESERVE_ZERO_FRACTION);
}

/**
 * Normalises numeric strings and whole floats so "90", 90 and 90.0 compare equal.
 */
function afirst_cs_normalise($value) {
    if (is_array($value)) {
        $out = [];
        foreach ($value as $k => $v) {
            $out[$k] = afirst_cs_normalise($v);
        }
        if (array_keys($out) !== range(0, count($out) - 1)) {
            ksort($out);
        }
        return $out;
    }
    if (is_float($value) || (is_string($value) && is_numeric($value))) {
        $f = (float)$value;
        return $f == floor($f) ? (int)$f : $f;
    }
    return $value;
}

/**
 * Decides what to do with one activity.
 *
 * @param string|null $currentjson Existing course_modules.availability.
 * @param array $planned Planned tree.
 * @param bool $managed Whether the existing tree was written by this script (see afirst_cs_is_managed).
 * @param bool $replace Whether --replace was given.
 * @return string one of create | unchanged | update | replace | conflict
 */
function afirst_cs_decide(?string $currentjson, array $planned, bool $managed, bool $replace): string {
    if ($currentjson === null || trim($currentjson) === '') {
        return 'create';
    }
    $current = json_decode($currentjson, true);
    if (is_array($current) && afirst_cs_encode($current) === afirst_cs_encode($planned)) {
        return 'unchanged';
    }
    if ($managed) {
        return 'update';
    }
    return $replace ? 'replace' : 'conflict';
}

/**
 * Draft the "modules" part of a course_config.json from a course's sections, for a human to review.
 *
 * Conventions (see docs/ONBOARDING_A_COURSE.md):
 *  - a section is a module when its name contains a code like "1.2" or "Module 3"; otherwise its section number is used;
 *  - content: SCORM, page, book, file, URL, lesson and IMS content packages;
 *  - diagnostic: a quiz with ID number AFIRST-DIAG-* or "diagnostic" in its name;
 *  - retake: a quiz with "retake" in its name;
 *  - knowledge check: a quiz with "knowledge check" in its name, else the section's last other quiz;
 *  - microlearning: H5P activities.
 * Sections with neither content nor a knowledge check are left out.
 *
 * @param array $sections List of ['number' => int, 'name' => string, 'cms' => list of
 *     ['id' => int, 'modname' => string, 'name' => string, 'idnumber' => string]] in course order.
 * @return array{0: array, 1: string[]} Modules keyed by code, and warnings for the reviewer.
 */
function afirst_cs_draft_modules(array $sections): array {
    $contenttypes = ['scorm', 'page', 'book', 'resource', 'url', 'lesson', 'imscp'];
    $modules = [];
    $warnings = [];
    foreach ($sections as $section) {
        $name = (string)($section['name'] ?? '');
        if (preg_match('/\b(\d+\.\d+)\b/', $name, $m)) {
            $code = $m[1];
        } else if (preg_match('/\bmodule\s+(\d+)\b/i', $name, $m)) {
            $code = $m[1];
        } else {
            $code = (string)$section['number'];
        }
        $entry = ['diagnostic_cmid' => null, 'knowledgecheck_cmid' => null, 'content_cmids' => [],
            'microlearning_cmids' => [], 'retake_cmid' => null];
        $otherquizzes = [];
        foreach ($section['cms'] as $cm) {
            $cmname = strtolower((string)$cm['name']);
            if (in_array($cm['modname'], $contenttypes, true)) {
                $entry['content_cmids'][] = (int)$cm['id'];
            } else if ($cm['modname'] === 'h5pactivity') {
                $entry['microlearning_cmids'][] = (int)$cm['id'];
            } else if ($cm['modname'] === 'quiz') {
                if (str_starts_with(strtoupper((string)$cm['idnumber']), 'AFIRST-DIAG-') || str_contains($cmname, 'diagnostic')) {
                    $entry['diagnostic_cmid'] ??= (int)$cm['id'];
                } else if (str_contains($cmname, 'retake')) {
                    $entry['retake_cmid'] ??= (int)$cm['id'];
                } else if (str_contains($cmname, 'knowledge check')) {
                    $entry['knowledgecheck_cmid'] ??= (int)$cm['id'];
                } else {
                    $otherquizzes[] = (int)$cm['id'];
                }
            }
        }
        if ($entry['knowledgecheck_cmid'] === null && $otherquizzes) {
            $entry['knowledgecheck_cmid'] = end($otherquizzes);
            if (count($otherquizzes) > 1) {
                $warnings[] = "module $code: several quizzes; took the last one (cm {$entry['knowledgecheck_cmid']}) as the knowledge check";
            }
        }
        if (!$entry['content_cmids'] && $entry['knowledgecheck_cmid'] === null) {
            continue;
        }
        if ($entry['knowledgecheck_cmid'] === null) {
            $warnings[] = "module $code: no knowledge check quiz found";
        }
        if (isset($modules[$code])) {
            $warnings[] = "module $code appears in more than one section; only the first is kept";
            continue;
        }
        $modules[$code] = $entry;
    }
    return [$modules, $warnings];
}
