# A-FIRST Phase 2: Building the Adaptive Layer
**Architecture, Technical Specification, and Implementation Roadmap**
*Africa CDC — Data Science and Analytics in Public Health*  
*Document Version:* 2.0 (Post-August 21 Alignment)

---

## 1. Executive Summary & Foundational Principle

### The Core Idea
> **The adaptive layer does not create a completely new course for every learner. It builds a dynamic learner competency profile from multiple empirical signals, identifies knowledge and competency gaps, and dynamically routes the learner to existing, validated microlearning units.**

This principle ensures that A-FIRST remains scientifically defensible, pedagogically sound, and feasible within the project's scope:
1. **Curriculum Integrity**: Core instructional content is curated, validated by Africa CDC subject matter experts, and vetted for clinical and public health accuracy—never hallucinated by generative models on the fly.
2. **Pedagogical Actionability**: "AI identifies &rarr; AI recommends &rarr; validated microlearning delivers &rarr; learner is reassessed."
3. **Developmental Evaluation (DE)**: All thresholds and models are treated as hypotheses tested through A/B experiments (e.g., comparing 3-signal vs 5-signal gap classification thresholds) to measure downstream routing consequences, false positives, and bypass rates.

---

## 2. Production Baseline & Existing Assets (Server `10.215.70.14`)

An inspection of the live Moodle 5.0 database (`moodle`) on the Africa CDC production server reveals that key scaffolding is already in place:

### A. Existing Assessment Inventory (Course ID 2: *Data Science for Health*)
| Quiz ID | Quiz Name | Questions | Current Coverage | Status / Alignment Need |
| :--- | :--- | :--- | :--- | :--- |
| **1** | **Placement Test** | 10 | Module 1.1 & 1.2 only | **Major Gap**: Currently covers only data literacy & governance; must be expanded across all 9 modules to serve as a true course pre-assessment. |
| **2** | **Module 1.1 Knowledge Check** | 5 | Module 1.1 (Q71–Q75) | Formative signal for Module 1.1 |
| **3** | **Module 1.2 Knowledge Check** | 5 | Module 1.2 (Q76–Q80) | Formative signal for Module 1.2 |
| **4** | **Module 2.1 Knowledge Check** | 5 | Module 2.1 (Q55–Q59) | Formative signal for Module 2.1 |
| **5** | **Module 2.2 Knowledge Check** | 5 | Module 2.2 (Q81–Q85) | Formative signal for Module 2.2 |
| **6** | **Module 3.1 Knowledge Check** | 5 | Module 3.1 (Q60–Q64) | Formative signal for Module 3.1 |
| **7** | **Module 3.2 Knowledge Check** | 5 | Module 3.2 (Q86–Q90) | Formative signal for Module 3.2 |
| **8** | **Module 4.1 Knowledge Check** | 6 | Module 4.1 (Q65–Q70) | Formative signal for Module 4.1 (GLMs) |
| **9** | **Module 4.2 Knowledge Check** | 5 | Module 4.2 (Q96–Q100)| Formative signal for Module 4.2 (ML) |
| **10**| **Module 4.3 Knowledge Check** | 5 | Module 4.3 (Q91–Q95) | Formative signal for Module 4.3 (NLP) |
| **11**| **Comprehensive Course Quiz** | 10 | Summative Post-test | Post-course evaluation benchmark |

### B. Moodle Native Competency Infrastructure
Moodle 5.0 includes the full native competency subsystem:
- `mdl_competency_framework`, `mdl_competency`, `mdl_competency_coursecomp`
- `mdl_competency_usercomp`, `mdl_competency_usercompcourse`, `mdl_competency_evidence`

A-FIRST can anchor its data structures into Moodle's native framework while running an adaptive decision engine on top.

### C. AI Tutor Signal Pipeline (`block_ollama_chat`)
- The active AI Tutor block already routes queries through `/rag/` and receives matching module sources (`source: "Module X.Y..."`) and similarity scores.
- This provides an immediate channel to emit **Signal 3 (AI Interaction events)** into the learner profile.

---

## 3. The Four Core Projects of Phase 2

```
┌─────────────────────────────────────────────────────────────────────────┐
│                           PHASE 2 ARCHITECTURE                          │
├────────────────────┬────────────────────┬───────────────────────────────┤
│ P2.1 LEARNER MODEL │ P2.2 GAP DETECTION │ P2.3 MICROLEARNING (H5P/xAPI) │
│ • 9 Course Modules │ • Pre-assessment   │ • 5-10 min interactive units  │
│ • Competencies     │ • In-course quizzes│ • Specific learning formats   │
│ • Proficiency state│ • AI Tutor signals │ • Mastery criteria            │
└─────────┬──────────┴─────────┬──────────┴───────────────┬───────────────┘
          │                    │                          │
          └────────────────────┼──────────────────────────┘
                               ▼
            ┌──────────────────────────────────────────┐
            │  P2.4 ADAPTIVE ROUTING & DASHBOARD       │
            │  • Recommendation Engine (Rules + Graph) │
            │  • Content Bypass (>=20% Target)         │
            │  • "My Learning Profile" Dashboard       │
            └──────────────────────────────────────────┘
```

