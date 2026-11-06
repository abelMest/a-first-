<?php
// Local development only: restore a course backup as a NEW course with every user-related setting off.
//
// Usage (inside the web container):
//   php restore_without_users.php <backup.mbz> "<full name>" <shortname> [categoryid]
// Learner accounts, enrolments, role assignments, completion, logs, grade history and comments are not restored,
// so a production backup can be used for development without copying personal data (Proposal §3.6).

define('CLI_SCRIPT', true);
require('/var/www/html/config.php');
require_once($CFG->libdir . '/clilib.php');
require_once($CFG->dirroot . '/backup/util/includes/restore_includes.php');

[$file, $fullname, $shortname] = array_slice($argv, 1, 3);
$categoryid = (int)($argv[4] ?? 1);
if (!$file || !$fullname || !$shortname || !is_readable($file)) {
    cli_error('Usage: php restore_without_users.php <backup.mbz> "<full name>" <shortname> [categoryid]');
}
if ($DB->record_exists('course', ['shortname' => $shortname])) {
    cli_error("A course with shortname '$shortname' already exists.");
}
$admin = get_admin();
\core\session\manager::set_user($admin);

$folder = 'restore_' . uniqid();
$path = make_backup_temp_directory($folder);
cli_writeln('Extracting backup...');
get_file_packer('application/vnd.moodle.backup')->extract_to_pathname($file, $path);

$courseid = restore_dbops::create_new_course($fullname, $shortname, $categoryid);
$rc = new restore_controller($folder, $courseid, backup::INTERACTIVE_NO, backup::MODE_GENERAL, $admin->id,
    backup::TARGET_NEW_COURSE);

// Everything that carries personal data stays out.
$off = ['users', 'role_assignments', 'enrol_migratetomanual', 'userscompletion', 'logs', 'grade_histories',
    'comments', 'badges', 'groups', 'competencies', 'contentbankcontent', 'xapistate', 'customfield'];
foreach ($off as $name) {
    if ($rc->get_plan()->setting_exists($name)) {
        $setting = $rc->get_plan()->get_setting($name);
        if ($setting->get_status() == backup_setting::NOT_LOCKED) {
            $setting->set_value(false);
        }
        cli_writeln(sprintf('  %-22s %s', $name, $setting->get_value() ? 'ON (locked)' : 'off'));
    }
}

if (!$rc->execute_precheck()) {
    $results = $rc->get_precheck_results();
    if (!empty($results['errors'])) {
        $rc->destroy();
        delete_course($courseid, false);
        cli_error('Precheck failed: ' . print_r($results['errors'], true));
    }
}
$warnings = $rc->get_precheck_results()['warnings'] ?? [];
foreach ($warnings as $w) {
    cli_writeln('Warning: ' . $w);
}

cli_writeln('Restoring...');
$rc->execute_plan();
$rc->destroy();

$course = get_course($courseid);
$course->fullname = $fullname;
$course->shortname = $shortname;
update_course($course);
cli_writeln("Restored as course id $courseid: {$CFG->wwwroot}/course/view.php?id=$courseid");
