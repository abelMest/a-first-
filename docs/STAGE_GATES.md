# Stage gates: BASIC, then ADAPTIVE, then READINESS

A-FIRST is built and released one layer at a time (D-19). Each layer starts only after the one before it has passed its gate. The code for later layers may already exist in the repository, but it is **not deployed, piloted or reported as evidence** until its stage opens.

| Stage | Layer | Window (Prop §4.1, monthly sprints) | Status |
|---|---|---|---|
| 1 | **BASIC**: source-grounded assistant | Now → end of Sprint 6 (November 2026) | **In progress.** Plugin built and tested on local Moodle; gate not yet run |
| 2 | **ADAPTIVE**: diagnostics, fast-track, remediation | Opens when Gate 1 passes → Sprints 7–8 | **Built on the local dev site only** (2026-11-06, D-22–D-24): diagnostics, review units, retakes, rules and competencies on the restored DS4H course; review units hidden pending owner review. Nothing in production; the stage formally opens when Gate 1 passes |
| 3 | **READINESS**: scenario-based readiness | Opens when Gate 2 passes | **Parked.** v1 exists in `report/afirst_readiness/` (D-18); not deployed |

## Gate 1: BASIC go/no-go (end of Sprint 6)

Criteria are the plan's own (Plan v3 §7, D-11). All must be met, and the advisory team takes the decision.

| # | Criterion | Evidence | Owner | Status |
|---|---|---|---|---|
| 1.1 | Accuracy ≥ 95% on the 50-question reviewer-audited set (Plan §7; Prop §3.7) | `cli/run_audit.php` output on `courses/ds4h/evaluation/audit_set.csv`, graded by two reviewers | Fellow, reviewers | **Run done, grading pending.** 3B local run on the real course (2026-11-06, `courses/ds4h/evaluation/results/2026-11-06-gate1-ollama-llama3.2-3b/`): 36/40 answered, all citing the expected module; two reviewers must grade A1 and re-anchor 14 items worded from the old topic texts |
| 1.2 | 100% of answers cite a verifiable source (Plan §7) | Same audit run. Uncited answers are already withheld in code (D-14) | Fellow | **Met automatically** (36/36 cited, 36/36 the expected module); graders confirm |
| 1.3 | Out-of-scope questions are refused | The 10 out-of-scope items in the audit set | Fellow | **Met** on the 2026-11-06 run: 10/10 refused, plus 3/3 in the bias sample |
| 1.4 | Bias audit done: 3 language groups × 5 topic areas, WHO-adapted checklist, one external reviewer (Prop §3.9) | `courses/ds4h/evaluation/bias_audit_sample.csv` scored with `evaluation/fairness_checklist.md` | Fellow, external reviewer | **Run done, review pending.** With D-27 (cross-lingual on, back-translation support check, temperature 0): English, French and Portuguese 6/6 each, nothing out of scope answered (was 0/6 for French and Portuguese). Reviewer not named (Q2) |
| 1.5 | Model chosen from the evaluation matrix: 2 open and 2 cloud models on accuracy, latency and cost (Plan §6, Sprint 3) | Audit runs per provider instance | Fellow, admin | **Decided: local llama3.2 3B** (D-25, no cloud). Matrix to compare open models only; an 8B run needs a server with more memory |
| 1.6 | Grounding threshold set from the audit (not the default 3.0) | Threshold recorded in `DECISION_LOG.md` | Fellow | **Set** (D-26): keyword 1.0, similarity 0.7. No threshold changed out-of-scope outcomes; safety comes from the decline and the support check |
| 1.10 | Answers whose facts are not in the cited passages are withheld (support check), verified by re-running the audit | Audit results | Fellow | **Verified**: 0 out-of-scope answered in two repeat runs at temperature 0; now language-aware (plugin 0.5.1, D-27) |
| 1.7 | Ethics status allows a beta (Prop §3.6, §3.9); with local models (D-25) no question text leaves Africa CDC | Ethics correspondence | Fellow, supervisor | Pending (Q1) |
| 1.8 | Plugin tests pass on Moodle 5.0 (PHPUnit and Behat) | CI or local run log | Fellow | Done locally (2026-10-26); re-run on the release tag |
| 1.9 | Advisory team decision recorded | `DECISION_LOG.md` entry | Advisory team | Pending |

