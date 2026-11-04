**A-FIRST**

AI-Enabled Frontline Integrated Readiness and Skills Training

**WORK PLAN**

# 1. Purpose and Background

A-FIRST is a capstone project under the Africa CDC Public Health Informatics (AES-PHI) Fellowship. The project tests whether AI integrated into the Africa CDC Moodle learning management system (LMS) produces measurable competency gains among frontline public health practitioners working in public health.

The pilot course is the Data Science Course from the Africa CDC Surveillance Division. The project meets all six AES-PHI Capstone criteria: it addresses a clearly identified need; involves activities with defined endpoints; applies and extends public health informatics competencies; is grounded in published evidence; includes a structured evaluation; and contributes to improving public health informatics practice and community health across Africa.

# 2. Project Vision

Frontline public health practitioners across the African Epidemic Service complete the Data Science Course on the Africa CDC LMS and demonstrate measurable understanding of core competencies. AI tools help them get the right answer the first time and close personal skill gaps without repeating content they have already mastered.

This vision is grounded in evidence on adaptive learning and AI-assisted instruction. Research on technology-enhanced learning in resource-constrained settings shows that personalised, feedback-driven instruction can accelerate competency development and reduce attrition in online professional education (Means et al., 2013; Pane et al., 2015). The integration of retrieval-augmented generation (RAG) into health education environments has demonstrated measurable improvements in knowledge retention and learner confidence in health workforce training contexts (Kasireddy & Brown, 2023).

# 3. Research Questions

A-FIRST is designed as an applied research project. The following primary and secondary research questions guide the study design, evaluation framework, and data collection approach.

## 3.1 Primary Research Question

Does integrating AI-assisted instruction into the Africa CDC Moodle LMS produce measurable competency gains among AES-PHI track participants completing the Data Science Course, compared with standard LMS delivery?

## 3.2 Secondary Research Questions

1.  To what extent does source-grounded AI (retrieval-augmented generation) improve accuracy and reduce knowledge gaps among frontline practitioners completing the Data Science Course?

2.  Does adaptive, gap-based microlearning produce greater competency gains than standard linear course delivery among AES-PHI cohort participants?

3.  What is the learner experience of AI-assisted tools — including perceived usability, trust, and utility — among public health practitioners operating in variable-bandwidth environments across Africa?

4.  Does personalised learning pathway differentiation correlate with improved post-assessment performance, and what proportion of learners receive a meaningfully distinct learning path?

5.  What implementation barriers and facilitators shape the adoption of AI-enabled learning tools within the Africa CDC Moodle environment?

These questions align with the AES-PHI Capstone requirement for projects that address both learning outcome (competency gain) and training program process (usability, implementation fidelity) dimensions, following a mixed-methods design consistent with the Developmental Evaluation approach for emergent, complex initiatives (Dozois, Langlois & Blanchet-Cohen, 2010).

# 4. Objectives

## Objective 1 

Deploy a source-grounded AI assistant inside the Africa CDC Moodle environment that explains, summarises, and answers learner questions using Data Science Course content. Every AI response must be traceable to an approved source. No open-web access is permitted during the pilot.

## Objective 2

Layer personalised learning onto the BASIC foundation. The system identifies skill gaps from quiz performance, delivers targeted microlearning, and skips content the learner has already mastered.

A third strategy, READINESS (Prove Performance), is excluded from fellowship deliverables. The project will produce a handover specification for a follow-on team by Sprint 12.

# 5. Scope

## 5.1 In Scope

|              |                                                                                                                                                              |
|--------------|--------------------------------------------------------------------------------------------------------------------------------------------------------------|
| **Item**     | **Detail**                                                                                                                                                   |
| Strategies   | BASIC (Build Understanding) and ADAPTIVE (Close Gaps). READINESS is documented as a future phase.                                                            |
| Audience     | Public Health Professionals                                                                                                                                  |
| Pilot Course | Data Science Course, Africa CDC Surveillance Division.                                                                                                       |
| Platform     | Africa CDC Moodle environment.                                                                                                                               |
| Integration  | Hybrid Option 1 + Option 2: native Moodle AI plugins as the foundation, layered with DataCurso (course recommendations) and Nolej (interactive H5P content). |
| Evaluation   | Pre/post competency assessment, learner experience survey, and structured comparison against framework success criteria (see Section 9).                     |

## 5.2 Out of Scope

-   READINESS strategy: end-to-end simulation and performance assessment (Phase 4 future work).

-   New course development: no new content will be authored during the fellowship.

-   Custom AI recommendation engine (Option 3 from the integration analysis).

-   Production-grade local LLM on Africa CDC servers: tested in development only.

## 5.3 Assumptions

-   The Data Science Course from the Surveillance Division will be accessible and authorised for the pilot.

-   Moodle administrators grant configuration access for the pilot course within Sprint 1.

-   Weekly Friday reviews with supervisors continue through the 12-sprint cycle.

-   Ethical clearance is obtained before Sprint 6 (pilot launch).

-   DataCurso and Nolej trial or institutional licensing is achievable within the project budget.

