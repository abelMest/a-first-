# ADAPTIVE setup checklist: diagnostics, review units, restrict access, competencies

This checklist sets up the ADAPTIVE layer (Stage 2, [`docs/STAGE_GATES.md`](../docs/STAGE_GATES.md)) on a Moodle 5.0 course with core features only, plus four repeatable CLI scripts. It was run end to end on the local docker Moodle (`dev/`) on 2026-11-06, on *Data Science & Analytics for Public Health* restored without user data (dev course 4). Production (`10.215.70.14`) is changed only with the project lead's go-ahead, after Gate 1.

## What is in this folder, and what is per course

The tools work for any course. Each course's data lives in `courses/<course>/`; the pilot course is `courses/ds4h/`. To add another course, follow [`docs/ONBOARDING_A_COURSE.md`](../docs/ONBOARDING_A_COURSE.md).

| File | Purpose |
|---|---|
| `build_diagnostics.php` | Builds the module diagnostics from `diagnostics_map.json` (copies the Placement Test's settings, adds the listed bank questions). Idempotent, `--dry-run`. |
| `microlearning.php` | Pure functions that extract a review unit (objectives, key points, key terms) word for word from course text and write the H5P content. |
| `build_microlearning.php` | Builds one hidden H5P review unit and one hidden retake per sub-module, placed after its Knowledge Check, and writes the `.h5p` files and `REVIEW.md`. `--show` / `--hide` after owner review. |
| `import_competencies.php` | Imports the competency CSV (core `tool_lpimportcsv`) and links module competencies to the course, Knowledge Checks and retakes. |
| `rules.php` | The restrict-access rules as pure functions, with no Moodle dependency. |
| `apply_course_config.php` | Writes the restrict-access rules from `course_config.json`. Idempotent, `--dry-run`, `--replace`. |
| `generate_course_config.php` | Drafts a `course_config.json` from a course whose sections are one sub-module each. Courses with several sub-modules per section (like ds4h) need the config written by hand. |
| `taxonomy_to_csv.py` | Turns `courses/<course>/taxonomy.json` into the competency CSV and the prerequisite file. |
| `selfcheck.php` | Plain-PHP tests for `rules.php` and `microlearning.php` (41 checks). |

| Per-course file (`courses/<course>/`) | Purpose |
|---|---|
| `diagnostics_map.json` | Each module diagnostic: ID number, name, sub-modules and question bank entry ids. |
| `course_config.json` | Sub-module → diagnostic, Knowledge Check, content, review unit and retake cmids, and the thresholds. |
| `competency_framework_<id>.csv` | Generated competency import (ds4h: 1 framework, 9 module parents `MOD-x.y`, 35 competencies `COMP-x.y.z`). |
| `microlearning/` | The built `.h5p` review units and `REVIEW.md`, the content owner's review sheet. |
| `taxonomy.json`, `competency_prerequisites.json`, `evaluation/` | Source taxonomy, prerequisite edges (not imported), audit and bias sets. |

The rules for each sub-module (thresholds in `course_config.json`):

- **Fast-track:** the Knowledge Check opens when the learner has completed the sub-module's SCORM **or** scored ≥ 90% on the module's diagnostic (one diagnostic per module, D-23).
- **Remediation:** the "Review: Module X.Y" unit opens when the Knowledge Check grade is **below 70%**, the course pass mark (D-22). It is invisible to everyone else.
- **Retake:** "Module X.Y Knowledge Check (retake)" opens when the Knowledge Check is below 70% **and** the review unit is complete (viewed).

Gating is per sub-module, not per competency, because individual quiz questions don't reach the gradebook.

> **Behaviour change to agree before production:** Knowledge Checks are open to everyone today. After the rules are applied, a learner who hasn't completed the SCORM and hasn't fast-tracked sees the Knowledge Check as locked. This enforces what the quiz text already says ("pass it (70% or higher) to continue").

## Steps

All scripts run from the Moodle root as the web-server user. On the dev stack, copy `course-setup/` and `courses/` into the container (e.g. `/opt/afirst`) and use `afirst_exec`. Every script refuses a site that is not `localhost`, `*.localhost` or `*.test` unless `--allow-host` is given; pass it only on production, with the go-ahead.

### 1. Site features (site admin)
Site administration › Advanced features: **Enable restricted access**, **Enable completion tracking**, **Competencies**. Plugins › Availability restrictions: **Grade** and **Activity completion** enabled. In the course settings, **Completion tracking = Yes**. Then fetch the H5P content types: run the scheduled task `\core\task\h5p_get_content_types_task` (needs internet access to h5p.org).

### 2. Diagnostics
```bash
php /opt/afirst/course-setup/build_diagnostics.php --courseid=4 --map=/opt/afirst/courses/ds4h/diagnostics_map.json --dry-run
php /opt/afirst/course-setup/build_diagnostics.php --courseid=4 --map=/opt/afirst/courses/ds4h/diagnostics_map.json
```
Expect: the Placement Test gets ID number `AFIRST-DIAG-M1`; three "Module N diagnostic (optional)" quizzes are created with 10 questions each, no completion requirement. A rerun prints "unchanged" four times. Write the cmids into `course_config.json` (`diagnostic_cmid`; sub-modules of one module share it).

### 3. Review units and retakes
```bash
php /opt/afirst/course-setup/build_microlearning.php --courseid=4 --outdir=/opt/afirst/out --dry-run
php /opt/afirst/course-setup/build_microlearning.php --courseid=4 --outdir=/opt/afirst/out
```
Expect: per sub-module, a hidden "Review: Module X.Y" (H5P, completion on view, attempt tracking, no share buttons) and a hidden retake, in the order Knowledge Check → Review → Retake. Copy `out/` to `courses/<course>/microlearning/` and send `REVIEW.md` to the content owner. Write the cmids into `course_config.json` (`microlearning_cmids`, `retake_cmid`).

After the owner approves (Gate 2.3), show the approved units: `--show --codes=1.1,1.2,...`. A rejected unit stays hidden; replace its package (Nolej or the H5P editor) or rebuild with `--update-package`.

### 4. Restrict access
```bash
php /opt/afirst/course-setup/selfcheck.php
php /opt/afirst/course-setup/apply_course_config.php --config=/opt/afirst/courses/ds4h/course_config.json --dry-run
php /opt/afirst/course-setup/apply_course_config.php --config=/opt/afirst/courses/ds4h/course_config.json
```
Read the dry run: each activity is CREATE, UPDATE, UNCHANGED or CONFLICT (hand-made restrictions, kept unless `--replace`). A rerun must report only UNCHANGED. Take a course backup first on production.

### 5. Competencies
```bash
python taxonomy_to_csv.py --course=ds4h
php /opt/afirst/course-setup/import_competencies.php --csv=/opt/afirst/courses/ds4h/competency_framework_A-FIRST-DS4H-V1.csv --config=/opt/afirst/courses/ds4h/course_config.json
```
Expect: 44 competencies imported (skipped if the framework exists), the 9 module competencies linked to the course and to each Knowledge Check and retake with "Attach evidence" on completion, so ratings stay a teacher decision.

### 6. Verify as test learners (never real learners)
On dev, `dev/simulate_attempt.php` makes test learners and scripted attempts through the core quiz API, and `--report` lists what a learner can open.

| Test learner | Do | Expect | Dev result (2026-11-06) |
|---|---|---|---|
| A (strong) | 10/10 on the Module 2 diagnostic | Knowledge Checks 2.1 and 2.2 open without the SCORM | ✔ student1 |
| B (gap) | Below 70% on Knowledge Check 1.1 (units shown) | Review 1.1 appears; the retake opens after the review is viewed | ✔ student2 (20%); unit played in the browser |
| C (new) | Nothing yet | Every Knowledge Check locked with its "any of" conditions; reviews invisible | ✔ student3 |
| D (pass) | ≥ 70% on a Knowledge Check | No review or retake | Covered by `selfcheck.php` threshold checks |

Also check Reports › Competency breakdown after completions. Grades may need cron (`php admin/cli/cron.php`) before availability updates.

## Known limits

- The ds4h cmids are those of the dev restore. A backup/restore renumbers them: rebuild with the scripts on the target site (they find activities by ID number and name) and update `course_config.json`.
- Review units are rule-based extracts: depth varies (0–9 key terms), and some objectives copied from the course look misplaced (Module 4.1 lists "prepare abstracts, posters, or slides"). The owner review is the quality gate.
- Retakes reuse the Knowledge Check's questions.
- Moodle core logs each step of the backup/restore behind `duplicate_module()` to the CLI; that output is harmless.
- The final "Quiz" in *Final Course Activities & Assessment* holds questions from a different course (preparedness). It is untouched and flagged for the content owner (`docs/PLAN_VS_ACTUAL.md`).
