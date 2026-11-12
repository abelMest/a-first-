# A-FIRST demo script: Phase 2, ADAPTIVE (local instance, 2026-11-13)

**What you're showing:** the ADAPTIVE layer on the real course, *Data Science & Analytics for Public Health* (dev course 4, restored without any learner data). Learners who already know a module can skip ahead; learners who struggle get a targeted review and a second try. Everything is built from **standard Moodle features** (restrict access, completion, H5P, competencies), set up by scripts, with no custom learning engine. About **15 minutes**.

> Evidence class: **dev**. Every learner shown is a scripted test account. No real learner has used ADAPTIVE yet.

## The evening before (10 minutes)
- [ ] Docker Desktop running; http://localhost:8710 opens.
- [ ] Ollama running (for the assistant recap): `ollama ps` lists `llama3.2`. If not, start the Ollama app, then warm it:
  `curl http://127.0.0.1:11434/api/generate -d "{\"model\":\"llama3.2\",\"prompt\":\"\",\"keep_alive\":\"6h\"}"`
- [ ] Reset the demo learners (safe to repeat; run it again after any rehearsal):
  `cd /c/AFIRST_Backup/afirst-rebuild && bash dev/demo_phase2.sh`
- [ ] Open **four browser windows** (separate profiles or private windows), each on http://localhost:8710/course/view.php?id=4. Learner password: `AFIRST_DEV_STUDENT_PASS` in `dev/.env`; teacher: `AFIRST_DEV_TEACHER_PASS`.

| Window | Log in as | State |
|---|---|---|
| A | `newlearner` | Nothing done yet: every Knowledge Check locked, diagnostics open |
| B | `fasttrack` | 10/10 on the Module 2 diagnostic |
| C | `remediation` | Finished Module 1.1, then 1/5 (20%) on its Knowledge Check |
| D | `teacher1` | Teacher view of the same course |

- [ ] Do one full rehearsal, then run `bash dev/demo_phase2.sh` again so window C starts with the retake locked.

## 1. Where Phase 1 left us (1 minute)
The course assistant answers only from approved course text, cites it, and refuses everything else. Gate 1 candidate run on this course with a local model: **0 of 10 out-of-scope questions answered, 37 of 40 course questions answered**, all citing the right module; **English, French and Portuguese 6/6 each**. Waiting on reviewer grading, the external bias review, ethics and the advisory decision.

Optional (30 s): in any window, click **Ask the course** and ask *"What are the dimensions of data quality?"*.

## 2. The problem ADAPTIVE solves (1 minute)
Everyone gets the same sequence: an experienced epidemiologist sits through Module 2 they already know, and a learner who fails a Knowledge Check gets no help except "try again". ADAPTIVE adds two paths to the same course:
- **Fast-track**: prove you know a module, skip to its Knowledge Checks.
- **Remediation**: struggle on a Knowledge Check, get a short targeted review, then a retake.

## 3. A new learner (window A, 2 minutes)
- Scroll the course page: under each module, the **Knowledge Check** shows *"Not available unless any of: the module is marked complete, or you achieve a certain score in the Module N diagnostic"*.
- In **Pre Course Activities**: the **Placement Test** (Module 1 diagnostic) and the **Module 2, 3, 4 diagnostics (optional)**: 10 questions each from the course's own question bank.
- Say: *a score of 90% or more opens that module's Knowledge Checks straight away. Nobody is forced to take them.*

## 4. Fast-track (window B, 2 minutes)
- `fasttrack` scored **10/10 on the Module 2 diagnostic**.
- Scroll to Module 2: **Module 2.1 and 2.2 Knowledge Checks are open**, although the Module 2 content has not been opened. Module 1, 3 and 4 Knowledge Checks are still locked.
- Say: *the learner still has to pass the Knowledge Check, so the competency evidence is the same; only the time is saved.*

## 5. Remediation (window C, 4 minutes: the core of the demo)
1. `remediation` finished Module 1.1 and scored **20%** on its Knowledge Check (pass mark 70%).
2. Under the Knowledge Check a new activity has appeared: **Review: Module 1.1**. Other learners don't see it.
3. Below it, **Module 1.1 Knowledge Check (retake)** is locked: *"Not available unless: the activity Review: Module 1.1 is marked complete"*.
4. Open **Review: Module 1.1** (H5P): the learning objectives, key points (accordion) and flashcards of the module's definitions.
   Say: *every line is copied word for word from the module's own SCORM text by a script; nothing is written by AI. The content owner signs each unit off in `REVIEW.md` before learners see it.*
5. Go back to the course page and refresh: the **retake is now open**.

## 6. What the teacher sees (window D, 3 minutes)
- Open the **Module 1.1 Knowledge Check (retake)** → *Settings* → **Restrict access**: the rules are ordinary Moodle conditions. Nothing proprietary; they back up and restore with the course.
- **Competencies** (course page → More → Competencies): the A-FIRST framework, 9 module competencies with 35 sub-competencies, linked to the Knowledge Checks and retakes, so passing either gives evidence.
- **Pre Course Activities**, hidden from learners (eye-slash icon): the **pilot study instruments**: information sheet (draft), consent, starting questionnaire, baseline and endline tests (18 questions), mid and end questionnaires. Consent gates all of them. They wait for the advisory team and ethics approval (D-29).
- Optional: **Final Course Activities**: the hidden **readiness scenario** (Phase 3 preview, a branching cholera-cluster Lesson) and *Reports → Readiness* showing the five result types for test learners.

## 7. Numbers, status and asks (2 minutes)
| Built on dev | Count |
|---|---|
| Module diagnostics (10 questions each) | 4 |
| Review units (H5P, word for word from SCORM) | 9 |
| Retakes | 9 |
| Access rules set by `apply_course_config.php` (re-running changes nothing) | 27 |
| Competencies imported (9 module, 35 sub) | 44 |
| Pilot instruments (hidden) | 7 |

- **Production (2026-11-11/12):** the assistant plugin is installed on the Africa CDC server, configured with the server's own local model; nothing is shown to learners (D-28). A staff-only copy of the course is being restored there for testing.
- **Decisions needed from the advisory team:**
  1. Gate 1 go/no-go once the two reviewers have graded the answers (open questions 12, 18, 19).
  2. ADAPTIVE design: remediation below the 70% pass mark, four 10-question diagnostics, review units copied from the SCORM text (open question 15).
  3. Who signs off the 9 review units (open question 17), and which quiz is the course's final assessment (16).
  4. The pilot instruments and the 18-question test set (open question 20).
- **Next:** Gate 2 beta with 3–5 volunteers (January), pilot launch in February 2027.

## If something goes wrong
- **A locked activity should be open, or the other way round:** the demo state was changed during rehearsal. Run `bash dev/demo_phase2.sh` again (about 1 minute) and refresh.
- **The retake stays locked after viewing the review:** refresh the course page; completion on view is recorded when the H5P page loads.
- **The review unit shows a blank area:** wait a few seconds for H5P to load; if still blank, open it in a new tab.
- **"The assistant is unavailable right now":** Ollama stopped. Start the Ollama app and warm the model (command above).
- **Page doesn't load:** Docker Desktop stopped. Start it, then in Git Bash: `cd /c/AFIRST_Backup/afirst-rebuild/dev && . ./env.sh && afirst_up`.

## After the demo
- The review units and retakes are visible **on dev only** for the demo (the setup script shows them). Production keeps them hidden until the content owner approves them.
