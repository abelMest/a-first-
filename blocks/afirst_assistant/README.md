# Course assistant (block_afirst_assistant)

A Moodle 5.0 plugin that answers learner questions **only from course text a teacher has approved**, cites the passages it used, and refuses when the approved text does not cover the question. Built for the Africa CDC A-FIRST pilot (BASIC layer, Capstone Proposal objective 1).

## What learners see
- An **"Ask the course"** button at the bottom right of every page of the course, including inside SCORM modules and quizzes. No block needs to be added (D-21). It opens a side panel (a bottom sheet on phones).
- The panel knows where the learner is ("You are in Module 2.2"), favours that module's content, and offers **starter questions** for it.
- While waiting: "Searching the approved course material…", then "Found in Module 4.1. Writing the answer… (6 s)".
- Each answer: `[n]` citations that open the exact passage, the AI disclaimer, **Was this helpful?** (thumbs up/down) and **Report a problem**.
- **Follow-up questions** ("Can you give an example?") use the previous question to find passages. The conversation follows the learner between pages of the course in the same browser tab.
- Learner-facing text in English, French, Portuguese and Arabic (with the matching Moodle language pack installed).

## How it answers
1. The question is matched against passages cut from approved sources: **BM25 keyword search**, plus, when configured, **meaning-based search** with a local embedding model (hybrid retrieval, rank fusion). Passages from the learner's current module are boosted.
2. A passage can ground an answer if it clears the **grounding threshold** (keywords, default 3.0) or the **meaning threshold** (default 0.7). If none does, the assistant refuses **without calling the AI model**. A short follow-up is retried with the previous question.
3. Otherwise the best passages are numbered and sent, through Moodle's core AI subsystem (`generate_text`), with instructions to answer only from them, cite `[n]`, and reply `NOT_IN_COURSE` if they don't contain the answer. That reply becomes a refusal.
4. The answer is checked before it is shown. It is withheld if it cites no supplied passage (D-14), or if its content is not found in the passages it cites: under the **support threshold** (default 0.5) of its content words, or a number that appears in neither the cited passages nor the question (D-20). Withheld answers stay in the log for reviewers.

No name or other identity is sent to the model. The AI provider, model, quotas and rate limits are configured by the site administrator in core (Site administration > General > AI). Reaching a rate limit shows the learner a clear "please wait" message.

## Sources
- **Approved sources** page (link in the panel and block for teachers): upload `.txt`, `.md` or `.html`, check the passage count, approve or retire.
- **Import text from this course's SCORM modules**: reads Articulate Rise packages (and plain-HTML SCORM) and adds each module as a pending source to review and approve. CLI: `cli/import_scorm.php --courseid=N [--approve] [--dry-run]`.
- **Starter questions** per source (module), edited on the same page or loaded from JSON: `cli/set_suggestions.php --courseid=N --file=courses/<key>/assistant_suggestions.json`.

## Meaning-based search (optional, local)
1. `ollama pull nomic-embed-text` on the Ollama server.
2. Site administration > Plugins > Blocks > Course assistant: set **Embedding endpoint** (e.g. `http://127.0.0.1:11434`). Keep **Run the embedding model on CPU** on unless the server has a GPU to spare.
3. `cli/embed_sources.php --courseid=N` once per course (new and re-indexed sources are embedded automatically).
If the endpoint is unreachable, the assistant falls back to keyword search.

## Requirements
- Moodle 5.0.
- An AI provider instance with the **Generate text** action enabled. Until then the assistant is hidden from learners.
- Learners must accept the core AI usage policy before first use.

## Roles
| Capability | Default | Allows |
|---|---|---|
| `block/afirst_assistant:use` | student, teacher | Ask, rate and report |
| `block/afirst_assistant:managesources` | editing teacher, manager | Upload, import, approve, retire sources; starter questions |
| `block/afirst_assistant:review` | editing teacher, manager | Review queue, unanswered questions, helpful rate, CSV export (learner ids pseudonymised) |

## Data
- `block_afirst_source` / `block_afirst_chunk`: approved documents, starter questions, passages and their meaning vectors. Retired sources are kept so past citations stay verifiable.
- `block_afirst_log`: every question with answer or refusal reason, cited passages, best keyword score and meaning similarity, support score, module context, follow-up flag, rating, model, tokens, latency and browser-reported connection type (for the bandwidth equity analysis).
- `block_afirst_escalation`: learner reports; reviewers are notified and have 48 hours.
- Privacy API: export and delete per learner and per course.

## Development
See `../../dev/README.md` for the local docker Moodle. Tests:
```
vendor/bin/phpunit --testsuite block_afirst_assistant_testsuite
php admin/tool/behat/cli/run.php --tags=@block_afirst_assistant
```
Build JavaScript with Moodle's Grunt from the Moodle root: `npx grunt amd --root=blocks/afirst_assistant`.
