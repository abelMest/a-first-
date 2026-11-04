# A-FIRST Learner Competency Model (LCM v1)
## Competency Mapping Matrix for Data Science and Analytics in Public Health
*Africa CDC — EPR AI Modelling Project*  
*Document Version:* 1.0  
*Date:* September 4, 2026

---

## 1. Architectural Overview & Taxonomy Structure

The A-FIRST Learner Competency Model (LCM v1) provides the structural foundation for Phase 2's adaptive learning layer. It organizes the 9 course modules into a 5-tier pedagogical hierarchy:

$$\text{Course (DS4H)} \longrightarrow \text{Module (1.1–4.3)} \longrightarrow \text{Topic} \longrightarrow \text{Learning Objective (LO)} \longrightarrow \text{Competency (COMP)}$$

```
                   COURSE: Data Science for Health
                                  │
         ┌────────────────────────┴────────────────────────┐
   Module 1: Foundations                     Module 4: Advanced
   ├── 1.1 Data Literacy                     ├── 4.1 GLMs
   └── 1.2 Data Governance                   ├── 4.2 Machine Learning
                                             └── 4.3 NLP
         │
         ▼
   Topic: Types & Structure
         │
         ▼
   Learning Objective: Distinguish between structured and unstructured health data
         │
         ▼
   Competency [COMP-1.1.2]: Classify health datasets by structural schema and format
         │
         ├── EVIDENCE SIGNALS:
         │   ├── Pre-Assessment: Quiz 1 (Slot 2, Q5/Q6)
         │   ├── Formative Quiz: Quiz 2 (Slot 2, Q72)
         │   ├── AI Tutor: Inquiries matched to Topic 1.1 (BM25 score > 8.0)
         │   └── H5P / xAPI: Interactive drag-and-drop classification score
         │
         └── ADAPTIVE ACTION:
             ├── Demonstrated Mastery (>=90%) ───► Content Bypass
             └── Flagged Gap (<70% or 3 pts)   ───► Microlearning [ML-1.1-STRUCT]
```

### Proficiency States
1. **UNASSESSED (`UN`):** No evidence recorded yet.
2. **EMERGING / GAP (`EM`):** Score $<70\%$ or cumulative negative evidence $\ge 3$ signal points. Triggers remedial microlearning.
3. **COMPETENT (`CO`):** Score $70\% - 89\%$. Ready for progression.
4. **MASTERED (`MA`):** Score $\ge 90\%$. Eligible for content bypass ($\ge 20\%$ bypass goal).

---

## 2. Comprehensive 9-Module Competency Mapping Matrix

### Module 1.1: Foundations of Health Data Literacy

| Competency ID | Bloom's Level | Learning Objective (LO) | Competency Description | Pre-Test Item (Quiz 1) | Knowledge Check (Quiz 2) | Recommended Microlearning Unit |
| :--- | :--- | :--- | :--- | :--- | :--- | :--- |
| **COMP-1.1.1** | Remember / Understand | Define data literacy and core concepts related to health data | Define and articulate health data literacy, its components, and importance in public health practice. | Slot 1 (Q2, Q3, Q4) | Slot 1 (Q71) | `ML-1.1-DEF`: *Defining Health Data Literacy* (Flashcards / Quiz) |
| **COMP-1.1.2** | Understand / Analyze | Distinguish between the main types of health data | Classify health data by structure (structured, semi-structured, unstructured) and function (routine, surveillance, clinical). | Slot 2 (Q5, Q6) | Slot 2 (Q72) | `ML-1.1-STRUCT`: *Health Data Types & Formats* (Drag-and-Drop) |
| **COMP-1.1.3** | Apply | Identify typical internal and external data sources | Identify and compare health facility reports (HMIS/DHIS2), line lists, vital registration, and population census. | Slot 3 (Q7) | Slot 3 (Q73), Slot 4 (Q74), Slot 5 (Q75) | `ML-1.1-SOURCE`: *Public Health Data Sources* (Interactive Scenario) |

---

### Module 1.2: Data Governance and Quality Assurance