**If Gate 1 fails:** fix and re-run the audit; ADAPTIVE does not start. Record the result either way (DE log).

**When Gate 1 passes:** deploy BASIC only (Stage 1 of [`DEPLOYMENT.md`](DEPLOYMENT.md)), hide the old `block_ollama_chat`, and open Stage 2.

## Gate 2: ADAPTIVE ready for the pilot (end of Sprint 8)

| # | Criterion | Evidence | Owner | Status (2026-11-06) |
|---|---|---|---|---|
| 2.1 | BASIC running in production with no open critical issues | Smoke test (DEPLOYMENT §7), reviewer queue | Fellow | Pending (Gate 1) |
| 2.2 | Competency framework imported and linked; module diagnostics built from the existing question bank (4 × 10 questions, D-23) | `course-setup/build_diagnostics.php`, `import_competencies.php` | Fellow, content owner | **Done on dev**: 4 diagnostics (Placement Test + 3 new, 10 bank questions each); 44 competencies imported, the 9 module competencies linked to the course, Knowledge Checks and retakes (evidence on completion). Repeat on the production course after Gate 1 |
| 2.3 | 9 H5P microlearning units built and reviewed by the content owner (D-05, D-24) | `courses/ds4h/microlearning/REVIEW.md` signed | Content owner | **Built, awaiting review.** 9 units copied word for word from the SCORM text, hidden from learners. The owner marks each unit in `REVIEW.md`; then `build_microlearning.php --show` |
| 2.4 | Fast-track (diagnostic ≥ 90%) and remediation (knowledge check < 70%, D-22) rules verified as test students | `course-setup/CHECKLIST.md` sign-off | Fellow | **Verified on dev** with scripted attempts (`dev/simulate_attempt.php`) and the browser: 10/10 on the Module 2 diagnostic opened both Module 2 Knowledge Checks without the SCORM; 20% on Knowledge Check 1.1 opened Review 1.1, and viewing it opened the retake; a learner with no attempts saw every Knowledge Check locked |
| 2.5 | Beta with 3–5 volunteers covering BASIC and ADAPTIVE; critical issues fixed (Prop §4.1 Sprint 8) | Beta notes, DE log | Fellow | Pending |
| 2.6 | Synthetic Phase 2 data purged before the Sprint 9 baseline (D-13) | Purge record | Admin | Pending |
| 2.7 | Advisory team decision recorded | `DECISION_LOG.md` | Advisory team | Pending |

**When Gate 2 passes:** the pilot launches at Sprint 9 with BASIC and ADAPTIVE. Stage 3 may open.

## Gate 3: READINESS may be used (after Gate 2)

READINESS is outside the original fellowship scope (Plan §2.2, WP §13). It opens only when all of these hold:

| # | Criterion | Owner |
|---|---|---|
| 3.1 | Advisory team accepts the scope change (D-18) | Advisory team |
| 3.2 | Pilot scenario `DS4H-RDY-01` signed off by a subject-matter expert (no `--allow-draft`) | Content owner |
| 3.3 | Protocol and ethics cover READINESS outcomes; otherwise results are labelled `dev`/`beta` evidence only | Fellow, supervisor |
| 3.4 | Readiness thresholds reviewed against the ADAPTIVE data from the pilot | Fellow |

**Status (2026-11-08):** not open. v1 is built and dev-tested on the real course (dev course 4, all five result types reproduced, [`READINESS_SPEC.md`](READINESS_SPEC.md) §9). 3.1–3.4 are all pending; the scenario is still `draft` (no SME named).

If Gate 3 is not opened during the fellowship, the deliverable stays the handover specification in [`READINESS_SPEC.md`](READINESS_SPEC.md), as the plan intended.
