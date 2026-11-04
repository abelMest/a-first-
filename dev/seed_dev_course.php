<?php
// Local development only: seed a test course for block_afirst_assistant.
//
// Usage (inside the web container): php seed_dev_course.php <studentpass> <teacherpass> <source.txt>...
// Creates an Ollama provider instance pointing at the host's Ollama, a course with one teacher and one
// student, the assistant block, and approved sources from the given text files. Safe to re-run.

define('CLI_SCRIPT', true);
require('/var/www/html/config.php');
require_once($CFG->libdir . '/clilib.php');
require_once($CFG->dirroot . '/course/lib.php');
require_once($CFG->dirroot . '/user/lib.php');

use block_afirst_assistant\local\source_manager;
use core_ai\aiactions\generate_text;
use core_ai\manager;

[$studentpass, $teacherpass] = array_slice($argv, 1, 2);
$sourcefiles = array_slice($argv, 3);
if (!$studentpass || !$teacherpass) {
    cli_error('Usage: php seed_dev_course.php <studentpass> <teacherpass> <source.txt>...');
}
\core\session\manager::set_user(get_admin());

// AI provider: the host's Ollama, text generation only.
$aimanager = \core\di::get(manager::class);
if (!$DB->record_exists('ai_providers', ['provider' => \aiprovider_ollama\provider::class])) {
    $aimanager->create_provider_instance(
        classname: '\aiprovider_ollama\provider',
        name: 'Local Ollama (dev)',
        enabled: true,
        config: ['endpoint' => 'http://host.docker.internal:11434/', 'enableuserratelimit' => true, 'userratelimit' => 30],
        actionconfig: [
            generate_text::class => [
                'enabled' => true,
                'settings' => [
                    'model' => 'llama3.2:1b', // The 3B model needs more free RAM than this machine has with Docker running.
                    'systeminstruction' => get_string('action_generate_text_instruction', 'core_ai'),
                ],
            ],
        ],
    );
    cli_writeln('Created Ollama provider instance.');
}

// Course.
$course = $DB->get_record('course', ['shortname' => 'DS4H-DEV']);
if (!$course) {
    $course = create_course((object) [
        'fullname' => 'Data Science for Health (dev)', 'shortname' => 'DS4H-DEV', 'category' => 1, 'format' => 'topics',
        'numsections' => 3,
    ]);
    cli_writeln("Created course {$course->id}.");
}

// Users.
$enrol = enrol_get_plugin('manual');
$instance = $DB->get_record('enrol', ['courseid' => $course->id, 'enrol' => 'manual'], '*', MUST_EXIST);
foreach (['teacher1' => ['editingteacher', $teacherpass], 'student1' => ['student', $studentpass]] as $username => [$role, $pass]) {
    $user = $DB->get_record('user', ['username' => $username]);
    if (!$user) {
        $id = user_create_user((object) [
            'username' => $username, 'password' => $pass, 'firstname' => ucfirst(substr($username, 0, -1)),
            'lastname' => 'Dev', 'email' => "$username@example.com", 'auth' => 'manual', 'confirmed' => 1,
            'mnethostid' => $CFG->mnet_localhost_id,
        ]);
        $user = $DB->get_record('user', ['id' => $id]);
        cli_writeln("Created $username.");
    } else {
        update_internal_user_password($user, $pass);
    }
    $enrol->enrol_user($instance, $user->id, $DB->get_field('role', 'id', ['shortname' => $role]));
}

// Block on the course page.
$context = context_course::instance($course->id);
if (!$DB->record_exists('block_instances', ['blockname' => 'afirst_assistant', 'parentcontextid' => $context->id])) {
    $page = new moodle_page();
    $page->set_course($course);
    $page->set_pagetype('course-view-' . $course->format);
    $page->blocks->add_region('side-pre');
    $page->blocks->add_block('afirst_assistant', 'side-pre', -10, false, 'course-view-*');
    cli_writeln('Added assistant block.');
}

// Approved sources.
foreach ($sourcefiles as $path) {
    $title = 'Module ' . str_replace('_', '.', preg_replace('/^topic_(\d_\d).*$/', '$1', basename($path)));
    if ($DB->record_exists('block_afirst_source', ['courseid' => $course->id, 'title' => $title])) {
        continue;
    }
    $now = time();
    $source = (object) [
        'courseid' => $course->id, 'title' => $title, 'filename' => basename($path),
        'contenthash' => sha1_file($path), 'status' => source_manager::STATUS_PENDING, 'usermodified' => get_admin()->id,
        'timecreated' => $now, 'timemodified' => $now,
    ];
    $source->id = $DB->insert_record('block_afirst_source', $source);
    $source->chunkcount = source_manager::index_text($source->id, file_get_contents($path));
    $DB->set_field('block_afirst_source', 'chunkcount', $source->chunkcount, ['id' => $source->id]);
    source_manager::approve($source);
    cli_writeln("Approved $title ({$source->chunkcount} passages).");
}

cli_writeln("Done. Course id {$course->id}: {$CFG->wwwroot}/course/view.php?id={$course->id}");
