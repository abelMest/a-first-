# Gate 1 reviewer packs

Built by `evaluation/make_reviewer_packs.py` from the temperature-0 runs of 6 November 2026 (`audit-ollama-llama3.2-3b-t0-run1.csv`, `bias-ollama-llama3.2-3b-t0.csv`) and the text of every passage the assistant cited (`../cited_passages.json`, course content only). Rebuild after any new audit run.

| File | For | What they do | Gate criteria |
|---|---|---|---|
| `Gate1_audit_grading_Reviewer_A.xlsx` | Reviewer A | Grade the 50 audit items (A1–A5), sign off each question, give a new anchor phrase for the 15 flagged items | 1.1 accuracy ≥ 95%, 1.2 traceability 100%, 1.3 out-of-scope refused |
| `Gate1_audit_grading_Reviewer_B.xlsx` | Reviewer B | The same, independently | as above |
| `Gate1_bias_review_External.xlsx` | External bias reviewer | Grade the 21 bias items (EN/FR/PT) and complete Part B (WHO principles) | 1.4 bias audit |

Each workbook has a "Read me" sheet with the rubric and an example; only yellow cells are filled in. The "Summary" sheet computes the Gate 1 figures live; the formulas were checked in Excel with test grades (44/50 correct gave 88%, NOT MET, as expected).

**Process.** The two reviewers grade independently and return their files to the fellow. Agreement is computed (items with the same outcome; Cohen's kappa on A1), disagreements are settled in a short consensus call, and the consensus grades go into `audit_set.csv` (reviewer columns and `signoff_status`) and the Gate 1 record in `docs/STAGE_GATES.md`. Reviewer names are recorded in the decision log only with their agreement.

**Re-anchoring.** For 15 answered items the expected quote was written from an older course text and is not in the cited passages, although the answer cites the expected module. Reviewers copy the matching words from the passages; the agreed anchors replace `expected_source` in `audit_set.csv`, and the audit is rerun.
