# Phase 2 archive (September–October 2026)

This folder keeps the Phase 2 outputs for the record and for the Developmental Evaluation. **They do not describe the current platform**, and their results are **not evidence**.

> **Status:** superseded by the rebuild (D-04, D-09). The cohort, gap-detection and gate results are **synthetic system testing**: 6 scripted test accounts driven by `simulate_learner_attempt.py` (D-12). Do not cite the 83.3% bypass, 93.7% sensitivity or 100% routing figures as pilot, beta or cohort results.

| Folder | Content |
|---|---|
| `reports/` | Phase 2 specification, completion report, gap-detection report, cohort "DE" report (here DE meant *Differentiated Education*, not Developmental Evaluation), competency matrix and microlearning catalogue. |
| `briefing/` | Executive briefing (HTML, PDF, PPTX) from 30 October 2026. **It repeats the synthetic figures as validated results. Do not present it without correcting slides 1, 4 and the verbal pitch.** |
| `data/` | JSON outputs of the synthetic evaluations. |

Still in use by the rebuild:
- The competency taxonomy and microlearning library, now `courses/ds4h/taxonomy.json` and `courses/ds4h/microlearning_library.json`.
- The 3 HTML microlearning units, now in [`courses/ds4h/microlearning-phase2/`](../../courses/ds4h/microlearning-phase2/). They are to be re-authored as H5P (D-05).

The Phase 2 code (`block_ollama_chat.php`, `adaptive_router.py`, `gap_detector.py` and the inspection scripts) is deliberately **not** in this repository. Older copies contained credentials (D-02). It remains in the backup folder and tarballs under `C:\AFIRST_Backup` until the D-13 purge.