| Competency ID | Bloom's Level | Learning Objective (LO) | Competency Description | Pre-Test Item (Quiz 1) | Knowledge Check (Quiz 3) | Recommended Microlearning Unit |
| :--- | :--- | :--- | :--- | :--- | :--- | :--- |
| **COMP-1.2.1** | Understand | Define data governance and quality in public health | Explain principles of data stewardship, security, privacy, and accountability in health systems. | Slot 4 (Q8), Slot 5 (Q9) | Slot 1 (Q76) | `ML-1.2-GOV`: *Principles of Health Data Governance* (Interactive Book) |
| **COMP-1.2.2** | Understand / Evaluate | Explain why governance and data quality are essential | Articulate risks of poor data governance and evaluate consequences of compromised health data. | Slot 6 (Q10) | Slot 2 (Q77) | `ML-1.2-RISK`: *Governance Risks & Consequences* (Case Study) |
| **COMP-1.2.3** | Analyze | Describe key dimensions of data quality and checks | Apply dimensions of data quality (completeness, timeliness, consistency, validity, accuracy) to datasets. | Slot 7 (Q11), Slot 8 (Q12) | Slot 3 (Q78), Slot 4 (Q79) | `ML-1.2-DIMS`: *Data Quality Dimensions & Verification* (Interactive Table) |
| **COMP-1.2.4** | Apply / Evaluate | Recognize common governance gaps & quality issues | Detect anomalies, implausible values, and security gaps in small public health datasets (e.g. Kifaru District). | Slot 9 (Q13), Slot 10 (Q14) | Slot 5 (Q80) | `ML-1.2-ANOM`: *Spotting Data Quality Anomalies* (Branching Scenario) |

---

### Module 2.1: Data Wrangling and Reproducible Workflows

| Competency ID | Bloom's Level | Learning Objective (LO) | Competency Description | Pre-Test Item (Gap) | Knowledge Check (Quiz 4) | Recommended Microlearning Unit |
| :--- | :--- | :--- | :--- | :--- | :--- | :--- |
| **COMP-2.1.1** | Understand | Define data wrangling and its importance in analytics | Define data wrangling and contrast raw vs analysis-ready public health datasets. | *Needed in Pre-Test* | Slot 1 (Q55) | `ML-2.1-INTRO`: *What is Data Wrangling?* (Video + Check) |
| **COMP-2.1.2** | Understand / Apply | Explain core steps in the data wrangling process | Sequence the 5 wrangling stages: Discovery, Structuring, Cleaning, Enriching, and Validating. | *Needed in Pre-Test* | Slot 2 (Q56) | `ML-2.1-STEPS`: *5 Steps of Data Wrangling* (Interactive Sequence) |
| **COMP-2.1.3** | Apply | Describe basic data cleaning techniques | Apply techniques to handle missing values, standardize dates/codes, and resolve implausible outliers. | *Needed in Pre-Test* | Slot 3 (Q57) | `ML-2.1-CLEAN`: *Cleaning Missing & Outlier Data* (Interactive Exercise) |
| **COMP-2.1.4** | Analyze / Create | Recognize the importance of reproducible workflows | Explain principles of reproducibility: script automation (Python/R), version control (Git), and documentation. | *Needed in Pre-Test* | Slot 4 (Q58), Slot 5 (Q59) | `ML-2.1-REPRO`: *Reproducible Workflows & Git* (Column Tutorial) |

---

### Module 2.2: Exploratory Data Analysis (EDA)

