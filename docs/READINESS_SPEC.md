# READINESS layer: specification and first version

READINESS is the third A-FIRST strategy: *prove performance, practise in realistic scenarios, prove you are ready* (Project Plan §2.2). The approved plan kept it out of the fellowship, with only a handover specification (WP §13). On 2026-11-04 the fellow asked for a working first version (decision D-18). This document is both:

- the WP §13 handover specification;
- the description of what v1 implements.

> **Status: v1, dev-tested.** Scenarios are drafts until a subject-matter expert signs them off. Readiness thresholds are starting values to be validated with real learners (DE question DQ8). Nothing here is a certification.

## 1. Principles

1. **Authored, not generated.** Scenario content, options, scores and feedback are written by people and approved by the content owner, like every A-FIRST source (Prop §3.9). The AI does not invent the scenario or grade the learner. During a scenario the learner may use the course assistant, as they would use references at work.
2. **Built on core Moodle.** The simulation runs as a standard **Lesson** activity, with branching, per-option scores and the gradebook, so it works on any Moodle 5.0 site, backs up with the course, and needs no custom player (in line with D-04, plugins over custom code). The only custom code is a course report that turns attempts into a readiness decision, and a builder that creates the Lesson from a scenario file.
3. **Evidence from three sources.** Readiness combines knowledge (ADAPTIVE knowledge checks), integrated performance (the scenario) and, optionally, a supervisor's judgement.
4. **Course-agnostic.** Scenarios and settings live in `courses/<course>/readiness/`.

## 2. Simulation requirements (WP §13, item 1)

A scenario is a branching sequence of **decision points** in an outbreak-response storyline.

| Element | Meaning |
|---|---|
| Domain | A performance area assessed across the scenario, for example *Data quality and reproducible wrangling*. Each domain is linked to course competencies (`COMP-x.y.z`). |
| Decision point | A situation update (the "inject"), a question, and 2–5 options. |
| Option | Text, immediate feedback (consequence), points per domain (0–3), the next decision point or `end`, and optionally `critical: true`. |
| Critical option | An action that would cause serious harm in real work, for example publishing identifiable patient data. Any critical choice caps the result at *not yet independent*. |
| Branch | An option may lead to a different decision point (for example a remediation step after a poor cleaning choice). All paths must reach `end`, and cycles are not allowed. |

The file format is checked by `report_afirst_readiness\local\scenario::validate()`. See `courses/ds4h/readiness/scenarios/DS4H-RDY-01.json` for a full example.

## 3. Performance assessment criteria (WP §13, item 2)

For each learner and scenario:

- **Domain score** = points earned in that domain ÷ the highest points available in that domain on the path the learner actually took × 100.
- **Knowledge** = the learner's grade on each module knowledge check listed in the readiness settings (from `course_config.json`).
- **Supervisor rating** (optional) = the grade on a named assignment, for example a rubric-marked situation report or an observed exercise.

| Result | Rule (v1 defaults, set per course) |
|---|---|
| **Ready for independent work** | Scenario completed; every domain ≥ 80%; no critical choice; every knowledge check ≥ 80%; supervisor rating ≥ 70% if a supervisor activity is configured |
| **Ready with supervision** | Scenario completed; every domain ≥ 60%; knowledge mean ≥ 60%; not independent (including any critical choice) |
| **Not yet ready** | Scenario completed, but below the supervised thresholds |
| **In progress / not started** | The scenario was not finished / not attempted |

The report shows which rule each learner missed, so the result can be acted on. The thresholds are design choices to be checked against expert judgement during the beta and pilot. Agreement between the rule and supervisors is a DE measure (DQ8).

## 4. Technology options (WP §13, item 3)

| Option | Fit | v1 choice |
|---|---|---|
| Core Lesson activity, branching with custom scoring | Every Moodle site; gradebook, completion, backup and restore; mobile app support | **Chosen** |
| H5P Branching Scenario | Richer media; scores are less granular per domain; authoring outside the scenario file | Later, for media-rich injects |
| Custom activity module with AI role-play | Most realistic; highest risk (hallucination, cost, maintenance) | Not in v1. Revisit after the pilot evaluation |
| External simulation engine (LTI) | Strongest simulation; separate hosting, data agreements | Phase 4 option |

## 5. What v1 contains

