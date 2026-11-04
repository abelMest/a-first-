# A-FIRST adaptation, learning and barrier log

This is the running Developmental Evaluation record (see [`DE_FRAMEWORK.md`](DE_FRAMEWORK.md)). Add new entries at the bottom of each section. Never delete an entry; mark it superseded instead.

Evidence class: `synthetic` · `dev` · `beta` · `pilot` (DE_FRAMEWORK §4).

## A. Adaptations and learning

| ID | Date | Sprint | What we expected | What happened | What we learned | Adaptation / decision | Evidence class |
|---|---|---|---|---|---|---|---|
| A-01 | 2026-09-08 | 4 | A custom gap detector, router and local 3B chat would deliver the ADAPTIVE layer quickly. | Built and deployed on production course 2. Reported "all five gates validated". | Results came from 6 scripted test accounts. The 3B model's citations could not be verified. The build had become Option 3, which the plan excludes (Plan §3.2). | Rebuild on core Moodle plus one narrow plugin (D-04, D-09). Results reclassified (D-12). | synthetic |
| A-02 | 2026-10-25 | 5 | `mod_adaptivequiz`, DataCurso and the Nolej plugin would cover adaptive needs (Plan §5.1–5.2). | No Moodle 5.0 adaptive quiz. DataCurso is course-level only. The Nolej plugin isn't needed. | Vendor plans made before checking Moodle 5.0 compatibility were not reliable. | Core quiz and restrict access; Nolej web app (D-05, D-06, D-08). | dev |
| A-03 | 2026-10-25 | 5 | Go/no-go and bias audit in Sprint 5. | There was no valid build to audit. | Rebuilds need room in the schedule. | Moved to the end of Sprint 6 (D-11). | — |
| A-04 | 2026-10-25 | 5 | Phase 2 endpoints were safe for production. | Security review found unauthenticated log and profile endpoints, and credentials in source. | Security review must happen before a demo, not after. | D-01, D-02. Password rotation outstanding. | — |
| A-05 | 2026-11-04 | 6 | Project documentation would live with the code. | Plans, Phase 2 reports and the taxonomy were scattered across `C:\AFIRST_Backup`. The taxonomy script read a file outside the repo. | One repository is needed for replication (Prop §3.10). | All plan documents, DE material, Phase 2 archive and course data moved into `afirst-rebuild/`. | — |
| A-06 | 2026-11-04 | 6 | The assistant always cited when it answered. | Code review found that an answer with no valid citation was still shown as "answered". The documented audit harness did not exist. | Traceability has to be enforced in code, not left to the prompt. | Uncited answers withheld; harness built; 32 PHPUnit tests pass (D-14). | dev |
| A-07 | 2026-11-04 | 6 | The bias audit would test answer quality across languages. | Running the bias sample against one English passage in the dev stack: 0% of French and Portuguese prompts reached the model (lexical retrieval). | The equity risk sits in retrieval, before the model. | Cross-lingual retrieval options proposed (D-17). Measure it on the real corpus in the Sprint 6 bias audit. | dev |
| A-08 | 2026-11-04 | 6 | One VM install would be enough. | The fellow asked for a dockerised platform that scales and serves any course. | Packaging and per-course data are needed for handover and replication. | Docker stack, per-course layout and onboarding tools (D-15, D-16). Tested on 2 web replicas. | dev |

## B. Barrier and facilitator register (WP RQ5 / DQ1)

Type: `technical` · `vendor` · `infrastructure` · `governance` · `people` · `content` · `equity`.

| ID | Date seen | Type | Barrier (B) or facilitator (F) | Description | Effect | Response | Status |
|---|---|---|---|---|---|---|---|
| B-01 | 2026-10 | infrastructure | B | No GPU server. The local 3B model needed more RAM than the dev machine has with Docker running. | Production quality model not available locally. | Cloud provider with rate limits for the pilot (D-10), pending data-protection approval (Q4). | open |
| B-02 | 2026-10 | vendor | B | Planned plugins have no Moodle 5.0 release (adaptive quiz). | Lost a planned feature. | Core features (D-06). | resolved |
| B-03 | 2026-10 | content | B | SCORM packages report completion only, with no topic-level signal. | Sub-unit routing not possible from SCORM. | Per-module diagnostics plus H5P (D-09). Limitation reported. | accepted |
| B-04 | 2026-10 | governance | B | Ethics clearance status unknown. It is required by Sprint 6. | Risk to the Sprint 9 launch. | Escalate (Q1). | open |
| B-05 | 2026-10 | people | B | Solo developer (Plan §8 risk 1). | Rebuild compresses Sprints 7–8. | Narrow scope; plugins over custom code. | open |
| B-06 | 2026-06 | infrastructure | F | Africa CDC VM and Moodle admin access provisioned in-kind. | Fast iteration on a real environment. | — | — |
| B-07 | 2026-10 | technical | F | Moodle 5.0 core AI subsystem, restrict access and competencies cover most needs. | Less custom code to maintain. | D-03. | — |
