# Replicating A-FIRST: AI-Enabled Course Deployment Guide (skeleton)

> **Status:** skeleton (October 2026). The full guide is a Sprint 12 deliverable (Proposal §4.1, Sprint 12: "AI-enabled course replication guide"), to be published openly on the Africa CDC project repository (Proposal §1.3, §3.10; Objective 4 in §1.4). Each section below states what it must cover. Fill them in from the pilot evidence, not from intentions.

## 1. Who this guide is for

Africa CDC course teams, member-state agencies and partners who want to add a source-grounded AI assistant and rule-based adaptivity to an existing Moodle course.

- State the assumed skills: Moodle site admin and course editing. No AI engineering is needed.
- State what the guide does **not** cover: READINESS simulation (see the handover specification), and custom recommenders (Option 3).

## 2. What you get, and what the pilot showed

- A one-page summary of the BASIC and ADAPTIVE layers.
- The headline pilot results with confidence intervals (Proposal §3.8). Present them as indicative, not definitive (§3.4).
- What did not work, linked to the decision log.

## 3. Prerequisites

- **Moodle 5.0 or later**, with the core AI subsystem. Record the exact versions tested.
- Server sizing used in the pilot. Cron must run every minute (the adhoc indexing task needs it).
- An AI provider account (cloud), with a spend limit. Optionally, a local Ollama for testing.
- Approvals: ethics, data protection review of the cloud provider, and the content owner's sign-off on sources.

## 4. Governance before you build

- A decision log template (see `DECISION_LOG.md`).
- The AI disclosure wording and consent with opt-out (Proposal §3.9; Plan v3 §10).
- The escalation process and the 48-hour reviewer SLA. Name the reviewer roles.
- A bias audit protocol: language × topic sample, WHO checklist.

## 5. Preparing course content

- Choosing and approving the source documents: what counts as "approved", and who signs off.
- Preparing the text: plain-text extraction, section labels, and avoiding scanned PDFs.
- Splitting the course into modules that a diagnostic can gate on.
- Authoring H5P microlearning for each module (Nolej web app or the core H5P editor). Include the time per unit actually spent in the pilot.

## 6. Installing the grounded assistant (`block_afirst_assistant`)

- Install and upgrade steps (see `DEPLOYMENT.md` §2).
- The provider instance and rate limits (`DEPLOYMENT.md` §3). How to size the limits from the pilot's usage data.
- Uploading sources, indexing, and the retrieval threshold. How to tune the threshold with your own audit set.
- Capabilities: use, managesources, review.

## 7. Configuring adaptivity with core Moodle

- The competency framework import (`course-setup/taxonomy_to_csv.py`, `tool_lpimportcsv`).
- Per-module diagnostics. Fast-track and remediation rules (≥ 90% and < 80% in the pilot; justify your own values).
- `course-setup/apply_course_config.php` and the manual checklist.
- **Limitation:** routing is per module, not per competency (D-09).

## 8. Evaluating before you launch

- Building a 50-question audit set: mix of question types, about 20% out of scope, reviewer validation.
- Running `blocks/afirst_assistant/cli/run_audit.php` for each provider instance (enable only that provider for the run). Reading the accuracy, traceability, refusal and latency outputs.
- The go/no-go thresholds the pilot used (Plan v3 §7), and the result.
- Running the bias audit.

## 9. Running the pilot

- Onboarding session outline (60 minutes, Proposal §3.9).
- Baseline and endline instruments, and survey timing.
- Weekly checks: escalations, unanswered log, spend.
- Data hygiene: no test or synthetic accounts in study data (lesson from D-12 and D-13).

## 10. Using the logs

- Unanswered question log → content gap inventory (Objective 4).
- Pseudonymised export and analysis scripts.
- Equity analysis by connection type, and its limits.

## 11. Costs and effort

- Actual cloud spend per learner per month; licences; staff time per phase. Use pilot figures.

## 12. Known limitations and open issues

- Lexical (BM25) retrieval: no multi-document reasoning; English-only stop words.
- Per-module adaptivity.
- Dependence on a cloud provider and its data terms.

## 13. Lessons learned

- Drawn from the decision log and retrospectives. Include the Phase 2 drift and the rebuild (Proposal §1.6 Developmental Evaluation).

## Appendices

- A. Configuration checklist
- B. Audit set template
- C. Consent and disclosure text
- D. Decision log extract
- E. Troubleshooting