| Part | Path |
|---|---|
| Course report plugin | `report/afirst_readiness/` (Moodle: Course › Reports › Readiness) |
| Scenario validator and builder | `classes/local/scenario.php`, `classes/local/scenario_builder.php` |
| Readiness rules (pure, unit-tested) | `classes/local/rules.php` |
| Build CLI | `report/afirst_readiness/cli/build_scenario.php` |
| Pilot scenario (draft) | `courses/ds4h/readiness/scenarios/DS4H-RDY-01.json` |
| Course settings | `courses/ds4h/readiness/readiness_settings.json` |

Building a scenario into a course:

```bash
php report/afirst_readiness/cli/build_scenario.php --courseid=4 --section=6 \
    --scenario=/opt/afirst/courses/ds4h/readiness/scenarios/DS4H-RDY-01.json \
    --settings=/opt/afirst/courses/ds4h/readiness/readiness_settings.json \
    --course-config=/opt/afirst/courses/ds4h/course_config.json     --taxonomy=/opt/afirst/courses/ds4h/taxonomy.json
```

(Course 4, section 6 "Final Course Activities & Assessment" are the dev ids; use the production ids there. Add `--allow-draft` only on dev or for the beta.)

The builder creates a hidden Lesson: custom scoring, one attempt per page, no retakes, feedback shown. It records which answer maps to which option, so the report never relies on answer text. The teacher reviews the Lesson and makes it visible.

## 6. Resource and timeline estimates (WP §13, item 4)

| Work | Estimate |
|---|---|
| Author one scenario of 7–9 decision points, with expert review | 3–5 days of SME and analyst time |
| Translate one scenario (FR, PT) and review | 1–2 days per language |
| Beta with 3–5 volunteers; compare rule vs supervisor judgement | 2 weeks |
| Media-rich injects (H5P), AI role-play exploration | Phase 4: 2–3 months of a developer plus SME |

## 7. Lessons from BASIC and ADAPTIVE that shape READINESS (WP §13, item 5)

- Results from scripted test accounts are synthetic, not evidence (D-12). READINESS results count only from consented `beta` or `pilot` learners.
- Grounding and human sign-off are non-negotiable. The same applies to scenario content.
- Per-module adaptivity worked with core features (D-09). Per-domain readiness reuses the same approach: core activity plus a thin report.
- Lexical retrieval disadvantages non-English learners (D-17). Scenarios need translated versions, not on-the-fly translation.

## 8. Known limits of v1

- One attempt per scenario. Retakes need a new attempt policy, to be decided with supervisors.
- Domain scores come from authored option points. Their validity depends on expert review of those points.
- The report reads the learner's latest Lesson attempt. If a teacher allows retakes, earlier attempts are ignored.
- The builder is not idempotent: running it twice creates a second Lesson and a second report entry (the report shows the newest). Delete the old Lesson before rebuilding a new scenario version.

## 9. Dev test on the real course (2026-11-08)

**Evidence class: `dev`** (scripted test accounts, D-12). The draft scenario was built into dev course 4 (*Data Science & Analytics for Public Health*, restored without user data) as Lesson cm 60, hidden, with the 9 Knowledge Checks from `course_config.json` as knowledge evidence. Every domain competency was checked against `taxonomy.json`. Test learners played it through the Lesson web-service API, the same calls the Moodle app makes (`dev/simulate_attempt.php --path=...`):

| Learner | Knowledge Checks | Path | Domains | Result |
|---|---|---|---|---|
| student1 | all 100% | best option everywhere | all 100% | **Ready for independent work** |
| student4 | 80% (4.1: 67%) | weak cleaning step, then remediation; one weak analysis choice | DQW 60%, ANA 78%, others 100% | **Ready with supervision**, gaps listed |
| student2 | all 100% | shares identifiable data (critical) | GOV 50% | **Not yet ready**: critical choice, governance 50% |
| student3 | none taken | poor choices | 0–50% | **Not yet ready**: every domain, knowledge mean 0% with 9 not taken |
| student5 | none | stopped at decision 4 | — | **In progress** |

Two report fixes came out of this run (plugin 0.1.1): a critical choice is now named even when low scores already decide "not yet ready" (before, student2's reason did not mention it), and a knowledge mean pulled down by checks not taken says how many were missing.
