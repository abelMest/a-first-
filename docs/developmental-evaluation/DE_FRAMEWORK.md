# A-FIRST Developmental Evaluation (DE) framework

A-FIRST is a **Developmental Evaluation** (Capstone Proposal §1.6; Work Plan §3, §9). It follows Patton, Westley and Zimmerman (Patton 2011) and the practitioner guide by Dozois, Langlois and Blanchet-Cohen (2010). This document turns that framing into concrete practice for the repository: what is recorded, by whom, when, and how it feeds decisions and the final report.

> **Terminology note.** In the Phase 2 reports (September 2026), "DE" was expanded as *Differentiated Education* (`docs/phase2-archive/reports/COHORT_DE_EVALUATION_REPORT.md`). In this project, **DE always means Developmental Evaluation**. That Phase 2 report is synthetic system testing (D-12). It is not a DE output or learner evidence.

## 1. Why DE fits A-FIRST

- The intervention is **emergent**. The architecture changed in October 2026: the Phase 2 custom router (Option 3) was replaced by core Moodle features plus one grounded-chat plugin (D-04, D-09). DE treats that kind of change as data, not as failure.
- The **evaluator is embedded**: the fellow builds and evaluates. DE makes this explicit and offsets it with documented decisions, external review (bias audit reviewer, two-reviewer audit sign-off) and advisory-team approval.
- **Documentation is an output** (Prop §1.6). The decision log, adaptation log, sprint reflections, unanswered-question log and replication guide are all evaluation deliverables.

DE sits **alongside** the summative pre-post pilot (Prop §3.2). DE informs how the system is built and adapted. The pilot measures effect. They answer different questions and must not be mixed: a DE observation is never reported as an outcome finding.

## 2. Evaluation questions

### 2.1 Summative pilot questions (fixed; answered in Sprints 9–11)

| Ref | Question | Source |
|---|---|---|
| RQ-P | Does the Moodle-integrated, source-grounded assistant improve speed, accuracy and comprehension compared with standard navigation? | Prop §1.5 |
| RQ1 | Does AI-assisted instruction produce measurable competency gains? | WP §3.1 |
| RQ2 | Does source-grounded AI improve accuracy and reduce knowledge gaps? | WP §3.2 |
| RQ3 | Does gap-based microlearning give greater gains than linear delivery? | WP §3.2 |
| RQ4 | What proportion of learners get a meaningfully distinct path (≥ 20% bypass or replacement), and does it relate to post-scores? | WP §3.2; Prop §1.5 |
| RQ-S1 | What does the assistant fail to answer, and why? Do failures reveal content gaps? | Prop §1.5 |
| RQ-S2 | How does experience differ by bandwidth? | Prop §1.5 |

### 2.2 Developmental questions (evolving; answered continuously)

These are reviewed at every sprint reflection. New questions are added with a date, and retired questions are kept with the reason.

| Ref | Developmental question | Opened | Evidence that answers it |
|---|---|---|---|
| DQ1 | What implementation barriers and facilitators shape adoption in the Africa CDC Moodle environment? (WP RQ5) | 2026-06 | Barrier register (`DE_LOG.md` §B), retrospectives |
| DQ2 | Which planned tools and vendors actually work on Moodle 5.0 at Africa CDC, and what replaces those that don't? | 2026-10-25 | D-03 to D-08 |
| DQ3 | Is per-module adaptivity enough to differentiate paths, given that SCORM gives no sub-unit signal? | 2026-10-25 | D-09; Sprint 8 beta paths; Sprint 9–11 path data |
| DQ4 | What refusal threshold balances wrong answers against unhelpful refusals? | 2026-10-25 | Audit runs (`evaluation/`), unanswered-question log |
| DQ5 | Can a single fellow keep a grounded assistant accurate as content changes? What does maintenance cost? | 2026-10-25 | Re-audit effort, source re-index time, escalation workload |
| DQ6 | How did learners and facilitators use (or not use) escalation, and did corrective actions close the loop within 48 hours? | 2026-10-25 | Escalation queue and SLA report |
| DQ7 | Does the assistant behave equitably across English, French and Portuguese prompts and across bandwidth? | 2026-10-25 | Bias audit (`courses/ds4h/evaluation/bias_audit_sample.csv`), connection metadata |

## 3. DE instruments in this repository

