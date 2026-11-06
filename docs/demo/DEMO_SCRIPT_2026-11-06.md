# A-FIRST demo script (local instance, 2026-11-06)

**What you're showing:** the BASIC layer, the source-grounded course assistant, running on Moodle 5.0 with a free local model (Llama 3.2 3B through Ollama). No cloud service is involved and no data leaves this laptop.

## Before you start (5 minutes before)
- [ ] Docker Desktop running; site opens at **http://localhost:8710**
- [ ] Ollama running and the model warm: in a terminal, `ollama ps` shows `llama3.2:latest` with "2 hours from now". If not, ask one question once to warm it up (the first answer after a cold start is slow).
- [ ] Logged in as **student1** (password in `dev/.env`, `AFIRST_DEV_STUDENT_PASS`), on the real course **Data Science & Analytics for Public Health**: http://localhost:8710/course/view.php?id=4 (restored from the course backup without any user data; the 9 SCORM modules and quizzes play). The simpler course "Data Science for Health" (id 2) is the fallback.
- [ ] Optional opener: open **Module 1.1** (SCORM) to show the real course content, then come back to the course page and ask the assistant about it.
- [ ] The **Ask the course** button is at the bottom right of every page of the course (no block drawer needed). Best opener: open **Module 4.1 Knowledge Check** (or any Module page) so the panel shows "You are in Module 4.1" and its starter questions; click a starter and point at the progress line "Found in Module 4.1… Writing the answer".
- [ ] Show a follow-up: after an answer, ask "Can you give an example?". Then rate the answer with the thumbs, and move to another page: the conversation follows.
- [ ] Second browser window (or private window) logged in as **teacher1** (`AFIRST_DEV_TEACHER_PASS`) for the review queue.
- [ ] Tell the audience: answers take about **10–20 seconds** on this laptop's CPU. A GPU server would make them near-instant.

## 1. The problem (1 minute)
Learners need trustworthy answers from approved Africa CDC course content, not the open web. General chatbots answer fluently even when they are wrong. A-FIRST answers **only** from course text that a teacher has approved, shows its sources, and refuses when the course doesn't cover the question.

## 2. A grounded answer (3 minutes)
Ask, one at a time (tested today, all answered with full support):

1. **What are the dimensions of data quality?** → accuracy, completeness, timeliness, consistency, validity, citing Module 1.2.
2. **What is the difference between logistic regression and Poisson regression?** → binary vs count outcomes, citing Module 4.1.

Point out:
- the **[1] [2] markers**: click one and the exact course passage opens underneath;
- "retrieved, not cited" on passages the model was given but didn't use;
- the **AI disclaimer** under every answer (wording from Proposal §3.9).

Backup questions if needed: *What is data literacy?* · *What is data wrangling?* · *What is overfitting in machine learning?* · *What is the difference between Bag-of-Words and TF-IDF?*

## 3. Safety: it refuses what the course doesn't cover (3 minutes)
Ask:

3. **What is the recommended adult dose of oral rehydration salts for cholera?**
4. **Who is the current Director General of Africa CDC?**

Both are refused with the standard message. Explain the four safeguards:
1. no matching course text → refuse without calling the model;
2. the model must reply "not in course" when the passages don't answer;
3. no citation → answer withheld;
4. **support check**: the answer's content must actually be in the cited passages, or it is withheld.

The story behind safeguard 4 (Developmental Evaluation, DE A-12/A-13): in our rehearsal, a small model invented a cholera ORS dose and still added citation markers. We added the support check, and invented answers to out-of-scope questions fell from 6 of 10 to none, without losing any correct answer.

## 4. Human oversight (2 minutes)
- Under any answer, click **Report a problem with this answer**, choose a reason, send. The learner sees "a reviewer will check this answer within 48 hours".
- Switch to the **teacher1** window → open the assistant panel → **Review queue** link at the bottom of the panel (it also shows the helpful rate from the thumbs):
  - the report with its 48-hour deadline and a Resolve box;
  - headline numbers (questions, unanswered rate, answers citing a source);
  - the **Unanswered questions** tab: the content-gap log. Example to mention: *"What is an epidemic curve used for?"* found no match today, which tells the content team the course text words it differently.
- **Approved sources** (link at the bottom of the panel for teachers; it has the **Import text from this course's SCORM modules** button and per-module starter questions): teachers upload course text and approve it before the assistant can use it; retiring keeps old citations verifiable.

## 5. Where we are and what's next (2 minutes)
- Built in sequence: **BASIC → ADAPTIVE → READINESS**, each behind a gate (`docs/STAGE_GATES.md`).
- BASIC: built and tested (43 automated tests, browser tests, audit rehearsals). Next: the 50-question audit on the chosen local model, the bias audit, then the go/no-go at the end of Sprint 6.
- Decision today: **local open models, no cloud** (cost and data sovereignty; the plan's preferred option, Plan §5.4). Needs Africa CDC server capacity, ideally a GPU, for the pilot.
- ADAPTIVE (diagnostics, fast-track, remediation) and READINESS (scenario-based readiness) are prepared but not deployed until their gates.

## If something goes wrong
- **"The assistant is unavailable right now"**: Ollama stopped or restarted (it happened once before the demo). In a terminal run `ollama ps`; if `llama3.2` isn't listed, warm it up and ask again:
  `curl http://127.0.0.1:11434/api/generate -d "{\"model\":\"llama3.2\",\"prompt\":\"\",\"keep_alive\":\"2h\",\"options\":{\"num_ctx\":2048}}"`
- **A fair course question is refused**: very general wording ("what is health data?") scores low in keyword search. The demo site's grounding threshold is now 1.0 (was 3.0); rephrase with a course term if it still happens.
- **Very slow first answer**: the model was cold. Ask a backup question while it loads.
- **Page doesn't load**: Docker Desktop stopped. Start it, then in Git Bash: `cd /c/AFIRST_Backup/afirst-rebuild/dev && . ./env.sh && afirst_up`.

## After the demo
- Restart the containers stopped to free memory (other projects): the list is in Claude's scratchpad; ask Claude to restore them.
- Dev site debug output was turned off for the demo (`config.php` lines marked `// demo` in the container).
