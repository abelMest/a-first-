# Advisory meetings, November 2026

Weekly Friday reviews with Dr Herman Tolentino and Dr Tadesse Wuhib (CDC/GHC/DGHT). Source: Teams transcripts (6 Nov, 42 min; 13 Nov, 24 min), kept by the fellow outside the repository. Notes cover the project points only.

## 6 November 2026 (Sprint 6)

**1. A course glossary as deterministic grounding (Dr Tolentino).** Besides retrieval (RAG), extract the course's key concepts into a glossary saved as JSON or JSON Lines, each term linked to the course material that defines it. A pre-built, deterministic mapping lowers the chance of hallucination and gives the assistant answers to definitional questions the course text covers only implicitly.
- DE question raised: *why would a course with many technical terms not have a glossary?* The DS4H course has none, and neither do most Moodle courses the fellow has seen. With an AI assistant in the course, the context has changed.
- Fellow's concern: who builds it, the A-FIRST team now or the instructional designers in future courses. Agreed to record this as a DE finding and a recommendation to content developers.
- Link to existing work: the review-unit extractor (D-24) already pulls the "X is …" definitions out of each module (9 for Module 1.1). It can produce the glossary file with source references.

**2. Instructional design.** Content development follows ADDIE (assess, design, develop, implement, evaluate) with feedback loops; three fields meet here: instructional design, digital health and developmental evaluation. Fellow to read up and reflect it in Phase 2.

**3. Publications (Dr Tolentino).** Plan at least two papers:
- **A developmental evaluation paper.** Its outcomes differ from a traditional evaluation: (a) the framework that emerged for incorporating AI into content delivery; (b) how it emerged: the DE moments, what changed each sprint and why; (c) a **proposed evaluation framework**, which becomes possible once the framework exists.
- **An implementation case study:** integration into the Africa CDC architecture.
- Strength comes from methods: rigour and relevance. Choose venues deliberately (DE and evaluation journals, informatics journals). Start now: an outline, and notes turned into usable data. Both advisors agreed to co-author.

**4. CDC's parallel work (Dr Wuhib).** CDC's COMPASS: adaptive learning for the CDC AI literacy framework (foundational level first), motivated by a 31-point spread in staff AI literacy. Architecture: curriculum (the authority) → rule-based engine → LMS runtime with a model as a service, on enterprise infrastructure. Curriculum built for adaptivity: 73 learning objectives, 446 teaching items, 71 exam items, distractors written to catch misconceptions. Lesson shared: *a curriculum for adaptive learning is built differently from a traditional one.* The two efforts are to share progress. CDC can't use a local model; A-FIRST may have no choice but local for now.

**5. Governance.** Need for a second fellow on AI governance, policy and maturity for NPHIs; Dr Wuhib shared a recently published paper on NPHI approaches to AI, to read.

## 13 November 2026 (Phase 2 presentation)

**1. Versions and models (Dr Tolentino).** Document every version. Explore other models besides the local Llama 3.2; Claude Sonnet or Haiku would be worth trying. Fellow: the current licence doesn't cover API use; a cost estimate is pending. (Note: D-25 commits the pilot to local models; any cloud trial would be an evaluation comparison and needs a decision.)

**2. Will the tests vary? (Dr Wuhib).** Diagnostics, Knowledge Checks and retakes reuse the same items. Can a learner be tested in different ways across the course? This matches the D-29 concern (practice effect) and open question 20.

**3. The OODA loop as part of the engine (both).** Observe → orient → decide → act structures the telemetry: define the **events** to observe, so the data become structured; the decision and the act include **feedback to the person**. Dr Tolentino's example: a deterministic, sensor-driven loop that detects a change (Wi-Fi lost, wind rising, propane at 20%) and tells him at once what changed and what to do.
- Fellow's self-assessment against the loop: *observe*: quiz data collected, assistant signals collected but unused; *orient*: module/competency mapping partly there; *decide*: rules exist for quizzes only; *act*: review units, teacher log and content-gap log exist, but **unlocks are silent**: learners are not told what changed.

**4. Course material for review (Dr Wuhib).** Wants the course content as a Word document, to examine it against the COMPASS principles he shared. Fellow to convert the SCORM text and send it.

**5. Remote access.** Africa CDC VPN (FortiClient) unreliable for a month, blocking work on the VM; suggestion: WireGuard or Tailscale.

**6. Abstracts.** Fellow to draft and share the A-FIRST abstract and publication drafts. The fellow has a separate accepted abstract (continental PHC digitalisation framework) at CPHIA.

## Actions

| # | Action | Owner | Status |
|---|---|---|---|
| 1 | Send the DS4H course content as a Word document to Dr Wuhib | Fellow | Open (promised 13 Nov) |
| 2 | Glossary file (JSON Lines, term → definition → source module) from the extracted definitions; use it in the assistant; DE recommendation to content developers | Fellow + Claude | Open |
| 3 | OODA design note: structured event list (observe), signal-to-module/competency mapping (orient), rules incl. assistant signals (decide), in-the-moment learner and teacher feedback (act) | Fellow + Claude | Open |
| 4 | Parallel item sets so learners are tested in different ways (diagnostic, Knowledge Check, retake, pre/post) | Content owner, SME | Open (Q20) |
| 5 | Publication outline: DE paper (emergent framework, DE moments per sprint, proposed evaluation framework) and implementation case study; share with both advisors | Fellow | Open |
| 6 | Version record for every release (plugins, models, settings) | Fellow | Ongoing (git tags, decision log) |
| 7 | Cost estimate for a cloud model API as an evaluation comparison | Fellow | Open |
| 8 | Read the NPHI AI paper; follow up on a governance fellow | Fellow | Open |
| 9 | Read on ADDIE; reflect in Phase 2 design notes | Fellow | Open |
