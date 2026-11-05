# Course assistant (block_afirst_assistant)

A Moodle 5.0 block that answers learner questions **only from course text a teacher has approved**, cites the passages it used, and refuses when the approved text does not cover the question. Built for the Africa CDC A-FIRST pilot (BASIC layer, Capstone Proposal objective 1).

## How it answers
1. The question is matched against passages cut from approved sources (BM25 keyword retrieval).
2. If no passage reaches the **grounding threshold** (site setting, default 3.0), the assistant refuses **without calling the AI model**.
3. Otherwise the best passages are numbered and sent, through Moodle's core AI subsystem (`generate_text`), with instructions to answer only from them, cite `[n]`, and reply `NOT_IN_COURSE` if they don't contain the answer. That reply becomes a refusal.
4. The answer is checked before it is shown. It is withheld if it cites no supplied passage (D-14), or if its content is not found in the passages it cites: under the **support threshold** (site setting, default 0.5) of its content words, or a number that appears in neither the cited passages nor the question (D-20). Withheld answers stay in the log for reviewers.
5. The learner sees the answer, the passages behind it (expandable), the AI disclaimer, and a **Report a problem** button.

No name or other identity is sent to the model. The AI provider, model, quotas and rate limits are configured by the site administrator in core (Site administration > General > AI).

## Requirements
- Moodle 5.0.
- An AI provider instance with the **Generate text** action enabled. Until then the block is hidden from learners.
- Learners must accept the core AI usage policy before first use.

## Roles
| Capability | Default | Allows |
|---|---|---|
| `block/afirst_assistant:use` | student, teacher | Ask and report |
| `block/afirst_assistant:managesources` | editing teacher, manager | Upload, approve, retire sources |
| `block/afirst_assistant:review` | editing teacher, manager | Review queue, unanswered questions, CSV export (learner ids pseudonymised) |

## Data
- `block_afirst_source` / `block_afirst_chunk`: approved documents and their passages. Retired sources are kept so past citations stay verifiable.
- `block_afirst_log`: every question with answer or refusal reason, cited passages, best retrieval score, model, tokens, latency and browser-reported connection type (for the bandwidth equity analysis).
- `block_afirst_escalation`: learner reports; reviewers are notified and have 48 hours.
- Privacy API: export and delete per learner and per course.

## Development
See `../../dev/env.sh` for the local docker Moodle. Tests:
```
vendor/bin/phpunit --testsuite block_afirst_assistant_testsuite
```
Build JavaScript with Moodle's Grunt from the Moodle root: `npx grunt amd --root=blocks/afirst_assistant`.
