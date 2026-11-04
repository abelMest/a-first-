# Course 2 setup checklist: competencies, diagnostics, restrict access

This checklist covers the ADAPTIVE layer for *Data Science for Health* (course id 2) using Moodle 5.0 core features only. Do it first on the local docker Moodle (`dev/`). Production (`10.215.70.14`) is changed only with the project lead's go-ahead at deployment time (Sprint 8).

## What is in this folder, and what is per course

The tools here work for any course. Each course's data lives in its own folder, `courses/<course>/`. The pilot course is `courses/ds4h/`. To add another course, follow [`docs/ONBOARDING_A_COURSE.md`](../docs/ONBOARDING_A_COURSE.md).

| File | Purpose |
|---|---|
| `taxonomy_to_csv.py` | Turns `courses/<course>/taxonomy.json` into the import CSV and the prerequisite file (`--course=<course>`). |
| `generate_course_config.php` | Reads a live course and writes a draft `course_config.json` with the cmids filled in, for review. |
| `rules.php` | The rules as pure functions, with no Moodle dependency. |
| `apply_course_config.php` | Moodle CLI script that writes the restrict-access rules. Supports `--dry-run` and is idempotent. |
| `selfcheck.php` | Plain-PHP tests for `rules.php`. |

| Per-course file (`courses/<course>/`) | Purpose |
|---|---|
| `taxonomy.json` | Modules, competencies, prerequisites and recommended microlearning. |
| `competency_framework_<framework_id>.csv` | Generated output, ready for **Import competency framework**. For ds4h: 1 framework row, 9 module parents (`MOD-1.1` to `MOD-4.3`) and 35 competencies (`COMP-x.y.z`). |
| `competency_prerequisites.json` | Generated output (ds4h: 40 directed prerequisite edges). Not imported, because Moodle cross-references are symmetric. |
| `diagnostics_map.json` | Which placement-test questions go into each module diagnostic. |
| `course_config.json` | Module → diagnostic, knowledge check, content, microlearning and retake cmids. Read by the script. |
| `evaluation/` | The course's audit set and bias sample (format in `evaluation/README.md`). |

The rules applied to each module:

- **Fast-track:** the module knowledge check opens when the learner has completed the module content (SCORM) **or** scored ≥ 90% on the module diagnostic.
- **Remediation:** each H5P microlearning unit for the module opens only when the knowledge check grade is **below 80%**. It is hidden until then.
- **Retake (optional):** a separate retake quiz opens when the knowledge check is below 80% **and** all of the module's microlearning units are complete.

Thresholds are set in `course_config.json`. Gating is per module, not per competency, because individual quiz questions don't reach the gradebook.

> **Behaviour change to agree before production:** knowledge checks are open to everyone today. After the script runs, a learner who hasn't completed the module's SCORM package and hasn't fast-tracked will see the knowledge check as locked. The existing quiz text says "70% to access the next module", but nothing enforces it. The new remediation threshold is 80%. Update the quiz intro text to match.

## Steps

Each step lists who does it and what it needs.

### 1. Enable the site features
**Who:** site admin.

Go to Site administration › General › Advanced features and turn on:
- **Enable restricted access** (`enableavailability`)
- **Enable completion tracking** (`enablecompletion`)
- **Competencies** (`core_competency | enabled`, under Site administration › Competencies › Competencies settings)

Then go to Plugins › Availability restrictions › Manage restrictions and check that **Grade** and **Activity completion** are enabled.

### 2. Course settings
**Who:** teacher or manager on course 2.

In Course › Settings › Completion tracking, set **Enable completion tracking = Yes**.

Each SCORM package (cms 11, 13, 15, 17, 21, 23, 27, 29, 31) and each new H5P activity needs completion conditions. The local dump shows SCORMs already set to automatic completion, but confirm it.

### 3. Import the competency framework
**Who:** site admin, or a user with `moodle/competency:competencymanage` at system level.

1. Run:
   ```bash
   python taxonomy_to_csv.py --course=ds4h
   ```
   For ds4h it must report 9 module parents and 35 child competencies.
2. Go to Site administration › Competencies › **Import competency framework**. Upload `courses/ds4h/competency_framework_A-FIRST-DS4H-V1.csv` with separator **comma** and encoding **UTF-8**.
3. On the column-mapping screen, every header should map to itself. Confirm.
4. Check the result:
   - The framework `A-FIRST-DS4H-V1` uses the "Default competence scale" (Not yet competent / Competent). If no matching scale exists, the importer creates one.
   - The taxonomies are *domain* at level 1 and *competency* at level 2.
   - There are 9 domains, each with its competencies.

### 4. Link competencies to the course and activities
**Who:** teacher with `moodle/competency:coursecompetencymanage`.

