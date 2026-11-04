# A-FIRST: Plan vs Actual (status at 2026-10-25, Sprint 5; updated 2026-11-04)

For the advisory team. This maps each commitment in the approved documents to its status.

**Status key**

| Status | Meaning |
|---|---|
| **done** | Delivered and valid for the rebuild |
| **rebuilt** | The Phase 2 version exists but is being replaced to match the plan |
| **dropped** | Will not be delivered as written (decision logged) |
| **pending** | Not yet due, or not yet started |

Decision numbers (D-nn) refer to `DECISION_LOG.md`.

**Sources**

| Short name | Document |
|---|---|
| Prop | Capstone Proposal (June 2026) |
| Plan | Project Plan v3 (April 2026) |
| WP | Work Plan |
| Opt | AI Integration Options |

## 1. Objectives (Prop §1.4; WP §3)

| # | Commitment | Status | Notes |
|---|---|---|---|
| O1 | "Deploy a source-grounded AI assistant … with traceable citations on every response and no open-web access" | **rebuilt** | Phase 2 chat: 3B local model in production, citations not verifiable. Replaced by `block_afirst_assistant` on core AI, with citations, refusal, disclaimer, logs and escalation (D-04, D-10). |
| O1 | Explain and summarise course content (WP §3, Obj. 1) | **pending** | Core AI course-assistance placement. Enable at Sprint 8 deploy. |
| O2 | "skill gap detection from quiz performance and microlearning recommendations at sub-unit granularity" | **rebuilt** | The Phase 2 custom router is retired (Option 3). Replaced by per-module diagnostics, restrict access and H5P units (D-09). Sub-unit *routing* is not delivered; it is a stated limitation. |
| O2 | Skip mastered content (WP §3, Obj. 2) | **rebuilt** | Diagnostic ≥ 90% fast-tracks a module (D-09). |
| O3 | Pre-post within-subject pilot, statistical and qualitative analysis | **pending** | Sprints 9–11. Phase 2 "cohort" results are synthetic, not evidence (D-12). |
| O4 | Unanswered question log | **rebuilt** | Built into the plugin log (refused and reason). Proposal Sprint 7 adds "schema defined". |
| O4 | Content gap inventory | **pending** | Derived from the unanswered log in Sprints 10–12. |
| O4 | Replication guide (Prop §1.3, §3.10, Sprint 12) | **pending** | Skeleton in `REPLICATION_GUIDE.md`. |
| — | READINESS handover specification (Plan §11) | **pending** | Sprint 12. |

## 2. Integration approach (Plan §3.1, §5, §12; WP §4.1, §5)

| Commitment | Status | Notes |
|---|---|---|
| Hybrid Option 1 + Option 2; no Option 3 (Plan §3.2) | **rebuilt** | The Phase 2 build had become Option 3. The rebuild uses core features, plus one narrow grounded-chat plugin (D-04, a logged deviation). |
| Native Moodle AI for chat and summarisation (Plan §5.1) | **pending** | Moodle 5.0 core AI subsystem (D-03). |
| Adaptive Quiz activity (Plan §5.1) | **dropped** | No Moodle 5.0 version (D-06). Core quiz and restrict access instead. |
| Conditional release (Plan §5.1) | **pending** | Sprint 7, `course-setup/apply_course_config.php`. |
| DataCurso course recommendations and analytics (Plan §5.2) | **dropped** | Course-level only; pilot is one course (D-08). Analytics via core report builder. |
| Nolej H5P at sub-unit level (Plan §5.2–5.3) | **rebuilt** | Nolej web app (or the core H5P editor), not the Moodle plugin (D-05). 9 units in Sprint 7. The 3 Phase 2 HTML units are re-authored as H5P. |
| xAPI (Plan §5.3; Prop §3.6) | **pending** | Core H5P attempt data. `logstore_xapi` now supports Moodle 5.0, so the decision is reopened (D-07). |
| Local model dev only; cloud in pilot with caps (Plan §3.2, §5.4) | **rebuilt** | Phase 2 ran a local 3B model in production. Rebuild: cloud provider instance with rate limits (D-10). |
| Competency framework (Prop §1.1) | **pending** | Import 9 parent and 35 child competencies (Sprint 7). The taxonomy JSON from Phase 2 is reused. |

