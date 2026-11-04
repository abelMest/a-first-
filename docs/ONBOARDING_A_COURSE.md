# Onboarding a course onto A-FIRST

A-FIRST is not tied to *Data Science for Health*. The assistant keeps sources, index, logs and escalations per course, and the adaptive layer is driven by a per-course config. This guide adds a new course. In the examples, the course is `feb` (Field Epidemiology Basics) with Moodle course id 7.

## 0. Decide and record

- **Owner:** who approves the course's source texts (Prop §3.9: the assistant only answers from signed-off content).
- **Course key:** a short folder name under `courses/`, for example `feb`.
- **Evaluation:** an audit set and bias sample written for this course, signed off by two reviewers (`evaluation/README.md`) before any learner uses the assistant.
- Log the onboarding as a decision (`DECISION_LOG.md`), with the approver.

## 1. Course folder

```
courses/feb/
  taxonomy.json            modules → competencies → prerequisites, recommended microlearning
  course_config.json       generated in step 4, then reviewed
  diagnostics_map.json     optional: which placement questions form each module diagnostic
  evaluation/
    audit_set.csv          same columns as courses/ds4h/evaluation/audit_set.csv
    bias_audit_sample.csv  same columns as the ds4h sample
  sources/                 (optional) approved texts, or keep them in the content owner's store
```

Copy the structure of `courses/ds4h/taxonomy.json`: `framework_id`, `title`, `version`, `course_id`, `course_name`, `total_modules`, `total_competencies` and `modules[]` (each with `id`, `title`, `competencies[]`). Each competency has `id`, `title`, `bloom_level`, `prerequisites` and `recommended_microlearning`. Use a new `framework_id`, for example `A-FIRST-FEB-V1`.

## 2. Competency framework

```bash
python course-setup/taxonomy_to_csv.py --course=feb
```

Then import `courses/feb/competency_framework_A-FIRST-FEB-V1.csv` (Site administration › Competencies › Import competency framework) and link the competencies to the course and its activities (`course-setup/CHECKLIST.md`, steps 3–4).

## 3. Approved sources for the assistant

Export each module's approved text as `.txt`, `.md` or `.html`. Start each title with `Module x.y` so citations and the audit can name the module. Then either upload the texts on the block's **Manage sources** page, or bulk-import them:

```bash
php blocks/afirst_assistant/cli/import_sources.php --courseid=7 --dir=/path/to/feb/sources --dry-run
php blocks/afirst_assistant/cli/import_sources.php --courseid=7 --dir=/path/to/feb/sources
```

Approve the sources only after the content owner has signed them off. Use the web page, or `--approve` when the sign-off is already recorded. A source that yields no passages can't be approved. Add the **Course assistant** block to the course.

## 4. Adaptive rules

Name the course structure so the generator can read it, or fix the draft by hand:

| Role | Detected as |
|---|---|
| Module code | Section name containing `1.2` or `Module 3`, else the section number |
| Content | SCORM, page, book, file, URL, lesson or IMS package in the section |
| Diagnostic | Quiz with ID number `AFIRST-DIAG-…` or "diagnostic" in its name |
| Knowledge check | Quiz with "knowledge check" in its name, else the last other quiz in the section |
| Retake | Quiz with "retake" in its name |
| Microlearning | H5P activity in the section |

```bash
php /opt/afirst/course-setup/generate_course_config.php --courseid=7 --out=/opt/afirst/courses/feb/course_config.json
php /opt/afirst/course-setup/apply_course_config.php --config=/opt/afirst/courses/feb/course_config.json --dry-run
php /opt/afirst/course-setup/apply_course_config.php --config=/opt/afirst/courses/feb/course_config.json
```

Read every WARNING and NOTICE in the output. For example, content without completion tracking can never unlock a knowledge check. Justify your thresholds; the pilot uses ≥ 90% to fast-track and < 80% for remediation. Then verify the rules as test students (`course-setup/CHECKLIST.md`, step 8).

## 5. Audit before learners

```bash
php blocks/afirst_assistant/cli/run_audit.php --courseid=7 --set=/opt/afirst/courses/feb/evaluation/audit_set.csv \
    --out=/var/moodledata/audit/feb-<provider>.csv --provider=<provider>
```

Two graders score the results (`evaluation/fairness_checklist.md`). Tune `minscore` from the `top_score` column. Repeat with `bias_audit_sample.csv`. Go live only against the agreed criteria: ≥ 95% accuracy and 100% traceability (Plan §7).

## 6. Commit

Commit `courses/feb/` (no learner data, no secrets) and rebuild the image, so every node carries the same course data.

## Known limits

- Retrieval is lexical (BM25) over the source language. A question in another language than the sources usually finds no passage and is refused. The ds4h bias sample (French, Portuguese) is expected to show this; see DE_LOG A-07.
- Adaptivity is per module, not per competency (D-09).