---

### Project 2.1 — Learner Competency Model (LCM v1)

**Purpose**: Define what the system knows about each learner, structuring competencies in a clean hierarchy.

#### Competency Hierarchy
$$\text{Course} \longrightarrow \text{Module} \longrightarrow \text{Topic} \longrightarrow \text{Learning Objective} \longrightarrow \text{Competency}$$

#### Competency Data Structure
```json
{
  "learner_id": 104,
  "course_id": 2,
  "competencies": {
    "COMP-1.1.1": {
      "code": "DATA_LITERACY_FOUNDATION",
      "module": "1.1",
      "title": "Define and classify health data types by structure and function",
      "proficiency_state": "COMPETENT",
      "proficiency_score": 0.85,
      "evidence_count": 4,
      "signals": {
        "pre_assessment": {"score": 1.0, "timestamp": "2026-09-04T08:00:00Z"},
        "quiz_items": [{"item_id": 71, "correct": true}, {"item_id": 72, "correct": true}],
        "h5p_events": [],
        "ai_interactions": [{"topic": "1.1", "confidence": 0.82, "flagged": false}]
      },
      "active_gap": false,
      "recommended_unit": null,
      "last_updated": "2026-09-04T08:15:00Z"
    }
  }
}
```

#### Proficiency States
1. **UNASSESSED**: Learner has not yet completed diagnostic or topic tasks.
2. **EMERGING** (Knowledge Gap): Evidence indicates score $< 70\%$ or repeated incorrect attempts on linked items.
3. **COMPETENT**: Score $70\% - 89\%$ on topic-level assessment.
4. **MASTERED**: Score $\ge 90\%$ on pre-assessment or post-remediation assessment (qualifies for content bypass).

---

### Project 2.2 — Competency & Gap Assessment Engine

**Purpose**: Synthesize multiple assessment signals to reliably detect competency gaps without relying on a single quiz score.

```
                  EVIDENCE SIGNALS
  ┌───────────────────────┬───────────────────────┬───────────────────────┐
  │ Signal 1: Pre-Assess  │ Signal 2: Performance │ Signal 3: AI Signals  │
  │ • Placement Test      │ • Module Quizzes      │ • Questions to Tutor  │
  │ • Diagnostic coverage │ • Question-level item │ • RAG topic matching  │
  │ • Prior knowledge     │ • Attempts & errors   │ • Soft signal (weight)│
  └───────────┬───────────┴───────────┬───────────┴───────────┬───────────┘
              └───────────────────────┼───────────────────────┘
                                      ▼
                      MULTI-SIGNAL GAP DETECTOR
                      • Evidence accumulation rule
                      • A/B Threshold testing (3 vs 5)
                      • False positive/negative filter
                                      ▼
                          FLAGGED COMPETENCY GAP
```

#### The Three Signals
1. **Signal 1 — Pre-Assessment (Diagnostic)**:
   - Assesses baseline proficiency before a module.
   - *Action*: Expand Quiz 1 (Placement Test) from 10 questions to 18–27 balanced questions covering all 9 modules (2–3 questions per module).
2. **Signal 2 — In-Course Learning Performance (Formative)**:
   - Question-level performance in Quizzes 2–10.
   - H5P interaction events (attempts, scores, repeated failures).
3. **Signal 3 — AI Tutor Interactions (Behavioral / Soft Signal)**:
   - When a learner asks the AI tutor questions about a topic, the question is mapped via RAG to the relevant competency.
   - *Crucial Rule*: An AI inquiry is a **soft signal of uncertainty**, not automatic proof of a gap. A single question does not trigger remediation unless reinforced by Quiz/H5P struggles.

#### Developmental Evaluation (DE) Experiment: Threshold A/B Testing
To ensure thresholds are not arbitrary, the engine supports configurable threshold parameters:
- **Variant A (Aggressive Detection)**: Gap flagged if $\text{Score} < 70\%$ OR ($1 \text{ Quiz Failure} + 1 \text{ AI Query}$). Total threshold = **3 signal points**.
- **Variant B (Conservative Detection)**: Gap flagged only if $\text{Score} < 65\%$ AND ($2 \text{ Quiz Failures} + 1 \text{ AI Query}$). Total threshold = **5 signal points**.
- **Metrics Evaluated**:
  - False positive rate (unnecessary remedial routing).
  - False negative rate (unaddressed weaknesses persisting to summative quiz).
  - Learner drop-off / frustration with remediation.

---

### Project 2.3 — Microlearning Content Layer (H5P Library)

**Purpose**: A curated repository of modular, competency-linked learning objects that can be completed in 5 to 10 minutes.

#### Microlearning Unit Metadata Schema
| Attribute | Type | Description | Example |
| :--- | :--- | :--- | :--- |
| `unit_id` | String | Unique identifier | `ML-2.1-AUTO` |
| `title` | String | Clear pedagogical title | *Automating Data Cleaning Scripts in Python* |
| `module` | String | Course Module code | `Module 2.1` |
| `competency_id`| String | Target Competency code | `COMP-2.1.3` |
| `learning_objective` | String | Specific LO | *Explain role of automation in reproducible pipelines* |
| `h5p_type` | String | H5P interactive content format | `Interactive Video` / `Branching Scenario` |
| `est_time_mins` | Integer | Estimated duration | `7` |
| `prerequisites` | List | Required competencies | `["COMP-1.1.1"]` |
| `mastery_threshold` | Float | Score needed to clear gap | `0.80` (80%) |

