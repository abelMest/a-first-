# Source this before running moodle-docker commands:  . dev/env.sh
# Local Moodle 5.0 for development and tests only; nothing here touches the production server.
DEV_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd -W)"
export MOODLE_DOCKER_WWWROOT="$DEV_DIR/moodle"
export MOODLE_DOCKER_DB=pgsql
export MOODLE_DOCKER_PHP_VERSION=8.3
export MOODLE_DOCKER_WEB_PORT=127.0.0.1:8710
export COMPOSE_PROJECT_NAME=afirst
# Container-side paths need MSYS_NO_PATHCONV=1 on the exec command only; the compose wrapper needs conversion on.
MDC="$DEV_DIR/moodle-docker/bin/moodle-docker-compose"
WEB=afirst-webserver-1

# Run a command inside the web container, e.g.  afirst_exec php admin/cli/purge_caches.php
afirst_exec() {
    MSYS_NO_PATHCONV=1 $MDC exec -T webserver "$@"
}

# Start the stack with Moodle served from a Docker volume (see local.yml), seeding the volume on first run.
# The Windows bind mount is too slow for Moodle's CLI (an upgrade sat in disk wait for 30+ minutes).
afirst_up() {
    cp "$DEV_DIR/local.yml" "$DEV_DIR/moodle-docker/local.yml"
    $MDC up -d
    if ! afirst_exec test -f /var/www/html/version.php; then
        echo "Seeding the Moodle code volume (one-off)..."
        (cd "$DEV_DIR/moodle" && tar --exclude=./node_modules --exclude=./.git -cf - .) |
            MSYS_NO_PATHCONV=1 docker exec -i $WEB tar -xf - -C /var/www/html
    fi
    afirst_sync
}

# Copy the plugin (source of truth: ../blocks/afirst_assistant) into the running Moodle.
afirst_sync() {
    afirst_exec rm -rf /var/www/html/blocks/afirst_assistant
    (cd "$DEV_DIR/../blocks" && tar -cf - afirst_assistant) |
        MSYS_NO_PATHCONV=1 docker exec -i $WEB tar -xf - -C /var/www/html/blocks
    afirst_exec chown -R www-data:www-data /var/www/html/blocks/afirst_assistant
}
