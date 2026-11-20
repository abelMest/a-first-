#!/usr/bin/env bash
# Dev only: put dev course 4 into the Phase 3 (READINESS) demo state. Safe to re-run before each demo.
#
#   cd /c/AFIRST_Backup/afirst-rebuild && bash dev/demo_phase3.sh
#
# - Shows the readiness scenario Lesson ON DEV ONLY (production keeps it hidden until Gate 3).
# - Rebuilds the five example learners, one per readiness result (scenario paths through the Lesson web service):
#     student1  best choices, Knowledge Checks 100%           -> Ready for independent work
#     student4  two weaker choices, Knowledge Checks ~80%      -> Ready with supervision
#     student2  shares identifiable data (critical choice)    -> Not yet ready
#     student3  poor choices, no Knowledge Checks             -> Not yet ready
#     student5  stopped at decision 4                         -> In progress
# - Creates "scenario" (password AFIRST_DEV_STUDENT_PASS in dev/.env) with all Knowledge Checks passed and no
#   scenario attempt, to play the scenario live: the best path gives "Ready for independent work".
set -euo pipefail
cd "$(dirname "$0")/.."
. dev/env.sh >/dev/null
set -a; . dev/.env; set +a

run() { MSYS_NO_PATHCONV=1 $MDC exec -T -e AFIRST_DEV_STUDENT_PASS webserver "$@" 2>&1 | grep -v "Including local\|attemptsremaining"; }
sim() { run php simulate_attempt.php "$@"; }
MSYS_NO_PATHCONV=1 docker cp dev/simulate_attempt.php afirst-webserver-1:/var/www/html/simulate_attempt.php

MSYS_NO_PATHCONV=1 docker exec -i afirst-webserver-1 sh -c 'cat > /tmp/afirst_demo3.php' <<'EOF'
<?php
// Show the readiness Lesson (dev demo) and clear the scenario attempts of the demo learners.
define('CLI_SCRIPT', true);
require('/var/www/html/config.php');
require_once($CFG->dirroot . '/course/lib.php');
require_once($CFG->dirroot . '/mod/lesson/lib.php');
\core\cron::setup_user();
$record = $DB->get_record('report_afirst_readiness', ['courseid' => 4], '*', IGNORE_MULTIPLE);
if (!$record) {
    fwrite(STDERR, "No readiness scenario built in course 4: run report/afirst_readiness/cli/build_scenario.php first.\n");
    exit(1);
}
set_coursemodule_visible($record->lessoncmid, 1);
$lesson = $DB->get_record('lesson', ['id' => $record->lessonid], '*', MUST_EXIST);
foreach (['student1', 'student2', 'student3', 'student4', 'student5', 'scenario'] as $username) {
    $id = $DB->get_field('user', 'id', ['username' => $username]);
    if (!$id) {
        continue;
    }
    foreach (['lesson_attempts', 'lesson_grades', 'lesson_timer', 'lesson_branch'] as $table) {
        $DB->delete_records($table, ['lessonid' => $lesson->id, 'userid' => $id]);
    }
    lesson_update_grades($lesson, $id);
}
rebuild_course_cache(4, true);
echo "readiness Lesson cm {$record->lessoncmid} shown (dev), scenario attempts cleared\n";
echo "CMID={$record->lessoncmid}\n";
EOF

sim --ensure-user=scenario --password-env=AFIRST_DEV_STUDENT_PASS --courseid=4 >/dev/null
cmid=$(run php /tmp/afirst_demo3.php | tee /dev/stderr | sed -n 's/^CMID=//p')

# "scenario": Knowledge Checks passed once (kept on re-runs).
have=$(run php -r 'define("CLI_SCRIPT",1); require("/var/www/html/config.php"); $u=$DB->get_record("user",["username"=>"scenario"]); echo $DB->count_records_select("quiz_attempts","userid=? AND state=?",[$u->id,"finished"]);')
if [ "${have:-0}" -lt 9 ]; then
    for kc in 11 13 15 17 21 23 27 29 31; do sim --user=scenario --cmid=$kc --correct=99 >/dev/null; done
    echo "scenario: Knowledge Checks passed"
fi

sim --user=student1 --cmid=$cmid --path=A,A,A,A,A,A,A
sim --user=student4 --cmid=$cmid --path=A,B,A,A,A,C,A,A
sim --user=student2 --cmid=$cmid --path=A,A,B,A,A,A,A
sim --user=student3 --cmid=$cmid --path=B,C,B,C,B,B,B,C
sim --user=student5 --cmid=$cmid --path=A,A,A
echo "Report: http://localhost:8710/report/afirst_readiness/index.php?id=4"
