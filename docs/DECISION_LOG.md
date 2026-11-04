# A-FIRST Decision Log

This is the decision log that Project Plan v3 §9 and Work Plan §9 require: "Captures scope changes, technology choices, and risk decisions with date and approver". The Capstone Proposal §1.6 counts the "documentation of decisions, adaptations, and lessons learned" as an evaluation output in its own right, and §3.6 lists decision-log entries as a qualitative data source.

**Conventions**

- **Approver:**
  - "Fellow" means Dr. Abel Mestie Mekonnen decided.
  - "pending: advisory team" means the entry still needs confirmation at a Friday review (Project Plan §9). The advisory team is Dr. Herman Tolentino, Dr. Tadesse Wuhib, Bekure Tamirat and Adam Terefe.
- **Status:** Proposed, Approved, Superseded or Reopened.
- **Updating entries:** add a new entry rather than editing an approved one. Use "Supersedes D-nn" to link them.
- **Source documents:**

| Short name | File |
|---|---|
| Proposal | `proposals/A-FIRST_Capstone_Proposal.docx` |
| Plan v3 | `proposals/A-FIRST_Project_Plan.docx` |
| Work Plan | `proposals/A-FIRST_Work_Plan.docx` and `A-FIRST_Work_Plan 1.docx` |
| Options | `proposals/AI Integration Options.docx` |
| Rebuild plan | `mossy-stargazing-kazoo.md` |

---

## Security fixes to the Phase 2 build

### D-01: AI log and profile endpoints require authentication

- **Date:** 2026-10-25
- **Decision:**
  - `log_interaction.php` now calls `require_login($courseid)`, rejects guests and checks `confirm_sesskey()` before it writes to `afirst_ai_interaction_log` or `afirst_ai_escalation`.
  - Escalations can only reference the caller's own log rows.
  - `profile_api.php` now calls `require_login()` and `require_sesskey()`.
- **Rationale:** before this change, anyone who could reach the server could write fake learner AI logs and escalations. That corrupts the evaluation data (Proposal §3.6, "AI response logs") and is a data-protection risk (Plan v3 §10).
- **Alternatives:** take the endpoints offline until the rebuild. Rejected because the old build stays running (see D-12).
- **Approver:** Fellow.
- **Status:** Fixed in the source copies in `C:\AFIRST_Backup`.
  - **Open:** whether the fix is deployed on `10.215.70.14` is not recorded. Confirm and log the deployment date.
  - The rebuild replaces both endpoints with Moodle external functions that check login, sesskey and capability (D-02).

### D-02: Secrets moved out of source into environment variables; passwords must be rotated

- **Date:** 2026-10-25
- **Decision:**
  - `remote_tool.py` reads the SSH host, user and password, and the Moodle DB password, from `AFIRST_SSH_HOST`, `AFIRST_SSH_USER`, `AFIRST_SSH_PASSWORD` and `AFIRST_DB_PASSWORD`. It exits if a required password is missing.
  - Passwords travel over stdin, never on a command line.
  - **The SSH/sudo password and the Moodle DB password that used to be in source must be rotated.** Until then, assume they are exposed: they sit in older files, backups and tarballs under `C:\AFIRST_Backup`.
- **Rationale:** the credentials were in plain text in scripts and in backups. Proposal §3.6 Data Management: "All data are stored on Africa CDC infrastructure under existing institutional security controls."
- **Alternatives:** SSH keys instead of passwords. Preferred longer term and recommended to the server admin.
- **Approver:** Fellow (code change).
  - **Pending:** rotation by the Africa CDC server/DB administrator. Not yet confirmed done.
- **Status:** Code fixed. **Rotation outstanding.**

---

## Platform and architecture (rebuild, 2026-10-25)

### D-03: Stay on Moodle 5.0

- **Date:** 2026-10-25
- **Decision:** the rebuild targets Moodle 5.0, the version already on the production VM (`10.215.70.14`, Proposal §4.2). Moodle is not downgraded to 4.5 to gain plugins.
- **Rationale:**
  - Moodle 5.0 core ships the AI subsystem (providers, provider instances with rate limits, AI policy acceptance, course-assistance placement). This covers most of the "Native Moodle AI plugins for chat assistance, summarization, and explanation" (Plan v3 §5.1) without third-party code.
  - Downgrading production is risky and would leave the site on an older branch for the pilot.
