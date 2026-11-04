**A-FIRST**

AI-Enabled Frontline Integrated Readiness and Skills Training

**WORK PLAN**

# 1. Purpose and Background

A-FIRST is an applied research project under the Africa CDC Public Health Informatics Fellowship, delivered jointly with the US CDC Division of Global Health Protection. The project tests whether AI integrated into the Africa CDC Moodle learning management system produces measurable competency gains in frontline public health practitioners across the African Epidemic Service (AES) Public Health Informatics track.

This Work Plan formalises the refined scope following direct guidance from the technical advisory team at the April 24, 2026 sprint review. The pilot course is the Data Science Course from the Africa CDC Surveillance Division.

# 2. Project Vision

Frontline public health practitioners across the African Epidemic Service complete the Data Science Course on the Africa CDC LMS and demonstrate measurable understanding of core competencies, with AI helping them get the right answer the first time and close personal skill gaps without sitting through content they have already mastered.

# 3. Objectives

**Objective 1**

Deploy a source-grounded AI assistant inside the Africa CDC Moodle environment that explains, summarises, and answers learner questions using the Data Science Course content. Every AI response must be traceable to an approved source. No open-web access.

**Objective 2**

Layer personalised learning onto the BASIC foundation. The system identifies skill gaps from quiz performance, delivers targeted microlearning, and skips content the learner has already mastered.

A third strategy, READINESS (Prove Performance), is excluded from fellowship deliverables. The project will produce a handover specification for a follow-on team by Sprint 12.

# 4. Scope

## 4.1 In Scope

| **Item**         | **Detail**                                                                                                                                               |
|------------------|----------------------------------------------------------------------------------------------------------------------------------------------------------|
| **Strategies**   | BASIC (Build Understanding) and ADAPTIVE (Close Gaps). READINESS is documented as a future phase.                                                        |
| **Audience**     | Public Health Professional                                                                                                                               |
| **Pilot Course** | Data Science Course, Africa CDC Surveillance Division.                                                                                                   |
| **Platform**     | Africa CDC Moodle environment.                                                                                                                           |
| **Integration**  | Hybrid Option 1 + Option 2: native Moodle AI plugins as foundation, layered with DataCurso (course recommendations) and Nolej (interactive H5P content). |
| **Evaluation**   | Pre/post competency assessment, learner experience survey, and structured comparison against framework success criteria.                                 |

## 4.2 Out of Scope

-   READINESS strategy: end-to-end simulation and performance assessment (Phase 4 future work).

-   New course development: no new content authored during the fellowship.

-   Custom AI recommendation engine (Option 3 from integration analysis).

-   Production-grade local LLM on Africa CDC servers: tested in development only.

## 4.3 Assumptions

-   The Data Science Course from the Surveillance Division is accessible and authorised for the pilot.

-   Moodle administrators grant configuration access for the pilot course within Sprint 1.

-   Weekly Friday reviews with supervisors continue through the 12-sprint cycle.

-   Ethical clearance completes before Sprint 6.

-   DataCurso and Nolej trial or institutional licensing is achievable within the project budget.

# 5. Technical Approach

## 5.1 BASIC Layer

Native Moodle AI plugins provide chat assistance, summarisation, and question answering. Retrieval-augmented generation (RAG) grounds every AI response in Data Science Course content, reducing hallucination risk. Adaptive Quiz activity and conditional release rules allow performance-based progression. Model selection prioritises open-source models locally first, then cloud endpoints evaluated on accuracy, latency, and cost.

## 5.2 ADAPTIVE Layer

DataCurso AI handles course-level recommendations and learner analytics. Nolej AI generates interactive H5P content at sub-unit level where SCORM granularity is too coarse. Skill gap detection wires quiz outcomes to targeted reinforcement, and skip logic bypasses content the learner has already demonstrated competency on.

## 5.3 SCORM and H5P Strategy

SCORM packages are treated as single activities by Moodle. The project applies BASIC layer features to the existing Data Science Course (with xAPI where supported) and supplements with H5P interactive elements through Nolej to enable deeper ADAPTIVE personalisation.

## 5.4 Model Deployment

-   Local open-source model on Africa CDC GPU servers once CDR procurement completes (best for data sovereignty and cost).

-   Local open-source model on the project lead's workstation for development and small-scale testing.

-   Cloud endpoint during the pilot if local infrastructure is not ready. Cost capped through quotas and prompt-length controls.

# 6. Work Plan 

The project executes as 12 one-week sprints aligned to the Friday review cadence. Jira tracks the backlog and burndown. WhatsApp handles between-sprint coordination. The project lead sends a Thursday brief before each Friday review.

