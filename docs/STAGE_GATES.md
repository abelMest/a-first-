# Stage gates: BASIC, then ADAPTIVE, then READINESS

A-FIRST is built and released one layer at a time (D-19). Each layer starts only after the one before it has passed its gate. The code for later layers may already exist in the repository, but it is **not deployed, piloted or reported as evidence** until its stage opens.

| Stage | Layer | Window (Prop §4.1, monthly sprints) | Status |
|---|---|---|---|
| 1 | **BASIC**: source-grounded assistant | Now → end of Sprint 6 (November 2026) | **In progress.** Plugin built and tested on local Moodle; gate not yet run |
| 2 | **ADAPTIVE**: diagnostics, fast-track, remediation | Opens when Gate 1 passes → Sprints 7–8 | **Not started.** Tooling exists in `course-setup/`; not applied to any course |
| 3 | **READINESS**: scenario-based readiness | Opens when Gate 2 passes | **Parked.** v1 exists in `report/afirst_readiness/` (D-18); not deployed |

## Gate 1: BASIC go/no-go (end of Sprint 6)

Criteria are the plan's own (Plan v3 §7, D-11). All must be met, and the advisory team takes the decision.

| # | Criterion | Evidence | Owner | Status |
|---|---|---|---|---|
| 1.1 | Accuracy ≥ 95% on the 50-question reviewer-audited set (Plan §7; Prop §3.7) | `cli/run_audit.php` output on `courses/ds4h/evaluation/audit_set.csv`, graded by two reviewers | Fellow, reviewers | Pending: needs a provider instance (1.5) |
| 1.2 | 100% of answers cite a verifiable source (Plan §7) | Same audit run. Uncited answers are already withheld in code (D-14) | Fellow | Pending |
| 1.3 | Out-of-scope questions are refused | The 10 out-of-scope items in the audit set | Fellow | Pending |
| 1.4 | Bias audit done: 3 language groups × 5 topic areas, WHO-adapted checklist, one external reviewer (Prop §3.9) | `courses/ds4h/evaluation/bias_audit_sample.csv` scored with `evaluation/fairness_checklist.md` | Fellow, external reviewer | Pending: reviewer not named (Q2); FR/PT wording unchecked |
| 1.5 | Model chosen from the evaluation matrix: 2 open and 2 cloud models on accuracy, latency and cost (Plan §6, Sprint 3) | Audit runs per provider instance | Fellow, admin | Pending: cloud provider and key (Q4) |
| 1.6 | Grounding threshold set from the audit (not the default 3.0) | Threshold recorded in `DECISION_LOG.md` | Fellow | Pending. Rehearsal (DE A-12): retrieval scores alone cannot separate out-of-scope questions, so the threshold is a weak filter |
| 1.10 | Answers whose facts are not in the cited passages are withheld (support check), verified by re-running the audit | Audit results | Fellow | Built in plugin 0.4.0 (D-20). Rehearsal run 2: out-of-scope answered 6 → 1, no anchored answer withheld (DE A-13). Re-verify on the cloud model |
| 1.7 | Ethics status allows a beta, and the cloud provider's data handling is covered (Prop §3.6, §3.9) | Ethics correspondence | Fellow, supervisor | Pending (Q1, Q4) |
| 1.8 | Plugin tests pass on Moodle 5.0 (PHPUnit and Behat) | CI or local run log | Fellow | Done locally (2026-10-26); re-run on the release tag |
| 1.9 | Advisory team decision recorded | `DECISION_LOG.md` entry | Advisory team | Pending |

**If Gate 1 fails:** fix and re-run the audit; ADAPTIVE does not start. Record the result either way (DE log).

**When Gate 1 passes:** deploy BASIC only (Stage 1 of [`DEPLOYMENT.md`](DEPLOYMENT.md)), hide the old `block_ollama_chat`, and open Stage 2.

## Gate 2: ADAPTIVE ready for the pilot (end of Sprint 8)

| # | Criterion | Evidence | Owner |
|---|---|---|---|
| 2.1 | BASIC running in production with no open critical issues | Smoke test (DEPLOYMENT §7), reviewer queue | Fellow |
| 2.2 | Competency framework imported and linked; 9 per-module diagnostics built from the existing question bank | Moodle course 2 | Fellow, content owner |
| 2.3 | 9 H5P microlearning units built and reviewed by the content owner (D-05) | Content bank | Content owner |
| 2.4 | Fast-track (diagnostic ≥ 90%) and remediation (knowledge check < 80%) rules verified as test students | `course-setup/CHECKLIST.md` sign-off | Fellow |
| 2.5 | Beta with 3–5 volunteers covering BASIC and ADAPTIVE; critical issues fixed (Prop §4.1 Sprint 8) | Beta notes, DE log | Fellow |
| 2.6 | Synthetic Phase 2 data purged before the Sprint 9 baseline (D-13) | Purge record | Admin |
| 2.7 | Advisory team decision recorded | `DECISION_LOG.md` | Advisory team |

**When Gate 2 passes:** the pilot launches at Sprint 9 with BASIC and ADAPTIVE. Stage 3 may open.

## Gate 3: READINESS may be used (after Gate 2)

READINESS is outside the original fellowship scope (Plan §2.2, WP §13). It opens only when all of these hold:

| # | Criterion | Owner |
|---|---|---|
| 3.1 | Advisory team accepts the scope change (D-18) | Advisory team |
| 3.2 | Pilot scenario `DS4H-RDY-01` signed off by a subject-matter expert (no `--allow-draft`) | Content owner |
| 3.3 | Protocol and ethics cover READINESS outcomes; otherwise results are labelled `dev`/`beta` evidence only | Fellow, supervisor |
| 3.4 | Readiness thresholds reviewed against the ADAPTIVE data from the pilot | Fellow |

If Gate 3 is not opened during the fellowship, the deliverable stays the handover specification in [`READINESS_SPEC.md`](READINESS_SPEC.md), as the plan intended.