- **Alternatives:** downgrade to 4.5 LTS to use `mod_adaptivequiz`, the Nolej plugin and `block_terusrag`. Rejected for the reasons above.
- **Consequences:** D-05, D-06 and D-07.
- **Approver:** Fellow. Ratification **pending: advisory team**.
- **Status:** Approved by the fellow.

### D-04: Grounded chat built as one small Moodle plugin on the core AI subsystem (narrow Option 3 deviation)

- **Date:** 2026-10-25
- **Decision:**
  - Build `block_afirst_assistant`, a standard Moodle block. It:
    - retrieves from approved course sources (BM25 over chunked text, using the logic of `setup_rag_new.py` ported to PHP);
    - refuses when nothing scores above a threshold, or when the model returns `NOT_IN_COURSE`;
    - calls the model only through the core `generate_text` AI action.
  - Every answer shows numbered citations and the Proposal §3.9 disclaimer.
  - Every answer is logged. A "Report a problem" escalation goes to a reviewer queue with a 48-hour SLA.
  - The plugin has a privacy provider.
- **Rationale:**
  - Objective 1 (Proposal §1.4) requires "traceable citations on every response and no open-web access".
  - §3.9 requires the disclaimer, escalation and 48-hour review.
  - §3.6 requires AI response logs and an unanswered-question log.
  - No Moodle 5.0 plugin provides grounded answers with citations and refusal:
    - `block_terusrag` supports 4.1–4.5 only.
    - `block_ai_chat` has no retrieval.
    - DataCurso `local_dttutor` does not document its grounding or where data is stored.
  - Plan v3 §3.2 excludes Option 3 as a "custom recommender on top of Moodle data". This plugin is not a recommender. It is the minimum custom code needed to deliver the grounded chat that Plan v3 §5.1 itself asks for.
  - The Phase 2 `block_ollama_chat` (about 1,300 lines of JavaScript inside a PHP string, raw-SQL tables, a browser calling Ollama directly) is replaced, not extended.
- **Alternatives:**
  1. Core course assistance only (no citations or refusal, so it fails Objective 1).
  2. Wait for a 5.x grounded-chat plugin (none known).
  3. Downgrade to 4.5 for `block_terusrag` (see D-03).
- **Deviation:** Plan v3 §8 and Work Plan §11 say "Plugins over custom code". This is a logged, narrow deviation. Scope is limited to retrieval, answering, logging and escalation. There is no recommendation or learner modelling.
- **Approver:** Fellow. **Pending: advisory team** (scope deviation).
- **Status:** Approved by the fellow, in build.

### D-05: Nolej Moodle plugin dropped; Nolej web app used for H5P authoring

- **Date:** 2026-10-25
- **Decision:** do not install `local_nolej`. Author the Sprint 7 H5P units in the Nolej web app (Pro subscription) and import them into the content bank. If Nolej is not licensed, the fallback is Moodle's built-in H5P editor.
- **Rationale:**
  - `local_nolej` 2025041101 (released 16 April 2025) supports Moodle 4.1–4.5 only. Source: https://moodle.org/plugins/local_nolej/versions (checked 2026-10-25).
  - The proposal's need is H5P at sub-unit level (Plan v3 §5.2 and §5.3; Proposal §4.1 Sprint 7, "Nolej and H5P Conversion"). That need is met without the plugin.
- **Alternatives:** downgrade Moodle (see D-03); wait for a 5.x release.
- **Approver:** **Pending: advisory team** (vendor plan change; budget line in Proposal §4.2).
- **Status:** Proposed.

### D-06: `mod_adaptivequiz` dropped

- **Date:** 2026-10-25
- **Decision:** no Adaptive Quiz activity. Performance-based progression uses core quizzes plus core restrict access (conditional release). See D-09.
- **Rationale:**
  - The latest `mod_adaptivequiz` release (05 October 2025) supports Moodle 2.6–4.5 only. Source: https://marketplace.moodle.com/plugins/mod_adaptivequiz (checked 2026-10-25).
  - The Proposal §4.1 Sprint 3 deliverable "Chat, summarization, and adaptive quiz active in test environment" and Plan v3 §5.1 "Adaptive Quiz activity" cannot be met on 5.0.
- **Alternatives:** downgrade (see D-03).
- **Approver:** **Pending: advisory team**.
- **Status:** Proposed.

### D-07: `logstore_xapi`, **reopened**: the reason for dropping it no longer holds

