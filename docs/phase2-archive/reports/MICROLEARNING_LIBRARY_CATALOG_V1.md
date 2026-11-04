# A-FIRST Microlearning Library Catalog (v1)
## Modular Interactive Content Layer for Adaptive Public Health Learning
*Africa CDC — EPR AI Modelling Project (Phase 2, P2.3)*  
*Document Version:* 1.0  
*Date:* September 4, 2026

---

## 1. Pedagogical Design & Architectural Principles

In accordance with the August 21 architectural decisions, the objective of Phase 2 Project 2.3 is **not** to build entire 45-minute courses from scratch with H5P.

Instead, the objective is to build a **modular repository of targeted, validated, 5- to 10-minute microlearning units** that the adaptive engine can dynamically prescribe when specific competency gaps are detected:

```
        COMPETENCY GAP DETECTED
  [COMP-2.1.3]: Handling Missing & Outlier Data
                   │
                   ▼
       ADAPTIVE RECOMMENDATION
  "Recommended Review: Techniques for Missing Values"
                   │
                   ▼
     TARGETED MICROLEARNING (H5P)
  Unit ID: ML-2.1-CLEAN (Duration: 7 mins)
  Format: Interactive Scenario / Decision Matrix
                   │
                   ▼
        EMBEDDED MICRO-ASSESSMENT
  3 Formative Check Questions (Mastery >= 80%)
                   │
                   ▼
     UPDATE LEARNER COMPETENCY PROFILE
  State updated: EMERGING ──► COMPETENT
```

### Microlearning Unit Standards
- **Duration**: Strict 5–10 minute completion window (average 7 minutes).
- **Cognitive Load**: Focuses on **one** atomic competency or skill per unit.
- **Pedagogical Alignment**: H5P content types are mapped to cognitive demands (e.g., Drag-and-Drop for classification, Branching Scenarios for outbreak investigation).
- **Formative Feedback**: Every unit concludes with a 2–3 question micro-assessment providing immediate feedback.

---

## 2. Microlearning Format Selection Matrix

| Cognitive Demand | Learning Need | Optimal H5P / Interactive Content Format | Pedagogical Rationale |
| :--- | :--- | :--- | :--- |
| **Recall / Terms** | Technical definitions, acronyms, and indicators | **Flashcards** / **Single Choice Set** | Spaced retrieval with immediate feedback |
| **Classification** | Distinguishing data types, scale levels, or sources | **Drag-and-Drop** / **Categorization** | Active visual sorting reinforces mental models |
| **Process / Pipeline** | Sequencing steps in wrangling, EDA, or NLP | **Interactive Sequence** / **Timeline** | Enforces temporal and logical order |
| **Interpretation** | Odds ratios, epi curves, dashboard KPIs, 2x2 tables | **Interactive Chart** / **Image Hotspots** | Contextual inspection with explanatory popups |
| **Application** | Choosing regression models or investigating outliers | **Branching Scenario** / **Case Challenge** | Realistic public health dilemmas with consequences |
| **Synthesis** | Auditing visualizations or dashboard ethics | **Interactive Book** / **Checklist Audit** | Multi-step evaluation of real-world artifacts |

---

## 3. Comprehensive 35-Unit Microlearning Library Catalog

### Module 1.1: Foundations of Health Data Literacy

| Unit ID | Title | Competency | Est. Time | Interactive Format | Mastery Criterion | Prerequisites |
| :--- | :--- | :--- | :---: | :--- | :---: | :--- |
| `ML-1.1-DEF` | *Defining Health Data Literacy* | `COMP-1.1.1` | 5 min | Flashcards & Quiz | $\ge 80\%$ (4/5) | None |
| `ML-1.1-STRUCT`| *Classifying Health Data by Structure & Function* | `COMP-1.1.2` | 7 min | Drag-and-Drop Sorting | $\ge 85\%$ | `COMP-1.1.1` |
| `ML-1.1-SOURCE`| *Navigating Public Health Surveillance & HMIS Sources*| `COMP-1.1.3` | 8 min | Interactive Decision Tree | $\ge 80\%$ | `COMP-1.1.2` |

---

### Module 1.2: Data Governance and Quality Assurance

| Unit ID | Title | Competency | Est. Time | Interactive Format | Mastery Criterion | Prerequisites |
| :--- | :--- | :--- | :---: | :--- | :---: | :--- |
| `ML-1.2-GOV` | *Core Principles of Health Data Governance* | `COMP-1.2.1` | 6 min | Interactive Book & Check | $\ge 80\%$ | `COMP-1.1.1` |
| `ML-1.2-RISK` | *Evaluating Governance Risks & Breaches* | `COMP-1.2.2` | 8 min | Branching Scenario | $\ge 80\%$ | `COMP-1.2.1` |
| `ML-1.2-DIMS` | *The 5 Dimensions of Health Data Quality* | `COMP-1.2.3` | 7 min | Interactive Table Audit | $\ge 85\%$ | `COMP-1.1.2` |
| `ML-1.2-ANOM` | *Detecting Quality Anomalies: The Kifaru Case* | `COMP-1.2.4` | 9 min | Outbreak Case Simulation | $\ge 85\%$ | `COMP-1.2.3` |

