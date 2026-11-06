# Microlearning review sheet: Data Science & Analytics for Public Health

Built 2026-11-06 by `course-setup/build_microlearning.php` (D-24). Each unit is a short review shown to a learner whose Knowledge Check score is below the remediation threshold. Every line is copied word for word from the module's SCORM text; nothing was written by a model. Units stay hidden from learners until the content owner approves them (Gate 2.3).

For each unit: check that the key points and terms are correct, useful and complete enough for a review, and mark a decision.

## Review: Module 1.1 — Foundations of Health Data Literacy

Source: SCORM "Module 1.1 Foundations of Health Data Literacy". Line numbers refer to the text the assistant imported from it. Every line below is copied word for word.

**Learning objectives** (3)

- define data literacy and core concepts related to health data _(line 17)_
- distinguish between the main types of health data _(line 18)_
- identify typical internal and external data sources used for key public health questions _(line 19)_

**Key points** (10)

| Heading | Text | Line |
|---|---|---|
| Foundations of Data Literacy | Data literacy is the ability to read, understand, work with, and communicate data. But it is not limited to statistics or programming. | 25 |
| Data Literacy for Public Health Decision-Making | Every important public health decision is based at least in part on data – for example, deciding where to deploy outreach teams, which districts to prioritise for surveillance, or whether an outbreak is under control. If those using the data do not fully understand it, mistakes are made. | 45 |
| Misinterpreting trends | Example: Monthly data show measles coverage in one district dropping from 92% to 55%, so the team assumes services have collapsed and reallocates staff. Later, they discover several facilities reported late and left fields blank in the new electronic system. | 49 |
| Core Concepts and Terms | To build a common language for the rest of the course, this section introduces three core ideas: health data, datasets, variables and indicators, and the health data lifecycle. | 64 |
| Health Data | Health data includes all measurable information related to the health of individuals, communities, or health systems. | 68 |
| The Health Data Lifecycle | Health data does not simply appear on a dashboard. It passes through a series of stages, often called the data lifecycle. | 113 |
| Reflection | Before we move on to the next topic, let’s summarise what we’ve learnt so far. | 140 |
| Types of Health Data by Structure | Health data can be organised in different ways. Its structure affects how easily it can be stored, linked, and analysed. | 177 |
| Structured Data | Structured data is organised into a clear format, usually rows and columns with predefined variables. It is the easiest to analyse with standard tools. | 181 |
| Types of Health Data by Function | Health data can also be grouped by what it is used for in the health system. The same dataset may serve more than one function, but it is helpful to understand the main categories. | 204 |

**Key terms** (9)

| Term | Card back | Line |
|---|---|---|
| Data literacy | Data literacy is the ability to read, understand, work with, and communicate data. | 25 |
| Indicators | Indicators are usually derived from one or more datasets. | 104 |
| Structured data | Structured data is organised into a clear format, usually rows and columns with predefined variables. | 181 |
| Clinical data | Clinical data are collected during the care of individual patients. | 208 |
| Surveillance data | Surveillance data are collected to monitor diseases, conditions, or events at population level. | 215 |
| Population-based and survey data | Population-based and survey data are collected through structured surveys or censuses from a sample or the whole population, rather than only from those who use services. | 229 |
| Internal data sources | Internal data sources are generated within your own organisation or health system. | 241 |
| Internal data | Internal data are often more detailed and more frequently updated. | 246 |
| External data sources | External data sources are produced outside your immediate organisation, even if they relate to your country or programmes. | 248 |

Owner decision: ☐ approve as is ☐ approve with the changes noted ☐ reject

## Review: Module 1.2 — Data Governance and Quality Assurance

Source: SCORM "Module 1.2 Data Governance and Quality Assurance". Line numbers refer to the text the assistant imported from it. Every line below is copied word for word.

**Learning objectives** (4)

- define data governance and data quality in the public health context _(line 16)_
- explain why good data governance and high data quality are essential for sound public health decision-making _(line 17)_
- describe key dimensions of data quality and simple checks that can be used to assess them _(line 18)_
- recognise common governance gaps and data quality issues in small public health datasets _(line 19)_

**Key points** (8)

