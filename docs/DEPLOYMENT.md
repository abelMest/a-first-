# A-FIRST Production Deployment (staged: BASIC, then ADAPTIVE)

This is the rollout of the rebuilt layers to the Africa CDC Moodle 5.0 site (VM `10.215.70.14`, course 2 "Data Science for Health"). The layers go out in sequence (D-19, [`STAGE_GATES.md`](STAGE_GATES.md)):

- **Stage 1, BASIC** (after Gate 1, the Sprint 6 go/no-go): sections 0–4, steps 5.5–5.8, 6, 7 (except 7.6) and 8.
- **Stage 2, ADAPTIVE** (Sprints 7–8, before the Gate 2 beta with 3 to 5 volunteers): steps 5.1–5.4 and smoke test 7.6, with a fresh backup (section 1) first.
- **READINESS** (`report/afirst_readiness`) is **not** part of this runbook. It stays parked until Gate 3.

> **No step here runs before its gate has passed.** Each production change needs the fellow's go-ahead at the time (DECISION_LOG D-13). Everything is first rehearsed on the local docker Moodle 5.0 (`dev/`), using a restored copy of course 2.

**Roles**

| Code | Who |
|---|---|
| **F** | Fellow (Dr. Abel Mestie Mekonnen) |
| **A** | Africa CDC Moodle/server administrator |
| **C** | Content owner (Surveillance Division) |
| **S** | Host site supervisor (Judith Nguimfack) |

**Notation:** "F → A" means F prepares and A executes.

## 0. Preconditions (go/no-go for the deployment itself)

| # | Check | Who |
|---|---|---|
| 0.1 | The BASIC go/no-go passed at the end of Sprint 6: ≥ 95% accuracy and 100% traceability on the 50-question audit (Plan v3 §7, D-11) | F, advisory team |
| 0.2 | The plugin release is tagged in git. PHPUnit and Behat pass on Moodle 5.0. `version.php` `$plugin->version` is recorded below | F |
| 0.3 | The rehearsal on the local docker Moodle, using this runbook, is complete with no open critical issues | F |
| 0.4 | The approved sources (storyboards and topic texts) are signed off for upload | C |
| 0.5 | The ethics status allows a beta with volunteers. The cloud provider's data terms are cleared (D-10) | F, S |
| 0.6 | A maintenance window is agreed, and learners are told about it | F, A |
| 0.7 | The rotated SSH and DB passwords are in use (D-02) | A |

Plugin release deployed: `__________`. Date: `__________`.

## 1. Backup

| # | Step | Who |
|---|---|---|
| 1.1 | Enable maintenance mode: `sudo -u www-data php admin/cli/maintenance.php --enable` | A |
| 1.2 | Make a full DB dump with `mysqldump --single-transaction` or `pg_dump` (whichever the site uses). Name it `afirst_db_pre_sprint8_YYYYMMDD.sql.gz` | A |
| 1.3 | Archive moodledata (`filedir`, at minimum) and the Moodle code directory | A |
| 1.4 | Make a course backup of course 2 (`.mbz`, with user data) as an extra rollback path | A |
| 1.5 | Verify that the backups are readable. Record their sizes and checksums. Store them on Africa CDC infrastructure only (Proposal §3.6) | A |

## 2. Plugin install

| # | Step | Who |
|---|---|---|
| 2.1 | Copy `blocks/afirst_assistant/` from the tagged release to `<moodle>/blocks/afirst_assistant/`. Ownership must match the web server user | F → A |
| 2.2 | Run `sudo -u www-data php admin/cli/upgrade.php --non-interactive`. Check that the output lists `block_afirst_assistant` with no errors | A |
| 2.3 | Purge caches with `php admin/cli/purge_caches.php` | A |
| 2.4 | Check that **Site administration > Plugins > Plugins overview** shows the expected version | F |

## 3. AI provider instance (the cost cap)

| # | Step | Who |
|---|---|---|
| 3.1 | Go to **Site administration > General > AI > AI providers** and create a provider instance (OpenAI or an OpenAI-compatible endpoint, per D-10). Name it `A-FIRST pilot` | A |
| 3.2 | **The admin enters the API key directly in the form.** The key is never sent to the fellow, never committed, and never put in chat or e-mail. Use a key scoped to a dedicated project, with a monthly spend limit set in the vendor's billing console | **A only** |
| 3.3 | Turn on **Set site-wide rate limit** and **Set user rate limit**. Proposed starting values: 300 requests per hour site-wide and 20 requests per hour per user. Adjust after the beta. Record the final values in the decision log | A (values from F) |
| 3.4 | Under the instance's **Generate text** action, set the model chosen by the evaluation matrix. Leave the other actions (image generation) disabled | A |
| 3.5 | Make sure no other provider instance (for example a leftover Ollama instance) is enabled for `generate_text` in production (D-10) | A |
| 3.6 | Set the assistant's maximum question and prompt length (plugin setting) and its retrieval threshold. The threshold is the value set from the Sprint 6 audit (default 3.0) | F |

Cost check: the Proposal §4.2 ceiling is USD 3,000 for the cycle. F reviews usage in the vendor console every week from beta onward and logs it.

## 4. AI policy and consent