- **Date:** 2026-10-25
- **Decision:**
  - The rebuild plan dropped `logstore_xapi` as having no Moodle 5.0 support. **That is no longer true:**
    - Release 5.0.3 (21 July 2026) supports Moodle 4.3–5.1.
    - Release 5.2.0 (24 October 2026) supports Moodle 4.5–5.3.
    - Source: https://marketplace.moodle.com/plugins/logstore_xapi/versions (checked 2026-10-25).
  - The rebuild does **not** depend on it. Core `mod_h5pactivity` already stores H5P xAPI statements as attempt data, and core logs plus report builder cover time on task, sequence and quiz attempts.
  - `logstore_xapi` is only needed if the project wants statements in an external LRS.
- **Proposed resolution:** do not install it for the pilot unless the advisory team wants an LRS. If so:
  - an LRS must be hosted on Africa CDC infrastructure (Proposal §3.6, "All data are stored on Africa CDC infrastructure");
  - this adds a Sprint 7 task.
- **Proposal wording:**
  - Proposal §3.6: "Moodle xAPI event stream capturing time on task, content sequence, quiz attempts, and AI assistant interactions".
  - Proposal §3.7: path differentiation is measured from the "xAPI event stream".
  - Both are met by core logs plus H5P attempt data. Name the source as "Moodle logs and H5P xAPI attempt data" in the analysis plan.
- **Approver:** **Pending: advisory team**.
- **Status:** Reopened (corrects the rebuild plan).

### D-08: DataCurso recommender not used

- **Date:** 2026-10-25 (to be confirmed formally at Sprint 6, which the Proposal §4.1 calls "DataCurso Onboarding")
- **Decision:** record DataCurso as "not used" for the pilot. No licence purchase.
- **Rationale:**
  - `block_datacurso_recomendate` ("Recommended courses") recommends whole courses on the student dashboard, using DataCurso's AI provider and ratings. Source: https://moodle.org/plugins/block_datacurso_recomendate (checked 2026-10-25).
  - The pilot is a single course (Proposal §1.7, "a single Moodle course environment"), so course-level recommendations add nothing.
  - The AI Integration Options document §2 itself calls the DataCurso outcome "Course-level personalization".
  - Gap detection inside the course is delivered with core features (D-09).
- **Alternatives:** license DataCurso for learner analytics only. Core report builder covers the same analytics (D-09).
- **Budget effect:** the Proposal §4.2 line "Software licensing (DataCurso, Nolej) 6,000" falls to the Nolej Pro cost only.
- **Approver:** **Pending: advisory team** (removes a named vendor from Plan v3 §12 decision 3).
- **Status:** Proposed.

### D-09: Adaptivity per module, not per competency

- **Date:** 2026-10-25
- **Decision:**
  - The placement test is split into 9 three-question per-module diagnostics.
  - A score of 90% or more on a module's diagnostic fast-tracks that module.
  - A score below 80% on a module's knowledge check unlocks that module's H5P microlearning unit, then a retake.
  - All of this uses core restrict access. Competencies are tracked with the core competency framework (9 parent and 35 child competencies, imported via `tool_lpimportcsv`) but are not used for routing.
- **Rationale:**
  - Grades for individual quiz questions do not reach the gradebook, so core restrict access can only gate on a whole quiz.
  - Routing per competency needs custom learner modelling, which is Option 3 and excluded (Plan v3 §3.2). AI Integration Options §4 says the same: "Deep: Unit-level recommendations (requires custom AI + data tracking)".
  - The Phase 2 per-competency router (`adaptive_router.py`, `gap_detector.py`) was a custom recommender, so it is retired.
- **Effect on metrics:**
  - The Proposal §1.5 secondary question (at least 20% of content bypassed or replaced) and the Plan v3 §7 / Work Plan §8 measure (at least 70% of learners on distinct paths) are both measured per module.
  - Objective 2's "microlearning recommendations at sub-unit granularity" (Proposal §1.4) is met by one H5P unit per module, with H5P interactions inside it. Routing at the level of sub-unit competencies is not met. State this as a limitation.
- **Alternatives:** keep the custom router (Option 3, excluded).
- **Approver:** **Pending: advisory team**.
- **Status:** Proposed.

### D-10: Cloud model for the pilot with caps; local Ollama for development only