## 3. Evaluation metrics (Plan §7; WP §8; Prop §3.7–3.8)

| Measure | Target (source) | Status | Notes |
|---|---|---|---|
| AI response accuracy | ≥ 95% on a 50-question reviewer-audited set (Plan §7; Prop §3.7) | **pending** | Audit set in `courses/ds4h/evaluation/`. Harness `blocks/afirst_assistant/cli/run_audit.php` built and tested (D-14). Needs a provider instance (Q4). Run in Sprint 6 for the go/no-go (D-11). |
| Source traceability | 100% of responses cite source (Plan §7) | **pending** | Enforced in code: uncited answers are withheld (D-14). Automatic anchor pre-check in the harness; graders confirm. |
| Comprehension or competency gain | ≥ 20% mean improvement (Plan §7). Prop §3.8 instead keeps "at least 15 percent" as the secondary threshold, with Cohen's d as primary | **pending** | Baseline at Sprint 9, endline at Sprint 11. 27-question placement test as the instrument. **Thresholds conflict; see Q3.** |
| Path differentiation | ≥ 70% of learners on distinct paths (Plan §7). Prop §1.5 / §3.7: share with ≥ 20% content bypass or replacement | **pending** | Measured per module (D-09). The 83.3% bypass figure from Phase 2 is synthetic (D-12). |
| Learner experience | NPS positive (Plan §7). NPS ≥ +20 (Prop §3.7) | **pending** | Core Feedback activity at Sprints 10 and 11. **Thresholds conflict; see Q3.** |
| Unanswered question rate | Count and category (Prop §3.7) | **pending** | Plugin log. |
| Bandwidth equity | Experience on < 5 Mbps (Prop §2.5, §1.5) | **pending** | The plugin records `navigator.connection`. Connection type is a proxy for bandwidth, not a measurement. |
| Model evaluation matrix | 2 open and 2 cloud models; accuracy, latency, cost (Plan §6 Sprint 3; Prop §4.1 Sprint 3) | **pending** | Due Sprint 3 (August). Moved to Sprint 6, run through the audit harness. |
| Implementation barriers | Documented per barrier type (WP 1 §9) | **pending** | Decision log plus retrospectives. |

## 4. Sprints (Prop §4.1 monthly; Plan §6 / WP §6)

| Sprint | Planned deliverable | Status |
|---|---|---|
| 1 Jun | Scope lock, course confirmed, Jira live | **done** (Data Science course = Moodle course 2) |
| 2 Jul | Cohort survey, ethics submitted, admin access | Admin access **done**. Survey and ethics submission: **confirm (Q1)** |
| 3 Aug | Chat, summarisation, adaptive quiz in test environment; model matrix | Chat **rebuilt**. Adaptive quiz **dropped**. Matrix **pending** (Sprint 6) |
| 4 Sep | Content indexed; grounding tested on 50 questions (Prop) / 20 questions (Plan) | Index **rebuilt** (plugin). Test **pending** (50 questions, Sprint 6) |
| 5 Oct | Demo; bias audit; go/no-go | Demo of the rebuild in progress. Bias audit and go/no-go **moved to Sprint 6** (D-11) |
| 6 Nov | DataCurso onboarding; ethics clearance confirmed | DataCurso **dropped** (D-08). Ethics **pending**. Go/no-go and bias audit added |
| 7 Dec | Nolej and H5P; unanswered log schema | **pending** (D-05). Schema already in the plugin |
| 8 Jan | ADAPTIVE integration; beta with 3–5 volunteers; human review of AI sample | **pending**. Production deploy per `DEPLOYMENT.md` |
| 9 Feb | Launch, onboarding, baseline, consent | **pending**. Synthetic-data purge first (D-13) |
| 10–12 | Midpoint, endline, interviews, report, guide, manuscript | **pending** |

## 5. Ethics, privacy and governance (Prop §3.6, §3.9; Plan §9–10)