# 6. Technical Approach

## 6.1 BASIC Layer

Native Moodle AI plugins provide chat assistance, summarisation, and question answering. Retrieval-augmented generation (RAG) grounds every AI response in Data Science Course content, reducing hallucination risk. The Adaptive Quiz activity and conditional release rules enable performance-based progression. Model selection prioritises open-source models locally first, then cloud endpoints evaluated on accuracy, latency, and cost.

## 6.2 ADAPTIVE Layer

DataCurso AI handles course-level recommendations and learner analytics. Nolej AI generates interactive H5P content at sub-unit level where SCORM granularity is too coarse. Skill gap detection links quiz outcomes to targeted reinforcement, and skip logic bypasses content the learner has already demonstrated competency in.

## 6.3 SCORM and H5P Strategy

SCORM packages are treated as single activities by Moodle. The project applies BASIC layer features to the existing Data Science Course (with xAPI where supported) and supplements content with H5P interactive elements through Nolej to enable deeper ADAPTIVE personalisation.

## 6.4 Model Deployment

-   Local open-source model on Africa CDC GPU servers once CDR procurement is complete (preferred for data sovereignty and cost efficiency).

-   Local open-source model on the project lead's workstation for development and small-scale testing.

-   Cloud endpoint during the pilot if local infrastructure is not ready. Token usage is cost-capped through quotas and prompt-length controls.

# 7. Work Plan

The project executes as 12 one-week sprints aligned to the Friday supervisor review cadence. Jira tracks the backlog and burndown. WhatsApp handles between-sprint coordination. The project lead sends a written Thursday brief before each Friday review.

|            |           |                                     |                                                                                                           |
|------------|-----------|-------------------------------------|-----------------------------------------------------------------------------------------------------------|
| **Sprint** | **Phase** | **Goal**                            | **Friday Deliverables**                                                                                   |
| 1          | BASIC     | Scope Lock & Course Confirmation    | Revised work plan signed off. Data Science Course confirmed as pilot course. Jira board live.             |
| 2          | BASIC     | Learner Profile & Ethics Submission | Cohort survey instrument approved. Ethics application submitted. Moodle admin access confirmed.           |
| 3          | BASIC     | Native Moodle AI Configuration      | Chat, summarisation, and adaptive quiz active in test environment. Model evaluation matrix complete.      |
| 4          | BASIC     | Source Grounding & Retrieval Setup  | Data Science Course content indexed. Source grounding tested against 20 representative learner questions. |
| 5          | BASIC     | BASIC Layer Internal Review         | Demo presented to supervisor. Go/no-go decision documented for learner pilot.                             |
| 6          | ADAPTIVE  | DataCurso Onboarding                | DataCurso trial or licence confirmed. Integration plan documented. Ethics clearance status confirmed.     |
| 7          | ADAPTIVE  | Nolej Onboarding & H5P Conversion   | Nolej active. Data Science Course supplemented with H5P interactive elements.                             |
| 8          | ADAPTIVE  | ADAPTIVE Layer Integration          | Gap detection linked to quiz outcomes. Microlearning recommendation tested on three sample learners.      |
| 9          | PILOT     | Pilot Launch — Full Cohort          | Pilot opens to AES-PHI cohort. Onboarding session delivered. Baseline assessment captured.                |
| 10         | PILOT     | Pilot Mid-Point Review              | Engagement and quiz data reviewed. Learner feedback collected. Issues triaged and documented.             |
| 11         | EVAL      | Pilot Close & Evaluation            | Endline assessment complete. Findings compared against baseline. Learner experience survey closed.        |
| 12         | WRAP      | Reporting & Handover                | Final report. READINESS phase specification. Lessons-learned log. Manuscript draft for peer review.       |

# 8. Deliverables

Deliverables align with the AES-PHI Capstone assessment structure (Proposal, W-I-P I & II, Final Report, Final Presentation, Abstract, and Manuscript/Product).

-   Source-grounded AI assistant integrated into the Data Science Course on the Africa CDC Moodle LMS (BASIC layer).

-   Personalised microlearning system with gap detection and skip logic (ADAPTIVE layer).

-   Pilot evaluation report with pre/post competency data and learner experience findings.

-   READINESS phase handover specification for a follow-on team (Sprint 12).

-   Manuscript draft for submission to a peer-reviewed public health informatics journal (Sprint 12).

-   Continuous documentation in the Africa CDC project repository: sprint outputs, risk decisions, and lessons-learned log.

# 9. Evaluation Framework

The table below operationalises success for BASIC and ADAPTIVE outcomes and maps directly to the research questions in Section 3. The evaluation follows a Developmental Evaluation (DE) approach (Dozois et al., 2010), which prioritises continuous learning and real-time feedback over a single summative judgment.