| Heading | Text | Line |
|---|---|---|
| Data Governance in Public Health | Data governance refers to the organised system that guides how health data is handled from the moment it is collected to the moment it is archived. It defines who is responsible for data, who is allowed to access it, and what standards must be followed to protect it. | 25 |
| Core Principles of Data Governance | Although institutions vary, most governance frameworks rest on five core principles that shape how health data is managed. Explore the graphic to see what each principle means in practice. | 33 |
| Data stewardship and ownership | Every dataset needs a clear “home” and someone who is accountable for its accuracy and maintenance. When ownership is unclear, errors accumulate and no one feels responsible for correcting them. | 37 |
| Common Governance Risks and Their Consequences | When governance systems are weak or unclear, problems appear quickly. The main risks fall into the following 2 categories. | 48 |
| Data Quality and Quality Assurance | When we say that data are “good enough” for decision-making, we are talking about data quality. Data quality is the degree to which a dataset can be trusted to reflect reality and support sound conclusions. | 88 |
| Key Data Quality Dimensions | To judge whether data are truly reliable, public health teams rely on a small set of essential data quality dimensions. | 92 |
| Governance and Quality in Action | Imagine you are working in the district health office of Kifaru District. Your team is reviewing data from the routine immunisation programme for children under one year of age. | 160 |
| Key Takeaways | Data governance sets the rules, roles, and safeguards that keep health data trustworthy, ethical, and secure. | 215 |

**Key terms** (3)

| Term | Card back | Line |
|---|---|---|
| Data governance | Data governance refers to the organised system that guides how health data is handled from the moment it is collected to the moment it is archived. | 25 |
| Accuracy | Accuracy is about how close recorded values are to the truth. | 96 |
| Data quality | Data quality is about how well data reflect reality, using dimensions such as accuracy, completeness, timeliness, consistency, and validity. | 216 |

Owner decision: ☐ approve as is ☐ approve with the changes noted ☐ reject

## Review: Module 2.1 — Data Wrangling and Reproducible Workflows

Source: SCORM "Module 2.1 Data Wrangling and Reproducible Workflows". Line numbers refer to the text the assistant imported from it. Every line below is copied word for word.

**Learning objectives** (4)

- define data wrangling and its importance in public health data analysis _(line 15)_
- explain the core steps in the data wrangling process, from discovery to validation _(line 16)_
- describe basic data cleaning techniques and how they improve data quality _(line 17)_
- recognise the importance of reproducible workflows and their role in ensuring transparency and consistency in data analysis _(line 18)_

**Key points** (6)

| Heading | Text | Line |
|---|---|---|
| Key tools for data wrangling | Data wrangling can be carried out with many different tools. The choice depends on the size of the dataset, the experience of the team, and how important reproducibility is for the task. | 62 |
| Spreadsheets | Many public health staff use spreadsheets such as Excel for small to medium-sized datasets. Spreadsheets are familiar and flexible, and they allow simple sorting, filtering, and formula-based corrections. | 66 |
| Reproducible workflows | A reproducible workflow is a way of organising your data work so that the same results can be obtained again, either by you at a later time or by someone else. It means that the steps from raw data to final tables and figures are not just done, but recorded and repeatable. | 98 |
| Transparency | Every step in the process should be written down. This allows others to understand how and why the analysis was performed in a particular way. | 121 |
| Data Wrangling Workflow in Action | A research team studies the relationship between age and resting blood pressure by merging data from a mobile app and electronic health records (EHR). The dataset below contains invalid, missing, and inconsistent values typical of real-world health data. | 133 |
| Key Takeaways | Data wrangling transforms raw, messy data into clean, structured datasets ready for analysis. | 293 |

**Key terms** (3)

| Term | Card back | Line |
|---|---|---|
| Data wrangling | Data wrangling is the process of transforming raw, messy data into an analysis-ready dataset. | 24 |
| Cleaning | Cleaning is the core of data wrangling, where you address key issues with the data set to make sure your analysis is not distorted. | 38 |
| Reproducible workflow | A reproducible workflow is a way of organising your data work so that the same results can be obtained again, either by you at a later time or by someone else. | 98 |

Owner decision: ☐ approve as is ☐ approve with the changes noted ☐ reject

## Review: Module 2.2 — Exploratory Data Analysis and Implementation Python

Source: SCORM "Module 2.2 Exploratory Data Analysis and Implementation Python". Line numbers refer to the text the assistant imported from it. Every line below is copied word for word.

