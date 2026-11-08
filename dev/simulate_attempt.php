<?php
// Dev only: test learners and scripted quiz attempts for checking the ADAPTIVE rules.
//
// Inside the web container, from the Moodle root:
//   php simulate_attempt.php --ensure-user=student2 --password-env=AFIRST_DEV_STUDENT_PASS --courseid=4
//   php simulate_attempt.php --user=student1 --cmid=38 --correct=10      (answer 10 questions right, rest wrong)
//   php simulate_attempt.php --user=student1 --report --courseid=4       (what is available to the learner)
//   php simulate_attempt.php --user=student1 --reset --courseid=4        (remove the learner's quiz and lesson attempts)
//   php simulate_attempt.php --user=student1 --cmid=60 --path=A,A,A,A,A,A,A  (play a readiness scenario Lesson)
// Attempts go through the core quiz attempt API, so grades, completion and availability update as they
// would for a real learner. Never run this on a site with real learners.

define('CLI_SCRIPT', true);
require('config.php');
require_once($CFG->libdir . '/clilib.php');
require_once($CFG->dirroot . '/mod/quiz/locallib.php');
require_once($CFG->dirroot . '/user/lib.php');
require_once($CFG->libdir . '/enrollib.php');
require_once($CFG->dirroot . '/mod/lesson/locallib.php');

[$o] = cli_get_params(['user' => null, 'cmid' => null, 'correct' => 0, 'report' => false, 'reset' => false, 'courseid' => null,
    'ensure-user' => null, 'password-env' => null, 'path' => null]);

$host = parse_url($CFG->wwwroot, PHP_URL_HOST);
if (!in_array($host, ['localhost', '127.0.0.1'], true)) {
    cli_error('Dev sites only.');
}
\core\cron::setup_user();

if ($o['ensure-user']) {
    $user = $DB->get_record('user', ['username' => $o['ensure-user'], 'deleted' => 0]);
    if (!$user) {
        $password = getenv((string)$o['password-env']);
        if (!$password) {
            cli_error('Set the password in the environment variable named by --password-env.');
        }
        $id = user_create_user((object)['username' => $o['ensure-user'], 'password' => $password, 'auth' => 'manual',
            'firstname' => 'Test', 'lastname' => ucfirst($o['ensure-user']), 'email' => $o['ensure-user'] . '@example.test',
            'confirmed' => 1, 'mnethostid' => $CFG->mnet_localhost_id]);
        $user = $DB->get_record('user', ['id' => $id]);
        echo "created {$user->username} (id {$user->id})\n";
    }
    $instance = $DB->get_record('enrol', ['courseid' => $o['courseid'], 'enrol' => 'manual'], '*', MUST_EXIST);
    enrol_get_plugin('manual')->enrol_user($instance, $user->id, $DB->get_field('role', 'id', ['shortname' => 'student']));
    echo "{$user->username} enrolled as student in course {$o['courseid']}\n";
    exit(0);
}

$user = $DB->get_record('user', ['username' => $o['user'], 'deleted' => 0], '*', MUST_EXIST);

if ($o['reset']) {
    // Remove the learner's quiz attempts in the course (grades and completion are recalculated by core).
    foreach ($DB->get_records('quiz', ['course' => $o['courseid']]) as $quiz) {
        foreach (quiz_get_user_attempts($quiz->id, $user->id, 'all') as $attempt) {
            quiz_delete_attempt($attempt, $quiz);
        }
        $cm = get_coursemodule_from_instance('quiz', $quiz->id, $quiz->course);
        $completion = new completion_info(get_course($quiz->course));
        $completion->update_state($cm, COMPLETION_UNKNOWN, $user->id);
    }
    echo "{$user->username}: quiz attempts in course {$o['courseid']} removed\n";
    exit(0);
}

if ($o['report']) {
    $modinfo = get_fast_modinfo($o['courseid'], $user->id);
    foreach ($modinfo->get_cms() as $cm) {
        if (!in_array($cm->modname, ['quiz', 'scorm', 'h5pactivity'])) {
            continue;
        }
        $state = !$cm->visible ? 'hidden' : ($cm->available ? 'AVAILABLE' : 'locked');
        printf("  cm %-3d %-10s %-48s %s\n", $cm->id, $state, mb_strimwidth($cm->name, 0, 48), $cm->visible && !$cm->available
            ? trim(html_to_text(\core_availability\info::format_info($cm->availableinfo, $cm->get_course()), 0, false)) : '');
    }
    exit(0);
}