| Competency ID | Bloom's Level | Learning Objective (LO) | Competency Description | Pre-Test Item (Gap) | Knowledge Check (Quiz 5) | Recommended Microlearning Unit |
| :--- | :--- | :--- | :--- | :--- | :--- | :--- |
| **COMP-2.2.1** | Understand | Explain what EDA is and why it is important | Define EDA and explain how initial exploration uncovers distributions, outliers, and data integrity issues. | *Needed in Pre-Test* | Slot 1 (Q81) | `ML-2.2-EDA`: *The Purpose of EDA* (Flashcards) |
| **COMP-2.2.2** | Apply / Analyze | Explore single variables & bivariate relationships | Calculate and interpret measures of central tendency, spread, frequency tables, and $2\times 2$ contingency tables. | *Needed in Pre-Test* | Slot 2 (Q82), Slot 3 (Q83) | `ML-2.2-TABLES`: *Building & Interpreting 2x2 Tables* (Interactive Exercise) |
| **COMP-2.2.3** | Evaluate | Explain how EDA reveals remaining data quality issues | Use summary statistics to spot unexpected spikes, negative values, and coding errors before modeling. | *Needed in Pre-Test* | Slot 4 (Q84) | `ML-2.2-CHECK`: *Quality Audit Through EDA* (Case Scenario) |
| **COMP-2.2.4** | Apply | Describe Python scripts for basic wrangling & EDA | Identify core Python tools (Pandas, summary statistics functions) to generate frequency distributions. | *Needed in Pre-Test* | Slot 5 (Q85) | `ML-2.2-PY`: *Python for EDA Basics* (Code & Quiz) |

---

### Module 3.1: Foundations of Data Visualisation

| Competency ID | Bloom's Level | Learning Objective (LO) | Competency Description | Pre-Test Item (Gap) | Knowledge Check (Quiz 6) | Recommended Microlearning Unit |
| :--- | :--- | :--- | :--- | :--- | :--- | :--- |
| **COMP-3.1.1** | Understand | Explain why visualization is important for decisions | Explain cognitive advantages of visual encoding over raw tabular figures in surveillance and decision-making. | *Needed in Pre-Test* | Slot 1 (Q60) | `ML-3.1-VALUE`: *Visualizing for Decisions* (Interactive Presentation) |
| **COMP-3.1.2** | Understand / Analyze | Distinguish between different data types for visual display | Classify variables as nominal, ordinal, discrete, or continuous and identify their visual constraints. | *Needed in Pre-Test* | Slot 2 (Q61) | `ML-3.1-TYPES`: *Data Types & Visual Matching* (Drag-and-Drop) |
| **COMP-3.1.3** | Apply / Create | Select appropriate visualization formats | Select correct visual types (bar charts, histograms, scatter plots, box plots) based on data and question. | *Needed in Pre-Test* | Slot 3 (Q62), Slot 4 (Q63) | `ML-3.1-SELECT`: *Chart Selection Matrix* (Interactive Decision Tree) |
| **COMP-3.1.4** | Evaluate | Interpret key messages and avoid misleading visuals | Audit visual displays for deceptive truncated axes, 3D distortions, and missing baseline contexts. | *Needed in Pre-Test* | Slot 5 (Q64) | `ML-3.1-AUDIT`: *Spotting Misleading Charts* (Image Hotspots) |

---

### Module 3.2: Time Series and Dashboards

| Competency ID | Bloom's Level | Learning Objective (LO) | Competency Description | Pre-Test Item (Gap) | Knowledge Check (Quiz 7) | Recommended Microlearning Unit |
| :--- | :--- | :--- | :--- | :--- | :--- | :--- |
| **COMP-3.2.1** | Understand / Analyze | Explain time series in tracking public health trends | Deconstruct time series into level, overall trend, seasonality, and unusual outbreak anomalies. | *Needed in Pre-Test* | Slot 1 (Q86) | `ML-3.2-TIME`: *Deconstructing Time Series* (Interactive Line Chart) |
| **COMP-3.2.2** | Apply | Identify key features of time-series data | Construct and interpret epidemic curves (epi curves), moving averages, and alert thresholds. | *Needed in Pre-Test* | Slot 2 (Q87) | `ML-3.2-EPICURVE`: *Epi Curves & Thresholds* (Branching Scenario) |
| **COMP-3.2.3** | Analyze / Create | Describe how dashboards combine visual components | Structure dashboards around Key Performance Indicators (KPIs), maps, summary charts, and filters. | *Needed in Pre-Test* | Slot 3 (Q88), Slot 4 (Q89) | `ML-3.2-KPI`: *Essential Dashboard Architecture* (Interactive Exploration) |
| **COMP-3.2.4** | Evaluate | Apply best practices in public health dashboard design | Apply clarity rules: 5-second glance test, minimal cognitive clutter, accessible color palettes, and ethical presentation. | *Needed in Pre-Test* | Slot 5 (Q90) | `ML-3.2-RULES`: *Dashboard Usability & Ethics* (Audit Checklist) |

