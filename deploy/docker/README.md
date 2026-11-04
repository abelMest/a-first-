# A-FIRST Docker deployment

One image, `afirst/moodle:<release>`, holds pinned Moodle core (`v5.0.11`), `block_afirst_assistant`, `report_afirst_readiness`, the course-setup tools and the per-course data. The container command selects its role:

| Role | What it does | How many |
|---|---|---|
| `init` | Waits for the database. Installs Moodle on an empty database, or upgrades it. Turns on restrict access, completion and competencies. Then exits. | Exactly one per release; web and cron wait for it to finish successfully |
| `web` | Apache + PHP 8.3 | Any number. Sessions are in Redis and files are in the shared `moodledata` volume |
| `cron` | Moodle cron in a loop | Exactly one per site |

Around it, `compose.yml` runs PostgreSQL 16, Redis 7 and Caddy. Caddy is the load balancer: it round-robins across all web replicas and can terminate TLS.

```
            ┌──────────── Caddy (:8720 / :443) ────────────┐
            │       round robin, health-checked             │
         web-1      web-2      …      web-N        cron (1)
            └────────┬───────────┬───────────────────┘
          PostgreSQL (data)   Redis (sessions)   moodledata volume (files, shared MUC caches)
```

## Quick start (one host)

```bash
cd deploy/docker
cp .env.example .env                      # set MOODLE_WWWROOT to the address learners will use
openssl rand -base64 24 | tr -d '\n' > secrets/db_password.txt
openssl rand -base64 18 | tr -d '\n' > secrets/admin_password.txt
docker compose up -d --build
```

The first start installs Moodle, which takes about 2 minutes. The site is then at `MOODLE_WWWROOT`. Sign in as `admin` with the password in `secrets/admin_password.txt`, then change it.

To scale the web tier, run this. Caddy picks up the new replicas within 10 seconds.

```bash
docker compose up -d --scale web=3
```

## Configuration

All settings are environment variables (`.env`) or Docker secrets (`*_FILE`), read by [`config.php`](config.php). Secrets never go in `.env`, in the image or in git.

| Variable | Meaning |
|---|---|
| `MOODLE_WWWROOT` | Public address. It must match how learners reach the proxy. |
| `AFIRST_SITE_ADDRESS` | `:80` for plain HTTP, or a domain name for automatic HTTPS. With a domain, also set `MOODLE_WWWROOT=https://…` and `MOODLE_SSLPROXY=1`. |
| `MOODLE_DBHOST`, `MOODLE_DBNAME`, `MOODLE_DBUSER`, `MOODLE_DBPASS(_FILE)`, `MOODLE_DBPORT` | Database. Compose points them at its own Postgres. |
| `MOODLE_REDIS_HOST`, `MOODLE_REDIS_PORT`, `MOODLE_REDIS_PASSWORD` | Session store. Without it, sessions fall back to the database. |
| `MOODLE_SMTP_*`, `MOODLE_NOEMAIL` | Mail. Keep `MOODLE_NOEMAIL=1` on test stacks. |
| `MOODLE_REF`, `AFIRST_IMAGE` | Moodle tag to build, and the image name and tag. |

The AI provider is **not** configured by the image. An administrator adds it in Site administration > General > AI providers, with the key typed directly into the form ([`docs/DEPLOYMENT.md`](../../docs/DEPLOYMENT.md) §3). Enable only one `generate_text` provider: core AI falls back through every enabled provider.

## Releases and upgrades

1. Bump `blocks/afirst_assistant/version.php` if the plugin changed, and set `AFIRST_IMAGE=afirst/moodle:<new release>`.
2. Back up (see below).
3. Run `docker compose up -d --build`. `init` runs `admin/cli/upgrade.php` before any new web or cron container starts.
4. Roll back by setting the previous `AFIRST_IMAGE` and restoring the database backup. A Moodle schema upgrade cannot be undone without that backup.

Code is read-only inside the containers. Plugins come only from the image (`$CFG->disableupdateautodeploy`).

## Backups

```bash
docker compose exec -T db pg_dump -U moodle -Fc moodle > afirst_db_$(date +%Y%m%d).dump
docker run --rm -v afirst-prod_moodledata:/d -v "$PWD":/b alpine tar czf /b/afirst_moodledata_$(date +%Y%m%d).tgz -C /d .
```

Keep backups on Africa CDC infrastructure only (Prop §3.6).

## Running the A-FIRST tools

Inside any web container (`docker compose exec web bash`), as `www-data`:

```bash
runuser -u www-data -- php /opt/afirst/course-setup/generate_course_config.php --courseid=7
runuser -u www-data -- php /opt/afirst/course-setup/apply_course_config.php --config=/opt/afirst/courses/<course>/course_config.json --dry-run
runuser -u www-data -- php blocks/afirst_assistant/cli/import_sources.php --courseid=7 --dir=/path/to/approved/texts
runuser -u www-data -- php blocks/afirst_assistant/cli/run_audit.php --courseid=7 --set=/opt/afirst/courses/<course>/evaluation/audit_set.csv --out=/var/moodledata/audit/run.csv --provider=<label>
```

See [`docs/ONBOARDING_A_COURSE.md`](../../docs/ONBOARDING_A_COURSE.md) for the full course onboarding sequence.

## Scaling beyond one host

The compose file targets one Docker host. To spread across hosts (Docker Swarm, Kubernetes, or several VMs behind an Africa CDC load balancer), keep the same image and roles and change the shared services:

| Concern | One host (compose) | Several hosts |
|---|---|---|
| Database | `db` container | Managed or dedicated PostgreSQL, with backups and a replica |
| Sessions | `redis` container | A shared Redis that every web node reaches |
| `moodledata` | Named volume | Shared file system (NFS or similar) mounted at `/var/moodledata` on every web and cron node |
| Local caches | `/var/moodle-localcache`, per container | The same. Never share it |
| Cron | 1 container | Still exactly 1 |
| `init` | Runs on `up` | One job per release, before web nodes roll |

The assistant's per-course retrieval index is a Moodle application cache, stored in `moodledata`, so all nodes share it. For heavy load, map the `block_afirst_assistant/courseindex` and core application caches to a Redis cache store (Site administration > Plugins > Caching).

## What has been tested (2026-11-04, Docker Desktop on Windows)

- Fresh install, then restart and upgrade, through `init`.
- 2 web replicas behind Caddy. Requests alternate between replicas. A logged-in session holds 8 of 8 requests across both replicas, after a fresh install and after a cold restart of both. One earlier run lost the session; it was not reproduced in three later runs.
- Cron loop completes runs. Site settings applied. Redis session handler in use.
- `generate_course_config.php` and `apply_course_config.php --dry-run` on a second course, `import_sources.php` (including an idempotent re-run), and `run_audit.php` on the audit and bias sets (no provider configured, so retrieval and refusal only).

- READINESS: the pilot scenario built into a course with `build_scenario.php`, played to the end in a browser as a test learner, and the report and CSV checked against a hand calculation. Plugin upgrade (new log column) applied through `init`.

**Not yet tested:** TLS with a real domain, multi-host shared storage, load testing, a live AI provider, and migrating the existing production site (`10.215.70.14`) into this stack.
