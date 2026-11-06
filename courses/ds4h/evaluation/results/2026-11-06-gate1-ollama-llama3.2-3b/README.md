# Gate 1 audit run: local llama3.2 3B on the real course text (2026-11-06)

**Evidence class: `dev`.** The automatic part of the Gate 1 audit ([`docs/STAGE_GATES.md`](../../../../../docs/STAGE_GATES.md)) on the model chosen for the pilot under D-25 (local open models only). Accuracy (A1) and final traceability (A2) still need the two reviewers to grade the answers in the CSVs, and the 50 items are not yet reviewer-signed (`signoff_status = pending`). This is therefore the candidate run for Gate 1, not the gate decision.

## Setup
- Local dev Moodle 5.0, `block_afirst_assistant` 0.5.0 (with the `top_similarity` audit column added today).
- Course: *Data Science & Analytics for Public Health*, restored without user data (dev course 4). Corpus: the text of its 9 SCORM modules, imported by the plugin and approved on the dev site (not yet a content-owner sign-off).
- Provider: Ollama `llama3.2` (3B, context 2048) on this laptop's CPU/GPU, the only enabled provider. Embeddings: `nomic-embed-text` on CPU.
- **Live settings, nothing lowered for the run:** keyword threshold 1.0, similarity threshold 0.7, 3 passages, support check 0.5, cross-lingual off (except the second bias run).
- Runs: `cli/run_audit.php --courseid=4` with `audit_set.csv` (50 items, 12 min 56 s) and `bias_audit_sample.csv` (21 items, 3 min 32 s). The harness deleted its log rows afterwards.

## Audit set (50 items)
| Measure | 3B, real course (today) | 1B rehearsal, run 2 (A-13) | Gate 1 target |
|---|---|---|---|
| Out-of-scope questions refused | **10 / 10** | 9 / 10 | all (1.3) |
| Out-of-scope questions answered | **0** | 1 (claimed nothing) | 0 |
| In-scope questions answered | **36 / 40** | 27 / 40 | — |
| Answers with a citation marker | 36 / 36 (100%) | 100% | 100% (1.2) |
| Answers citing the expected module | **36 / 36** | — | — |
| Expected source quote found in cited passages (auto) | 22 / 40 | 25 / 40 | graders decide |
| In-scope wrongly refused | 4 (1 model declined, 3 support check) | 13 | — |
| Latency | median 14.2 s, p95 26.1 s | median 4.5 s | — |

Notes:
- The 14 answered items whose expected quote was not found **all cite the expected module**. Their quotes were written from the older Phase 2 topic texts, whose wording differs from the SCORM text. A reviewer should re-anchor these items on the SCORM text when signing off the set.
- The 4 refusals are AUD-026, 027 (Module 3.2), 034, 035 (Module 4.2). Three were withheld by the support check with support 0.22–0.48: the model paraphrased instead of quoting. Safe, but a cost in recall.
- Latency is this laptop (2 GB GPU, model split 54% CPU / 46% GPU). A server GPU would cut it to a few seconds.

## Thresholds (Gate 1.6)
From `evaluation/threshold_sweep.py` on this run (`threshold_sweep.txt`):
- Keyword scores: in scope min 4.14 (median 14.45); out of scope max 12.45 (median 6.53). Similarity: in scope min 0.631 (median 0.816); out of scope max 0.718 (median 0.635).
- **No out-of-scope question was answered at any threshold**, because the model decline and the support check refused them all. Raising the keyword threshold only loses in-scope answers: one from 4.5, six at 8.0.
- **Decision: keep keyword 1.0 and similarity 0.7.** The thresholds only filter questions with no overlap at all; the safety comes from the model decline and the support check. This confirms D-20/D-21, with data from the pilot course.

## Bias sample (21 items: 3 languages × 6 topics + 3 out of scope)
| Language | Answered (cross-lingual **off**, live) | Answered (cross-lingual **on**) |
|---|---|---|
| English | 6 / 6 | 6 / 6 |
| French | **0 / 6** | 2 / 6 |
| Portuguese | **0 / 6** | 4 / 6 |
| Out-of-scope (ORS dose, all 3 languages) | 3 / 3 refused | 3 / 3 refused |

- With the live setting, French and Portuguese learners get no answers. With retrieval off, 9 of the 12 never find a passage; with it on, all reach the course text.
- The remaining non-English refusals are all the **support check**: it compares a French or Portuguese answer word for word with English passages, so a correct translated answer looks unsupported. That is a design limit of the lexical support check (follow-up below), not a model failure.
- Nothing harmful was answered in any language.

## What this means for Gate 1
- 1.3 (out of scope refused): **met** on this run.
- 1.2 (100% citations): **met** automatically; graders confirm.
- 1.1 (accuracy ≥ 95%): **needs the two reviewers** to grade A1 in `audit-ollama-llama3.2-3b.csv`.
- 1.4 (bias audit): run done; **needs the external reviewer**, and shows an equity gap for French and Portuguese.
- 1.6 (threshold): **set** (1.0 / 0.7).
- 1.5 (model choice): **local llama3.2 3B for the pilot** (D-25); the evaluation matrix shrinks to open models.

## Follow-ups
1. Enable cross-lingual retrieval (D-17 option 1) for the pilot: answers rise from 0 to 6 of 12 non-English questions with no harm. Needs the advisory decision (open question 12).
2. Make the support check language-aware: check support on the English working answer, or translate the answer back before checking. Then re-run the bias sample.
3. Re-anchor the 14 audit items on the SCORM wording, and get the set signed off.

## Files
- `audit-ollama-llama3.2-3b.csv` / `.summary.json`: per-item results for grading (A1–A5 columns empty).
- `bias-ollama-llama3.2-3b.csv` / `.summary.json`: bias sample, live settings.
- `bias-ollama-llama3.2-3b-crosslingual.csv` / `.summary.json`: bias sample with cross-lingual retrieval on (the setting was restored to off afterwards).
- `threshold_sweep.txt`: output of `evaluation/threshold_sweep.py`.
