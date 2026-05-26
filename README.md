# A-FIRST
## AI-Enabled Frontline Integrated Readiness and Skills Training

**Project Lead:** Dr. Abel Mestie Mekonnen, Public Health Informatics Fellow, Africa CDC

**Fellowship Period:** 2025-2027

---

## What Is A-FIRST?

A-FIRST integrates AI into Africa CDC's existing Moodle-based learning management system to strengthen public health informatics competencies among frontline health workers across Africa. The project delivers three AI-enhanced learning layers:

| Layer | Name | Description | Status |
|-------|------|-------------|--------|
| BASIC | AI Chat Assistant | Learners ask questions and receive instant AI-powered responses inside Moodle courses | In development |
| ADAPTIVE | Personalized Learning Paths | AI adapts course content and sequencing to individual learner competency gaps | Planned |
| READINESS | Epidemic Preparedness Simulator | AI-driven simulation of outbreak scenarios for decision-making training | Future phase |

---

## Learning Framework

*This project applies Developmental Evaluation (DE) principles throughout. The learning framework below maps the key challenges, opportunities, and learning needs guiding A-FIRST development. It is a living document updated at each sprint review.*

### What We Need to Pay Attention To

#### 1. Technical Integration
- Moodle version compatibility with AI plugins across Africa CDC infrastructure
- Local model performance vs cloud API fallback decisions
- FHIR and OpenHIE alignment for health data interoperability
- Data sovereignty: where learner data is stored and processed

#### 2. Learner Experience
- Baseline digital literacy of AES PHI fellows and frontline health workers
- Language and multilingual support needs across Member States
- Response quality and hallucination risk in AI-generated answers
- Accessibility in low-bandwidth environments

#### 3. Content Governance
- Alignment with Africa CDC approved Public Health Informatics curriculum
- Source grounding: AI must answer from approved content, not open web
- Curriculum update cycles and version control of training materials
- SCORM packaging quality of existing course library

#### 4. Institutional Readiness
- Africa CDC Moodle admin access and IT governance processes
- GPU server procurement for production AI deployment
- Ethical clearance timelines from Africa CDC and partner institutions
- Stakeholder buy-in from US CDC advisory team and MoH counterparts

#### 5. Sustainability and Scale
- Avoiding donor-dependent infrastructure: local model preference over paid API
- Capacity building for Africa CDC IT team to maintain the system post-fellowship
- Evidence generation for continental scale-up case

---

## Repository Structure

```
a-first/
├── README.md                          # This file: project overview and learning framework
├── moodle/
│   └── blocks/
│       └── ollama_chat/               # Custom Moodle AI chat block
│           ├── version.php
│           ├── block_ollama_chat.php
│           ├── db/
│           │   └── access.php
│           └── lang/
│               └── en/
│                   └── block_ollama_chat.php
├── docs/
│   ├── sprint-reviews/
│   │   └── sprint-01-review-2026-05-22.pptx
│   ├── concept-note/
│   │   └── AFIRST_Concept_Note.docx
│   └── project-plan/
│       └── AFIRST_Project_Plan.docx
├── config/
│   └── ollama-setup.md                # Ollama installation and model configuration guide
└── .github/
    └── ISSUE_TEMPLATE/
        └── sprint-issue.md
```

---

## Technical Stack

| Component | Technology | Notes |
|-----------|------------|-------|
| LMS | Moodle 4.5 LTS | Local dev on Windows XAMPP bundle |
| AI Model (dev) | Ollama llama3.2:1b | Local. No internet dependency. No per-query cost. |
| AI Model (production fallback) | Claude API (Anthropic) | OpenAI-compatible endpoint |
| Custom Plugin | block_ollama_chat | 4-file Moodle block. Full admin control. |
| OS (dev) | Windows 10/11 | XAMPP bundle |
| OS (production target) | Africa CDC Linux servers | GPU-enabled. Procurement pending. |
| Standards | FHIR R4, OpenHIE, DHIS2 | Interoperability alignment |