**Learning objectives** (4)

- explain what exploratory data analysis (EDA) is and why it is important in public health _(line 15)_
- describe how to explore single variables and simple relationships between variables using basic summaries and tables _(line 16)_
- explain how EDA can reveal remaining data quality issues and guide further analysis _(line 17)_
- describe, in simple terms, how Python scripts can be used to carry out basic data wrangling, EDA, and produce summary statistics such as 2×2 tables _(line 18)_

**Key points** (4)

| Heading | Text | Line |
|---|---|---|
| Exploratory Data Analysis (EDA) | Once data have been cleaned and organised, the next step is to understand what story they tell. Exploratory Data Analysis (EDA) can help with that. | 24 |
| Exploring relationships between variables | After looking at each variable on its own, the next step in EDA is to see how two variables move together. | 87 |
| From manual steps to code | Up to now, you have worked with data wrangling and EDA mostly in Excel. You have seen how to spot problems, fix them, and then explore the cleaned blood-pressure dataset. | 184 |
| Key Takeaways | EDA starts after data wrangling and helps you understand what your cleaned dataset actually shows. | 284 |

**Key terms** (0)

| Term | Card back | Line |
|---|---|---|

Owner decision: ☐ approve as is ☐ approve with the changes noted ☐ reject

## Review: Module 3.1 — Foundations of Data Visualisation

Source: SCORM "Module 3.1: Foundations of Data Visualisation". Line numbers refer to the text the assistant imported from it. Every line below is copied word for word.

**Learning objectives** (4)

- explain why data visualisation is important for public health decisions _(line 15)_
- distinguish between different data types _(line 16)_
- describe which types of visualisation are appropriate for different data types and public health questions _(line 17)_
- interpret key messages from these visualisations _(line 18)_

**Key points** (10)

| Heading | Text | Line |
|---|---|---|
| Visualisation and Data Types in Public Health | Public health work generates large volumes of information: case counts, admissions, vaccination coverage, stock levels, survey responses and more. Reading these values only as tables is slow and makes important patterns easy to miss. | 24 |
| How Visualisation Supports Public Health Decisions | Visualisations are not only about “making data look nice”. They support concrete decisions at different levels of the health system. | 40 |
| Detecting problems early | A simple line chart with temporal data (for example weekly case counts) can reveal an unusual increase that signals an outbreak. A heat map of wards can show where bed occupancy is consistently high. | 44 |
| Core Visualisation Types | In this section, you will see which chart types work best for different questions and data types. As you read, note what kind of data sits on each axis (or is encoded by colour or area): categorical, numerical, temporal or spatial. | 98 |
| Bar charts | Bar charts are one of the most useful tools in public health. They work best when you have categorical data on one axis (for example region, age group, sex) and numerical data on the other (for example number of cases, percentage coverage). | 106 |
| Histograms | A histogram groups values into intervals and shows how many observations fall into each interval. For outpatient data, for example, a histogram of daily visit counts could reveal whether most days have a similar number of visits or whether there are many very quiet or very busy days. | 153 |
| Relationship Charts | Sometimes we want to know whether two numerical variables are related – for example, whether age is associated with blood pressure, or whether BMI is linked to physical activity. There are 2 main tools that are used for this purpose. | 171 |
| Scatter Plots | A scatter plot is the basic tool. Each point represents one observation (for example a person), positioned according to its value on the x-axis and y-axis (both numerical). | 175 |
| Choropleth Map | The most common type is the choropleth map, where each administrative unit – such as a district or province – is shaded according to the value of an indicator, for example immunisation coverage or incidence of a disease. Darker or more intense colours often indicate higher values. | 194 |
| Isopleth Map | Another approach uses continuous colour fields to show phenomena that are not constrained by borders, such as estimated disease risk or air pollution. These maps are helpful when the underlying process is not confined to administrative borders. | 197 |

**Key terms** (2)

| Term | Card back | Line |
|---|---|---|
| Discrete data | Discrete data are counts, such as number of malaria cases, births or outpatient visits. | 66 |
| Bar charts | Bar charts are one of the most useful tools in public health. | 106 |

Owner decision: ☐ approve as is ☐ approve with the changes noted ☐ reject

## Review: Module 3.2 — Time-Series and Dashboards