if ($o['path'] !== null) {
    // Play a readiness scenario Lesson through the Lesson web-service API (as the mobile app does).
    // --path lists the option ids chosen at each decision point in order, e.g. --path=A,A,A,A,A,A,A.
    $cm = get_coursemodule_from_id('lesson', $o['cmid'], 0, false, MUST_EXIST);
    $built = $DB->get_record('report_afirst_readiness', ['lessoncmid' => $cm->id], '*', MUST_EXIST);
    $scenario = json_decode($built->scenario, true);
    $answerids = [];
    foreach (json_decode($built->answermap, true) as $answerid => $m) {
        $answerids[$m['node']][$m['option']] = (int)$answerid;
    }
    $nodes = array_column($scenario['nodes'], null, 'id');
    \core\session\manager::set_user($user);
    \mod_lesson_external::launch_attempt($cm->instance);
    $node = $scenario['start'];
    $taken = [];
    foreach (explode(',', $o['path']) as $option) {
        if ($node === 'end') {
            cli_error('The path is longer than the scenario.');
        }
        $answerid = $answerids[$node][$option] ?? cli_error("No option $option at $node.");
        $pageid = (int)$DB->get_field('lesson_answers', 'pageid', ['id' => $answerid], MUST_EXIST);
        \mod_lesson_external::process_page($cm->instance, $pageid, [
            // The hidden field marks the Lesson answer form as submitted, as the mobile app sends it.
            ['name' => '_qf__lesson_display_answer_form_multichoice_singleanswer', 'value' => 1],
            ['name' => 'answerid', 'value' => $answerid],
        ]);
        $taken[] = "$node:$option";
        $node = array_column($nodes[$node]['options'], 'next', 'id')[$option];
    }
    if ($node !== 'end') {
        echo "{$user->username}: stopped at $node (attempt left unfinished)
";
        exit(0);
    }
    $result = \mod_lesson_external::finish_attempt($cm->instance);
    $grade = $DB->get_field_sql('SELECT MAX(grade) FROM {lesson_grades} WHERE lessonid = ? AND userid = ?', [$cm->instance, $user->id]);
    printf("%s: %s -> finished, lesson grade %s%%
", $user->username, implode(' ', $taken), format_float((float)$grade, 1));
    exit(0);
}

$cm = get_coursemodule_from_id('quiz', $o['cmid'], 0, false, MUST_EXIST);
$quizobj = \mod_quiz\quiz_settings::create($cm->instance, $user->id);
\core\session\manager::set_user($user);
$quba = question_engine::make_questions_usage_by_activity('mod_quiz', $quizobj->get_context());
$quba->set_preferred_behaviour($quizobj->get_quiz()->preferredbehaviour);
$attempts = quiz_get_user_attempts($cm->instance, $user->id, 'all');
$number = count($attempts) + 1;
$now = time();
$attempt = quiz_create_attempt($quizobj, $number, end($attempts) ?: null, $now, false, $user->id);
quiz_start_new_attempt($quizobj, $quba, $attempt, $number, $now);
quiz_attempt_save_started($quizobj, $quba, $attempt);
$attemptobj = \mod_quiz\quiz_attempt::create($attempt->id);

$responses = [];
$right = (int)$o['correct'];
foreach ($attemptobj->get_slots() as $i => $slot) {
    // Single-answer multichoice: simulated responses name the answer by its text.
    $question = $attemptobj->get_question_attempt($slot)->get_question();
    foreach ($question->answers as $answer) {
        if (($answer->fraction > 0) === ($i < $right)) {
            $responses[$slot] = ['answer' => clean_param($answer->answer, PARAM_NOTAGS)];
            break;
        }
    }
}
$attemptobj->process_submitted_actions($now, false, $responses);
$attemptobj->process_submit($now, false);
$attemptobj->process_grade_submission($now);
$attemptobj = \mod_quiz\quiz_attempt::create($attempt->id);
printf("%s: attempt %d on \"%s\": %s / %s\n", $user->username, $number, $cm->name,
    format_float($attemptobj->get_sum_marks(), 2), format_float($quizobj->get_quiz()->sumgrades, 2));