---

### Module 2.1: Data Wrangling and Reproducible Workflows

| Unit ID | Title | Competency | Est. Time | Interactive Format | Mastery Criterion | Prerequisites |
| :--- | :--- | :--- | :---: | :--- | :---: | :--- |
| `ML-2.1-INTRO`| *From Raw to Analysis-Ready: Wrangling Fundamentals* | `COMP-2.1.1` | 5 min | Interactive Video & Check | $\ge 80\%$ | `COMP-1.1.2` |
| `ML-2.1-STEPS`| *The 5 Stages of the Data Wrangling Pipeline* | `COMP-2.1.2` | 7 min | Interactive Sequence Sorter | $\ge 85\%$ | `COMP-2.1.1` |
| `ML-2.1-CLEAN`| *Resolving Missing Values, Duplicates & Outliers* | `COMP-2.1.3` | 8 min | Interactive Step-by-Step Task | $\ge 85\%$ | `COMP-1.2.3` |
| `ML-2.1-REPRO`| *Reproducible Workflows with Git and Scripts* | `COMP-2.1.4` | 8 min | Code & Architecture Tour | $\ge 80\%$ | `COMP-2.1.2` |

---

### Module 2.2: Exploratory Data Analysis (EDA)

| Unit ID | Title | Competency | Est. Time | Interactive Format | Mastery Criterion | Prerequisites |
| :--- | :--- | :--- | :---: | :--- | :---: | :--- |
| `ML-2.2-EDA` | *The Purpose and Scope of EDA in Surveillance* | `COMP-2.2.1` | 6 min | Interactive Presentation | $\ge 80\%$ | `COMP-2.1.1` |
| `ML-2.2-TABLES`| *Constructing & Interpreting 2x2 Contingency Tables* | `COMP-2.2.2` | 9 min | Interactive Calculator & Case | $\ge 85\%$ | `COMP-2.2.1` |
| `ML-2.2-CHECK`| *Spotting Residual Data Defects via Summary Stats* | `COMP-2.2.3` | 7 min | Diagnostic Audit Scenario | $\ge 80\%$ | `COMP-2.2.2` |
| `ML-2.2-PY` | *Python Scripts for Rapid EDA & Distributions* | `COMP-2.2.4` | 8 min | Interactive Code Inspection | $\ge 80\%$ | `COMP-2.1.4` |

---

### Module 3.1: Foundations of Data Visualisation

| Unit ID | Title | Competency | Est. Time | Interactive Format | Mastery Criterion | Prerequisites |
| :--- | :--- | :--- | :---: | :--- | :---: | :--- |
| `ML-3.1-VALUE`| *Visual Perception and Cognitive Encoding in Health* | `COMP-3.1.1` | 6 min | Interactive Graphic Tour | $\ge 80\%$ | `COMP-1.1.1` |
| `ML-3.1-TYPES`| *Matching Data Scales to Visual Attributes* | `COMP-3.1.2` | 7 min | Drag-and-Drop Matching | $\ge 85\%$ | `COMP-1.1.2` |
| `ML-3.1-SELECT`| *The Chart Selection Decision Matrix* | `COMP-3.1.3` | 8 min | Branching Decision Tree | $\ge 85\%$ | `COMP-3.1.2` |
| `ML-3.1-AUDIT`| *Auditing Visuals: Detecting Truncated Axes & Distortion*| `COMP-3.1.4`| 7 min | Image Hotspot Investigation | $\ge 85\%$ | `COMP-3.1.3` |

---

### Module 3.2: Time Series and Dashboards

| Unit ID | Title | Competency | Est. Time | Interactive Format | Mastery Criterion | Prerequisites |
| :--- | :--- | :--- | :---: | :--- | :---: | :--- |
| `ML-3.2-TIME` | *Deconstructing Level, Trend, and Seasonality* | `COMP-3.2.1` | 7 min | Interactive Time-Series Plot | $\ge 85\%$ | `COMP-3.1.3` |
| `ML-3.2-EPICURVE`| *Constructing Epidemic Curves and Alert Thresholds*| `COMP-3.2.2`| 8 min | Epi Curve Simulator | $\ge 85\%$ | `COMP-3.2.1` |
| `ML-3.2-KPI` | *Architecting Health Dashboards: KPIs and Filters* | `COMP-3.2.3` | 9 min | Dashboard Hotspot Exploration| $\ge 80\%$ | `COMP-3.2.1` |
| `ML-3.2-RULES`| *Dashboard Usability & 5-Second Glance Rules* | `COMP-3.2.4` | 6 min | Checklist & Quiz Challenge | $\ge 80\%$ | `COMP-3.2.3` |

