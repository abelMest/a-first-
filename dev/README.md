# Local development Moodle

A throwaway Moodle 5.0 in Docker for building and testing `block_afirst_assistant`. It never talks to the production server.

## First-time setup (Git Bash)
```bash
cd dev
git clone --depth 1 https://github.com/moodlehq/moodle-docker.git moodle-docker
git clone --depth 1 --branch MOODLE_500_STABLE https://github.com/moodle/moodle.git moodle
cp moodle-docker/config.docker-template.php moodle/config.php
. ./env.sh
afirst_up
afirst_exec php admin/cli/install_database.php --agree-license --fullname="A-FIRST Dev" --shortname=afirstdev \
    --adminpass="<from .env>" --adminemail=admin@example.com
```
The site is at http://localhost:8710 (ports 8000 and 8080–8092 are used by other projects on this machine). Local admin credentials are in `dev/.env`, which is not committed.

## Code lives in a Docker volume
`local.yml` serves Moodle from a Docker volume; the Windows bind mount was too slow (an upgrade sat in disk wait for 30+ minutes). Start with `afirst_up` instead of `$MDC up -d`; it seeds the volume on first run and copies the plugin in.

## Test course with a local model
```bash
ollama pull llama3.2:1b      # the 3B model needs more free RAM than this machine has with Docker running
docker cp dev_allow_ollama.php afirst-webserver-1:/tmp/ && afirst_exec php /tmp/dev_allow_ollama.php   # dev only: lets Moodle reach host.docker.internal:11434
docker cp seed_dev_course.php afirst-webserver-1:/tmp/   # plus the topic_*_clean.txt files to use as sources
afirst_exec php /tmp/seed_dev_course.php "$AFIRST_DEV_STUDENT_PASS" "$AFIRST_DEV_TEACHER_PASS" /tmp/topic_1_2_clean.txt
```
Never apply `dev_allow_ollama.php` to production: it unblocks private network ranges for outbound requests.

## Everyday commands
```bash
. ./env.sh
afirst_sync                                              # copy the plugin into the Moodle tree
afirst_exec php admin/cli/upgrade.php --non-interactive  # after changing version.php or db/
afirst_exec php admin/cli/purge_caches.php
afirst_exec php admin/tool/phpunit/cli/init.php          # once
afirst_exec vendor/bin/phpunit --testsuite block_afirst_assistant_testsuite
(cd moodle && npx grunt amd --root=blocks/afirst_assistant)  # then copy amd/build back into blocks/
$MDC down                                                # stop
```

Moodle's CLI is slow on Windows bind mounts; a first install or upgrade can take 20–30 minutes.
