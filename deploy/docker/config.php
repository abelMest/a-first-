<?php
// A-FIRST container configuration. Every value comes from the environment, so one image serves any site and any
// number of web nodes. NAME_FILE variants (Docker secrets) take precedence over NAME. See deploy/docker/README.md.

unset($CFG);
global $CFG;
$CFG = new stdClass();

/**
 * Read a setting from NAME_FILE (a mounted secret) or NAME.
 *
 * @param string $name
 * @param string|null $default
 * @return string|null
 */
function afirst_env(string $name, ?string $default = null): ?string {
    $file = getenv($name . '_FILE');
    if ($file !== false && $file !== '' && is_readable($file)) {
        return trim((string)file_get_contents($file));
    }
    $value = getenv($name);
    return ($value === false || $value === '') ? $default : $value;
}

$CFG->dbtype    = afirst_env('MOODLE_DBTYPE', 'pgsql');
$CFG->dblibrary = 'native';
$CFG->dbhost    = afirst_env('MOODLE_DBHOST', 'db');
$CFG->dbname    = afirst_env('MOODLE_DBNAME', 'moodle');
$CFG->dbuser    = afirst_env('MOODLE_DBUSER', 'moodle');
$CFG->dbpass    = afirst_env('MOODLE_DBPASS', '');
$CFG->prefix    = afirst_env('MOODLE_DBPREFIX', 'mdl_');
$CFG->dboptions = [
    'dbpersist' => 0,
    'dbport' => afirst_env('MOODLE_DBPORT', ''),
    'dbsocket' => '',
];

$CFG->wwwroot = rtrim((string)afirst_env('MOODLE_WWWROOT', 'http://localhost'), '/');
$CFG->admin = 'admin';
$CFG->directorypermissions = 02770;

// Shared between all web and cron nodes (a volume on one host; NFS or similar across hosts).
$CFG->dataroot = '/var/moodledata';
// Per node: Moodle's local caches must NOT be shared.
$CFG->localcachedir = '/var/moodle-localcache';

// TLS terminated at the proxy (Caddy or an Africa CDC load balancer).
if (afirst_env('MOODLE_SSLPROXY') === '1') {
    $CFG->sslproxy = true;
}
if (afirst_env('MOODLE_REVERSEPROXY') === '1') {
    $CFG->reverseproxy = true;
}

// Sessions in Redis: any web node can serve any request, so the web service scales horizontally.
if ($redishost = afirst_env('MOODLE_REDIS_HOST')) {
    $CFG->session_handler_class = '\core\session\redis';
    $CFG->session_redis_host = $redishost;
    $CFG->session_redis_port = (int)afirst_env('MOODLE_REDIS_PORT', '6379');
    $CFG->session_redis_auth = afirst_env('MOODLE_REDIS_PASSWORD', '');
    $CFG->session_redis_prefix = 'afirst_sess_';
    $CFG->session_redis_acquire_lock_timeout = 120;
    $CFG->session_redis_lock_expire = 7200;
}

// The image is immutable: no code changes from the web UI.
$CFG->disableupdateautodeploy = true;
$CFG->disableupdatenotifications = true;
$CFG->preventexecpath = true;
$CFG->pathtophp = '/usr/local/bin/php';

// Outgoing mail.
if ($smtp = afirst_env('MOODLE_SMTP_HOSTS')) {
    $CFG->smtphosts = $smtp;
    $CFG->smtpsecure = afirst_env('MOODLE_SMTP_SECURE', '');
    $CFG->smtpuser = afirst_env('MOODLE_SMTP_USER', '');
    $CFG->smtppass = afirst_env('MOODLE_SMTP_PASS', '');
    $CFG->noreplyaddress = afirst_env('MOODLE_NOREPLY', 'noreply@' . parse_url($CFG->wwwroot, PHP_URL_HOST));
}
if (afirst_env('MOODLE_NOEMAIL') === '1') {
    $CFG->noemailever = true;
}

if (afirst_env('MOODLE_DEBUG') === '1') {
    $CFG->debug = E_ALL;
    $CFG->debugdisplay = 1;
}

require_once(__DIR__ . '/lib/setup.php');