| **Sprint** | **Phase** | **Goal**                           | **Friday Deliverables**                                                                                               |
|------------|-----------|------------------------------------|-----------------------------------------------------------------------------------------------------------------------|
| **1**      | BASIC     | Scope Lock & Course Confirmation   | Refined work plan signed off. Data Science Course (Surveillance Division) confirmed as pilot course. Jira board live. |
| **2**      | BASIC     | Learner Profile & Ethics Start     | Cohort survey approved. Ethics application submitted. Moodle admin access confirmed.                                  |
| **3**      | BASIC     | Native Moodle AI Configuration     | Chat, summarisation, and adaptive quiz active in test environment. Model evaluation matrix complete.                  |
| **4**      | BASIC     | Source Grounding & Retrieval Setup | Data Science course content indexed. Source grounding tested on 20 representative learner questions.                  |
| **5**      | BASIC     | BASIC Layer Internal Review        | Demo to supervisor. Go/no-go decision for learner pilot.                                                              |
| **6**      | ADAPTIVE  | DataCurso Onboarding               | DataCurso trial/license confirmed. Integration plan documented. Ethics clearance status checked.                      |
| **7**      | ADAPTIVE  | Nolej Onboarding & H5P Conversion  | Nolej active. Data Science course supplemented with H5P interactive elements.                                         |
| **8**      | ADAPTIVE  | ADAPTIVE Layer Integration         | Gap detection wired to quiz outcomes. Microlearning recommendation tested on three sample learners.                   |
| **9**      | PILOT     | Pilot Launch — Full Cohort         | Pilot opens to AES PHI cohort. Onboarding session delivered. Baseline assessment captured.                            |
| **10**     | PILOT     | Pilot Mid-Point Review             | Engagement and quiz data reviewed. Learner feedback collected. Issues triaged.                                        |
| **11**     | EVAL      | Pilot Close & Evaluation           | Endline assessment. Comparison against baseline. Learner experience survey closed.                                    |
| **12**     | WRAP      | Reporting & Handover               | Final report. READINESS phase specification. Lessons learned. Manuscript draft for peer review.                       |

# 7. Deliverables

-   Source-grounded AI assistant integrated into the Data Science Course on Africa CDC Moodle (BASIC layer).

-   Personalised microlearning system with gap detection and skip logic (ADAPTIVE layer).

-   Pilot evaluation report with pre/post competency data and learner experience findings.

-   READINESS phase handover specification for a follow-on team (Sprint 12).

-   Manuscript draft for peer-reviewed publication (Sprint 12).

-   Continuous documentation in the Africa CDC project repository: sprint outputs, risk decisions, and lessons-learned log.

# 8. Evaluation Framework

Success is defined as: people are capable and ready to perform. Activity metrics alone — training hours, AI usage volume, user satisfaction — are not success measures. The table below operationalises success for BASIC and ADAPTIVE outcomes.

| **Strategy**   | **Outcome**              | **Success Threshold**                        | **Measurement**                |
|----------------|--------------------------|----------------------------------------------|--------------------------------|
| **BASIC**      | AI response accuracy     | &gt;= 95% on 50-question set                 | Reviewer-graded response audit |
| **BASIC**      | Source traceability      | 100% of responses cite source                | Automated log review           |
| **ADAPTIVE**   | Competency gain          | &gt;= 20% mean quiz score improvement        | Pre/post assessment            |
| **ADAPTIVE**   | Personalisation fidelity | &gt;= 70% of learners receive distinct paths | Path differentiation analysis  |
| **Experience** | Usability                | Net recommendation score positive            | Survey + interviews            |

# 9. Governance and Cadence

**Weekly Friday Supervisor Review**

Treated as a sprint review. Project lead presents outputs. Supervisor reacts and approves the next sprint goal.

**Mid-Week Working Group**

WhatsApp channel for questions, blockers, and shared resources. Friday calls reserved for review and decision.

**Pre-Friday Brief**

Project lead sends a short Thursday brief so the supervisor is prepared before each review call.

**Jira Board**

Shared with the working group. Epics aligned to BASIC, ADAPTIVE, evaluation, and governance. Sprint backlog reviewed every Friday.

**Decision Log**

Maintained in the Africa CDC project repository. Captures scope changes, technology choices, and risk decisions with date and approver.

# 10. Ethics, Privacy, and Equity

-   Ethical clearance through appropriate Africa CDC and partner institution channels before pilot launch (by Sprint 6).

-   Informed consent for all pilot participants with a clear opt-out path.

-   Data minimisation: only data needed to evaluate BASIC and ADAPTIVE outcomes is collected.

-   Bias and fairness check on AI responses across language, region, and topic during the Sprint 5 internal review.

-   Source grounding reduces misinformation risk: AI responses cite approved course content.

-   Equity check: the pilot evaluates whether the integration works for learners on lower-bandwidth connections.

# 11. Risk Register

| **Risk**                               | **Impact** | **Likelihood** | **Mitigation**                                                                                         |
|----------------------------------------|------------|----------------|--------------------------------------------------------------------------------------------------------|
| **Solo developer bottleneck**          | High       | High           | Tight scope: BASIC and ADAPTIVE only. Plugins over custom code. Weekly reviews surface blockers early. |
| **Ethical clearance delay**            | High       | Medium         | Submit in Sprint 2. Pre-engage ethics committee. Buffer between Sprint 8 and pilot launch.             |
| **GPU servers not ready before pilot** | Medium     | High           | Plan for cloud/workstation models. Cap token usage. Document local hosting as Phase 4 work.            |
| **Vendor licensing not feasible**      | Medium     | Medium         | Confirm trial access in Sprint 1. Identify open-source fallbacks. Reduce ADAPTIVE features as needed.  |
| **AI hallucination undermines trust**  | High       | Medium         | Source-grounded retrieval. 50-question reviewer audit. Source citations on all learner responses.      |
| **Content too coarse for ADAPTIVE**    | Medium     | Medium         | Supplement Data Science course with H5P via Nolej. Use xAPI where available.                           |

# 12. READINESS Phase Handover Specification (Phase 4)

READINESS is excluded from fellowship deliverables. A handover specification delivered in Sprint 12 will enable a follow-on team to implement without rediscovery cost. It will cover:

-   Simulation requirements for end-to-end outbreak response scenarios.

-   Performance assessment criteria including readiness thresholds for independent versus supervised work.

-   Technology options, including simulation engines that integrate with Moodle.

-   Resource and timeline estimates for a follow-on phase.

-   Lessons from the BASIC and ADAPTIVE pilot that should shape READINESS design.