---

## Sprint Progress

| Sprint | Focus | Status | Key Output |
|--------|-------|--------|------------|
| Sprint 1 | Infrastructure and first AI demonstration | Complete | Moodle 4.5 + Ollama + block_ollama_chat running locally |
| Sprint 2 | Learner profile and ethical clearance | In progress | Cohort survey instrument, ethics application |
| Sprint 3 | AI subsystem configuration | Planned | Production Moodle AI plugin configured |
| Sprint 4 | RAG implementation | Planned | AI grounded in Africa CDC approved content |
| Sprint 5 | ADAPTIVE layer | Planned | Personalized learning paths |
| Sprint 6 | Pilot launch | Planned | AES PHI fellows cohort |
| Sprint 7 | Evaluation and documentation | Planned | DE synthesis report |

---

## Developmental Evaluation Approach

This project uses Developmental Evaluation (Patton, 2008) as its primary evaluation methodology. DE is appropriate here because:

- The initiative operates in a complex, emergent environment with multiple stakeholders across Africa CDC, US CDC, and Member State health systems
- The technical path is not fully predictable: integration decisions are made in real time based on what the infrastructure supports
- The goal is real-time learning and course correction, not summative judgment

### DE Practices Applied

**Orienting:** Each sprint begins with a landscape review of what has changed in the technical environment, policy context, or stakeholder landscape since the last sprint.

**Watching:** The project lead observes key developmental moments, group dynamics, and technical integration barriers as they emerge and logs them in the Jira board under the relevant sprint.

**Sense-Making:** Sprint retrospectives are collective sense-making sessions with the advisory team. Findings inform the next sprint's learning framework.

**Intervening:** When barriers arise (e.g., Moodle version incompatibility, RAM constraints), the project lead intervenes with evidence-based pivots documented transparently in the sprint review record.

---

## Ethical Commitments

- Learner data stays within Africa CDC infrastructure. No learner data sent to external APIs without explicit consent.
- AI responses are grounded in approved Africa CDC content from Sprint 4 onward. Open web responses flagged as unverified.
- All AI outputs are monitored for hallucination and bias. A human review layer is built into Sprint 6 pilot.
- Solutions are designed to work in low-bandwidth, low-resource settings across African Member States, not only urban-connected facilities.

---

## Getting Started (Local Development)

### Prerequisites
- Windows 10/11
- Moodle 4.5 Windows package from download.moodle.org
- Ollama installed from ollama.com
- llama3.2:1b model pulled

### Installation

1. Install Moodle 4.5 to `C:\moodle`
2. Start Moodle via `Start Moodle.exe`
3. Copy `moodle/blocks/ollama_chat` to `C:\moodle\server\moodle\blocks\`
4. Pull the AI model:
```bash
ollama pull llama3.2:1b
```
5. Log into Moodle at `localhost/moodle` as admin
6. Go to Site Administration, Notifications to trigger plugin installation
7. Add the A-FIRST AI Assistant block to any course

### Test the Integration
```bash
curl -X POST http://localhost:11434/api/generate \
  -H "Content-Type: application/json" \
  -d '{"model":"llama3.2:1b","prompt":"What is DHIS2?","stream":false}'
```

A valid JSON response with a `response` key confirms Ollama is running correctly.

---

## References

- Dozois, E., Langlois, M., & Blanchet-Cohen, N. (2010). DE 201: A Practitioner's Guide to Developmental Evaluation. J.W. McConnell Family Foundation.
- Patton, M.Q. (2008). Utilization-Focused Evaluation. Sage Publications.
- Africa CDC Digital Health and Information Systems Department Strategic Plan 2026
- WHO Guidelines on AI for Health (2021)

---

*This repository is maintained by Dr. Abel Mestie Mekonnen as part of the Africa CDC Public Health Informatics Fellowship 2026.*
