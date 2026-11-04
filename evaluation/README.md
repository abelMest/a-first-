# A-FIRST evaluation instruments

The method, formats and tools here apply to any course. Each course keeps its own sets in `courses/<course>/evaluation/`; the files described below are the pilot sets in [`courses/ds4h/evaluation/`](../courses/ds4h/evaluation/). The harness is `blocks/afirst_assistant/cli/run_audit.php`.

These files measure the BASIC success criteria in the rebuild plan:

- At least 95% reviewer-graded accuracy on a 50-question set.
- 100% source traceability.
- A bias audit across 3 language groups and 5 topic areas.

| File | Purpose |
|---|---|
| `audit_set.csv` | 50 questions: 40 in scope (31 factual, 9 multi-step, spread over all 9 modules) and 10 out of scope, which the assistant must refuse. |
| `bias_audit_sample.csv` | 21 prompts: 7 prompt groups × English, French and Portuguese. Covers 5 topic areas, plus a sensitive-topic probe and a refusal-parity probe. |
| `fairness_checklist.md` | The per-response scoring rubric (A1–A5) and the system-level checklist adapted from the WHO principles. |
| `check_sets.py` | Checks that both CSVs parse, prints counts, and confirms that every anchor phrase appears in the course text. |

All CSVs are UTF-8 (no BOM), comma-separated, with RFC 4180 quoting and a header row.

## Where the answers come from

Every in-scope `expected_answer` comes only from the course text in `afirst_phase2_evaluated.tar.gz`, in the files `opt/afirst/content/topic_<m>_<n>_clean.txt`. The copies inside `storyboard_final.tar.gz` are byte-identical. No outside knowledge was used.

`expected_source` has the form `Module 2.1: "<verbatim anchor phrase>"`. The anchor phrase is copied exactly from that module's text, including the source's typos ("wranlging") and en dashes.

To re-verify the anchors after any edit, extract the archive into an empty folder outside the project, then run:

```bash
python -I check_sets.py ../courses/ds4h/evaluation <folder containing topic_*_clean.txt>
```

The questions were seeded from these sources:

- **`verify_all_modules.py`, Modules 1.1–3.2:** questions reworded to be answerable. That script's 4.1–4.3 queries describe an older syllabus (with a "capstone"), so the capstone query is reused as out-of-scope item AUD-049.
- **`test_mod4.py`:** the three Module 4 questions.
- **`benchmark_rag.py`:** the queries on governance, reproducibility, EDA and dashboards.
- **`competency_taxonomy_v1.json`:** the remaining questions, written from its learning objectives.

Of the taxonomy's 35 competencies, 35 are covered.

Known gaps in the course text:

- Module 4.2 announces "two broad types" of supervised learning (classification and regression), but the extract doesn't list them. No item depends on that list.
- Module 3.1's description of the two map types is also missing from the extract.

## Reviewer validation (two-reviewer sign-off)

Both CSV sets must be validated before the Sprint 9 baseline run.

`audit_set.csv` has these sign-off columns:

| Column | Values |
|---|---|
| `reviewer1_name`, `reviewer2_name` | Name or initials. Reviewer 1 should be a subject-matter expert (the content owner or a facilitator). Reviewer 2 should be someone independent of the item author. |
| `reviewer1_verdict`, `reviewer2_verdict` | `approve`, `revise` or `reject` |
| `reviewer1_comment`, `reviewer2_comment` | Free text. Required for `revise` or `reject`. |
| `signoff_status` | `pending` → `approved` when both verdicts are `approve`, or `disputed` when the reviewers disagree |

Each reviewer checks every row for the following:

1. **In-scope questions:**
   - The question is clear, and a learner of that module could ask it.
   - `expected_answer` is fully supported by the anchored passage and nearby text. Open the course text and read the context around the anchor. Nothing in the answer comes from outside the course.
   - `competency_id` matches the learning objective being tested.
   - `type` is right. `multi-step` means the answer needs two or more facts combined, or a fact applied to a scenario.
2. **Out-of-scope questions:** the topic really is absent from all nine modules, and refusing it is the desired behaviour.
3. **Bias sample:**
   - A native or fluent speaker confirms that the French and Portuguese prompts mean the same as the English one and sound natural. Portuguese uses the European/African norm.
   - Once confirmed, the reviewer deletes "Translation needs native-speaker check" from `notes`.

Resolve disputes by discussion. The content owner has the final say. Record any item replaced after sign-off in the decision log and re-run `check_sets.py`.

Keep `id`s stable. Never reuse an id. Retire an item by setting `signoff_status` to `rejected`. `run_audit.php` skips rejected rows.

## Input format for `run_audit.php`

The harness must select columns **by header name, not by position**, and ignore columns it doesn't know.

### `audit_set.csv`

| Column | Used by the harness | Notes |
|---|---|---|
| `id` | yes | Key for the results. Format `AUD-001` to `AUD-050`. |
| `module` | yes | `1.1` to `4.3`, or `none` for out-of-scope items. Traceability check: at least one cited passage should come from this module. |
| `competency_id` | reporting | `COMP-x.y.z` or `none`. |
| `question` | yes | Sent to the answerer exactly as written. |
| `expected_answer` | no | For human graders. Out-of-scope rows start with `REFUSE:`. |
| `expected_source` | optional | `Module x.y: "<anchor>"` or `NONE`. Automatic traceability pre-check: does any cited passage contain the anchor (after normalising whitespace and quotes)? A human still confirms. |
| `type` | yes | `factual`, `multi-step` or `out_of_scope`. Out-of-scope rows are expected to come back as refused. All others are expected to be answered. |
| `notes`, `reviewer*`, `signoff_status` | `signoff_status` only | Skip rows where `signoff_status` is `rejected`. For the official baseline, use only `approved` rows, through a flag such as `--approved-only`. |

### `bias_audit_sample.csv`

This file has the same structure, keyed by `id` (`BIAS-001`…). The harness uses:

- `prompt`, sent as the question.
- `type`, either `factual` or `out_of_scope`.
- `module`.
- `expected_source`.
- `language` and `topic_area`, for grouping.

`source_audit_id` links each prompt group to the matching audit item. The sensitive-topic group (BIAS-016 to BIAS-018) is a related probe, not a copy of AUD-027.

Bias rows have no reviewer columns. Their sign-off is recorded in the results sheet.

### Suggested results file (one row per item × provider)

The harness fills the automatic columns. The empty columns at the end are for the two graders, who follow `fairness_checklist.md`.

```
run_id,provider,model,item_id,set,language,type,module,status,refusal_reason,
answer,cited_passages,cited_modules,anchor_in_citations,latency_ms,top_score,
A1_accuracy,A2_traceability,A3_refusal,A4_harm,A4_flags,A5_language,grader,grader_comment
```

The automatic columns hold the following:

- `status` is `answered` or `refused`, as returned by `answer_result`.
- `cited_passages` is a list of passage ids.
- `cited_modules` lists the modules of the cited passages.
- `anchor_in_citations` is `1` if any cited passage contains the anchor, otherwise `0`.
- `top_score` is the best retrieval score, which is used to tune the refusal threshold.

The harness can compute these metrics before grading:

- **Refusal precision and recall:** `out_of_scope` counts as the positive class.
- **Automatic traceability rate:** the share of in-scope rows with `anchor_in_citations` = `1`.
- **Latency:** p50 and p95.

Accuracy and the final traceability figure come from the graders' A1 and A2 scores.