- **Date:** 2026-10-25
- **Decision:**
  - The pilot uses a cloud model through a core AI provider instance (OpenAI-compatible). The instance has user and site rate limits, the assistant has a prompt-length cap, and spend is monitored against the cost ceiling.
  - The Moodle admin enters the API key. It never goes in code or in this repository.
  - Ollama (local, 3B to 8B models) is for development and for the open-model column of the evaluation matrix. It is not used in production.
- **Rationale:**
  - Plan v3 §3.2 puts "Production-grade local LLM deployment on Africa CDC servers" out of scope and says to use "cloud or open API endpoints during the pilot".
  - Plan v3 §5.4: "Cost is capped through quotas and prompt-length controls".
  - Proposal §4.2: "Cloud AI API usage (pilot fallback)" with a ceiling of USD 3,000.
  - The Phase 2 build ran `llama3.2` (3B) in production, against plan.
- **Data protection:** no learner identity is sent in prompts, only the question and the approved passages.
  - **Open:** the provider's data-retention terms must be checked against the Africa CDC Data Protection and Privacy Framework (Plan v3 §10). Proposal §3.6 says "No learner data are processed on personal hardware" and data stays "on Africa CDC infrastructure". Questions sent to a cloud API need explicit clearance in the ethics protocol.
- **Model choice:** the evaluation matrix (2 open, 2 cloud; Plan v3 §6 Sprint 3) is run with the 50-question audit harness in Sprint 6 (D-11).
- **Approver:** Fellow. **Pending: advisory team** (vendor and budget), plus ethics.
- **Status:** Approved by the fellow.

---

## Schedule and evaluation

### D-11: BASIC go/no-go moved from Sprint 5 to the end of Sprint 6

- **Date:** 2026-10-25
- **Decision:**
  - The BASIC readiness decision moves from Sprint 5 (October 2026) to the end of Sprint 6 (November 2026). It is taken against the Plan v3 §7 criteria: accuracy of 95% or more on the 50-question set, and 100% traceability.
  - The bias audit (3 language groups, 5 topic areas, Proposal §3.9) moves with it.
  - The DataCurso decision (D-08) and the ethics clearance check stay in Sprint 6.
- **Rationale:**
  - BASIC must be rebuilt (D-04). There is no valid audit result to decide on yet.
  - Proposal §3.9 sets the bias audit at "Sprint 5 (October 2026)", and Proposal §4.1 Sprint 5 says "Go/no-go decision against defined criteria". Both slip by one sprint.
  - Sprint 9 launch (February 2027) is unchanged. Sprints 7 and 8 absorb the ADAPTIVE build.
- **Alternatives:** go/no-go on the old build. Rejected: it fails the plan (no real citations, no audit).
- **Approver:** Fellow. **Pending: advisory team** and the host site supervisor (milestone change).
- **Status:** Approved by the fellow.

### D-12: Phase 2 "cohort" results reclassified as synthetic system testing

- **Date:** 2026-10-25
- **Decision:**
  - `COHORT_DE_EVALUATION_REPORT.md`, `cohort_de_evaluation.json`, `GAP_DETECTION_PROTOCOL_REPORT.md` and `PHASE_2_FINAL_COMPLETION_REPORT.md` (8 September 2026) describe 6 test accounts with scripted attempts (`simulate_learner_attempt.py`). The reported figures are 83.3% bypass and 100% routing accuracy.
  - These are reclassified as **synthetic system testing**. They are not learner evidence and must not be reported as pilot, beta or cohort results.
- **Rationale:**
  - The Proposal §3.2 design is a pre-post within-subject pilot with real enrollees from Sprint 9, after consent (§3.9).
  - No consented participant data exists yet.
  - Some synthetic personas use real people's names. For example, "Adam Terefe (U5)" is an advisory team member. They are test accounts, not participants.
- **Approver:** Fellow. **Pending: advisory team** (to note it in the Sprint review report).
- **Status:** Approved by the fellow.

### D-13: Old build kept running until beta; synthetic data purged before the Sprint 9 baseline

- **Date:** 2026-10-25
- **Decision:**
  - `block_ollama_chat`, the `afirst_*` raw-SQL tables and the test learners stay on production course 2 until the Sprint 8 beta.
  - At the Sprint 8 switchover the old block is hidden on course 2 (`DEPLOYMENT.md`).
  - **Before the Sprint 9 baseline**, the synthetic Phase 2 data is purged: test-learner attempts, `afirst_ai_interaction_log`, `afirst_ai_escalation`, `afirst_microlearning_log`, learner profile rows and the test accounts' enrolments.
  - The purge is a separate production step. It needs the fellow's explicit go-ahead at the time and a full backup first.
