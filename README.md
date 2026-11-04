# A-FIRST: AI-Enabled Frontline Integrated Reference and Skills Training

The three A-FIRST layers for the Africa CDC Moodle 5.0 environment: BASIC (a source-grounded AI assistant), ADAPTIVE (diagnostics, fast-track and remediation) and READINESS (scenario-based readiness assessment). It is piloted on the *Data Science for Health* course (course id 2). This is the capstone project of the Africa Epidemic Service Public Health Informatics Fellowship (June 2026 to May 2027).

**This repository is the single source of truth** for the code, plans, decisions and Developmental Evaluation record.

**Sequence:** the layers are released one at a time: **BASIC → ADAPTIVE → READINESS** (D-19). Each starts only after the previous one passes its gate; see [`docs/STAGE_GATES.md`](docs/STAGE_GATES.md). Current stage: **BASIC** (Gate 1 due end of Sprint 6). ADAPTIVE tooling and READINESS v1 are in the repository but not deployed.

## Layout

| Path | What it is |
|---|---|
| [`blocks/afirst_assistant/`](blocks/afirst_assistant/) | BASIC layer: the grounded-chat Moodle block. It gives cited answers, refuses out-of-scope questions, shows the AI disclaimer, logs interactions and has an escalation queue. Works in any course: sources, index and logs are per course. CLI tools: `cli/import_sources.php`, `cli/run_audit.php`. |
| [`report/afirst_readiness/`](report/afirst_readiness/) | READINESS layer: builds outbreak scenarios as Lesson activities and reports each learner as ready for independent work, ready with supervision, or not yet. See [`docs/READINESS_SPEC.md`](docs/READINESS_SPEC.md). |
| [`course-setup/`](course-setup/) | ADAPTIVE layer tooling on core Moodle, for any course: competency framework import, config generator, restrict-access rules. Start with [`CHECKLIST.md`](course-setup/CHECKLIST.md). |
| [`courses/`](courses/) | One folder per course: taxonomy, course config, diagnostics map, evaluation sets, microlearning, readiness scenarios. The pilot is [`courses/ds4h/`](courses/ds4h/). Adding a course: [`docs/ONBOARDING_A_COURSE.md`](docs/ONBOARDING_A_COURSE.md). |
| [`deploy/docker/`](deploy/docker/) | Production Docker stack: one image for web, cron and init; Postgres, Redis and a load balancer. See its README. |
| [`evaluation/`](evaluation/) | Evaluation method and tools for any course: set formats, fairness checklist, `check_sets.py`. The pilot sets are in `courses/ds4h/evaluation/`. |
| [`dev/`](dev/) | Local Docker Moodle 5.0 for development. It never talks to production. |
| [`docs/`](docs/) | Plans, decisions, deployment, Developmental Evaluation and the Phase 2 archive. See [`docs/README.md`](docs/README.md). |

## Where things stand

See [`docs/PLAN_VS_ACTUAL.md`](docs/PLAN_VS_ACTUAL.md) for status against the approved plan, and [`docs/DECISION_LOG.md`](docs/DECISION_LOG.md) for every change of course.

## Rules for this repository

- **No secrets.** Credentials go in `dev/.env` (ignored) or environment variables (D-02).
- **No participant data.** Learner logs, exports and identities stay on Africa CDC infrastructure (Prop §3.6).
- **Label evidence.** Results are `synthetic`, `dev`, `beta` or `pilot`. Only `pilot` results are study findings (see the DE framework, §4).
- **Log decisions.** Any change in scope, technology or risk gets a `D-nn` entry with date and approver.
- **Production is changed only with the fellow's explicit go-ahead,** following [`docs/DEPLOYMENT.md`](docs/DEPLOYMENT.md).