#### Mapping Learning Needs to H5P Formats
- **Recall & Terminology**: *Flashcards* / *Single Choice Set*
- **Conceptual Workflow**: *Interactive Book* / *Column Layout*
- **Data Classification**: *Drag and Drop* (e.g. classifying Structured vs Semi-structured data)
- **Public Health Decision-Making**: *Branching Scenario* (e.g. outbreak data anomaly investigation)
- **Visual Analytics**: *Image Hotspots* / *Interactive Chart Exploration*

---

### Project 2.4 — Adaptive Routing & Learner Dashboard

**Purpose**: Orchestrate the adaptive learning loop and present clear, motivating progress tracking to the learner.

#### The Core Adaptive Workflow Loop

```
┌────────────────────────────────────────────────────────┐
│ 1. LEARNER ONBOARDING & PRE-ASSESSMENT (Placement Test)│
└───────────────────────────┬────────────────────────────┘
                            │
                            ▼
┌────────────────────────────────────────────────────────┐
│ 2. DYNAMIC LEARNER COMPETENCY PROFILE GENERATED        │
└───────────────┬────────────────────────┬───────────────┘
                │                        │
       [Demonstrated Mastery]     [Identified Gap]
                │                        │
                ▼                        ▼
┌───────────────────────────────┐ ┌───────────────────────────────┐
│ 3. CONTENT BYPASS             │ │ 4. TARGETED MICROLEARNING     │
│ Advance past basic module     │ │ Route to 5-10 min H5P unit    │
│ (Target: >=20% bypass rate)   │ │ with focused interactive task │
└───────────────┬───────────────┘ └──────────────┬────────────────┘
                │                                │
                │                                ▼
                │                 ┌───────────────────────────────┐
                │                 │ 5. TOPIC RE-ASSESSMENT      +++++++++++++++++  │
                │                 │ (Formative check on gap)      │
                │                 └──────────────┬────────────────┘
                │                                │
                └───────────────┬────────────────┘
                                │
                                ▼
┌────────────────────────────────────────────────────────┐
│ 6. PROFILE UPDATED & NEXT RECOMMENDED STEP SERVED      │
└────────────────────────────────────────────────────────┘
```

#### Learner Dashboard: *"My Learning Profile"*
A dedicated block or dashboard tab in Moodle displaying:
1. **Competency Wheel / Matrix**: Visual indicators for all 9 modules (Green = Mastered, Blue = Competent, Amber = Action Required).
2. **Current Recommendations**: Direct links to microlearning units for any active gaps.
3. **Bypass Badges**: Recognition for testing out of foundational content based on demonstrated competency.
4. **AI Tutor Context**: The AI Tutor automatically greets the learner with awareness of their current focus topic and active learning goals.

---

## 4. Phase 2 Evaluation Gates & Success Criteria

| Criterion | Evaluation Metric | Target Benchmark |
| :--- | :--- | :--- |
| **1. Assessment Validity** | Content validity ratio between quiz items and learning objectives | $100\%$ alignment across all 9 modules |
| **2. Gap Detection Accuracy** | Agreement between multi-signal detector and post-module performance | $\ge 85\%$ sensitivity, $<15\%$ false positive rate |
| **3. Routing Accuracy** | Identified gap maps to correct microlearning unit | $100\%$ semantic and technical mapping |
| **4. Adaptive Differentiation** | Proportion of cohort experiencing differentiated pathways | **$\ge 20\%$ of learners** receive content bypass or tailored remediation |
| **5. Closed Learning Loop** | Re-assessment after microlearning updates competency state | $100\%$ profile synchronization within Moodle |

---

## 5. Implementation Roadmap: Execution Phases

```
P2.1: Learner Model (Weeks 1-3)
  ├── Task 1: Complete 9-module competency mapping matrix
  ├── Task 2: Database schema design (Moodle custom/native tables)
  └── Task 3: Profile generator prototype

P2.2: Gap Detection & AI Signals (Weeks 3-6)
  ├── Task 4: Expand Placement Test (Quiz 1) across modules 1-4
  ├── Task 5: AI Tutor signal logging hook in block_ollama_chat
  └── Task 6: Multi-signal scoring engine & A/B threshold harness

P2.3: Microlearning Library (Weeks 5-8)
  ├── Task 7: Priority topic selection (GLMs, Wrangling, Data Governance)
  ├── Task 8: Author H5P interactive learning units
  └── Task 9: Metadata catalog & prerequisite graph

P2.4: Adaptive Routing & Dashboard (Weeks 7-10)
  ├── Task 10: Routing workflow integration in Moodle
  ├── Task 11: "My Learning Profile" learner dashboard UI
  └── Task 12: End-to-end DE evaluation with test learner cohorts
```