|                |                       |                          |                                              |                                     |
|----------------|-----------------------|--------------------------|----------------------------------------------|-------------------------------------|
| **Strategy**   | **Research Question** | **Outcome**              | **Success Threshold**                        | **Measurement**                     |
| BASIC          | RQ1, RQ2              | AI response accuracy     | &gt;= 95% on 50-question set                 | Reviewer-graded response audit      |
| BASIC          | RQ2                   | Source traceability      | 100% of responses cite source                | Automated log review                |
| ADAPTIVE       | RQ1, RQ3              | Competency gain          | &gt;= 20% mean quiz score improvement        | Pre/post assessment                 |
| ADAPTIVE       | RQ4                   | Personalisation fidelity | &gt;= 70% of learners receive distinct paths | Path differentiation analysis       |
| Experience     | RQ4, RQ5              | Usability                | Net recommendation score positive            | Survey + structured interviews      |
| Implementation | RQ5                   | Adoption barriers        | Documented per barrier type                  | Sprint retrospectives + field notes |

# 10. Governance and Cadence

## Weekly Friday Supervisor Review

Treated as a sprint review. Project lead presents outputs. Advisors provides feedback and approves the next sprint goal.

## Mid-Week Working Group

WhatsApp channel for questions, blockers, and shared resources. Friday calls are reserved for review and decisions.

## Pre-Friday Brief

Project lead sends a short written brief on Thursday so the advisors are prepared before each review call.

## Jira Board

Shared with the working group. Epics aligned to BASIC, ADAPTIVE, Evaluation, and Governance. Sprint backlog reviewed every Friday.

## Decision Log

Maintained in the Africa CDC project repository. Captures scope changes, technology choices, and risk decisions with date and approver.

# 11. Ethics, Privacy, and Equity

-   Ethical clearance obtained through appropriate Africa CDC and partner institution channels before pilot launch (target: Sprint 6).

-   Informed consent required from all pilot participants, with a clearly communicated opt-out path.

-   Data minimisation: only data required to evaluate BASIC and ADAPTIVE outcomes is collected and retained.

-   Bias and fairness audit on AI responses across language, region, and topic area during the Sprint 5 internal review.

-   Source grounding reduces misinformation risk: all AI responses cite approved course content.

-   Equity check: the pilot evaluates whether the integration functions effectively for learners on lower-bandwidth connections across diverse African contexts.

# 12. Risk Register

|                                              |            |                |                                                                                                                     |
|----------------------------------------------|------------|----------------|---------------------------------------------------------------------------------------------------------------------|
| **Risk**                                     | **Impact** | **Likelihood** | **Mitigation**                                                                                                      |
| Solo developer bottleneck                    | High       | High           | Tight scope: BASIC and ADAPTIVE only. Plugins over custom code. Weekly reviews surface blockers early.              |
| Ethical clearance delay                      | High       | Medium         | Submit in Sprint 2. Pre-engage ethics committee. Buffer between Sprint 8 and pilot launch.                          |
| GPU servers not ready before pilot           | Medium     | High           | Cloud or workstation models as fallback. Token usage capped. Local hosting documented as Phase 4 work.              |
| Vendor licensing not feasible                | Medium     | Medium         | Trial access confirmed in Sprint 1. Open-source fallbacks identified. ADAPTIVE features reduced as needed.          |
| AI hallucination undermines learner trust    | High       | Medium         | Source-grounded retrieval via RAG. 50-question reviewer audit. Source citations mandatory on all learner responses. |
| Course content too coarse for ADAPTIVE layer | Medium     | Medium         | Data Science Course supplemented with H5P content via Nolej. xAPI used where available.                             |
| Low pilot enrolment or drop-off              | Medium     | Medium         | Onboarding session at pilot launch. Mid-point check-in at Sprint 10. Participant support plan in place.             |

# 13. READINESS Phase Handover Specification (Phase 4)

READINESS is excluded from fellowship deliverables. A handover specification delivered in Sprint 12 will enable a follow-on team to implement without rediscovery costs. It will cover:

-   Simulation requirements for end-to-end outbreak response scenarios.

-   Performance assessment criteria including readiness thresholds for independent versus supervised work.

-   Technology options, including simulation engines that integrate with Moodle.

-   Resource and timeline estimates for a follow-on implementation phase.

-   Lessons from the BASIC and ADAPTIVE pilot that should shape READINESS design.

# 14. References

Dozois, E., Langlois, M., & Blanchet-Cohen, N. (2010). DE 201: A Practitioner's Guide to Developmental Evaluation. The J.W. McConnell Family Foundation and the International Institute for Child Rights and Development.

Kasireddy, P., & Brown, C. (2023). Retrieval-augmented generation for health workforce education: Evidence from clinical training environments. Journal of Medical Internet Research, 25(4), e42301.

Means, B., Toyama, Y., Murphy, R., & Baki, M. (2013). The effectiveness of online and blended learning: A meta-analysis of the empirical literature. Teachers College Record, 115(3), 1-47.

Pane, J. F., Steiner, E. D., Baird, M. D., & Hamilton, L. S. (2015). Continued Progress: Promising Evidence on Personalized Learning. RAND Corporation.

World Health Organization. (2023). Global Strategy on Digital Health 2020-2025. WHO Press.
