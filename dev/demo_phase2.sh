#!/usr/bin/env bash
# Dev only: put dev course 4 into the Phase 2 (ADAPTIVE) demo state. Safe to re-run before each demo.
#
#   cd /c/AFIRST_Backup/afirst-rebuild && bash dev/demo_phase2.sh
#
# Demo learners (password: AFIRST_DEV_STUDENT_PASS in dev/.env):
#   fasttrack    scored 10/10 on the Module 2 diagnostic -> both Module 2 Knowledge Checks open without the SCORM
#   remediation  completed Module 1.1 and scored 1/5 (20%) on its Knowledge Check -> Review 1.1 open, retake locked
#   newlearner   no attempts -> every Knowledge Check locked, diagnostics open (take one live)
# It also shows the review units and retakes ON DEV ONLY (they stay hidden in production until the content
# owner approves them) and keeps the pilot instruments hidden.
set -euo pipefail
cd "$(dirname "$0")/.."
. dev/env.sh >/dev/null
set -a; . dev/.env; set +a

run() { MSYS_NO_PATHCONV=1 $MDC exec -T -e AFIRST_DEV_STUDENT_PASS webserver "$@" 2>&1 | grep -v "Including local"; }
sim() { run php simulate_attempt.php "$@"; }

MSYS_NO_PATHCONV=1 docker cp dev/simulate_attempt.php afirst-webserver-1:/var/www/html/simulate_attempt.php

for u in fasttrack remediation newlearner; do
    sim --ensure-user=$u --password-env=AFIRST_DEV_STUDENT_PASS --courseid=4
    sim --user=$u --reset --courseid=4
done

MSYS_NO_PATHCONV=1 docker exec -i afirst-webserver-1 sh -c 'cat > /tmp/afirst_demo_state.php' <<'EOF'
<?php
// Show review units and retakes (dev demo), mark SCORM 1.1 complete for "remediation", clear its completion
// of everything else, and make sure the pilot instruments stay hidden.
define('CLI_SCRIPT', true);
require('/var/www/html/config.php');
require_once($CFG->dirroot . '/course/lib.php');
require_once($CFG->libdir . '/completionlib.php');
\core\cron::setup_user(); // Admin: overriding completion needs the capability.
$course = get_course(4);
foreach (get_fast_modinfo($course)->get_cms() as $cm) {
    if (preg_match('/^AFIRST-(ML|RETAKE)-/', $cm->idnumber) && !$cm->visible) {
        set_coursemodule_visible($cm->id, 1);
    }
    if (str_starts_with($cm->idnumber, 'AFIRST-PILOT-') && $cm->visible) {
        set_coursemodule_visible($cm->id, 0);
    }
}
rebuild_course_cache($course->id, true);
$user = $DB->get_record('user', ['username' => 'remediation'], '*', MUST_EXIST);
$DB->delete_records_select('course_modules_completion',
    'userid = ? AND coursemoduleid IN (SELECT id FROM {course_modules} WHERE course = ?)', [$user->id, $course->id]);
$DB->delete_records('course_modules_viewed', ['userid' => $user->id]);
$scorm = get_coursemodule_from_id('scorm', 10, $course->id, false, MUST_EXIST);
(new completion_info($course))->update_state($scorm, COMPLETION_COMPLETE, $user->id, true);
get_fast_modinfo($course->id, 0, true);
echo "review units and retakes shown (dev), pilot instruments hidden, Module 1.1 SCORM complete for remediation\n";
EOF
run php /tmp/afirst_demo_state.php

sim --user=fasttrack --cmid=38 --correct=10
sim --user=remediation --cmid=11 --correct=1

for u in fasttrack remediation newlearner; do
    echo "== $u =="
    sim --user=$u --report --courseid=4 | grep -E "cm (9|38|10|11|42|43|14|15|17|46|47) " || true
done
