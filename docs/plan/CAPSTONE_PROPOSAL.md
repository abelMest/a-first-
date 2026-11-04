**AFRICA CENTRES FOR DISEASE CONTROL AND PREVENTION**

**Africa Epidemic Service**

*Public Health Informatics Fellowship*

**CAPSTONE PROJECT PROPOSAL**

**A-FIRST**

**AI-Enabled Frontline Integrated Reference and Skills Training**

*A Source-Grounded AI Learning Assistant for the Africa CDC Moodle Environment*

|                          |                                                                                                                                                                                               |
|--------------------------|-----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|
| **Fellow**               | Dr. Abel Mestie Mekonnen, MD                                                                                                                                                                  |
| **Host Site**            | Africa CDC, Digital Health and Information Systems Department                                                                                                                                 |
| **Host Site Supervisor** | Judith Nguimfack, Africa CDC                                                                                                                                                                  |
| **PHI Competencies**     | Information Systems Design and Implementation, Data Science and Analytics, Public Health Workforce Capacity Building, Ethical and Responsible AI in Health, Project Management and Evaluation |
| **Capstone Duration**    | 12 months (June 2026 to May 2027), with dissemination through June 2027                                                                                                                       |
| **Submission Date**      | June 2026                                                                                                                                                                                     |

# Table of Contents

# 