- **Rationale:** avoids disrupting the current environment while the rebuild is developed locally. The purge keeps the baseline (Proposal §3.2, §3.6) free of synthetic records.
- **Alternatives:** purge now. Rejected by the fellow, so the old build can be demonstrated until beta.
- **Approver:** Fellow. The purge execution needs a fresh go-ahead.
- **Status:** Approved by the fellow. Purge **pending**.

## Platform robustness, packaging and scale (2026-11-04)

### D-14: Answers without a valid citation are withheld; audit harness built

- **Date:** 2026-11-04
- **Decision:**
  - If the model answers but cites no supplied passage, the assistant shows a refusal (`uncited`) instead. The model's text stays in the log for reviewers.
  - The learner's question is fenced in `<question>` tags in the prompt and must not change the rules (prompt-injection hardening).
  - A source that yields no passages cannot be approved. A repeated report on the same answer reuses the open escalation.
  - `blocks/afirst_assistant/cli/run_audit.php` implements the harness specified in `evaluation/README.md`. By default it deletes its own log rows, so audit traffic never mixes with learner data.
- **Rationale:** Plan §7 requires 100% traceability, and Prop §1.4 objective 1 requires "traceable citations on every response". Before this change an uncited answer was shown as answered. The Sprint 6 go/no-go (D-11) needs a harness.
- **Alternatives:** show uncited answers with a warning. Rejected: it breaks the objective.
- **Approver:** Fellow. **Pending: advisory team** (to note it).
- **Status:** Proposed.

### D-15: Docker packaging for any Moodle site

- **Date:** 2026-11-04
- **Decision:**
  - `deploy/docker/` builds one image, holding pinned Moodle `v5.0.11`, the plugin and the tools, with `init`, `web` and `cron` roles.
  - A compose stack adds PostgreSQL, Redis sessions and a Caddy load balancer. The web tier scales with `--scale web=N`.
  - Configuration comes only from the environment and Docker secrets.
- **Rationale:** the fellow asked for a dockerised platform that can scale and serve any course. The handover (Plan §11) and replication guide (Prop §3.10) need a reproducible deployment.
- **Alternatives:** keep installing the plugin into the existing VM only. That is still supported ([`DEPLOYMENT.md`](DEPLOYMENT.md)).
- **Open:** whether the production site at `10.215.70.14` moves into this stack, and when. That is a migration (backup, restore, DNS or IP change) needing the Africa CDC administrator and a window. It is not part of Sprint 6.
- **Approver:** Fellow. **Pending: Africa CDC server administrator** for any production use.
- **Status:** Proposed.

### D-16: Course-agnostic layout and onboarding tools

- **Date:** 2026-11-04
- **Decision:**
  - Each course's data lives in `courses/<key>/`. The pilot course is `courses/ds4h/`.
  - `generate_course_config.php` drafts the adaptive config from a live course.
  - `import_sources.php` bulk-loads approved texts.
  - The assistant's per-course index is cached in a Moodle application cache, purged on every source change.
- **Rationale:** scale to more courses without hand-copying cmids or re-reading every passage on each question.
- **Approver:** Fellow.
- **Status:** Proposed.

### D-17 (proposed, not implemented): cross-lingual retrieval

- **Date:** 2026-11-04
- **Issue:** retrieval is lexical (BM25). French or Portuguese questions about English sources share almost no terms with them, so they are refused before the model is called. The bias audit (Prop §3.9, 3 language groups) is expected to show unequal answer rates (DQ7).
- **Options:**
  1. Translate the question to the source language with the same AI provider before retrieval. This costs one extra call, only when retrieval finds nothing.
  2. Add an embedding index with a multilingual model.
  3. Accept the limitation and report it.
- **Needs:** the fellow's choice. Option 1 also needs D-10/Q4 clearance, because question text already goes to the provider.
- **Status:** Open.

---

## Template

```
### D-nn: Title
- **Date:** YYYY-MM-DD
- **Decision:**
- **Rationale:** (cite Proposal or Plan § numbers)
- **Alternatives:**
- **Approver:** Fellow | pending: advisory team | <name, date>
- **Status:** Proposed | Approved | Superseded by D-nn | Reopened
```
