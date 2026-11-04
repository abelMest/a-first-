#!/bin/bash
# A-FIRST container entrypoint. Roles:
#   init  - wait for the database, install Moodle on first run or upgrade it, apply A-FIRST site settings, exit.
#           Run exactly one init per deployment (compose does this); web and cron wait for it.
#   web   - Apache + PHP. Scale freely (sessions are in Redis, files in the shared moodledata volume).
#   cron  - Moodle cron in a loop. Run ONE cron container per site.
#   any other command is executed as given (for example: php admin/cli/purge_caches.php).
set -euo pipefail

ROLE="${1:-web}"
MOODLE=/var/www/html
echo "$ROLE" > /tmp/afirst-role

as_www() { runuser -u www-data -- "$@"; }
moodle_php() { as_www php "$MOODLE/$1" "${@:2}"; }

read_env() {  # NAME -> value of NAME_FILE or NAME
    local file_var="${1}_FILE"
    if [[ -n "${!file_var:-}" && -r "${!file_var}" ]]; then cat "${!file_var}"; else echo "${!1:-}"; fi
}

prepare_dirs() {
    mkdir -p /var/moodledata /var/moodle-localcache
    chown www-data:www-data /var/moodledata /var/moodle-localcache
    chmod 2770 /var/moodledata
}

wait_for_db() {
    local tries=0
    until as_www php -r '
        define("CLI_SCRIPT", true); define("ABORT_AFTER_CONFIG", true);
        require "/var/www/html/config.php";
        $c = @pg_connect(sprintf("host=%s port=%s dbname=%s user=%s password=%s connect_timeout=3",
            $CFG->dbhost, $CFG->dboptions["dbport"] ?: 5432, $CFG->dbname, $CFG->dbuser, $CFG->dbpass));
        exit($c ? 0 : 1);' 2>/dev/null; do
        tries=$((tries + 1))
        if (( tries > 60 )); then echo "Database not reachable after 5 minutes" >&2; exit 1; fi
        sleep 5
    done
}

is_installed() {
    as_www php -r '
        define("CLI_SCRIPT", true); define("ABORT_AFTER_CONFIG", true);
        require "/var/www/html/config.php";
        $c = pg_connect(sprintf("host=%s port=%s dbname=%s user=%s password=%s",
            $CFG->dbhost, $CFG->dboptions["dbport"] ?: 5432, $CFG->dbname, $CFG->dbuser, $CFG->dbpass));
        $r = @pg_query($c, "SELECT 1 FROM " . $CFG->prefix . "config WHERE name = '"'"'version'"'"'");
        exit($r && pg_num_rows($r) === 1 ? 0 : 1);' 2>/dev/null
}

wait_for_install() {
    local tries=0
    until is_installed; do
        tries=$((tries + 1))
        if (( tries > 120 )); then echo "Moodle is not installed after 20 minutes; did the init container fail?" >&2; exit 1; fi
        sleep 10
    done
}

site_settings() {
    # Core features the A-FIRST adaptive layer depends on (course-setup/CHECKLIST.md, step 1).
    moodle_php admin/cli/cfg.php --name=enableavailability --set=1
    moodle_php admin/cli/cfg.php --name=enablecompletion --set=1
    moodle_php admin/cli/cfg.php --component=core_competency --name=enabled --set=1
}

case "$ROLE" in
    init)
        prepare_dirs
        wait_for_db
        if is_installed; then
            echo "Moodle installed: running upgrade."
            moodle_php admin/cli/upgrade.php --non-interactive --allow-unstable
        else
            echo "Fresh database: installing Moodle."
            ADMINPASS="$(read_env MOODLE_ADMIN_PASSWORD)"
            if [[ -z "$ADMINPASS" ]]; then echo "MOODLE_ADMIN_PASSWORD(_FILE) is required for the first install" >&2; exit 1; fi
            moodle_php admin/cli/install_database.php --agree-license \
                --fullname="${MOODLE_SITE_FULLNAME:-A-FIRST}" --shortname="${MOODLE_SITE_SHORTNAME:-afirst}" \
                --adminuser="${MOODLE_ADMIN_USER:-admin}" --adminpass="$ADMINPASS" \
                --adminemail="${MOODLE_ADMIN_EMAIL:-admin@example.invalid}" --supportemail="${MOODLE_ADMIN_EMAIL:-admin@example.invalid}"
        fi
        site_settings
        moodle_php admin/cli/purge_caches.php
        echo "init complete."
        ;;
    web)
        prepare_dirs
        wait_for_db
        wait_for_install
        exec /usr/local/bin/moodle-docker-php-entrypoint apache2-foreground
        ;;
    cron)
        prepare_dirs
        wait_for_db
        wait_for_install
        echo "Cron loop started."
        while true; do
            moodle_php admin/cli/cron.php --keep-alive=240 || echo "cron run failed (exit $?); retrying in 60s" >&2
            sleep 60
        done
        ;;
    *)
        exec "$@"
        ;;
esac