[1. Introduction 5](#introduction)

[1.1 Background 5](#background)

[1.2 Problem Statement 5](#problem-statement)

[1.3 Justification and Rationale 6](#justification-and-rationale)

[1.4 Objectives 6](#objectives)

[1.5 Research Question 7](#research-question)

[1.6 Evaluation Framing: Developmental Evaluation 7](#evaluation-framing-developmental-evaluation)

[1.7 Project Boundaries 8](#project-boundaries)

[2. Literature Review 9](#literature-review)

[2.1 Africa Workforce Capacity and the Continuing Education Gap 9](#africa-workforce-capacity-and-the-continuing-education-gap)

[2.2 Large Language Models in Medical Education 9](#large-language-models-in-medical-education)

[2.3 Retrieval-Augmented Generation for Domain-Specific Knowledge 9](#retrieval-augmented-generation-for-domain-specific-knowledge)

[2.4 Adaptive Learning in Resource-Constrained Settings 10](#adaptive-learning-in-resource-constrained-settings)

[2.5 Equity, Bias, and Ethical AI in Health Contexts 10](#equity-bias-and-ethical-ai-in-health-contexts)

[2.6 Gaps in the Literature 11](#gaps-in-the-literature)

[3. Methods 12](#methods)

[3.1 Setting and Population 12](#setting-and-population)

[3.2 Study Design 12](#study-design)

[3.3 Eligibility Criteria 12](#eligibility-criteria)

[3.4 Sample Size 12](#sample-size)

[3.5 Sampling Method 13](#sampling-method)

[3.6 Data Sources and Data Collection 13](#data-sources-and-data-collection)

[3.7 Variables and Measures 13](#variables-and-measures)

[3.8 Data Analysis 14](#data-analysis)

[3.9 Ethical Considerations 15](#ethical-considerations)

[3.10 Dissemination of Results 16](#dissemination-of-results)

[4. Timeline and Budget 17](#timeline-and-budget)

[4.1 Project Timeline 17](#project-timeline)

[4.2 Budget 18](#budget)

[5. References 20](#references)

# Abbreviations

|                  |                                                           |
|------------------|-----------------------------------------------------------|
| **Abbreviation** | **Definition**                                            |
| AES              | Africa Epidemic Service                                   |
| Africa CDC       | Africa Centres for Disease Control and Prevention         |
| AI               | Artificial Intelligence                                   |
| DE               | Developmental Evaluation                                  |
| H5P              | HTML5 Package, an interactive content authoring standard  |
| LMS              | Learning Management System                                |
| LLM              | Large Language Model                                      |
| NPS              | Net Promoter Score                                        |
| PHI              | Public Health Informatics                                 |
| RACI             | Responsible, Accountable, Consulted, Informed             |
| RAG              | Retrieval-Augmented Generation                            |
| SCORM            | Sharable Content Object Reference Model                   |
| WHO              | World Health Organization                                 |
| xAPI             | Experience API, an e-learning data tracking specification |

# Goal Statement

A-FIRST equips Africa Epidemic Service Public Health Informatics fellows with a source-grounded AI assistant inside the Africa CDC Moodle environment. The goal is to improve speed, accuracy, and comprehension when fellows locate and understand approved public health content, while building an institutional model Africa CDC will scale to future cohorts and member states.

# 1. Introduction

## 1.1 Background

Africa lost a generation of public health workforce capacity to the COVID-19 pandemic and to chronic underinvestment in continuing professional education. Africa CDC and partner agencies now train new cohorts of frontline practitioners each year through the Africa Epidemic Service. The Public Health Informatics track within AES prepares fellows to design, deploy, and evaluate digital systems for surveillance, outbreak response, and health information exchange. Training relies on the Africa CDC Moodle learning management system, which hosts structured courses authored by the surveillance division and partner programs.

Frontline learners face two persistent problems. Existing course material is long, dense, and time-locked to a fixed schedule. Learners with deep prior knowledge sit through content they have already mastered. Learners with knowledge gaps complete the same content without targeted reinforcement. Both groups leave the course with uneven competency. The result is a workforce trained on paper but uneven in performance.

Recent advances in large language models, retrieval-augmented generation, and adaptive learning analytics offer a path to reshape this experience. A source-grounded AI assistant retrieves answers from approved course content, cites the source on every response, and refuses requests outside its grounded scope. Layered with adaptive content delivery, the same system identifies skill gaps from quiz performance and routes the learner to targeted reinforcement. This approach respects two non-negotiables in public health training: traceability of every answer to an approved source, and protection of learners from unsupported AI output.

A-FIRST tests this approach inside the Africa CDC Moodle environment using the Data Science Course from the Surveillance Division as the pilot. The Data Science Course is selected because it has stable content, and a defined competency framework. The course also exposes learners to applied epidemiology methods where AI assistance has the highest near-term value: data interpretation, syntax recall, and method selection.

## 1.2 Problem Statement

The Africa CDC Moodle environment hosts authoritative public health content. Learners struggle to find the right answer at the right time. Standard Moodle navigation depends on hierarchical menus, full-text search across hundreds of pages, and instructor-mediated clarification. Frontline practitioners working under time pressure abandon the search and fall back on open web sources of unknown quality. Open web search exposes learners to misinformation, region-irrelevant guidance, and content not aligned with Africa CDC continental frameworks.

At the same time, learner heterogeneity defeats one-size-fits-all course design. A fellow with a medical doctorate and three years of field experience receives the same module sequence as a recent graduate of an undergraduate program. Quiz performance reveals gaps, but the Moodle activity model treats SCORM packages as single completion events. Granular gap detection at the topic level is not available without supplementary tooling.

No published evaluation has measured whether a source-grounded AI assistant deployed inside an African public health LMS improves learner gap-calibrated retrieval accuracy and comprehension among frontline learners. The gap is operational, methodological, and equity-relevant. Africa CDC operates in low-bandwidth environments. Benefits identified from western LMS-AI studies implemented in high-resource contexts may not translate directly to public health learners working from regional offices on intermittent connections.

## 1.3 Justification and Rationale

This project aligns with the Africa CDC Continental Digital Health Strategy 2024 to 2030 \[add ref\], the Continental AI Strategy 2024 to 2034, and the WHO Global Strategy on Human Resources for Health \[add ref\]. Each instrument identifies workforce development and trustworthy AI adoption as priorities.

The knowledge generated by this project answers a single, focused research question with operational value. Africa CDC will use the findings to decide whether to extend AI-enabled learning across the AES track and other workforce programs. The evidence package includes an unanswered question log, a content gap inventory, and a replication guide. These outputs serve workforce development teams, content authors, and governance bodies, not only learners.

Beneficiaries include AES fellows, public health professionals, Africa CDC course developers, partners supporting the fellowship, and member state public health agencies seeking to scale digital learning. The dissemination plan covers a peer-reviewed manuscript, a continental briefing to the Africa CDC Director General office, presentations at Africa CDC scientific conferences, and an open replication guide hosted on the Africa CDC project repository.

## 1.4 Objectives

### General Objective

To evaluate the effect of a source-grounded AI assistant integrated into the Africa CDC Moodle environment on retrieval accuracy, comprehension, and learner experience among public health professionals enrolled in the Data Science Course over a 12-month capstone period.

### Specific Objectives

1.  Deploy a source-grounded AI assistant inside the Africa CDC Moodle environment, configured against the Data Science Course content, with traceable citations on every response and no open-web access.

2.  Layer adaptive learning features onto the AI foundation, including skill gap detection from quiz performance and microlearning recommendations at sub-unit granularity.

3.  Measure the effect of the integrated system on retrieval accuracy, comprehension, and learner experience using a pre-post within-subject pilot design, with statistical and qualitative analysis.

4.  Produce institutional artifacts that extend the value of the pilot beyond the fellowship, including an unanswered question log, a content gap inventory, and a replication guide for AI-enabled course deployment.

## 1.5 Research Question

*This project investigates a single, focused, answerable research question.*

Does a Moodle-integrated, source-grounded AI assistant improve enrolled public health professionals speed, accuracy, and comprehension in locating and understanding approved public health content, compared with standard Moodle navigation alone?

Secondary questions sit beneath the primary question and inform interpretation, not headline findings:

-   What categories of learner questions does the source-grounded assistant fail to answer? Why did the assistant fail? Do these failures mean there are gaps in approved content?

-   How does learner experience differ between high-bandwidth and low-bandwidth connections during pilot delivery?

-   What proportion of learners receive a meaningfully distinct adaptive path, defined as bypassing or replacing at least 20 percent of standard course content based on quiz performance?

## 1.6 Evaluation Framing: Developmental Evaluation

A-FIRST is a Developmental Evaluation following the framework articulated by Patton, Westley, and Zimmerman. The evaluator is embedded in the intervention. Evaluation questions emerge as the system architecture becomes clearer. Documentation of decisions, adaptations, and lessons learned is itself an evaluation output. The 12-month capstone timeline provides the extended observation period that Developmental Evaluation requires: enough time for the intervention to mature, for adaptation cycles to be documented, and for meaningful learning data to accumulate.

## 1.7 Project Boundaries

This project focuses on evaluating the use of AI-assisted knowledge retrieval within a single Moodle course environment. It will assess how effectively AI can support access to approved course materials, improve retrieval accuracy, and enhance user comprehension. The project will use only validated and approved documents as its knowledge base and aims to generate practical evidence and insights for Africa CDC.

The project is intentionally scoped for completion by one fellow within a 12-month period. It is not intended to build a new AI system from scratch, develop a full adaptive learning platform, create a readiness scoring engine, or serve as a general-purpose chatbot

# 2. Literature Review

## 2.1 Africa Workforce Capacity and the Continuing Education Gap

WHO estimates a continental shortage of 6.1 million health workers in Africa by 2030 (WHO, 2022). The Africa CDC continental strategy positions the Africa Epidemic Service as a flagship program for closing the public health workforce gap (Africa CDC, 2023). AES targets 6,000 frontline practitioners by 2030. Continuing education models built on physical classroom training do not scale to this target. Digital learning is the only feasible delivery mode at continental scale (Kasonde and Bonnel, 2020).

Evidence on the effectiveness of digital learning for African health workers is mixed. A systematic review of e-learning in African medical education found moderate effects on knowledge acquisition but limited evidence on competency transfer to practice (Frehywot et al., 2013). Subsequent studies report that engagement with self-paced LMS content is low when learners do not receive structured guidance (Barteit et al., 2020). The implication is operational. Content alone is insufficient. Learners need active scaffolding to translate content into competency.

## 2.2 Large Language Models in Medical Education

Large language models have moved from research curiosity to operational tool in medical education within three years. GPT-4 and similar foundation models pass medical licensing examinations at or above the median candidate score (Singhal et al., 2023). Studies in undergraduate medical training report time savings on routine retrieval tasks and improved engagement with case-based learning when AI is integrated into the LMS (Eysenbach, 2023, and Kung et al., 2023). The same studies surface a persistent risk: hallucination. Foundation models may produce fluent, internally consistent responses unsupported by source evidence. In a public health context, an unsupported AI response is operationally indistinguishable from misinformation, and can potentially lead to unintended outcomes.

Mitigation strategies cluster around three approaches. Source-grounded retrieval (RAG) constrains the model to retrieve from an approved corpus before generating a response (Lewis et al., 2020). Citation enforcement requires every response to display the verifiable source. Refusal patterns train the model to decline questions outside the grounded corpus. Each mitigation reduces but does not eliminate hallucination risk. Human review of AI outputs is therefore not an optional quality check. It is a governance requirement in any deployment serving public health learners (WHO, 2024).

## 2.3 Retrieval-Augmented Generation for Domain-Specific Knowledge

Retrieval-Augmented Generation was introduced by Lewis et al. in 2020 as a method for grounding generative models in an external corpus. Subsequent work in medical and legal domains established RAG as the dominant pattern for high-stakes domain applications (Yang et al., 2023). The architecture is now mature: an embedding model indexes the corpus, a retriever returns the most relevant chunks for a given query, and the language model generates a response constrained by the retrieved context. Performance depends on chunk size, embedding quality, retriever recall, and the prompt template binding retrieved context to the response. Each parameter requires empirical tuning.

Recent benchmarks on domain-specific RAG in clinical settings report retrieval accuracy above 90 percent for structured guideline content (Zakka et al., 2024). Accuracy degrades on questions requiring multi-document reasoning, numerical computation, and graphical interpretation. The pilot expects high accuracy on factual retrieval from the Data Science Course and lower accuracy on multi-step analytical questions. The evaluation will report both separately.

## 2.4 Adaptive Learning in Resource-Constrained Settings

Adaptive learning systems modify content delivery based on learner performance. Commercial platforms demonstrated learning gains in undergraduate science education (Pane et al., 2014). Translation to public health workforce contexts is limited. African deployments face three constraints: low bandwidth, intermittent connectivity, and content authored at a granularity too coarse for adaptive routing. SCORM packages, the dominant interoperability standard in workforce LMS deployments, report completion as a single pass or fail event. The system has no signal on which specific topic a learner struggled with.

Two adaptive strategies address the granularity problem. The H5P content format reports interaction-level events through the xAPI specification, exposing topic-level performance data (Kay and Kummerfeld, 2019). Recommendation engines layered on top of the LMS use these events to route the learner to targeted microlearning. DataCurso and Nolej are two vendors in this space with deployment experience in francophone and lusophone Africa.

## 2.5 Equity, Bias, and Ethical AI in Health Contexts

WHO published its first guidance on AI ethics and governance for health in 2021 and updated it in 2024 (WHO, 2024). The six core principles are: protect autonomy, promote human safety and wellbeing, ensure transparency, foster responsibility and accountability, ensure inclusiveness and equity, and promote responsive and sustainable AI. Africa CDC adapted these principles in its Continental AI Strategy 2024 to 2034. A-FIRST operationalizes the principles through source-grounded retrieval, citation enforcement, human review checkpoints, learner-facing AI disclosure, and a bias audit across language, region, and topic.

Equity in AI deployment is not a checkbox. Africa CDC operates across 55 member states with five working languages. A model trained predominantly on English-language sources and evaluated only on urban-bandwidth learners will reproduce existing inequities at scale. The pilot evaluates the system on connections below 5 Mbps and documents the learner experience by connection type.

## 2.6 Gaps in the Literature

Five gaps frame the A-FIRST research question as context.

-   No published evaluation has measured the effect of a source-grounded LLM assistant on public health learners in an African public health LMS.

-   RAG benchmarks in medicine focus on clinical decision support, not on workforce training comprehension.

-   Adaptive learning research in Africa focuses on K-12 and undergraduate contexts, not on frontline public health continuing education.

-   Equity evidence by bandwidth is rare. Most reported AI-in-LMS pilots ran on broadband connections.

-   Documentation of what the AI fails to answer (the unanswered question log) is rarely published, yet this artifact is itself a content gap diagnostic.

# 3. Methods

## 3.1 Setting and Population

The study runs within the Africa CDC Moodle learning management system hosted on Africa CDC infrastructure in Addis Ababa, Ethiopia. The pilot course is the Data Science Course authored by the Africa CDC Surveillance Division. The study population is the public health professionals enrolled in the Data Science Course during the 2026 to 2027 training cycle, estimated at 50 enrollees across the African Union member states. Enrollees hold professional health qualifications and English proficiency adequate for written course material.

## 3.2 Study Design

A-FIRST is a pre-post within-subject pilot study with embedded developmental evaluation. Each enrollee serves as their own control. Baseline assessment captures retrieval accuracy and comprehension before AI assistant exposure. Endline assessment captures the same outcomes after the three-month pilot exposure period (Sprints 9 to 11). Mid-pilot review at Sprint 10 informs intervention adaptation. Qualitative interviews enrich the quantitative comparison. The 12-month capstone timeline provides an extended development and iteration window before the pilot, and a dedicated evaluation and reporting phase afterward.

## 3.3 Eligibility Criteria

### Inclusion

-   Active registration in the Data Science Course on the Africa CDC Moodle.

-   Provision of informed consent for pilot participation and data collection.

### Exclusion

-   Enrollees opting out of the pilot at any point in the cycle.

-   Fellows unable to access the Moodle environment for technical reasons beyond pilot scope.

## 3.4 Sample Size

The pilot recruits the full Data Science Course cohort, estimated at 50 learners. Statistical power for a paired comparison with 50 participants supports detection of large effects (Cohen d at least 0.8). Moderate or small effects will not reach conventional significance thresholds. The evaluation reports effect sizes and 95 percent confidence intervals alongside p-values, with practical significance interpreted in the discussion. This limitation is acknowledged openly. Stakeholder communication frames findings as a pilot with indicative, not definitive, results.

## 3.5 Sampling Method

Sampling is total enumeration of the registered participants. The pilot does not exclude enrollees by performance, region, or specialty. All eligible enrollees receive the invitation. Participation is voluntary.

## 3.6 Data Sources and Data Collection

### Quantitative Data

-   Baseline and endline competency assessments delivered through Moodle quiz activities, scored automatically and reviewer-validated.

-   Moodle xAPI event stream capturing time on task, content sequence, quiz attempts, and AI assistant interactions.

-   AI response logs with query, retrieved sources, model output, latency, and reviewer accuracy classification.

-   Connectivity metadata derived from session telemetry to support equity analysis by bandwidth.

### Qualitative Data

-   Learner experience survey at midpoint (Sprint 10) and endpoint (Sprint 11).

-   Semi-structured interviews with 6 to 8 purposively sampled enrollees at endline, covering experience, perceived trust, and barriers.

-   Decision log entries from review meetings, capturing intervention adaptations across the 12-month cycle.

-   Unanswered question log: every learner question the AI declined or failed to answer, classified by category and content gap.

### Data Management

All data are stored on Africa CDC infrastructure under existing institutional security controls. No learner data are processed on personal hardware. Pseudonymization is applied to all learner records before analysis. Identifying keys are held by the host site supervisor under controlled access.

## 3.7 Variables and Measures

|                               |                              |                                                                                |                                       |
|-------------------------------|------------------------------|--------------------------------------------------------------------------------|---------------------------------------|
| **Variable**                  | **Type**                     | **Measure**                                                                    | **Source**                            |
| Retrieval accuracy            | Continuous (percent)         | Correct answer on 50-question reviewer-audited set                             | AI response logs                      |
| Source traceability           | Binary (yes/no)              | Citation present and verifiable on response                                    | AI response logs                      |
| Comprehension                 | Continuous (score)           | Pre-post quiz score delta                                                      | Moodle quiz attempts                  |
| Adaptive path differentiation | Continuous (percent)         | Share of learners with at least 20% content bypass or replacement              | xAPI event stream                     |
| Learner experience            | Mixed                        | NPS at least +20, confidence gain, AI utilization rate, course completion rate | End-of-pilot survey, Moodle analytics |
| Unanswered question rate      | Continuous (count, category) | Frequency and category of declined or unanswered queries                       | Unanswered question log               |

## 3.8 Data Analysis

### Statistical Analysis Plan

Pre-post retrieval accuracy and comprehension scores are compared using a paired t-test where normality assumptions hold, and a Wilcoxon signed-rank test where they do not. Effect size is reported as Cohen d. Given the small sample (approximately 50 learners), the study is powered to detect large effects (d at least 0.8) but not moderate effects. Results are interpreted with 95 percent confidence intervals and practical significance, not p-values alone. A secondary minimum threshold of at least 15 percent mean improvement is retained for go/no-go reporting purposes.

### Qualitative Analysis

Interview transcripts are coded thematically using an inductive-deductive approach. Deductive codes follow the WHO ethical AI principles. Inductive codes emerge from the data. Two coders work independently on a 20 percent subsample to assess intercoder reliability.

## 3.9 Ethical Considerations

Ethical clearance is required before any participant data are collected. Pilot launch at Sprint 9 (February 2027) is contingent on clearance confirmed by Sprint 6 (November 2026). The protocol will be submitted to the Africa CDC ethics review process and the relevant Ethiopian national institutional review board.

### Benefits

-   Improved access to approved public health content during the Data Science Course.

-   Contribution to a continental institutional capability with direct relevance to fellow career progression.

### Risks

-   Exposure to inaccurate or incomplete AI responses, mitigated by source citations, human review checkpoints, an escalation mechanism, and a visible AI disclaimer on every response.

-   Time burden of participation, mitigated by integration into existing course activities.

### Informed Consent

Enrollees receive a written information sheet, a verbal briefing during the Sprint 9 onboarding session (synchronous, 60 minutes), and sign a consent form before baseline assessment.

### AI Disclosure to Learners

Learners are informed at onboarding and at the point of interaction that responses are generated by an AI assistant grounded in approved course content. A visible disclaimer on every AI response reads: This response is generated by AI and grounded in approved course content. Verify operational decisions with a qualified supervisor.

### Human-in-the-Loop Escalation

Learners encountering inaccurate, incomplete, or misleading AI responses submit concerns through a designated escalation mechanism within the Moodle environment. Escalated responses undergo human review within 48 hours. Corrective action is logged in the project repository.

### Bias and Fairness Audit

A structured bias audit using a fairness checklist adapted from WHO AI ethics guidance is conducted at Sprint 5 (October 2026) by the fellow and one external reviewer. The audit samples AI responses across at least three language groups and five topic areas. Results are documented in the Sprint 5 review report.

## 3.10 Dissemination of Results

-   Peer-reviewed manuscript submitted to a journal target confirmed by Sprint 10, with institutional approvals secured before Sprint 12. Target journals include Lancet Global Health, BMJ Global Health, NEJM AI, and Frontiers in Digital Health.

-   Open replication guide hosted on the Africa CDC project repository.

-   Presentations at AES annual scientific conference, Africa CDC CPHIA, HIMSS Africa, and AMEE.

-   Policy brief for Africa CDC leadership on scaling AI-enabled learning across the AES track and partner workforce programs.

# 4. Timeline and Budget

## 4.1 Project Timeline

The capstone executes as 12 monthly sprints from June 2026 to May 2027, with dissemination continuing through June 2027. Weekly supervisor reviews run within each monthly sprint.

|            |           |                                    |                                                                                                                                                                                               |
|------------|-----------|------------------------------------|-----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|
| **Sprint** | **Month** | **Phase and Goal**                 | **End-of-Sprint Deliverables**                                                                                                                                                                |
| 1          | Jun 2026  | Scope Lock and Course Confirmation | Refined work plan signed off by host site supervisor and faculty advisors. Data Science Course confirmed as pilot course. Jira board live. Project sign-off form completed.                   |
| 2          | Jul 2026  | Learner Profile and Ethics Start   | Cohort survey approved by host site supervisor. Ethics application submitted to Africa CDC and national IRB. Moodle admin access confirmed. Sandbox contingency triggered if needed.          |
| 3          | Aug 2026  | Native Moodle AI Configuration     | Chat, summarization, and adaptive quiz active in test environment. Model evaluation matrix complete with accuracy, latency, and cost thresholds.                                              |
| 4          | Sep 2026  | Source Grounding and Retrieval     | Data Science Course content indexed. Source grounding tested on 50 representative learner questions. External bias reviewer confirmed for Sprint 5.                                           |
| 5          | Oct 2026  | Internal Review and Bias Audit     | Demo to supervisor. Structured bias audit completed (3 language groups, 5 topic areas). Go/no-go decision against defined criteria.                                                           |
| 6          | Nov 2026  | DataCurso Onboarding               | DataCurso license confirmed. Integration plan documented. Ethics clearance confirmed. If not confirmed, pilot timeline escalated.                                                             |
| 7          | Dec 2026  | Nolej and H5P Conversion           | Nolej active. Course supplemented with H5P interactive elements at sub-unit level. Unanswered question log schema defined.                                                                    |
| 8          | Jan 2027  | Integration and Beta Test          | ADAPTIVE layer integration complete. Beta test with 3 to 5 volunteer enrollees. Critical issues resolved. Human review of AI sample (Sprint 10 preview).                                      |
| 9          | Feb 2027  | Launch                             | Onboarding session delivered (60 min synchronous). Baseline assessment captured. Informed consent collected.                                                                                  |
| 10         | Mar 2027  | Mid-Point Review                   | Engagement and quiz data reviewed. Learner feedback collected. Issues triaged. Human review of AI outputs sample. Journal target and author list confirmed.                                   |
| 11         | Apr 2027  | Pilot Close and Evaluation         | Endline assessment. Comparison against baseline. Learner experience survey closed. Semi-structured interviews (6 to 8 enrollees). Institutional approval for manuscript submission confirmed. |
| 12         | May 2027  | Reporting and Handover             | Final report. READINESS phase handover specification. Lessons learned. AI-enabled course replication guide. Manuscript draft for peer review.                                                 |

*June 2027: Dissemination activities continue. Continental briefing, conference presentations, manuscript submission.*

## 4.2 Budget

The budget below presents a high-level summary. All figures are estimates in United States Dollars (USD). The fellow contributes labor through the fellowship stipend, which is not costed here. Software licensing duration reflects the 12-month project cycle.

|                                        |                    |                                                                              |
|----------------------------------------|--------------------|------------------------------------------------------------------------------|
| **Category**                           | **Estimate (USD)** | **Notes**                                                                    |
| Software licensing (DataCurso, Nolej)  | 6,000              | Annual institutional or trial license, both vendors (Sprints 6 to 12)        |
| Cloud AI API usage (pilot fallback)    | 3,000              | Cost ceiling for 12-month cycle. Quotas and prompt-length controls enforced. |
| Compute infrastructure (Africa CDC VM) | 0                  | In-kind contribution by Africa CDC. VM 10.215.70.14 provisioned.             |
| Ethics submission and processing       | 0                  | In-kind institutional ethics committees                                      |
| Manuscript and dissemination           | 2,000              | Open-access fees, copy editing, replication guide hosting, conference travel |
| Contingency (10 percent)               | 1,230              | Reserved for unforeseen requirements                                         |
| **TOTAL**                              | **12,230**         |                                                                              |

# 5. References

1\. Africa CDC. (2023). Continental Strategy on Public Health Workforce Development 2023 to 2030. Addis Ababa: Africa Centres for Disease Control and Prevention.

2\. Africa CDC. (2024). Continental Artificial Intelligence Strategy 2024 to 2034. Addis Ababa: Africa Centres for Disease Control and Prevention.

3\. Africa CDC. (2024). Continental Digital Health Strategy 2024 to 2030. Addis Ababa: Africa Centres for Disease Control and Prevention.

4\. Barteit, S., Guzek, D., Jahn, A., Barnighausen, T., Jorge, M. M., and Neuhann, F. (2020). Evaluation of e-learning for medical education in low- and middle-income countries: A systematic review. Computers and Education, 145, 103726.

5\. Bommasani, R., Hudson, D. A., Adeli, E., et al. (2022). On the opportunities and risks of foundation models. Stanford CRFM Report.

6\. Eysenbach, G. (2023). The role of ChatGPT, generative language models, and artificial intelligence in medical education. JMIR Medical Education, 9, e46885.

7\. Frehywot, S., Vovides, Y., Talib, Z., Mikhail, N., Ross, H., Wohltjen, H., et al. (2013). E-learning in medical education in resource constrained low- and middle-income countries. Human Resources for Health, 11(1), 4.

8\. Greenhalgh, T., Wherton, J., Papoutsi, C., et al. (2017). Beyond adoption: A new framework for theorizing and evaluating nonadoption, abandonment, and challenges to the scale-up, spread, and sustainability of health and care technologies. Journal of Medical Internet Research, 19(11), e367.

9\. Kasonde, L., and Bonnel, R. (2020). Scaling up Africa health workforce education and training: Policy implications. Health Affairs, 39(11), 1962 to 1969.

10\. Kay, J., and Kummerfeld, B. (2019). Open learner models and learning analytics for adaptive learning. Lecture Notes in Computer Science, 11491, 3 to 16.

11\. Kung, T. H., Cheatham, M., Medenilla, A., et al. (2023). Performance of ChatGPT on USMLE: Potential for AI-assisted medical education using large language models. PLOS Digital Health, 2(2), e0000198.

12\. Lewis, P., Perez, E., Piktus, A., et al. (2020). Retrieval-Augmented Generation for knowledge-intensive NLP tasks. Advances in Neural Information Processing Systems, 33, 9459 to 9474.

13\. Liu, X., Faes, L., Kale, A. U., et al. (2019). A comparison of deep learning performance against healthcare professionals in detecting diseases from medical imaging. The Lancet Digital Health, 1(6), e271 to e297.

14\. Pane, J. F., Steiner, E. D., Baird, M. D., and Hamilton, L. S. (2014). Effectiveness of cognitive tutor algebra I at scale. Educational Evaluation and Policy Analysis, 36(2), 127 to 144.

15\. Patton, M. Q. (2011). Developmental Evaluation: Applying complexity concepts to enhance innovation and use. New York: Guilford Press.

16\. Sallam, M. (2023). ChatGPT utility in healthcare education, research, and practice: Systematic review on the promising perspectives and valid concerns. Healthcare, 11(6), 887.

17\. Singhal, K., Azizi, S., Tu, T., et al. (2023). Large language models encode clinical knowledge. Nature, 620, 172 to 180.

18\. Topol, E. J. (2019). High-performance medicine: The convergence of human and artificial intelligence. Nature Medicine, 25(1), 44 to 56.

19\. Westley, F., Zimmerman, B., and Patton, M. Q. (2007). Getting to maybe: How the world is changed. Toronto: Vintage Canada.

20\. WHO. (2021). Ethics and Governance of Artificial Intelligence for Health: WHO guidance. Geneva: World Health Organization.

21\. WHO. (2022). Working for Health: Action plan for African region 2022 to 2030. Brazzaville: WHO Regional Office for Africa.

22\. WHO. (2024). Ethics and Governance of Artificial Intelligence for Health: Guidance on large multi-modal models. Geneva: World Health Organization.

23\. WHO and ITU. (2020). Digital Health Platform Handbook: Building a digital information infrastructure for health. Geneva: World Health Organization.

24\. Yang, X., Chen, A., PourNejatian, N., et al. (2023). A large language model for electronic health records. NPJ Digital Medicine, 5, 194.

25\. Zakka, C., Shad, R., Chaurasia, A., et al. (2024). Almanac: Retrieval-augmented language models for clinical medicine. NEJM AI, 1(2).

26\. Zhou, L., Pan, S., Wang, J., and Vasilakos, A. V. (2017). Machine learning on big data: Opportunities and challenges. Neurocomputing, 237, 350 to 361.

27\. Bekele, A., Tessema, F., and Mekonnen, Y. (2021). Capacity building for the African public health workforce: Lessons from the African Field Epidemiology Network. Pan African Medical Journal, 38, 280.