---

### Module 4.1: Advanced Analytics: Generalized Linear Models (GLMs)

| Competency ID | Bloom's Level | Learning Objective (LO) | Competency Description | Pre-Test Item (Gap) | Knowledge Check (Quiz 8) | Recommended Microlearning Unit |
| :--- | :--- | :--- | :--- | :--- | :--- | :--- |
| **COMP-4.1.1** | Understand | Define GLMs and their role in public health analytics | Define Generalized Linear Models and explain how they quantify risk factor relationships beyond simple trends. | *Needed in Pre-Test* | Slot 1 (Q65) | `ML-4.1-GLM`: *What Are GLMs?* (Interactive Overview) |
| **COMP-4.1.2** | Analyze | Distinguish between logistic and Poisson regression | Select logistic regression for binary outcomes (sick/healthy) and Poisson regression for counts/rates (cases per 10k). | *Needed in Pre-Test* | Slot 2 (Q66), Slot 3 (Q67) | `ML-4.1-DIFF`: *Logistic vs Poisson Regression* (Scenario Sorting) |
| **COMP-4.1.3** | Apply / Interpret | Explain how GLMs estimate risks, counts, and rates | Interpret odds ratios (OR), relative risks (RR), and incident rate ratios (IRR) with confidence intervals. | *Needed in Pre-Test* | Slot 4 (Q68), Slot 5 (Q69) | `ML-4.1-OR`: *Interpreting Odds & Risk Ratios* (Interactive Calculation) |
| **COMP-4.1.4** | Evaluate | Identify public health scenarios where GLMs support decisions | Formulate GLM specifications for vaccine effectiveness, hospital readmissions, and environmental exposures. | *Needed in Pre-Test* | Slot 6 (Q70) | `ML-4.1-CASE`: *GLMs in Public Health Decisions* (Case Study) |

---

### Module 4.2: Applied Analytics: Machine Learning

| Competency ID | Bloom's Level | Learning Objective (LO) | Competency Description | Pre-Test Item (Gap) | Knowledge Check (Quiz 9) | Recommended Microlearning Unit |
| :--- | :--- | :--- | :--- | :--- | :--- | :--- |
| **COMP-4.2.1** | Understand | Define Machine Learning & contrast with traditional statistics | Contrast hypothesis-driven statistical modeling (explanation) with data-driven machine learning (prediction). | *Needed in Pre-Test* | Slot 1 (Q96) | `ML-4.2-STATS`: *Statistics vs Machine Learning* (Comparison Table) |
| **COMP-4.2.2** | Analyze | Distinguish classification vs regression tasks in ML | Categorize public health ML problems into classification (discrete labels) and regression (continuous values). | *Needed in Pre-Test* | Slot 2 (Q97) | `ML-4.2-TASKS`: *Classification vs Regression* (Categorization Exercise) |
| **COMP-4.2.3** | Understand / Apply | Explain decision trees and random forests | Explain how decision trees split data using if-then rules and how random forests aggregate multiple trees. | *Needed in Pre-Test* | Slot 3 (Q98) | `ML-4.2-TREES`: *Decision Trees & Random Forests* (Visual Walkthrough) |
| **COMP-4.2.4** | Evaluate | Describe validation, bias checks, and overfitting | Explain train/test split, detect overfitting, and evaluate equity and demographic bias in health predictive models. | *Needed in Pre-Test* | Slot 4 (Q99), Slot 5 (Q100)| `ML-4.2-EVAL`: *Model Validation & Bias Checks* (Interactive Audit) |

---

### Module 4.3: Advanced Methods: Natural Language Processing (NLP)