Source: SCORM "Module 3.2: Time-Series and Dashboards". Line numbers refer to the text the assistant imported from it. Every line below is copied word for word.

**Learning objectives** (4)

- explain the importance of time-series analysis in tracking public health trends and interventions _(line 15)_
- identify key features of time-series data _(line 16)_
- describe how dashboards combine multiple visualisations to support public health decision-making _(line 17)_
- apply best practices in dashboard design to ensure clarity, usability, and ethical data representation _(line 18)_

**Key points** (8)

| Heading | Text | Line |
|---|---|---|
| Time Series in Practice | If you paid attention in the previous module, you will appreciate that the main visual tool for time series has to be the line chart. Time goes on the horizontal axis, the indicator on the vertical axis, and a line connects the values. | 41 |
| Moving average | Adding a moving average means, instead of only plotting each weekly value, you also plot an average of the last few weeks. This smoothed line reduces the effect of random ups and downs and makes the underlying trend easier to see. | 45 |
| Dashboards and Interactive Reporting | A dashboard is a single view that brings together the most important indicators for a specific purpose. Instead of showing all the data available in a system, it highlights a small set of measures that help a user monitor performance, spot problems and make decisions quickly. | 104 |
| Operational dashboards | Support day-to-day monitoring, for example a district officer checking this week’s case notifications or vaccine stocks. | 110 |
| KPI | KPIs are often displayed as numbers, gauges or small summary charts so that users can immediately see whether performance is on, above or below target. | 124 |
| Best Practice Rules for Public Health Dashboards | Health managers and policy-makers often only have a few seconds to glance at a dashboard. The more effort it takes to understand a visual, the less likely it is to be used. | 159 |
| Summary and Reflection | In this module, you’ve learned how to use time-series analysis and dashboards to make sense of public health data. | 208 |
| Key Takeaways | Time-series analysis helps track changes in public health data over time, revealing trends, seasonality, and sudden shifts. | 213 |

**Key terms** (2)

| Term | Card back | Line |
|---|---|---|
| Dashboard | A dashboard is a single view that brings together the most important indicators for a specific purpose. | 104 |
| KPIs | KPIs are often displayed as numbers, gauges or small summary charts so that users can immediately see whether performance is on, above or below target. | 124 |

Owner decision: ☐ approve as is ☐ approve with the changes noted ☐ reject

## Review: Module 4.1 — Generalized Linear Models (GLMs)

Source: SCORM "Module 4.1: Generalized Linear Models (GLMs)". Line numbers refer to the text the assistant imported from it. Every line below is copied word for word.

**Learning objectives** (4)

- define Generalised Linear Models (GLMs) and their role in public health _(line 15)_
- distinguish between logistic regression and Poisson regression based on the types of outcomes they model _(line 16)_
- prepare abstracts, posters, or slides that effectively communicate study findings _(line 17)_
- adapt scientific content into plain language suitable for non-technical audiences _(line 18)_

**Key points** (3)

| Heading | Text | Line |
|---|---|---|
| Logistic Regression | Logistic regression is a type of GLM used when the outcome we are trying to predict has only two possible values– like yes/no, true/false, or success/failure. For example, it could be predicting whether someone gets sick or not, based on certain factors like age, lifestyle, or vaccination status. | 51 |
| Poisson Regression | Poisson regression is used when we want to predict how many times something will happen – like the number of malaria cases or hospital admissions – over a certain period of time or in a specific area. It is often used for count data, where the outcome is a number. | 77 |
| Key Takeaways | You’ve now been introduced to Generalised Linear Models (GLMs) – what they are, the types of outcomes they can handle, and how logistic and Poisson regression help you estimate risks, counts, and rates in public health. | 197 |

**Key terms** (2)

| Term | Card back | Line |
|---|---|---|
| Logistic regression | Logistic regression is a type of GLM used when the outcome we are trying to predict has only two possible values– like yes/no, true/false, or success/failure. | 51 |
| Poisson regression | Poisson regression is used when we want to predict how many times something will happen – like the number of malaria cases or hospital admissions – over a certain period of time or in a specific area. | 77 |

Owner decision: ☐ approve as is ☐ approve with the changes noted ☐ reject

## Review: Module 4.2 — Machine Learning (ML) for Public Health

Source: SCORM "Module 4.2: Machine Learning (ML) for Public Health". Line numbers refer to the text the assistant imported from it. Every line below is copied word for word.