| Instrument | File | Who writes it | When |
|---|---|---|---|
| Decision log | [`../DECISION_LOG.md`](../DECISION_LOG.md) | Fellow; approver recorded | Whenever scope, technology or risk changes |
| Adaptation and learning log | [`DE_LOG.md`](DE_LOG.md) | Fellow | Whenever something is learned or the intervention is adapted |
| Barrier register | [`DE_LOG.md`](DE_LOG.md) §B | Fellow | Ongoing; reviewed monthly |
| Sprint reflection | [`sprint-reflections/`](sprint-reflections/), from [`SPRINT_REFLECTION_TEMPLATE.md`](SPRINT_REFLECTION_TEMPLATE.md) | Fellow, reviewed by supervisor | End of each monthly sprint |
| Plan vs actual | [`../PLAN_VS_ACTUAL.md`](../PLAN_VS_ACTUAL.md) | Fellow | Updated at each sprint reflection |
| Audit and bias results | `evaluation/` | Fellow + reviewers | Sprint 6 (go/no-go), Sprint 8, after any content or model change |
| Unanswered-question log | Plugin table `block_afirst_assistant_log` (refused rows) | System | Continuous; reviewed Sprints 10–12 |
| Escalation record | Plugin escalation queue | Learners, reviewers | Continuous; 48-hour SLA |

## 4. The DE cycle

Each monthly sprint (Prop §4.1) runs one DE cycle, with the weekly Friday review (WP §10) as the touchpoint:

These are the four DE practices used since Sprint 1. GitHub issues tag their practice through `.github/ISSUE_TEMPLATE/sprint-issue.md`.

1. **Orienting:** at the start of the sprint, review what has changed in the technical environment, policy context or stakeholder landscape. Record expectations (sprint reflection §1–2).
2. **Watching:** during the sprint, collect what happens: builds, test results, logs, feedback, developmental moments and integration barriers (`DE_LOG.md`, Jira).
3. **Sense-making:** at the end of the sprint, fill in the sprint reflection with the advisory team. What did we expect? What actually happened? Why the difference?
4. **Intervening:** turn the insights into evidence-based pivots: decisions (`DECISION_LOG.md`) and adaptations (`DE_LOG.md` §A). Update `PLAN_VS_ACTUAL.md`. The reflection feeds the Thursday pre-Friday brief and the sprint review report.

### Learning framework: what we pay attention to

This is a living list, first written in Sprint 1 (May 2026) and reviewed at each sprint reflection. Each area points to where the evidence is kept now.

| Area | Watch for | Where it is tracked |
|---|---|---|
| 1. Technical integration | Moodle version compatibility with AI plugins; local model vs cloud fallback; FHIR / OpenHIE alignment; data sovereignty (where learner data is stored and processed) | D-03 to D-10, D-15; DQ2; B-01, B-02 |
| 2. Learner experience | Baseline digital literacy; language and multilingual support across Member States; response quality and hallucination risk; low-bandwidth access | DQ7, D-17; audit and bias results; connection metadata |
| 3. Content governance | Alignment with the approved curriculum; source grounding (no open web); curriculum update cycles and version control; SCORM packaging quality | Source approval workflow; D-14; DQ5; B-03 |
| 4. Institutional readiness | Moodle admin access and IT governance; GPU server provision; ethics clearance | B-01, B-04, B-06; Q1, Q4, Q11 |
| 5. Sustainability and scale | Capacity of the Africa CDC IT team to maintain the system after the fellowship; evidence for continental scale-up; onboarding further courses | D-15, D-16; `docs/ONBOARDING_A_COURSE.md`; replication guide |

Sprint 1 material (the review deck and the original repository README) is in [`sprint-reviews/`](sprint-reviews/) and [`../sprint1-archive/`](../sprint1-archive/).

Rules that keep the DE honest:

- **Evidence class is always labelled:** `synthetic` (scripted or test accounts), `dev` (local docker), `beta` (3–5 consented volunteers, Sprint 8), `pilot` (consented enrollees, Sprints 9–11). Only `pilot` data answers the summative questions. Phase 2 cohort numbers are `synthetic` (D-12).
- **Record the expectation before the result.** For audits and the beta, write the expected outcome in the sprint reflection *before* the run.
- **Negative results are kept.** Failed approaches stay in the log with the reason (for example the Phase 2 3B-model citations).
- **No participant data in this repository.** Logs and exports stay on Africa CDC infrastructure (Prop §3.6). The repository holds aggregates and instruments only.

## 5. Mapping to the final report

| Final-report section (Sprint 12) | DE source |
|---|---|
| Implementation narrative and adaptations | `DE_LOG.md` §A, `DECISION_LOG.md` |
| Barriers and facilitators (RQ5 / DQ1) | `DE_LOG.md` §B, sprint reflections |
| Lessons learned | Sprint reflections, "what we would do differently" |
| Replication guide | `../REPLICATION_GUIDE.md`, built from the above |
| READINESS handover (WP §13) | Lessons that should shape READINESS design |

## References

- Dozois, E., Langlois, M., and Blanchet-Cohen, N. (2010). *DE 201: A Practitioner's Guide to Developmental Evaluation.* J.W. McConnell Family Foundation and IICRD.
- Patton, M. Q. (2011). *Developmental Evaluation: Applying complexity concepts to enhance innovation and use.* Guilford Press.
- Westley, F., Zimmerman, B., and Patton, M. Q. (2007). *Getting to Maybe: How the world is changed.* Vintage Canada.
