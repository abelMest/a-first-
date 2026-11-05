# Audit rehearsal: local llama3.2:1b (2026-11-04)

**Evidence class: `dev`.** A rehearsal of the Gate 1 audit ([`docs/STAGE_GATES.md`](../../../../../docs/STAGE_GATES.md)), not the gate itself. It tests the harness, the grounding threshold and the refusal design. It is not a result for the advisory team: the model is a 1B local model chosen only because it fits this machine, the 50 items are not yet reviewer-signed (`signoff_status = pending`), and nobody has graded the answers.

## Setup
- Local dev Moodle 5.0.11 (`dev/`), throwaway course "DS4H audit rehearsal", `block_afirst_assistant` 0.3.0.
- Corpus: the 9 Phase 2 module texts (`topic_*_clean.txt`), 176 passages. Approved inside the rehearsal course only; **not** a content-owner sign-off.
- Provider: Ollama `llama3.2:1b` on the host, the only enabled provider. Cross-lingual retrieval off.
- Grounding threshold set to 0.01 for the run, so every item reached the model; thresholds were then simulated offline from the recorded scores (`evaluation/threshold_sweep.py`). Restored to 3.0 afterwards.
- Run: `cli/run_audit.php --set=audit_set.csv`, 50 items, 4 min 18 s. Audit log rows were deleted by the harness after the run.

## Results (automatic measures only)
| Measure | Value | Gate 1 target |
|---|---|---|
| In-scope questions answered | 31 / 40 | — |
| In-scope answers whose expected source anchor is in the cited passages (auto traceability) | 25 / 40 (62.5%) | 100% traceability; ≥ 95% accuracy |
| Answers carrying at least one citation marker | 37 / 37 (100%) | — |
| Out-of-scope questions refused | 4 / 10 | all |
| Out-of-scope questions **answered** | 6 / 10 | 0 |
| In-scope questions wrongly refused | 9 (6 model declined, 3 uncited) | — |
| Latency | median 4.5 s, p90 7.8 s, max 12.2 s | — |

## Findings
1. **Citation markers are not proof of grounding.** Six out-of-scope questions were answered with invented facts, and each answer carried `[1]`–`[3]` markers, so the "uncited answers are withheld" rule (D-14) let them through. Examples: an oral rehydration salts dose for cholera (a clinical safety risk), a named "Director General of Africa CDC", a case fatality rate for the 2023 Malawi outbreak. All were wrong and none came from the passages.
2. **The retrieval threshold cannot be the main guard.** Out-of-scope questions scored up to 12.1 (median 8.4); in-scope ones from 3.9 (median 14.9). Any threshold that blocks most out-of-scope items also refuses many in-scope ones (at 9.5: 1 out-of-scope answered, but 15 in-scope refused). The 3.0 default blocks nothing in this set. See `threshold_sweep.txt`.
3. **A 1B model is far below the bar.** Even before grading, at most 25 of 40 answers could count as traceable. The plan already scopes local models to development (D-10); this confirms it.
4. **The harness works.** It ran end to end on the full corpus, wrote the per-item CSV and summary, and cleaned up its log rows.

## Implications for Gate 1
- Add a **support check** before an answer is shown: facts in the answer (at least numbers, names and key terms) must appear in the cited passages, or the answer is withheld. Re-run this rehearsal to measure it. **Done: run 2 below (D-20).**
- Keep the threshold low (it only filters questions with no overlap at all) and rely on the model decline plus the support check.
- Run the real audit with the cloud model chosen for the pilot, then have two reviewers grade accuracy.

## Run 2: with the support check (plugin 0.4.0, D-20)

Same course, corpus, model and threshold (0.01). Support threshold 0.5 (default). Files: `audit-ollama-llama3.2-1b-support.*`. 50 items, 5 min 37 s.

**Calibration first.** On run 1's answers (`evaluation/support_calibration.py`), the share of answer content words found in the cited passages separated the groups: invented out-of-scope answers 0.10–0.38, except one (0.67) that stated numbers absent from the passages (0.13, 2023); 24 of 25 anchored in-scope answers ≥ 0.60. Hence the rule: withhold below 0.5, or when a number is in neither the cited passages nor the question (whole numbers 0–10 ignored).

| Measure | Run 1 | Run 2 |
|---|---|---|
| Out-of-scope questions answered | 6 / 10 | 1 / 10, and that one states "not specified in the passages" with no invented fact |
| Out-of-scope refusal recall | 40% | 90% |
| In-scope answered / with expected anchor | 31 / 25 | 26 / 22 |
| Withheld as unsupported | — | 5 (3 out-of-scope, 2 in-scope) |
| Model declined | 10 | 14 |
| Latency median / p95 | 4.5 s / 9.3 s | 6.2 s / 11.6 s |

- The 3 out-of-scope answers it caught: an invented Africa CDC Director General (coverage 0.00), a CNN explanation for chest X-rays (0.09), Bayesian vs frequentist statistics (0.44).
- The 2 in-scope answers it withheld were wrong: a garbled list (0.27) and an answer that invented "a median age of 35 years" (0.55, unsupported number). **No answer with the expected anchor was withheld.**
- The drop in in-scope answers comes from the model declining more often (10 → 14), not from the check. A 1B model varies between runs; this is a reason to compare models on several runs, not one.
- Remaining gap: answers that say "not specified in the passages" in prose are shown as answers. They are harmless but count against the answer rate; a phrase list could map them to a refusal.
- Known limit: with cross-lingual retrieval on (D-17, off by default), only the number check applies, because an answer in French cannot be compared word for word with English passages. Measure this in the bias audit.