| Commitment | Status | Notes |
|---|---|---|
| Ethics clearance confirmed by Sprint 6 (Prop §3.9) | **pending** | Status not known to this repository (Q1). |
| Informed consent with opt-out (Prop §3.9; Plan §10) | **pending** | `tool_policy` site policy with opt-out, plus a signed form at Sprint 9. |
| AI disclosure at onboarding and on every response (Prop §3.9) | **rebuilt** | Core AI policy acceptance plus the plugin's disclaimer, with the exact §3.9 wording. |
| Escalation with 48-hour human review; corrective action logged (Prop §3.9) | **rebuilt** | Plugin escalation queue with an SLA indicator. |
| Bias audit: 3 language groups × 5 topics, WHO checklist, external reviewer (Prop §3.9) | **pending** | Moved to Sprint 6 (D-11). External reviewer: confirm (Q2). |
| Data minimisation and pseudonymisation (Prop §3.6; Plan §10) | **rebuilt** | No identity in prompts. Pseudonymised CSV export. Privacy provider. |
| Data on Africa CDC infrastructure only (Prop §3.6) | **pending** | The cloud model receives question text (D-10). Needs ethics and data-protection clearance (Q4). |
| Decision log with date and approver (Plan §9) | **done** | `DECISION_LOG.md`. |
| Security of the existing build | **done** (code) / **pending** (rotation) | D-01, D-02. Password rotation outstanding. |
| No synthetic data in study records | **pending** | Purge before the Sprint 9 baseline (D-13). |

## 6. Added after the plan (2026-11-04)

| Item | Status | Notes |
|---|---|---|
| Docker packaging, scalable web tier | **done** (dev-tested) | `deploy/docker/` (D-15). Production migration not decided. |
| Course-agnostic onboarding | **done** (dev-tested) | `courses/<key>/`, `docs/ONBOARDING_A_COURSE.md` (D-16). |
| Cross-lingual retrieval | **pending decision** | D-17. |

## Open questions for the advisory team

1. **Ethics:** what is the status of the ethics submissions (Africa CDC and the Ethiopian national IRB)? Prop §3.9 requires clearance confirmed by Sprint 6 (November 2026), or the pilot timeline is escalated.
2. **Bias audit reviewer:** who is the external reviewer for the bias audit (Prop §3.9 and Sprint 4)? Do you agree to move the audit and the go/no-go to the end of Sprint 6 (D-11)?
3. **Conflicting thresholds:** which thresholds apply? Plan §7 says competency gain ≥ 20% and NPS positive. Prop §3.7–3.8 say ≥ 15% secondary threshold, Cohen's d primary, and NPS ≥ +20. Sprint 4 grounding test: 20 questions (Plan) or 50 (Prop)?
4. **Cloud model and data protection:** do you approve the cloud model for the pilot (D-10)? Which provider? Does the ethics protocol cover question text leaving Africa CDC infrastructure (Prop §3.6)?
5. **Plugin deviation:** do you accept the grounded-chat plugin as a narrow deviation from "plugins over custom code" (D-04)?
6. **Vendor changes:** do you accept dropping DataCurso (D-08), `mod_adaptivequiz` (D-06) and the Nolej plugin in favour of the Nolej web app (D-05)? Can the budget line "Software licensing (DataCurso, Nolej) 6,000" (Prop §4.2) be reduced or reallocated?
7. **Per-module adaptivity:** is it acceptable for Objective 2, with sub-unit routing reported as a limitation (D-09)?
8. **xAPI LRS:** is an external xAPI LRS wanted (D-07), or are core logs plus H5P attempt data sufficient for Prop §3.6?
9. **Sprint length:** Prop §4.1 has monthly sprints, while Plan §6 and the WP have one-week sprints. Confirm that the monthly calendar is authoritative.
10. **Synthetic personas:** the Phase 2 test personas use real names (for example "Adam Terefe (U5)"). Should those accounts be renamed now, or only removed in the D-13 purge?
11. **Docker migration (D-15):** should production move to the Docker stack, and when? Who at Africa CDC operates it?
12. **Cross-lingual retrieval (D-17):** which option should be used for French and Portuguese questions?