1. In Course › More › **Competencies**, add all 35 competencies (or the 9 module parents plus children).
2. In each activity's settings › Competencies, link the module's competencies to:
   - the **knowledge check**
   - the new **diagnostic**
   - each **H5P microlearning** unit

   Each competency's `recommended_microlearning` in the taxonomy names its unit. Set "Upon activity completion" to **Attach evidence**, so ratings stay a teacher decision.
3. Optional: use `competency_prerequisites.json` to order learning-plan templates. Moodle doesn't enforce prerequisites.

### 5. Create the 9 module diagnostics
**Who:** teacher with question-bank access to course 2.

Do not edit the Placement Test (quiz 1, cm 10). It keeps all 27 questions for the baseline and endline scores.

For each module in `diagnostics_map.json`:
1. Add a Quiz in the module's course section, before the SCORM. Name it `Module X.Y Diagnostic` and set ID number `AFIRST-DIAG-X.Y`.
   - Grade to pass: 90%
   - Attempts allowed: 1
   - Grading method: highest grade
   - Completion: "receive a grade"
2. Add the three questions listed under `questions` from the existing course question bank. They are the same question bank entries the placement test uses: search by topic or name, since the UI shows names, not entry ids. Give each question a mark of 1.
3. Write down the new cmid (shown as `?id=` in the URL) and put it in `course_config.json` as `diagnostic_cmid`.

### 6. Add the microlearning and (optionally) retakes
**Who:** teacher.

1. Add each module's H5P activity (from the content bank) with completion set to "view" or "receive a grade". Put its cmid in `microlearning_cmids`.
2. Choose a retake option:
   - **(a)** Allow 2 attempts on the knowledge check. This is the simplest, but the second attempt is not gated behind the microlearning.
   - **(b)** Add a separate "Module X.Y Knowledge Check (retake)" quiz using the same questions, and put its cmid in `retake_cmid`. The script then gates it.

### 7. Run the script
**Who:** server admin with shell access to the Moodle host, running as the web-server user.

Copy this folder to the server, outside the web root. From the Moodle root:

```bash
sudo -u www-data php /path/to/course-setup/selfcheck.php
```

```bash
sudo -u www-data php /path/to/course-setup/apply_course_config.php --config=/path/to/courses/ds4h/course_config.json --dry-run
```

Read the dry run:
- Every activity is listed as CREATE, UPDATE, UNCHANGED or CONFLICT, with the planned availability JSON.
- WARNING and NOTICE lines name missing settings or activities that aren't created yet.
- CONFLICT means the activity already has hand-made restrictions. They are kept unless you add `--replace`.

Then apply:

```bash
sudo -u www-data php /path/to/course-setup/apply_course_config.php --config=/path/to/courses/ds4h/course_config.json
```

Running it again should report only UNCHANGED. Changing a threshold and re-running updates the rules the script wrote, in place.

Take a course backup before running it on production.

### 8. Verify as test students
**Who:** teacher with "Log in as", or test accounts. Use local or beta test users only, never real learners.

| Test student | Do | Expect |
|---|---|---|
| A (strong) | Score 3/3 on the Module 1.1 diagnostic | Knowledge check 1.1 opens without the SCORM. H5P 1.1 stays hidden. |
| B (normal) | Score 1/3 on the diagnostic, complete the SCORM | Knowledge check opens after the SCORM is complete. |
| C (gap) | As B, then score < 80% on the knowledge check | H5P 1.1 appears. The retake (if any) opens after the H5P is complete. |
| D (pass) | As B, then score ≥ 80% on the knowledge check | No H5P or retake shown. |

Also check that the activity's **Restrictions** text shows on the course page for knowledge checks, and that Reports › Competency breakdown shows evidence after completions. Grades can take a moment to reach the gradebook; if needed, run cron, or `php admin/cli/cron.php` locally.

## Known limits and what was not tested

- `apply_course_config.php` has passed `php -l` (PHP 8.3), and the rule logic has passed `selfcheck.php` (28 checks: rule shapes, threshold edges, skipping on missing activities, idempotency, conflict and `--replace`). The Moodle-facing parts haven't been run against a Moodle instance yet:
  - the grade-item lookup
  - `core_availability\tree` validation
  - the write, the cache rebuild and the event
  - the enabled-feature checks

  Run it first on the local docker Moodle (plan › Verification step 2).
- The CSV column layout was checked against the `MOODLE_500_STABLE` source of `tool_lpimportcsv`: `framework_importer::read_mapping_data()` and the language strings. It hasn't been imported into a real site yet.
- The ds4h cmids in `course_config.json` and `diagnostics_map.json` come from the local dump `afirst_db_phase2_evaluated.sql.gz`. A backup/restore into the dev site renumbers them, so update the config there.
- The availability is written with the DML API (`$DB->set_field` on `course_modules.availability`, then `rebuild_course_cache`), the same as core's `\core_availability\info::update_dependency_id_across_course()`. Moodle has no `set_coursemodule_availability()`; the `set_coursemodule_*` helpers cover visibility, ID number and the like, not availability. No raw SQL is used.