---

### Module 4.1: Advanced Analytics: Generalized Linear Models (GLMs)

| Unit ID | Title | Competency | Est. Time | Interactive Format | Mastery Criterion | Prerequisites |
| :--- | :--- | :--- | :---: | :--- | :---: | :--- |
| `ML-4.1-GLM` | *Understanding Generalized Linear Models (GLMs)* | `COMP-4.1.1` | 7 min | Interactive Concept Guide | $\ge 80\%$ | `COMP-2.2.2` |
| `ML-4.1-DIFF` | *Logistic vs. Poisson Regression: Selecting the Right Model*| `COMP-4.1.2`| 8 min | Scenario Classification | $\ge 85\%$ | `COMP-4.1.1` |
| `ML-4.1-OR` | *Interpreting Odds Ratios, Relative Risks & Rates* | `COMP-4.1.3` | 9 min | Interactive Risk Calculator | $\ge 85\%$ | `COMP-4.1.2` |
| `ML-4.1-CASE` | *Applying GLMs to Vaccine Effectiveness & Risk Studies*| `COMP-4.1.4`| 8 min | Real-World Outbreak Study | $\ge 80\%$ | `COMP-4.1.3` |

---

### Module 4.2: Applied Analytics: Machine Learning

| Unit ID | Title | Competency | Est. Time | Interactive Format | Mastery Criterion | Prerequisites |
| :--- | :--- | :--- | :---: | :--- | :---: | :--- |
| `ML-4.2-STATS`| *Machine Learning vs. Classical Biostatistics* | `COMP-4.2.1` | 6 min | Interactive Comparison Table | $\ge 80\%$ | `COMP-4.1.1` |
| `ML-4.2-TASKS`| *Formulating Classification vs. Regression Problems* | `COMP-4.2.2` | 7 min | Task Sorting Exercise | $\ge 85\%$ | `COMP-4.2.1` |
| `ML-4.2-TREES`| *How Decision Trees and Random Forests Predict Outcomes*| `COMP-4.2.3`| 8 min | Visual Tree Traversal | $\ge 85\%$ | `COMP-4.2.2` |
| `ML-4.2-EVAL` | *Validating ML Models: Overfitting, Splits & Bias Checks*| `COMP-4.2.4`| 8 min | Model Bias & Audit Lab | $\ge 85\%$ | `COMP-4.2.3` |

---

### Module 4.3: Advanced Methods: Natural Language Processing (NLP)

| Unit ID | Title | Competency | Est. Time | Interactive Format | Mastery Criterion | Prerequisites |
| :--- | :--- | :--- | :---: | :--- | :---: | :--- |
| `ML-4.3-NLP` | *Unlocking Unstructured Text in Public Health* | `COMP-4.3.1` | 6 min | Narrative Text Explorer | $\ge 80\%$ | `COMP-1.1.2` |
| `ML-4.3-PIPE` | *The Text Preprocessing Pipeline: Cleaning to Tokens*| `COMP-4.3.2` | 8 min | Interactive Step Pipeline | $\ge 85\%$ | `COMP-4.3.1` |
| `ML-4.3-TFIDF`| *Representing Text as Numbers: Bag-of-Words vs. TF-IDF*| `COMP-4.3.3`| 8 min | Interactive Matrix Vectorizer | $\ge 85\%$ | `COMP-4.3.2` |
| `ML-4.3-OUTBREAK`| *NLP for Early Outbreak Signal Detection & Rumor Triage*| `COMP-4.3.4`| 9 min | Epidemic Signal Simulation | $\ge 85\%$ | `COMP-4.3.3` |

---

## 4. Metadata Schema Implementation (JSON)

Every microlearning unit is cataloged with the following machine-readable schema in `microlearning_library_v1.json`:

```json
{
  "unit_id": "ML-2.1-CLEAN",
  "title": "Resolving Missing Values, Duplicates & Outliers",
  "module": "Module 2.1",
  "topic": "Data Cleaning & Quality",
  "competency_id": "COMP-2.1.3",
  "learning_objective": "Describe basic data cleaning techniques and how they improve data quality",
  "bloom_level": "Apply",
  "difficulty": "Intermediate",
  "est_time_mins": 8,
  "h5p_format": "Interactive Scenario",
  "mastery_criterion": 0.85,
  "prerequisites": ["COMP-1.2.3", "COMP-2.1.2"],
  "content_url": "/mod/h5pactivity/view.php?id=101",
  "assessment": {
    "question_count": 3,
    "pass_score": 0.85
  }
}
```
