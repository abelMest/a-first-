# A-FIRST Moodle 5.0 image: Moodle core + block_afirst_assistant + course-setup and evaluation tooling.
# Build from the repository root:
#   docker build -f deploy/docker/Dockerfile -t afirst/moodle:0.2.0 .
# One image runs every role (init, web, cron); the role is the container command. See deploy/docker/README.md.

ARG MOODLE_REF=v5.0.11
ARG PHP_IMAGE=moodlehq/moodle-php-apache:8.3

# Stage 1: fetch a pinned Moodle release (tag, not a moving branch, so every node runs identical code).
FROM ${PHP_IMAGE} AS moodle
ARG MOODLE_REF
RUN git clone --depth 1 --branch "${MOODLE_REF}" https://github.com/moodle/moodle.git /moodle \
 && rm -rf /moodle/.git

# Stage 2: runtime.
FROM ${PHP_IMAGE}
ARG MOODLE_REF
LABEL org.opencontainers.image.title="A-FIRST Moodle" \
      org.opencontainers.image.description="Moodle with the A-FIRST source-grounded course assistant" \
      afirst.moodle.ref="${MOODLE_REF}"

# Code is owned by root and read-only to the web server: plugins are installed by rebuilding the image, never from the UI.
COPY --from=moodle /moodle /var/www/html
COPY blocks/afirst_assistant /var/www/html/blocks/afirst_assistant
COPY deploy/docker/config.php /var/www/html/config.php
COPY course-setup /opt/afirst/course-setup
COPY courses /opt/afirst/courses
COPY evaluation /opt/afirst/evaluation
COPY deploy/docker/php-production.ini /usr/local/etc/php/conf.d/90-afirst-production.ini
COPY deploy/docker/entrypoint.sh /usr/local/bin/afirst-entrypoint

RUN chown -R root:root /var/www/html /opt/afirst \
 && chmod -R a+rX /var/www/html /opt/afirst \
 && chmod 0755 /usr/local/bin/afirst-entrypoint \
 && mkdir -p /var/moodledata /var/moodle-localcache \
 && chown www-data:www-data /var/moodledata /var/moodle-localcache

VOLUME ["/var/moodledata"]
EXPOSE 80
HEALTHCHECK --interval=30s --timeout=10s --start-period=120s --retries=3 \
  CMD test "$(cat /tmp/afirst-role 2>/dev/null)" != "web" || curl -fsS -o /dev/null http://127.0.0.1/login/index.php || exit 1

ENTRYPOINT ["/usr/local/bin/afirst-entrypoint"]
CMD ["web"]