| Competency ID | Bloom's Level | Learning Objective (LO) | Competency Description | Pre-Test Item (Gap) | Knowledge Check (Quiz 10) | Recommended Microlearning Unit |
| :--- | :--- | :--- | :--- | :--- | :--- | :--- |
| **COMP-4.3.1** | Understand | Define NLP & its role in transforming unstructured text | Explain how NLP unlocks insights from narrative clinical notes, community rumors, and epidemic bulletins. | *Needed in Pre-Test* | Slot 1 (Q91) | `ML-4.3-NLP`: *Unstructured Text in Public Health* (Interactive Overview) |
| **COMP-4.3.2** | Apply | Describe the standard text preprocessing pipeline | Sequence core preprocessing steps: cleaning, lowercasing, tokenization, stop-word removal, and normalization. | *Needed in Pre-Test* | Slot 2 (Q92) | `ML-4.3-PIPE`: *The NLP Preprocessing Pipeline* (Step-by-Step Tool) |
| **COMP-4.3.3** | Analyze | Distinguish Bag-of-Words and TF-IDF representations | Compare frequency counting (Bag-of-Words) with inverse document frequency weighting (TF-IDF). | *Needed in Pre-Test* | Slot 3 (Q93), Slot 4 (Q94) | `ML-4.3-TFIDF`: *Bag-of-Words vs TF-IDF* (Interactive Vectorizer) |
| **COMP-4.3.4** | Evaluate / Apply | Identify public health applications of NLP | Evaluate NLP implementations for early outbreak signal detection, sentiment monitoring, and automated report triage. | *Needed in Pre-Test* | Slot 5 (Q95) | `ML-4.3-OUTBREAK`: *NLP for Outbreak Surveillance* (Branching Simulation) |

---

## 3. Pre-Assessment (Placement Test) Expansion Requirement

Our database audit revealed that **Quiz 1 (Placement Test)** currently contains 10 questions covering *only* Module 1.1 (Slots 1–3) and Module 1.2 (Slots 4–10).

To enable true course-wide adaptive routing and content bypass ($\ge 20\%$), Quiz 1 must be expanded from 10 to **27 questions** (3 questions per module):

```
CURRENT QUIZ 1:  [ Mod 1.1 (3) ][ Mod 1.2 (7) ] ── (0 questions for Modules 2.1 through 4.3)

EXPANDED QUIZ 1: [ 1.1 (3) ][ 1.2 (3) ][ 2.1 (3) ][ 2.2 (3) ][ 3.1 (3) ][ 3.2 (3) ][ 4.1 (3) ][ 4.2 (3) ][ 4.3 (3) ]
                 ────────────────────────────── 27 Balanced Questions ──────────────────────────────────
```

When a learner scores $\ge 90\%$ on a module's 3 diagnostic questions during onboarding, the engine flags that module as **MASTERED** and activates the **Content Bypass** pathway.

---

## 4. Multi-Signal Evidence Weighting Formula

For any competency $c$, its updated proficiency score $P(c) \in [0.0, 1.0]$ is computed dynamically as:

$$P(c) = w_{\text{pre}} S_{\text{pre}}(c) + w_{\text{quiz}} S_{\text{quiz}}(c) + w_{\text{h5p}} S_{\text{h5p}}(c) - w_{\text{ai}} N_{\text{ai\_gaps}}(c)$$

Where:
- $w_{\text{pre}} = 0.25$ (Diagnostic signal)
- $w_{\text{quiz}} = 0.45$ (Formative quiz performance on linked item)
- $w_{\text{h5p}} = 0.30$ (Topic-level interactive task performance)
- $w_{\text{ai}} = 0.05$ per inquiry (Soft uncertainty penalty, capped at $0.15$)

### Gap Detection Thresholds for A/B Testing
- **Variant A (Fast Remediation, 3 Points):** Gap triggered if $P(c) < 0.70$ OR $2 \text{ Quiz Failures} + 1 \text{ AI Inquiry}$.
- **Variant B (Conservative Remediation, 5 Points):** Gap triggered only if $P(c) < 0.65$ AND ($3 \text{ Quiz Failures} + 1 \text{ AI Inquiry}$).