| # | Step | Who |
|---|---|---|
| 4.1 | Check that core **AI policy acceptance** is shown to learners on first use of any AI feature, including the assistant. The plugin checks this before its first call | F |
| 4.2 | Go to **Site administration > Users > Privacy and policies > Manage policies** (`tool_policy`) and confirm the site policy handler is "Policies" | A |
| 4.3 | Add the A-FIRST pilot consent policy as a version, using the text from the approved ethics information sheet. Make it **optional** (consent can be refused or withdrawn, giving an opt-out path; Plan v3 §10, Proposal §3.3). It must cover the AI disclosure wording from Proposal §3.9. Activate it | F drafts, A activates |
| 4.4 | Confirm that the disclaimer on every answer reads exactly: "This response is generated by AI and grounded in approved course content. Verify operational decisions with a qualified supervisor." (Proposal §3.9) | F |

## 5. Course 2 configuration

Steps 5.1–5.4 are **Stage 2 (ADAPTIVE)** and run only after Gate 1 has passed and BASIC is live. Steps 5.5–5.8 are Stage 1 (BASIC).

| # | Step | Who |
|---|---|---|
| 5.1 | **[Stage 2]** Import the competency framework `A-FIRST-DS4H-V1` CSV with **Site administration > Competencies > Import competency framework** (`tool_lpimportcsv`). Link the competencies to course 2 | F (A if permissions require) |
| 5.2 | **[Stage 2]** Import the 9 H5P units into the course content bank and add them as H5P activities | F |
| 5.3 | **[Stage 2]** Add the 9 per-module diagnostics. Keep the 27-question placement test for the baseline and endline | F |
| 5.4 | **[Stage 2]** Run the course configuration script, dry run first: `php course-setup/apply_course_config.php --course=2 --dry-run`, then for real. Then work through `course-setup` checklist by hand (fast-track ≥ 90%, remediation < 80%) | F → A (CLI) |
| 5.5 | Add the `A-FIRST assistant` block to course 2. Upload the approved sources (step 0.4) to the block's source area and wait for the indexing adhoc task (`php admin/cli/adhoc_task.php --execute` if cron is slow) | F |
| 5.6 | Assign `block/afirst_assistant:review` to the reviewers (fellow plus a designated content reviewer) for the 48-hour escalation SLA (Proposal §3.9) | F |
| 5.7 | Enable **AI placements > Course assistance** (summarise and explain) if the advisory team agrees | A |
| 5.8 | Add the Feedback activity (NPS, trust, usability) for the midpoint and endline. Keep it hidden until Sprint 10 | F |

## 6. Hide the old build

| # | Step | Who |
|---|---|---|
| 6.1 | On course 2, hide (do **not** delete) the `block_ollama_chat` instance. Leave the plugin installed until after the pilot | F |
| 6.2 | Disable the Phase 2 custom endpoints (`log_interaction.php`, `profile_api.php`) and any Phase 2 scripts or cron entries that write to `afirst_*` tables | A |
| 6.3 | The synthetic-data purge (D-13) is **not** part of this runbook. It is a separate approved step before the Sprint 9 baseline | — |

## 7. Smoke test (with maintenance mode off and a test student account, not a pilot participant)

| # | Check | Expected | Who |
|---|---|---|---|
| 7.1 | First use | AI policy prompt appears and must be accepted | F |
| 7.2 | An in-scope question (from the audit set) | Answer with [n] citations; each expands to its passage; disclaimer shown | F |
| 7.3 | An out-of-scope question | Refusal; no model call; logged as refused | F |
| 7.4 | "Report a problem" | Escalation appears in the reviewer queue with SLA indicator; reviewer gets a notification | F |
| 7.5 | Rate limit | Exceeding the user limit gives a clear message, not an error page | F |
| 7.6 | **[Stage 2]** Diagnostics | A score of ≥ 90% shows the fast-track route; below 80% on the knowledge check unlocks the H5P unit | F |
| 7.7 | Mobile width and low bandwidth (browser throttling) | Usable; connection type recorded in the log | F |
| 7.8 | CSV export | Learner ids are pseudonymised | F |
| 7.9 | Old block | Not visible to students | F |
| 7.10 | Logs | No PHP errors in the web server log or in **Reports > Logs** | A |

Sign-off: F `____` A `____` Date `____`. Then disable maintenance mode (A).

## 8. Rollback

Trigger: a critical smoke-test failure, or data integrity in doubt. The decision is made by F with A.

| # | Step | Who |
|---|---|---|
| 8.1 | Enable maintenance mode | A |
| 8.2 | **Light rollback** (configuration problem only): remove the assistant block from course 2, disable the provider instance, unhide `block_ollama_chat` if it is needed until the fix | F, A |
| 8.3 | **Plugin rollback:** uninstall with `php admin/cli/uninstall_plugins.php --plugins=block_afirst_assistant --run`, then remove the code directory. Note: this deletes the plugin's tables and logs | A |
| 8.4 | **Full rollback:** restore the step 1.2 DB dump and the step 1.3 moodledata and code, then purge caches | A |
| 8.5 | Disable maintenance mode. Log the incident and the cause in `DECISION_LOG.md` | A, F |