**Learning objectives** (4)

- define Machine Learning and describe how it complements traditional statistical methods in public health _(line 15)_
- distinguish supervised learning problems as classification or regression tasks, based on the type of outcome _(line 16)_
- explain in simple terms how decision trees and random forests learn from past data to make predictions _(line 17)_
- describe why validation, bias checks, and interpretability are important when applying Machine Learning models in public health _(line 18)_

**Key points** (7)

| Heading | Text | Line |
|---|---|---|
| Traditional statistics | Traditional statistics usually start with a specific question or hypothesis, choose a suitable model, and then interpret the results to understand relationships between variables. The focus is on explanation and interpretability, with predictions derived from clearly understood relationships between variables. | 29 |
| Decision Trees and Random Forests | Decision trees and random forests are some of the most widely used machine learning models in public health. Both are examples of supervised learning, where the model learns from past data with known outcomes to make predictions for new cases. | 45 |
| Training (learning) phase | You give the algorithm many past examples with inputs and known outcomes. | 60 |
| Random forests – many trees, one stable decision | A random forest is simply a large group of decision trees (often hundreds) that work together to make a more reliable prediction. For this to work, it is important that the generated trees are not identical. | 72 |
| Biases | Models can also reflect and even amplify existing inequalities. If rural facilities, certain age groups, or women are under-represented in the training data, predictions for these groups may be less accurate or systematically biased. | 122 |
| Interpretability | Complex models can be hard to explain. If clinicians and managers do not understand why a model flags a patient or district as “high risk”, they may not trust or appropriately use its output. | 128 |
| Key Takeaways | You’ve now been introduced to Machine Learning – how it differs from and complements GLMs, how it can be used to turn past data into practical risk scores and forecasts. You’ve also seen why validation, bias checks, interpretability, and regular updating are essential to keep these models safe and reliable for public health use. | 136 |

**Key terms** (2)

| Term | Card back | Line |
|---|---|---|
| Decision trees and random forests | Decision trees and random forests are some of the most widely used machine learning models in public health. | 45 |
| Random forest | A random forest is simply a large group of decision trees (often hundreds) that work together to make a more reliable prediction. | 72 |

Owner decision: ☐ approve as is ☐ approve with the changes noted ☐ reject

## Review: Module 4.3 — Natural Language Processing (NLP) in Public Health

Source: SCORM "Module 4.3: Natural Language Processing (NLP) in Public Health". Line numbers refer to the text the assistant imported from it. Every line below is copied word for word.

**Learning objectives** (4)

- define Natural Language Processing (NLP) and describe its role in turning unstructured text into usable data for public health _(line 15)_
- describe a simple NLP pipeline that converts raw text into numerical representations for analysis _(line 16)_
- distinguish between Bag-of-Words and TF-IDF as basic methods for representing text as numbers _(line 17)_
- identify key public health applications of NLP, including event detection, topic clustering, and sentiment or perception monitoring _(line 18)_

**Key points** (5)

| Heading | Text | Line |
|---|---|---|
| How NLP Turns Text into Data | To make text useful for analysis, NLP follows a few simple steps. | 38 |
| Basic Text Representations | In public health applications, text representations act like a numeric fingerprint for each document that models can use for classification, clustering, or event detection. Two very common and very useful approaches are Bag-of-Words (BoW) and Term Frequency–Inverse Document Frequency (TF-IDF). | 85 |
| Bag-of-Words – counting words | The Bag-of-Words approach ignores grammar and word order and simply counts how often each word appears in a text. | 88 |
| TF-IDF – highlighting the most informative terms | A limitation of pure counting is that very common words (“clinic”, “patient”, “report”) appear in almost every text and can dominate the counts. Term Frequency–Inverse Document Frequency (TF-IDF) adjusts for this. | 104 |
| Key Takeaways | You’ve now been introduced to Natural Language Processing (NLP) and how methods like Bag-of-Words and TF-IDF feed into practical applications. | 207 |

**Key terms** (1)

| Term | Card back | Line |
|---|---|---|
| Natural Language Processing (NLP) | Natural Language Processing (NLP) is a set of methods that help computers work with this kind of unstructured text. | 23 |

Owner decision: ☐ approve as is ☐ approve with the changes noted ☐ reject

